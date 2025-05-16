<?php
session_start();
require('../../server/connectdb.php');
header('Content-Type: text/html; charset=UTF-8');

$hotel_id = intval($_GET['hotel_id']);
$default_amenities = [
    'general' => [
        ['name' => 'Wifi Miễn Phí', 'icon' => 'fa-solid fa-wifi', 'color' => 'text-primary'],
        ['name' => 'Điều Hòa', 'icon' => 'fa-solid fa-snowflake', 'color' => 'text-info'],
        ['name' => 'Lễ Tân 24/7', 'icon' => 'fa-solid fa-concierge-bell', 'color' => 'text-success'],
    ],
    'room' => [
        ['name' => 'Phòng Sạch Sẽ', 'icon' => 'fa-solid fa-broom', 'color' => 'text-secondary'],
        ['name' => 'Dịch Vụ Phòng', 'icon' => 'fa-solid fa-bell', 'color' => 'text-warning'],
        ['name' => 'Két An Toàn', 'icon' => 'fa-solid fa-lock', 'color' => 'text-dark'],
    ],
    'bathroom' => [
        ['name' => 'Phòng Tắm Riêng', 'icon' => 'fa-solid fa-shower', 'color' => 'text-primary'],
        ['name' => 'Đồ Vệ Sinh Miễn Phí', 'icon' => 'fa-solid fa-soap', 'color' => 'text-info'],
        ['name' => 'Máy Sấy Tóc', 'icon' => 'fa-solid fa-wind', 'color' => 'text-danger'],
    ],
    'food' => [
        ['name' => 'Bữa Sáng Miễn Phí', 'icon' => 'fa-solid fa-utensils', 'color' => 'text-success'],
        ['name' => 'Quầy Bar', 'icon' => 'fa-solid fa-martini-glass', 'color' => 'text-warning'],
        ['name' => 'Nhà Hàng', 'icon' => 'fa-solid fa-utensils', 'color' => 'text-danger'],
    ],
    'recreation' => [
        ['name' => 'Hồ Bơi', 'icon' => 'fa-solid fa-person-swimming', 'color' => 'text-info'],
        ['name' => 'Phòng Gym', 'icon' => 'fa-solid fa-dumbbell', 'color' => 'text-success'],
        ['name' => 'Spa', 'icon' => 'fa-solid fa-spa', 'color' => 'text-warning'],
    ],
    'parking' => [
        ['name' => 'Bãi Đỗ Xe', 'icon' => 'fa-solid fa-square-parking', 'color' => 'text-secondary'],
        ['name' => 'Xe Đưa Đón', 'icon' => 'fa-solid fa-van-shuttle', 'color' => 'text-primary'],
    ],
    'accessibility' => [
        ['name' => 'Thang Máy', 'icon' => 'fa-solid fa-elevator', 'color' => 'text-dark'],
        ['name' => 'Phù Hợp Người Khuyết Tật', 'icon' => 'fa-solid fa-wheelchair', 'color' => 'text-info'],
    ]
];

$review_stmt = $conn->prepare("
    SELECT 
        r.review_id,
        u.usr_name,
        r.rating,
        r.review_text,
        r.review_date
    FROM reviews r
    JOIN users u ON r.usr_id = u.usr_id
    WHERE r.booking_type = 'Hotel' 
    AND r.booking_item_id = :hotel_id
    ORDER BY r.review_date DESC
");
$review_stmt->bindParam(':hotel_id', $hotel_id);
$review_stmt->execute();
$hotel_reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
$rating_stmt = $conn->prepare("
    SELECT 
        AVG(rating) as avg_rating, 
        COUNT(*) as total_reviews 
    FROM reviews 
    WHERE booking_type = 'Hotel' 
    AND booking_item_id = :hotel_id
");
$rating_stmt->bindParam(':hotel_id', $hotel_id);
$rating_stmt->execute();
$rating_info = $rating_stmt->fetch(PDO::FETCH_ASSOC);
function checkRoomAvailability($conn, $room_id, $hotel_id, $check_in, $check_out)
{
    $stmt = $conn->prepare("
        SELECT 
            r.room_type, 
            r.price_per_night,
            r.stock,
            COALESCE((
                SELECT SUM(b.quantity) 
                FROM hotel_bookings b 
                WHERE b.room_id = :room_id
                  AND b.status = 'paid'
                  AND b.check_in < :check_out 
                  AND b.check_out > :check_in
            ), 0) AS booked_rooms
        FROM rooms r
        WHERE r.room_id = :room_id 
        AND r.hotel_id = :hotel_id
   
    ");
    $stmt->execute([
        ':room_id' => $room_id,
        ':hotel_id' => $hotel_id,
        ':check_in' => $check_in,
        ':check_out' => $check_out
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $result['available_rooms'] = $result['stock'];
        error_log("Kiểm tra phòng $room_id: Tổng={$result['stock']}, Đã đặt={$result['booked_rooms']}, Còn trống={$result['available_rooms']}");
    }
    return $result;
}
if (isset($_GET['action']) && $_GET['action'] == 'check_availability') {
    header('Content-Type: application/json');

    if (!isset($_GET['room_id']) || !isset($_GET['hotel_id']) || !isset($_GET['check_in']) || !isset($_GET['check_out'])) {
        echo json_encode(['error' => 'Thiếu thông tin cần thiết']);
        exit;
    }
    $hotel_id = intval($_GET['hotel_id']);
    $room_id = intval($_GET['room_id']);
    $check_in = $_GET['check_in'];
    $check_out = $_GET['check_out'];

    try {

        $date1 = new DateTime($check_in);
        $date2 = new DateTime($check_out);

        if ($date1 >= $date2) {
            echo json_encode([
                'available' => false,
                'message' => 'Ngày trả phòng phải sau ngày nhận phòng'
            ]);
            exit;
        }

        $result = checkRoomAvailability($conn, $room_id, $hotel_id, $check_in, $check_out);

        if (!$result || $result['available_rooms'] <= 0) {
            echo json_encode([
                'available' => false,
                'message' => 'Phòng đã hết chỗ trong khoảng thời gian này'
            ]);
        } else {
            echo json_encode([
                'available' => true,
                'message' => 'Còn ' . $result['available_rooms'] . ' phòng trống',
                'room_type' => $result['room_type'],
                'rooms_left' => $result['available_rooms']
            ]);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Lỗi: ' . $e->getMessage()]);
        exit;
    }
}
if (!isset($_GET['hotel_id']) || empty($_GET['hotel_id'])) {
    $_SESSION['error'] = "Không tìm thấy khách sạn.";
    header('location:hotel.php');
    exit();
}
$hotel_id = intval($_GET['hotel_id']);
$check_in = isset($_POST['check_in']) ? $_POST['check_in'] : null;
$check_out = isset($_POST['check_out']) ? $_POST['check_out'] : null;
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : null;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
try {
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE hotel_id = ? ");
    $stmt->execute([$hotel_id]);
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hotel) {
        $_SESSION['error'] = "Khách sạn không tồn tại hoặc đã bị xóa.";
        header('location:hotel.php');
        exit();
    }
    $stmt_rooms = $conn->prepare("
        SELECT room_id, room_type, price_per_night, max_guests, stock, img_url 
        FROM rooms 
        WHERE hotel_id = ?  AND stock > 0
    ");
    $stmt_rooms->execute([$hotel_id]);
    $rooms = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $check_in && $check_out && $room_id) {

        if (!isset($_SESSION['usr_id'])) {
            $_SESSION['error'] = "Vui lòng đăng nhập để đặt phòng";
            header('Location: ../login.php');
            exit();
        }
        $date1 = new DateTime($check_in);
        $date2 = new DateTime($check_out);

        if ($date1 >= $date2) {
            $_SESSION['error'] = "Ngày trả phòng phải sau ngày nhận phòng";
            header('Location: detail_hotels.php?hotel_id=' . $hotel_id);
            exit();
        }
        $result = checkRoomAvailability($conn, $room_id, $hotel_id, $check_in, $check_out);
        if (!$result || $result['available_rooms'] < $quantity) {
            $_SESSION['error'] = "Xin lỗi, không đủ phòng trống trong khoảng thời gian này";
            header('Location: detail_hotels.php?hotel_id=' . $hotel_id);
            exit();
        }
        $num_nights = $date2->diff($date1)->days;
        $total_price = $result['price_per_night'] * $num_nights * $quantity;
        $_SESSION['booking_info'] = [
            'check_in' => $check_in,
            'check_out' => $check_out,
            'num_nights' => $num_nights,
            'room_id' => $room_id,
            'room_type' => $result['room_type'],
            'hotel_id' => $hotel_id,
            'hotel_name' => $hotel['hotel_name'],
            'price_per_night' => $result['price_per_night'],
            'total_price' => $total_price,
            'quantity' => $quantity
        ];

        header('Location: payment.php');
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Đã xảy ra lỗi khi truy vấn dữ liệu: " . $e->getMessage();
    header('Location: hotel.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlentities($hotel['hotel_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<div>
    <?php include('../layout/header.php'); ?>

    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="hotel.php">Khách sạn</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($hotel['hotel_name']); ?>
                </li>
            </ol>
        </nav>
        <div class="row">
            <div class="col-md-6">
                <div id="hotelCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php
                        $images = array_filter([
                            $hotel['img_url'],
                            $hotel['img_detail1'],
                            $hotel['img_detail2'],
                            $hotel['img_detail3']
                        ], function ($img) {
                            return !empty($img);
                        });

                        foreach ($images as $index => $img) {
                            $activeClass = $index === 0 ? 'active' : '';
                            echo '<div class="carousel-item ' . $activeClass . '">
                                <img src="' . htmlentities($img) . '" 
                                     class="d-block w-100" 
                                     alt="' . htmlentities($hotel['hotel_name']) . '">
                            </div>';
                        }
                        ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <button class="carousel-control-prev btn-mov" type="button" data-bs-target="#hotelCarousel"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        </button>
                        <button class="carousel-control-next btn-mov" type="button" data-bs-target="#hotelCarousel"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <h2><b><?php echo htmlentities($hotel['hotel_name']); ?></b></h2>
                <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlentities($hotel['location']); ?></p>
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

                <p class="text-muted">Giá: <strong id="price">
                        <?php echo !empty($rooms) ? number_format($rooms[0]['price_per_night']) . "đ/đêm" : "Chưa có phòng"; ?>
                    </strong></p>

                <form action="book_hotel.php" method="POST" id="booking-form">
                    <input type="hidden" name="hotel_id" value="<?php echo $hotel['hotel_id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Chọn ngày nhận phòng</label>
                        <input type="date" name="check_in" class="form-control" value="<?php echo date('Y-m-d'); ?>"
                            min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chọn ngày trả phòng</label>
                        <input type="date" name="check_out" class="form-control"
                            value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                            min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loại phòng</label>
                        <select name="room_id" id="room-select" class="form-select">
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['room_id']; ?>"
                                    data-price="<?php echo $room['price_per_night']; ?>">
                                    <?php echo $room['room_type']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                            <label class="form-label">Số lượng</label>
                            <input type="number" class="form-control" name="quantity" min="1" max=<?php echo $room['stock'] ?> value="1" required>
                    </div>

                    <div id="availability-status" class="alert mt-2" style="display: none;"></div>

                    <button type="submit" class="btn btn-warning w-100" id="book-button">ĐẶT PHÒNG</button>
                </form>

            </div>
        </div>
    </div>
    <div class="review-section mt-4">
    <div class="row">
        <div class="col-md-6">
            <h3>Giới Thiệu Khách Sạn</h3>
            <div class="hotel-description bg-light p-3 rounded">
                <p class="description-text"><?php echo nl2br(htmlspecialchars($hotel['description'])); ?></p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="amenities-container">
                <?php foreach ($default_amenities as $category => $amenities): ?>
                    <div class="amenity-category">
                        <h5 class="category-title text-uppercase">
                            <?php
                            $category_labels = [
                                'general' => 'Tiện Ích Chung',
                                'room' => 'Tiện Nghi Phòng',
                                'bathroom' => 'Phòng Tắm',
                                'food' => 'Ẩm Thực',
                                'recreation' => 'Giải Trí',
                                'parking' => 'Xe Cộ',
                                'accessibility' => 'Tiện Ích Truy Cập'
                            ];
                            echo $category_labels[$category] ?? ucfirst($category);
                            ?>
                        </h5>
                        <div class="row">
                            <?php foreach ($amenities as $amenity): ?>
                                <div class="col-md-3 col-lg-3 text-center amenity-item">
                                    <div class="amenity-icon-wrapper">
                                        <i class="fas <?php echo $amenity['icon']; ?> fa-2x <?php echo $amenity['color']; ?>"></i>
                                    </div>
                                    <p class="amenity-name"><?php echo $amenity['name']; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
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
                <?php if (!empty($hotel_reviews)): ?>
                    <div class="reviews-list">
                        <?php foreach ($hotel_reviews as $review): ?>
                            <div class="review-item border-bottom w-25">
                                <div class="review-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="me-2"><?php echo htmlspecialchars($review['usr_name']); ?></strong>
                                        <div class="review-rating d-inline-block">
                                            <?php
                                            for ($i = 1; $i <= $review['rating']; $i++) {
                                                echo '<span class="text-warning">★</span>';
                                            }
                                            for ($i = $review['rating'] + 1; $i <= 5; $i++) {
                                                echo '<span class="text-muted">★</span>';
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkInInput = document.querySelector('[name="check_in"]');
        const checkOutInput = document.querySelector('[name="check_out"]');
        const roomSelect = document.getElementById('room-select');
        const bookButton = document.getElementById('book-button');
        const availabilityDiv = document.getElementById('availability-status');
        const hotelId = document.querySelector('[name="hotel_id"]').value;


        function checkAvailability() {
            const checkIn = checkInInput.value;
            const checkOut = checkOutInput.value;
            const roomId = roomSelect.value;

            if (!checkIn || !checkOut || !roomId) return;


            availabilityDiv.style.display = 'block';
            availabilityDiv.className = 'alert alert-info mt-2';
            availabilityDiv.textContent = 'Đang kiểm tra phòng trống...';


            fetch(`detail_hotels.php?action=check_availability&hotel_id=${hotelId}&room_id=${roomId}&check_in=${checkIn}&check_out=${checkOut}`)
                .then(response => response.json())
                .then(data => {
                    availabilityDiv.style.display = 'block';

                    if (data.available) {
                        availabilityDiv.className = 'alert alert-success mt-2';
                        availabilityDiv.textContent = data.message;
                        bookButton.disabled = false;
                    } else {
                        availabilityDiv.className = 'alert alert-danger mt-2';
                        availabilityDiv.textContent = data.message;
                        bookButton.disabled = true;
                    }
                })
                .catch(error => {
                    availabilityDiv.className = 'alert alert-danger mt-2';
                    availabilityDiv.textContent = 'Lỗi khi kiểm tra phòng trống';
                    console.error('Error:', error);
                    bookButton.disabled = true;
                });
        }


        checkInInput.addEventListener('change', checkAvailability);
        checkOutInput.addEventListener('change', checkAvailability);
        roomSelect.addEventListener('change', checkAvailability);


        if (checkInInput.value && checkOutInput.value) {
            setTimeout(checkAvailability, 500);


            document.getElementById('booking-form').addEventListener('submit', function (e) {
                const checkIn = new Date(checkInInput.value);
                const checkOut = new Date(checkOutInput.value);

                if (checkIn >= checkOut) {
                    alert('Ngày trả phòng phải sau ngày nhận phòng');
                    e.preventDefault();
                    return;
                }

                if (bookButton.disabled) {
                    alert('Phòng không còn trống trong khoảng thời gian này');
                    e.preventDefault();
                }
            });
        }
    });

    document.getElementById('room-select').addEventListener('change', function () {
        let selectedOption = this.options[this.selectedIndex];
        let newPrice = Number(selectedOption.getAttribute('data-price')).toLocaleString() + " đ";
        document.getElementById('price').innerText = newPrice;
    });

</script>

</body>

</html>