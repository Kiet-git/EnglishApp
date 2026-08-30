<?php
// [SỬA LỖI ĐƯỜNG DẪN]: Thêm ../../ để lùi ra ngoài thư mục gốc
require '../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Chỉ Admin mới được vào
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    // Sửa lại đường dẫn chuyển hướng
    header("Location: ../../auth/login.php"); exit; 
}

// Xử lý khi Admin bấm "Duyệt"
if (isset($_GET['approve_id'])) {
    $txn_id = intval($_GET['approve_id']);
    
    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ? AND status = 'WAITING'");
    $stmt->execute([$txn_id]);
    $txn = $stmt->fetch();

    if ($txn) {
        // Cập nhật trạng thái và cộng tiền
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE transactions SET status = 'PAID' WHERE id = ?")->execute([$txn_id]);
            $conn->prepare("UPDATE users SET credits = credits + ? WHERE user_id = ?")->execute([$txn['credits'], $txn['user_id']]);
            $conn->commit();
            echo "<script>alert('Duyệt thành công! Đã cộng {$txn['credits']} Credits cho user.'); window.location.href='manage_transactions.php';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi hệ thống!');</script>";
        }
    }
}

// ==========================================
// XỬ LÝ TÌM KIẾM
// ==========================================
$search = $_GET['search'] ?? '';
$query = "SELECT t.*, u.full_name, u.username FROM transactions t JOIN users u ON t.user_id = u.user_id";
$params = [];

if (!empty($search)) {
    // Tìm theo Mã ĐH, Username hoặc Tên hiển thị
    $query .= " WHERE t.order_code LIKE ? OR u.username LIKE ? OR u.full_name LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// [SỬA LỖI ĐƯỜNG DẪN]
include '../../includes/header.php';
?>

<div  style="margin-top: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color:var(--primary-color); margin: 0;"><i class="fas fa-money-check-alt"></i> Quản lý Nạp Credits</h2>
    </div>

    <form method="GET" style="margin-top: 30px;margin-bottom:30px ;display: flex; gap: 10px;">
            <input required type="text" name="search" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($search) ?>" style="margin: 0;padding: 10px; border: 1px solid #ccc; border-radius: 5px;width:100%">
            <button type="submit" class="btn" style="background: var(--primary-color);width:200px;padding:0"><i class="fas fa-search"></i> Tìm kiếm</button>
            <?php if($search !== ''): ?>
                <a href="manage_transactions.php" class="btn" style="background: #95a5a6;align-content: center;width:100px;"><i class="fas fa-times"></i> Hủy</a>
            <?php endif; ?>
    </form>
    
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <thead>
            <tr style="background: #34495e; color: white; text-align: left;">
                <th style="padding: 12px; border: 1px solid #ddd;">Thời gian</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Mã ĐH</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Người dùng</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Số tiền</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Gói nạp</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Trạng thái</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($transactions) > 0): ?>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd;"><?= $t['created_at'] ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; font-weight: bold;"><?= $t['order_code'] ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd;"><?= $t['full_name'] ?> <br><small>(<?= $t['username'] ?>)</small></td>
                    <td style="padding: 12px; border: 1px solid #ddd; color: #e74c3c; font-weight: bold;"><?= number_format($t['amount']) ?>đ</td>
                    <td style="padding: 12px; border: 1px solid #ddd; color: #27ae60; font-weight: bold;">+<?= $t['credits'] ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <?php 
                            if ($t['status'] == 'PENDING') echo "<span style='color: #7f8c8d;'>Đang tạo mã</span>";
                            elseif ($t['status'] == 'WAITING') echo "<span style='color: #f39c12; font-weight:bold;'>Chờ duyệt</span>";
                            elseif ($t['status'] == 'PAID') echo "<span style='color: #27ae60; font-weight:bold;'>Đã duyệt</span>";
                        ?>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <?php if ($t['status'] == 'WAITING'): ?>
                            <a href="manage_transactions.php?approve_id=<?= $t['id'] ?>" class="btn" style="background: #27ae60; padding: 5px 10px; font-size: 0.85rem;" onclick="return confirm('Bạn đã nhận được tiền đơn hàng này chưa?');">Duyệt nạp</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="padding: 20px; text-align: center; color: #7f8c8d;">Không tìm thấy giao dịch nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>