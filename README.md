# EnglishApp - Nền Tảng Học Tiếng Anh AI

EnglishApp là một nền tảng học tiếng Anh **miễn phí/thu phí** (freemium) xây dựng bằng **PHP + MySQL**, tích hợp **tạo nội dung bằng AI (Google Gemini)** để sinh bài đọc, từ vựng và quiz tự động.

> ⚠️ Dự án được phát triển cho **mục đích học tập & demo**. Nếu sử dụng trong môi trường thực tế/ thương mại, hãy kiểm tra lại bảo mật, cấu hình máy chủ và quy định sử dụng API.

---

## 🌟 Tính Năng Nổi Bật

### 🔍 Học tập & Luyện tập

- Trang chủ, tìm kiếm và điều hướng dễ dùng
- Quản lý chủ đề, từ vựng, bài đọc và quiz
- Quiz trắc nghiệm (4 lựa chọn) với điểm, lịch sử và random hóa câu hỏi
- Bài đọc tương tác: highlight từ vựng, tooltip nghĩa và phát âm

### 🤖 AI Auto-Generation (Google Gemini)

- Sinh **Bài đọc tiếng Anh + giải nghĩa + từ vựng + quiz** tự động
- Sinh **Quiz từ chủ đề** với 2 chế độ:
  - **Simple** (miễn phí)
  - **AI** (tốn credits, kèm giải thích)
- Hệ thống giới hạn miễn phí:
  - 1 bài đọc đầu tiên (tài khoản) hoặc tối đa 2 lần cho cùng IP
  - Sau đó: mỗi bài đọc/quizz AI tốn **2 credits**

### 💳 Credits & Thanh Toán

- Hệ thống credits tích hợp với chức năng “nạp credits”
- Quản trị viên có thể duyệt và theo dõi giao dịch
- Dùng credits để gọi API AI (Gemini)

### 👩‍💼 Quản Trị & Giáo Viên

- Bảng điều khiển admin thống kê (user, quiz, điểm)
- CRUD cho users, topics, quizzes, readings, vocabularies, questions, transactions
- Xuất dữ liệu CSV (UTF-8)

---

## 🧩 Kiến Trúc Dự Án (Thư mục chính)

```
EnglishApp/
├── index.php
├── search.php
├── theory.php
├── english_app.sql
├── admin/
│   ├── dashboard.php
│   ├── cruds/
│   │   ├── edit_question.php
│   │   ├── edit_quiz.php
│   │   ├── edit_reading.php
│   │   ├── edit_topic.php
│   │   ├── edit_vocab.php
│   │   ├── manage_questions.php
│   │   ├── manage_quizzes.php
│   │   ├── manage_readings.php
│   │   ├── manage_topics.php
│   │   ├── manage_transactions.php
│   │   ├── manage_users.php
│   │   └── manage_vocab.php
│   └── exports/
│       ├── export_stats.php
│       └── statistics.php
├── assets/
│   ├── css/style.css
│   ├── images/
│   └── script/script.js
├── auth/
│   ├── forgot_password.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── reset_password.php
├── config/
│   ├── config.php
│   └── db.php
├── includes/
│   ├── footer.php
│   ├── header.php
│   └── pagination_helper.php
├── modules/
│   ├── create_ai.php
│   ├── quiz/
│   │   ├── create_quiz_ai.php
│   │   ├── quiz_list.php
│   │   └── quiz.php
│   ├── reading/
│   │   ├── create_reading.php
│   │   ├── reading_list.php
│   │   └── reading.php
│   └── topics/
│       ├── topics_list.php
│       └── topics_vocab.php
├── payments/
│   ├── create_payment.php
│   └── topup.php
├── uploads/
└── user/
    ├── history.php
    ├── my_quizzes.php
    ├── profile.php
    └── result.php
```

---

## 🗄️ Cơ Sở Dữ Liệu (Schema)

Các bảng chính:

- `users` (tài khoản, credits, role)
- `topics` (chủ đề học)
- `vocabularies` (từ vựng)
- `readings` (bài đọc + dữ liệu AI)
- `quizzes` (bài kiểm tra)
- `questions` (câu hỏi trắc nghiệm)
- `test_results` (lịch sử làm quiz)
- `transactions` (lịch sử nạp credits)

> File tạo schema: `english_app.sql`

---

## ⚙️ Yêu Cầu Môi Trường

### Phần mềm yêu cầu

- PHP 7.4+ (hoặc 8.x)
- MySQL 5.7+ / MariaDB 10.4+
- Apache/Nginx (mod_php hoặc PHP-FPM)

### Extension PHP cần có

- `pdo`, `pdo_mysql`
- `json`
- `session`
- `curl`
- `mbstring`

---

## 🛠️ Cài Đặt & Chạy Ứng Dụng

### 1) Clone source

```bash
git clone <repository-url>
cd EnglishApp
```

### 2) Tạo database và import schema

```sql
CREATE DATABASE english_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
mysql -u root -p english_app < english_app.sql
```

### 3) Tạo file `.env` ở gốc dự án

Tạo file `.env` (cùng cấp `index.php`) với nội dung mẫu:

```
DB_HOST=localhost
DB_NAME=english_app
DB_USER=root
DB_PASS=
BASE_URL=http://localhost/EnglishApp
GEMINI_API_KEY=your-gemini-api-key-here
```

> 📌 Xin lưu ý: mã nguồn hiện tại không bao gồm `.env`, vì vậy bạn cần tự tạo.

### 4) Truy cập ứng dụng

Mở trình duyệt và vào:

```
http://localhost/EnglishApp/
```

---

## 🔐 Cấu hình Admin

Ứng dụng không có tài khoản admin mặc định. Sau khi đăng ký (register) một tài khoản, bạn có thể cấp quyền `admin` thủ công:

```sql
UPDATE users SET role='admin' WHERE email='youremail@example.com';
```

---

## 🔍 Lưu ý về AI & Credits

- Các tính năng AI (tạo bài đọc/quiz/từ vựng) sử dụng **Google Gemini API**
- Cần **GEMINI_API_KEY** trong `.env`
- Credit được trừ tự động khi dùng các tính năng AI (thường là 2 credits / lần)
- Người dùng mới được **1 lần tạo bài đọc miễn phí** (và mỗi IP tối đa 2 lần miễn phí)

---

## 📌 Gợi ý bảo mật (Production)

- Chạy qua **HTTPS**
- Ẩn `.env` (không commit lên Git)
- Đặt quyền file/folder hợp lý (đặc biệt `uploads/`)
- Tắt hiển thị lỗi PHP trong production

---

## 🤝 Đóng Góp

Dự án này được thiết kế cho mục đích học tập. Nếu bạn muốn đóng góp:

1. Fork repo
2. Tạo nhánh mới: `git checkout -b feature/your-feature`
3. Commit & push
4. Tạo Pull Request

---

## 📄 Giấy Phép

Dự án được phát triển cho mục đích học tập và demo. Sử dụng thương mại cần có sự đồng ý của tác giả.

---

## 👨‍💻 Tác Giả

- **Developer**: Tạ Nguyễn Hoàng Kiệt
- **Phiên bản**: 1.0.0
- **Cập nhật lần cuối**: March 2026
