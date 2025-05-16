<?php
require_once '../../server/connectdb.php';

if (isset($_GET['booking_id']) && isset($_GET['type'])) {
    $booking_id = intval($_GET['booking_id']);
    $booking_type = $_GET['type'];
    try {
        if ($booking_type == 'ticket') {
            $sql = "DELETE FROM ticket_bookings WHERE booking_id = ?";
        } elseif ($booking_type == 'hotel') {
            $sql = "DELETE FROM hotel_bookings WHERE booking_id = ?";
        } elseif ($booking_type == 'tour') {
            $sql = "DELETE FROM tour_bookings WHERE booking_id = ?";
        } 
        elseif ($booking_type == 'guide') {
            $sql = "DELETE FROM guide_bookings WHERE booking_id = ?";
    
        }else {
            echo "Loại đặt chỗ không hợp lệ.";
            exit();
        }

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $booking_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "<script>alert('Đã hủy đơn thành công!'); window.location.href='../../index.php';</script>";
            exit();
        } else {
            echo "<script>alert('Lỗi khi hủy đơn!'); history.back();</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi: " . $e->getMessage() . "'); history.back();</script>";
    }
} else {
    echo "<script>alert('Không có thông tin đơn đặt hợp lệ.'); history.back();</script>";
}
?>
