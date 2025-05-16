<?php
include('../server/connectdb.php');
session_start();
if (!isset($_SESSION['admin_login'])) {
    echo ("Vui lòng đăng nhập");
    header('location: login.php');
    exit();
}

$total_users_query = "SELECT COUNT(*) as total FROM Users WHERE role='user'";
$total_users_stmt = $conn->query($total_users_query);
$total_users = $total_users_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$user_query = "SELECT usr_id, usr_name, email, role, created_at FROM Users where role='user' ORDER BY created_at DESC";
$user_stmt = $conn->query($user_query);
$users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);
if (isset($_GET['delete_usr'])) {
    $usr_id = $_GET['delete_usr'];

    $query = "DELETE FROM users WHERE usr_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$usr_id]);

    header("Location: manage_usr.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Travel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
  
</head>

<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">ADMIN</div>
            </div>
            <div class="menu">
                <a href="index.php" class="menu-item "><i class="fas fa-home"></i><span>Trang chủ</span></a>
                <a href="hotel_manage.php" class="menu-item"><i class="fas fa-hotel"></i><span>Quản lý khách
                        sạn</span></a>
                <a href="tour_manage.php" class="menu-item"><i class="fas fa-map-marked-alt"></i><span>Quản lý
                        tour</span></a>
                <a href="ticket_manage.php" class="menu-item "><i class="fas fa-ticket-alt"></i><span>Quản lý
                        vé</span></a>
                <a href="guide_manage.php" class="menu-item"><i class="fas fa-user-tie"></i><span>Quản lý hướng dẫn
                        viên</span></a>
                <a href="manage_user.php" class="menu-item active"><i class="fas fa-users"></i><span>Quản lý người
                        dùng</span></a>
                <a href="booking_manage.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Quản lý đặt
                        lịch</span></a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="page-title">Quản lý người dùng</div>
                <div class="user-profile">
                    <h4>Admin</h4>
                    <img src="assets/img/admin.jpg" alt="User Profile">
                </div>
            </div>
            <div class="total-users mb-3">
                <p>Tổng số người dùng: <strong><?= $total_users ?></strong></p>
            </div>
            <!-- Quản lý người dùng -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-users me-2"></i> Quản lý người dùng
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Tên người dùng</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Vai trò</th>
                                    <th scope="col">Ngày tạo</th>
                                    <th scope="col">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3">Không có người dùng nào</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['usr_id'] ?></td>
                                            <td><?= htmlspecialchars($user['usr_name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['role']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <button onclick="confirmDelete(<?= $user['usr_id'] ?>)"
                                                    class="btn btn-sm btn-danger action-btn">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>



            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                function confirmDelete(userID) {
                    if (confirm('Bạn có chắc chắn muốn xóa người dùng này không?')) {
                        window.location.href = `manage_usr.php?delete_usr=${userID}`;
                    }
                }
            </script>
</body>

</html>