<?php
session_start();
include('includes/connectdb.php');

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['pw'];
    $sql = "SELECT * FROM Users WHERE email=:email AND role='admin'";
    $query = $conn->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->execute();
    $admin = $query->fetch(PDO::FETCH_ASSOC);
    if ($admin && $password == $admin['password']) {
        $_SESSION['admin_login'] = true;
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Thông tin đăng nhập không chính xác!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<title>Admin Login</title>
	<link rel="stylesheet" href="assets/css/lgin.css">
</head>

<body>
<video autoplay loop muted playsinline id="bg-video">
    <source src="../images/bgvid.mp4" type="video/mp4">
    Trình duyệt của bạn không hỗ trợ video.
</video>


    <section>
        <div class="container-lg" id="container">
            <div class="form-container sign-in-container">
                <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
                    <h1>Đăng Nhập Admin</h1>
                    <input type="hidden" name="action" value="login">
                    <label>
                        <input type="email" id="email" name="email" placeholder="Email" required>
                    </label>
                    <label>
                        <input type="password" name="pw" id="pw" placeholder="Mật khẩu" required />
                    </label>
                    <button name="login" type="submit">Đăng Nhập</button>
                </form>
            </div>
        </div>
    </section>

</body>

</html>