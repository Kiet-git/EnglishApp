<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

include '../includes/header.php';
?>

<div  style="text-align: center;">
    <h2><i class="fas fa-coins" style="color: #f1c40f;"></i> Nạp Thêm Lượt (Credits)</h2>
    <p style="color: #7f8c8d; margin-bottom: 30px;">Chọn gói nạp phù hợp để tiếp tục sử dụng AI tạo đề thi.</p>

    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
        <div style="background: #fff; border: 2px solid #ecf0f1; border-radius: 10px; padding: 30px; width: 220px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
            <h3 style="color: #3498db; margin-top: 0;">Gói Cơ Bản</h3>
            <h2 style="color: #2c3e50; font-size: 2rem; margin: 15px 0;">10,000đ</h2>
            <p style="font-weight: bold; color: #27ae60;">Nhận 10 Credits</p>
            <form action="create_payment.php" method="POST">
                <input type="hidden" name="amount" value="10000">
                <input type="hidden" name="credits" value="10">
                <button type="submit" class="btn" style="width: 100%; margin-top: 15px; background: #3498db;">Mua ngay</button>
            </form>
        </div>

        <div style="background: #fff; border: 2px solid #f39c12; border-radius: 10px; padding: 30px; width: 220px; box-shadow: 0 4px 15px rgba(243, 156, 18, 0.2); position: relative;">
            <span style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #f39c12; color: white; padding: 2px 10px; border-radius: 10px; font-size: 0.8rem; font-weight: bold;">PHỔ BIẾN</span>
            <h3 style="color: #f39c12; margin-top: 0;">Gói Tiêu Chuẩn</h3>
            <h2 style="color: #2c3e50; font-size: 2rem; margin: 15px 0;">20,000đ</h2>
            <p style="font-weight: bold; color: #27ae60;">Nhận 25 Credits</p>
            <form action="create_payment.php" method="POST">
                <input type="hidden" name="amount" value="20000">
                <input type="hidden" name="credits" value="25">
                <button type="submit" class="btn" style="width: 100%; margin-top: 15px; background: #f39c12;">Mua ngay</button>
            </form>
        </div>

        <div style="background: #fff; border: 2px solid #ecf0f1; border-radius: 10px; padding: 30px; width: 220px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
            <h3 style="color: #9b59b6; margin-top: 0;">Gói Đam Mê</h3>
            <h2 style="color: #2c3e50; font-size: 2rem; margin: 15px 0;">50,000đ</h2>
            <p style="font-weight: bold; color: #27ae60;">Nhận 70 Credits</p>
            <form action="create_payment.php" method="POST">
                <input type="hidden" name="amount" value="50000">
                <input type="hidden" name="credits" value="70">
                <button type="submit" class="btn" style="width: 100%; margin-top: 15px; background: #9b59b6;">Mua ngay</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>