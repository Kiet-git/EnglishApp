<?php
require_once '../../config/db.php';
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Chỉ Admin mới được phép dùng tính năng này
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$GEMINI_API_KEY = GEMINI_API_KEY;

// Lấy danh sách các chủ đề đã có để hiển thị ra form
$topics = $conn->query("SELECT topic_id, topic_name FROM topics ORDER BY topic_name ASC")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['generate_vocab'])) {
    $existing_topic_id = $_POST['existing_topic_id'] ?? '';
    $theme = trim($_POST['theme'] ?? '');
    $num_words = intval($_POST['num_words'] ?? 20);

    // --- XỬ LÝ CHỌN MODEL AI ---
    $allowed_models = [
        'gemini-2.5-flash', 
        'gemini-3-flash', 
        'gemini-3.1-flash-lite',
        'gemini-3.1-pro'
    ];
    // Lấy model từ form, nếu người dùng cố tình f12 sửa value thì fallback về mặc định
    $MODEL = in_array($_POST['ai_model'], $allowed_models) ? $_POST['ai_model'] : 'gemini-2.5-flash';
    // Lấy danh sách tên các chủ đề đang có để cấm AI tạo trùng
    $existing_names = array_column($topics, 'topic_name');
    $avoid_list = implode(", ", $existing_names);

    // Xử lý Logic Prompt: Tạo mới hay Thêm vào cái cũ
    if (!empty($existing_topic_id)) {
        // Lấy tên chủ đề cũ từ DB
        $stmtName = $conn->prepare("SELECT topic_name FROM topics WHERE topic_id = ?");
        $stmtName->execute([$existing_topic_id]);
        $existing_topic_name = $stmtName->fetchColumn();

        $theme_prompt = "Chủ đề bắt buộc: '$existing_topic_name'. 
        Hãy tạo tiếp $num_words từ vựng mới thuộc chủ đề này. Hãy ưu tiên chọn những từ vựng nâng cao hoặc ít phổ biến hơn một chút để tránh bị trùng lặp với những từ cơ bản mà tôi đã có sẵn.";
    } else {
        $theme_prompt = empty($theme) 
            ? "Hãy tự chọn một chủ đề tiếng Anh phổ biến bất kỳ. TUYỆT ĐỐI KHÔNG chọn các chủ đề sau vì tôi đã có rồi: $avoid_list." 
            : "Chủ đề bắt buộc: '$theme'";
    }

    $prompt = "Bạn là một chuyên gia ngôn ngữ học. $theme_prompt.
    Nhiệm vụ của bạn:
    1. Tạo một tên chủ đề tiếng Anh.
    2. Mô tả ngắn gọn chủ đề đó bằng tiếng Việt với 10-15 chữ.
    3. Cung cấp đúng $num_words từ vựng thuộc chủ đề này. KHÔNG ĐƯỢC THIẾU.

    Yêu cầu định dạng JSON xuất ra:
    - word: Từ vựng tiếng Anh.
    - word_type: Từ loại (n, v, adj, adv).
    - pronunciation: Phiên âm quốc tế IPA với accent US (ví dụ: /kæt/).
    - meaning: Nghĩa tiếng Việt NGẮN GỌN.
    ";

    // ÉP KIỂU JSON SCHEMA
    $responseSchema = [
        "type" => "OBJECT",
        "properties" => [
            "topic_name" => ["type" => "STRING", "description" => "Tên chủ đề tiếng Anh"],
            "description" => ["type" => "STRING", "description" => "Mô tả ngắn gọn chủ đề bằng tiếng Việt"],
            "vocabularies" => [
                "type" => "ARRAY",
                "items" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "word" => ["type" => "STRING"],
                        "word_type" => ["type" => "STRING"],
                        "pronunciation" => ["type" => "STRING"],
                        "meaning" => ["type" => "STRING"]
                    ],
                    "required" => ["word", "word_type", "pronunciation", "meaning"]
                ]
            ]
        ],
        "required" => ["topic_name", "description", "vocabularies"]
    ];

    $payload = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => [
            "responseMimeType" => "application/json", 
            "temperature" => 0.7,
            "responseSchema" => $responseSchema
        ]
    ];

    // VÒNG LẶP AUTO-RETRY CHỐNG LỖI AI
    $max_retries = 3;
    $parsed = null;
    $ai_error_msg = "";

    for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$MODEL}:generateContent?key={$GEMINI_API_KEY}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false, CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        $ai_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        $ai_text = str_replace(['```json', '```JSON', '```'], '', $ai_text);
        $ai_text = trim($ai_text);
        $parsed = json_decode($ai_text, true);

        if ($parsed && isset($parsed['topic_name'], $parsed['description'], $parsed['vocabularies']) && count($parsed['vocabularies']) > 0) {
            break; 
        } else {
            $ai_error_msg = json_last_error_msg();
            $parsed = null;
            sleep(2); 
        }
    }

    if (!$parsed) {
        die("<script>alert('AI đã quá tải khi cố gắng tạo $num_words từ. Hãy thử lại với số lượng ít hơn hoặc chọn một mô hình AI khác nhé!');history.back();</script>");
    }

    try {
        $conn->beginTransaction();

        // 1. LƯU CHỦ ĐỀ HOẶC LẤY CHỦ ĐỀ CÓ SẴN
        // 1. LƯU CHỦ ĐỀ HOẶC LẤY CHỦ ĐỀ CÓ SẴN
        if (!empty($existing_topic_id)) {
            $final_topic_id = $existing_topic_id;
            $final_topic_name = $existing_topic_name; // Bỏ qua tên AI sinh ra, dùng tên gốc
        } else {
            // Lớp bảo mật 2: Kiểm tra xem chủ đề AI nghĩ ra có bị trùng tên không (không phân biệt Hoa/Thường)
            $stmtCheckTopic = $conn->prepare("SELECT topic_id FROM topics WHERE LOWER(topic_name) = LOWER(?)");
            $stmtCheckTopic->execute([trim($parsed['topic_name'])]);
            $found_topic_id = $stmtCheckTopic->fetchColumn();

            if ($found_topic_id) {
                // Nếu AI lỡ tạo trùng tên -> Tự động gom mớ từ vựng này vào chủ đề cũ luôn, không insert chủ đề mới
                $final_topic_id = $found_topic_id;
                $final_topic_name = $parsed['topic_name'];
            } else {
                // Nếu tên hoàn toàn mới -> Tạo chủ đề mới bình thường
                $stmtTopic = $conn->prepare("INSERT INTO topics (topic_name, description) VALUES (?, ?)");
                $stmtTopic->execute([$parsed['topic_name'], $parsed['description']]);
                $final_topic_id = $conn->lastInsertId();
                $final_topic_name = $parsed['topic_name'];
            }
        }

        // 2. LƯU TỪ VỰNG & KIỂM TRA TRÙNG LẶP
        $stmtCheckWord = $conn->prepare("SELECT COUNT(*) FROM vocabularies WHERE LOWER(word) = LOWER(?)");
        $stmtVocab = $conn->prepare("INSERT INTO vocabularies (topic_id, word, word_type, pronunciation, meaning) VALUES (?, ?, ?, ?, ?)");
        
        $count_success = 0;
        $count_skipped = 0;

        foreach ($parsed['vocabularies'] as $v) {
            $word_clean = trim($v['word']);

            // Kiểm tra xem từ vựng đã có trong DB chưa (không phân biệt hoa thường)
            $stmtCheckWord->execute([$word_clean]);
            if ($stmtCheckWord->fetchColumn() > 0) {
                // Đã tồn tại -> Bỏ qua
                $count_skipped++;
                continue; 
            }

            // Nếu chưa tồn tại -> Thêm vào DB
            $stmtVocab->execute([
                $final_topic_id, 
                $word_clean, 
                $v['word_type'], 
                $v['pronunciation'], 
                $v['meaning']
            ]);
            $count_success++;
        }

        $conn->commit();

        // Thông báo kết quả thông minh
        $msg = "Tuyệt vời! Đã thêm thành công {$count_success} từ vựng vào chủ đề [{$final_topic_name}].";
        if ($count_skipped > 0) {
            $msg .= "\\n\\nĐã phát hiện và BỎ QUA {$count_skipped} từ vựng bị trùng lặp trong CSDL!";
        }
        
        echo "<script>alert('{$msg}'); window.location='auto_generate_vocab.php';</script>";
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        die("<script>alert('Lỗi lưu Database: " . addslashes($e->getMessage()) . "');history.back();</script>");
    }
}

include '../../includes/header.php';
?>

<div id="loading-overlay">
    <div class="spinner"></div>
    <h3 style="margin-top: 20px; color: white;">AI đang cày cuốc tạo từ vựng...</h3>
    <p style="color: #ddd; font-size: 0.9rem;">Quá trình này có thể mất từ 10 - 60 giây tùy số lượng từ. Vui lòng không đóng trang!</p>
</div>

<style>
    #loading-overlay{ position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.85); display:none; flex-direction:column; justify-content:center; align-items:center; z-index:9999; backdrop-filter:blur(5px); }
    .spinner{ border:6px solid #f3f3f3; border-top:6px solid #3498db; border-radius:50%; width:60px; height:60px; animation:spin 1s linear infinite; }
    @keyframes spin{ 0%{transform:rotate(0deg);} 100%{transform:rotate(360deg);} }
    .auto-ai-container{ width:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; margin-top:40px; margin-bottom: 60px;}
    .auto-ai-box{ width:100%; max-width:550px; padding:25px; border-top:5px solid #3498db; background:#fff; box-shadow:0 5px 15px rgba(0,0,0,0.05); border-radius:8px; }
</style>

<div class="auto-ai-container">

    <div style="text-align:center;margin-bottom:25px;">
        <h2 style="color:#2980b9;">
            <i class="fas fa-magic"></i> Auto Tạo Từ Vựng Bằng AI
        </h2>
        <p style="color:#7f8c8d;">
            Tạo mới hoặc bổ sung hàng loạt từ vựng vào kho dữ liệu nhanh chóng với khả năng lọc trùng lặp thông minh.
        </p>
    </div>

    <div class="auto-ai-box">

        <form method="POST" onsubmit="document.getElementById('loading-overlay').style.display='flex';">

            <div style="margin-bottom:20px; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;">
                <label style="font-weight:bold;display:block;margin-bottom:8px;color:#e67e22;">
                    <i class="fas fa-folder-plus"></i> Bổ sung vào chủ đề đã có:
                </label>
                <select name="existing_topic_id" id="existing_topic_id" onchange="toggleThemeInput()" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:5px;font-size:1rem;">
                    <option value="">-- KHÔNG CHỌN (Sẽ tạo Chủ đề mới) --</option>
                    <?php foreach ($topics as $t): ?>
                        <option value="<?= $t['topic_id'] ?>"><?= htmlspecialchars($t['topic_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:20px;" id="new_theme_div">
                <label style="font-weight:bold;display:block;margin-bottom:8px;color:#2c3e50;">
                    <i class="fas fa-lightbulb"></i> Gợi ý chủ đề mới (Không bắt buộc):
                </label>
                <input type="text" name="theme" id="theme_input" placeholder="VD: Động vật, Công nghệ thông tin, IELTS Task 1..." style="width:100%;padding:12px;border:1px solid #ddd;border-radius:5px;font-size:1rem;">
                <small style="color:#95a5a6;display:block;margin-top:5px;">
                    <i class="fas fa-info-circle"></i> Bỏ trống để AI tự động chọn ngẫu nhiên một chủ đề hay.
                </small>
            </div>

            <div style="margin-bottom:25px;">
                <label style="font-weight:bold;display:block;margin-bottom:8px;color:#2c3e50;">
                    <i class="fas fa-sort-numeric-up"></i> Số lượng từ vựng muốn tạo:
                </label>
                <select name="num_words" required style="width:100%;padding:12px;border:1px solid #ddd;border-radius:5px;font-size:1rem;">
                    <option value="10">Chạy thử nghiệm (10 từ)</option>
                    <option value="20">Mức độ Nhẹ nhàng (20 từ)</option>
                    <option value="50" selected>Mức độ Tiêu chuẩn (50 từ)</option>
                    <option value="100">Mức độ Chuyên sâu (100 từ - Tốn thời gian chờ)</option>
                </select>
            </div>

            <div style="margin-bottom:25px;">
                <label style="font-weight:bold;display:block;margin-bottom:8px;color:#2c3e50;">
                    <i class="fas fa-robot"></i> Chọn "Bộ não" AI xử lý:
                </label>
                <select name="ai_model" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:5px;font-size:1rem; background: #fdfefe;">
                    <option value="gemini-2.5-flash" selected>Gemini 2.5 Flash (Tốc độ siêu nhanh, Khuyên dùng)</option>
                    <option value="gemini-3-flash">Gemini 3 Flash (Thông minh hơn, Từ vựng chuyên sâu, Tốn thời gian hơn)</option>
                    <option value="gemini-3.1-flash-lite">Gemini 3.1 Flash Lite (Bản cũ ổn định)</option>
                    <option value="gemini-3.1-pro">Gemini 3.1 Pro (Thông minh nhất, Từ vựng học thuật cao, Tốn thời gian nhất)</option>
                </select>
                <small style="color:#95a5a6;display:block;margin-top:5px;">
                    <i class="fas fa-info-circle"></i> Bản Pro trả kết quả chậm hơn nhưng từ vựng mang tính học thuật cao hơn.
                </small>
            </div>

            <button type="submit" name="generate_vocab" class="btn" style="width:100%;background:#3498db;padding:15px;font-size:1.1rem;border-radius:5px;">
                <i class="fas fa-bolt"></i> Bắt Đầu Auto Generate
            </button>

        </form>

    </div>
</div>

<script>
    // JS Ẩn/Hiện ô nhập chủ đề mới nếu người dùng chọn chủ đề có sẵn
    function toggleThemeInput() {
        var existingTopic = document.getElementById('existing_topic_id').value;
        var newThemeDiv = document.getElementById('new_theme_div');
        var themeInput = document.getElementById('theme_input');

        if (existingTopic !== '') {
            newThemeDiv.style.opacity = '0.4';
            themeInput.disabled = true;
            themeInput.value = ''; // Xóa trắng dữ liệu cũ nếu có
        } else {
            newThemeDiv.style.opacity = '1';
            themeInput.disabled = false;
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>