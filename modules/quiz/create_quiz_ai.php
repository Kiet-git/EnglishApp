<?php
require_once '../../config/db.php';
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) { 
    header("Location: ../../auth/login.php"); 
    exit; 
}

include '../../includes/header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student';

// 1. Kiểm tra Credit hiện tại 
$stmt = $conn->prepare("SELECT credits FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$credits = $stmt->fetchColumn(); 
if ($credits === false) $credits = 0;

// Lấy danh sách chủ đề & Số từ vựng
$topics = $conn->query("
    SELECT t.topic_id, t.topic_name, COUNT(v.vocab_id) as vocab_count 
    FROM topics t 
    LEFT JOIN vocabularies v ON t.topic_id = v.topic_id 
    GROUP BY t.topic_id
")->fetchAll(PDO::FETCH_ASSOC);

$all_vocabs = $conn->query("SELECT word, meaning, pronunciation FROM vocabularies")->fetchAll(PDO::FETCH_ASSOC);

// --- XỬ LÝ TẠO ĐỀ THI ---
if (isset($_POST['generate_quiz'])) {
    $topic_id_input = $_POST['topic_id'];
    $num_questions = (int)$_POST['num_questions'];
    $quiz_mode = $_POST['quiz_mode'] ?? 'simple';
    
    // ĐỊNH GIÁ: AI = 2 credits, Cơ bản = Miễn phí (0)
    $cost = ($quiz_mode === 'ai') ? 2 : 0; 

    if ($role != 'admin' && $cost > 0 && $credits < $cost) {
        die("<script>alert('Bạn không đủ Credit để nhờ AI tạo đề. Vui lòng dùng chế độ Cơ bản (Miễn phí)!'); history.back();</script>");
    }

    $quiz_title_input = trim($_POST['quiz_title_input'] ?? '');
    $quiz_title = "";
    $vocab_list = [];

    // LẤY DỮ LIỆU TỪ VỰNG TỪ DATABASE
    if ($topic_id_input === 'all') {
        $default_title = "Bài thi Tổng hợp (" . ($quiz_mode === 'ai' ? "AI" : "Cơ bản") . ")";
        $quiz_title = !empty($quiz_title_input) ? $quiz_title_input : $default_title;        
        
        $all_words_raw = $conn->query("SELECT topic_id, word, meaning, pronunciation FROM vocabularies")->fetchAll(PDO::FETCH_ASSOC);
        $words_by_topic = [];
        foreach ($all_words_raw as $w) { $words_by_topic[$w['topic_id']][] = $w; }
        foreach ($words_by_topic as &$words) { shuffle($words); }
        unset($words);
        
        while (count($vocab_list) < $num_questions && !empty($words_by_topic)) {
            foreach ($words_by_topic as $tid => &$words) {
                if (count($vocab_list) >= $num_questions) break 2;
                if (!empty($words)) {
                    $vocab_list[] = array_shift($words); 
                } else {
                    unset($words_by_topic[$tid]);
                }
            }
        }
        shuffle($vocab_list);
        $topic_name = "Tổng hợp";
        
    } else {
        $stmt_t = $conn->prepare("SELECT topic_name FROM topics WHERE topic_id = ?");
        $stmt_t->execute([$topic_id_input]);
        $topic_name = $stmt_t->fetchColumn() ?: "Chủ đề Không xác định";
        $default_title = "Bài thi: $topic_name (" . ($quiz_mode === 'ai' ? "AI" : "Cơ bản") . ")";
        $quiz_title = !empty($quiz_title_input) ? $quiz_title_input : $default_title;

        $fetch_limit = ($quiz_mode === 'ai') ? max($num_questions, 40) : $num_questions;
        $stmt = $conn->prepare("SELECT word, meaning, pronunciation FROM vocabularies WHERE topic_id = ? ORDER BY RAND() LIMIT " . (int)$fetch_limit);
        $stmt->execute([$topic_id_input]);
        $vocab_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (count($vocab_list) === 0) {
        die("<script>alert('Không có từ vựng để tạo bài thi!');history.back();</script>");
    }

    $count_success = 0;
    $new_quiz_id = 0;

    // ==============================================================
    // LUỒNG 1: TẠO ĐỀ CƠ BẢN (MIỄN PHÍ)
    // ==============================================================
    if ($quiz_mode === 'simple') {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO quizzes (title, created_by) VALUES (?, ?)");
            $stmt->execute([$quiz_title, $user_id]);
            $new_quiz_id = $conn->lastInsertId();

            $stmt_q = $conn->prepare("INSERT INTO questions (quiz_id, content, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($vocab_list as $v) {
                if ($count_success >= $num_questions) break; 

                $available_types = [1, 2];
                if (!empty(trim($v['pronunciation']))) $available_types[] = 3; 
                
                $q_type = $available_types[array_rand($available_types)];
                
                if ($q_type == 1) {
                    $content = "Nghĩa của từ '<strong>" . htmlspecialchars($v['word']) . "</strong>' là gì?";
                    $correct_val = $v['meaning'];
                    $pool = array_column($all_vocabs, 'meaning');
                } elseif ($q_type == 2) {
                    $content = "Từ tiếng Anh nào có nghĩa là: '<strong>" . htmlspecialchars($v['meaning']) . "</strong>'?";
                    $correct_val = $v['word'];
                    $pool = array_column($all_vocabs, 'word');
                } else {
                    $content = "Phiên âm của từ '<strong>" . htmlspecialchars($v['word']) . "</strong>' là gì?";
                    $correct_val = $v['pronunciation'];
                    $pool = array_filter(array_column($all_vocabs, 'pronunciation')); 
                }

                $wrong_pool = array_filter($pool, function($val) use ($correct_val) {
                    return strtolower(trim($val)) !== strtolower(trim($correct_val)) && !empty(trim($val));
                });
                $wrong_pool = array_values(array_unique($wrong_pool));
                
                if (count($wrong_pool) >= 3) {
                    shuffle($wrong_pool);
                    $distractors = array_slice($wrong_pool, 0, 3);
                } else {
                    $distractors = ["/ˈsæm.pəl/", "Một đáp án khác", "Từ vựng ngẫu nhiên", "Đáp án gây nhiễu"];
                    shuffle($distractors);
                    $distractors = array_slice($distractors, 0, 3);
                }
                
                $options_val = array_merge([$correct_val], $distractors);
                shuffle($options_val);
                
                $correct_answer = 'A';
                if ($options_val[1] === $correct_val) $correct_answer = 'B';
                if ($options_val[2] === $correct_val) $correct_answer = 'C';
                if ($options_val[3] === $correct_val) $correct_answer = 'D';
                
                $stmt_q->execute([$new_quiz_id, $content, $options_val[0], $options_val[1], $options_val[2], $options_val[3], $correct_answer]);
                $count_success++;
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            die("<script>alert('Lỗi DB: ". $e->getMessage() ."');history.back();</script>");
        }
    } 
    // ==============================================================
    // LUỒNG 2: TẠO ĐỀ NÂNG CAO AI (2 CREDITS) - ĐÃ BỔ SUNG SCHEMA
    // ==============================================================
    else if ($quiz_mode === 'ai') {
        $MODEL = "gemini-2.5-flash";
        $GEMINI_API_KEY = GEMINI_API_KEY;

        $vocab_string = "";
        foreach ($vocab_list as $idx => $v) {
            if ($idx >= 40) break; // Khống chế số lượng để API khỏi quá tải token
            $vocab_string .= "- " . $v['word'] . " (nghĩa: " . $v['meaning'] . ")\n";
        }

        $prompt = "Bạn là giáo viên tiếng Anh. Có danh sách từ vựng chủ đề '$topic_name' sau:
        $vocab_string

        NHIỆM VỤ: 
        Tạo CHÍNH XÁC $num_questions câu hỏi trắc nghiệm dùng các từ trên. Đa dạng hóa câu hỏi (điền từ vào câu, từ đồng nghĩa, trái nghĩa...). 
        Lưu ý: Nếu thiếu từ, hãy tạo nhiều dạng câu hỏi khác nhau cho cùng một từ. Không dùng dấu ngoặc kép bên trong nội dung câu hỏi.";

        // ÉP CẤU TRÚC JSON (SCHEMA) - ĐẢM BẢO KHÔNG THỂ LỖI CÚ PHÁP
        $payload = [
            "contents" => [["parts" => [["text" => $prompt]]]],
            "generationConfig" => [
                "responseMimeType" => "application/json", 
                "temperature" => 0.7,
                "responseSchema" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "questions" => [
                            "type" => "ARRAY",
                            "items" => [
                                "type" => "OBJECT",
                                "properties" => [
                                    "question" => ["type" => "STRING"],
                                    "option_a" => ["type" => "STRING"],
                                    "option_b" => ["type" => "STRING"],
                                    "option_c" => ["type" => "STRING"],
                                    "option_d" => ["type" => "STRING"],
                                    "correct" => ["type" => "STRING", "enum" => ["A", "B", "C", "D"]]
                                ],
                                "required" => ["question", "option_a", "option_b", "option_c", "option_d", "correct"]
                            ]
                        ]
                    ],
                    "required" => ["questions"]
                ]
            ]
        ];

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
        
        // Dọn dẹp tàn dư markdown nếu có
        $ai_text = str_replace(['```json', '```JSON', '```'], '', $ai_text);
        $ai_text = trim($ai_text);

        $parsed = json_decode($ai_text, true);

        if (!$parsed || !isset($parsed['questions']) || count($parsed['questions']) == 0) {
            $err = json_last_error_msg();
            die("<script>alert('Lỗi định dạng từ AI ($err). Hệ thống đang quá tải, bạn CHƯA bị trừ tiền, hãy thử lại nhé!');history.back();</script>");
        }

        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO quizzes (title, created_by) VALUES (?, ?)");
            $stmt->execute([$quiz_title, $user_id]);
            $new_quiz_id = $conn->lastInsertId();

            $stmt_q = $conn->prepare("INSERT INTO questions (quiz_id, content, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($parsed['questions'] as $q) {
                if (isset($q['question'], $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], $q['correct'])) {
                    $stmt_q->execute([
                        $new_quiz_id, 
                        $q['question'], 
                        $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], 
                        strtoupper($q['correct'])
                    ]);
                    $count_success++;
                }
            }

            // Trừ tiền khi đã lưu thành công
            if ($role != 'admin') {
                $conn->prepare("UPDATE users SET credits = credits - ? WHERE user_id = ?")->execute([$cost, $user_id]);
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            die("<script>alert('Lỗi lưu Database AI: ". $e->getMessage() ."');history.back();</script>");
        }
    }

    if ($role == 'admin') {
        echo "<script>alert('Tạo bài thi thành công! Đã tạo $count_success câu hỏi.'); window.location.href='create_quiz_ai.php';</script>";
    } else {
        echo "<script>alert('Tạo bài thi thành công! Đã tạo $count_success câu hỏi.'); window.location.href='quiz.php?quiz_id=$new_quiz_id';</script>";
    }
    exit;
}
?>

<style>
    .mode-card { border: 2px solid #eee; border-radius: 10px; padding: 15px; cursor: pointer; transition: 0.3s; display: flex; gap: 15px; align-items: flex-start; margin-bottom: 15px; }
    .mode-card:hover { border-color: #3498db; background: #f4f9f9; }
    .mode-card input[type="radio"] { margin-top: 5px; transform: scale(1.2); }
    .mode-active { border-color: #3498db; background: #ebf5fb; }
</style>

<div style="margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    
    <div style="text-align: center; margin-bottom: 25px;">
        <h2 style="color: var(--primary-color); margin-bottom: 10px;"><i class="fas fa-magic"></i> Tạo Đề Thi Tự Động</h2>
        <?php if ($role == 'admin'): ?>
            <p style="color: #27ae60; font-weight: bold; background: #e8f8f5; display: inline-block; padding: 5px 15px; border-radius: 20px;">Admin - Miễn phí toàn bộ</p>
        <?php else: ?>
            <p style="color: #e67e22; font-weight: bold; background: #fef5e7; display: inline-block; padding: 5px 15px; border-radius: 20px;">Số dư hiện tại: <?= $credits ?> Credits</p>
        <?php endif; ?>
    </div>

    <form method="POST" onsubmit="document.getElementById('loading-overlay').style.display = 'flex';" id="autoQuizForm">
        
        <label style="font-weight: bold; display: block; margin-bottom: 10px;"><i class="fas fa-cogs"></i> Chọn chế độ tạo đề:</label>
        
        <label class="mode-card mode-active" id="card_simple">
            <input type="radio" name="quiz_mode" value="simple" checked onchange="toggleMode()">
            <div>
                <strong style="color: #2c3e50; font-size: 1.1rem;">Cơ bản (Khuyên dùng)</strong>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 0.9rem;">Hệ thống tự bốc ngẫu nhiên từ vựng để hỏi nghĩa và phiên âm. Nhanh và hiệu quả.</p>
                <span style="display: inline-block; margin-top: 8px; font-weight: bold; color: #27ae60; background: #eafaf1; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;"><i class="fas fa-coins"></i> Chi phí: Miễn phí</span>
            </div>
        </label>

        <label class="mode-card" id="card_ai">
            <input type="radio" name="quiz_mode" value="ai" onchange="toggleMode()">
            <div>
                <strong style="color: #8e44ad; font-size: 1.1rem;"><i class="fas fa-robot"></i> Đa dạng bằng AI (Mới)</strong>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 0.9rem;">AI tạo các câu hỏi điền từ vào ngữ cảnh, tìm từ đồng nghĩa/trái nghĩa siêu thực tế.</p>
                <span style="display: inline-block; margin-top: 8px; font-weight: bold; color: #e74c3c; background: #fdedec; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;"><i class="fas fa-coins"></i> Chi phí: 2 Credits</span>
            </div>
        </label>

        <div style="margin-bottom: 25px; margin-top: 25px;">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold;">Tên bài thi (tùy chọn):</label>
                <input type="text" name="quiz_title_input" placeholder="VD: Bài test Ôn tập..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: medium; margin-top: 5px;">
            </div>
            
            <label style="font-weight: bold;">Chọn chủ đề:</label>
            <?php if ($role == 'admin'): ?>
                <select name="topic_id" id="topicSelect" onchange="updateQuestionOptions()" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: medium; margin-top: 5px;">
                    <option value="all">🌐 Tổng hợp tất cả chủ đề</option>
                    <?php foreach ($topics as $t): ?>
                        <option value="<?= $t['topic_id'] ?>"><?= htmlspecialchars($t['topic_name']) ?> (<?= $t['vocab_count'] ?> từ)</option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <select name="topic_id" id="topicSelect" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: medium; background: #f1f1f1; color: #555; pointer-events: none; margin-top: 5px;" tabindex="-1">
                    <option value="all" selected>🌐 Tổng hợp tất cả chủ đề</option>
                </select>
                <small style="color: #e67e22; font-style: italic; margin-top: 5px; display: block;"><i class="fas fa-info-circle"></i> Học viên chỉ được tạo bài tổng hợp để ôn toàn diện.</small>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 25px;">
            <label style="font-weight: bold;">Số lượng câu hỏi:</label>
            <select name="num_questions" id="numSelect" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: medium; margin-top: 5px;">
            </select>
            <small id="ai_warning" style="display: none; color: #8e44ad; font-style: italic; margin-top: 5px;"><i class="fas fa-lightbulb"></i> Lưu ý: Số câu hỏi tối đa bằng AI bị giới hạn ở 30 câu để đảm bảo chất lượng và tránh lỗi.</small>
        </div>

        <button type="submit" name="generate_quiz" id="btnSubmit" onclick="return confirmCost()" class="btn" style="width: 100%; padding: 12px; font-size: 1.1rem; background: var(--primary-color); color: white; border: none; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-bolt"></i> Tạo Đề Ngay (Miễn phí)
        </button>
    </method=>
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
<script>
const topicsData = <?= json_encode($topics) ?>;

function toggleMode() {
    let isAi = document.querySelector('input[name="quiz_mode"]:checked').value === 'ai';
    
    // Đổi màu Card
    document.getElementById('card_simple').classList.toggle('mode-active', !isAi);
    document.getElementById('card_ai').classList.toggle('mode-active', isAi);

    // Cập nhật cảnh báo và Nút
    document.getElementById('ai_warning').style.display = isAi ? 'block' : 'none';
    document.getElementById('btnSubmit').innerHTML = isAi 
        ? '<i class="fas fa-robot"></i> Nhờ AI Tạo Đề (2 Credits)' 
        : '<i class="fas fa-bolt"></i> Tạo Đề Ngay (Miễn phí)';
        
    updateQuestionOptions();
}

function updateQuestionOptions() {
    const topicId = document.getElementById('topicSelect').value;
    const numSelect = document.getElementById('numSelect');
    let isAi = document.querySelector('input[name="quiz_mode"]:checked').value === 'ai';
    
    numSelect.innerHTML = '';

    if (topicId === 'all') {
        if(isAi) {
            numSelect.add(new Option('10 câu (Đề ngắn)', 10));
            numSelect.add(new Option('20 câu (Đề tiêu chuẩn)', 20));
            numSelect.add(new Option('30 câu (Tối đa khả năng AI)', 30));
        } else {
            numSelect.add(new Option('50 câu (Tiêu chuẩn)', 50));
            numSelect.add(new Option('100 câu (Thử thách cao)', 100));
            numSelect.add(new Option('200 câu (Cực khó)', 200));
        }
        numSelect.selectedIndex = 0; 
    } else {
        const topic = topicsData.find(t => t.topic_id == topicId);
        const maxVocab = topic ? parseInt(topic.vocab_count) : 0;

        if (maxVocab === 0) {
            numSelect.add(new Option('Chưa có từ vựng', 0));
            return;
        }

        const baseOptions = [10, 15, 20];
        baseOptions.forEach(num => {
            if (isAi && num <= 30) {
                numSelect.add(new Option(`${num} câu`, num));
            } else if (!isAi && maxVocab > num) {
                numSelect.add(new Option(`${num} câu`, num));
            }
        });

        if(!isAi) {
            numSelect.add(new Option(`Tối đa (${maxVocab} câu)`, maxVocab));
            numSelect.selectedIndex = numSelect.options.length - 1;
        } else {
            if (maxVocab > 0 && maxVocab <= 30 && !baseOptions.includes(maxVocab)) {
                numSelect.add(new Option(`Bám sát kho từ (${maxVocab} câu)`, maxVocab));
            }
        }
    }
}

function confirmCost() {
    let isAi = document.querySelector('input[name="quiz_mode"]:checked').value === 'ai';
    
    // Nếu là Cơ bản thì miễn phí -> Bỏ qua Confirm
    if (!isAi) return true;

    <?php if ($role != 'admin'): ?>
        return confirm('Bạn sẽ bị trừ 2 Credits để nhờ AI tạo đề thi đa dạng. Chấp nhận?');
    <?php else: ?>
        return true;
    <?php endif; ?>
}

updateQuestionOptions();
</script>

<?php include '../../includes/footer.php'; ?>