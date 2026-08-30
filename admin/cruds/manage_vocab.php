<?php
require '../../config/db.php';
require '../../includes/pagination_helper.php'; 


// Kiểm tra tham số topic_id
if (!isset($_GET['topic_id'])) {
    echo "<div class='container'><p style='color:red; text-align:center; margin-top:50px;'>Không tìm thấy chủ đề!</p></div>";
    include '../../includes/footer.php';
    exit;
}
$topic_id = $_GET['topic_id'];

// Lấy thông tin Topic để hiện tên
$topic = $conn->prepare("SELECT * FROM topics WHERE topic_id = ?");
$topic->execute([$topic_id]);
$currentTopic = $topic->fetch();

if (!$currentTopic) {
    echo "<div class='container'><p style='color:red'>Chủ đề không tồn tại.</p></div>";
    exit;
}

// --- XỬ LÝ THÊM TỪ VỰNG ---
if (isset($_POST['add_vocab'])) {
    $word = trim($_POST['word']);
    $word_type = trim($_POST['word_type']);
    $mean = trim($_POST['meaning']);
    $pron = trim($_POST['pronunciation']);

    if (!empty($word) && !empty($mean)) {
        $stmt = $conn->prepare("INSERT INTO vocabularies (topic_id, word, word_type, meaning, pronunciation) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$topic_id, $word, $word_type, $mean, $pron]);
        
        // Quan trọng: Chuyển hướng để tránh gửi lại form khi F5
        header("Location: manage_vocab.php?topic_id=$topic_id");
        exit;
    }
}

// --- XỬ LÝ XÓA TỪ ---
if (isset($_GET['del_vocab'])) {
    $id = $_GET['del_vocab'];
    $stmt = $conn->prepare("DELETE FROM vocabularies WHERE vocab_id = ?");
    $stmt->execute([$id]);
    
    header("Location: manage_vocab.php?topic_id=$topic_id");
    exit;
}

//SEARCH & PHAN TRANG
// 1. XỬ LÝ TÌM KIẾM
$search = trim($_GET['search'] ?? '');
$search_sql = "";

// Luôn luôn có topic_id là tham số đầu tiên
$params = [$topic_id]; 

if ($search !== '') {
    // Dùng AND vì đằng trước đã có WHERE topic_id = ?
    $search_sql = " AND (word LIKE ? OR meaning LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// 2. PHÂN TRANG & LẤY DỮ LIỆU
// Nối đoạn tìm kiếm vào đuôi câu SQL
$sql_count = "SELECT COUNT(*) FROM vocabularies WHERE topic_id = ? $search_sql";
$sql_data  = "SELECT * FROM vocabularies WHERE topic_id = ? $search_sql ORDER BY vocab_id DESC";

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // 20 từ vựng mỗi trang

// 3. Gọi hàm helper (Truyền mảng $params đã chuẩn bị sẵn vào)
$paging_result = getPagingData($conn, $sql_count, $sql_data, $params, $limit, $page);

$vocabs = $paging_result['data'];
$total_pages = $paging_result['total_pages'];
$current_page = $paging_result['current_page'];
include '../../includes/header.php';
?>

<div >
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin-bottom: 5px; color: var(--primary-color);">Quản lý Từ vựng</h2>
        <a href="manage_topics.php" class="btn" style="background: #777;">&larr; Quay lại DS Chủ đề</a>
    </div>
    <p style="color: #666; margin: 0;">Chủ đề: <b style="color: var(--primary-color); font-size: 1.1rem;"><?= htmlspecialchars($currentTopic['topic_name']) ?></b></p>

    <form method="GET" style="margin-top: 30px;margin-bottom:30px ;display: flex; gap: 10px;">
            <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
            <input required type="text" name="search" placeholder="Nhập từ tiếng Anh hoặc nghĩa tiếng Việt..." value="<?= htmlspecialchars($search) ?>" style="width: 100%;margin: 0;padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            <button type="submit" class="btn" style="background: var(--primary-color);width:200px;padding:0"><i class="fas fa-search"></i> Tìm kiếm</button>
            <?php if($search !== ''): ?>
                <a href="manage_vocab.php?topic_id=<?= $topic_id ?>" class="btn" style="background: #95a5a6;align-content: center;width:100px;"><i class="fas fa-times"></i> Hủy</a>
            <?php endif; ?>
    </form>

    <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <h4 style="margin-top: 0; color: var(--primary-color); border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
            <i class="fas fa-plus-circle"></i> Thêm từ vựng mới
        </h4>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="font-weight: bold; font-size: 0.9rem;">Tiếng Anh <span style="color:red">*</span></label>
                    <input type="text" name="word" placeholder="VD: Apple" required style="width: 100%; margin-top: 5px;">
                </div>
                
                <div>
                    <label style="font-weight: bold; font-size: 0.9rem;">Loại Từ</label>
                    <input type="text" name="word_type" placeholder="VD: Động từ (verb)" style="width: 100%; margin-top: 5px;">
                </div>

                <div>
                    <label style="font-weight: bold; font-size: 0.9rem;">Phiên âm (IPA)</label>
                    <input type="text" name="pronunciation" placeholder="VD: ˈæp.əl" style="width: 100%; margin-top: 5px;">
                </div>

                <div>
                    <label style="font-weight: bold; font-size: 0.9rem;">Nghĩa Tiếng Việt <span style="color:red">*</span></label>
                    <input type="text" name="meaning" placeholder="VD: Quả táo" required style="width: 100%; margin-top: 5px;">
                </div>
            </div>

            <div style="margin-top: 20px; text-align: right;">
                <button type="submit" name="add_vocab" class="btn" style="padding: 10px 30px; background:var(--success-color)">
                    <i class="fas fa-save"></i> Lưu từ vựng
                </button>
            </div>
        </form>
    </div>

    <div style="background: #fff; padding: 10px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 20%;">Tiếng Anh</th>
                    <th style="width: 15%;">Loại Từ</th>
                    <th style="width: 20%;">Phiên âm</th>
                    <th style="width: 25%;">Nghĩa Tiếng Việt</th>
                    <th style="width: 20%; text-align: center;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($vocabs) == 0): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px; color: #888;">
                            Chưa có từ vựng nào trong chủ đề này. Hãy thêm từ mới!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vocabs as $v): ?>
                    <tr>
                        <td>
                            <b style="color: var(--primary-color); font-size: 1.1rem;"><?= htmlspecialchars($v['word']) ?></b>
                        </td>
                        <td>
                            <?= htmlspecialchars($v['word_type']) ?>
                        </td>
                        <td style="font-family: 'Lucida Sans Unicode', 'Arial Unicode MS', sans-serif; color: #555;">
                            <?= $v['pronunciation'] ? "/{$v['pronunciation']}/" : '' ?>
                        </td>
                        <td><?= htmlspecialchars($v['meaning']) ?></td>
                        <td style="text-align: center;">
                            <a href="edit_vocab.php?id=<?= $v['vocab_id'] ?>" 
                               class="btn btn-warning" 
                               style="padding: 6px 12px; font-size: 0.85rem;" 
                               title="Sửa">
                               <i class="fas fa-edit"></i>
                            </a>
                            
                            <a href="manage_vocab.php?topic_id=<?= $topic_id ?>&del_vocab=<?= $v['vocab_id'] ?>" 
                               class="btn btn-danger" 
                               style="padding: 6px 12px; font-size: 0.85rem;"
                               onclick="return confirm('Bạn chắc chắn muốn xóa từ này?')"
                               title="Xóa">
                               <i class="fas fa-trash"></i>
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

<?php include '../../includes/footer.php'; ?>