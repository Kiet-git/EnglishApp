<?php
require 'config/db.php';
include 'includes/header.php';

$keyword = $_GET['q'] ?? '';
?>
<style>
    .btn-audio {
        background: #f0f4f8;
        color: var(--primary-color, #3498db);
        border: none;
        font-size: 1.3rem;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        cursor: pointer;
        margin-top: 15px;
        transition: 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .btn-audio:hover {
        background: var(--primary-color, #3498db);
        color: #fff;
        transform: scale(1.1);
    }
</style>
<div >
    <h2>Kết quả tìm kiếm cho: "<?= htmlspecialchars($keyword) ?>"</h2>
    
    <ul style="list-style: none; padding: 0;">
    <?php
    if ($keyword) {
        // --- SỬA CÂU SQL ---
        // Dùng JOIN để nối bảng vocabularies (v) với bảng topics (t)
        // Lấy tất cả thông tin từ vựng (v.*) và lấy thêm topic_name từ bảng t
        $sql = "SELECT v.*, t.topic_name 
                FROM vocabularies v
                JOIN topics t ON v.topic_id = t.topic_id
                WHERE v.word LIKE ? OR v.meaning LIKE ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(["%$keyword%", "%$keyword%"]);
        $results = $stmt->fetchAll();

        if(count($results) > 0){
            foreach($results as $r) {
                ?>
                <li style="background: #fff; padding: 15px; margin-bottom: 10px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <div style="font-size: 1.2rem; color: var(--primary-color);">
                        <b><?= $r['word'] ?></b>
                        <span style="color: #555; font-size: 1rem;"> - <?= $r['meaning'] ?></span>
                        <button class="btn-audio"
                            onclick="playAudio(event, 
                            '<?= addslashes($r['word']) ?>', 
                            '<?= isset($r['audio_url']) ? addslashes($r['audio_url']) : '' ?>')"
                            title="Nghe phát âm">
                            <i class="fas fa-volume-up"></i>
                        </button>
                    </div>
                    
                    <div style="margin-top: 5px; font-size: 0.9rem; color: #888;">
                        📂 Chủ đề: 
                        <a href="modules/topics/topics_vocab.php?topic_id=<?= $r['topic_id'] ?>" style="font-weight: bold; text-decoration: none;">
                            <?= $r['topic_name'] ?>
                        </a>
                    </div>
                    
                    <?php if($r['pronunciation']): ?>
                        <div style="font-style: italic; color: #666; font-size: 0.85rem;">
                            Phiên âm: /<?= $r['pronunciation'] ?>/
                        </div>
                    <?php endif; ?>
                </li>
                <?php
            }
        } else {
            echo "<p>Không tìm thấy từ vựng nào khớp với từ khóa.</p>";
        }
    }
    ?>
    </ul>
    
    <a href="index.php" class="btn" style="background: #777;">Quay lại Trang chủ</a>
</div>

<script>
    /**
     * Hàm phát âm thanh thông minh
     * @param {Event} event - Bắt sự kiện click
     * @param {string} word - Từ vựng cần đọc
     * @param {string} audioUrl - Link file audio (nếu có)
     */
    function playAudio(event, word, audioUrl) {
        // NGĂN CHẶN thẻ bị lật khi người dùng bấm vào nút Loa
        event.stopPropagation();

        if (audioUrl && audioUrl.trim() !== '') {
            // CÁCH 1: Nếu có file âm thanh do Admin upload
            let audio = new Audio(audioUrl);
            audio.play().catch(e => console.log('Lỗi phát file MP3:', e));
        } else {
            // CÁCH 2: TỐI ƯU AI (Text-to-Speech)
            // Nếu không có file mp3, tự động dùng AI của trình duyệt đọc từ vựng
            if ('speechSynthesis' in window) {
                let speech = new SpeechSynthesisUtterance(word);
                speech.lang = 'en-US'; // Giọng Mỹ chuẩn
                speech.rate = 0.9;     // Tốc độ chậm lại một chút để dễ nghe
                window.speechSynthesis.speak(speech);
            } else {
                alert('Trình duyệt của bạn không hỗ trợ tính năng phát âm mặc định.');
            }
        }
    }
</script>

<?php include 'includes/footer.php'; ?>