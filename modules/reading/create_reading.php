<?php
require_once '../../config/db.php';
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Chỉ cần đăng nhập là được dùng
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

// Lấy địa chỉ IP của người dùng
$user_ip = $_SERVER['REMOTE_ADDR'];

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student'; // Lấy vai trò (role)
$MODEL = "gemini-2.5-flash";
$GEMINI_API_KEY = GEMINI_API_KEY;

// 1. KIỂM TRA QUYỀN VÀ TÍNH PHÍ
if ($role === 'admin') {
    $is_free = true;
    $cost = 0;
    $insert_user_id = null; // Admin tạo thì lưu là bài hệ thống (NULL)
} else {
    // Đếm số bài đọc tài khoản này đã tạo
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM readings WHERE user_id = ?");
    $stmtCount->execute([$user_id]);
    $created_count = $stmtCount->fetchColumn();

    // Đếm số bài đọc đã được tạo từ MẠNG WIFI / IP này
    $stmtIpCount = $conn->prepare("SELECT COUNT(*) FROM readings WHERE ip_address = ?");
    $stmtIpCount->execute([$user_ip]);
    $created_by_ip = $stmtIpCount->fetchColumn();

    // Chống Spam: Chỉ miễn phí nếu Tài khoản chưa tạo lần nào VÀ IP này chưa xài quá 2 lần free
    $is_free = ($created_count == 0 && $created_by_ip < 2); 
    
    $cost = $is_free ? 0 : 2; // Các lần sau = 2 credits
    $insert_user_id = $user_id; // Học viên tạo thì lưu tên học viên
}

// Lấy số dư hiện tại của User
$stmtUser = $conn->prepare("SELECT credits FROM users WHERE user_id = ?");
$stmtUser->execute([$user_id]);
$current_credits = $stmtUser->fetchColumn();

// Lấy danh sách topic
$topics = $conn->query("SELECT topic_id, topic_name FROM topics ORDER BY topic_name ASC")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['generate'])) {
    // Nếu không đủ tiền và không phải lần free (chỉ áp dụng Student)
    if ($role !== 'admin' && !$is_free && $current_credits < $cost) {
         die("<script>alert('Bạn không đủ Credit. Vui lòng nạp thêm!'); window.location='../../payments/topup.php';</script>");
    }

    $topic_id = intval($_POST['topic_id'] ?? 0);
    $custom_title = trim($_POST['custom_title'] ?? '');
    $level = trim($_POST['level'] ?? '');

    if (($topic_id <= 0 && empty($custom_title)) || empty($level)) {
        die("<script>alert('Vui lòng chọn Chủ đề hoặc Nhập tiêu đề!');history.back();</script>");
    }

    $topic_name = !empty($custom_title) ? $custom_title : '';
    if (empty($topic_name)) {
        $tStmt = $conn->prepare("SELECT topic_name FROM topics WHERE topic_id = ?");
        $tStmt->execute([$topic_id]);
        $topic_name = $tStmt->fetchColumn();
    }
    $final_topic_id = !empty($custom_title) ? null : $topic_id;

    // 2. PROMPT TỐI ƯU
    $prompt = "Bạn là giáo viên tiếng Anh. Tạo 1 bài đọc tiếng Anh chủ đề '$topic_name' trình độ $level. KHÔNG tạo bản dịch toàn bài.";

    // 3. ÉP KIỂU JSON SCHEMA
    $responseSchema = [
        "type" => "OBJECT",
        "properties" => [
            "title" => ["type" => "STRING"],
            "content" => ["type" => "STRING"],
            "vocabularies" => [
                "type" => "ARRAY",
                "items" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "word" => ["type" => "STRING"],
                        "type" => ["type" => "STRING"],
                        "pronunciation" => ["type" => "STRING"],
                        "meaning_vi" => ["type" => "STRING"],
                        "meaning_en" => ["type" => "STRING"],
                        "example_en" => ["type" => "STRING"]
                    ],
                    "required" => ["word", "type", "pronunciation", "meaning_vi", "meaning_en", "example_en"]
                ]
            ],
            "quizzes" => [
                "type" => "ARRAY",
                "items" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "question" => ["type" => "STRING"],
                        "options" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "A" => ["type" => "STRING"], "B" => ["type" => "STRING"],
                                "C" => ["type" => "STRING"], "D" => ["type" => "STRING"]
                            ]
                        ],
                        "answer" => ["type" => "STRING", "enum" => ["A", "B", "C", "D"]]
                    ],
                    "required" => ["question", "options", "answer"]
                ]
            ]
        ],
        "required" => ["title", "content", "vocabularies", "quizzes"]
    ];

    $payload = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => [
            "responseMimeType" => "application/json", 
            "temperature" => 0.7,
            "responseSchema" => $responseSchema
        ]
    ];

    // 4. VÒNG LẶP AUTO-RETRY CHỐNG LỖI AI
    $max_retries = 3;
    $parsed = null;
    $ai_error_msg = "";

    for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$MODEL}:generateContent?key={$GEMINI_API_KEY}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false, CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        $ai_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        // Dọn dẹp markdown rác nếu có
        $ai_text = str_replace(['```json', '```JSON', '```'], '', $ai_text);
        $ai_text = trim($ai_text);

        $parsed = json_decode($ai_text, true);

        if ($parsed && isset($parsed['title'], $parsed['content'], $parsed['vocabularies'], $parsed['quizzes'])) {
            break; // Thành công thì thoát vòng lặp
        } else {
            $ai_error_msg = json_last_error_msg();
            $parsed = null;
            sleep(1); // Nghỉ 1s rồi thử lại
        }
    }

    if (!$parsed) {
        die("<script>alert('Lỗi định dạng AI. Hãy thử lại (Bạn chưa bị trừ tiền).');history.back();</script>");
    }

    try {
        $conn->beginTransaction();

        // Trừ tiền Student nếu không phải lần free
        if ($role !== 'admin' && !$is_free) {
            $updateCred = $conn->prepare("UPDATE users SET credits = credits - ? WHERE user_id = ?");
            $updateCred->execute([$cost, $user_id]);
        }

        // =========================================================================
        // TỰ ĐỘNG NHẬN DIỆN CHỦ ĐỀ DỰA TRÊN TỪ VỰNG AI TẠO RA
        // =========================================================================
        $final_topic_id = null; // Mặc định là NULL (Chủ đề Mới/Tự chọn)
        
        // 1. Gom tất cả các từ vựng tiếng Anh do AI vừa tạo vào một mảng
        $ai_words = [];
        if (isset($parsed['vocabularies']) && is_array($parsed['vocabularies'])) {
            foreach ($parsed['vocabularies'] as $v) {
                if (!empty($v['word'])) {
                    $ai_words[] = strtolower(trim($v['word']));
                }
            }
        }

        // 2. Nếu có từ vựng, đem đi kiểm tra trong Database
        if (count($ai_words) > 0) {
            // Tạo chuỗi tham số ẩn (VD: ?, ?, ?) để query an toàn
            $placeholders = implode(',', array_fill(0, count($ai_words), '?'));
            
            // Tìm trong DB xem có từ nào trùng không. Dùng LIMIT 1 để lấy Chủ đề của từ đầu tiên trùng khớp.
            $sqlFindTopic = "SELECT topic_id FROM vocabularies WHERE LOWER(word) IN ($placeholders) AND topic_id IS NOT NULL LIMIT 1";
            $stmtFindTopic = $conn->prepare($sqlFindTopic);
            $stmtFindTopic->execute($ai_words);
            
            $found_topic_id = $stmtFindTopic->fetchColumn();

            // Nếu tìm thấy chủ đề liên quan, ghi đè final_topic_id
            if ($found_topic_id) {
                $final_topic_id = $found_topic_id;
            }
        }
        // =========================================================================

        // Lưu bài đọc với user_id, ip_address và topic_id (tự động nhận diện) tương ứng
        $stmt = $conn->prepare("
            INSERT INTO readings (topic_id, user_id, title, level, content, content_vi, vocab_data, quiz_data, created_at, ip_address)
            VALUES (?, ?, ?, ?, ?, '', ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $final_topic_id, 
            $insert_user_id, 
            $parsed['title'], 
            $level, 
            $parsed['content'],
            json_encode($parsed['vocabularies'], JSON_UNESCAPED_UNICODE),
            json_encode($parsed['quizzes'], JSON_UNESCAPED_UNICODE),
            $user_ip // IP của người tạo bài đọc (dùng để kiểm soát spam)
        ]);

        $new_id = $conn->lastInsertId();
        $conn->commit();

        echo "<script>alert('Tạo bài đọc thành công!'); window.location='../../modules/reading/reading.php?id=$new_id';</script>";
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        die("<script>alert('Lỗi lưu Database: " . addslashes($e->getMessage()) . "');history.back();</script>");
    }
}

include '../../includes/header.php';
?>

<div class="container" style="max-width: 600px; margin-top: 30px; margin-bottom: 50px;">
    <h2><i class="fas fa-magic"></i> Tự Tạo Bài Đọc (AI Reading)</h2>

    <div style="background: #e8f6f3; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #1abc9c;">
        <h4 style="margin:0 0 10px 0; color: #16a085;">Thông tin tài khoản:</h4>
        
        <?php if ($role === 'admin'): ?>
            <p style="color: #27ae60; font-weight: bold; background: #e8f8f5; display: inline-block; padding: 5px 15px; border-radius: 20px; margin: 0;"><i class="fas fa-crown"></i> Admin - Miễn phí toàn bộ</p>
        <?php else: ?>
            <p style="margin: 5px 0;">💰 Số dư hiện tại: <b><?= $current_credits ?> Credits</b></p>
            <?php if ($is_free): ?>
                <p style="margin: 5px 0; color: #e74c3c; font-weight: bold;">🎁 BẠN ĐANG CÓ 1 LƯỢT TẠO MIỄN PHÍ!</p>
            <?php else: ?>
                <p style="margin: 5px 0;">⚡ Phí tạo mỗi bài: <b>2 Credits</b></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <form method="POST" onsubmit="document.getElementById('loading-overlay').style.display = 'flex';">
        <div class="form-group" style="margin-bottom: 15px;">
            <label style="font-weight: bold;">Nhập Tiêu Đề Tạo:</label>
            <input required type="text" name="custom_title" id="input_title" placeholder="VD: The evolution of Artificial Intelligence..." style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
        </div>
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="font-weight: bold;">Cấp độ (CEFR):</label>
            <select name="level" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                <option value="A1">A1 - Cơ bản</option>
                <option value="A2">A2 - Sơ cấp</option>
                <option value="B1">B1 - Trung cấp</option>
                <option value="B2">B2 - Trung cao cấp</option>
                <option value="C1">C1 - Nâng cao</option>
            </select>
        </div>

        <?php if ($role === 'admin'): ?>
            <button type="submit" name="generate" style="width: 100%; background: #27ae60; color: white; padding: 12px; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">
                <i class="fas fa-robot"></i> Tạo Bài Đọc Hệ Thống (Miễn Phí)
            </button>
        <?php else: ?>
            <button type="submit" name="generate" onclick="return confirm('Xác nhận tạo bài đọc? <?= !$is_free ? 'Bạn sẽ bị trừ 2 Credits.' : '' ?>')" style="width: 100%; background: #8e44ad; color: white; padding: 12px; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">
                <i class="fas fa-robot"></i> Tạo Bài Đọc Ngay
            </button>
        <?php endif; ?>
    </form>
</div>
<style>
    /* CSS Thuần cho Loading */
    #loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.85); /* Nền đen mờ */
        display: none; /* Mặc định ẩn */
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999; /* Luôn nổi lên trên cùng */
        backdrop-filter: blur(5px); /* Làm mờ background phía sau */
    }

    /* Vòng xoay Spinner */
    .spinner {
        border: 6px solid #f3f3f3; /* Màu nền vòng */
        border-top: 6px solid #8e44ad; /* Màu xoay (Tím theo theme của bạn) */
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 1s linear infinite;
    }

    /* Hiệu ứng xoay */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
<div id="loading-overlay">
    <div class="spinner"></div>
    <h3 style="margin-top: 20px; color: white;">Đang nhờ AI viết bài...</h3>
    <p style="color: #ddd; font-size: 0.9rem;">Vui lòng không đóng trình duyệt, quá trình này mất khoảng 10-15 giây.</p>
</div>
<?php include '../../includes/footer.php'; ?>