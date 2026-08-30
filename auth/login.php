<?php
session_start();

require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :u");
    $stmt->execute(['u' => $user]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data && password_verify($pass, $data['password'])) {

        $_SESSION['user_id'] = $data['user_id'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['name'] = $data['full_name'];

        if ($data['role'] == 'admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../index.php");
        }
        exit;

    } else {
        $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - English App</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        #eyeIcon:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-box">
            <h2>Welcome Back!</h2>
            <p>Đăng nhập để tiếp tục học tập</p>
            
            <?php if(isset($error)) echo "<p style='color:red; font-weight:bold;'>$error</p>"; ?>           
                <form method="POST">
                    <input type="text" name="username" placeholder="Tên đăng nhập" required autocomplete="off">
                    
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" placeholder="Mật khẩu" required
                            style="width: 100%; padding: 10px 45px 10px 15px; box-sizing: border-box;">
                        
                        <i id="eyeIcon" class="fa-solid fa-eye" onclick="togglePassword()"
                            style="position: absolute; right: 15px; top: 44.3%; transform: translateY(-50%); cursor: pointer; color: #7f8c8d;"></i>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">ĐĂNG NHẬP</button>
                </form>           
            <div class="auth-footer">
                Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
            </div>
            <div style="margin-top: 10px; text-align: center;">
                <a href="#" style="color: #e74c3c; text-decoration: none; font-size: 0.9rem;">Quên mật khẩu?</a>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener("DOMContentLoaded", function() {
            document.body.classList.add("loaded");
        });
    </script>
    <script>
        function togglePassword() {
            const pass = document.getElementById("password");
            const icon = document.getElementById("eyeIcon");

            if (pass.type === "password") {
                pass.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                pass.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>