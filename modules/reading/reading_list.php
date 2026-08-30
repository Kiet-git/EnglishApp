<?php
// Lưu ý: Nếu file này bạn để ở thư mục gốc (ngang hàng index.php) thì đổi thành require 'config/db.php';
require '../../config/db.php'; 
if (session_status() === PHP_SESSION_NONE) session_start();

// Lấy danh sách chủ đề để hiển thị trong Dropdown lọc
$topicStmt = $conn->query("SELECT topic_id, topic_name FROM topics ORDER BY topic_name ASC");
$topics = $topicStmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// XỬ LÝ BỘ LỌC TÌM KIẾM & TAB NGUỒN BÀI ĐỌC
// ==========================================
$filter_source = $_GET['source'] ?? 'system'; // Mặc định là bài của hệ thống
$filter_level = $_GET['level'] ?? '';
$filter_topic = $_GET['topic'] ?? '';
$sort_order = $_GET['sort'] ?? 'DESC';

// Nếu chọn xem "Bài của tôi" nhưng chưa đăng nhập thì bắt đăng nhập
if ($filter_source === 'mine' && !isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$query = "
    SELECT r.*, t.topic_name 
    FROM readings r 
    LEFT JOIN topics t ON r.topic_id = t.topic_id 
    WHERE 1=1
";
$params = [];

// Xử lý phân loại bài đọc
if ($filter_source === 'mine') {
    $query .= " AND r.user_id = ?";
    $params[] = $_SESSION['user_id'];
} else {
    // Bài của hệ thống là bài có user_id = NULL
    $query .= " AND r.user_id IS NULL";
}

// Xử lý lọc cấp độ
if (!empty($filter_level)) {
    $query .= " AND r.level = ?";
    $params[] = $filter_level;
}

// Xử lý sắp xếp
$order = ($sort_order === 'ASC') ? 'ASC' : 'DESC';
$query .= " ORDER BY r.created_at " . $order;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php'; 
?>

<style>
    .reading-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 10px;
    }
    .reading-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        padding: 25px;
        transition: all 0.3s ease;
        border-top: 5px solid #3498db;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
    }
    .reading-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .level-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
        color: white;
        margin-bottom: 15px;
    }
    .level-A1, .level-A2 { background: #2ecc71; } /* Xanh lá */
    .level-B1, .level-B2 { background: #f39c12; } /* Cam */
    .level-C1, .level-C2 { background: #e74c3c; } /* Đỏ */
    
    .reading-title {
        font-size: 1.25rem;
        color: #2c3e50;
        margin: 0 0 10px 0;
        font-weight: bold;
        line-height: 1.4;
    }
    .reading-topic {
        color: #7f8c8d;
        font-size: 0.95rem;
        margin-bottom: 20px;
        flex-grow: 1;
    }
    .btn-read {
        display: block;
        text-align: center;
        background: #3498db;
        color: white;
        padding: 10px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
        transition: 0.2s;
    }
    .btn-read:hover {
        background: #2980b9;
        color: white;
    }
    .empty-state {
        text-align: center;
        padding: 50px;
        background: #f8f9fa;
        border-radius: 10px;
        color: #7f8c8d;
    }
    .filter-box {
        background: #fff;
        padding: 20px;
        border-radius: 0 12px 12px 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        border: 1px solid #eaeaea;
    }
    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .filter-select {
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #ccc;
        outline: none;
        min-width: 150px;
        font-family: inherit;
    }
    /* Style cho Tabs */
    .custom-tabs {
        display: flex;
        gap: 5px;
        margin-bottom: -1px;
    }
    .custom-tab {
        padding: 12px 25px;
        background: #ecf0f1;
        color: #7f8c8d;
        text-decoration: none;
        font-weight: bold;
        border-radius: 10px 10px 0 0;
        border: 1px solid #eaeaea;
        border-bottom: none;
        transition: 0.3s;
    }
    .custom-tab.active {
        background: #fff;
        color: #3498db;
        border-top: 3px solid #3498db;
    }
    .custom-tab:hover:not(.active) {
        background: #e0e6ed;
    }
</style>

<div >
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ecf0f1; padding-bottom: 15px; margin-bottom: 25px;">
        <h2 style="margin: 0; color: var(--primary-color);"><i class="fas fa-layer-group"></i> Thư viện bài đọc (Reading)</h2>
        <span style="background: #ecf0f1; color: #7f8c8d; padding: 5px 15px; border-radius: 20px; font-weight: bold;">
            Hiển thị: <?= count($readings) ?> bài
        </span>
    </div>

    <div class="custom-tabs">
        <a href="?source=system" class="custom-tab <?= $filter_source === 'system' ? 'active' : '' ?>">
            <i class="fas fa-globe"></i> Bài đọc Hệ thống
        </a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="?source=mine" class="custom-tab <?= $filter_source === 'mine' ? 'active' : '' ?>">
                <i class="fas fa-user-edit"></i> Bài đọc Của tôi
            </a>
        <?php endif; ?>
    </div>

    <div class="filter-box">
        <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            
            <input type="hidden" name="source" value="<?= htmlspecialchars($filter_source) ?>">

            <div class="filter-group">
                <label style="font-weight: bold; color: #2c3e50;"><i class="fas fa-signal"></i> Cấp độ:</label>
                <select name="level" class="filter-select">
                    <option value="">-- Tất cả --</option>
                    <option value="A1" <?= $filter_level == 'A1' ? 'selected' : '' ?>>A1 - Cơ bản</option>
                    <option value="A2" <?= $filter_level == 'A2' ? 'selected' : '' ?>>A2 - Sơ cấp</option>
                    <option value="B1" <?= $filter_level == 'B1' ? 'selected' : '' ?>>B1 - Trung cấp</option>
                    <option value="B2" <?= $filter_level == 'B2' ? 'selected' : '' ?>>B2 - Trung cao cấp</option>
                    <option value="C1" <?= $filter_level == 'C1' ? 'selected' : '' ?>>C1 - Nâng cao</option>
                </select>
            </div>

            <div class="filter-group">
                <label style="font-weight: bold; color: #2c3e50;"><i class="fas fa-sort"></i> Sắp xếp:</label>
                <select name="sort" class="filter-select">
                    <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>Cũ nhất</option>
                </select>
            </div>

            <button type="submit" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s;">
                <i class="fas fa-search"></i> Lọc
            </button>

            <?php if (!empty($filter_level) || !empty($filter_topic) || $sort_order === 'ASC'): ?>
                <a href="?source=<?= htmlspecialchars($filter_source) ?>" style="color: #e74c3c; text-decoration: none; font-weight: bold; padding: 10px; border-radius: 6px; background: #fdf2f0;">
                    <i class="fas fa-times"></i> Xóa lọc
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (count($readings) > 0): ?>
        <div class="reading-grid">
            <?php foreach ($readings as $r): 
                $level_code = substr($r['level'], 0, 2); 
            ?>
                <div class="reading-card">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <span class="level-badge level-<?= $level_code ?>" style="margin-bottom: 0;">
                                <i class="fas fa-signal"></i> <?= htmlspecialchars($r['level']) ?>
                            </span>
                            
                            <?php if($filter_source === 'mine'): ?>
                                <span style="font-size: 0.8rem; color: #8e44ad; font-weight: bold; background: #f4ecf7; padding: 4px 8px; border-radius: 4px;">
                                    <i class="fas fa-user"></i> Của tôi
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="reading-title"><?= htmlspecialchars($r['title']) ?></h3>
                        
                        <div class="reading-topic">
                            <i class="fas fa-hashtag"></i> Chủ đề: 
                            <b><?= $r['topic_name'] ? htmlspecialchars($r['topic_name']) : 'Tự chọn' ?></b>
                        </div>
                    </div>
                    
                    <a href="reading.php?id=<?= $r['id'] ?>" class="btn-read">
                        <i class="fas fa-book-open"></i> Vào học ngay
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-folder-open" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 15px;"></i>
            <?php if($filter_source === 'mine'): ?>
                <h3>Bạn chưa tạo bài đọc nào</h3>
                <p>Hãy sử dụng AI để tạo các bài đọc cá nhân hóa theo sở thích của bạn nhé!</p>
                <a href="create_reading.php" style="display: inline-block; margin-top: 15px; background: #8e44ad; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold;">
                    Tạo bài đọc ngay
                </a>
            <?php else: ?>
                <h3>Không tìm thấy bài đọc</h3>
                <p>Không có bài đọc nào phù hợp với bộ lọc hiện tại. Vui lòng thử lại!</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>