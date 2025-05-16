<?php
session_start();
include('../server/connectdb.php');
if (!isset($_SESSION['admin_login'])) {
    echo ("Vui lòng đăng nhập");
    header('location: login.php');
    exit();
}
else{
$items_per_page = 5;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $items_per_page;
$query = "SELECT *
          FROM tickets
          LIMIT :offset, :limit";
$stmt = $conn->prepare($query);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_query = "SELECT COUNT(*) as total FROM tickets";
$total_stmt = $conn->prepare($total_query);
$total_stmt->execute();
$total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $items_per_page);
$current_page = min($current_page, $total_pages);
// thêm vé
if (isset($_GET['delete_ticket'])) {
    $ticket_id = $_GET['delete_ticket'];

    $query = "DELETE FROM tickets WHERE ticket_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$ticket_id]);

    header("Location: ticket_manage.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_ticket'])) {
    $ticketname = $_POST['ticketname'];
    $price = $_POST['price'];
    $location = $_POST['location'];
    $content = $_POST['content'] ?? '';
    $description = $_POST['description'];

    $rating = $_POST['rating'] ?? 0;
    $img_url = '';
    $itinerary = $_POST['itinerary'];

    if (isset($_FILES['add_ticket_image']) && $_FILES['add_ticket_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../img/";
        $file_name = basename($_FILES['add_ticket_image']['name']); 
        $target_file = $target_dir . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['add_ticket_image']['type'], $allowed_types)) {
            if (!move_uploaded_file($_FILES['add_ticket_image']['tmp_name'], $target_file)) {
                $error = "Error: Failed to move uploaded file.";
            } else {
                $img_url = $target_file;
            }
        } else {
            $error = "Error: Invalid file type.";
        }
    }

    if (!empty($ticketname) && !empty($price) && !empty($location) && !empty($description)) {
        $query = "INSERT INTO Tickets (ticketname, price, location, content, ticket_describe, img_url, rating,itinerary) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$ticketname, $price, $location, $content, $description, $img_url, $rating, $itinerary]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Thêm vé mới thành công!'); window.location.href='ticket_manage.php';</script>";
        } else {
            echo "<script>alert('Thêm vé thất bại!');</script>";
        }
    } else {
        echo "<script>alert('Vui lòng điền đầy đủ thông tin bắt buộc!');</script>";
    }
}

if (isset($_GET['ticket_id']) && !isset($_POST['edit_ticket'])) {
    $ticket_id = $_GET['ticket_id'];
    $stmt = $conn->prepare("SELECT * FROM Tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ticket) {
        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            header('Content-Type: application/json');
            echo json_encode($ticket);
            exit;
        }
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Không tìm thấy vé!']);
            exit;
        } else {
            echo "<script>alert('Không tìm thấy vé!');</script>";
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $ticketname = $_POST['ticketname'];
    $price = $_POST['price'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $itinerary = $_POST['itinerary'];
    $img_url = $_POST['img_url'] ?? null;
    $rating = $_POST['rating'] ?? null;

    if (isset($_FILES['ticket_image']) && $_FILES['ticket_image']['error'] == 0) {
        $file_tmp = $_FILES['ticket_image']['tmp_name'];
        $file_name = basename($_FILES['ticket_image']['name']);
        $physical_img_path = dirname(__DIR__) . 'admin/img/' . $file_name;
        $img_url = '../img/' . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['ticket_image']['type'], $allowed_types)) {

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

    if (!empty($ticket_id) && !empty($ticketname) && !empty($location) && !empty($description)) {
        $query = "UPDATE Tickets SET ticketname = ?, price = ?, location = ?, ticket_describe = ?,itinerary=?, img_url = ?, rating=?  WHERE ticket_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$ticketname, $price, $location, $description,$itinerary, $img_url, $rating, $ticket_id]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Vé đã được cập nhật thành công!'); window.location.href='ticket_manage.php';</script>";
        } else {
            echo "<script>alert('Không có thay đổi hoặc cập nhật thất bại!');</script>";
        }
    } else {
        echo "<script>alert('Vui lòng điền đầy đủ thông tin!');</script>";
    }
}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management - Travel Admin</title>
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
                <a href="ticket_manage.php" class="menu-item active "><i class="fas fa-ticket-alt"></i><span>Quản lý
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
                <div class="page-title">Quản lý vé</div>
                <div class="user-profile">
                    <h4>Admin</h4>
                    <img src="assets/img/admin.jpg" alt="User Profile">
                </div>
            </div>


            <!-- Tickets Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Tất cả vé</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTicketModal"><i
                            class="fas fa-plus"></i>Thêm vé</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ảnh</th>
                                    <th>Vé & Trải nghiệm</th>
                                    <th>Địa điểm</th>
                                    <th>Mô tả</th>
                                    <th>Lịch trình</th>
                                    <th>Giá</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result as $row): ?>
                                    <tr>
                                        <td><?= $row['ticket_id'] ?></td>
                                        <td><img src="<?= str_replace("../img/", "../usr/img/", $row['img_url']) ?>"
                                                class="ticket-image"></td>
                                        <td><?= $row['ticketname'] ?></td>
                                        <td><?= $row['location'] ?></td>
                                        <td><?= mb_substr($row['ticket_describe'], 0, 25, "UTF-8") . '...' ?></td>
                                        <td><?= mb_substr($row['itinerary'], 0, 25, "UTF-8") . '...' ?></td>
                                        <td><?= number_format($row['price'], 0, ',', '.') ?> VND/Ng</td>

                                        <td>

                                            <button class="btn btn-sm btn-primary action-btn edit-btn"
                                                data-bs-toggle="modal" data-bs-target="#editTicketModal"
                                                data-ticketid="<?= $row['ticket_id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="confirmDelete(<?= $row['ticket_id'] ?>)"
                                                class="btn btn-sm btn-danger action-btn">
                                                <i class="fas fa-trash"></i>
                                            </button>



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


    <!-- Add Ticket Modal -->
    <div class="modal fade" id="addTicketModal" tabindex="-1" aria-labelledby="addTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTicketModalLabel">Thêm vé mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addTicketForm" method="POST" action="ticket_manage.php" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_ticketname" class="form-label">Tên vé <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_ticketname" name="ticketname" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_price" class="form-label">Giá (VND) <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="add_price" name="price" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_location" class="form-label">Địa điểm <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_location" name="location" required>
                            </div>

                        </div>

                        <div class="mb-3">
                            <label for="add_content" class="form-label">Nội dung ngắn</label>
                            <input type="text" class="form-control" id="add_content" name="content"
                                placeholder="Mô tả ngắn gọn về vé (tối đa 255 ký tự)" maxlength="255">
                        </div>

                        <div class="mb-3">
                            <label for="add_description" class="form-label">Mô tả chi tiết <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="add_description" name="description" rows="4"
                                required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="add_description" class="form-label">Gợi ý lịch trình<span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="add_itinerary" name="itinerary" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_rating" class="form-label">Đánh giá ban đầu (0-5)</label>
                                <input type="number" class="form-control" id="add_rating" name="rating" min="0" max="5"
                                    step="0.1" value="0.0">
                            </div>
                            
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ảnh <span class="text-danger">*</span></label>
                            <div class="tab-container">
                                <div class="tab-buttons">
                                    <div class="tab-btn active" data-tab="add-upload-tab">Upload ảnh</div>
                                    <div class="tab-btn" data-tab="add-url-tab">URL ảnh</div>
                                </div>
                                <div id="add-upload-tab" class="tab-content active">
                                    <div class="image-upload-wrapper">
                                        <button type="button" class="image-upload-btn" id="add-image-upload-btn">
                                            <i class="fas fa-upload"></i> Chọn từ máy
                                        </button>
                                        <input type="file" id="add_ticket_image" name="add_ticket_image"
                                            accept="image/*">
                                    </div>
                                    <div class="image-preview-container">
                                        <p id="add-file-name">Không có file nào được chọn</p>
                                        <img id="add-preview-image" class="preview-image" src="#" alt="Preview">
                                    </div>
                                </div>
                                <div id="add-url-tab" class="tab-content">
                                    <input type="text" class="form-control" id="add_img_url" name="img_url"
                                        placeholder="Nhập URL ảnh">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" name="add_ticket" class="btn btn-success">Thêm vé</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Ticket Modal -->
    <div class="modal fade" id="editTicketModal" tabindex="-1" aria-labelledby="editTicketModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTicketModalLabel">Chỉnh sửa vé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editTicketForm" method="POST" action="ticket_manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="ticket_id" value="">
                        <div class="mb-3">
                            <label for="ticketname" class="form-label">Tên vé</label>
                            <input type="text" class="form-control" id="ticketname" name="ticketname" required>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Giá</label>
                            <input type="number" class="form-control" id="price" name="price" required>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Địa điểm</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="description" name="description" rows="4"
                                required></textarea>
                        </div>
                        <div class="mb-3"> 
                                <label for="add_rating" class="form-label">Đánh giá ban đầu (0-5)</label>
                                <input type="number" class="form-control" id="rating" name="rating" min="0" max="5"
                                    step="0.1" value="0.0">
                        </div>

                        <div class="mb-3">
                            <label for="add_description" class="form-label">Gợi ý lịch trình<span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="itinerary" name="itinerary" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ảnh</label>
                            <div class="tab-container">
                                <div class="tab-buttons">
                                    <div class="tab-btn active" data-tab="upload-tab">Upload ảnh</div>
                                    <div class="tab-btn" data-tab="url-tab">URL ảnh</div>
                                </div>
                                <div id="upload-tab" class="tab-content active">
                                    <div class="image-upload-wrapper">
                                        <button type="button" class="image-upload-btn">
                                            <i class="fas fa-upload"></i> Chọn từ máy
                                        </button>
                                        <input type="file" id="ticket_image" name="ticket_image" accept="image/*">
                                    </div>
                                    <div class="image-preview-container">
                                        <p id="file-name">Không có file nào được chọn</p>
                                        <img id="preview-image" class="preview-image" src="#" alt="Preview">
                                    </div>
                                </div>
                                <div id="url-tab" class="tab-content">
                                    <input type="text" class="form-control" id="img_url" name="img_url"
                                        placeholder="Nhập URL ảnh">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" name="edit_ticket" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const ticketId = this.getAttribute('data-ticketid');
                    console.log('Fetching ticket data for ID:', ticketId);
                    fetch(`ticket_manage.php?ticket_id=${ticketId}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(ticket => {
                            console.log('Received ticket data:', ticket);
                            document.querySelector('#editTicketForm input[name="ticket_id"]').value = ticket.ticket_id;
                            document.querySelector('#editTicketForm input[name="ticketname"]').value = ticket.ticketname;
                            document.querySelector('#editTicketForm input[name="price"]').value = ticket.price;
                            document.querySelector('#editTicketForm input[name="location"]').value = ticket.location;
                            document.querySelector('#editTicketForm textarea[name="description"]').value = ticket.ticket_describe;
                            document.querySelector('#editTicketForm textarea[name="itinerary"]').value = ticket.itinerary;
                            document.querySelector('#editTicketForm input[name="rating"]').value = ticket.rating;
                            document.querySelector('#editTicketForm input[name="img_url"]').value = ticket.img_url;

                            const previewImage = document.getElementById('preview-image');
                            if (previewImage && ticket.img_url) {
                                previewImage.src = ticket.img_url;
                                previewImage.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching ticket data:', error);
                            alert('Đã xảy ra lỗi khi tải thông tin vé');
                        });
                });
            });
            document.getElementById('add-image-upload-btn').addEventListener('click', function () {
                document.getElementById('add_ticket_image').click();
            });

            document.getElementById('add_ticket_image').addEventListener('change', function (e) {
                const fileName = e.target.files[0] ? e.target.files[0].name : 'Không có file nào được chọn';
                document.getElementById('add-file-name').textContent = fileName;

                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const previewImage = document.getElementById('add-preview-image');
                        previewImage.src = e.target.result;
                        previewImage.style.display = 'block';
                    }
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            document.getElementById('add_img_url').addEventListener('input', function () {
                const imgUrl = this.value.trim();
                if (imgUrl) {
                    const tempImg = new Image();
                    tempImg.onload = function () {
                        document.getElementById('add-preview-image').src = imgUrl;
                        document.getElementById('add-preview-image').style.display = 'block';
                    };
                    tempImg.onerror = function () {
                        document.getElementById('add-preview-image').style.display = 'none';
                    };
                    tempImg.src = imgUrl;
                } else {
                    document.getElementById('add-preview-image').style.display = 'none';
                }
            });
            document.querySelectorAll('.tab-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const tabId = this.getAttribute('data-tab');
                    document.querySelectorAll('.tab-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });

            document.querySelector('#editTicketModal .image-upload-btn').addEventListener('click', function () {
                document.getElementById('ticket_image').click();
            });


            document.getElementById('ticket_image').addEventListener('change', function (e) {
                const fileName = e.target.files[0] ? e.target.files[0].name : 'Không có file nào được chọn';
                document.getElementById('file-name').textContent = fileName;

                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const previewImage = document.getElementById('preview-image');
                        previewImage.src = e.target.result;
                        previewImage.style.display = 'block';
                    }
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            document.getElementById('img_url').addEventListener('input', function () {
                const imgUrl = this.value.trim();
                if (imgUrl) {

                    const tempImg = new Image();
                    tempImg.onload = function () {
                        document.getElementById('preview-image').src = imgUrl;
                        document.getElementById('preview-image').style.display = 'block';
                    };
                    tempImg.onerror = function () {

                        document.getElementById('preview-image').style.display = 'none';
                    };
                    tempImg.src = imgUrl;
                } else {
                    document.getElementById('preview-image').style.display = 'none';
                }
            });
        });

    </script>


    <script>
        function confirmDelete(ticketId) {
            if (confirm('Bạn có chắc chắn muốn xóa vé này không?')) {
                window.location.href = `ticket_manage.php?delete_ticket=${ticketId}`;
            }
        }
    </script>

</body>

</html>