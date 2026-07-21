-- データベースの作成
CREATE DATABASE IF NOT EXISTS study_app CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE study_app;

-- メンバーB担当：課題管理テーブル
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(50) NOT NULL,
    deadline DATE NOT NULL,
    required_time INT NOT NULL,
    difficulty INT NOT NULL,
    is_completed TINYINT(1) DEFAULT 0
);

-- メンバーD担当：資源・ステータステーブル
CREATE TABLE IF NOT EXISTS user_status (
    id INT PRIMARY KEY,
    materials INT DEFAULT 0,
    food INT DEFAULT 0,
    math_level INT DEFAULT 1,
    english_level INT DEFAULT 1,
    physics_level INT DEFAULT 1,
    chemistry_level INT DEFAULT 1,
    information_level INT DEFAULT 1
);

-- メンバーE担当：ボトルメールテーブル
CREATE TABLE IF NOT EXISTS bottle_mails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT,
    canvas_data LONGTEXT, -- お絵描きデータ保存用
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
