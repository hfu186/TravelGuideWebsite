<?php
session_start();
require('../../server/connectdb.php');
if (!isset($_SESSION['usr_id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để đặt hướng dẫn viên";
    header('location: log_in.php');
    exit();
}


$required_fields = ['guide_id', 'booking_date', 'quantity'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin";
        header('location: detail_guide.php?id=' . $_POST['guide_id']);
        exit();
    }
}

$guide_id = intval($_POST['guide_id']);
$user_id = $_SESSION['usr_id'];
$booking_date = $_POST['booking_date'];
$days = intval($_POST['quantity']);


if ($days < 1) {
    $_SESSION['error'] = "Số ngày phải lớn hơn 0";
    header('location: detail_guide.php?id=' . $guide_id);
    exit();
}

try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("CALL check_guide_availability(?, ?, ?)");
    $stmt->execute([$guide_id, $booking_date, $days]);
    $availability = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor(); 
    if ($availability['next_available_date'] !== null) {
        $_SESSION['error'] = "Hướng dẫn viên không khả dụng vào ngày này. "; 
        header('location: detail_guide.php?id=' . $guide_id);
        exit();
    }

    $stmt = $conn->prepare("SELECT price FROM Tour_Guides WHERE guide_id = ?");
    $stmt->execute([$guide_id]);
    $guide = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$guide) {
        throw new Exception("Hướng dẫn viên không tồn tại");
    }
    $price_per_day = $guide['price'];
    $total_price = $price_per_day * $days;

    $stmt = $conn->prepare("
        INSERT INTO guide_bookings 
            (usr_id, guide_id, booking_date, days, total_price, status, created_at) 
        VALUES 
            (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $guide_id,
        $booking_date,
        $days,
        $total_price
    ]);
    
    $booking_id = $conn->lastInsertId();
    $conn->commit();


    header("Location: payment.php?type=guide&booking_id=" . $booking_id);
    exit();

} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
    header('location: detail_guide.php?id=' . $guide_id);
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('location: detail_guide.php?id=' . $guide_id);
    exit();
}