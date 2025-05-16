<?php
session_start();
include('server/connectdb.php');
$sql_popular_tours = "select t.tour_id, t.tour_name, t.image_url, t.price, t.days,
    COUNT(tb.booking_id) as total_bookings,
    (t.rating) as avg_rating
    FROM Tours t
    JOIN Tour_Bookings tb ON t.tour_id = tb.tour_id
    WHERE tb.status = 'paid'
    GROUP BY t.tour_id
    LIMIT 3";
$stmt = $conn->prepare($sql_popular_tours);
$stmt->execute();
$popular_tours = $stmt->fetchAll(PDO::FETCH_ASSOC);


$sql = "SELECT 
    r.review_id, 
    r.rating, 
    r.review_text, 
    r.review_date, 
    u.usr_name, 
    CASE 
        WHEN r.booking_type = 'Tour' THEN CONCAT('Tour: ', t.tour_name)
        WHEN r.booking_type = 'Hotel' THEN CONCAT('Khách sạn: ', h.hotel_name)
        WHEN r.booking_type = 'Ticket' THEN CONCAT('Vé: ', tk.ticketname)
        WHEN r.booking_type = 'Guide' THEN CONCAT('Hướng dẫn viên: ', g.name)
        ELSE 'Unknown'
    END AS booking_name
        FROM reviews r
        JOIN users u ON r.usr_id = u.usr_id
        LEFT JOIN Tours t ON r.booking_type = 'Tour' AND r.booking_item_id = t.tour_id
        LEFT JOIN Hotels h ON r.booking_type = 'Hotel' AND r.booking_item_id = h.hotel_id
        LEFT JOIN Tickets tk ON r.booking_type = 'Ticket' AND r.booking_item_id = tk.ticket_id
        LEFT JOIN Tour_Guides g ON r.booking_type = 'Guide' AND r.booking_item_id = g.guide_id
        ORDER BY RAND()
        LIMIT 3;";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Aloha - Đi Để Cảm Nhận</title>
    <link rel="stylesheet" href="https://cdn.materialdesignicons.com/4.8.95/css/materialdesignicons.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="usr/assets/style.css">
    <script src="usr/data/fetch.js"></script>

    <style>
        #btn-back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
            background: #f8c146;
            border: none;
            border-radius: 50%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            font-size: 18px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
        }

        #btn-back-to-top i {
            color: #fff;
        }

        #btn-back-to-top:hover {
            background: #e0a800;
        }

        :root {
            --primary-color: #ff9800;
            --secondary-color: #03a9f4;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: #333;

        }


        .btn-admin {
            font-weight: bold;
            border-radius: 10px;
            text-decoration: none;
            padding: 8px 20px;
        }

        .btn-admin:hover {
            background-color: rgb(253, 243, 230);
            color: black;
        }

        /* Tour Card Styles */
        .tour-card {
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .tour-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .tour-card .tour-img {
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .tour-card:hover .tour-img {
            transform: scale(1.05);
        }

        .tour-price {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255, 152, 0, 0.9);
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            font-weight: bold;
            z-index: 10;
        }

        .tour-card .badge-popular {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            z-index: 10;
        }

        .tour-card .card-body {

            transition: background-color 0.3s ease;
        }

        .tour-card:hover .card-body {
            background-color: rgba(255, 152, 0, 0.05);
        }

        /* Rating Styles */
        .tour-rating {
            display: flex;
            align-items: center;
        }

        .tour-rating .rating-stars {
            display: flex;
            margin-right: 10px;
        }

        .tour-rating .rating-stars i {
            color: #ffc107;
            margin-right: 2px;
        }

        .tour-rating .rating-count {
            color: #6c757d;
            font-size: 0.9rem;
        }




        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .tour-card {
                margin-bottom: 20px;
            }

            .tour-card .tour-img {
                height: 200px;
            }
        }


        .tour-destination {
            display: flex;
            align-items: center;
            transition: color 0.3s ease;
        }

        .tour-destination i {
            margin-right: 8px;
            color: #ff5722;
            transition: transform 0.3s ease;
        }

        .tour-destination:hover i {
            transform: translateX(3px);
        }

        .tour-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, #ff9800, #f44336);
            z-index: 1;
        }


        .participants-indicator {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 0.9rem;
        }

        .participants-indicator i {
            margin-right: 5px;
            color: #2196f3;
        }


        .tour-duration-pill {
            background-color: rgba(33, 150, 243, 0.1);
            color: #2196f3;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
        }

        .tour-duration-pill i {
            margin-right: 5px;
        }

        .cart-icon {
            font-size: 1.2rem;
        }


        #homeCarousel {
            height: 80vh;
            overflow: hidden;
        }

        #homeCarousel .carousel-item {
            height: 80vh;
        }

        #homeCarousel img {
            width: 100%;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .overlay {
            background-color: rgba(0, 0, 0, 0.4);
            top: 0;
            left: 0;
        }

        /* Menu Navigation */
        #menu .nav-link {
            color: #333;
            font-weight: 500;
            padding: 10px 20px;
            margin: 0 5px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        #menu .nav-link:hover,
        #menu .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        /* Why Choose Us Section */
        .feature-icon {
            background-color: rgba(255, 152, 0, 0.1);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            transition: all 0.3s ease;
        }

        .feature-icon i {
            font-size: 2.5rem;
            color: var(--primary-color);
        }

        .feature-box:hover .feature-icon {
            background-color: var(--primary-color);
        }

        .feature-box:hover .feature-icon i {
            color: white;
        }


        .featured-destination {
            padding: 60px 0;
            background-image: url('usr/img/bg5.jpg');
            background-size: 100%
        }

        .destination-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border: none;
            width: 310px;
            max-height: 400px;
        }

        .destination-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .destination-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .btn-explore {
            background-color: var(--primary-color);
            color: white;
            border-radius: 30px;
            padding: 8px 25px;
            border: none;
            font-size: 14px;
            width: 150px;
            transition: all 0.3s ease;
        }

        .btn-explore:hover {
            background-color: #e67e00;
            color: white;
        }

        .testimonial-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.1);
        }

        .testimonial-avatar img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border: 3px solid #ffc107;
        }

        .testimonial-text {
            font-size: 1rem;
            color: #555;
        }

        .rating i {
            font-size: 1.2rem;
        }

        .rating {
            color: #ffc107;
            margin-bottom: 10px;
        }

        /* Popular Tours */
        .popular-tours {
            padding: 60px 0;
        }

        .tour-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            height: 100%;
            border: none;
            transition: all 0.3s ease;
        }

        .tour-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .tour-img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .tour-price {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 30px;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            background-color: #212529;
            color: #adb5bd;
        }

        .footer a {
            color: #adb5bd;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer a:hover {
            color: var(--primary-color);
            text-decoration: none;
        }

        .footer-logo {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

     
        .cta-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('usr/img/bg3.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            text-align: center;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="usr/img/logo.jpeg" alt="Logo" class="logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form action="./usr/controllers/searchdes.php" id="search" class="d-flex search-box mx-auto">
                    <input class="form-control rounded-start-pill" type="search" name="search"
                        placeholder="Tìm địa điểm hoặc hoạt động...">

                    <button class="btn btn-warning rounded-end-pill" type="submit">Tìm kiếm</button>
                </form>

                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="usr/controllers/log_in.php">Đăng nhập</a></li>
                            <li><a class="dropdown-item" href="usr/controllers/acc.php">Thông tin cá nhân</a></li>
                            <li><a class="dropdown-item" href="usr/controllers/log_out.php">Đăng xuất</a></li>
                        </ul>
                    </div>
                    <a class="btn-admin" href="admin/login.php">Admin
                        <i class="fa-solid fa-user-tie"></i>
                    </a>

                </div>
            </div>
        </div>
    </nav>


    <!-- Hero Section -->
    <section class="hero-section">
        <div id="homeCarousel" class="carousel slide" data-bs-ride="carousel">
            <!-- 1. Indicators/dots -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#homeCarousel" data-bs-slide-to="0" class="active"
                    aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#homeCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#homeCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>

            <!-- 2. Carousel items/slides -->
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="usr/img/bg1.jpg" class="d-block w-100" alt="Background 1">
                </div>
                <div class="carousel-item">
                    <img src="usr/img/bg2.jpg" class="d-block w-100" alt="Background 2">
                </div>
                <div class="carousel-item">
                    <img src="usr/img/bg3.jpg" class="d-block w-100" alt="Background 3">
                </div>
            </div>

            <!-- 3. Controls/arrows -->
            <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>

            <!-- Overlay content -->
            <div
                class="overlay position-absolute w-100 h-100 d-flex flex-column justify-content-center align-items-center">
                <h1 class="display-4 fw-bold animate__animated animate__fadeInDown">🏝️ Cùng Aloha, Đi Để Cảm Nhận</h1>
                <p class="fs-5 animate__animated animate__fadeInUp animate__delay-1s">Trải Nghiệm Không Giới Hạn!</p>
                <a href="usr/controllers/tour.php" class="btn btn-lg rounded-pill animate__animated animate__fadeInUp">
                    Khám phá ngay <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>


    <!-- Menu Navigation -->
    <div class="container">
        <div class="row text-center py-4">
            <div class="col">
                <ul class="nav nav-pills justify-content-center" id="menu">
                    <li class="nav-item"><a class="nav-link" href="./usr/controllers/tickets.php"><b>Vé & Trải
                                nghiệm</b></a></li>
                    <li class="nav-item"><a class="nav-link" href="./usr/controllers/tour.php"><b>Tour</b></a></li>
                    <li class="nav-item"><a class="nav-link" href="./usr/controllers/hotel.php"><b>Khách sạn</b></a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="./usr/controllers/guider.php"><b>Hướng dẫn
                                viên</b></a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Why Choose Us Section -->
    <section class="container my-5 py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">Tại sao chọn Aloha?</h2>
            <p class="lead text-muted">Aloha mang đến cho bạn trải nghiệm du lịch tuyệt vời với dịch vụ đặt tour, vé
                tham quan, khách sạn và hướng dẫn viên chuyên nghiệp.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center feature-box p-4">
                    <div class="feature-icon">
                        <i class="fas fa-plane"></i>
                    </div>
                    <h4 class="mt-3 fw-bold">Đặt tour dễ dàng</h4>
                    <p class="text-muted">Nhiều lựa chọn tour phù hợp với sở thích của bạn với quy trình đặt tour đơn
                        giản, nhanh chóng.</p>
                    <p class="text-primary fw-bold">1000+ tour đang chờ bạn</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-center feature-box p-4">
                    <div class="feature-icon">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <h4 class="mt-3 fw-bold">Khách sạn tiện nghi</h4>
                    <p class="text-muted">Chọn từ hàng ngàn khách sạn chất lượng cao với đầy đủ tiện nghi và dịch vụ tốt
                        nhất.</p>
                    <p class="text-primary fw-bold">Đảm bảo giá tốt nhất</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-center feature-box p-4">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4 class="mt-3 fw-bold">Trải nghiệm độc đáo</h4>
                    <p class="text-muted">Tham gia các hoạt động khám phá thú vị và đa dạng tại mỗi điểm đến.</p>
                    <p class="text-primary fw-bold">Hàng ngàn hoạt động hấp dẫn</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Điểm đến nổi bật -->
    <section class="featured-destination py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Điểm Đến Nổi Bật</h2>
                <p class="text-muted">Khám phá những điểm đến hấp dẫn và độc đáo nhất tại Việt Nam</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card destination-card h-100">
                        <div class="position-relative overflow-hidden">
                            <img src="usr/img/cantho.jpg" alt="Cần Thơ" class="card-img-top">
                            <div
                                class="position-absolute top-0 start-0 bg-warning text-white px-3 py-1 m-3 rounded-pill">
                                <i class="fas fa-fire me-1"></i> Hot
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Cần Thơ</h5>
                            <p class="card-text text-muted">Thành phố Tây Đô với chợ nổi Cái Răng và miệt vườn trù phú.
                            </p>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                <span>Đồng bằng sông Cửu Long</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                    <span class="text-muted ms-1">(120)</span>
                                </span>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card destination-card h-100">
                        <div class="position-relative overflow-hidden">
                            <img src="usr/img/angiang.jpg" alt="An Giang" class="card-img-top">
                            <div class="position-absolute top-0 start-0 bg-info text-white px-3 py-1 m-3 rounded-pill">
                                <i class="fas fa-thumbs-up me-1"></i> Đề xuất
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold">An Giang</h5>
                            <p class="card-text text-muted">Vùng đất linh thiêng với rừng tràm Trà Sư và núi Sam huyền
                                bí.</p>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                <span>Đồng bằng sông Cửu Long</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <span class="text-muted ms-1">(98)</span>
                                </span>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card destination-card h-100">
                        <div class="position-relative overflow-hidden">
                            <img src="usr/img/baclieu.jpg" alt="Bạc Liêu" class="card-img-top">
                            <div
                                class="position-absolute top-0 start-0 bg-success text-white px-3 py-1 m-3 rounded-pill">
                                <i class="fas fa-percentage me-1"></i> Giảm giá
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Bạc Liêu</h5>
                            <p class="card-text text-muted">Miền đất của công tử Bạc Liêu, đờn ca tài tử và cánh đồng
                                quạt gió.</p>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                <span>Đồng bằng sông Cửu Long</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <span class="text-muted ms-1">(145)</span>
                                </span>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Tours Section -->
    <section class="container popular-tours my-5 py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">Tour Phổ Biến</h2>
            <p class="text-muted">Những tour du lịch được yêu thích nhất tại Aloha</p>
        </div>

        <div class="row g-4">
            <?php foreach ($popular_tours as $tour): ?>
                <div class="col">
                    <div class="card">
                        <div class="position-relative">
                            <img src="<?= str_replace("../img/", "usr/img/", $tour['image_url']) ?>" class="ticket-image">
                            <span class="tour-price"><?php echo number_format($tour['price']); ?>đ</span>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge bg-info">
                                    <i class="fas fa-clock me-1"></i> <?php echo $tour['days']; ?> ngày
                                </span>
                                <span class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <?php echo number_format($tour['avg_rating'], 1); ?>
                                </span>
                            </div>
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($tour['tour_name']); ?></h5>

                            <div class="d-flex justify-content-between align-items-center">

                                <a href="usr/controllers/detail_tour.php?id=<?= $tour['tour_id'] ?>"
                                    class="btn btn-warning rounded-pill">Đặt ngay</a>

                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="usr/controllers/tour.php" class="btn btn-outline-warning btn-lg rounded-pill px-4">
                Xem tất cả tour <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section mb-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="display-5 fw-bold mb-4 animate__animated animate__fadeInDown">Sẵn sàng cho chuyến đi tiếp
                        theo?</h2>
                    <p class="lead mb-4 animate__animated animate__fadeInUp">Đăng ký nhận thông báo về các ưu đãi đặc
                        biệt và cập nhật mới nhất từ Aloha</p>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="input-group mb-3">
                                <input type="email" class="form-control form-control-lg rounded-start-pill"
                                    placeholder="Nhập email của bạn">
                                <a class="btn btn-warning rounded-end-pill px-4" type="button"
                                    href="usr/controllers/log_in.php">Đăng ký</a>
                            </div>
                        </div>
                    </div>
                    <p class="small text-light-50 mt-2">Chúng tôi cam kết bảo mật thông tin của bạn</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Đánh giá khách hàng -->
    <section class="testimonial-section py-5">
        <div class="container text-center">
            <h2 class="fw-bold mb-4">Khách hàng nói gì về Aloha?</h2>
            <p class="text-muted mb-5">Những trải nghiệm thực tế từ khách hàng đã sử dụng dịch vụ của chúng tôi</p>

            <div class="row g-4">
                <?php foreach ($result as $row) { ?>
                    <div class="col-lg-4 col-md-6">
                        <div
                            class=" card testimonial-card p-4 rounded-4 shadow-lg bg-white h-100 position-relative transition-hover">

                            <div class="rating mb-3 text-warning">
                                <?php for ($i = 0; $i < 5; $i++) {
                                    echo $i < $row["rating"]
                                        ? '<i class="fas fa-star"></i>'
                                        : '<i class="far fa-star"></i>';
                                } ?>
                            </div>
                            <p class="testimonial-text fst-italic text-muted">"<?= htmlspecialchars($row["review_text"]) ?>"
                            </p>
                            <div class="d-flex align-items-center mt-4">
                                <div class="testimonial-image mt-4">
                                    <img src="https://picsum.photos/100?random=<?= rand(1, 1000) ?>" alt="Avatar"
                                        class="avatar rounded-circle">
                                </div>
                                <div>
                                    <h5 class="mb-1"><?= htmlspecialchars($row["usr_name"]) ?></h5>
                                    <p class="text-muted mb-0"><?= $row["booking_name"] ?: "Dịch vụ khác" ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer bg-dark">
        <div class="container">
            <div class="row text-center">
                <div class="footer-section">
                    <img src="usr/img/logo.jpeg" class="footer-logo">
                </div>
                <div class="footer-section">
                    <h5 class="-text-uppercase text-light">Dịch vụ</h5>
                    <ul class="list-unstyled">
                        <li><a href="usr/controllers/tour.php"><i class="fas fa-chevron-right me-2"></i> Tour</a></li>
                        <li><a href="usr/controllers/tickets.php"><i class="fas fa-chevron-right me-2"></i> Vé & Trải
                                nghiệm</a></li>
                        <li><a href="usr/controllers/shop.php"><i class="fas fa-chevron-right me-2"></i> Ẩm thực</a>
                        </li>
                        <li><a href="usr/controllers/hotel.php"><i class="fas fa-chevron-right me-2"></i> Khách sạn</a>
                        </li>
                        <li><a href="usr/controllers/guider.php"><i class="fas fa-chevron-right me-2"></i> Hướng dẫn
                                viên</a></li>
                    </ul>
                </div>
                <div class="footer-section col-lg-4 col-md-6 col-12">
                    <h5 class=" text-uppercase text-light">Liên hệ</h5>
                    <ul>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 101 Hùng Vương, Q. Tân Bình,TPHCM</li>
                        <li><i class="fas fa-envelope me-2"></i> <a
                                href="mailto:travelaloha@travelaloha.com">travelaloha@travelaloha.com</a></li>
                        <li><i class="fas fa-phone-alt me-2"></i> <a href="tel:+84942035835">+84942 035 835</a></li>
                        <li><i class="far fa-clock me-2"></i> Thứ 2 - Chủ nhật: 8:00 - 20:00</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom ">
            <p>&copy; 2025 Aloha Company. All Rights Reserved.</p>
        </div>
    </footer>
    <button id="btn-back-to-top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            var myCarousel = document.querySelector('#homeCarousel');
            if (myCarousel) {
                var carousel = new bootstrap.Carousel(myCarousel, {
                    interval: 4000,
                    wrap: true
                });

            }

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });


            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate__animated', 'animate__fadeIn');
                    }
                });
            }, {
                threshold: 0.1
            });

            document.querySelectorAll('.feature-box, .destination-card, .testimonial-card, .tour-card').forEach(element => {
                observer.observe(element);
            });
        });


        let mybutton = document.getElementById("btn-back-to-top");

        window.onscroll = function () {
            scrollFunction();
        };

        function scrollFunction() {
            if (mybutton) {
                if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                    mybutton.style.display = "block";
                } else {
                    mybutton.style.display = "none";
                }
            }
        }

        if (mybutton) {
            mybutton.addEventListener("click", backToTop);
        }

        function backToTop() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        }
    </script>

    <script>
        const btnBackToTop = document.getElementById("btn-back-to-top");

        window.onscroll = function () {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                btnBackToTop.style.opacity = "1";
                btnBackToTop.style.transform = "translateY(0)";
            } else {
                btnBackToTop.style.opacity = "0";
                btnBackToTop.style.transform = "translateY(20px)";
            }
        };

        btnBackToTop.addEventListener("click", function () {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    </script>

</body>

</html>