<?php
require '../config/db.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['username']);
    $raw_password = $_POST['password'];
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']); // Số điện thoại có thể rỗng do là tùy chọn
    
    // 1. KIỂM TRA TRÙNG LẶP EMAIL VÀ USERNAME TRƯỚC KHI THÊM
    // Kiểm tra Email
    $stmtCheckEmail = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmtCheckEmail->execute([$email]);
    $emailExists = $stmtCheckEmail->fetchColumn();

    // Kiểm tra Tên đăng nhập
    $stmtCheckUser = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmtCheckUser->execute([$user]);
    $userExists = $stmtCheckUser->fetchColumn();

    // 2. RÀNG BUỘC KIỂM TRA (Từ trên xuống dưới)
    if ($emailExists > 0) {
        $message = "<p style='color:red; margin-bottom: 10px;'>❌ Địa chỉ Email này đã được đăng ký. Vui lòng sử dụng Email khác!</p>";
    } elseif ($userExists > 0) {
        $message = "<p style='color:red; margin-bottom: 10px;'>❌ Tên đăng nhập này đã có người sử dụng. Vui lòng chọn tên khác!</p>";
    } elseif (strlen($user) < 6) {
        $message = "<p style='color:red; margin-bottom: 10px;'>❌ Tên đăng nhập phải có ít nhất 6 ký tự!</p>";
    } elseif (strlen($raw_password) < 6) {
        $message = "<p style='color:red; margin-bottom: 10px;'>❌ Mật khẩu phải có ít nhất 6 ký tự!</p>";
    } else {
        // 3. NẾU VƯỢT QUA MỌI KIỂM TRA -> MÃ HÓA PASS VÀ LƯU VÀO DB
        $pass = password_hash($raw_password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, 'student')");
            $stmt->execute([$user, $pass, $name, $email, $phone]);
            $message = "<p style='color:green; font-weight:bold; margin-bottom: 10px;'>✅ Đăng ký thành công! <a href='login.php' style='color: #2980b9;'>Đăng nhập ngay</a></p>";
        } catch (PDOException $e) {
            $message = "<p style='color:red; margin-bottom: 10px;'>❌ Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký - English App</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .auth-box input {
            margin-bottom: 12px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
            font-size: medium;
        }
        .auth-box input:focus {
            border-color: var(--primary-color, #3498db);
            outline: none;
        }
        .auth-footer {
            margin-top: 15px;
            text-align: center;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-box">
            <h2 style="margin-bottom: 20px; color: #333;">Tạo tài khoản mới</h2>
            
            <?= $message ?>
            
            <form method="POST">
                <input type="email" name="email" placeholder="Địa chỉ Email" required>
                <input type="text" name="full_name" placeholder="Họ và tên" required>
                <input type="text" name="phone" placeholder="Số điện thoại (Nếu có)">
                <input type="text" name="username" placeholder="Tên đăng nhập (Tối thiểu 6 ký tự)" required minlength="6">
                <input type="password" name="password" placeholder="Mật khẩu (Tối thiểu 6 ký tự)" required minlength="6">
                
                <button type="submit" class="btn" style="width: 100%; padding: 12px; font-size: 1.05rem; margin-top: 10px;">ĐĂNG KÝ</button>
            </form>
            
            <div class="auth-footer">
                Đã có tài khoản? <a href="login.php" style="color: var(--primary-color, #3498db); font-weight: bold; text-decoration: none;">Đăng nhập ngay</a>
            </div>
        </div>
    </div>
</body>
</html>