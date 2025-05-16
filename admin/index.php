<?php
session_start();
include('../server/connectdb.php');
if (!isset(  $_SESSION['admin_login'] )) {
    echo ("Vui lòng đăng nhập");
    header('location: login.php');
    exit();
}
function getTotalBookings($conn)
{
    $stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM guide_bookings) +
            (SELECT COUNT(*) FROM hotel_bookings) +
            (SELECT COUNT(*) FROM tour_bookings) +
            (SELECT COUNT(*) FROM ticket_bookings) as total
    ");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}


function getTotalRevenue($conn)
{
    $stmt = $conn->query("
        SELECT 
            (SELECT COALESCE(SUM(total_price), 0) FROM guide_bookings) +
            (SELECT COALESCE(SUM(total_price), 0) FROM hotel_bookings) +
            (SELECT COALESCE(SUM(total_price), 0) FROM tour_bookings) +
            (SELECT COALESCE(SUM(total_price), 0) FROM ticket_bookings) as total
    ");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}


function getTotalTours($conn)
{
    $stmt = $conn->query("SELECT COUNT(*) as total FROM Tours");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}


function getTotalUser($conn)
{
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users where role='user' ");
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}


function getRecentBookings($conn)
{
    $stmt = $conn->query("
        SELECT 'guide' as type, booking_id, usr_id, booking_date, total_price, status FROM guide_bookings
        UNION ALL
        SELECT 'hotel' as type, booking_id, usr_id, created_at as booking_date, total_price, status FROM hotel_bookings
        UNION ALL
        SELECT 'tour' as type,  tb.booking_id, tb.usr_id, tb.created_at as booking_date,tb.total_price, tb.status
        from tour_bookings tb   
        inner join tour_dates td on tb.tour_id=td.tour_id
        UNION ALL
        SELECT 'ticket' as type, booking_id, usr_id, booking_date, total_price, status FROM ticket_bookings
        ORDER BY booking_date DESC
        LIMIT 4
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



$total_bookings = getTotalBookings($conn);
$total_revenue = getTotalRevenue($conn);
$totaltour=getTotalTours(conn: $conn);
$totalUser = getTotalUser($conn);
$recent_bookings = getRecentBookings(conn: $conn);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/CSS/style.css">
    <style>
        .stat-card {
            background-color: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            color: #fff;
        }

        .stat-icon.primary {
            background-color: #3498db;
        }

        .stat-icon.success {
            background-color: #2ecc71;
        }

        .stat-icon.info {
            background-color: #1abc9c;
        }

        .stat-icon.warning {
            background-color: #f1c40f;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .badge {
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
        }


        .chart-container {
            background-color: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .confirmed {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4caf50;
        }

        .pending {
            background-color: rgba(255, 152, 0, 0.1);
            color: #ff9800;
        }

        .cancelled {
            background-color: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                padding: 0.75rem;
            }

            .sidebar-logo {
                padding: 1rem;
                text-align: center;
            }

            .sidebar-logo h3 {
                display: none;
            }

            .nav-link span {
                display: none;
            }

            .nav-link i {
                margin-right: 0;
                font-size: 1.25rem;
            }

            .main-content {
                margin-left: 80px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .sidebar {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <div class="logo">Welcome Admin</div>
                </div>
                <div class="menu">
                    <a href="index.php" class="menu-item active"><i class="fas fa-home"></i><span>Trang chủ</span></a>
                    <a href="hotel_manage.php" class="menu-item"><i class="fas fa-hotel"></i><span>Quản lý khách
                            sạn</span></a>
                    <a href="tour_manage.php" class="menu-item"><i class="fas fa-map-marked-alt"></i><span>Quản lý
                            tour</span></a>
                    <a href="ticket_manage.php" class="menu-item"><i class="fas fa-ticket-alt"></i><span>Quản lý
                            vé</span></a>
                    <a href="guide_manage.php" class="menu-item"><i class="fas fa-user-tie"></i><span>Quản lý hướng dẫn
                            viên</span></a>
                    <a href="users.html" class="menu-item"><i class="fas fa-users"></i><span>Quản lý người
                            dùng</span></a>
                    <a href="booking_manage.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Quản lý đặt
                            lịch</span></a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="header">
                    <div class="page-title">Hệ Thống Quản Lý</div>
                    <div class="user-profile">
                        <div class="dropdown">
                            <button class="btn dropdown-toggle" type="button" id="profileDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="me-2">Admin</span>
                                <img src="assets/img/admin.jpg" alt="Admin Profile">
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                            
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="../index.php"><i
                                            class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row gy-4 mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="stat-icon" style="background-color: var(--primary-color);">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>
                            </div>
                            <h6 class="text-muted">Số Lượng Đặt Vé</h6>
                            <div class="stat-value"><?= $total_bookings ?></div>

                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="stat-icon" style="background-color: var(--success-color);">
                                    <i class="fa-solid fa-dollar-sign"></i>
                                </div>
                            </div>
                            <h6 class="text-muted">Doanh Thu</h6>
                            <div class="stat-value"><?= number_format($total_revenue, 0) ?> VND</div>

                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="stat-icon" style="background-color: var(--primary-color);">
                                    <i class="fa-solid fa-signs-post"></i>
                                </div>
                            </div>
                            <h6 class="text-muted">Tour </h6>
                            <div class="stat-value"><?= $totaltour ?></div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="stat-icon" style="background-color: var(--warning-color);">
                                    <i class="fa-solid fa-id-badge"></i>
                                </div>
                              
                            </div>
                            <h6 class="text-muted">Số lượng khách</h6>
                            <div class="stat-value"><?= $totalUser ?></div>
                        </div>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="m-0">Các đơn gần đây</h5>
                        <a href="booking_manage.php" class="btn btn-primary btn-sm">Xem tất cả đơn đặt</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">Booking ID</th>
                                    <th scope="col">Khách hàng ID</th>
                                    <th scope="col">Dịch vụ</th>
                                    <th scope="col">Ngày</th>
                                    <th scope="col">Giá</th>
                                    <th scope="col">Trạng thái</th>
                                    <th scope="col">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($booking['booking_id']) ?></td>
                                        <td><?= htmlspecialchars($booking['usr_id']) ?></td>
                                        <td><?= htmlspecialchars($booking['type']) ?></td>
                                        <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                                        <td><?= number_format($booking['total_price'], 0) ?> VND</td>
                                        <td><span
                                                class="status-badge <?= strtolower($booking['status']) ?>"><?= htmlspecialchars($booking['status']) ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>