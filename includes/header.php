<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// include config for BASE_URL constant
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>English Learning App</title>
    <!-- use BASE_URL so paths work from any directory depth -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* 1. ĐỊNH NGHĨA HIỆU ỨNG FADE IN + TRƯỢT LÊN */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px); /* Dịch xuống 20px */
            }
            to {
                opacity: 1;
                transform: translateY(0); /* Trở về vị trí cũ */
            }
        }

        /* 2. ÁP DỤNG CHO TOÀN BỘ NỘI DUNG */
        body {
            /* Giúp font chữ mượt hơn trên màn hình hiện đại */
            -webkit-font-smoothing: antialiased; 
        }

        /* Class .container là nơi chứa nội dung chính của bạn */
        .container {
            animation: fadeInUp 0.6s ease-out; /* Chạy hiệu ứng trong 0.6 giây */
        }

        /* 3. HIỆU ỨNG NÚT BẤM (HOVER) */
        .btn, button, a {
            transition: all 0.3s ease; /* Mọi thay đổi màu sắc sẽ diễn ra từ từ trong 0.3s */
        }
        
        .btn:hover {
            transform: translateY(-2px); /* Nhấc nhẹ nút lên khi di chuột vào */
            box-shadow: 0 4px 8px rgba(0,0,0,0.15); /* Đổ bóng nhẹ */
        }

        /* 4. HIỆU ỨNG FORM INPUT */
        input[type="text"], input[type="password"], textarea, select {
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            border-color: var(--primary-color, #3498db);
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.3); /* Phát sáng nhẹ khi gõ */
            outline: none;
        }

        /* 5. HIỆU ỨNG BẢNG (TABLE) */
        table tbody tr {
            transition: background-color 0.2s ease;
        }
        table tbody tr:hover {
            background-color: #f1f7ff; /* Đổi màu nền nhẹ khi di chuột qua dòng */
            cursor: default;
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-content">
        <div class="left-nav">
            <a href="<?php echo BASE_URL; ?>/index.php" style="font-size: 1.2rem; text-transform: uppercase;margin-right:40px">
                <i class="fas fa-graduation-cap"></i> English App
            </a>
            <a href="<?php echo BASE_URL; ?>/modules/topics/topics_list.php">Chủ đề</a>
            <a href="<?php echo BASE_URL; ?>/modules/reading/reading_list.php">Bài đọc</a>
            <a href="<?php echo BASE_URL; ?>/modules/quiz/quiz_list.php">Luyện tập</a>
            <a href="<?php echo BASE_URL; ?>/modules/create_ai.php">Tiện ích</a>
            <a href="<?php echo BASE_URL; ?>/theory.php">Cẩm nang</a>
            <a href="<?php echo BASE_URL; ?>/payments/topup.php">Gói Credits</a>                

        </div>
        
        <div class="right-nav">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?php echo BASE_URL; ?>/user/profile.php" style="margin-left: 15px; color: #f1c40f; font-weight: bold; border: 1px solid #f1c40f; padding: 5px 10px; border-radius: 20px; transition: 0.3s;">
                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']) ?>
                </a>

                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" style="margin-left: 10px; background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 4px;">Admin Panel</a>
                <?php endif; ?>

                <a href="<?php echo BASE_URL; ?>/auth/logout.php" style="margin-left: 15px; color: #ffcccc;">Đăng xuất</a>
            
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/auth/login.php">Đăng nhập</a>
                <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn" style="background: white; color: var(--primary-color);">Đăng ký</a>
            <?php endif; ?>
        </div>
        
    </div>
    
</nav>

<div class="container">
    