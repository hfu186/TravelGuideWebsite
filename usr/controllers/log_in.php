<?php
session_start();
require('../../server/connectdb.php');

$error_msg = '';
$success_msg = '';

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['pw']);
    $action = $_POST['action'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Email không hợp lệ!";
    } else {
        try {
            if ($action === "register") {
                $fullname = trim($_POST['fullname']);
                $stmt = $conn->prepare("SELECT COUNT(*) FROM Users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                if ($stmt->fetchColumn()) {
                    $error_msg = "Email này đã được sử dụng. Vui lòng chọn email khác.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'user';
                    $stmt = $conn->prepare("INSERT INTO Users (usr_name, email, password, role) VALUES (:fullname, :email, :password, :role)");
                    $stmt->bindParam(':fullname', $fullname);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':role', $role);
                    if ($stmt->execute()) {
                        $userId = $conn->lastInsertId();
                        $_SESSION['usr_id'] = $userId;
                        $_SESSION['usr_name'] = $fullname;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = $role;
                        $_SESSION['logged_in'] = true;
                        $success_msg = "Đăng ký thành công!";
    
                    } else {
                        $error_msg = "Đã xảy ra lỗi khi đăng ký. Vui lòng thử lại.";
                    }
                }
            } elseif ($action === "login") {
                $stmt = $conn->prepare("SELECT * FROM Users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                 
                    if ($user['password'] === $password) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $updateStmt = $conn->prepare("UPDATE Users SET password = :password WHERE usr_id = :id");
                        $updateStmt->bindParam(':password', $hashed_password);
                        $updateStmt->bindParam(':id', $user['usr_id']);
                        $updateStmt->execute();
                        $_SESSION['usr_id'] = $user['usr_id'];
                        $_SESSION['usr_name'] = $user['usr_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in'] = true;
                        if (isset($_SESSION['redirect_after_login'])) {
                            $redirectUrl = $_SESSION['redirect_after_login'];
                            unset($_SESSION['redirect_after_login']);
                            header("Location: " . $redirectUrl);
                            exit();
                        } else {
                            header("Location: ../../index.php");
                            exit();
                        }
                    }
                    else if (password_verify($password, $user['password'])) {
                        $_SESSION['usr_id'] = $user['usr_id'];
                        $_SESSION['usr_name'] = $user['usr_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in'] = true;
                        
                        if (isset($_SESSION['redirect_after_login'])) {
                            $redirectUrl = $_SESSION['redirect_after_login'];
                            unset($_SESSION['redirect_after_login']);
                            header("Location: " . $redirectUrl);
                            exit();
                        } else {
                            header("Location: ../../index.php");
                            exit();
                        }
                    } else {
                        $error_msg = "Email hoặc mật khẩu không chính xác!";
                    }
                } else {
                    $error_msg = "Email hoặc mật khẩu không chính xác!";
                }
            }
        } catch (PDOException $e) {
            $error_msg = "Lỗi: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <title>Đăng Nhập / Đăng Ký</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/login.css" />

</head>

<body>
    <section>
        <div class="container-lg" id="container">
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show position-absolute top-0 start-50 translate-middle-x mt-3" role="alert" style="z-index:1000">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show position-absolute top-0 start-50 translate-middle-x mt-3" role="alert" style="z-index:1000">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="form-container sign-up-container">
            <form action="log_in.php" method="POST" class="signup-form">
                    <h1>Đăng Ký</h1>
                    <input type="hidden" name="action" value="register">
                    <label>
                        <input type="email" id="register_email" name="email" placeholder="Nhập email" required />
                    </label>
                    <label>
                        <input type="text" id="fullname" name="fullname" placeholder="Họ và tên" required />
                    </label>
                    <label>
                        <input type="password" id="register_pw" name="pw" placeholder="Mật khẩu" required minlength="6" />
                    </label>
                    <button type="submit" name="submit" style="margin-top: 9px">Đăng Ký</button>
                </form>
            </div>

            <div class="form-container sign-in-container">
                <form action="log_in.php" method="POST">
                    <h1>Đăng Nhập</h1>
                    <input type="hidden" name="action" value="login">
                    <label>
                        <input type="email" id="login_email" name="email" placeholder="Email" required>
                    </label>
                    <label>
                        <input type="password" id="login_pw" name="pw" placeholder="Mật khẩu" required />
                    </label>
                    <button  type="submit" name="submit">Đăng Nhập</button>
                </form>
            </div>

            <div class="overlay-container">
                <div class="overlay">
                    <div class="overlay-panel overlay-left">
                        <h1>Đăng Nhập</h1>
                        <p>Nếu bạn đã có tài khoản, hãy đăng nhập ngay tại đây!</p>
                        <button class="ghost mt-5" id="signIn">Đăng Nhập</button>
                    </div>
                    <div class="overlay-panel overlay-right">
                        <h1>Tạo tài khoản!</h1>
                        <p>Nếu bạn chưa có tài khoản, hãy đăng ký ngay.</p>
                        <button class="ghost" id="signUp">Đăng Ký</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../data/login.js"></script>

</body>

</html>
