<?php
session_start();
require('../../server/connectdb.php');
if (!isset($_SESSION['logged_in'])) {
    header('location: log_in.php');
    exit;
}
$usr_id = $_SESSION['usr_id'];
try {
    $stmt = $conn->prepare("
        SELECT distinct
            'Hotel' AS booking_type,
            h.hotel_name AS p_name,
            h.img_url AS p_image,
            hb.created_at AS ordate,
            hb.total_price,
            hb.status,
            hb.booking_id AS original_id,
            CONCAT('hotel_', hb.booking_id) AS id
        FROM hotel_bookings hb
        JOIN Rooms r ON hb.room_id = r.room_id
        JOIN Hotels h ON r.hotel_id = h.hotel_id
        WHERE hb.usr_id = :usr_id

        UNION ALL
        SELECT distinct
            'Tour' AS booking_type,
            t.tour_name AS p_name,
            t.image_url AS p_image,
            tb.created_at as orderdate,
            tb.total_price,
            tb.status,
            tb.booking_id AS original_id,
            CONCAT('tour_', tb.booking_id) AS id
        FROM tour_bookings tb
        join tour_dates td on tb.tour_id=td.tour_id
        JOIN Tours t ON tb.tour_id = t.tour_id
        WHERE tb.usr_id = :usr_id

        UNION ALL

        SELECT distinct
            'Ticket' AS booking_type,
            tk.ticketname AS p_name,
            tk.img_url AS p_image,
            tickb.booking_date AS ordate,
            tickb.total_price,
            tickb.status,
            tickb.booking_id AS original_id,
            CONCAT('ticket_', tickb.booking_id) AS id
        FROM ticket_bookings tickb
        JOIN Tickets tk ON tickb.ticket_id = tk.ticket_id
        WHERE tickb.usr_id = :usr_id

        UNION ALL

        SELECT distinct
            'Guide' AS booking_type,
            tg.name AS p_name,
            tg.img_url AS p_image,
            gb.booking_date AS ordate,
            gb.total_price,
            gb.status,
            gb.booking_id AS original_id,
            CONCAT('guide_', gb.booking_id) AS id
        FROM guide_bookings gb
        JOIN Tour_Guides tg ON tg.guide_id = gb.guide_id
        WHERE gb.usr_id = :usr_id

        ORDER BY ordate DESC
    ");
    $stmt->bindParam(':usr_id', $usr_id, PDO::PARAM_INT);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("⚠ Lỗi khi lấy thông tin đặt chỗ: " . $e->getMessage());
}
if (isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['cancel_booking_id'];
    $booking_type = $_POST['booking_type'];
    try {
        switch ($booking_type) {
            case 'Hotel':
                $stmt = $conn->prepare("UPDATE Rooms r 
                JOIN hotel_bookings hb ON r.room_id = hb.room_id 
                SET r.stock = r.stock + hb.quantity 
                WHERE hb.booking_id = ?");
                $stmt->bindParam(1, $booking_id, PDO::PARAM_INT);
                $stmt->execute();
                $stmt = $conn->prepare("Delete from hotel_bookings WHERE booking_id = ?");
                break; 
            case 'Tour':
                $stmt = $conn->prepare("Delete from  tour_bookings WHERE booking_id = ?");
                break;
            case 'Ticket':
                $stmt = $conn->prepare("Delete from ticket_bookings  WHERE booking_id = ?");
                break;
            case 'Guide':
                $stmt = $conn->prepare("Delete from guide_bookings WHERE booking_id = ?");
                break;
            default:
                die("⚠ Lỗi: Loại đặt chỗ không hợp lệ.");
        }

        $stmt->bindParam(1, $booking_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            header("Location: acc.php?message=Booking cancelled successfully!");
            exit;
        } else {
            die("⚠ Lỗi khi hủy đơn đặt!");
        }
    } catch (PDOException $e) {
        die("⚠ Lỗi khi cập nhật trạng thái đơn đặt: " . $e->getMessage());
    }
}


if (isset($_POST['change_pass'])) {
    $password = trim($_POST['password']);
    $confirmp = trim($_POST['confirmpass']);

    if (empty($password) || empty($confirmp)) {
        die("⚠ Lỗi: Không được để trống mật khẩu!");
    }
    if ($password !== $confirmp) {
        die("⚠ Lỗi: Mật khẩu không khớp!");
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE usr_id = ?');
        $stmt->bindParam(1, $hashedPassword);
        $stmt->bindParam(2, $usr_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            header("Location: acc.php?message=Update successful!");
            exit;
        } else {
            header("Location: acc.php?message=Fail!");
            exit;
        }
    } catch (PDOException $e) {
        die("⚠ Lỗi khi cập nhật mật khẩu: " . $e->getMessage());
    }
}


if (isset($_GET['logout'])) {
    unset($_SESSION['logged_in']);
    unset($_SESSION['usr_id']);
    header('location: log_in.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSOutlet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include('../layout/header.php'); ?>
    <section class="my-5 pt-3">
        <div class="accountrow-container">
            <div class="text-center mt-5 pt-3 col-lg-6 col-md-12 col-sm-12">
                <h2 style="font-weight:700">Tài khoản</h2>
                <hr class="mx-auto">
                <div class="info">
                    <p>Tên: <span><?php echo htmlspecialchars($_SESSION['usr_name']); ?></span></p>
                    <p>Email: <span><?php echo htmlspecialchars($_SESSION['email']); ?></span></p>
                    <p><a href="#ord-btn">Lịch sử đặt</a></p>
                    <p><a href="acc.php?logout=1">Đăng xuất</a></p>
                </div>
            </div>
            <div class="my-5 pt-3 col-lg-3 col-md-12 col-sm-12">
                <form name="changepass" id="account-form" method="POST" action="acc.php">
                    <h2 class="text-center fw-bold">Thay đổi mật khẩu</h2>
                    <hr class="mx-auto w-50">
                    <div class="form-group">
                        <label for="account-password">Mật khẩu mới</label>
                        <input type="password" class="form-control" id="account-password" name="password"
                            placeholder="Nhập mật khẩu mới" required>
                    </div>
                    <div class="form-group mt- 3">
                        <label for="account-confirmpass">Nhập lại mật khẩu</label>
                        <input type="password" class="form-control" id="account-confirmpass" name="confirmpass"
                            placeholder="Nhập lại để xác thực" required>
                    </div>
                    <div class="form-group mt-3">
                        <input type="submit" value="Đồng ý" name="change_pass" id="changepassword"
                            class="btn btn-primary">
                    </div>
                </form>
            </div>
        </div>
    </section>

    <hr style="width:100%">
    <section>
        <h2 class="text-center mb-3">Lịch sử đặt chỗ của bạn</h2>
        <hr class="mx-auto">


        <?php foreach ($bookings as $row): ?>
            
            <div class="booking-item">
                <img src="<?php echo htmlspecialchars($row['p_image']); ?>"
                    alt="<?php echo htmlspecialchars($row['p_name']); ?>" class="booking-image"  ?>
                <div class="booking-details">
                    <h5 >
                      <?php echo htmlspecialchars($row['booking_type']); ?></h5>
                    <h5 ><?php echo htmlspecialchars($row['p_name']); ?></h5>

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div>
                            <small>Ngày đặt: <?php echo date('d/m/Y', strtotime($row['ordate'])); ?></small>
                            <br>
                            <strong>Giá: <?php echo number_format($row['total_price'], 0, ',', '.'); ?> VNĐ</strong>
                        </div>
                        <div>
                        
                    <?php if ($row['status'] !== 'cancelled'): ?>
                        <form method="POST" action="acc.php" style="display:inline;">
                            <input type="hidden" name="cancel_booking_id" value="<?php echo $row['original_id']; ?>">
                            <input type="hidden" name="booking_type" value="<?php echo $row['booking_type']; ?>">
                            <button type="submit" name="cancel_booking" class="btn btn-primary btn-sm"
                                onclick="return confirm('Bạn có chắc muốn hủy đơn này không?');">
                                Hủy đơn
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="text-danger"><strong>Đã hủy</strong></span>
                    <?php endif; ?>

                            <a href="review.php?booking_id=<?php
                            echo urlencode($row['booking_type'] . '_' . $row['id']);
                            ?>" class="btn btn-primary btn-sm">
                                Viết Đánh Giá
                            </a>
                        </div>


                    </div>
                    
                </div>
            </div>
        <?php endforeach; ?>


    </section>



    <?php include('../layout/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>