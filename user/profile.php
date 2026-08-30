<?php

require '../config/db.php';
include '../includes/header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// XỬ LÝ CẬP NHẬT HỒ SƠ
$update_message = "";
if (isset($_POST['btn_update_profile'])) {
    $new_phone = trim($_POST['update_phone']);
    $new_name = $_POST['update_name'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $user_id = $_SESSION['user_id'];

    // 1. Cập nhật số điện thoại & tên trước
    $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE user_id = ?");
    $stmt->execute([$new_phone, $user_id]);

    $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
    $stmt->execute([$new_name, $user_id]);
    $_SESSION['name'] = $new_name;

    $update_message = "<p style='color: green; text-align: center;'>Đã cập nhật!</p>";

    // 2. Nếu người dùng có nhập mật khẩu mới thì tiến hành đổi mật khẩu
    if (!empty($new_password)) {
        // Lấy mật khẩu cũ từ DB để kiểm tra
        $stmt_pass = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt_pass->execute([$user_id]);
        $current_hash = $stmt_pass->fetchColumn();

        if (password_verify($old_password, $current_hash)) {
            if (strlen($new_password) < 6) {
                $update_message .= "<p style='color: red; text-align: center;'>Mật khẩu mới phải từ 6 ký tự trở lên!</p>";
            } else {
                // Đổi mật khẩu
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_up_pass = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt_up_pass->execute([$new_hash, $user_id]);
                $update_message .= "<p style='color: green; text-align: center;'>Đã đổi mật khẩu thành công!</p>";
            }
        } else {
            $update_message .= "<p style='color: red; text-align: center;'>Mật khẩu hiện tại không đúng!</p>";
        }
    }
    
    // Refresh lại dữ liệu user để hiển thị số điện thoại mới ngay lập tức
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

$user_id = $_SESSION['user_id'];
// Lấy thông tin user mới nhất
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// === LẤY LỊCH SỬ GIAO DỊCH (Tối đa 10 giao dịch gần nhất) ===
$stmt_history = $conn->prepare("SELECT amount, credits, status, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt_history->execute([$user_id]);
$transactions = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

?>
<style>
    .btn-danger:hover{
        background: #cf4333;
    }
</style>
<div>
    <div style="display: flex; gap: 30px; margin-top: 30px;">
        
        <div style="flex: 1; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); height: fit-content;">
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['full_name']) ?>&background=random" style="width: 100px; border-radius: 50%;">
                <h3><?= htmlspecialchars($user['full_name']) ?></h3>
                
                <p style="color: #666; margin: 5px 0;">Email: <?= htmlspecialchars($user['email']) ?></p>
                <p style="color: #666; margin: 5px 0;">Số điện thoại: <?= htmlspecialchars($user['phone'] ?? 'Chưa cập nhật') ?></p>
                
                <div style="background: #f0f8ff; padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px dashed #3498db;">
                    <span style="display: block; font-size: 0.9rem; color: #555;">Số Credits của tôi:</span>
                    <strong style="font-size: 2rem; color: #3498db;"><?= number_format($user['credits']) ?></strong>
                </div>
            </div>
            
            <?= $update_message ?? '' ?>

            <button onclick="toggleEditForm()" style="width: 100%; display: block; text-align: center; margin-bottom: 10px; height:45.6px; background: #f39c12; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem;">
                <i class="fas fa-user-edit"></i> Sửa hồ sơ
            </button>
            
            <a href="../auth/logout.php" class="btn btn-danger" style="width: 100%; display: block; text-align: center; padding: 10px; text-decoration: none; border-radius: 5px;">Đăng xuất</a>

            <div id="editProfileForm" style="display: none; margin-top: 20px; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd; text-align: left;">
                <h4 style="margin-top: 0; color: #333; border-bottom: 1px solid #ccc; padding-bottom: 8px; margin-bottom: 15px;">Cập nhật thông tin</h4>
                <form method="POST">
                    <div style="margin-bottom: 6px;">
                        <label style="font-size: 0.9rem; color: #555; display: block; margin-bottom: 5px;">Họ & Tên của bạn:</label>
                        <input type="text" name="update_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Nhập Họ và Tên" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;margin:0px">
                    </div>
                    <div style="margin-bottom: 6px;">
                        <label style="font-size: 0.9rem; color: #555; display: block; margin-bottom: 5px;">Số điện thoại:</label>
                        <input type="text" name="update_phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Nhập số điện thoại" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;margin:0px">
                    </div>
                    
                    <hr style="border: 0; border-top: 1px dashed #ccc; margin: 15px 0;">
                    <p style="font-size: 0.85rem; color: #888; margin-bottom: 10px;"><i>(Bỏ trống nếu không muốn đổi mật khẩu)</i></p>
                    
                    <div style="margin-bottom: 12px;">
                        <label style="font-size: 0.9rem; color: #555; display: block; margin-bottom: 5px;">Mật khẩu hiện tại:</label>
                        <input type="password" name="old_password" placeholder="Mật khẩu cũ" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;margin:0px">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="font-size: 0.9rem; color: #555; display: block; margin-bottom: 5px;">Mật khẩu mới:</label>
                        <input type="password" name="new_password" placeholder="Ít nhất 6 ký tự" minlength="6" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;margin:0px">
                    </div>
                    
                    <button type="submit" name="btn_update_profile" style="width: 100%; padding: 10px; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                        Lưu Thay Đổi
                    </button>
                </form>
            </div>
        </div>

        <div style="flex: 2;">
            
            <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 25px;">
                <h3 style="margin-top: 0; color: var(--primary-color);"><i class="fas fa-magic"></i> AI Tạo Bài Tự Động & Kho Riêng</h3>
                <p>Tạo bài thi và bài đọc mới hoặc xem lại các bài bạn đã tạo.</p>
                
                <div style="display: flex; gap: 10px;">
                        <a href="../modules/create_ai.php" class="btn" style="flex: 1; text-align: center; background: var(--primary-color);">
                            <i class="fas fa-plus-circle"></i> Tạo Mới Ngay
                        </a>
                    <a href="../user/my_quizzes.php" class="btn" style="flex: 1; text-align: center; background: #f39c12;">
                        <i class="fas fa-folder-open"></i> Kho Của Tôi
                    </a>
                </div>
            </div>

            <h3 style="margin-bottom: 15px;"><i class="fas fa-shopping-cart"></i> Mua thêm lượt tạo đề</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
                <div style="background: #fff; padding: 20px; border-radius: 8px; text-align: center; border: 1px solid #eee;box-shadow: 0 4px 10px rgba(0,0,0,0.05)">
                    <h4>Gói Cơ Bản</h4>
                    <h2 style="color: #27ae60;">10.000đ</h2>
                    <p>Nhận <b>10</b> Credits</p>
                    <form action="../payments/create_payment.php" method="POST">
                        <input type="hidden" name="amount" value="10000">
                        <input type="hidden" name="credits" value="10">
                        <button type="submit" class="btn" style="width: 100%; margin-top: 15px; background: #3498db;font-size: 0.8rem;">Mua ngay</button>
                    </form>
                </div>

                <div style="background: #fff; padding: 20px; border-radius: 8px; text-align: center; border: 2px solid #f1c40f; position: relative;box-shadow: 0 4px 10px rgba(0,0,0,0.05)">
                    <div style="position: absolute; top: -10px; right: -10px; background: #e74c3c; color: white; padding: 5px 10px; font-size: 0.7rem; border-radius: 10px;">Phổ biến</div>
                    <h4>Gói Tiêu Chuẩn</h4>
                    <h2 style="color: #27ae60;">20.000đ</h2>
                    <p>Nhận <b>25</b> Credits</p>
                    <form action="../payments/create_payment.php" method="POST">
                        <input type="hidden" name="amount" value="20000">
                        <input type="hidden" name="credits" value="25">
                        <button type="submit" class="btn" style="width: 100%; margin-top: 15px; background: #f39c12;font-size: 0.8rem;">Mua ngay</button>
                    </form>
                </div>

                <div style="background: #fff; padding: 20px; border-radius: 8px; text-align: center; border: 1px solid #eee;box-shadow: 0 4px 10px rgba(0,0,0,0.05)">
                    <h4>Gói Chuyên Gia</h4>
                    <h2 style="color: #27ae60;">50.000đ</h2>
                    <p>Nhận <b>70</b> Credits</p>
                    <form action="../payments/create_payment.php" method="POST">
                        <input type="hidden" name="amount" value="50000">
                        <input type="hidden" name="credits" value="70">
                        <button type="submit" class="btn" style="width: 100%; margin-top: 15px; background: #9b59b6;font-size: 0.8rem;">Mua ngay</button>
                    </form>
                </div>
            </div>

            <h3 style="margin-bottom: 15px;"><i class="fas fa-history"></i> Lịch sử nạp Credits</h3>
            <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
                    <thead>
                        <tr style="background: #f9f9f9; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px;">Ngày nạp</th>
                            <th style="padding: 12px;">Số tiền</th>
                            <th style="padding: 12px;">Credits nhận</th>
                            <th style="padding: 12px;">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $txn): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($txn['created_at'])) ?></td>
                                    <td style="padding: 12px; font-weight: bold; color: #27ae60;"><?= number_format($txn['amount']) ?>đ</td>
                                    <td style="padding: 12px; font-weight: bold;">+<?= $txn['credits'] ?></td>
                                    <td style="padding: 12px;">
                                        <?php
                                        // Phân loại badge trạng thái
                                        $status = strtolower($txn['status']);
                                        if (in_array($status, ['success', 'completed', 'paid'])) {
                                            echo '<span style="background: #e8f8f5; color: #27ae60; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;">Thành công</span>';
                                        } elseif (in_array($status, ['pending', 'processing','waiting'])) {
                                            echo '<span style="background: #fef9e7; color: #f39c12; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;">Chờ xử lý</span>';
                                        } else {
                                            echo '<span style="background: #fdedec; color: #e74c3c; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;">Thất bại/Hủy</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="padding: 20px; text-align: center; color: #888;">Bạn chưa có giao dịch nạp Credits nào.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
<script>
function toggleEditForm() {
    var form = document.getElementById('editProfileForm');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}
</script>
<?php include '../includes/footer.php'; ?>