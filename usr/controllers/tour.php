<?php include('../../server/connectdb.php')?>

<!DOCTYPE html>
<html lang="en">

<head>
   <?php include ('../layout/header.php')?>
</head>

<body>
<section class="home position-relative text-center text-white">
    <img src="../img/bghead.jpg" class="img-fluid" alt="Du lịch">
    <div class="overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center">
        <h1 class="display-4 fw-bold">Tour</h1>
        <h4 class="fs-5">Hành trình đáng nhớ, kỷ niệm trọn đời</>
    </div>
</section>

<div class="container">
    <div class="row text-center py-4">
        <div class="col">
            <ul class="nav nav-pills justify-content-center" id="menu">
                <li class="nav-item"><a class="nav-link " href="./tickets.php">Vé & Trải nghiệm</a></li>
                <li class="nav-item"><a class="nav-link" href="./tour.php">Tour</a></li>
                <li class="nav-item"><a class="nav-link" href="./hotel.php">Khách sạn</a></li>
                <li class="nav-item"><a class="nav-link" href="./guider.php">Hướng dẫn viên</a></li>
            </ul>
        </div>
    </div>
</div>  
    <section>
        <h2 class="text-center"><b>TOUR</b></h2>
        <h6>Cung cấp những chương trình tour theo sở thích - thời gian - ngân sách của quý khách.</h6>
        <hr class="mx-auto w-25">
        <div class="container my-2">
            <div class="row g-4" id="tour-list">
            </div>
        </div>
    </section>
    <?php include ('../layout/footer.php')?>
    <script src="../data/fetch.js"></script>
</body>

</html>