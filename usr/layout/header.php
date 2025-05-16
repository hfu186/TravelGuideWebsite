<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Aloha</title>

    <link rel="stylesheet" href="https://cdn.materialdesignicons.com/4.8.95/css/materialdesignicons.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../data/fetch.js"></script>

</head>

<body>
 
    <nav class="header navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="/src/index.php">
            <img src="../img/logo.jpeg" alt="Logo" class="logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
            <form  action="../controllers/searchdes.php" id="search" class="d-flex search-box mx-auto">
                <input class="form-control rounded-start-pill" type="search" name="search"
                    placeholder="Tìm địa điểm hoặc hoạt động...">
               
                <button class="btn btn-warning rounded-end-pill" type="submit">Tìm kiếm</button>
            </form>
            <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user" style="color: #FFD43B;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="../controllers/log_in.php">Đăng nhập</a></li>
                            <li><a class="dropdown-item" href="../controllers/acc.php">Thông tin cá nhân</a></li>
                            <li><a class="dropdown-item" href="../controllers/log_out.php">Đăng xuất</a></li>
                        </ul>
                    </div>

                </div>
        </div>
    </div>
</nav>


</body>

</html>