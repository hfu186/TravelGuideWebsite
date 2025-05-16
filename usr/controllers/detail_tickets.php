<?php
session_start();
include('../../server/connectdb.php');

header('Content-Type: text/html; charset=UTF-8');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo ("Không tìm thấy vé");
    header('location:tickets.php');
    exit();
}


$ticket_id = intval($_GET['id']);
$review_stmt = $conn->prepare("
SELECT 
    r.review_id,
    u.usr_name,
    r.rating,
    r.review_text,
    r.review_date
FROM reviews r
JOIN users u ON r.usr_id = u.usr_id
WHERE r.booking_type = 'Ticket' 
AND r.booking_item_id = :ticket_id
ORDER BY r.review_date DESC
");
$review_stmt->bindParam(':ticket_id', $ticket_id);
$review_stmt->execute();
$ticket_reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
$rating_stmt = $conn->prepare("
SELECT 
    AVG(rating) as avg_rating, 
    COUNT(*) as total_reviews 
FROM reviews 
WHERE booking_type = 'Ticket' 
AND booking_item_id = :ticket_id
");
$rating_stmt->bindParam(':ticket_id', $ticket_id);
$rating_stmt->execute();
$rating_info = $rating_stmt->fetch(PDO::FETCH_ASSOC);

try {

    $stmt = $conn->prepare("SELECT * FROM Tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("Vé không tồn tại.");
    }
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}


?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($ticket['ticketname']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .breadcrumb {
            background-color: transparent;
            padding: 0.75rem 0;
        }
    </style>

</head>

<body>
    <?php include('../layout/header.php'); ?>
    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="tickets.php">Vé du lịch</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($ticket['ticketname']); ?>
                </li>
            </ol>
        </nav>
        <div class="ticket-detail-container">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="ticket-image-gallery">
                        <div class="main-image mb-3">
                            <img src="<?php echo htmlspecialchars($ticket['img_url']); ?>"
                                class="img-fluid w-100 rounded" id="mainImage"
                                alt="<?php echo htmlspecialchars($ticket['ticketname']); ?>">
                        </div>

                        <div class="gallery-thumbnails row g-2">
                            <?php
                            $gallery_images = array_filter([
                                $ticket['img_detail1'] ?? null,
                                $ticket['img_detail2'] ?? null,
                                $ticket['img_detail3'] ?? null
                            ]);

                            foreach ($gallery_images as $index => $img):
                                ?>
                                <div class="col-3">
                                    <img src="<?php echo htmlspecialchars($img); ?>"
                                        class="gallery-img img-fluid img-thumbnail"
                                        onclick="document.getElementById('mainImage').src=this.src"
                                        alt="Gallery image <?php echo $index + 1; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="ticket-info">
                        <h2 class="ticket-title mb-3">
                            <?php echo htmlspecialchars($ticket['ticketname']); ?>
                        </h2>
                        <?php if ($rating_info['avg_rating']): ?>
                        <div class="average-rating mb-3">
                            <p>
                                <?php
                                $full_stars = floor($rating_info['avg_rating']);
                                for ($i = 1; $i <= $full_stars; $i++) {
                                    echo '<span class="text-warning"><i class="fa-solid fa-star"></i></span>';
                                }
                                for ($i = $full_stars + 1; $i <= 5; $i++) {
                                    echo '<span class="text-muted"><i class="fa-solid fa-star"></i></span>';
                                }
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                        <div class="ticket-price mb-4">
                            <h4 class="text-warning">
                                Giá từ: <?php echo number_format($ticket['price'], 0, ',', '.'); ?> đ/người
                            </h4>
                        </div>

                        <form action="book_ticket.php" method="POST" class="booking-form needs-validation" novalidate>
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Chọn ngày đi</label>
                                    <input type="date" class="form-control" name="booking_date"
                                        min="<?php echo date('Y-m-d'); ?>" required>
                                    <div class="invalid-feedback">
                                        Vui lòng chọn ngày khởi hành
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Số người</label>
                                    <input type="number" class="form-control" name="quantity" min="1" value="1"
                                        required>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-warning btn-lg w-100">
                                    ĐẶT VÉ NGAY
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="container">
        <ul class="nav nav-tabs" id="tourTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#nav-info">Thông tin chung</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#nav-itinerary">Lịch trình</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#nav-policy">Chính sách</a>
            </li>
        </ul>
    </div>
    <div class="tab-content mt-3" id="nav-tabContent">
        <div class="tab-pane fade show active" id="nav-info">
            <h4 class="mb-3 text-primary"><i class="fas fa-info-circle me-2"></i>Thông tin chung</h4>
            <div class="p-3 border-0 shadow-sm w-100">
                <div>
                    <?php echo nl2br(htmlspecialchars($ticket['ticket_describe']));  ?>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="nav-itinerary">
            <h4 class="mb-3 text-info"><i class="fas fa-route me-2"></i>Đề xuất lịch trình</h4>
            <?php
            $itinerary = nl2br(htmlspecialchars($ticket['itinerary']));
            ?>

            <p><?php echo $itinerary; ?></p>

        </div>
        <div class="tab-pane fade" id="nav-policy">
            <h4 class="mb-3 text-warning">
                <i class="fas fa-exclamation-circle me-2"></i> Chính sách
            </h4>
            <div class="row g-3 d-flex justify-content-center">
                <div class="col-md-6">
                    <div class="border-0 shadow-sm policy-card w-75">
                        <div class="bg-danger text-white rounded-top">
                            <h5 class="mb-0 text-center">Hủy Tour</h5>
                        </div>
                        <div>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-ban text-danger me-2"></i>Hủy trước 7 ngày: Hoàn 100%</li>
                                <li><i class="fas fa-ban text-danger me-2"></i>Hủy trước 3 ngày: Hoàn 50%</li>
                                <li><i class="fas fa-ban text-danger me-2"></i>Hủy sau 3 ngày: Không hoàn tiền
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="border-0 shadow-sm policy-card w-75">
                        <div class="bg-success text-white rounded-top">
                            <h5 class="mb-0 text-center">Bảo đảm</h5>
                        </div>
                        <div>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success me-2"></i>Đảm bảo khởi hành</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Giá tốt nhất</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Hỗ trợ 24/7</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="review-section mt-4">
    <div class="row">
        <div class="col-12">
            <div class="reviews-section bg-light p-3 rounded">
                <h3 class="mb-3">Đánh Giá (<?php echo $rating_info['total_reviews'] ?? 0; ?> đánh giá)</h3>
                <?php if (!empty($ticket_reviews)): ?>
                    <div class="reviews-list">
                        <?php foreach ($ticket_reviews as $review): ?>
                            <div class="review-item border-bottom w-25">
                                <div class="review-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="me-2"><?php echo htmlspecialchars($review['usr_name']); ?></strong>
                                        <div class="review-rating d-inline-block">
                                            <?php
                                            for ($i = 1; $i <= $review['rating']; $i++) {
                                                echo '<span class="text-warning"><i class="fa-solid fa-star" style="color: #FFD43B;"></i></span>';
                                            }
                                            for ($i = $review['rating'] + 1; $i <= 5; $i++) {
                                                echo '<span class="text-muted"><i class="fa-solid fa-star" style="color: #FFD43B;"></i></span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <span class="text-muted small">
                                        <?php echo date('d/m/Y', strtotime($review['review_date'])); ?>
                                    </span>
                                </div>
                                <p class="mt-2 text-muted">
                                    <?php echo htmlspecialchars($review['review_text']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p class="mb-0">Chưa có đánh giá nào cho khách sạn này.</p>
                    </div>  
                <?php endif; ?>

                <?php if ($rating_info['total_reviews'] > 5): ?>
                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-outline-primary">Xem tất cả đánh giá</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
    <?php include('../layout/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>