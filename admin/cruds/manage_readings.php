<?php
require '../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Kiểm tra quyền Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// XỬ LÝ XÓA BÀI ĐỌC
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM readings WHERE id = ?");
        $stmt->execute([$del_id]);
        echo "<script>alert('Đã xóa bài đọc thành công!'); window.location='manage_readings.php';</script>";
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Lỗi khi xóa: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// ==========================================
// XỬ LÝ BỘ LỌC TÌM KIẾM NHANH
// ==========================================
$filter_level = $_GET['level'] ?? '';
$sort_order = $_GET['sort'] ?? 'DESC'; // Mặc định là mới nhất (DESC)

// CHỈ CHỌN CÁC BÀI DO HỆ THỐNG TẠO (user_id IS NULL)
$query = "
    SELECT r.id, r.title, r.level, r.created_at, t.topic_name 
    FROM readings r 
    LEFT JOIN topics t ON r.topic_id = t.topic_id 
    WHERE r.user_id IS NULL
";
$params = [];

// Nếu có chọn cấp độ
if (!empty($filter_level)) {
    $query .= " AND r.level = ?";
    $params[] = $filter_level;
}

// Xử lý sắp xếp (Tránh SQL Injection bằng cách kiểm tra cứng giá trị)
$order = ($sort_order === 'ASC') ? 'ASC' : 'DESC';
$query .= " ORDER BY r.created_at " . $order;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="container" style="margin-top: 30px; margin-bottom: 50px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #2c3e50;"><i class="fas fa-tasks"></i> Quản Lý Bài Đọc Hệ Thống</h2>
        <a href="../../admin/dashboard.php" class="btn" style="background: #777;">&larr; Quay lại Dashboard</a>
    </div>

    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e9ecef;">
        <form method="GET" style="display: flex; gap: 15px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <label style="font-weight: bold; color: #2c3e50;"><i class="fas fa-filter"></i> Cấp độ:</label>
                <select name="level" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="">-- Tất cả --</option>
                    <option value="A1" <?= $filter_level == 'A1' ? 'selected' : '' ?>>A1 - Cơ bản</option>
                    <option value="A2" <?= $filter_level == 'A2' ? 'selected' : '' ?>>A2 - Sơ cấp</option>
                    <option value="B1" <?= $filter_level == 'B1' ? 'selected' : '' ?>>B1 - Trung cấp</option>
                    <option value="B2" <?= $filter_level == 'B2' ? 'selected' : '' ?>>B2 - Trung cao cấp</option>
                    <option value="C1" <?= $filter_level == 'C1' ? 'selected' : '' ?>>C1 - Nâng cao</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <label style="font-weight: bold; color: #2c3e50;"><i class="fas fa-sort"></i> Sắp xếp:</label>
                <select name="sort" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>Cũ nhất</option>
                </select>
            </div>

            <button type="submit" style="background: #27ae60; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                Áp dụng
            </button>
            
            <?php if (!empty($filter_level) || $sort_order === 'ASC'): ?>
                <a href="manage_readings.php" style="color: #e74c3c; text-decoration: none; font-size: 0.9rem; margin-left: 10px; font-weight: bold;">
                    <i class="fas fa-times"></i> Xóa lọc
                </a>
            <?php endif; ?>
        </form>
    </div>
    <div style="display: flex;
    justify-content: right">
        <a href="../../modules/reading/create_reading.php" class="btn btn-primary" style="background: #8e44ad; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold;">
            <i class="fas fa-plus"></i> Tạo Bài Đọc Mới (AI)
        </a>
    </div>
        
    <div style="background: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: #2c3e50; color: white;">
                    <th style="padding: 15px;">ID</th>
                    <th style="padding: 15px;">Tiêu đề</th>                   
                    <th style="padding: 15px;">Cấp độ</th>
                    <th style="padding: 15px;">Ngày tạo</th>
                    <th style="padding: 15px; text-align: center;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($readings) > 0): ?>
                    <?php foreach ($readings as $r): ?>
                        <tr style="border-bottom: 1px solid #ecf0f1;">
                            <td style="padding: 15px; font-weight: bold;">#<?= $r['id'] ?></td>
                            <td style="padding: 15px; color: #2980b9; font-weight: bold;"><?= htmlspecialchars($r['title']) ?></td>
                            <td style="padding: 15px;">
                                <span style="background: #f39c12; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.85rem;">
                                    <?= htmlspecialchars($r['level']) ?>
                                </span>
                            </td>
                            <td style="padding: 15px; color: #7f8c8d; font-size: 0.9rem;"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                            <td style="padding: 15px; text-align: center;">
                                <a href="../../modules/reading/reading.php?id=<?= $r['id'] ?>" target="_blank" title="Xem thử" style="color: #27ae60; margin-right: 10px; font-size: 1.2rem;"><i class="fas fa-eye"></i></a>
                                <a href="edit_reading.php?id=<?= $r['id'] ?>" title="Sửa" style="color: #3498db; margin-right: 10px; font-size: 1.2rem;"><i class="fas fa-edit"></i></a>
                                <a href="?delete_id=<?= $r['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa bài đọc này không? Dữ liệu không thể khôi phục!');" title="Xóa" style="color: #e74c3c; font-size: 1.2rem;"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="padding: 30px; text-align: center; color: #95a5a6;">Chưa có bài đọc hệ thống nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>