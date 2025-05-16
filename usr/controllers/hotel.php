<?php
include('../../server/connectdb.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('../layout/header.php') ?>

</head>

<body>
    <section class="home position-relative text-center text-white">
        <img src="../img/bgks.jpg" class="img-fluid" alt="Du lịch">
        <div
            class="overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center">
            <h1 class="display-4 fw-bold">Khách sạn</h1>
            <h4 class="fs-5">Nơi dừng chân lý tưởng – Ở đâu cũng như ở nhà</h4>
        </div>
    </section>
    <div class="container">
        <div class="row text-center py-4">
            <div class="col">
                <ul class="nav nav-pills justify-content-center" id="menu">
                    <li class="nav-item"><a class="nav-link " href="tickets.php">Vé & Trải nghiệm</a></li>
                    <li class="nav-item"><a class="nav-link" href="tour.php">Tour</a></li>
                    <li class="nav-item"><a class="nav-link" href="hotel.php">Khách sạn</a></li>
                    <li class="nav-item"><a class="nav-link" href="guider.php">Hướng dẫn viên</a></li>
                </ul>
            </div>
        </div>
    </div>
    <h2 class="text-center"><b>Khách sạn</b></h2>
    <h6>Tận hưởng kỳ nghỉ thoải mái với những khách sạn tốt nhất và giá cả hợp lý.</h6>
    <hr class="mx-auto w-25">
    <div class="py-3 mt-3 w-100">
        <div class="row">
           
                <div class="col-2">
                <div class="search-box-ks">
                <h5>Tìm kiếm</h5>
                    <select id="citySelect" class="form-control mb-2">
                        <option value="">Thành phố</option>
                    </select>
                    <button id="searchButton" class="btn btn-primary w-100">Tìm kiếm</button>
                </div>
            </div>
            <div class="col-9">
                <div id="hotel-list"></div>
            </div>
        </div>
    </div>

    <?php include('../layout/footer.php') ?>
</body>

</html>