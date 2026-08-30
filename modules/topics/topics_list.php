<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../../config/db.php';
include '../../includes/header.php';

// --- XỬ LÝ LỌC (FILTER) ---
// Lấy giá trị filter từ URL, mặc định là 'newest'
$filter = $_GET['filter'] ?? 'trending'; 

// Xác định câu lệnh ORDER BY dựa trên filter
if ($filter === 'trending') {
    $orderBy = "views DESC"; // Sắp xếp theo lượt xem giảm dần
} elseif ($filter === 'oldest') {
    $orderBy = "topic_id ASC"; // Sắp xếp theo ID tăng dần (Cũ nhất)
} else {
    $orderBy = "topic_id DESC"; // Mặc định: Sắp xếp theo ID giảm dần (Mới nhất)
}

// Lấy danh sách chủ đề
$sql = "SELECT * FROM topics ORDER BY $orderBy";
$stmt = $conn->query($sql);
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* CSS cho các nút lọc */
    .active-filter { background: #3498db; color: white; border: 1px solid #3498db; box-shadow: 0 2px 5px rgba(52, 152, 219, 0.3); }
    .inactive-filter { background: #f9f9f9; color: #555; border: 1px solid #ddd; }
    .inactive-filter:hover { background: #eee; }
</style>

<div>
    <h2 style="color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <i class="fas fa-list-ul"></i> Danh sách Chủ đề Từ vựng
    </h2>
    
    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="?filter=trending" class="btn <?= $filter == 'trending' ? 'active-filter' : 'inactive-filter' ?>" style="padding: 8px 15px; border-radius: 5px; text-decoration: none;">
            <i class="fas fa-fire" style="<?= $filter == 'trending' ? 'color: #ffce56;' : 'color: #e74c3c;' ?>"></i> Thịnh hành
        </a>
        <a href="?filter=newest" class="btn <?= $filter == 'newest' ? 'active-filter' : 'inactive-filter' ?>" style="padding: 8px 15px; border-radius: 5px; text-decoration: none;">
            <i class="fas fa-sort-amount-down"></i> Mới nhất
        </a>
        <a href="?filter=oldest" class="btn <?= $filter == 'oldest' ? 'active-filter' : 'inactive-filter' ?>" style="padding: 8px 15px; border-radius: 5px; text-decoration: none;">
            <i class="fas fa-sort-amount-up"></i> Cũ nhất
        </a>
    </div>

    <div class="topic-grid" style=" gap: 20px;">
        <?php foreach($topics as $t): ?>
        <div class="topic-card" style="display: flex; flex-direction: column; height: 100%; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee;">
            
            <div style="height: 150px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f9f9f9; position: relative;">
                <?php if (!empty($t['image'])): ?>
                    <img src="../../uploads/<?= htmlspecialchars($t['image']) ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;">
                <?php else: ?>
                    <span style="color: #ccc; font-size: 40px;"><i class="fas fa-images"></i></span>
                <?php endif; ?>

                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); color: white; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: bold;">
                    <i class="fas fa-eye"></i> <?= number_format($t['views'] ?? 0) ?>
                </div>
            </div>
            
            <div style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1;">
                <h3 style="margin-top: 0; color: #2c3e50; font-size: 1.2rem;"><?= htmlspecialchars($t['topic_name']) ?></h3>
                <p style="color: #7f8c8d; font-size: 0.95rem; line-height: 1.5; margin-bottom: 20px; flex-grow: 1;">
                    <?= htmlspecialchars($t['description']) ?>
                </p>
                
                <a href="topics_vocab.php?topic_id=<?= $t['topic_id'] ?>" class="btn" style="margin-top: auto; display: block; text-align: center; background: #3498db; color: white; padding: 10px; border-radius: 6px; text-decoration: none;">
                    <i class="fas fa-play-circle"></i> Học ngay
                </a>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>