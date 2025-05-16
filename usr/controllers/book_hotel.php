<?php
session_start();
require('../../server/connectdb.php');
if (!isset($_SESSION['usr_id'])) {
    echo ("Bạn cần đăng nhập để đặt tour");
    header('location: log_in.php');
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['hotel_id'], $_POST['check_in'], $_POST['check_out'], $_POST['room_id'])) {
       echo "Thiếu thông tin đặt phòng!";
       header('location:hotel.php');
       exit();
    }

    $usr_id = $_SESSION['usr_id'];
    $hotel_id = intval($_POST['hotel_id']);
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $room_id = intval($_POST['room_id']);
    $quantity = intval($_POST['quantity']);
    if (strtotime($check_in) >= strtotime($check_out)) {
        echo "Ngày trả phòng phải lớn hơn ngày nhận phòng!";
        exit();
    }

    try {

        $stmt = $conn->prepare("SELECT price_per_night FROM rooms WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            die("Phòng không tồn tại!");
        }

        $price_per_night = $room['price_per_night'];
        $num_nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
        $total_price = $num_nights * $price_per_night * $quantity;
        $stmt = $conn->prepare("INSERT INTO hotel_bookings (usr_id, room_id, hotel_id, check_in, check_out,quantity, total_price, status) 
                                VALUES (?, ?, ?, ?, ?, ?,?, 'pending')");
        $stmt->execute([$usr_id, $room_id, $hotel_id, $check_in, $check_out,$quantity, $total_price]);

        $booking_id = $conn->lastInsertId();
        $stmt = $conn->prepare("SELECT u.usr_name, u.email
                                FROM users u 
                                WHERE u.usr_id = ?");
        $stmt->execute([$usr_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die("Lỗi: Không tìm thấy thông tin người dùng!");
        }

        $_SESSION['user_name'] = $user['usr_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['check_in'] = $check_in;
        $_SESSION['check_out'] = $check_out;
        $_SESSION['num_nights'] = $num_nights;
        $_SESSION['total_price'] = $total_price;
        $_SESSION['hotel_id'] = $hotel_id;
        $_SESSION['room_id'] = $room_id;
        $_SESSION['booking_id'] = $booking_id;
        $_SESSION['quantity']=$quantity;

    
        header("Location: payment.php?type=hotel&booking_id=$booking_id");
        exit();

    } catch (PDOException $e) {
        // Xử lý lỗi chi tiết
        error_log("Lỗi đặt phòng: " . $e->getMessage());
        die("Đã xảy ra lỗi trong quá trình đặt phòng. Vui lòng thử lại sau.");
    }
} else {
    die("Truy cập không hợp lệ!");
}
?>
