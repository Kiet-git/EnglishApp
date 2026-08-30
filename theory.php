<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'includes/header.php';
?>

<style>
    .theory-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
        line-height: 1.6;
        color: #333;
    }
    .theory-section {
        background: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        border-top: 4px solid var(--primary-color, #3498db);
    }
    .theory-section h3 {
        color: var(--primary-color, #3498db);
        margin-top: 0;
        border-bottom: 2px dashed #eee;
        padding-bottom: 10px;
    }
    .vocab-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .vocab-table th, .vocab-table td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }
    .vocab-table th {
        background-color: #f8f9fa;
        color: #2c3e50;
    }
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 0.85rem;
    }
    .badge-n { background: #e8f8f5; color: #27ae60; border: 1px solid #c8e6c9; }
    .badge-v { background: #fdedec; color: #e74c3c; border: 1px solid #fadbd8; }
    .badge-adj { background: #fef9e7; color: #f39c12; border: 1px solid #fcf3cf; }
    .badge-adv { background: #ebf5fb; color: #2980b9; border: 1px solid #d4e6f1; }
</style>

<div class="theory-container">
    <h2 style="text-align: center; margin-bottom: 30px; color: var(--primary-color)"><i class="fas fa-book-open"></i> Lý Thuyết Cơ Bản & Cẩm Nang</h2>

    <div class="theory-section">
        <h3><i class="fas fa-tags"></i> 1. Các loại từ cơ bản trong Tiếng Anh</h3>
        <p>Khi học từ vựng, việc nắm rõ loại từ sẽ giúp bạn đặt câu chính xác và hiểu rõ ngữ pháp. Dưới đây là các loại từ phổ biến nhất:</p>
        
        <table class="vocab-table">
            <tr>
                <th style="width: 25%">Loại từ (Viết tắt)</th>
                <th style="width: 55%;">Chức năng</th>
                <th style="width:20%">Ví dụ</th>
            </tr>
            <tr>
                <td><span class="badge badge-n">Noun: Danh từ (n)</span></td>
                <td>Chỉ người, vật, sự việc, hiện tượng, khái niệm. Thường làm chủ ngữ hoặc tân ngữ trong câu.</td>
                <td><i>car, water, happiness, teacher</i></td>
            </tr>
            <tr>
                <td><span class="badge badge-v">Verb: Động từ (v)</span></td>
                <td>Chỉ hành động hoặc trạng thái của sự vật.</td>
                <td><i>run, eat, think, is/are</i></td>
            </tr>
            <tr>
                <td><span class="badge badge-adj">Adjective: Tính từ (adj)</span></td>
                <td>Miêu tả đặc điểm, tính chất của danh từ. Thường đứng trước danh từ hoặc sau động từ to-be.</td>
                <td><i>beautiful, tall, hot, expensive</i></td>
            </tr>
            <tr>
                <td><span class="badge badge-adv">Adverb: Trạng từ (adv)</span></td>
                <td>Bổ nghĩa cho động từ, tính từ hoặc một trạng từ khác (thường chỉ cách thức, thời gian, mức độ).</td>
                <td><i>quickly, very, yesterday, always</i></td>
            </tr>
        </table>
    </div>

    <div class="theory-section">
        <h3><i class="fas fa-globe-americas"></i> 2. Sự khác biệt giữa Tiếng Anh Mỹ (US) và Anh (UK)</h3>
        <p>Có thể bạn sẽ thấy một từ có 2 cách đọc hoặc 2 cách viết khác nhau. Đó là do sự khác biệt giữa chuẩn Mỹ và chuẩn Anh. Dưới đây là những điểm khác biệt dễ nhận thấy nhất:</p>

        <h4 style="color: #8e44ad; margin-top: 20px;"><i class="fas fa-microphone-alt"></i> Khác biệt về Phát âm</h4>
        

[Image of English IPA chart]

        <ul>
            <li><b>Âm /r/ ở cuối từ:</b> 
                Người Mỹ (US) thường uốn lưỡi phát âm rất rõ chữ "r" ở cuối (VD: Car, Water). 
                Người Anh (UK) thường bỏ âm "r" này và kéo dài nguyên âm trước đó (đọc giống như /kɑː/, /ˈwɔːtə/).
            </li>
            <li style="margin-top: 10px;"><b>Âm /t/ ở giữa từ:</b>
                Người Mỹ hay biến âm /t/ giữa 2 nguyên âm thành âm /d/ nhẹ (VD: Water -> đọc giống "wa-đờ", Better -> "be-đờ").
                Người Anh phát âm chuẩn và rõ âm /t/ (VD: Water -> "wa-tờ").
            </li>
            <li style="margin-top: 10px;"><b>Âm /a/ ngắn:</b>
                Với các từ như <i>Ask, Dance, Fast</i>, người Mỹ thường đọc thiên về âm /æ/ (như "e" bẹt), trong khi người Anh đọc thiên về âm /ɑː/ (như "a" dài).
            </li>
        </ul>

        <h4 style="color: #e67e22; margin-top: 20px;"><i class="fas fa-spell-check"></i> Khác biệt về Từ vựng</h4>
        <table class="vocab-table">
            <tr>
                <th>Nghĩa Tiếng Việt</th>
                <th>Anh - Mỹ (US)</th>
                <th>Anh - Anh (UK)</th>
            </tr>
            <tr><td>Khoai tây chiên</td><td>French fries</td><td>Chips</td></tr>
            <tr><td>Bánh quy</td><td>Cookie</td><td>Biscuit</td></tr>
            <tr><td>Căn hộ</td><td>Apartment</td><td>Flat</td></tr>
            <tr><td>Bóng đá</td><td>Soccer</td><td>Football</td></tr>
            <tr><td>Kỳ nghỉ</td><td>Vacation</td><td>Holiday</td></tr>
        </table>
    </div>

</div>

<?php include 'includes/footer.php'; ?>