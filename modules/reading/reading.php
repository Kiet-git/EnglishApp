<?php
// [SỬA LỖI ĐƯỜNG DẪN Ở ĐÂY] Thêm ../../ để lùi ra ngoài thư mục gốc
require '../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
include '../../includes/header.php';

// Lấy ID bài đọc
$reading_id = $_GET['id'] ?? 1;

// Lấy bài đọc
$stmt = $conn->prepare("SELECT * FROM readings WHERE id = ?");
$stmt->execute([$reading_id]);
$reading = $stmt->fetch();

if (!$reading) {
    echo "<div class='container' style='margin-top: 50px; text-align: center;'><h2>Không tìm thấy bài đọc.</h2><a href='../../index.php' class='btn'>Quay lại trang chủ</a></div>";
    include '../../includes/footer.php';
    exit;
}

// Tăng lượt xem (views) lên 1 mỗi khi load trang thành công
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $stmtUpdateView = $conn->prepare("UPDATE readings SET views = views + 1 WHERE id = ?");
    $stmtUpdateView->execute([$reading_id]);
}
// ===================================

$vocabs = json_decode($reading['vocab_data'], true) ?? [];
$quizzes = json_decode($reading['quiz_data'], true) ?? [];

// Lọc bỏ mã độc từ văn bản gốc để an toàn hơn
$content = htmlspecialchars($reading['content'], ENT_QUOTES, 'UTF-8');

// 1. HIGHLIGHT TỪ VỰNG DO AI TẠO (Chỉ highlight 1 lần)
$ai_words = []; // Mảng lưu các từ AI đã tạo để lát nữa không bị trùng
if (!empty($vocabs)) {
    foreach ($vocabs as $index => $v) {
        $word = $v['word'];
        $ai_words[] = strtolower($word);
        
        // Mã hóa JSON an toàn tuyệt đối
        $tooltip_data = htmlspecialchars(json_encode($v, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        
        // REGEX MỚI: (?![^<]*>) giúp BỎ QUA các từ nằm bên trong thuộc tính HTML
        $pattern = "/\b(" . preg_quote($word, '/') . ")\b(?![^<]*>)/i";
        
        $content = preg_replace($pattern, "<span class='vocab-highlight' data-info='$tooltip_data' onclick='showVocabInfo(this)'>$1</span>", $content, 1);
    }
}

// 2. TÌM VÀ HIGHLIGHT TỪ VỰNG TỪ CHỦ ĐỀ CÓ SẴN (Tối đa 2 từ)
if (!empty($reading['topic_id'])) {
    $stmtVocab = $conn->prepare("SELECT * FROM vocabularies WHERE topic_id = ?");
    $stmtVocab->execute([$reading['topic_id']]);
    $topic_vocabs = $stmtVocab->fetchAll(PDO::FETCH_ASSOC);

    $extra_count = 0;
    foreach ($topic_vocabs as $tv) {
        if ($extra_count >= 2) break; // Chỉ lấy tối đa 2 từ

        $word = $tv['word'];
        
        // Vẫn dùng REGEX MỚI ở đây
        if (preg_match("/\b" . preg_quote($word, '/') . "\b(?![^<]*>)/i", $content) && !in_array(strtolower($word), $ai_words)) {
            $tooltip_info = [
                'word' => $tv['word'],
                'type' => $tv['word_type'] ?? '', 
                'pronunciation' => $tv['pronunciation'] ?? '', 
                'meaning_vi' => $tv['meaning'] ?? '' 
            ];
            
            $tooltip_data = htmlspecialchars(json_encode($tooltip_info, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            $pattern = "/\b(" . preg_quote($word, '/') . ")\b(?![^<]*>)/i";
            
            $content = preg_replace($pattern, "<span class='vocab-highlight' style='border-bottom: 2px dashed #2ecc71;' data-info='$tooltip_data' onclick='showVocabInfo(this)'>$1</span>", $content, 1);
            $extra_count++;
        }
    }
}

// Phục hồi lại thẻ <br> sau khi htmlspecialchars đã lọc
$content = nl2br($content);
?>

<style>
    .reading-box { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); line-height: 1.8; font-size: 1.15rem; color: #2c3e50; }
    .vocab-highlight { background: #fff3cd; color: #d35400; font-weight: bold; padding: 2px 6px; border-radius: 4px; cursor: pointer; transition: 0.2s; border-bottom: 2px dashed #f39c12; }
    .vocab-highlight:hover { background: #ffeaa7; }
    .quiz-box { margin-top: 30px; border-top: 3px solid #ecf0f1; padding-top: 20px; }
    .option-label { display: block; background: #f8f9fa; padding: 12px 15px; border-radius: 5px; margin-bottom: 10px; cursor: pointer; border: 1px solid #ddd; transition: 0.2s; }
    .option-label:hover { background: #e8f8f5; }
    .correct { background: #d4efdf !important; border-color: #27ae60 !important; font-weight: bold; color: #27ae60; }
    .wrong { background: #f2d7d5 !important; border-color: #e74c3c !important; color: #c0392b; }
    #vocabPopup { display: none; position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); background: #2c3e50; color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 1000; min-width: 320px; max-width: 400px; text-align: left; border-top: 4px solid #f1c40f;}
</style>

<div  style="margin-top: 30px;">
    <h2><i class="fas fa-book-reader"></i> Luyện đọc: <?= htmlspecialchars($reading['title']) ?></h2>
    
    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <span style="background: #3498db; color: white; padding: 5px 12px; border-radius: 15px; font-size: 0.9rem; font-weight: bold;">Cấp độ: <?= htmlspecialchars($reading['level']) ?></span>
        
        <?php if(!empty($reading['user_id'])): ?>
            <span style="background: #8e44ad; color: white; padding: 5px 12px; border-radius: 15px; font-size: 0.9rem; font-weight: bold;"><i class="fas fa-user-edit"></i> Bài đọc User tự tạo</span>
        <?php endif; ?>
    </div>

    <div class="reading-box">
        <div id="english-content">
            <?= nl2br($content) ?>
        </div>

        <div id="vietnamese-content" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #bdc3c7; color: #27ae60;">
            <h4 style="margin-top:0;"><i class="fas fa-language"></i> Bản dịch:</h4>
            <div id="vi-text-container">
                <?php if (!empty($reading['content_vi'])): ?>
                    <?= nl2br(htmlspecialchars($reading['content_vi'])) ?>
                <?php else: ?>
                    <span id="auto-translate-status" style="color: #f39c12;">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <button onclick="toggleTranslation()" id="btn-translate" style="margin-top: 20px; background: #f39c12; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: bold;">
            <i class="fas fa-eye"></i> Xem bản dịch
        </button>
    </div>

    <div class="quiz-box">
        <h3><i class="fas fa-tasks"></i> Bài tập kiểm tra</h3>
        <?php if(!empty($quizzes)): ?>
            <?php foreach ($quizzes as $qIndex => $q): ?>
                <div class="question-block" style="margin-bottom: 25px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <p style="font-weight: bold; font-size: 1.1rem; color: #2c3e50;">Câu <?= $qIndex + 1 ?>: <?= htmlspecialchars($q['question']) ?></p>
                    <?php foreach (['A', 'B', 'C', 'D'] as $key): ?>
                        <?php if (isset($q['options'][$key]) && trim($q['options'][$key]) !== ''): ?>
                            <label class="option-label" data-ans="<?= $q['answer'] ?>" onclick="checkAnswer(this, '<?= $key ?>')">
                                <b><?= $key ?>.</b> <?= htmlspecialchars($q['options'][$key]) ?>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Không có câu hỏi cho bài đọc này.</p>
        <?php endif; ?>
    </div>
</div>

<div id="vocabPopup">
    <div style="text-align: center; margin-bottom: 15px;">
        <h3 id="vp-word" style="margin: 0; color: #f1c40f; font-size: 1.8rem;">Word</h3>
        <p style="margin: 8px 0 5px 0; font-size: 1rem;">
            <span id="vp-type" style="background: #e74c3c; padding: 3px 8px; border-radius: 4px; font-size: 0.9rem; font-weight: bold;">n</span>
            <span id="vp-pron" style="font-style: italic; color: #bdc3c7; margin-left: 10px;">/wɜːrd/</span>
        </p>
        <p id="vp-mean-vi" style="margin: 10px 0 0 0; font-size: 1.2rem; color: #2ecc71; font-weight: bold;"></p>
    </div>
    
    <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
        <div id="vp-mean-en-container" style="display: none; margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">
            <p style="color: #95a5a6; font-size: 0.8rem; margin: 0 0 5px 0; text-transform: uppercase; font-weight: bold;">Ý Nghĩa:</p>
            <div style="position: relative; padding-right: 35px;">
                <span id="vp-mean-en" style="font-size: 0.95rem; color: #ecf0f1;"></span>
                <i id="btn-translate-mean-icon" class="fas fa-language" onclick="translateEnMeaning()" 
                   style="position: absolute; right: 0; top: 0; cursor: pointer; color: #3498db; font-size: 1.2rem; transition: color 0.2s;" 
                   title="Dịch nghĩa này"></i>
            </div>
            <div id="vp-translated-en-mean" style="display: none; color: #2ecc71; font-style: italic; font-size: 0.9rem; margin-top: 5px; border-left: 3px solid #2ecc71; padding-left: 10px;"></div>   
        </div>

        <div id="vp-example-container" style="display: none;">
            <p style="color: #95a5a6; font-size: 0.8rem; margin: 0 0 5px 0; text-transform: uppercase; font-weight: bold;">Ví dụ (Example):</p>
            <div style="position: relative; padding-right: 35px;">
                <span id="vp-example-en" style="font-size: 0.95rem; color: #ecf0f1; font-style: italic;"></span>
                <i id="btn-translate-example-icon" class="fas fa-language" onclick="translateExample()" 
                   style="position: absolute; right: 0; top: 0; cursor: pointer; color: #3498db; font-size: 1.2rem; transition: color 0.2s;" 
                   title="Dịch ví dụ này"></i>
            </div>
            <div id="vp-translated-example" style="display: none; color: #2ecc71; font-style: italic; font-size: 0.9rem; margin-top: 5px; border-left: 3px solid #2ecc71; padding-left: 10px;"></div>
        </div>
    </div>
    
    <div style="display: flex; gap: 10px; justify-content: center;">
        <button onclick="playPronunciation()" style="flex: 1; background: #3498db; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; font-weight: bold;">
            <i class="fas fa-volume-up"></i> Nghe
        </button>
        <button onclick="document.getElementById('vocabPopup').style.display='none'" style="flex: 1; background: transparent; color: #aaa; border: 1px solid #aaa; padding: 10px; border-radius: 5px; cursor: pointer; font-weight: bold;">
            Đóng
        </button>
    </div>
</div>

<script>
    let currentWord = "";

    // PHP truyền biến này xuống để dùng cho dịch cả bài
    const hasViContent = <?= !empty($reading['content_vi']) ? 'true' : 'false' ?>;
    const engContent = <?= json_encode($reading['content'], JSON_UNESCAPED_UNICODE) ?>;
    let isFullTranslated = false; 

    // Hàm hiển thị bản dịch nguyên bài
    function toggleTranslation() {
        let transDiv = document.getElementById('vietnamese-content');
        let btn = document.getElementById('btn-translate');
        
        if (transDiv.style.display === 'none') {
            transDiv.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-eye-slash"></i> Ẩn bản dịch';

            // Nếu chưa có tiếng Việt và chưa dịch lần nào thì gọi API
            if (!hasViContent && !isFullTranslated) {
                translateFullArticle(engContent);
            }
        } else {
            transDiv.style.display = 'none';
            btn.innerHTML = '<i class="fas fa-eye"></i> Xem bản dịch';
        }
    }

    // Hàm gọi Google dịch cho cả bài đọc
    function translateFullArticle(text) {
        let statusSpan = document.getElementById('auto-translate-status');
        let cleanText = text.trim(); 
        let url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=vi&dt=t&q=${encodeURIComponent(cleanText)}`;
        
        fetch(url)
            .then(response => response.json())
            .then(result => {
                let translatedText = "";
                if (result && result[0]) {
                    for (let i = 0; i < result[0].length; i++) {
                        if (result[0][i][0]) translatedText += result[0][i][0];
                    }
                }
                let htmlText = translatedText.replace(/(?:\r\n|\r|\n)/g, '<br>');
                document.getElementById('vi-text-container').innerHTML = htmlText;
                isFullTranslated = true; 
            })
            .catch(error => {
                console.error("Lỗi dịch bài:", error);
                if(statusSpan) {
                    statusSpan.innerHTML = '<span style="color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> Lỗi: Không thể tải bản dịch tự động.</span>';
                }
            });
    }

    // Hàm hiển thị Popup từ vựng 
    function showVocabInfo(element) {
        let data = JSON.parse(element.getAttribute('data-info'));
        
        // Reset các khung dịch cũ
        document.getElementById('vp-translated-en-mean').style.display = 'none';
        document.getElementById('vp-translated-en-mean').innerText = '';
        document.getElementById('vp-translated-example').style.display = 'none';
        document.getElementById('vp-translated-example').innerText = '';

        // Hiển thị thông tin cơ bản
        document.getElementById('vp-word').innerText = data.word;
        document.getElementById('vp-type').innerText = data.type ?? data.word_type ?? "";
        document.getElementById('vp-pron').innerText = data.pronunciation || "";
        
        // Hiển thị nghĩa tiếng Anh (Dành cho những bài cũ có lưu meaning_en)
        let meanEn = data.meaning_en || data.meaning || "";
        document.getElementById('vp-mean-en-container').style.display = meanEn ? 'block' : 'none';
        document.getElementById('vp-mean-en').innerText = meanEn;
        
        // Hiển thị Câu Ví dụ (Dành cho chức năng AI mới)
        let exampleEn = data.example_en || "";
        document.getElementById('vp-example-container').style.display = exampleEn ? 'block' : 'none';
        document.getElementById('vp-example-en').innerText = exampleEn;

        // Xử lý Nghĩa Tiếng Việt
        let viMeaningElement = document.getElementById('vp-mean-vi');
        
        if (data.meaning_vi && data.meaning_vi.trim() !== "") {
            viMeaningElement.innerText = data.meaning_vi;
        } else {
            // Nếu AI không trả nghĩa VN, tự động gọi Google dịch dự phòng
            viMeaningElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang dịch...';
            let textToTranslate = data.meaning_en || data.meaning || data.word;
            let url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=vi&dt=t&q=${encodeURIComponent(textToTranslate)}`;
            
            fetch(url)
                .then(response => response.json())
                .then(result => {
                    viMeaningElement.innerText = result[0][0][0];
                })
                .catch(error => {
                    viMeaningElement.innerText = "(Lỗi dịch tự động)";
                });
        }
        
        document.getElementById('vocabPopup').style.display = 'block';
        currentWord = data.word;
        playPronunciation();
    }

    // Hàm gọi API Google Translate chung
    function fetchGoogleTranslate(textToTranslate, targetDivId, iconId) {
        let transDiv = document.getElementById(targetDivId);
        let iconBtn = document.getElementById(iconId);

        if (!textToTranslate || textToTranslate.trim() === "") return;

        if (transDiv.style.display === 'block') {
            transDiv.style.display = 'none';
            return;
        }

        iconBtn.classList.remove('fa-language');
        iconBtn.classList.add('fa-spinner', 'fa-spin');

        let url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=vi&dt=t&q=${encodeURIComponent(textToTranslate)}`;

        fetch(url)
            .then(response => response.json())
            .then(result => {
                let translatedText = "";
                if (result && result[0]) {
                    for (let i = 0; i < result[0].length; i++) {
                        if (result[0][i][0]) translatedText += result[0][i][0];
                    }
                }
                transDiv.innerText = translatedText;
                transDiv.style.display = 'block';
                iconBtn.classList.remove('fa-spinner', 'fa-spin');
                iconBtn.classList.add('fa-language');
            })
            .catch(error => {
                transDiv.innerText = "(Lỗi mạng: Không thể dịch tự động)";
                transDiv.style.display = 'block';
                transDiv.style.color = '#e74c3c';
                iconBtn.classList.remove('fa-spinner', 'fa-spin');
                iconBtn.classList.add('fa-language');
            });
    }

    // Nút dịch Nghĩa Tiếng Anh
    function translateEnMeaning() {
        let text = document.getElementById('vp-mean-en').innerText;
        fetchGoogleTranslate(text, 'vp-translated-en-mean', 'btn-translate-mean-icon');
    }

    // Nút dịch Câu Ví dụ
    function translateExample() {
        let text = document.getElementById('vp-example-en').innerText;
        fetchGoogleTranslate(text, 'vp-translated-example', 'btn-translate-example-icon');
    }

    // Hàm phát âm dùng Web Speech API
    function playPronunciation() {
        if (currentWord !== "") {
            let msg = new SpeechSynthesisUtterance(currentWord);
            msg.lang = 'en-US';
            window.speechSynthesis.speak(msg);
        }
    }

    // Hàm kiểm tra đáp án bài tập
    function checkAnswer(label, selectedKey) {
        let parent = label.parentElement;
        let correctKey = label.getAttribute('data-ans');
        
        let labels = parent.querySelectorAll('.option-label');
        labels.forEach(l => l.style.pointerEvents = 'none');

        if (selectedKey === correctKey) {
            label.classList.add('correct');
        } else {
            label.classList.add('wrong');
            labels.forEach(l => {
                let bTag = l.querySelector('b');
                if (bTag && bTag.innerText.startsWith(correctKey)) {
                    l.classList.add('correct');
                }
            });
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>