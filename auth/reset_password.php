<?php
require '../config/db.php';

$message = "";
$token = $_GET['token'] ?? '';
$valid_token = false;

if (empty($token)) {
    die("Liên kết không hợp lệ!");
}

// Kiểm tra token có đúng và còn hạn không
$stmt = $conn->prepare("SELECT user_id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if ($user) {
    $valid_token = true;
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (strlen($new_password) < 6) {
            $message = "<p style='color:red;'>Mật khẩu phải có ít nhất 6 ký tự!</p>";
        } elseif ($new_password !== $confirm_password) {
            $message = "<p style='color:red;'>Mật khẩu nhập lại không khớp!</p>";
        } else {
            // Đổi mật khẩu thành công
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Cập nhật mật khẩu mới và Xóa token
            $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
            $update_stmt->execute([$hashed_password, $user['user_id']]);
            
            $message = "<p style='color:green; font-weight:bold;'>Đổi mật khẩu thành công! Đang chuyển hướng...</p>";
            echo "<script>setTimeout(() => { window.location.href = 'login.php'; }, 2500);</script>";
            $valid_token = false; // Ẩn form sau khi đổi xong
        }
    }
} else {
    $message = "<p style='color:red;'>Liên kết khôi phục không hợp lệ hoặc đã hết hạn.</p>";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .auth-box input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;}
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-box">
            <h2 style="margin-bottom: 20px;">Đặt lại mật khẩu mới</h2>
            <?= $message ?>
            
            <?php if($valid_token): ?>
            <form method="POST">
                <input type="password" name="new_password" placeholder="Mật khẩu mới (Tối thiểu 6 ký tự)" required minlength="6">
                <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required minlength="6">
                <button type="submit" class="btn" style="width: 100%; padding: 12px;">ĐỔI MẬT KHẨU</button>
            </form>
            <?php endif; ?>
            
            <?php if(!$valid_token && empty($_POST)): ?>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="forgot_password.php" class="btn">Thử lại</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>