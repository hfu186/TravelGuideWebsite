<?php
session_start();
require('../../server/connectdb.php');

if (!isset($_SESSION['usr_id'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để đặt tour";
    header('Location: log_in.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Phương thức không hợp lệ";
    header('Location: tour.php');
    exit();
}

$tour_id = $_POST['tour_id'] ?? null;
$selected_date = $_POST['selected_date'] ?? null;
$num_people = $_POST['num_people'] ?? null;
if (!$tour_id || !$selected_date || !$num_people) {
    $_SESSION['error'] = "Thông tin đặt tour không đầy đủ";
    header('Location: detail_tour.php?id=' . $tour_id);
    exit();
}

try {
    $conn->beginTransaction();
    $check_stmt = $conn->prepare("
        SELECT 
            t.tour_id,
            t.tour_name,
            t.price,
            td.tour_date_id,
            td.departure_date,
            td.available_slots,
            td.max_slots
        FROM 
            tours t
        JOIN 
            tour_dates td ON t.tour_id = td.tour_id
        WHERE 
            t.tour_id = ? 
            AND td.departure_date = ?
            AND td.status = 'available'
            AND td.available_slots >= ?
        FOR UPDATE
    ");
    $check_stmt->execute([$tour_id, $selected_date, $num_people]);
    $tour_info = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tour_info) {
        throw new Exception("Tour không khả dụng hoặc hết chỗ");
    }
    $booking_code = 'TB-' . strtoupper(bin2hex(random_bytes(5)));
    $total_price = $tour_info['price'] * $num_people;
    $booking_stmt = $conn->prepare("
        INSERT INTO tour_bookings (
            usr_id, 
            tour_id, 
            tour_date_id, 
            num_people, 
            total_price, 
            status, 
            booking_code, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $booking_stmt->execute([
        $_SESSION['usr_id'],
        $tour_id,
        $tour_info['tour_date_id'],
        $num_people,
        $total_price,
        $booking_code
    ]);
    $booking_id = $conn->lastInsertId();
    $update_slots_stmt = $conn->prepare("
        UPDATE tour_dates
        SET 
            available_slots = available_slots - ?,
            status = CASE 
                WHEN available_slots - ? = 0 THEN 'full'
                ELSE status 
            END
        WHERE 
            tour_date_id = ?
    ");
    $update_slots_stmt->execute([
        $num_people,
        $num_people,
        $tour_info['tour_date_id']
    ]);
    $conn->commit();
    $_SESSION['booking_id'] = $booking_id;
    header("Location: payment.php?type=tour&booking_id=" . $booking_id);
    exit();

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Booking Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: detail_tour.php?id=" . $tour_id);
    exit();
}
