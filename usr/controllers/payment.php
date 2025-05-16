<?php
session_start();
include('../../server/connectdb.php');

$user_name = $_SESSION['usr_name'];
$user_email = $_SESSION['email'];
if (!isset($_GET['type']) || !isset($_GET['booking_id'])) {
    echo "<script>alert('Thông tin đặt chỗ không hợp lệ!'); window.location.href='../../index.php';</script>";
    exit();
}

$booking_type = $_GET['type'];
$booking_id = intval($_GET['booking_id']);


if (!isset($_SESSION['usr_id'])) {
    echo "<script>alert('Vui lòng đăng nhập để thanh toán!'); window.location.href='log_in.php';</script>";
    exit();
}

$user_id = $_SESSION['usr_id'];

try {

    if ($booking_type == 'hotel') {
        $stmt = $conn->prepare("SELECT * FROM hotel_bookings WHERE booking_id = :booking_id AND usr_id = :usr_id");
    } elseif ($booking_type == 'tour') {
        $stmt = $conn->prepare("SELECT * FROM tour_bookings WHERE booking_id = :booking_id AND usr_id = :usr_id");
    } elseif ($booking_type == 'ticket') {
        $stmt = $conn->prepare("SELECT * FROM ticket_bookings WHERE booking_id = :booking_id AND usr_id = :usr_id");
    } elseif ($booking_type == 'guide') {
        $stmt = $conn->prepare("SELECT * FROM guide_bookings WHERE booking_id = :booking_id AND usr_id = :usr_id");
    } else {
        echo "<script>alert('Loại đặt chỗ không hợp lệ!'); window.location.href='../../index.php';</script>";
        exit();
    }

    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $stmt->bindParam(':usr_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $booking_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking_details) {
        echo "<script>alert('Không tìm thấy đơn đặt chỗ của bạn!'); window.location.href='../../index.php';</script>";
        exit();
    }
    if ($booking_details['status'] === 'rejected') {
        echo "<script>alert('Đơn đặt chỗ đã bị từ chối và tự động hủy!'); window.location.href='../../index.php';</script>";
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmPayment'])) {
        if ($booking_type == 'hotel') {
            $update_stmt = $conn->prepare("UPDATE hotel_bookings SET status = 'paid' WHERE booking_id = :booking_id");
        } elseif ($booking_type == 'tour') {
            $update_stmt = $conn->prepare("UPDATE tour_bookings SET status = 'paid' WHERE booking_id = :booking_id");
        } elseif ($booking_type == 'ticket') {
            $update_stmt = $conn->prepare("UPDATE ticket_bookings SET status = 'paid' WHERE booking_id = :booking_id");
        } elseif ($booking_type == 'guide') {
            $update_stmt = $conn->prepare("UPDATE guide_bookings SET status = 'paid' WHERE booking_id = :booking_id");
        }

        $update_stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
        $update_stmt->execute();

        if ($update_stmt->rowCount() > 0) {
 
            $emailBookingDetails = [
                'booking_id' => $booking_id,
                'type' => match ($booking_type) {
                    'hotel' => 'Đặt Phòng Khách Sạn',
                    'tour' => 'Tour Du Lịch',
                    'ticket' => 'Vé Sự Kiện',
                    'guide' => 'Hướng Dẫn Viên'
             
                },
                'total_price' => $total_price,
                'check_in' => $booking_type == 'hotel' ? $check_in : null,
                'check_out' => $booking_type == 'hotel' ? $check_out : null,
                'departure_date' => $departure_date,
                'end_date' => $end_date,
                'num_people' => $num_people,
                'quantity' => $quantity
            ];


            header("Location: mailer.php?action=send_email&booking_id=" . $booking_id .
            "&type=" . urlencode($booking_type) .
            "&email=" . urlencode($user_email));
     exit();
        } else {
            echo "<script>alert('Bạn đã thanh toán trước đó!');</script>";
            header('../../index.php');
            exit();
        }
    }
} catch (PDOException $e) {
    echo "<script>alert('Lỗi: " . $e->getMessage() . "');</script>";
}


$total_price = $booking_details['total_price'] ?? 0;
$check_in = $booking_details['check_in'] ?? '';
$check_out = $booking_details['check_out'] ?? '';
$tour_date = $booking_details['booking_date'] ?? '';
$num_people = $booking_details['num_people'] ?? 1;
$ticket_id = $booking_details['booking_id'] ?? '';
$ticket_date = $booking_details['booking_date'] ?? '';
$quantity = $booking_details['quantity'] ?? 0;
$day = $booking_details['days'] ?? 0;
$departure_date = $booking_details['departure_date'] ?? '';
$end_date = $booking_details['end_date'] ?? '';



if (empty($departure_date) && !empty($booking_details['tour_date_id'])) {
    $tour_date_id = $booking_details['tour_date_id'];
    $date_stmt = $conn->prepare("SELECT * FROM tour_dates WHERE tour_date_id = :tour_date_id");
    $date_stmt->bindParam(':tour_date_id', $tour_date_id, PDO::PARAM_INT);
    $date_stmt->execute();
    $tour_date_info = $date_stmt->fetch(PDO::FETCH_ASSOC);

    if ($tour_date_info) {
        $departure_date = $tour_date_info['departure_date'];
        $end_date = $tour_date_info['end_date'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../layout/header.php'); ?>
</head>

<body>
    <div class="payment-container">
        <h2>Thanh toán</h2>
        <p><strong>Mã đặt chỗ:</strong> <?= htmlentities($booking_id) ?></p>
        <p><strong>Loại dịch vụ:</strong>
            <?= $booking_type == 'hotel' ? 'Đặt phòng khách sạn' : ($booking_type == 'guide' ? 'Đặt hướng dẫn viên' : ($booking_type == 'tour' ? 'Đặt tour du lịch' : 'Đặt vé sự kiện')) ?>
        </p>
        <?php if ($booking_type == 'hotel'): ?>
            <p><strong>Ngày nhận phòng:</strong> <?= $check_in ? date('d-m-Y', strtotime($check_in)) : 'Không có dữ liệu' ?>
            </p>
            <p><strong>Ngày trả phòng:</strong>
                <?= $check_out ? date('d-m-Y', strtotime($check_out)) : 'Không có dữ liệu' ?></p>
                <p><strong>Số phòng</strong> <?= $quantity ?></p>
            <p><strong>Tổng giá:</strong> <?= number_format($total_price, 0, ',', '.') ?> VND</p>
        <?php elseif ($booking_type == 'tour'): ?>
            <p><strong>Ngày khởi hành:</strong>
                <?= $departure_date ? date('d-m-Y', strtotime($departure_date)) : 'Không có dữ liệu' ?></p>
            <p><strong>Ngày kết thúc:</strong>
                <?= $end_date ? date('d-m-Y', strtotime($end_date)) : 'Không có dữ liệu' ?></p>
            <p><strong>Tổng giá:</strong> <?= number_format($total_price, 0, ',', '.') ?> VND</p>
            <p><strong>Số người:</strong> <?= htmlentities($num_people) ?></p>
        <?php elseif ($booking_type == 'ticket'): ?>
            <p><strong>Tổng giá:</strong> <?= number_format($total_price, 0, ',', '.') ?> VND</p>
            <p><strong>Ngày tham quan:</strong>
                <?= $ticket_date ? date('d-m-Y', strtotime($ticket_date)) : 'Không có dữ liệu' ?></p>
            <p><strong>Số lượng vé:</strong> <?= htmlentities($quantity) ?></p>

        <?php elseif ($booking_type == 'guide'): ?>
            <p><strong>Ngày bắt đầu:</strong> <?= date('d-m-Y', strtotime($tour_date)) ?></p>
            <p><strong>Số ngày thuê:</strong> <?= htmlentities($day) ?></p>

            <p><strong>Tổng giá:</strong> <?= number_format($total_price, 0, ',', '.') ?> VND</p>
        <?php endif; ?>
        <h4>Thông tin người đặt:</h4>
        <p><strong>Họ và tên:</strong> <?= htmlentities($user_name) ?></p>
        <p><strong>Email:</strong> <?= htmlentities($user_email) ?></p>

        <div class="payment-methods">
            <h5>Chọn phương thức thanh toán</h5>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="paymentMethod" id="bankTransfer" value="bankTransfer"
                    checked>
                <label class="form-check-label" for="bankTransfer">
                    <i class="fas fa-university"></i> Chuyển khoản ngân hàng
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="paymentMethod" id="cash" value="cash">
                <label class="form-check-label" for="cash">
                    <i class="fas fa-money-bill-wave"></i> Thanh toán tiền mặt
                </label>
            </div>
        </div>

        <div class="bank-details" id="bankDetails" name="paymentMethod" value="bankTransfer">
            <h5>Thông tin chuyển khoản</h5>
            <p><strong>Ngân hàng:</strong> Vietcombank</p>
            <p><strong>Số tài khoản:</strong> 996666777788</p>
            <p><strong>Chủ tài khoản:</strong> ALoha Travel Viet Nam</p>
            <p><strong>Nội dung chuyển khoản:</strong> Thanh toán mã đặt chỗ:
                <?= htmlentities($booking_details['booking_id']) ?>
            </p>
            <p class="text-danger">Lưu ý: Vui lòng chuyển khoản đúng nội dung để hệ thống xác nhận tự động.</p>
        </div>

        <div class="cash-details" id="cashDetails" name="paymentMethod" value="cash">
            <h5>Thông tin thanh toán tiền mặt</h5>
            <p>Vui lòng đến văn phòng của chúng tôi để thanh toán trực tiếp.</p>
            <p><strong>Địa chỉ:</strong>101 Hùng Vương, Q. Tân Bình,TPHCM</p>
            <p><strong>Thời gian làm việc:</strong> 8:00 - 17:00 (Thứ 2 - Thứ 6)</p>
        </div>



        <form method="POST">
            <button class="btn btn-pay btn-success" name="confirmPayment">
                <i class="fas fa-check-circle"></i> Xác nhận thanh toán
            </button>
            <a href="cancel.php?booking_id=<?= htmlentities($booking_id) ?>&type=<?= htmlentities($booking_type) ?>"
                class="btn w-100 mt-2">Hủy Đơn</a>

        </form>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <?php include('../layout/footer.php'); ?>
</body>

</html>