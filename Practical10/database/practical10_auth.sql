-- Practical 10: role support and secure remember-me tokens
USE studenthub;

-- Adds a role to existing Practical 9 users. Existing users become students by default.
ALTER TABLE users ADD COLUMN role ENUM('student','admin') NOT NULL DEFAULT 'student';

CREATE TABLE IF NOT EXISTS remember_tokens (
    token_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    selector CHAR(18) NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_remember_user (user_id),
    INDEX idx_remember_expiry (expires_at)
) ENGINE=InnoDB;

-- Optional: after importing, change one chosen user's role to admin in phpMyAdmin.
-- Example (replace the username with your own registered username):
-- UPDATE users SET role = 'admin' WHERE username = 'your_username';
