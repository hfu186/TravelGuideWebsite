<?php
session_start();
require('../../server/connectdb.php');
header('Content-Type: text/html; charset=UTF-8');

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error'] = "Tour không hợp lệ";
    header('Location: tour.php');
    exit();
}

$tour_id = (int) $_GET['id'];
$stmt = $conn->prepare("
    SELECT 
    *, 
    MIN(td.departure_date) AS earliest_date,
MAX(td.departure_date) AS latest_date,
    GROUP_CONCAT(td.tour_date_id) AS date_ids
    FROM Tours t
    LEFT JOIN tour_dates td ON t.tour_id = td.tour_id
    WHERE t.tour_id = ?
    GROUP BY t.tour_id;
");
$stmt->execute([$tour_id]);
$tour = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tour) {
    throw new Exception("Tour không tồn tại");
}

$dates = [];
if (!empty($tour['date_ids'])) {
    $date_stmt = $conn->prepare("
        SELECT * FROM tour_dates 
        WHERE tour_date_id IN ({$tour['date_ids']})
        ORDER BY departure_date
    ");
    $date_stmt->execute();
    $dates = $date_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$review_stmt = $conn->prepare("
SELECT 
    r.review_id,
    u.usr_name,
    r.rating,
    r.review_text,
    r.review_date
FROM reviews r
JOIN users u ON r.usr_id = u.usr_id
WHERE r.booking_type = 'Tour' 
AND r.booking_item_id = :tour_id
ORDER BY r.review_date DESC
");
$review_stmt->bindParam(':tour_id', $tour_id );
$review_stmt->execute();
$tour_reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
$rating_stmt = $conn->prepare("
SELECT 
    AVG(rating) as avg_rating, 
    COUNT(*) as total_reviews 
FROM reviews 
WHERE booking_type = 'Tour' 
AND booking_item_id = :tour_id
");
$rating_stmt->bindParam(':tour_id', $tour_id );
$rating_stmt->execute();
$rating_info = $rating_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlentities($tour['tour_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include('../layout/header.php'); ?>
    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="tour.php">Tour</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($tour['tour_name']); ?>
                </li>
            </ol>
        </nav>
        <div class="row">
            <div class="col-md-6">
                <img src="<?= htmlentities($tour['image_url']) ?>" class="img-fluid w-100"
                    alt="<?= htmlentities($tour['tour_name']) ?>">
            </div>

            <div class="col-md-6">
                <h2 class="fw-bold"><?= htmlentities($tour['tour_name']) ?></h2>

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
                <h3 class="text-danger mb-4">
                    <?= number_format($tour['price'], 0, ',', '.') ?>₫
                    <small class="text-muted fs-6">/người</small>
                </h3>

                <form action="book_tour.php" method="POST">
                    <input type="hidden" name="tour_id" value="<?= $tour_id ?>">

                    <div class="mb-4">
                        <label class="form-label fw-bold">Chọn ngày khởi hành</label>
                        <select class="form-select" name="selected_date" id="tourDate" required>
                            <?php if (empty($dates)): ?>
                                <option value="">Hiện không có lịch trình</option>
                            <?php else: ?>
                                <option value="">-- Chọn ngày --</option>
                                <?php foreach ($dates as $date): ?>
                                    <option value="<?= $date['departure_date'] ?>"
                                        data-tour-date-id="<?= $date['tour_date_id'] ?>"
                                        data-slots="<?= $date['available_slots'] ?>" data-price="<?= $tour['price'] ?>">
                                        <?= date('d/m/Y', strtotime($date['departure_date'])) ?>
                                        - Còn <?= $date['available_slots'] ?> chỗ
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Số người</label>
                        <input type="number" class="form-control" name="num_people" id="numPeople" value="1" min="1"
                            required>
                        <div id="slotInfo" class="text-muted mt-2"></div>
                    </div>

                    <!-- Thêm nút submit -->
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="fas fa-shopping-cart me-2"></i>
                        ĐẶT TOUR NGAY
                    </button>
                </form>

            </div>
        </div>
        </div>

        <div class="container mt-4">
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
            <h4 class="mb-3 text-primary"><i class="fas fa-info-circle me-2"></i>Thông tin cơ bản</h4>
            <div class="p-3 border-0 shadow-sm w-100">
                <div>
                    <p><?php echo nl2br(htmlspecialchars($tour['content'])); ?></p>
                </div>
            </div>
        </div>
 
    <div class="tab-pane fade" id="nav-itinerary">
        <h4 class="mb-3 text-info"><i class="fas fa-route me-2"></i>Lộ trình chi tiết</h4>
        <?php
        $lines = explode("\n", $tour['description']);
        ?>
        <div class="list-group">
            <?php
            foreach ($lines as $line):
                $line = trim($line);
                if (strpos($line, "Ngày") === 0):
                    if (!empty($currentDayDiv))
                        echo "</div>";
                    echo "<div class='list-group-item'>";
                    echo "<h5 class='text-primary'>$line</h5>";
                    $currentDayDiv = true;
                else:
                    echo "<p>$line</p>";
                endif;
            endforeach;
            if (!empty($currentDayDiv))
                echo "</div>";
            ?>
        </div>
    </div>
    <div class="tab-pane fade" id="nav-policy">
        <h4 class="mb-3 text-warning">
            <i class="fas fa-exclamation-circle me-2"></i> Chính sách
        </h4>
        <div class="row g-3 d-flex justify-content-center">
            <!-- Hủy Tour -->
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

            <!-- Bảo đảm -->
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

    <div class="row">
        <div class="col-12">
            <div class="reviews-section bg-light p-3 rounded">
                <h3 class="mb-3">Đánh Giá (<?php echo $rating_info['total_reviews'] ?? 0; ?> đánh giá)</h3>
                <?php if (!empty($tour_reviews)): ?>
                    <div class="reviews-list">
                        <?php foreach ($tour_reviews as $review): ?>
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
                                                echo '<span class="text-muted"><i class="fa-solid fa-star";"></i></span>';
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
    <?php include('../layout/footer.php') ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('tourDate').addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const maxSlots = parseInt(selectedOption.dataset.slots) || 0;
            const numPeopleInput = document.getElementById('numPeople');
            const slotInfo = document.getElementById('slotInfo');
            numPeopleInput.max = maxSlots;
            numPeopleInput.min = 1;
            if (parseInt(numPeopleInput.value) > maxSlots) {
                numPeopleInput.value = maxSlots;
            }
            slotInfo.innerHTML = maxSlots > 0
                ? `Còn ${maxSlots} chỗ trống`
                : `<span class="text-danger">Hết chỗ</span>`;
        });

        document.querySelector('form').addEventListener('submit', function (e) {
            const tourDate = document.getElementById('tourDate');
            const numPeople = document.getElementById('numPeople');


            if (!tourDate.value) {
                e.preventDefault();
                alert('Vui lòng chọn ngày khởi hành');
                return;
            }


            const selectedOption = tourDate.options[tourDate.selectedIndex];
            const maxSlots = parseInt(selectedOption.dataset.slots);
            const peopleCount = parseInt(numPeople.value);


            if (peopleCount > maxSlots) {
                e.preventDefault();
                alert(`Chỉ còn ${maxSlots} chỗ trống`);
                return;
            }
        });


    </script>
</body>

</html>