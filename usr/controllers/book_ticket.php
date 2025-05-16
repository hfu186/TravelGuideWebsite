<?php
session_start();
require('../../server/connectdb.php');

if (!isset($_SESSION['usr_id'])) {
    echo ("Bạn cần đăng nhập để đặt tour");
    header('location: log_in.php');
    exit();
}

$required_fields = ['ticket_id', 'booking_date', 'quantity'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo("Vui lòng điền đầy đủ thông tin");
        header('location:tickets.php');
        exit();
    }
}


$ticket_id = intval($_POST['ticket_id']);

$user_id = $_SESSION['usr_id'];
$ticket_date = date('Y-m-d', strtotime($_POST['booking_date']));
$quantity = intval($_POST['quantity']);



try {
 
    $conn->beginTransaction();
    $stmt = $conn->prepare("SELECT price FROM Tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        throw new Exception("Vé không tồn tại");
    }

    $total_price = $ticket['price'] * $quantity;

   
    $stmt = $conn->prepare("INSERT INTO ticket_bookings
        (usr_id,ticket_id, booking_date, quantity, total_price, status) 
        VALUES (?,?,?, ?, ?,'pending')");
    
    $stmt->execute([
        $user_id,
        $ticket_id,
        $ticket_date,
        $quantity,
        $total_price
    ]);
    $booking_id = $conn->lastInsertId();

    $conn->commit();

    header("Location: payment.php?type=ticket&booking_id=" . $booking_id);
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    die("Lỗi đặt vé: " . $e->getMessage());
}
?>