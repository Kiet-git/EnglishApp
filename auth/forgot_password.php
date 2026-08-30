<?php
require '../config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Kiểm tra email có tồn tại không
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Tạo token ngẫu nhiên và thời gian hết hạn (1 giờ)
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 3600); // Hết hạn sau 1 tiếng
        
        // Lưu token vào DB
        $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $update_stmt->execute([$token, $expires, $email]);
        
        // Tạo link Khôi phục
        // Chú ý: Đổi 'localhost/english_app' thành tên thư mục dự án của bạn nếu khác
        $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/EnglishApp/auth/reset_password.php?token=" . $token;
        
        // Gửi email (Dùng hàm mail cơ bản của PHP)
        $subject = "Yeu cau khoi phuc mat khau";
        $body = "Chào " . $user['full_name'] . ",\n\nClick vào link sau để đặt lại mật khẩu của bạn (có hiệu lực trong 1 giờ):\n" . $reset_link;
        $headers = "From: noreply@englishapp.com";
        
        @mail($email, $subject, $body, $headers); // Thêm @ để ẩn lỗi nếu localhost chưa config mail

        $message = "<p style='color:green; margin-bottom: 15px;'>Nếu email tồn tại, chúng tôi đã gửi link reset.</p>";
        
        // [DÀNH CHO DEV] Hiển thị link ra màn hình để bạn test trên localhost (Khi up web thật hãy xóa dòng này)
        //$message .= "<div style='background:#eef; padding:10px; word-break: break-all; font-size: 0.9rem;'><b>Link test:</b> <a href='$reset_link'>$reset_link</a></div>";
        
    } else {
        $message = "<p style='color:red; margin-bottom: 15px;'>Nếu email tồn tại, chúng tôi đã gửi link reset.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-box">
            <h2 style="margin-bottom: 20px;">Quên mật khẩu</h2>
            <?= $message ?>
            <form method="POST">
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">Nhập Email bạn đã đăng ký để nhận link đặt lại mật khẩu.</p>
                <input type="email" name="email" placeholder="Nhập địa chỉ Email" required style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px;">
                <button type="submit" class="btn" style="width: 100%; padding: 12px;">GỬI LINK KHÔI PHỤC</button>
            </form>
            <div style="margin-top: 15px; text-align: center;">
                <a href="login.php" style="color: var(--primary-color); text-decoration: none;">&larr; Quay lại Đăng nhập</a>
            </div>
        </div>
    </div>
</body>
</html>