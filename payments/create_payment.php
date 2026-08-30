<?php
require '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

// --- 1. XỬ LÝ KHI KHÁCH BẤM NÚT "ĐÃ CHUYỂN KHOẢN" ---
// Chỉ lúc này chúng ta mới lưu vào Database (Lưu thẳng thành WAITING)
if (isset($_POST['mark_paid'])) {
    $user_id = $_SESSION['user_id'];
    $amount = intval($_POST['amount']);
    $credits = intval($_POST['credits']);
    $orderCode = $_POST['order_code']; // Lấy lại đúng cái mã QR lúc nãy

    // Insert thẳng vào DB trạng thái WAITING
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, order_code, amount, credits, status) VALUES (?, ?, ?, ?, 'WAITING')");
    $stmt->execute([$user_id, $orderCode, $amount, $credits]);

    echo "<script>alert('Đã gửi yêu cầu! Admin sẽ duyệt và cộng Credit cho bạn trong ít phút.'); window.location.href='topup.php';</script>";
    exit;
}

// --- 2. XỬ LÝ KHI KHÁCH VỪA TỪ TRANG CHỌN GÓI BƯỚC SANG ---
if (!isset($_POST['amount']) || !isset($_POST['credits'])) {
    header("Location: topup.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$amount = intval($_POST['amount']);
$credits = intval($_POST['credits']);

// TẠO MÃ ĐƠN HÀNG ĐỂ HIỂN THỊ QR (Chưa lưu vào DB)
$orderCode = date('ymdHis') . rand(10, 99);

// CẤU HÌNH NGÂN HÀNG CỦA BẠN
$bank_id = "MB"; // Mã ngân hàng
$account_no = "123456789"; // Số tài khoản
$account_name = "NGUYEN VAN A"; // Tên chủ tài khoản
$description = "NAP " . $orderCode; 

// GỌI API VIETQR
$qr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_no}-compact2.png?amount={$amount}&addInfo=" . urlencode($description) . "&accountName=" . urlencode($account_name);

include '../includes/header.php';
?>

<div  style="text-align: center; margin-top: 40px;">
    <div style="background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #3498db;">
        <h2 style="color: #2c3e50; margin-top: 0;">Thanh Toán Gói <?= $credits ?> Credits</h2>
        <p style="color: #7f8c8d;">Mở ứng dụng ngân hàng và quét mã QR dưới đây.</p>
        
        <div style="margin: 20px 0;">
            <img src="<?= $qr_url ?>" alt="QR Code" style="max-width: 100%; height: auto; border-radius: 10px; border: 1px solid #ddd; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        </div>
        
        <h3 style="color: #e74c3c;">Số tiền: <?= number_format($amount) ?> VNĐ</h3>
        <p><b>Nội dung CK:</b> <span style="color: #2980b9; font-weight: bold; font-size: 1.2rem; background: #ecf0f1; padding: 5px 10px; border-radius: 5px;"><?= $description ?></span></p>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

        <p style="font-size: 0.9rem; color: #555;">Sau khi chuyển khoản thành công, vui lòng bấm nút dưới đây để thông báo cho Admin.</p>
        
        <form method="POST">
            <input type="hidden" name="amount" value="<?= $amount ?>">
            <input type="hidden" name="credits" value="<?= $credits ?>">
            <input type="hidden" name="order_code" value="<?= $orderCode ?>">
            
            <button type="submit" name="mark_paid" class="btn" style="background: #27ae60; color: white; border: none; padding: 15px 25px; font-size: 1.1rem; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold;">
                <i class="fas fa-check-circle"></i> Tôi đã chuyển khoản xong
            </button>
        </form>
        
        <br>
        <a href="topup.php" style="color: #7f8c8d; text-decoration: none; font-size: 0.9rem;">Hủy giao dịch</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>