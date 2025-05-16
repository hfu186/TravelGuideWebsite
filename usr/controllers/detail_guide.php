<?php
session_start();
require('../../server/connectdb.php');

header('Content-Type: text/html; charset=UTF-8');
function renderStars($rating)
{
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;

    $starHtml = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $starHtml .= '<span class="text-warning">★</span>';
    }

    if ($halfStar) {
        $starHtml .= '<span class="text-warning">½</span>';
    }

    for ($i = 0; $i < $emptyStars; $i++) {
        $starHtml .= '<span class="text-muted">★</span>';
    }

    return $starHtml;
}
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Không tìm thấy thông tin hướng dẫn viên";
    header('location: guider.php');
    exit();
}

$guide_id = intval($_GET['id']);
try {
    $stmt = $conn->prepare("
        SELECT r.*, u.usr_name 
        FROM reviews r
        JOIN users u ON r.usr_id = u.usr_id
        WHERE r.booking_type = 'Guide' 
        AND r.booking_item_id = ?
        ORDER BY r.review_date DESC
    ");
    $stmt->execute([$guide_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("
        SELECT 
            AVG(rating) as avg_rating, 
            COUNT(*) as total_reviews 
        FROM reviews 
        WHERE booking_type = 'Guide' 
        AND booking_item_id = ?
    ");
    $stmt->execute([$guide_id]);
    $review_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reviews = [];
    $review_stats = ['avg_rating' => 0, 'total_reviews' => 0];
}


try {
    $stmt = $conn->prepare("SELECT * FROM Tour_Guides WHERE guide_id = ?");
    $stmt->execute([$guide_id]);
    $guide = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$guide) {
        die("Hướng dẫn viên không tồn tại.");
    }

} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}


$unavailable_dates = [];
try {
    $stmt = $conn->prepare("SELECT booking_date, days FROM guide_bookings WHERE guide_id = ? AND status != 'cancelled'");
    $stmt->execute([$guide_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $start_date = strtotime($row['booking_date']);
        $end_date = strtotime("+" . ($row['days'] - 1) . " days", $start_date);
        for ($date = $start_date; $date <= $end_date; $date = strtotime("+1 day", $date)) {
            $unavailable_dates[] = date('Y-m-d', $date);
        }
    }
} catch (PDOException $e) {
    die("Lỗi khi tải dữ liệu đặt chỗ: " . $e->getMessage());
}
$unavailable_json = json_encode($unavailable_dates);
$rating_info = [
    'avg_rating' => $review_stats['avg_rating'] ?? 0,
    'total_reviews' => $review_stats['total_reviews'] ?? 0
];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Hướng dẫn viên - <?php echo htmlentities($guide['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script>
        let unavailableDates = <?php echo $unavailable_json; ?>;
        function validateBooking() {
            let selectedDate = document.getElementById('booking_date').value;
            if (unavailableDates.includes(selectedDate)) {
                alert('Hướng dẫn viên không có sẵn vào ngày này. Vui lòng chọn ngày khác.');
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <?php include('../layout/header.php'); ?>
    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="guider.php">Hướng dẫn viên</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($guide['name']); ?>
                </li>
            </ol>
        </nav>
        <div class="row">
            <div class="col-md-4">
                <img src="<?php echo htmlentities($guide['img_url']); ?>" class="img-fluid"
                    alt="<?php echo htmlentities($guide['name']); ?>">
            </div>
            <div class="col-md-8">
                <h2><?php echo htmlentities($guide['name']); ?></h2>
                <p><strong>Email:</strong> <?php echo htmlentities($guide['email']); ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo htmlentities($guide['phone']); ?></p>
                <p><strong>Ngôn ngữ:</strong> <?php echo htmlentities($guide['language']); ?></p>
                <p><strong>Kinh nghiệm:</strong> <?php echo htmlentities($guide['experience']); ?> năm</p>
                <p><strong>Giá thuê:</strong> <?= number_format($guide['price'], 0, ',', '.') ?> đ/ngày</p>
                <form action="book_guide.php" method="POST" onsubmit="return validateBooking()">
                    <input type="hidden" name="guide_id" value="<?php echo $guide_id; ?>">
                    <div class="mb-3">
                        <label class="form-label">Chọn ngày đi</label>
                        <input type="date" id="booking_date" class="form-control" name="booking_date"
                            min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số ngày</label>
                        <input type="number" class="form-control" name="quantity" min="1" value="1" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">ĐẶT HƯỚNG DẪN VIÊN</button>
                </form>
            </div>
        </div>
    </div>
    <div class="review-section">
        <h3 class="mb-5">Đánh Giá (<?= $rating_info['total_reviews'] ?>)</h3>
        <?php if ($rating_info['total_reviews'] > 0): ?>
            <div class="row">
                <div class="col-md-4 text-center">
                    <h2 class="display-4"><?= number_format($rating_info['avg_rating'], 1) ?>/5</h2>
                    <div class="rating-stars mb-2">
                        <?= renderStars($rating_info['avg_rating']) ?>
                    </div>
                    <p>Tổng số <?= $rating_info['total_reviews'] ?> đánh giá</p>
                </div>
                <div class="col-md-8">
                    <?php
                    $starDistribution = [
                        5 => 0,
                        4 => 0,
                        3 => 0,
                        2 => 0,
                        1 => 0  
                    ];
                    foreach (array_reverse([5, 4, 3, 2, 1]) as $star):
                        ?>
                        <div class="row align-items-center mb-1">
                            <div class="col-2"><?= $star ?> <span class="text-warning">★</span></div>
                            <div class="col-8">
                                <div class="progress">
                                    <div class="progress-bar bg-warning" style="width: <?= $starDistribution[$star] ?>%">
                                    </div>
                                </div>
                            </div>
                            <div class="col-2 small text-muted">
                                <?= $starDistribution[$star] ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <hr class="w-100">
        <?php else: ?>
            <div class="alert alert-info">Chưa có đánh giá nào</div>
        <?php endif; ?>
        <div class="reviews-list">
            <?php foreach ($reviews as $review): ?>
                <div class="review-item border-bottom w-25">
                    <div class="review-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="me-2"><?php echo htmlspecialchars($review['usr_name']); ?></strong>
                            <div class="review-rating d-inline-block">
                                <?php
                                for ($i = 1; $i <= $review['rating']; $i++) {
                                    echo '<span class="text-warning"><i class="fa-solid fa-star"></i></span>';
                                }
                                for ($i = $review['rating'] + 1; $i <= 5; $i++) {
                                    echo '<span class="text-muted"><i class="fa-solid fa-star"></i></span>';
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
    </div>
    <?php include('../layout/footer.php'); ?>
</body>

</html>