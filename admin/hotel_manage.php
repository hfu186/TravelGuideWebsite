<?php
session_start();
include('../server/connectdb.php');
if (!isset($_SESSION['admin_login'])) {
    echo ("Vui lòng đăng nhập");
    header('location: login.php');
    exit();
}
$query = "SELECT * FROM Hotels ";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
$destQuery = "SELECT destination_id, name FROM Destinations";
$destStmt = $conn->prepare($destQuery);
$destStmt->execute();
$destinations = $destStmt->fetchAll(PDO::FETCH_ASSOC);

$roomsQuery = "SELECT r.*, h.hotel_name 
               FROM Rooms r 
               JOIN Hotels h ON r.hotel_id = h.hotel_id 
               ORDER BY h.hotel_name, r.room_type";
$roomsStmt = $conn->prepare($roomsQuery);
$roomsStmt->execute();
$allRooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_hotel'])) {
    $hotel_id = $_POST['hotel_id'];
    $hotel_name = $_POST['hotel_name'];
    $location = $_POST['location'];
    $destination_id = $_POST['destination_id'];
    $rating = $_POST['rating'];
    $img_url = '';
    $description = $_POST['description'];
    $error = null;

    if (isset($_FILES['hotel_image']) && $_FILES['hotel_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../img/";
        $file_name = basename($_FILES['hotel_image']['name']);
        $target_file = $target_dir . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['hotel_image']['type'], $allowed_types)) {
            $dir_path = dirname($physical_img_path);
            if (!move_uploaded_file($_FILES['hotel_image']['tmp_name'], $target_file)) {
                $error = "Lỗi: Không thể di chuyển file đã tải lên.";
            } else {
                $img_url = $target_file; 
            }
        } else {
            $error = "Lỗi: Định dạng file không hợp lệ.";
        }
    } elseif (!empty($_POST['img_url'])) {
        $img_url = $_POST['img_url'];
    }

    if (!$error) {
        if (empty($img_url)) {
            $stmt = $conn->prepare("SELECT img_url FROM Hotels WHERE hotel_id = ?");
            $stmt->execute([$hotel_id]);
            $existingHotel = $stmt->fetch(PDO::FETCH_ASSOC);
            $img_url = $existingHotel['img_url'];
        }

        $updateQuery = "UPDATE Hotels 
                        SET hotel_name = :hotel_name, 
                            location = :location, 
                            destination_id = :destination_id, 
                            rating = :rating, 
                            img_url = :img_url ,
                            description = :description
                        WHERE hotel_id = :hotel_id";

        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':hotel_name', $hotel_name);
        $updateStmt->bindParam(':location', $location);
        $updateStmt->bindParam(':destination_id', $destination_id);
        $updateStmt->bindParam(':rating', $rating);
        $updateStmt->bindParam(':img_url', $img_url);
        $updateStmt->bindParam(':description', $description);
        $updateStmt->bindParam(':hotel_id', $hotel_id);


        if ($updateStmt->execute()) {
            header("Location: hotel_manage.php?edit_success=1");
            exit();
        } else {
            $error = "Không thể cập nhật khách sạn";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] == 'get_all_rooms') {
        header('Content-Type: application/json');
        echo json_encode($allRooms);
        exit;
    } elseif ($_GET['action'] == 'get_rooms' && isset($_GET['hotel_id'])) {
        $hotelId = $_GET['hotel_id'];
        $stmt = $conn->prepare("SELECT * FROM Rooms WHERE hotel_id = ?");
        $stmt->execute([$hotelId]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($rooms);
        exit;
    } elseif ($_GET['action'] == 'get_room' && isset($_GET['id'])) {
        $roomId = $_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM Rooms WHERE room_id = ?");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($room ?: ['error' => 'Room not found']);
        exit;
    }
}


if (isset($_GET['delete_hotel'])) {
    $hotel_id = intval($_GET['delete_hotel']);

    $query = "DELETE FROM hotels WHERE hotel_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$hotel_id]);

    header("Location: hotel_manage.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_hotel') {
    $hotelId = $_GET['id'];

    if (!$hotelId) {
        throw new Exception('Invalid hotel ID');
    }
    $stmt = $conn->prepare("
    SELECT h.*, d.name as destination_name 
    FROM Hotels h
    LEFT JOIN Destinations d ON h.destination_id = d.destination_id
    WHERE h.hotel_id = ?
    ");

    $stmt->execute([$hotelId]);
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($hotel);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hotel'])) {
    $hotel_name = $_POST['hotel_name'];
    $location = $_POST['location'];
    $destination_id = $_POST['destination_id'];
    $rating = $_POST['rating'];
    $img_url = '';
    $description = $_POST['description'];
    $img_details = [];
    $error = null;
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $detailImages = ['img_detail1', 'img_detail2', 'img_detail3'];
    if (isset($_FILES['hotel_images']['name'][0]) && $_FILES['hotel_images']['error'][0] == UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['hotel_images']['name'][0]);
        $file_type = $_FILES['hotel_images']['type'][0];
        if (in_array($file_type, $allowed_types)) {
            $img_url = '../img/' . $file_name;
        } else {
            $error = "Lỗi: Định dạng ảnh chính không hợp lệ.";
        }
    } elseif (!empty($_POST['img_url'])) {
        $img_url = $_POST['img_url'];
    } else {
        $error = "Lỗi: Vui lòng cung cấp hình ảnh hoặc URL hình ảnh.";
    }
    if (!$error) {
        foreach ($detailImages as $index => $detailImage) {
            $fileIndex = $index + 1;

            if (isset($_FILES['hotel_images']['name'][$fileIndex]) &&$_FILES['hotel_images']['error'][$fileIndex] == UPLOAD_ERR_OK) {
                $file_name = $_FILES['hotel_images']['name'][$fileIndex];
                $file_type = $_FILES['hotel_images']['type'][$fileIndex];

                if (in_array($file_type, $allowed_types)) {

                    $img_details[$detailImage] = '../img/' . $file_name;
                } else {
                    $error = "Lỗi: Định dạng ảnh chi tiết không hợp lệ.";
                    break;
                }
            } else {
                $img_details[$detailImage] = null;
            }
        }
    }
    if (!$error) {
        try {
            $insertQuery = "INSERT INTO Hotels (
                hotel_name, 
                location, 
                destination_id, 
                rating, 
                img_url, 
                img_detail1, 
                img_detail2, 
                img_detail3,
                description
            ) VALUES (
                :hotel_name, 
                :location, 
                :destination_id, 
                :rating, 
                :img_url, 
                :img_detail1, 
                :img_detail2, 
                :img_detail3,
                :description
            )";

            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bindParam(':hotel_name', $hotel_name);
            $insertStmt->bindParam(':location', $location);
            $insertStmt->bindParam(':destination_id', $destination_id);
            $insertStmt->bindParam(':rating', $rating);
            $insertStmt->bindParam(':img_url', $img_url);
            $insertStmt->bindParam(':img_detail1', $img_details['img_detail1']);
            $insertStmt->bindParam(':img_detail2', $img_details['img_detail2']);
            $insertStmt->bindParam(':img_detail3', $img_details['img_detail3']);
            $insertStmt->bindParam(':description', $description);

            if ($insertStmt->execute()) {
                header("Location: hotel_manage.php?success=1");
                exit();
            } else {
                $error = "Không thể thêm khách sạn";
            }
        } catch (PDOException $e) {
            $error = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
    $img_url = '';

    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['room_image']['tmp_name'];
        $file_name = basename($_FILES['room_image']['name']);

        $physical_img_path = dirname(__DIR__) . '/img/rooms/' . $file_name;
        $img_url = '../img/rooms/' . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['room_image']['type'], $allowed_types)) {
            $dir_path = dirname($physical_img_path);
            if (!is_dir($dir_path)) {
                mkdir($dir_path, 0775, true);
            }
            if (!move_uploaded_file($file_tmp, $physical_img_path)) {
                $error = "Lỗi: Không thể di chuyển file đã tải lên.";
            }
        } else {
            $error = "Lỗi: Định dạng file không hợp lệ.";
        }
    }

    if (!isset($error)) {
        if (!empty($_POST['room_id'])) {
            $stmt = $conn->prepare("UPDATE Rooms SET 
                room_type = ?,
                price_per_night = ?,
                max_guests = ?
                " . ($img_url ? ", img_url = ?" : "") . "
                WHERE room_id = ?
            ");

            $params = [
                $_POST['room_type'],
                $_POST['price_per_night'],
                $_POST['max_guests']
            ];

            if ($img_url) {
                $params[] = $img_url;
            }

            $params[] = $_POST['room_id'];
            $stmt->execute($params);
        } else {
            $stmt = $conn->prepare("INSERT INTO Rooms 
                (hotel_id, room_type, price_per_night, max_guests, img_url)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['hotel_id'],
                $_POST['room_type'],
                $_POST['price_per_night'],
                $_POST['max_guests'],
                $img_url
            ]);
        }

        if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        header("Location: hotel_manage.php?room_success=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == 'delete_room') {
    $roomId = $_GET['id'];
    $stmt = $conn->prepare("Delete from rooms WHERE room_id = ?");
    $stmt->execute([$roomId]);

    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    header("Location: hotel_manage.php?room_deleted=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Khách Sạn - Tourism Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/CSS/STYLE.css">

</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">ADMIN</div>
            </div>
            <div class="menu">
                <a href="index.php" class="menu-item ">
                    <i class="fas fa-home"></i>
                    <span>Trang chủ</span>
                </a>
                <a href="hotel_manage.php" class="menu-item active">
                    <i class="fas fa-hotel"></i>
                    <span>Quản lý khách sạn</span>
                </a>
                <a href="tour_manage.php" class="menu-item">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Quản lý tour</span>
                </a>
                <a href="ticket_manage.php" class="menu-item">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Quản lý vé</span>
                </a>
                <a href="guide_manage.php" class="menu-item">
                    <i class="fas fa-user-tie"></i>
                    <span>Quản lý hướng dẫn viên</span>
                </a>
                <a href="users.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Quản lý người dùng</span>
                </a>
                <a href="booking_manage.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Quản lý đặt lịch </span>
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="page-title">Quản Lý Khách Sạn</div>
                <div class="user-profile">
                    <span>Admin</span>
                    <img src="assets/img/admin.jpg" alt="User Profile">
                </div>
            </div>

            <div id="alertContainer">
                <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Khách sạn đã được thêm thành công!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['room_success']) && $_GET['room_success'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Thông tin phòng đã được lưu thành công!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['room_deleted']) && $_GET['room_deleted'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Phòng đã được xóa thành công!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="action-buttons mb-4">
                <button class="btn btn-primary" id="addHotelBtn"><i class="fas fa-plus"></i> Thêm Khách Sạn Mới</button>
                <button class="btn btn-info text-white" id="viewAllRoomsBtn"><i class="fas fa-door-open"></i> Xem Tất Cả
                    Phòng</button>
            </div>

            <div class="hotels-grid">
                <?php foreach ($result as $row) { ?>
                    <div class="hotel-card">
                        <div class="hotel-image">
                            <?php
                            $image_url = $row['img_url'];

                            if (strpos($image_url, '../img/') === 0) {
                                $image_url = str_replace("../img/", "../usr/img/", $image_url);
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>"
                                alt="<?php echo htmlspecialchars($row['hotel_name']); ?>" class="ticket-image">
                        </div>
                        <div class="hotel-details">
                            <div class="hotel-name"><?= htmlspecialchars($row['hotel_name']) ?></div>
                            <div class="hotel-location"><i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($row['location']) ?></div>
                            <div class="hotel-stars">
                                <?= str_repeat('<i class="fas fa-star"></i>', floor($row['rating'])) ?>
                                <?= str_repeat('<i class="far fa-star"></i>', 5 - floor($row['rating'])) ?>
                            </div>
                            <div class="hotel-actions">
                                <button class="hotel-btn edit-btn" id="editHotelBtn"
                                    data-hotel-id="<?= $row['hotel_id'] ?>">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button class="btn btn-danger delete-hotel-btn"
                                    onclick="confirmDelete(<?= $row['hotel_id'] ?>, '<?= htmlspecialchars($row['hotel_name']) ?>')">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>




                                <button class="hotel-btn manage-rooms-btn" data-hotel-id="<?= $row['hotel_id'] ?>"
                                    data-hotel-name="<?= htmlspecialchars($row['hotel_name']) ?>">
                                    <i class="fas fa-door-open"></i> Quản Lý Phòng
                                </button>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <?php if (empty($result)): ?>
                    <div class="alert alert-info w-100 text-center">
                        <i class="fas fa-info-circle"></i> Không có khách sạn nào trong hệ thống.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- Modal Thêm Khách Sạn -->
    <div class="modal fade" id="addHotelModal" tabindex="-1" aria-labelledby="addHotelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addHotelModalLabel">Thêm Khách Sạn Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" enctype="multipart/form-data" id="addHotelForm">
                        <div class="mb-3">
                            <label for="hotel_name" class="form-label">Tên khách sạn</label>
                            <input type="text" class="form-control" id="hotel_name" name="hotel_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Địa điểm</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label for="destination_id" class="form-label">Điểm đến</label>
                            <select class="form-select" id="destination_id" name="destination_id" required>
                                <option value="">Chọn điểm đến</option>
                                <?php foreach ($destinations as $destination): ?>
                                    <option value="<?= $destination['destination_id'] ?>">
                                        <?= htmlspecialchars($destination['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả khách sạn</label>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="rating" class="form-label">Đánh giá (0.0 - 5.0)</label>
                            <input type="number" class="form-control" id="rating" name="rating" min="0" max="5"
                                step="0.1" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ảnh</label>
                            <nav>
                                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                    <button class="nav-link active" id="upload-tab-btn" data-bs-target="#upload-tab"
                                        type="button" role="tab" aria-selected="true">Tải ảnh lên</button>
                                    <button class="nav-link" id="url-tab-btn" data-bs-target="#url-tab" type="button"
                                        role="tab" aria-selected="false">URL ảnh</button>
                                </div>
                            </nav>
                            <div class="tab-content mt-2" id="nav-tabContent">
                                <div class="tab-pane fade show active" id="upload-tab" role="tabpanel">
                                    <div class="mb-3">
                                        <input type="file" class="form-control" id="hotel_images" name="hotel_images[]"
                                            accept="image/*" multiple>
                                        <div class="mt-2">
                                            <div id="preview-images" class="d-none"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="url-tab" role="tabpanel">
                                    <input type="text" class="form-control" id="img_url" name="img_url"
                                        placeholder="Nhập URL hình ảnh">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" name="add_hotel" class="btn btn-primary">Thêm khách sạn</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- sua khach san -->
    <div class="modal fade" id="editHotelModal" tabindex="-1" aria-labelledby="editHotelModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editHotelModal">Sửa Khách Sạn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="hotel_manage.php" enctype="multipart/form-data" id="editHotelForm">
                        <input type="hidden" id="hotel_id" name="hotel_id" value="">
                        <div class="mb-3">
                            <label for="hotel_name" class="form-label">Tên khách sạn</label>
                            <input type="text" class="form-control" id="hotel_name" name="hotel_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Địa điểm</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả khách sạn</label>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="destination_id" class="form-label">Điểm đến</label>
                            <select class="form-select" id="destination_id" name="destination_id" required>
                                <option value="">Chọn điểm đến</option>
                                <?php foreach ($destinations as $destination): ?>
                                    <option value="<?= $destination['destination_id'] ?>">
                                        <?= htmlspecialchars($destination['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="rating" class="form-label">Đánh giá (0.0 - 5.0)</label>
                            <input type="number" class="form-control" id="rating" name="rating" min="0" max="5"
                                step="0.1" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ảnh</label>
                            <nav>
                                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                    <button class="nav-link active" id="upload-tab-btn" data-bs-target="#upload-tab"
                                        type="button" role="tab" aria-selected="true">Tải ảnh lên</button>
                                    <button class="nav-link" id="url-tab-btn" data-bs-target="#url-tab" type="button"
                                        role="tab" aria-selected="false">URL ảnh</button>
                                </div>
                            </nav>
                            <div class="tab-content mt-2" id="nav-tabContent">
                                <div class="tab-pane fade show active" id="upload-tab" role="tabpanel">
                                    <div class="mb-3">
                                        <input type="file" class="form-control" id="hotel_image" name="hotel_image"
                                            accept="image/*">
                                        <div class="mt-2">
                                            <img id="preview-image" class="img-thumbnail d-none"
                                                style="max-height: 200px;" alt="Preview">
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="url-tab" role="tabpanel">
                                    <input type="text" class="form-control" id="img_url" name="img_url"
                                        placeholder="Nhập URL hình ảnh">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" name="edit_hotel" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal Tất Cả Phòng -->
    <div class="modal fade" id="allRoomsModal" tabindex="-1" aria-labelledby="allRoomsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="allRoomsModalLabel">Danh Sách Tất Cả Phòng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="sort-header" data-sort="hotel_name">Khách Sạn</th>
                                    <th class="sort-header" data-sort="room_type">Loại Phòng</th>
                                    <th class="sort-header" data-sort="price">Giá/Đêm</th>
                                    <th class="sort-header" data-sort="guests">Số Khách</th>
                                </tr>
                            </thead>
                            <tbody id="allRoomsTableBody" class="table-responsive-stack">
                                <?php foreach ($allRooms as $room): ?>
                                    <tr>
                                        <td data-label="Khách Sạn"><?= htmlspecialchars($room['hotel_name']) ?></td>
                                        <td data-label="Loại Phòng"><?= htmlspecialchars($room['room_type']) ?></td>
                                        <td data-label="Giá/Đêm"><?= number_format($room['price_per_night'], 0, ',', '.') ?>
                                            đ</td>
                                        <td data-label="Số Khách"><?= $room['max_guests'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($allRooms)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Không có phòng nào trong hệ thống
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Quản Lý Phòng của Khách Sạn -->
    <div class="modal fade" id="manageRoomsModal" tabindex="-1" aria-labelledby="manageRoomsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageRoomsModalLabel">Quản Lý Phòng - <span id="hotelNameTitle"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-end mb-3">
                        <button id="addRoomBtn" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Phòng
                            Mới</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Loại Phòng</th>
                                    <th>Giá/Đêm</th>
                                    <th>Số Khách</th>
                                    <th>Hình Ảnh</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody id="roomsTableBody" class="table-responsive-stack">
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Đang tải...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Phòng -->
    <div class="modal fade" id="roomFormModal" tabindex="-1" aria-labelledby="roomFormModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomFormModalLabel">Thêm Phòng Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="roomForm" method="post" action="hotel_manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="save_room" value="1">
                        <input type="hidden" id="roomHotelId" name="hotel_id" value="">
                        <input type="hidden" id="roomId" name="room_id" value="">

                        <div class="mb-3">
                            <label for="room_type" class="form-label">Loại Phòng</label>
                            <input type="text" class="form-control" id="room_type" name="room_type" required>
                        </div>

                        <div class="mb-3">
                            <label for="price_per_night" class="form-label">Giá/Đêm</label>
                            <input type="number" class="form-control" id="price_per_night" name="price_per_night"
                                required min="0">
                        </div>

                        <div class="mb-3">
                            <label for="max_guests" class="form-label">Số Khách Tối Đa</label>
                            <input type="number" class="form-control" id="max_guests" name="max_guests" required min="1"
                                max="10">
                        </div>

                        <div class="mb-3">
                            <label for="room_image" class="form-label">Hình Ảnh</label>
                            <input type="file" class="form-control" id="room_image" name="room_image" accept="image/*">
                            <div class="mt-2">
                                <img id="room-preview-image" class="img-thumbnail d-none" style="max-height: 200px;"
                                    alt="Preview">
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-primary">Lưu Phòng</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác Nhận Xóa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn khách sạn này không?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn delete-hotel-btn" id="confirmDeleteBtn">Xóa</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js.js"> </script>


</body>

</html>