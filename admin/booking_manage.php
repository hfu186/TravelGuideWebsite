<?php
session_start();
include('../server/connectdb.php');
if (!isset(  $_SESSION['admin_login'] )) {
    echo ("Vui lòng đăng nhập");
    header('location: login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = $_POST['booking_id'] ?? 0;
    $booking_type = $_POST['booking_type'] ?? '';
    $action_type = $_POST['action_type'] ?? '';
    $message = '';
    $success = false;

    try {
        if (!$booking_id || !$booking_type || !$action_type) {
            throw new Exception("Thiếu thông tin cần thiết");
        }

        $table_name = '';
        switch ($booking_type) {
            case 'hotel':
                $table_name = 'hotel_bookings';
                $status_field = 'confirmed';
                break;
            case 'tour':
                $table_name = 'Tour_Bookings';
                $status_field = 'confirmed';
                break;
            case 'ticket':
                $table_name = 'Ticket_Bookings';
                $status_field = 'confirmed';
                break;
            case 'guide':
                $table_name = 'Guide_Bookings';
                $status_field = 'confirmed';
                break;
            default:
                throw new Exception("Loại booking không hợp lệ");
        }

        if ($action_type === 'accept') {
            $stmt = $conn->prepare("UPDATE $table_name SET status = 'accept' WHERE booking_id = ?");
            if ($stmt->execute([$booking_id])) {
                $success = true;
                $message = "Đã chấp nhận đơn đặt chỗ ID #$booking_id thành công!";
            } else {
                throw new Exception("Không thể cập nhật trạng thái");
            }
        }
        if ($action_type === 'reject') {
            $stmt = $conn->prepare("Delete from $table_name  WHERE booking_id = ?");
            if ($stmt->execute([$booking_id])) {
                $success = true;
                $message = "Đã từ chối đơn đặt chỗ ID #$booking_id thành công!";
            } else {
                throw new Exception("Không thể cập nhật trạng thái");
            }
        }
    } catch (Exception $e) {
        $message = "Lỗi: " . $e->getMessage();
    }
}


$hotel_query = "SELECT hb.booking_id, hb.usr_id, hb.room_id, hb.check_in, hb.check_out, 
               hb.quantity, hb.total_price, hb.status, hb.created_at, 
               u.usr_name, h.hotel_name, r.room_type
               FROM hotel_bookings hb
               JOIN Users u ON hb.usr_id = u.usr_id
               JOIN Rooms r ON hb.room_id = r.room_id
               JOIN Hotels h ON r.hotel_id = h.hotel_id
               ORDER BY hb.created_at DESC";


$tour_query = "SELECT  tb.booking_id, tb.usr_id,  tb.tour_id, tb.tour_date_id,  
    tb.num_people, 
    tb.total_price, 
    td.departure_date AS booking_date, 
    tb.status,
    u.usr_name, 
    t.tour_name,
    td.end_date       
FROM Tour_Bookings tb
JOIN Users u ON tb.usr_id = u.usr_id
JOIN Tours t ON tb.tour_id = t.tour_id
LEFT JOIN Tour_Dates td ON tb.tour_date_id = td.tour_date_id
ORDER BY td.departure_date DESC";



$ticket_query = "SELECT tkb.booking_id, tkb.usr_id, tkb.ticket_id, 
                tkb.quantity, tkb.total_price, tkb.booking_date, tkb.status,
                u.usr_name, tk.ticketname
                FROM Ticket_Bookings tkb
                JOIN Users u ON tkb.usr_id = u.usr_id
                JOIN Tickets tk ON tkb.ticket_id = tk.ticket_id
                ORDER BY tkb.booking_date DESC";

$guide_query = "SELECT gb.booking_id, gb.usr_id, gb.guide_id, gb.booking_date, 
                gb.total_price, gb.status, u.usr_name, g.name
                FROM Guide_Bookings gb
                JOIN Users u ON gb.usr_id = u.usr_id
                JOIN Tour_Guides g ON gb.guide_id = g.guide_id
                ORDER BY gb.booking_date DESC";


$hotel_stmt = $conn->query($hotel_query);
$hotel_bookings = $hotel_stmt->fetchAll(PDO::FETCH_ASSOC);

$tour_stmt = $conn->query($tour_query);
$tour_bookings = $tour_stmt->fetchAll(PDO::FETCH_ASSOC);

$ticket_stmt = $conn->query($ticket_query);
$ticket_bookings = $ticket_stmt->fetchAll(PDO::FETCH_ASSOC);

$guide_stmt = $conn->query($guide_query);
$guide_bookings = $guide_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đặt Chỗ - Tourism Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 d-md-block bg-dark sidebar py-4">
                <div class="position-sticky">
                    <h5 class="text-white text-center mb-4">ADMIN</h5>
                    <div class="menu">
                        <a href="index.php" class="menu-item "><i class="fas fa-home"></i><span>Trang chủ</span></a>
                        <a href="hotel_manage.php" class="menu-item"><i class="fas fa-hotel"></i><span>Quản lý khách
                                sạn</span></a>
                        <a href="tour_manage.php" class="menu-item"><i class="fas fa-map-marked-alt"></i><span>Quản lý
                                tour</span></a>
                        <a href="ticket_manage.php" class="menu-item "><i class="fas fa-ticket-alt"></i><span>Quản lý
                                vé</span></a>
                        <a href="guide_manage.php" class="menu-item"><i class="fas fa-user-tie"></i><span>Quản lý hướng
                                dẫn viên</span></a>
                        <a href="manage_usr.php" class="menu-item "><i class="fas fa-users"></i><span>Quản lý người
                                dùng</span></a>
                        <a href="booking_manage.php" class="menu-item active"><i class="fas fa-chart-bar"></i><span>Quản
                                lý đặt lịch</span></a>
                    </div>
                </div>
            </nav>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4">
                <h1 class="h3 mb-4">Quản Lý Đặt Chỗ</h1>


                <?php if (isset($message)): ?>
                    <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Khách sạn -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-hotel me-2"></i> Đặt phòng khách sạn</span>
                        <span class="badge bg-white text-primary"><?= count($hotel_bookings) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Khách hàng</th>
                                        <th scope="col">Khách sạn / Phòng</th>
                                        <th scope="col">Check-in</th>
                                        <th scope="col">Check-out</th>
                                        <th scope="col">Số lượng</th>
                                        <th scope="col">Giá tiền</th>
                                        <th scope="col">Trạng thái</th>
                                        <th scope="col">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($hotel_bookings)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-3">Không có đơn đặt phòng nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($hotel_bookings as $booking): ?>
                                            <tr>
                                                <td><?= $booking['booking_id'] ?></td>
                                                <td><?= htmlspecialchars($booking['usr_name']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($booking['hotel_name']) ?><br>
                                                    <small
                                                        class="text-muted"><?= htmlspecialchars($booking['room_type']) ?></small>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($booking['check_in'])) ?></td>
                                                <td><?= date('d/m/Y', strtotime($booking['check_out'])) ?></td>
                                                <td class="text-center"><?= $booking['quantity'] ?> phòng</td>
                                                <td><?= number_format($booking['total_price'], 0, ',', '.') ?> VNĐ</td>
                                                <td>
                                                    <span class="badge status<?= $booking['status'] ?>">
                                                        <p> <?= ($booking['status'] === 'pending') ? 'Chờ xác nhận' : (($booking['status'] === 'rejected') ? 'Đã từ chối' : 'Đã xác nhận') ?>
                                                        </p>

                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <div class="btn-group">
                                                            <form method="post" class="me-1">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="action_type" value="accept">
                                                                <input type="hidden" name="booking_id"
                                                                    value="<?= $booking['booking_id'] ?>">
                                                                <input type="hidden" name="booking_type" value="hotel">
                                                                <button type="submit" class="btn btn-sm btn-success"
                                                                    onclick="return confirm('Chấp nhận đơn đặt phòng này?')">
                                                                    <i class="fas fa-check me-1"></i> Chấp nhận
                                                                </button>
                                                            </form>
                                                            <form method="post">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="action_type" value="reject">
                                                                <input type="hidden" name="booking_id"
                                                                    value="<?= $booking['booking_id'] ?>">
                                                                <input type="hidden" name="booking_type" value="hotel">
                                                                <button type="submit" class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Bạn có chắc muốn xóa đơn đặt phòng này?')">
                                                                    <i class="fas fa-times me-1"></i> Từ chối
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Đã xử lý</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tour -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-map-marked-alt me-2"></i> Đặt tour</span>
                        <span class="badge bg-white text-success"><?= count($tour_bookings) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Khách hàng</th>
                                        <th scope="col">Tên tour</th>
                                        <th scope="col">Ngày đặt</th>
                                        <th scope="col">Số người</th>
                                        <th scope="col">Giá tiền</th>
                                        <th scope="col">Trạng thái</th>
                                        <th scope="col">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tour_bookings)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-3">Không có đơn đặt tour nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tour_bookings as $booking): ?>
                                            <tr>
                                                <td><?= $booking['booking_id'] ?></td>
                                                <td><?= htmlspecialchars($booking['usr_name']) ?></td>
                                                <td><?= htmlspecialchars($booking['tour_name']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($booking['booking_date'])) ?></td>
                                                <td class="text-center"><?= $booking['num_people'] ?> người</td>
                                                <td><?= number_format($booking['total_price'], 0, ',', '.') ?> VNĐ</td>
                                                <td>
                                                    <span class="badge status-<?= $booking['status'] ?>">
                                                        <?= ($booking['status'] === 'pending') ? 'Chờ xác nhận' : (($booking['status'] === 'rejected') ? 'Đã từ chối' : 'Đã xác nhận') ?>

                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <div class="btn-group">
                                                            <form method="post" class="me-1">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="action_type" value="accept">
                                                                <input type="hidden" name="booking_id"
                                                                    value="<?= $booking['booking_id'] ?>">
                                                                <input type="hidden" name="booking_type" value="tour">
                                                                <button type="submit" class="btn btn-sm btn-success"
                                                                    onclick="return confirm('Chấp nhận đơn đặt tour này?')">
                                                                    <i class="fas fa-check me-1"></i> Chấp nhận
                                                                </button>
                                                            </form>
                                                            <form method="post">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="action_type" value="reject">
                                                                <input type="hidden" name="booking_id"
                                                                    value="<?= $booking['booking_id'] ?>">
                                                                <input type="hidden" name="booking_type" value="tour">
                                                                <button type="submit" class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Bạn có chắc muốn xóa đơn đặt tour này?')">
                                                                    <i class="fas fa-times me-1"></i> Từ chối
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Đã xử lý</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Đặt chỗ hướng dẫn viên -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-tie me-2"></i> Đặt chỗ hướng dẫn viên</span>
                        <span class="badge bg-white text-info"><?= count($guide_bookings) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Khách hàng</th>
                                        <th scope="col">Tên hướng dẫn viên</th>
                                        <th scope="col">Ngày đặt</th>
                                        <th scope="col">Giá tiền</th>
                                        <th scope="col">Trạng thái</th>
                                        <th scope="col">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($guide_bookings)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-3">Không có đơn đặt hướng dẫn viên nào
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($guide_bookings as $booking): ?>
                                            <tr>
                                                <td><?= $booking['booking_id'] ?></td>
                                                <td><?= htmlspecialchars($booking['usr_name']) ?></td>
                                                <td><?= htmlspecialchars($booking['name']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($booking['booking_date'])) ?></td>
                                                <td><?= number_format($booking['total_price'], 0, ',', '.') ?> VNĐ</td>
                                                <td>
                                                    <span class="badge status-<?= $booking['status'] ?>">
                                                        <p> <?= ($booking['status'] === 'pending') ? 'Chờ xác nhận' : (($booking['status'] === 'rejected') ? 'Đã từ chối' : 'Đã xác nhận') ?>
                                                        </p>
                                                    </span>

                                                </td>
                                                <td>
                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <div class="btn-group">
                                                            <form method="post" class="me-1">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="action_type" value="accept">
                                                                <input type="hidden" name="booking_id"
                                                                    value="<?= $booking['booking_id'] ?>">
                                                                <input type="hidden" name="booking_type" value="guide">
                                                                <button type="submit" class="btn btn-sm btn-success"
                                                                    onclick="return confirm('Chấp nhận đơn đặt hướng dẫn viên này?')">
                                                                    <i class="fas fa-check me-1"></i> Chấp nhận
                                                                </button>
                                                            </form>
                                                            <form method="post">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="action_type" value="reject">
                                                                <input type="hidden" name="booking_id"
                                                                    value="<?= $booking['booking_id'] ?>">
                                                                <input type="hidden" name="booking_type" value="guide">
                                                                <button type="submit" class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Bạn có chắc muốn xóa đơn đặt hướng dẫn viên này?')">
                                                                    <i class="fas fa-times me-1"></i> Từ chối
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Đã xử lý</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


                <!-- Vé -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-ticket-alt me-2"></i> Đặt vé</span>
                        <span class="badge bg-white text-warning"><?= count($ticket_bookings) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Khách hàng</th>
                                        <th scope="col">Tên vé/Điểm tham quan</th>
                                        <th scope="col">Ngày đặt</th>
                                        <th scope="col">Số vé</th>
                                        <th scope="col">Giá tiền</th>
                                        <th scope="col">Trạng thái</th>
                                        <th scope="col">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ticket_bookings)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-3">Không có đơn đặt vé nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($ticket_bookings as $booking): ?>
                                            <tr>
                                                <td><?= $booking['booking_id'] ?></td>
                                                <td><?= htmlspecialchars($booking['usr_name']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($booking['ticketname']) ?><br>

                                                </td>
                                                <td><?= date('d/m/Y', strtotime($booking['booking_date'])) ?></td>
                                                <td class="text-center"><?= $booking['quantity'] ?> vé</td>
                                                <td><?= number_format($booking['total_price'], 0, ',', '.') ?> VNĐ</td>
                                                <td>
                                                    <span class="badge status-<?= $booking['status'] ?>">
                                                        <p> <?= ($booking['status'] === 'pending') ? 'Chờ xác nhận' : (($booking['status'] === 'rejected') ? 'Đã từ chối' : 'Đã xác nhận') ?>
                                                        </p>

                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <div class="btn-group">
                                                            <form method="post" class="me-1">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="action_type" value="accept">
                                                                <input type="hidden" name="booking_id"
                                                                    value="<?= $booking['booking_id'] ?>">
                                                                <input type="hidden" name="booking_type" value="ticket">
                                                                <button type="submit" class="btn btn-sm btn-success"
                                                                    onclick="return confirm('Chấp nhận đơn đặt vé này?')">
                                                                    <i class="fas fa-check me-1"></i> Chấp nhận
                                                                </button>
                                                            </form>
                                                            <form method="post">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="action_type" value="reject">
                                                                <input type="hidden" name="booking_id"
                                                                    value="<?= $booking['booking_id'] ?>">
                                                                <input type="hidden" name="booking_type" value="ticket">
                                                                <button type="submit" class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Bạn có chắc muốn xóa đơn đặt vé này?')">
                                                                    <i class="fas fa-times me-1"></i> Từ chối
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Đã xử lý</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>