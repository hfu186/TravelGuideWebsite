<?php
session_start();
include('../server/connectdb.php');
if (!isset(  $_SESSION['admin_login'] )) {
    echo ("Vui lòng đăng nhập");
    header('location: login.php');
    exit();
}

$query = "SELECT * FROM Tour_Guides";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_guide'])) {
    $name = $_POST['name'];
    $language = $_POST['language'];
    $phone = $_POST['phone'];
    $email = $_POST['email'] ?? '';
    $price = $_POST['price'];
    $rating = $_POST['rating'] ?? 0;
    $img_url = '';

    if (isset($_FILES['guide_image']) && $_FILES['guide_image']['error'] == 0) {
        $target_dir = "../img/";
        $file_name = basename($_FILES['guide_image']['name']);
        $target_file = $target_dir . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (move_uploaded_file($_FILES["guide_image"]["tmp_name"], $target_file)) {
                $img_url = $target_file;
            }
        }

    if (!empty($name) && !empty($language) && !empty($phone)) {
        $query = "INSERT INTO Tour_Guides (name, language, phone, email,price,rating, img_url) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$name, $language, $phone, $email, $price, $rating, $img_url]);
        header("Location: guide_manage.php");
        exit();
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_guide'])) {
    $guide_id = $_POST['guide_id'];
    $name = $_POST['name'];
    $language = $_POST['language'];
    $phone = $_POST['phone'];
    $email = $_POST['email'] ?? '';
    $price = $_POST['price'];
    $rating = $_POST['rating'] ?? 0;
    $img_url = $_POST['current_img_url'];

    
    if (isset($_FILES['guide_image']) && $_FILES['guide_image']['error'] == 0) {
        $target_dir = "../img/";
        $file_name = basename($_FILES['guide_image']['name']);
        $target_file = $target_dir . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['hotel_image']['type'], $allowed_types)) {
            $dir_path = dirname($physical_img_path);
            if (move_uploaded_file($_FILES["guide_image"]["tmp_name"], $target_file)) {
                $img_url = $target_file;
            } else {
                $img_url = $target_file; 
            }
        } else {
            $error = "Lỗi: Định dạng file không hợp lệ.";
        }
        
    }

    if (!empty($guide_id) && !empty($name) && !empty($language) && !empty($phone)) {
        $query = "UPDATE Tour_Guides SET name = ?, language = ?, phone = ?, email = ?, rating = ?, img_url = ? ,price=?
                  WHERE guide_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$name, $language, $phone, $email, $rating, $img_url,$price, $guide_id]);
        
        header("Location: guide_manage.php");
        exit();
    }
}


if (isset($_GET['delete_guide'])) {
    $guide_id = $_GET['delete_guide'];
    $query = "DELETE FROM Tour_Guides WHERE guide_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$guide_id]);
    header("Location: guide_manage.php");
    exit();
}

if (isset($_GET['guide_id'])) {
    $guide_id = $_GET['guide_id'];
    $query = "SELECT * FROM Tour_Guides WHERE guide_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$guide_id]);
    $guide = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($guide) {
        header('Content-Type: application/json');
        echo json_encode($guide);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide Management - Travel Admin</title>
<link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
   
    <style>
    
:root {
    --primary-color: #4e73df;
    --secondary-color: #1cc88a;
    --warning-color: #f6c23e;
    --danger-color: #e74a3b;
    --info-color: #36b9cc;
    --dark-color: #5a5c69;
    --light-color: #f8f9fc;
    --border-color: #e3e6f0;
    --sidebar-width: 250px;
}


.main-content {
    margin-left: var(--sidebar-width);
    padding: 1.5rem;
    min-height: 100vh;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark-color);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-profile img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-color);
}

.user-profile h4 {
    font-size: 1rem;
    margin: 0;
    color: var(--dark-color);
}

/* Search Container */
.search-container {
    position: relative;
}

.search-container i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
}

.search-input {
    padding-left: 40px;
    border-radius: 30px;
    border: 1px solid var(--border-color);
    transition: all 0.3s;
}

.search-input:focus {
    box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    border-color: #bac8f3;
}


.stats-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    transition: transform 0.3s;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-card .card-body {
    display: flex;
    align-items: center;
    padding: 1.5rem;
}

.stats-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin-right: 1rem;
    color: white;
}

.bg-primary {
    background-color: var(--primary-color) !important;
}

.bg-success {
    background-color: var(--secondary-color) !important;
}

.bg-warning {
    background-color: var(--warning-color) !important;
}

.stats-info h5 {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: var(--dark-color);
    font-weight: normal;
}

.stats-info h3 {
    font-size: 1.5rem;
    margin: 0;
    font-weight: 700;
    color: var(--dark-color);
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    margin-bottom: 2rem;
    width: 100%;
    padding:0;
    margin: 0;
}

.card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.card-header h5 {
    font-weight: 700;
    color: var(--dark-color);
}

.table {
    color: var(--dark-color);
}

.table thead th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.03em;
    border-bottom: 2px solid var(--border-color);
    vertical-align: middle;
    padding: 1rem;
}

.table tbody td {
    vertical-align: middle;
    padding: 0.75rem 1rem;
}

.table tbody tr:hover {
    background-color: rgba(78, 115, 223, 0.05);
}

/* Guide Image */
.guide-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid var(--light-color);
    box-shadow: 0 0.15rem 0.5rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s;
}

.guide-image:hover {
    transform: scale(1.1);
}

/* Action Buttons */
.action-btn {
    margin-right: 5px;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.action-btn:hover {
    transform: translateY(-2px);
}

.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-danger {
    background-color: var(--danger-color);
    border-color: var(--danger-color);
}

.btn-info {
    background-color: var(--info-color);
    border-color: var(--info-color);
    color: white;
}

.btn-info:hover {
    color: white;
}

/* Star Rating */
.hotel-stars {
    color: var(--warning-color);
    font-size: 0.9rem;
}




/* Modal Styling */
.modal-content {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-header {
    background-color: var(--light-color);
    border-bottom: 1px solid var(--border-color);
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

.modal-footer {
    background-color: var(--light-color);
    border-top: 1px solid var(--border-color);
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
}

.form-label {
    font-weight: 600;
    color: var(--dark-color);
}

.form-control {
    border: 1px solid var(--border-color);
    border-radius: 5px;
    padding: 0.6rem 1rem;
}

.form-control:focus {
    border-color: #bac8f3;
    box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
}

/* Responsive */
@media (max-width: 992px) {
    .sidebar {
        width: 70px;
    }
    
    .sidebar .logo {
        font-size: 1rem;
    }
    
    .sidebar .menu-item span {
        display: none;
    }
    
    .sidebar .menu-item i {
        margin-right: 0;
    }
    
    .main-content {
        margin-left: 70px;
    }
}

@media (max-width: 768px) {
    .stats-card .card-body {
        flex-direction: column;
        text-align: center;
    }
    
    .stats-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    .guide-image {
        width: 50px;
        height: 50px;
    }
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.card, .stats-card {
    animation: fadeIn 0.5s ease-in-out;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}


#image_preview, #edit_image_preview {
    border-radius: 8px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s;
}

#image_preview:hover, #edit_image_preview:hover {
    transform: scale(1.05);
}

#view_image {
    border-radius: 10px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s;
}

#view_image:hover {
    transform: scale(1.05);
}

#view_name {
    color: var(--primary-color);
    font-weight: 700;
    margin-bottom: 1rem;
}


    </style>

</head>

<body>
    <div class="container-fluid">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">ADMIN</div>
            </div>
            <div class="menu">
                    <a href="index.php" class="menu-item "><i class="fas fa-home"></i><span>Trang chủ</span></a>
                    <a href="hotel_manage.php" class="menu-item"><i class="fas fa-hotel"></i><span>Quản lý khách sạn</span></a>
                    <a href="tour_manage.php" class="menu-item"><i class="fas fa-map-marked-alt"></i><span>Quản lý tour</span></a>
                    <a href="ticket_manage.php" class="menu-item  "><i class="fas fa-ticket-alt"></i><span>Quản lý vé</span></a>
                    <a href="guide_manage.php" class="menu-item active"><i class="fas fa-user-tie"></i><span>Quản lý hướng dẫn viên</span></a>
                    <a href="manage_usr.php" class="menu-item "><i class="fas fa-users"></i><span>Quản lý người dùng</span></a>
                    <a href="booking_manage.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Quản lý đặt lịch</span></a>
                </div>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="page-title">Guide Management</div>
                <div class="user-profile">
                    <h4>Admin</h4>
                    <img src="assets/img/admin.jpg" alt="User Profile">
                </div>
            </div>

            <!-- Guide Management Tools -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control search-input" id="guideSearch" placeholder="Tìm kiếm hướng dẫn viên...">
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGuideModal">
                        <i class="fas fa-plus me-2"></i>Thêm hướng dẫn viên
                    </button>
                   
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="stats-icon bg-primary">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stats-info">
                                <h5>Tổng số hướng dẫn viên</h5>
                                <h3><?php echo count($result); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="stats-icon bg-success">
                                <i class="fas fa-language"></i>
                            </div>
                            <div class="stats-info">
                                <h5>Ngôn ngữ phổ biến</h5>
                                <h3>Tiếng Anh</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="stats-icon bg-warning">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stats-info">
                                <h5>Đánh giá trung bình</h5>
                                <h3>4.5/5</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tour_Guide Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Danh sách hướng dẫn viên</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="guideTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ảnh đại diện</th>
                                    <th>Họ và tên</th>
                                    <th>Ngôn ngữ</th>
                                    <th>Số điện thoại</th>
                                    <th>Email</th>
                                    <th>Giá thuê</th>
                                    <th>Đánh giá</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result as $row) { ?>
                                    <tr>
                                        <td><?php echo $row['guide_id'] ?></td>
                                        <?php
                            $image_url = $row['img_url'];
                            $image_url = str_replace("../img/", "../usr/img/", $image_url);
                            ?>
                            <td><img src="<?php echo $image_url; ?>" class="guide-image"></td>
                                        <td><?php echo $row['name'] ?></td>
                                        <td><?php echo $row['language'] ?></td>
                                        <td><?php echo $row['phone'] ?></td>
                                        <td><?php echo $row['email'] ?? 'N/A' ?></td>
                                        <td><?php echo $row['price'] ?? 'N/A' ?></td>
                                        <td>
                                            <div class="hotel-stars">
                                                <?= str_repeat('<i class="fas fa-star"></i>', floor($row['rating'])) ?>
                                                <?= str_repeat('<i class="far fa-star"></i>', 5 - floor($row['rating'])) ?>
                                                <span class="ms-1">(<?= $row['rating'] ?>)</span>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary action-btn edit-guide-btn" 
                                                    data-id="<?php echo $row['guide_id'] ?>" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editGuideModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            </td>
                                            <td>
                                            <button class="btn btn-sm btn-danger action-btn delete-guide-btn" 
                                                    data-id="<?php echo $row['guide_id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Guide Modal -->
    <div class="modal fade" id="addGuideModal" tabindex="-1" aria-labelledby="addGuideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addGuideModalLabel">Thêm hướng dẫn viên mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addGuideForm" action="guide_manage.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="add_guide" value="1">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="language" class="form-label">Ngôn ngữ</label>
                                <input type="text" class="form-control" id="language" name="language" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="col-md-6">
                                <label for="price" class="form-label">Giá thuê</label>
                                <input type="price" class="form-control" id="price" name="price">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="rating" class="form-label">Đánh giá</label>
                                <input type="number" class="form-control" id="rating" name="rating" min="0" max="5" step="0.1" value="0">
                            </div>
                            <div class="col-md-6">
                                <label for="guide_image" class="form-label">Ảnh đại diện</label>
                                <input type="file" class="form-control" id="guide_image" name="guide_image">
                                <div class="mt-2">
                                    <img id="image_preview" src="" alt="Preview Image" style="max-height: 100px; display: none;">
                                </div>
                            </div>
                        </div>
                        
                      
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-primary">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Guide Modal -->
    <div class="modal fade" id="editGuideModal" tabindex="-1" aria-labelledby="editGuideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGuideModalLabel">Chỉnh sửa thông tin hướng dẫn viên</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editGuideForm" action="guide_manage.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="edit_guide" value="1">
                        <input type="hidden" id="edit_guide_id" name="guide_id">
                        <input type="hidden" id="current_img_url" name="current_img_url">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_name" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_language" class="form-label">Ngôn ngữ</label>
                                <input type="text" class="form-control" id="edit_language" name="language" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_phone" class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                            <div class="col-md-6">
                                <label for="price" class="form-label">Giá thuê</label>
                                <input type="price" class="form-control" id="edit_price" name="price">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_rating" class="form-label">Đánh giá</label>
                                <input type="number" class="form-control" id="edit_rating" name="rating" min="0" max="5" step="0.1">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_guide_image" class="form-label">Ảnh đại diện</label>
                                <input type="file" class="form-control" id="edit_guide_image" name="guide_image">
                                <div class="mt-2">
                                    <img id="edit_image_preview" src="" alt="Preview Image" style="max-height: 100px;">
                                </div>
                            </div>
                        </div>
                        
                       
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Guide Modal -->
    <div class="modal fade" id="viewGuideModal" tabindex="-1" aria-labelledby="viewGuideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewGuideModalLabel">Thông tin chi tiết hướng dẫn viên</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <img id="view_image" src="" alt="Guide Image" class="img-fluid rounded" style="max-height: 200px;">
                            <div class="mt-2">
                                <div id="view_stars" class="hotel-stars"></div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h4 id="view_name"></h4>
                            <p><strong>Ngôn ngữ:</strong> <span id="view_language"></span></p>
                            <p><strong>Số điện thoại:</strong> <span id="view_phone"></span></p>
                            <p><strong>Email:</strong> <span id="view_email"></span></p>
                     
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

    <script>
        $(document).ready(function() {
          
            $("#guideSearch").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#guideTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

        
            $('#guide_image').change(function() {
                previewImage(this, '#image_preview');
            });
            
            $('#edit_guide_image').change(function() {
                previewImage(this, '#edit_image_preview');
            });
            
            function previewImage(input, previewElement) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    
                    reader.onload = function(e) {
                        $(previewElement).attr('src', e.target.result).show();
                    }
                    
                    reader.readAsDataURL(input.files[0]);
                }
            }

          
            $('.edit-guide-btn').click(function() {
                const guideId = $(this).data('id');

                $.ajax({
                    url: 'guide_manage.php?guide_id=' + guideId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#edit_guide_id').val(data.guide_id);
                        $('#edit_name').val(data.name);
                        $('#edit_language').val(data.language);
                        $('#edit_phone').val(data.phone);
                        $('#edit_email').val(data.email);
                        $('#edit_price').val(data.price);
                        $('#edit_rating').val(data.rating);
                        $('#current_img_url').val(data.img_url);

                        if (data.img_url) {
                            $('#edit_image_preview').attr('src', data.img_url).show();
                        } else {
                            $('#edit_image_preview').hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('Không thể tải thông tin hướng dẫn viên. Vui lòng thử lại sau.');
                    }
                });
            });

            $('.view-guide-btn').click(function() {
                const guideId = $(this).data('id');

                $.ajax({
                    url: 'guide_manage.php?guide_id=' + guideId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#view_name').text(data.name);
                        $('#view_language').text(data.language);
                        $('#view_phone').text(data.phone);
                        $('#view_email').text(data.email || 'N/A');
                        
                  

                        if (data.img_url) {
                            $('#view_image').attr('src', data.img_url).show();
                        } else {
                            $('#view_image').attr('src', '../usr/img/default-guide.jpg').show();
                        }

                        
                        const rating = Math.floor(data.rating);
                        let starsHtml = '';
                        for (let i = 0; i < rating; i++) {
                            starsHtml += '<i class="fas fa-star"></i>';
                        }
                        for (let i = rating; i < 5; i++) {
                            starsHtml += '<i class="far fa-star"></i>';
                        }
                        starsHtml += `<span class="ms-1">(${data.rating})</span>`;
                        $('#view_stars').html(starsHtml);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('Không thể tải thông tin hướng dẫn viên. Vui lòng thử lại sau.');
                    }
                });
            });
                
                $('.delete-guide-btn').click(function() {
                    const guideId = $(this).data('id');
                    if (confirm('Bạn có chắc chắn muốn xóa hướng dẫn viên này?')) {
                        window.location.href = 'guide_manage.php?delete_guide=' + guideId;
                    }
                });
            });
    
    </script>
</body>

</html>
