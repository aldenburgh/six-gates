-- ============================================
-- MIGRATION 003: SLACK INTEGRATION (V7)
-- Replaces WhatsApp with Slack
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Modify Notifications Table
-- We will recreate it to match V7 spec + Slack replacement
DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
    `id` CHAR(36) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `urgency` ENUM('critical', 'high', 'medium', 'low', 'info') NOT NULL,
    
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `data` JSON NULL,
    
    `recommendation_id` CHAR(36) NULL,
    `alert_id` CHAR(36) NULL,
    
    -- Channels (Updated to Slack)
    `channel` ENUM('slack', 'push', 'both') NOT NULL,
    
    -- Slack tracking
    `slack_message_ts` VARCHAR(50) NULL, -- formatted timestamp ID from Slack
    `slack_channel` VARCHAR(50) NULL,
    `sent_at` TIMESTAMP NULL,
    `delivered_at` TIMESTAMP NULL,
    
    -- Push tracking
    `push_message_id` VARCHAR(100) NULL,
    
    -- Status
    `status` ENUM('pending', 'sent', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
    `error_message` TEXT NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_notif_type` (`type`),
    INDEX `idx_notif_status` (`status`),
    INDEX `idx_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. Modify Preferences Table
DROP TABLE IF EXISTS `notification_preferences`;

CREATE TABLE `notification_preferences` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Slack Settings
    `slack_enabled` TINYINT(1) DEFAULT 1,
    `slack_notification_channel` VARCHAR(50) NOT NULL DEFAULT '#six-gates-alerts',
    `slack_portfolio_channel` VARCHAR(50) NOT NULL DEFAULT '#six-gates-portfolio',
    
    `push_enabled` TINYINT(1) DEFAULT 1,
    `push_device_token` VARCHAR(255) NULL,
    
    -- Notification types
    `notify_buy_recommendations` TINYINT(1) DEFAULT 1,
    `notify_sell_alerts` TINYINT(1) DEFAULT 1,
    `notify_risk_warnings` TINYINT(1) DEFAULT 1,
    `notify_daily_summary` TINYINT(1) DEFAULT 1,
    `notify_weekly_progress` TINYINT(1) DEFAULT 1,
    `notify_ex_dates` TINYINT(1) DEFAULT 1,
    `notify_milestones` TINYINT(1) DEFAULT 1,
    
    -- Timing
    `daily_summary_time` TIME DEFAULT '18:00:00',
    `weekly_summary_day` ENUM('monday', 'sunday') DEFAULT 'sunday',
    `quiet_hours_start` TIME DEFAULT '22:00:00',
    `quiet_hours_end` TIME DEFAULT '07:00:00',
    `allow_urgent_during_quiet` TINYINT(1) DEFAULT 1,
    
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Update Audit Log Source Enum (if possible via modify, else ignore or recreate)
-- MySQL can alter enum
ALTER TABLE `audit_log` MODIFY COLUMN `source` ENUM('api', 'slack', 'ipad', 'cli', 'system', 'scheduler') NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- Seed default preferences
INSERT INTO `notification_preferences` (`slack_enabled`) VALUES (1);
