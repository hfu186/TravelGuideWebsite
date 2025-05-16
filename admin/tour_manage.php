<?php
session_start();
include('../server/connectdb.php');
if (!isset($_SESSION['admin_login'] )) {
    echo ("Vui lòng đăng nhập");
    header('location: login.php');
    exit();
}
$items_per_page = 5;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $items_per_page;
$query = "SELECT *
            FROM tours
            LIMIT :offset, :limit";
function getDestinationName($conn, $destination_id)
{
    $stmt = $conn->prepare("SELECT name FROM Destinations WHERE destination_id = ?");
    $stmt->execute([$destination_id]);
    $destination = $stmt->fetch(PDO::FETCH_ASSOC);
    return $destination ? $destination['name'] : 'Unknown';
}
try {
    $destinations_query = "SELECT * FROM Destinations";
    $destinations_stmt = $conn->prepare($destinations_query);
    $destinations_stmt->execute();
    $destinations = $destinations_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Lỗi lấy danh sách điểm đến: " . $e->getMessage();
    $destinations = [];
}

try {
    $query = "SELECT *
                FROM Tours t 
                LEFT JOIN Destinations d ON t.destination_id = d.destination_id 
                LIMIT :offset, :limit";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_query = "SELECT COUNT(*) as total FROM Tours";
    $total_stmt = $conn->prepare($total_query);
    $total_stmt->execute();
    $total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total / $items_per_page);

} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
    $result = [];
    $total_pages = 0;
}
// delete
if (isset($_GET['delete_tour'])) {
    $tour_id = $_GET['delete_tour'];

    $query = "DELETE FROM tours WHERE tour_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$tour_id]);

    header("Location: tour_manage.php");
    exit();
}
// thêm tour
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tour'])) {
    $tourname = $_POST['tourname'];
    $price = $_POST['price'];
    $destination_id = $_POST['destination_id'];
    $description = $_POST['description'];
    $days = $_POST['days'];
    $content = $_POST['content'];
    $img_url = null;
    $rating = 0;
    if (isset($_FILES['add_tour_image']) && $_FILES['add_tour_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../img/";
        $file_name = basename($_FILES['add_tour_image']['name']);
        $target_file = $target_dir . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
        if (in_array($_FILES['add_tour_image']['type'], $allowed_types)) {
            if (!move_uploaded_file($_FILES['add_tour_image']['tmp_name'], $target_file)) {
                $error = "Error: Failed to move uploaded file.";
            } else {
                $img_url = $target_file;
            }
        } else {
            $error = "Error: Invalid file type.";
        }
    } else {
        $error = "Error: No file uploaded or upload error.";
    }
    

    if (!empty($tourname) && !empty($price) && !empty($destination_id) && !empty($description)) {
        $query = "INSERT INTO Tours 
            (tour_name, price, destination_id,content,description, image_url, rating, days) 
            VALUES (?, ?, ?, ?, ?, ?,?,?)";

        $stmt = $conn->prepare($query);
        try {
            $stmt->execute([
                $tourname,
                $price,
                $destination_id,
                $content,
                $description,
                $img_url,
                $rating,
                $days
            ]);
            $tour_id = $conn->lastInsertId();
            if (isset($_POST['tour_dates']) && is_array($_POST['tour_dates'])) {
                $date_query = "INSERT INTO tour_dates 
                                (tour_id, departure_date, end_date, max_slots, available_slots) 
                                VALUES (?, ?, ?, ?, ?)";
                $date_stmt = $conn->prepare($date_query);

                try {
                    foreach ($_POST['tour_dates'] as $index => $date_info) {

                        $departure_date = isset($date_info['departure_date']) ? $date_info['departure_date'] : null;
                        $end_date = isset($date_info['end_date']) ? $date_info['end_date'] : null;
                        $max_slots = isset($date_info['max_slots']) ? intval($date_info['max_slots']) : 30;
                        if (empty($departure_date) || empty($end_date)) {
                            error_log("Bỏ qua ngày tour không hợp lệ tại index $index: " . print_r($date_info, true));
                            continue;
                        }

                        if (strtotime($departure_date) > strtotime($end_date)) {
                            error_log("Ngày bắt đầu phải nhỏ hơn ngày kết thúc tại index $index");
                            continue;
                        }

                        $result = $date_stmt->execute([
                            $tour_id,
                            $departure_date,
                            $end_date,
                            $max_slots,
                            $max_slots
                        ]);

                        if (!$result) {

                            error_log("Lỗi insert tour date tại index $index: " . print_r($date_stmt->errorInfo(), true));
                        } else {
                            error_log("Insert tour date thành công: $departure_date - $end_date");
                        }
                    }
                } catch (PDOException $e) {

                    error_log("Lỗi ngoại lệ khi insert tour dates: " . $e->getMessage());


                    $_SESSION['error'] = "Không thể thêm ngày tour: " . $e->getMessage();
                }
            } else {

                error_log("Không tìm thấy dữ liệu tour dates trong POST");
            }
        } catch (PDOException $e) {

            error_log("Lỗi ngoại lệ khi insert tour dates: " . $e->getMessage());

            $_SESSION['error'] = "Không thể thêm ngày tour: " . $e->getMessage();

        }
    }
}
if (isset($_GET['get_tour_dates']) && isset($_GET['tour_id'])) {
    $tour_id = $_GET['tour_id'];
    $date_query = "SELECT * FROM tour_dates WHERE tour_id = ?";
    $date_stmt = $conn->prepare($date_query);
    $date_stmt->execute([$tour_id]);
    $tour_dates = $date_stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($tour_dates);
    exit;
}
if (isset($_GET['tour_id'])) {
    $tour_id = $_GET['tour_id'];
    $stmt = $conn->prepare("SELECT * FROM Tours WHERE tour_id = ?");
    $stmt->execute([$tour_id]);
    $tour = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tour) {
        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {

            header('Content-Type: application/json');
            echo json_encode($tour);
            exit();
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Không tìm thấy tour!']);
        exit();
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_tour'])) {
    $tour_id = $_POST['tour_id'];
    $tourname = $_POST['tourname'];
    $price = $_POST['price'];
    $destination_id = $_POST['destination_id'];
    $description = $_POST['description'];
    $days = $_POST['days'];
    $img_url = $_POST['img_url'];
    $content = $_POST['content'];
    $departure_date = '';
    $end_date = '';
    $max_slots ='';
    if (isset($_FILES['tour_image']) && $_FILES['tour_image']['error'] == 0) {
        $file_tmp = $_FILES['tour_image']['tmp_name'];
        $file_name = basename($_FILES['tour_image']['name']);
        $physical_img_path = dirname(__DIR__) . 'admin/img/' . $file_name;
        $img_url = '../img/' . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['tour_image']['type'], $allowed_types)) {
            $dir_path = dirname($physical_img_path);
            if (!is_dir($dir_path)) {
                mkdir($dir_path, 0775, true);
            }
            if (!move_uploaded_file($file_tmp, $physical_img_path)) {
                $error = "Error: Failed to move uploaded file.";
            }
        } else {
            $error = "Error: Invalid file type.";
        }
    }

    if (!empty($tour_id) && !empty($tourname) && !empty($price) && !empty($destination_id) && !empty($description)) {
        $query = "UPDATE Tours 
                SET tour_name = ?, price = ?, destination_id = ?, content = ?,
                    description = ?, image_url = ?, days = ?
                WHERE tour_id = ?";

        $stmt = $conn->prepare($query);

        try {
            $stmt->execute([
                $tourname,
                $price,
                $destination_id,
                $content,
                $description,
                $img_url,
                $days,
                $tour_id
            ]);

            $delete_dates_query = "DELETE FROM tour_dates WHERE tour_id = ?";
            $delete_stmt = $conn->prepare($delete_dates_query);
            $delete_stmt->execute([$tour_id]);
            if (isset($_POST['tour_dates']) && is_array($_POST['tour_dates'])) {
                $date_query = "INSERT INTO tour_dates 
                            (tour_id, departure_date, end_date, max_slots, available_slots) 
                            VALUES (?, ?, ?, ?, ?)";
                $date_stmt = $conn->prepare($date_query);

                foreach ($_POST['tour_dates'] as $date_info) {
                    $departure_date = $date_info['departure_date'];
                    $end_date = $date_info['end_date'];
                    $max_slots = $date_info['max_slots'];

                    $date_stmt->execute([
                        $tour_id,
                        $departure_date,
                        $end_date,
                        $max_slots,
                        $max_slots
                    ]);
                }
            }
            echo "<script>
                alert('Cập nhật tour thành công!'); 
                 window.location.href='tour_manage.php';
            </script>";
           
        } catch (PDOException $e) {
            echo "<script>
                alert('Lỗi: " . $e->getMessage() . "');
            </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Management - Travel Admin</title>
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
                <a href="tour_manage.php" class="menu-item active"><i class="fas fa-map-marked-alt"></i><span>Quản lý
                        tour</span></a>
                <a href="ticket_manage.php" class="menu-item  "><i class="fas fa-ticket-alt"></i><span>Quản lý
                        vé</span></a>
                <a href="guide_manage.php" class="menu-item"><i class="fas fa-user-tie"></i><span>Quản lý hướng dẫn
                        viên</span></a>
                <a href="manage_usr.php" class="menu-item "><i class="fas fa-users"></i><span>Quản lý người
                        dùng</span></a>
                <a href="booking_manage.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Quản lý đặt
                        lịch</span></a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="page-title">Quản lý Tour</div>
                <div class="user-profile">
                    <h4>Admin</h4>
                    <img src="assets/img/admin.jpg" alt="User Profile">
                </div>
            </div>

            <!-- Tours Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Tất cả Tours</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTourModal">
                        <i class="fas fa-plus"></i> Thêm Tour mới
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Hình ảnh</th>
                                    <th>Tên Tour</th>
                                    <th>Giá</th>
                                    <th>Điểm đến</th>
                                    <th>Đánh giá</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result as $tour): ?>
                                    <tr>
                                        <td><?php echo $tour['tour_id']; ?></td>
                                        <td><img src="<?= str_replace("../img/", "../usr/img/", $tour['image_url']) ?>"
                                                class="ticket-image"></td>
                                        <td>
                                            <p class="w-100"><?php echo $tour['tour_name']; ?></p>
                                        </td>
                                        <td><?php echo number_format($tour['price'], 0, ',', '.') . ' VNĐ'; ?></td>
                                        <td><?php echo getDestinationName($conn, $tour['destination_id']); ?></td>

                                        <td>
                                            <div class="rating">
                                                <?php
                                                $rating = $tour['rating'];
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '<i class="fas fa-star text-warning"></i>';
                                                    } else if ($i - 0.5 <= $rating) {
                                                        echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star text-warning"></i>';
                                                    }
                                                }
                                                ?>
                                                <span class="ms-1">(<?php echo $tour['rating']; ?>)</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-info edit-tour-btn"
                                                    data-id="<?php echo $tour['tour_id']; ?>" data-bs-toggle="modal"
                                                    data-bs-target="#editTourModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-tour-btn"
                                                    data-id="<?php echo $tour['tour_id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $current_page - 1 ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $current_page == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $current_page + 1 ?>">Next</a>
            </li>
        </ul>
    </nav>

    <!-- Add Tour Modal -->
    <div class="modal fade" id="addTourModal" tabindex="-1" aria-labelledby="addTourModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTourModalLabel">Thêm Tour mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="tour_manage.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="tourname" class="form-label">Tên Tour</label>
                                        <input type="text" class="form-control" id="tourname" name="tourname" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="price" class="form-label">Giá Tour</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="price" name="price" required>
                                            <span class="input-group-text">VNĐ</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="destination_id" class="form-label">Điểm đến</label>
                                        <select class="form-select" id="destination_id" name="destination_id" required>
                                            <option value="">Chọn điểm đến</option>
                                            <?php foreach ($destinations as $destination): ?>
                                                <option value="<?php echo $destination['destination_id']; ?>">
                                                    <?php echo $destination['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="days" class="form-label">Số ngày</label>
                                        <input type="number" class="form-control" id="days" name="days" required
                                            min="1">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="content" class="form-label">Mô tả ngắn</label>
                                    <textarea class="form-control" id="content" name="content" rows="2"
                                        required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Lịch trình</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"
                                        required></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        Hình ảnh Tour
                                    </div>
                                    <div class="card-body">
                                        <input type="file" class="form-control" id="add_tour_image"
                                            name="add_tour_image" accept="image/*">
                                        <div class="mt-2 text-center">
                                            <img id="image_preview" src="" class="img-fluid"
                                                style="max-height: 200px; display: none;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Các ngày Tour</span>
                                <button class="btn" type="button" id="addTourDateBtn">Thêm ngày tour</button>
                            </div>
                            <div class="card-body" id="tour_dates_container">
                                <div class="tour-date-group mb-3">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label>Ngày bắt đầu</label>
                                            <input type="date" name="tour_dates[0][departure_date]" class="form-control"
                                                required>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Ngày kết thúc</label>
                                            <input type="date" name="tour_dates[0][end_date]" class="form-control"
                                                required>
                                        </div>

                                        <div class="col-md-2">
                                            <label>Số chỗ</label>
                                            <input type="number" name="tour_dates[0][max_slots]" class="form-control"
                                                value="30" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" name="add_tour" class="btn btn-primary">Thêm Tour</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Tour Modal -->
    <div class="modal fade" id="editTourModal" tabindex="-1" aria-labelledby="editTourModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTourModalLabel">Chỉnh sửa Tour</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="tour_manage.php" method="POST" enctype="multipart/form-data" id="editTourForm">
                        <input type="hidden" name="tour_id" id="edit_tour_id">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_tourname" class="form-label">Tên Tour</label>
                                        <input type="text" class="form-control" id="edit_tourname" name="tourname"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_price" class="form-label">Giá Tour</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_price" name="price"
                                                required>
                                            <span class="input-group-text">VNĐ</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_destination_id" class="form-label">Điểm đến</label>
                                        <select class="form-select" id="edit_destination_id" name="destination_id"
                                            required>
                                            <option value="">Chọn điểm đến</option>
                                            <?php foreach ($destinations as $destination): ?>
                                                <option value="<?php echo $destination['destination_id']; ?>">
                                                    <?php echo $destination['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_days" class="form-label">Số ngày</label>
                                        <input type="number" class="form-control" id="edit_days" name="days" required
                                            min="1">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="edit_content" class="form-label">Mô tả ngắn</label>
                                    <textarea class="form-control" id="edit_content" name="content" rows="2"
                                        required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Lịch trình</label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="4"
                                        required></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        Hình ảnh Tour
                                    </div>
                                    <div class="card-body">
                                        <input type="file" class="form-control" id="tour_image" name="tour_image"
                                            accept="image/*">
                                        <input type="hidden" id="edit_img_url" name="img_url">
                                        <div class="mt-2 text-center" id="current_image_container">
                                            <img id="current_image" src="" alt="Current Image"
                                                style="max-height: 200px; display: none;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Các ngày Tour</span>
                                <button class="btn" type="button" id="editTourDateBtn">Thêm ngày tour</button>
                            </div>
                            <div class="card-body" id="edit_tour_dates_container">

                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" name="edit_tour" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $(document).on('click', '#addTourDateBtn, #editTourDateBtn', function () {
                const isEdit = $(this).attr('id') === 'editTourDateBtn';
                const container = isEdit ? '#edit_tour_dates_container' : '#tour_dates_container';
                let dateIndex = $(container + ' .tour-date-group').length;

                const tourDateHtml = `
            <div class="tour-date-group mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <label>Ngày bắt đầu</label>
                        <input type="date" name="tour_dates[${dateIndex}][departure_date]" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label>Ngày kết thúc</label>
                        <input type="date" name="tour_dates[${dateIndex}][end_date]" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label>Số chỗ</label>
                        <input type="number" name="tour_dates[${dateIndex}][max_slots]" class="form-control" value="30" min="1">
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger remove-tour-date form-control">Xóa</button>
                    </div>
                </div>
            </div>
        `;
                $(container).append(tourDateHtml);
            });

            $(document).on('click', '.remove-tour-date', function () {
                $(this).closest('.tour-date-group').remove();
            });
            $('#add_tour_image, #tour_image').change(function () {
                const file = this.files[0];
                const previewId = $(this).attr('id') === 'add_tour_image' ? '#image_preview' : '#current_image';

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        $(previewId).attr('src', e.target.result).show();
                    }
                    reader.readAsDataURL(file);
                }
            });
            $('.delete-tour-btn').click(function () {
                const tourId = $(this).data('id');
                if (confirm('Bạn có chắc chắn muốn xóa tour này?')) {
                    window.location.href = 'tour_manage.php?delete_tour=' + tourId;
                }
            });
            $('.edit-tour-btn').click(function () {
                const tourId = $(this).data('id');
                $('#edit_tour_dates_container').empty();
                $.ajax({
                    url: 'tour_manage.php',
                    type: 'GET',
                    data: { tour_id: tourId },
                    dataType: 'json',
                    success: function (data) {
                        $('#edit_tour_id').val(data.tour_id);
                        $('#edit_tourname').val(data.tour_name);
                        $('#edit_price').val(data.price);
                        $('#edit_destination_id').val(data.destination_id);
                        $('#edit_days').val(data.days);
                        $('#edit_description').val(data.description);
                        $('#edit_content').val(data.content);
                        $('#edit_img_url').val(data.image_url);
                        if (data.image_url) {
                            $('#current_image').attr('src', data.image_url).show();
                        } else {
                            $('#current_image').hide();
                        }
                        $.ajax({
                            url: 'tour_manage.php',
                            type: 'GET',
                            data: {
                                get_tour_dates: true,
                                tour_id: tourId
                            },
                            dataType: 'json',
                            success: function (tourDates) {
                                console.log("Tour dates:", tourDates);
                                if (tourDates && tourDates.length > 0) {
                                    tourDates.forEach(function (date, index) {
                                        addTourDateToEdit(index, date);
                                    });
                                } else {
                                    addTourDateToEdit(0);
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('Lỗi lấy ngày tour:', error);
                                addTourDateToEdit(0);
                            }
                        });
                    },
                    error: function (xhr, status, error) {
                        console.error('Lỗi lấy thông tin tour:', error);
                        alert('Không thể tải thông tin tour. Vui lòng thử lại.');
                    }
                });
            });
            function addTourDateToEdit(index, date = null) {
                const tourDateHtml = `
            <div class="tour-date-group mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <label>Ngày bắt đầu</label>
                        <input type="date" name="tour_dates[${index}][departure_date]" 
                               class="form-control departure-date" 
                               value="${date ? formatDate(date.departure_date) : ''}" required>
                    </div>
                    <div class="col-md-3">
                        <label>Ngày kết thúc</label>
                        <input type="date" name="tour_dates[${index}][end_date]" 
                               class="form-control end-date" 
                               value="${date ? formatDate(date.end_date) : ''}" required>
                    </div>
                    <div class="col-md-2">
                        <label>Số chỗ</label>
                        <input type="number" name="tour_dates[${index}][max_slots]" 
                               class="form-control" 
                               value="${date ? date.max_slots : 30}" min="1">
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger remove-tour-date form-control">Xóa</button>
                    </div>
                </div>
            </div>`;

                $('#edit_tour_dates_container').append(tourDateHtml);
                if ($('#edit_tour_dates_container .tour-date-group').length <= 1) {
                    $('#edit_tour_dates_container .remove-tour-date').hide();
                }
            }
            function formatDate(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toISOString().split('T')[0];
            }
        });

    </script>


</body>

</html>