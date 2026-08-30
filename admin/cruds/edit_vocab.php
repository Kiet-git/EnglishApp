<?php
require '../../config/db.php';
include '../../includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../../auth/login.php"); exit; }

$id = $_GET['id'] ?? 0;

// Lấy thông tin từ vựng hiện tại
$stmt = $conn->prepare("SELECT * FROM vocabularies WHERE vocab_id = ?");
$stmt->execute([$id]);
$vocab = $stmt->fetch();

if (!$vocab) { header("Location: manage_topics.php"); exit; }

// Lấy danh sách tất cả chủ đề (để hiển thị dropdown chọn chủ đề)
$topics = $conn->query("SELECT * FROM topics")->fetchAll();

// Xử lý Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $word = $_POST['word'];
    $word_type = $_POST['word_type'];
    $mean = $_POST['meaning'];
    $pron = $_POST['pronunciation'];
    $topic_id = $_POST['topic_id'];
    
    $sql = "UPDATE vocabularies SET word=?, word_type=?, meaning=?, pronunciation=?, topic_id=? WHERE vocab_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$word, $word_type, $mean, $pron, $topic_id, $id]);
    
    // Quay lại đúng cái trang chủ đề của từ vựng đó
    echo "<script>alert('Đã cập nhật!'); window.location.href='manage_vocab.php?topic_id=$topic_id';</script>";
}
?>

<div >
    <h2>Sửa Từ vựng</h2>
    <a href="manage_vocab.php?topic_id=<?= $vocab['topic_id'] ?>" class="btn" style="background:#777; margin-bottom:20px;">Quay lại</a>

    <div class="auth-box" style="max-width: 600px; margin: 0 auto; text-align: left;">
        <form method="POST">
            <label style="font-weight:bold;">Từ tiếng Anh:</label>
            <input type="text" name="word" value="<?= htmlspecialchars($vocab['word']) ?>" required>

            <label style="font-weight:bold;">Loại Từ:</label>
            <input type="text" name="word_type" value="<?= htmlspecialchars($vocab['word_type']) ?>">
            
            <label style="font-weight:bold;">Phiên âm:</label>
            <input type="text" name="pronunciation" value="<?= htmlspecialchars($vocab['pronunciation']) ?>">

            <label style="font-weight:bold;">Nghĩa tiếng Việt:</label>
            <input type="text" name="meaning" value="<?= htmlspecialchars($vocab['meaning']) ?>" required>
            
            <label style="font-weight:bold;">Thuộc chủ đề:</label>
            <select name="topic_id" style="width: 100%; padding: 10px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #ccc;">
                <?php foreach($topics as $t): ?>
                    <option value="<?= $t['topic_id'] ?>" <?= ($t['topic_id'] == $vocab['topic_id']) ? 'selected' : '' ?>>
                        <?= $t['topic_name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-warning" style="width:100%">LƯU THAY ĐỔI</button>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>