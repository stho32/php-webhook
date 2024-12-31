-- Create the main table for storing repository push information
CREATE TABLE IF NOT EXISTS LastPushForRepository (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    repository VARCHAR(255) NOT NULL,
    LastPushDateTime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_repo (username, repository)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create stored procedure for saving push information
DELIMITER //

CREATE PROCEDURE SavePush(
    IN p_username VARCHAR(255),
    IN p_repository VARCHAR(255)
)
BEGIN
    INSERT INTO LastPushForRepository (username, repository, LastPushDateTime)
    VALUES (p_username, p_repository, CURRENT_TIMESTAMP)
    ON DUPLICATE KEY UPDATE
        LastPushDateTime = CURRENT_TIMESTAMP;
END //

DELIMITER ;
