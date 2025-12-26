CREATE TABLE IF NOT EXISTS `analysis_reports` (
    `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
    `ticker` VARCHAR(10) NOT NULL,
    `analysis_date` DATE NOT NULL,
    `report_content` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reports_ticker_date` (`ticker`, `analysis_date`)
);
