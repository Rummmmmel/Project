-- Run this in phpMyAdmin (SQL tab) or import it.
CREATE DATABASE IF NOT EXISTS campus_jo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_jo;

CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('student','faculty','utility','guard','admin') NOT NULL DEFAULT 'student',
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ADMIN ACCOUNT (can sign in with Google). Change the email to the real admin's @rtu.edu.ph Google account.
-- The password value is not a real hash on purpose: this account can only sign in through Google.
INSERT IGNORE INTO users (full_name, email, password_hash, role)
VALUES ('BFMU Administrator', 'bfmu.admin@rtu.edu.ph', 'GOOGLE_SIGN_IN_ONLY', 'admin');

-- ============ REPORTS, NOTIFICATIONS, AUDIT LOGS ============
CREATE TABLE IF NOT EXISTS reports (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    control_no     VARCHAR(20)  NOT NULL UNIQUE,
    user_id        INT          NOT NULL,
    location       VARCHAR(150) NOT NULL,
    description    TEXT         NOT NULL,
    category       VARCHAR(30)  NOT NULL,                 -- chosen by the reporter
    ai_category    VARCHAR(30)  NULL,                     -- suggested by the AI
    ai_priority    ENUM('Low','Medium','High') NULL,
    ai_team        VARCHAR(60)  NULL,
    ai_reason      VARCHAR(255) NULL,
    final_category VARCHAR(30)  NULL,                     -- confirmed by the admin
    final_priority ENUM('Low','Medium','High') NULL,
    assigned_team  VARCHAR(60)  NULL,
    target_date    DATE         NULL,
    status         ENUM('Pending','Assigned','In-Progress','Complete') NOT NULL DEFAULT 'Pending',
    photo          VARCHAR(255) NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    message    VARCHAR(255) NOT NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NULL,
    action     VARCHAR(80)  NOT NULL,
    record     VARCHAR(30)  NOT NULL,
    details    VARCHAR(255) NOT NULL,
    outcome    ENUM('Success','Blocked') NOT NULL DEFAULT 'Success',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);
