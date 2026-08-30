CREATE DATABASE IF NOT EXISTS english_app;
USE english_app;

-- USERS
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    email VARCHAR(150),
    phone VARCHAR(11),
    password VARCHAR(255),
    full_name VARCHAR(100),
    role ENUM('admin','student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    credits INT DEFAULT 0,
    reset_token VARCHAR(255),
    reset_expires DATETIME
);

-- TOPICS
CREATE TABLE topics (
    topic_id INT AUTO_INCREMENT PRIMARY KEY,
    topic_name VARCHAR(100),
    description TEXT,
    image VARCHAR(255),
    views INT DEFAULT 0
);

-- QUIZZES
CREATE TABLE quizzes (
    quiz_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    topic_id INT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE
);

-- QUESTIONS
CREATE TABLE questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT,
    content TEXT,
    option_a VARCHAR(200),
    option_b VARCHAR(200),
    option_c VARCHAR(200),
    option_d VARCHAR(200),
    correct_answer CHAR(1),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
);

-- VOCABULARIES
CREATE TABLE vocabularies (
    vocab_id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT,
    word VARCHAR(100),
    word_type VARCHAR(50),
    meaning VARCHAR(255),
    pronunciation VARCHAR(100),
    audio_url VARCHAR(255),
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE
);

-- READINGS
CREATE TABLE readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT,
    title VARCHAR(255),
    level VARCHAR(20),
    content TEXT,
    content_vi TEXT,
    vocab_data LONGTEXT,
    quiz_data LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    ip_address VARCHAR(45),
    views INT DEFAULT 0,
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- TEST RESULTS
CREATE TABLE test_results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    quiz_id INT,
    score FLOAT,
    test_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
);

-- TRANSACTIONS
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_code BIGINT,
    amount INT,
    credits INT,
    status VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);