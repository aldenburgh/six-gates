-- ============================================
-- CORE PORTFOLIO TABLES
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `market_context`;
DROP TABLE IF EXISTS `notification_preferences`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `stock_analyses`;
DROP TABLE IF EXISTS `watchlist`;
DROP TABLE IF EXISTS `milestones`;
DROP TABLE IF EXISTS `goal_settings`;
DROP TABLE IF EXISTS `income_snapshots`;
DROP TABLE IF EXISTS `dividend_payments`;
DROP TABLE IF EXISTS `execution_log`;
DROP TABLE IF EXISTS `recommendations`;
DROP TABLE IF EXISTS `positions`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `positions` (
    `id` CHAR(36) NOT NULL,
    `ticker` VARCHAR(10) NOT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `portfolio_type` ENUM('growth', 'dividend') NOT NULL,
    
    -- Holdings
    `shares` DECIMAL(12,4) NOT NULL,
    `average_cost` DECIMAL(12,4) NOT NULL,
    `cost_basis` DECIMAL(14,2) NOT NULL,
    
    -- Current values (updated by market data job)
    `current_price` DECIMAL(12,4) NULL,
    `market_value` DECIMAL(14,2) NULL,
    `gain_loss` DECIMAL(14,2) NULL,
    `gain_loss_percent` DECIMAL(8,4) NULL,
    
    -- Growth portfolio fields
    `quality_tier` ENUM('exceptional', 'high_quality', 'good_quality', 'acceptable') NULL,
    `profit_target_percent` DECIMAL(5,2) NULL,
    `stop_loss_percent` DECIMAL(5,2) NULL,
    
    -- Dividend portfolio fields
    `dividend_tier` ENUM('aristocrat', 'grower', 'value', 'caution') NULL,
    `annual_dividend_income` DECIMAL(12,2) NULL,
    `yield_on_cost` DECIMAL(5,4) NULL,
    `next_ex_date` DATE NULL,
    `payment_frequency` ENUM('monthly', 'quarterly', 'semi_annual', 'annual') NULL,
    
    -- Status
    `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    `opened_at` TIMESTAMP NOT NULL,
    `closed_at` TIMESTAMP NULL,
    
    -- Metadata
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_pos_ticker` (`ticker`),
    INDEX `idx_pos_portfolio` (`portfolio_type`),
    INDEX `idx_pos_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- RECOMMENDATION & EXECUTION TABLES
-- ============================================

CREATE TABLE `recommendations` (
    `id` CHAR(36) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    
    -- Action
    `action` ENUM('buy', 'sell') NOT NULL,
    `portfolio_type` ENUM('growth', 'dividend') NOT NULL,
    `ticker` VARCHAR(10) NOT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `sector` VARCHAR(100) NULL,
    
    -- Quantity recommendation
    `recommended_shares` INT UNSIGNED NOT NULL,
    `current_price` DECIMAL(12,4) NOT NULL,
    `estimated_cost` DECIMAL(14,2) NULL,
    `estimated_proceeds` DECIMAL(14,2) NULL,
    
    -- Order type recommendation
    `order_type` ENUM('market', 'limit') NOT NULL,
    `limit_price` DECIMAL(12,4) NULL,
    `limit_valid_until` DATE NULL,
    `order_type_reason` VARCHAR(500) NOT NULL,
    
    -- For SELL recommendations
    `position_id` CHAR(36) NULL,
    `position_shares` INT UNSIGNED NULL,
    `sell_percentage` INT UNSIGNED NULL,
    `sell_reason` VARCHAR(100) NULL,
    
    -- Quality context
    `quality_tier` VARCHAR(20) NULL,
    `dividend_tier` VARCHAR(20) NULL,
    
    -- Valuation context
    `fair_value` DECIMAL(12,4) NULL,
    `discount_percent` DECIMAL(5,2) NULL,
    
    -- Income impact (for dividend)
    `current_yield` DECIMAL(5,4) NULL,
    `expected_annual_income` DECIMAL(12,2) NULL,
    `income_impact` DECIMAL(12,2) NULL,
    `goal_impact_percent` DECIMAL(5,4) NULL,
    
    -- Rotation (for growth SELL)
    `rotation_amount` DECIMAL(14,2) NULL,
    `suggested_rotation_stocks` JSON NULL,
    
    -- Narrative
    `narrative_summary` TEXT NOT NULL,
    `full_narrative` LONGTEXT NULL,
    `urgency` ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    
    -- Status tracking
    `status` ENUM('pending', 'approved', 'denied', 'expired', 'executed') NOT NULL DEFAULT 'pending',
    `approved_at` TIMESTAMP NULL,
    `denied_at` TIMESTAMP NULL,
    `denial_reason` VARCHAR(500) NULL,
    `denial_notes` TEXT NULL,
    `executed_at` TIMESTAMP NULL,
    
    PRIMARY KEY (`id`),
    INDEX `idx_rec_status` (`status`),
    INDEX `idx_rec_ticker` (`ticker`),
    INDEX `idx_rec_expires` (`expires_at`),
    INDEX `idx_rec_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `execution_log` (
    `id` CHAR(36) NOT NULL,
    `recommendation_id` CHAR(36) NOT NULL,
    `position_id` CHAR(36) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Trade details
    `ticker` VARCHAR(10) NOT NULL,
    `action` ENUM('buy', 'sell') NOT NULL,
    `portfolio_type` ENUM('growth', 'dividend') NOT NULL,
    
    -- What was recommended
    `recommended_shares` INT UNSIGNED NOT NULL,
    `recommended_price` DECIMAL(12,4) NOT NULL,
    `recommended_order_type` ENUM('market', 'limit') NOT NULL,
    `recommended_total` DECIMAL(14,2) NOT NULL,
    
    -- What was actually executed
    `actual_shares` INT UNSIGNED NOT NULL,
    `actual_price` DECIMAL(12,4) NOT NULL,
    `actual_total` DECIMAL(14,2) NOT NULL,
    `commission` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `execution_date` DATE NOT NULL,
    `broker` VARCHAR(100) NULL,
    
    -- Variances
    `shares_variance` INT NOT NULL,
    `shares_variance_percent` DECIMAL(8,4) NOT NULL,
    `price_variance` DECIMAL(12,4) NOT NULL,
    `price_variance_percent` DECIMAL(8,4) NOT NULL,
    `total_variance` DECIMAL(14,2) NOT NULL,
    `total_variance_percent` DECIMAL(8,4) NOT NULL,
    
    -- Notes
    `notes` TEXT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`recommendation_id`) REFERENCES `recommendations`(`id`),
    INDEX `idx_exec_date` (`execution_date`),
    INDEX `idx_exec_ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- DIVIDEND TABLES
-- ============================================

CREATE TABLE `dividend_payments` (
    `id` CHAR(36) NOT NULL,
    `position_id` CHAR(36) NOT NULL,
    `ticker` VARCHAR(10) NOT NULL,
    
    `ex_date` DATE NOT NULL,
    `record_date` DATE NULL,
    `payment_date` DATE NOT NULL,
    
    `shares_held` DECIMAL(12,4) NOT NULL,
    `dividend_per_share` DECIMAL(10,6) NOT NULL,
    `gross_amount` DECIMAL(12,2) NOT NULL,
    `withholding_tax` DECIMAL(12,2) DEFAULT 0,
    `net_amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'EUR',
    
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`),
    INDEX `idx_div_date` (`payment_date`),
    INDEX `idx_div_ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- INCOME & GOAL TABLES
-- ============================================

CREATE TABLE `income_snapshots` (
    `id` CHAR(36) NOT NULL,
    `snapshot_date` DATE NOT NULL,
    
    `annual_income` DECIMAL(12,2) NOT NULL,
    `monthly_income` DECIMAL(12,2) NOT NULL,
    `target_monthly` DECIMAL(12,2) NOT NULL,
    `progress_percent` DECIMAL(5,2) NOT NULL,
    
    `dividend_positions_count` INT UNSIGNED NOT NULL,
    `portfolio_value` DECIMAL(14,2) NOT NULL,
    `average_yield` DECIMAL(5,4) NOT NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_snapshot_date` (`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `goal_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `base_monthly_target` DECIMAL(12,2) NOT NULL DEFAULT 20000,
    `base_year` INT NOT NULL DEFAULT 2025,
    `target_years` INT NOT NULL DEFAULT 10,
    `inflation_rate` DECIMAL(5,4) NOT NULL DEFAULT 0.025,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `milestones` (
    `id` CHAR(36) NOT NULL,
    `milestone_percent` INT UNSIGNED NOT NULL,
    `reached_at` TIMESTAMP NOT NULL,
    `monthly_income_at_milestone` DECIMAL(12,2) NOT NULL,
    `notified` TINYINT(1) DEFAULT 0,
    
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_milestone_pct` (`milestone_percent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- WATCHLIST & ANALYSIS TABLES
-- ============================================

CREATE TABLE `watchlist` (
    `id` CHAR(36) NOT NULL,
    `ticker` VARCHAR(10) NOT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `portfolio_type` ENUM('growth', 'dividend') NOT NULL,
    
    `quality_tier` VARCHAR(20) NULL,
    `dividend_tier` VARCHAR(20) NULL,
    
    `buy_zone_low` DECIMAL(12,4) NULL,
    `buy_zone_high` DECIMAL(12,4) NULL,
    `fair_value` DECIMAL(12,4) NULL,
    
    `current_price` DECIMAL(12,4) NULL,
    `in_buy_zone` TINYINT(1) DEFAULT 0,
    
    `notes` TEXT NULL,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_analyzed` TIMESTAMP NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_watchlist_ticker` (`ticker`, `portfolio_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `stock_analyses` (
    `id` CHAR(36) NOT NULL,
    `ticker` VARCHAR(10) NOT NULL,
    `analysis_date` DATE NOT NULL,
    
    -- Gate results
    `gate_1_passed` TINYINT(1) NULL,
    `gate_1_score` DECIMAL(3,2) NULL,
    `gate_1_data` JSON NULL,
    
    `gate_1_5_moat_type` VARCHAR(50) NULL,
    `gate_1_5_durability` ENUM('high', 'medium', 'low', 'none') NULL,
    `gate_1_5_data` JSON NULL,
    
    `gate_2_passed` TINYINT(1) NULL,
    `gate_2_roic` DECIMAL(5,4) NULL,
    `gate_2_wacc` DECIMAL(5,4) NULL,
    `gate_2_data` JSON NULL,
    
    `gate_2_5_passed` TINYINT(1) NULL,
    `gate_2_5_debt_ebitda` DECIMAL(5,2) NULL,
    `gate_2_5_data` JSON NULL,
    
    `gate_2_75_category` VARCHAR(20) NULL,
    `gate_2_75_data` JSON NULL,
    
    `gate_3_passed` TINYINT(1) NULL,
    `gate_3_fcf_conversion` DECIMAL(5,4) NULL,
    `gate_3_data` JSON NULL,
    
    `gate_3_5_too_hard` TINYINT(1) NULL,
    `gate_3_5_data` JSON NULL,
    
    `gate_4_in_zone` TINYINT(1) NULL,
    `gate_4_fair_value` DECIMAL(12,4) NULL,
    `gate_4_data` JSON NULL,
    
    `gate_5_score` DECIMAL(3,2) NULL,
    `gate_5_data` JSON NULL,
    
    -- Dividend analysis (if applicable)
    `dividend_yield` DECIMAL(5,4) NULL,
    `dividend_growth_5yr` DECIMAL(5,4) NULL,
    `consecutive_years` INT UNSIGNED NULL,
    `fcf_payout_ratio` DECIMAL(5,4) NULL,
    `chowder_number` DECIMAL(5,2) NULL,
    `dividend_tier` VARCHAR(20) NULL,
    
    -- Quality classification
    `quality_tier` VARCHAR(20) NULL,
    `recommended_portfolio` ENUM('growth', 'dividend', 'avoid') NULL,
    
    -- Narrative
    `narrative` LONGTEXT NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_analysis_ticker_date` (`ticker`, `analysis_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- NOTIFICATION TABLES
-- ============================================

CREATE TABLE `notifications` (
    `id` CHAR(36) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `urgency` ENUM('critical', 'high', 'medium', 'low', 'info') NOT NULL,
    
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `data` JSON NULL,
    
    `recommendation_id` CHAR(36) NULL,
    
    -- Channels
    `channel` ENUM('whatsapp', 'push', 'both') NOT NULL,
    
    -- WhatsApp tracking
    `whatsapp_message_id` VARCHAR(100) NULL,
    `whatsapp_sent_at` TIMESTAMP NULL,
    `whatsapp_delivered_at` TIMESTAMP NULL,
    
    -- Push tracking
    `push_message_id` VARCHAR(100) NULL,
    `push_sent_at` TIMESTAMP NULL,
    `push_delivered_at` TIMESTAMP NULL,
    
    -- Status
    `status` ENUM('pending', 'sent', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
    `error_message` TEXT NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_notif_type` (`type`),
    INDEX `idx_notif_status` (`status`),
    INDEX `idx_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `notification_preferences` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    `whatsapp_enabled` TINYINT(1) DEFAULT 1,
    `whatsapp_number` VARCHAR(20) NOT NULL,
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


-- ============================================
-- MARKET CONTEXT TABLE
-- ============================================

CREATE TABLE `market_context` (
    `id` CHAR(36) NOT NULL,
    `context_date` DATE NOT NULL,
    
    -- Valuations
    `cape_ratio` DECIMAL(6,2) NULL,
    `cape_percentile` INT UNSIGNED NULL,
    `market_pe` DECIMAL(6,2) NULL,
    
    -- Credit
    `high_yield_spread` DECIMAL(5,2) NULL,
    `spread_percentile` INT UNSIGNED NULL,
    
    -- Sentiment
    `vix` DECIMAL(5,2) NULL,
    `put_call_ratio` DECIMAL(4,2) NULL,
    `aaii_bull_bear_spread` DECIMAL(5,2) NULL,
    
    -- Cycle assessment
    `cycle_position` ENUM('early', 'mid', 'late') NULL,
    `recommended_cash_percent` INT UNSIGNED NULL,
    `position_size_multiplier` DECIMAL(3,2) NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_context_date` (`context_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- AUDIT LOG TABLE (ALL OPERATIONS LOGGED)
-- ============================================

CREATE TABLE `audit_log` (
    `id` CHAR(36) NOT NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- What happened
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` CHAR(36) NULL,
    
    -- Who/what did it
    `source` ENUM('api', 'whatsapp', 'ipad', 'system', 'scheduler') NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    
    -- Details
    `request_data` JSON NULL,
    `response_data` JSON NULL,
    `changes` JSON NULL,
    
    -- Status
    `success` TINYINT(1) NOT NULL,
    `error_message` TEXT NULL,
    
    PRIMARY KEY (`id`),
    INDEX `idx_audit_timestamp` (`timestamp`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
