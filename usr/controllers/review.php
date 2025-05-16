<?php
include('../../server/connectdb.php');
session_start();


if (!isset($_SESSION['logged_in'])) {
    header('location: log_in.php');
    exit;
}
$usr_id = $_SESSION['usr_id'];
if (isset($_GET['booking_id'])) {
    $booking_id = isset($_GET['booking_id']);
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_type = $_POST['booking_type'];
    $booking_item_id = $_POST['booking_item_id'];
    $rating = $_POST['rating'];
    $review_text = trim($_POST['review_text']);

    $errors = [];
    if (empty($rating)) {
        $errors[] = "Vui lòng chọn đánh giá sao";
    }
    if (empty($review_text)) {
        $errors[] = "Vui lòng nhập nội dung đánh giá";
    }


    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO reviews 
                (usr_id, booking_type, booking_item_id, rating, review_text) 
                VALUES (:usr_id, :booking_type, :booking_item_id, :rating, :review_text)
            ");

            $stmt->bindParam(':usr_id', $usr_id);
            $stmt->bindParam(':booking_type', $booking_type);
            $stmt->bindParam(':booking_item_id', $booking_item_id);
            $stmt->bindParam(':rating', $rating);
            $stmt->bindParam(':review_text', $review_text);

            if ($stmt->execute()) {
                if ($booking_type == 'Ticket') {
                    $updateStmt = $conn->prepare("UPDATE Tickets SET rating = :rating WHERE ticket_id = :booking_item_id");
                } elseif ($booking_type == 'Tour') {
                    $updateStmt = $conn->prepare("UPDATE Tours SET rating = :rating WHERE tour_id = :booking_item_id");
                } elseif ($booking_type == 'Hotel') {
                    $updateStmt = $conn->prepare("UPDATE Hotels SET rating = :rating WHERE hotel_id = :booking_item_id");
                } elseif ($booking_type == 'Guide') {
                    $updateStmt = $conn->prepare("UPDATE Tour_Guides SET rating = :rating WHERE guide_id = :booking_item_id");
                }
           
                if (isset($updateStmt)) {
                    $updateStmt->bindParam(':rating', $rating);
                    $updateStmt->bindParam(':booking_item_id', $booking_item_id);
                    $updateStmt->execute();
                }
        
                header("Location: acc.php?review_success=1");
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Lỗi: " . $e->getMessage();
        }
    }
}

try {
    $stmt = $conn->prepare("
    SELECT 
        CASE 
            WHEN booking_type = 'Hotel' THEN hb.hotel_id
            WHEN booking_type = 'Tour' THEN tb.tour_id
            WHEN booking_type = 'Ticket' THEN tkb.ticket_id
            WHEN booking_type = 'Guide' THEN gb.guide_id
        END AS booking_item_id,
        CASE 
            WHEN booking_type = 'Hotel' THEN h.hotel_name
            WHEN booking_type = 'Tour' THEN t.tour_name
            WHEN booking_type = 'Ticket' THEN tk.ticketname
            WHEN booking_type = 'Guide' THEN tg.name
        END AS item_name,
        CASE 
            WHEN booking_type = 'Hotel' THEN h.img_url
            WHEN booking_type = 'Tour' THEN t.image_url
            WHEN booking_type = 'Ticket' THEN tk.img_url
            WHEN booking_type = 'Guide' THEN tg.img_url
        END AS item_image,
        booking_type
    FROM (
        SELECT 'Hotel' AS booking_type, hotel_id, usr_id 
        FROM hotel_bookings WHERE usr_id = :usr_id
        UNION
        SELECT 'Tour', tour_id, usr_id 
        FROM Tour_Bookings WHERE usr_id = :usr_id
        UNION
        SELECT 'Ticket', ticket_id, usr_id 
        FROM Ticket_Bookings WHERE usr_id = :usr_id
        UNION
        SELECT 'Guide', guide_id, usr_id 
        FROM guide_bookings WHERE usr_id = :usr_id
    ) AS bookings
    LEFT JOIN hotel_bookings hb ON booking_type = 'Hotel' AND hb.hotel_id = bookings.hotel_id AND hb.usr_id = :usr_id
    LEFT JOIN Tour_Bookings tb ON booking_type = 'Tour' AND tb.tour_id = bookings.hotel_id AND tb.usr_id = :usr_id
    LEFT JOIN Ticket_Bookings tkb ON booking_type = 'Ticket' AND tkb.ticket_id = bookings.hotel_id AND tkb.usr_id = :usr_id
    LEFT JOIN guide_bookings gb ON booking_type = 'Guide' AND gb.guide_id = bookings.hotel_id AND gb.usr_id = :usr_id
    LEFT JOIN Rooms r ON booking_type = 'Hotel' AND r.room_id = hb.room_id
    LEFT JOIN Hotels h ON booking_type = 'Hotel' AND h.hotel_id = r.hotel_id
    LEFT JOIN Tours t ON booking_type = 'Tour' AND t.tour_id = tb.tour_id
    LEFT JOIN Tickets tk ON booking_type = 'Ticket' AND tk.ticket_id = tkb.ticket_id
    LEFT JOIN Tour_Guides tg ON booking_type = 'Guide' AND tg.guide_id = gb.guide_id
");

    $stmt->bindParam(':usr_id', $usr_id);
    $stmt->execute();
    $bookable_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh Giá Dịch Vụ</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
==
</head>

<body>
    <?php include ('../layout/header.php'); ?>

    <div class="container mt-5">
        <div class="review-container">
            <h2 class="text-center mb-4">
            <i class="fa-solid fa-star"></i> Đánh Giá Dịch Vụ
            </h2>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-1">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" id="reviewForm" novalidate>
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-list-alt me-2"></i>Chọn Dịch Vụ Để Đánh Giá
                        <span class="required-mark">*</span>
                    </label>
                    <select 
                        name="booking_type" 
                        class="form-select" 
                        id="serviceSelect" 
                        required
                    >
                        <option value="">Chọn Dịch Vụ</option>
                        <?php foreach ($bookable_items as $item): ?>
                            <option 
                                value="<?php echo $item['booking_type']; ?>"
                                data-image="<?php echo htmlspecialchars($item['item_image'] ?? ''); ?>"
                                data-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                data-id="<?php echo $item['booking_item_id']; ?>"
                            >
                                <?php echo htmlspecialchars($item['item_name'] . ' (' . $item['booking_type'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="error-message" id="serviceError"></div>
                </div>

                <div id="selectedServiceDetails" class="mb-3" style="display:none;">
                    <div>
                        <div>
                            <div class="row">
                                <div class="col-md-4">
                                    <img 
                                        id="serviceImage" 
                                        src="" 
                                        alt="Ảnh dịch vụ" 
                                        class="img-fluid rounded"
                                    >
                                </div>
                                <div class="col-md-8">
                                    <h5 id="serviceName"></h5>
                                    <p id="serviceType" class="text-muted"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đánh giá sao -->
                <div class="star-rating">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" <?php

                          if (isset($_POST['rating']) && $_POST['rating'] == $i) {
                              echo 'checked';
                          }
                          ?>>
                    <label for="star<?php echo $i; ?>"><i class="fa-solid fa-star"></i></label>
                <?php endfor; ?>
            </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-comment me-2"></i>Nội Dung Đánh Giá
                        <span class="required-mark">*</span>
                    </label>
                    <textarea 
                        name="review_text" 
                        class="form-control" 
                        rows="4" 
                        placeholder="Chia sẻ chi tiết trải nghiệm của bạn..."
                        required
                        minlength="10"
                        maxlength="500"
                    ></textarea>
                    <small class="form-text text-muted">
                        Từ 10 đến 500 ký tự
                    </small>
                    <div class="error-message" id="reviewTextError"></div>
                </div>
                <input type="hidden" name="booking_item_id" id="bookingItemId">
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane me-2"></i>Gửi Đánh Giá
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include ('../layout/footer.php'); ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const serviceSelect = document.getElementById('serviceSelect');
        const selectedServiceDetails = document.getElementById('selectedServiceDetails');
        const serviceImage = document.getElementById('serviceImage');
        const serviceName = document.getElementById('serviceName');
        const serviceType = document.getElementById('serviceType');
        const bookingItemId = document.getElementById('bookingItemId');

        serviceSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                selectedServiceDetails.style.display = 'block';
                const imageUrl = selectedOption.getAttribute('data-image');
                serviceImage.src = imageUrl || 'path/to/default/image.jpg';
                serviceImage.style.display = imageUrl ? 'block' : 'none';
                
                serviceName.textContent = selectedOption.getAttribute('data-name');
                serviceType.textContent = selectedOption.value;
                bookingItemId.value = selectedOption.getAttribute('data-id');
            } else {
                selectedServiceDetails.style.display = 'none';
                bookingItemId.value = '';
            }
        });

        const form = document.getElementById('reviewForm');
        form.addEventListener('submit', function(event) {
            let isValid = true;

            if (!serviceSelect.value) {
                document.getElementById('serviceError').textContent = 'Vui lòng chọn dịch vụ';
                isValid = false;
            } else {
                document.getElementById('serviceError').textContent = '';
            }

            document.querySelectorAll('.star-rating i').forEach(star => {
            star.addEventListener('click', function () {
                const rating = this.getAttribute('data-rating');
                document.querySelectorAll('.star-rating i').forEach(s => {
                    s.classList.remove('fas');
                    s.classList.add('far');
                });
                document.querySelectorAll('.star-rating i').forEach(s => {
                    if (parseInt(s.getAttribute('data-rating')) <= parseInt(rating)) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    }
                });
                document.getElementById('rating-input').value = rating;
            });
        });
            const reviewText = document.querySelector('textarea[name="review_text"]');
            if (reviewText.value.trim().length < 10) {
                document.getElementById('reviewTextError').textContent = 'Nội dung đánh giá phải từ 10 ký tự trở lên';
                isValid = false;
            } else {
                document.getElementById('reviewTextError').textContent = '';
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    });
    </script>
</body>
</html>


