<?php

require '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Kiểm tra file helper có tồn tại không, nếu không thì dùng logic phân trang đơn giản
if (file_exists('../../includes/pagination_helper.php')) {
    require '../../includes/pagination_helper.php';
}
include '../../includes/header.php';
// 1. CHECK QUYỀN ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../../auth/login.php"); 
    exit; 
}

// 2. XỬ LÝ NẠP CREDITS (Cộng/Trừ lượt tạo bài)
if (isset($_POST['update_credits'])) {
    $u_id = $_POST['user_id'];
    $amount = (int)$_POST['amount']; // Số lượng mới

    try {
        $stmt = $conn->prepare("UPDATE users SET credits = ? WHERE user_id = ?");
        $stmt->execute([$amount, $u_id]);
        echo "<script>alert('Đã cập nhật số dư thành công!'); window.location.href='manage_users.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi: " . $e->getMessage() . "');</script>";
    }
}

// 3. XỬ LÝ XÓA USER (Nâng cao)
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('Không thể tự xóa chính mình!'); window.location.href='manage_users.php';</script>";
        exit;
    }

    try {
        $conn->beginTransaction();

        // A. Xóa kết quả thi của user
        $conn->prepare("DELETE FROM test_results WHERE user_id = ?")->execute([$id]);

        // B. Xóa các câu hỏi trong các bài Quiz do User này tạo (Tính năng AI)
        // Lấy danh sách quiz_id do user này tạo
        $stmt_q = $conn->prepare("SELECT quiz_id FROM quizzes WHERE created_by = ?");
        $stmt_q->execute([$id]);
        $user_quizzes = $stmt_q->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($user_quizzes)) {
            // Xóa câu hỏi của các quiz đó
            $in  = str_repeat('?,', count($user_quizzes) - 1) . '?';
            $conn->prepare("DELETE FROM questions WHERE quiz_id IN ($in)")->execute($user_quizzes);
            
            // Xóa bài quiz
            $conn->prepare("DELETE FROM quizzes WHERE created_by = ?")->execute([$id]);
        }

        // C. Cuối cùng xóa User
        $conn->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);

        $conn->commit();
        echo "<script>alert('Đã xóa người dùng và toàn bộ dữ liệu liên quan!'); window.location.href='manage_users.php';</script>";
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "<script>alert('Lỗi xóa: " . $e->getMessage() . "');</script>";
    }
}

//SEARCH & PHÂN TRANG
// 1. TÌM KIẾM
$search = trim($_GET['search'] ?? '');
$search_sql = "";
$params = [];

if ($search !== '') {
    // Tìm kiếm tương đối (LIKE) trên 3 cột
    $search_sql = " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR username LIKE ? OR user_id LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];
}

// 2. PHÂN TRANG & LẤY DỮ LIỆU (Đã ghép nối điều kiện tìm kiếm)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Số học viên trên 1 trang (bạn đang để là 2)

// Ghép $search_sql vào lệnh đếm
$sql_count = "SELECT COUNT(*) FROM users WHERE role = 'student' $search_sql";

// Ghép $search_sql vào lệnh lấy dữ liệu
$sql_data  = "SELECT * FROM users WHERE role = 'student' $search_sql ORDER BY user_id DESC";

// Gọi hàm phân trang, NHỚ TRUYỀN BIẾN $params VÀO THAY VÌ ĐỂ TRỐNG []
$paging = getPagingData($conn, $sql_count, $sql_data, $params, $limit, $page);

// Xuất dữ liệu ra biến để HTML sử dụng
$users = $paging['data'];
$total_pages = $paging['total_pages'];
$current_page = $paging['current_page'];
$total_records = $paging['total_records'];

?>
<style>
    .btn-danger:hover {
        background: #d4402f;
}
</style>
<div >
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: var(--primary-color);"><i class="fas fa-users-cog"></i> Quản lý Học viên</h2>
        <a href="../../admin/dashboard.php" class="btn" style="background: #777;">&larr; Quay lại Dashboard</a>
    </div>
    <span class="badge" style="background: #eee; color: #333; padding: 10px;">Tổng: <b><?= $total_records ?></b> Học Viên</span>

    <form method="GET" style="margin-top: 30px;margin-bottom:30px ;display: flex; gap: 10px;">
            <input required type="text" name="search" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($search) ?>" style="margin: 0;padding: 10px; border: 1px solid #ccc; border-radius: 5px;width:100%">
            <button type="submit" class="btn" style="background: var(--primary-color);width:200px;padding:0"><i class="fas fa-search"></i> Tìm kiếm</button>
            <?php if($search !== ''): ?>
                <a href="manage_users.php" class="btn" style="background: #95a5a6;align-content: center;width:100px;"><i class="fas fa-times"></i> Hủy</a>
            <?php endif; ?>
    </form>
    
    <div style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
        <table class="table" style="margin-bottom: 0;">
            <thead style="background: #f8f9fa;">
                <tr>
                    <th>ID</th>
                    <th>Thông tin cá nhân</th>
                    <th>Liên hệ (Email)</th>
                    <th>Số Điện Thoại</th>
                    <th style="text-align: center;">Ví Credits (AI)</th>
                    <th>Ngày tham gia</th>
                    <th style="text-align: center;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($users)): ?>
                    <tr><td colspan="6" style="text-align:center; padding: 30px;">Chưa có học viên nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?= $u['user_id'] ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($u['username']) ?>&size=40&background=random" style="border-radius: 50%;">
                                <div>
                                    <div style="font-weight: bold;"><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></div>
                                    <div style="font-size: 0.85rem; color: #888;">@<?= htmlspecialchars($u['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($u['email'] ?? 'Chưa cập nhật') ?></td>
                        <td><?= htmlspecialchars($u['phone'] ?? 'Chưa cập nhật') ?></td>
                        
                        <td style="text-align: center;">
                            <span style="font-size: 1.1rem; font-weight: bold; color: <?= ($u['credits'] > 0) ? '#27ae60' : '#e74c3c' ?>">
                                <?= number_format($u['credits']) ?>
                            </span>
                            <br>
                            <button type="button" 
                                    class="btn-sm" 
                                    style="background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem; padding: 2px 8px; margin-top: 5px;"
                                    onclick="openCreditModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['username']) ?>', <?= $u['credits'] ?>)">
                                <i class="fas fa-edit"></i> Nạp/Sửa
                            </button>
                        </td>

                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        
                        <td style="text-align: center;">
                            <a href="manage_users.php?delete_id=<?= $u['user_id'] ?>" 
                               class="btn btn-danger"
                               style="padding: 5px 10px; font-size: 0.9rem;"
                               onclick="return confirm('CẢNH BÁO: Xóa user sẽ xóa hết bài thi tự tạo và lịch sử điểm của họ. Tiếp tục?')">
                                <i class="fas fa-trash"></i> Xóa
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php renderPagingUI($total_pages, $current_page); ?>
</div>

<div id="creditModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 10px; width: 400px; max-width: 90%; position: relative; animation: fadeInUp 0.3s;">
        <span onclick="document.getElementById('creditModal').style.display='none'" style="position: absolute; top: 10px; right: 15px; cursor: pointer; font-size: 1.5rem;">&times;</span>
        
        <h3 style="margin-top: 0; color: var(--primary-color);">Cập nhật Credits</h3>
        <p>Học viên: <b id="modalUsername">...</b></p>
        
        <form method="POST">
            <input type="hidden" name="user_id" id="modalUserId">
            
            <div style="margin-bottom: 15px;">
                <label>Số dư mới:</label>
                <input type="number" name="amount" id="modalAmount" class="form-control" style="width: 100%; padding: 10px; font-size: 1.2rem; font-weight: bold;">
                <small style="color: #666;">Nhập số lượt tạo bài bạn muốn set cho user này.</small>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="update_credits" class="btn" style="flex: 1; background: #27ae60;">Lưu thay đổi</button>
                <button type="button" onclick="document.getElementById('creditModal').style.display='none'" class="btn" style="background: #999;">Hủy</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Hàm mở Modal
    function openCreditModal(id, username, currentCredits) {
        document.getElementById('creditModal').style.display = 'flex';
        document.getElementById('modalUserId').value = id;
        document.getElementById('modalUsername').innerText = username;
        document.getElementById('modalAmount').value = currentCredits;
        document.getElementById('modalAmount').focus();
    }

    // Đóng modal khi click ra ngoài
    window.onclick = function(event) {
        if (event.target == document.getElementById('creditModal')) {
            document.getElementById('creditModal').style.display = 'none';
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>