<?php
require '../../config/db.php';
include '../../includes/header.php';

if (!isset($_GET['topic_id'])) die("Chưa chọn chủ đề");
$topic_id = $_GET['topic_id'];


// Tự động cộng 1 lượt xem cho chủ đề (Chỉ cộng khi người xem KHÔNG PHẢI là admin)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $stmtUpdateTopicView = $conn->prepare("UPDATE topics SET views = views + 1 WHERE topic_id = ?");
    $stmtUpdateTopicView->execute([$topic_id]);
}


// Lấy từ vựng
$stmt = $conn->prepare("SELECT * FROM vocabularies WHERE topic_id = ?");
$stmt->execute([$topic_id]);
$vocabs = $stmt->fetchAll();

// Get Topic
$topic = $conn->prepare("SELECT * FROM topics WHERE topic_id = ?");
$topic->execute([$topic_id]);
$currentTopic = $topic->fetch();

if (!$currentTopic) {
    die("Chủ đề không tồn tại!");
}
?>

<style>
    .flashcard-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
        margin-top: 20px;
    }

    .flip-card {
        background-color: transparent;
        width: 300px;
        height: 200px;
        perspective: 1000px; /* Hiệu ứng 3D */
        cursor: pointer;
    }

    .flip-card-inner {
        position: relative;
        width: 100%;
        height: 100%;
        text-align: center;
        transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1); /* Lật mượt hơn */
        transform-style: preserve-3d;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        border-radius: 15px;
    }

    /* --- CHẾ ĐỘ LẬT THẺ --- */
    
    /* 1. Khi bật "Chỉ Di chuột": Ưu tiên lật bằng Hover */
    body.hover-active .flip-card:hover .flip-card-inner {
        transform: rotateY(180deg) !important;
    }

    /* 2. Khi bật "Chỉ Click": ÉP KHÓA HOVER (Chặn đứng mọi CSS lật thẻ từ các file bên ngoài) */
    body:not(.hover-active) .flip-card:hover .flip-card-inner {
        transform: rotateY(0deg); 
    }

    /* 3. Lệnh lật thẻ bằng Click (Chỉ chạy khi thẻ được gắn class .is-flipped qua JS) */
    .flip-card-inner.is-flipped {
        transform: rotateY(180deg) !important;
    }

    /* --- GIAO DIỆN NÚT GẠT (SWITCH) --- */
    .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; }
    input:checked + .slider { background-color: #27ae60; }
    input:checked + .slider:before { transform: translateX(26px); }
    .slider.round { border-radius: 24px; background: var(--primary-color);}
    .slider.round:before { border-radius: 50%; 
    .slider.round { border-radius: 24px; }
    .slider.round:before { border-radius: 50%; }

    /* THÊM ĐOẠN NÀY ĐỂ BÁO HIỆU NÚT BỊ KHÓA TRÊN MOBILE */
    .switch input:disabled + .slider {
        opacity: 0.5;
        cursor: not-allowed;
    }}

    .flip-card-front, .flip-card-back {
        position: absolute;
        width: 100%;
        height: 100%;
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        border-radius: 15px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 20px;
        box-sizing: border-box;
    }

    /* Mặt trước: Tiếng Anh */
    .flip-card-front {
        background-color: #ffffff;
        color: #333;
        border: 2px solid var(--primary-color, #3498db);
    }

    /* Mặt sau: Tiếng Việt */
    .flip-card-back {
        background-color: var(--primary-color, #3498db);
        color: white;
        transform: rotateY(180deg);
    }

    /* Style cho Nút âm thanh tối ưu */
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
    
    .instruction-text {
        text-align: center;
        color: #666;
        margin-bottom: 20px;
    }
</style>

<div >
    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px;">
        <h2 style="color: var(--primary-color); margin: 0;"><i class="fas fa-layer-group"></i> Học Từ Vựng</h2>

        <div style="text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px; background: #f8f9fa; padding: 10px 15px; border-radius: 30px; border: 1px solid #eee;">
            <span id="modeLabelHover" style="font-weight: bold; color: var(--primary-color);">Hover</span>
            <label class="switch">
                <input type="checkbox" id="modeToggle" onchange="changeMode()">
                <span class="slider round"></span>
            </label>
            <span id="modeLabelClick" style="color: #999;">Click</span>
        </div>

    </div>

    <p style="color: #666; margin: 0;border-bottom: 2px solid #eee;"> Chủ đề: <b style="color: var(--primary-color); font-size: 1.1rem;"><?= htmlspecialchars($currentTopic['topic_name']) ?></b></p>

    <p class="instruction-text">
        <i class="fas fa-lightbulb" style="color: #f1c40f;"></i> 
        Di chuột hoặc <b>Click</b> vào thẻ để xem nghĩa. Bấm icon loa để nghe phát âm.
    </p>

    <!-- Voice Option -->
    <div style="display:flex; gap:15px; align-items:center; flex-wrap:wrap; margin-top:10px;">
        <!-- Accent -->
        <div>
            <label>Accent:</label>
            <select id="voiceAccent" style="padding:5px 10px; border-radius:20px;">
                <option value="en-US">US</option>
                <option value="en-GB">UK</option>
            </select>
        </div>

        <!-- Gender -->
        <div>
            <label>Giọng:</label>
            <select id="voiceGender" style="padding:5px 10px; border-radius:20px;">
                <option value="female">Nữ</option>
                <option value="male">Nam</option>
            </select>
        </div>

        <!-- Speed -->
        <div>
            <label>Tốc độ:</label>
            <select id="voiceSpeed" style="padding:5px 10px; border-radius:20px;">
                <option value="0.5">Slow</option>
                <option value="1" selected>Normal</option>
                <option value="1.5">Fast</option>
            </select>
        </div>
    </div>

    <div class="flashcard-container">
        <?php if(count($vocabs) == 0): ?>
            <div style="text-align:center; padding: 50px; width: 100%;">
                <p>Chủ đề này hiện chưa có từ vựng nào.</p>
            </div>
        <?php endif; ?>
        
        <?php foreach($vocabs as $v): ?>
        <div class="flip-card" onclick="toggleCard(this)">
            <div class="flip-card-inner">
                
                <div class="flip-card-front">
                    <h2 style="margin: 0; font-size: 2.2rem; color: var(--primary-color, #3498db);">
                        <?= htmlspecialchars($v['word']) ?>
                    </h2>

                    <?php if(!empty($v['word_type'])): ?>
                        <span style="background: #e8f8f5; color: #27ae60; padding: 3px 12px; border-radius: 15px; font-size: 0.85rem; font-weight: bold; border: 1px solid #c8e6c9;">
                            <?= htmlspecialchars($v['word_type']) ?>
                        </span>
                    <?php endif; ?>
                    
                    <small style="color: #888; margin-top: 5px; font-size: 1rem;">
                       US: <?= htmlspecialchars($v['pronunciation'] ?? '...') ?>
                        <!-- Có thể có thêm thông tin về phát âm -->
                    </small>
                </div>

                <div class="flip-card-back">
                    <h3 style="margin: 0; font-size: 1.8rem;"><?= htmlspecialchars($v['meaning']) ?></h3>
                    
                    <?php if(!empty($v['example'])): ?>
                        <p style="margin-top: 15px; font-style: italic; font-size: 0.95rem; opacity: 0.9;">
                            VD: "<?= htmlspecialchars($v['example']) ?>"
                        </p>
                    <?php endif; ?>
                    <button class="btn-audio" onclick="playAudio(event, '<?= addslashes($v['word']) ?>', '<?= isset($v['audio_url']) ? addslashes($v['audio_url']) : '' ?>')" title="Nghe phát âm">
                        <i class="fas fa-volume-up"></i>
                    </button>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    let availableVoices = [];

    function loadVoices() {
        availableVoices = window.speechSynthesis.getVoices();
    }

    loadVoices();

    if ('speechSynthesis' in window) {
        window.speechSynthesis.onvoiceschanged = loadVoices;
    }
    /**
     * Hàm phát âm thanh thông minh
     * @param {Event} event - Bắt sự kiện click
     * @param {string} word - Từ vựng cần đọc
     * @param {string} audioUrl - Link file audio (nếu có)
     */
    function playAudio(event, word, audioUrl) {
        event.stopPropagation();

        // Ưu tiên file mp3 nếu có
        if (audioUrl && audioUrl.trim() !== '') {
            let audio = new Audio(audioUrl);
            audio.play().catch(e => console.log('Lỗi MP3:', e));
            return;
        }

        if (!('speechSynthesis' in window)) {
            alert('Trình duyệt không hỗ trợ phát âm.');
            return;
        }

        // Ngăn chồng âm
        window.speechSynthesis.cancel();

        let speech = new SpeechSynthesisUtterance(word);

        let accent = document.getElementById('voiceAccent')?.value || 'en-US';
        let gender = document.getElementById('voiceGender')?.value || 'female';
        let speed = parseFloat(document.getElementById('voiceSpeed')?.value || 1);

        speech.lang = accent;
        speech.rate = speed;
        speech.pitch = 1;

        // Lọc voice theo accent
        let filteredVoices = availableVoices.filter(v => v.lang === accent);

        let selectedVoice = null;

        if (gender === 'female') {
            selectedVoice = filteredVoices.find(v =>
                v.name.toLowerCase().includes('female') ||
                v.name.includes('Samantha') ||
                v.name.includes('Zira') ||
                v.name.includes('Google UK English Female') ||
                v.name.includes('Google US English')
            );
        } else {
            selectedVoice = filteredVoices.find(v =>
                v.name.toLowerCase().includes('male') ||
                v.name.includes('David') ||
                v.name.includes('Mark')
            );
        }

        // Fallback nếu không có đúng giới tính
        if (!selectedVoice && filteredVoices.length > 0) {
            selectedVoice = filteredVoices[0];
        }

        if (selectedVoice) {
            speech.voice = selectedVoice;
        }

        window.speechSynthesis.speak(speech);
    }
    const modeToggle = document.getElementById('modeToggle');
    const labelHover = document.getElementById('modeLabelHover');
    const labelClick = document.getElementById('modeLabelClick');

    // Nhận diện Mobile (Màn hình < 768px hoặc thiết bị di động)
    const isMobile = window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    function initMode() {
        if (isMobile) {
            // Mobile: ÉP SANG CHẾ ĐỘ CLICK
            modeToggle.checked = true;
            modeToggle.disabled = true; // Khóa luôn nút gạt, không cho tắt
            document.body.classList.remove('hover-active');
        } else {
            // Desktop: MẶC ĐỊNH LÀ DI CHUỘT
            modeToggle.checked = false;
            document.body.classList.add('hover-active');
        }
        updateLabels();
    }

    function changeMode() {
        if (modeToggle.checked) {
            // BẬT SWITCH -> Chế độ Click
            document.body.classList.remove('hover-active');
        } else {
            // TẮT SWITCH -> Chế độ Di chuột
            document.body.classList.add('hover-active');
            // Thu gọn lại tất cả các thẻ đang lật dở
            document.querySelectorAll('.flip-card-inner').forEach(card => card.classList.remove('is-flipped'));
        }
        updateLabels();
    }

    function updateLabels() {
        if (modeToggle.checked) {
            labelHover.style.color = '#999'; labelHover.style.fontWeight = 'normal';
            labelClick.style.color = '#27ae60'; labelClick.style.fontWeight = 'bold';
        } else {
            labelHover.style.color = 'var(--primary-color, #3498db)'; labelHover.style.fontWeight = 'bold';
            labelClick.style.color = '#999'; labelClick.style.fontWeight = 'normal';
        }
    }

    // Hàm xử lý khi bấm vào thẻ
    function toggleCard(cardElement) {
        // Chỉ lật bằng Click nếu đang KHÔNG ở chế độ di chuột
        if (!document.body.classList.contains('hover-active')) {
            const inner = cardElement.querySelector('.flip-card-inner');
            inner.classList.toggle('is-flipped');
        }
    }

    // Chạy thuật toán cài đặt ban đầu khi load trang
    initMode();
</script>

<?php include '../../includes/footer.php'; ?>