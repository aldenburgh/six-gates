-- Stocks Table
CREATE TABLE `stocks` (
    `ticker` VARCHAR(10) NOT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `sector` VARCHAR(100) NULL,
    `industry` VARCHAR(100) NULL,
    `exchange` VARCHAR(50) NULL,
    `currency` VARCHAR(10) DEFAULT 'USD',
    `is_active` BOOLEAN DEFAULT TRUE,
    `last_analyzed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ticker`),
    INDEX `idx_stocks_sector` (`sector`),
    INDEX `idx_stocks_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analysis Results Table
CREATE TABLE `analysis_results` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticker` VARCHAR(10) NOT NULL,
    `run_date` DATE NOT NULL,
    `gate_results` JSON NOT NULL,
    `target_price_zones` JSON NULL,
    `conviction_score` DECIMAL(5,2) NULL,
    `passed_gates` JSON NOT NULL,
    `is_latest` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_analysis_ticker` (`ticker`),
    INDEX `idx_analysis_date` (`run_date`),
    INDEX `idx_analysis_latest` (`ticker`, `is_latest`),
    FOREIGN KEY (`ticker`) REFERENCES `stocks` (`ticker`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Watchlist Table
CREATE TABLE `watchlists` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticker` VARCHAR(10) NOT NULL,
    `conviction_score` DECIMAL(5,2) NOT NULL,
    `fair_value` DECIMAL(10,2) NOT NULL,
    `accumulate_price` DECIMAL(10,2) NOT NULL,
    `buy_price` DECIMAL(10,2) NOT NULL,
    `strong_buy_price` DECIMAL(10,2) NOT NULL,
    `target_zones` JSON NOT NULL,
    `last_price` DECIMAL(10,2) NULL,
    `last_price_updated_at` TIMESTAMP NULL,
    `analysis_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_watchlist_ticker` (`ticker`),
    FOREIGN KEY (`ticker`) REFERENCES `stocks` (`ticker`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Positions Table
CREATE TABLE `positions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticker` VARCHAR(10) NOT NULL,
    `entry_date` DATE NOT NULL,
    `entry_price` DECIMAL(10,2) NOT NULL,
    `shares` DECIMAL(18,6) NOT NULL,
    `entry_analysis_id` INT UNSIGNED NULL,
    `cost_basis` DECIMAL(18,2) NOT NULL,
    `fair_value_at_entry` DECIMAL(10,2) NULL,
    `current_fair_value` DECIMAL(10,2) NULL,
    `profit_taking_rule` ENUM('none', 'hard_exit', 'tiered') DEFAULT 'hard_exit',
    `profit_threshold` DECIMAL(5,4) DEFAULT 0.20,
    `tiers_completed` INT UNSIGNED DEFAULT 0,
    `status` ENUM('open', 'partial_exit', 'closed') DEFAULT 'open',
    `exit_date` DATE NULL,
    `exit_price` DECIMAL(10,2) NULL,
    `exit_reason` VARCHAR(255) NULL,
    `realized_gain_loss` DECIMAL(18,2) NULL,
    `realized_gain_loss_pct` DECIMAL(10,4) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_positions_ticker` (`ticker`),
    INDEX `idx_positions_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Position Exits Table
CREATE TABLE `position_exits` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `position_id` INT UNSIGNED NOT NULL,
    `exit_date` DATE NOT NULL,
    `exit_type` ENUM('profit_target', 'thesis_violation', 'valuation', 'stop_loss', 'manual') NOT NULL,
    `shares_sold` DECIMAL(18,6) NOT NULL,
    `exit_price` DECIMAL(10,2) NOT NULL,
    `proceeds` DECIMAL(18,2) NOT NULL,
    `realized_gain_loss` DECIMAL(18,2) NOT NULL,
    `realized_gain_loss_pct` DECIMAL(10,4) NOT NULL,
    `tier_number` INT UNSIGNED NULL,
    `signal_id` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_exits_position` (`position_id`),
    FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sell Signals Table
CREATE TABLE `sell_signals` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticker` VARCHAR(10) NOT NULL,
    `position_id` INT UNSIGNED NULL,
    `signal_type` ENUM('profit_target', 'thesis_violation', 'stop_loss', 'overvalued', 'valuation_target', 'deterioration') NOT NULL,
    `urgency` ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    `recommendation` ENUM('WATCH', 'TRIM', 'EXIT') NOT NULL,
    `trigger_gate` VARCHAR(20) NULL,
    `trigger_reason` VARCHAR(500) NULL,
    `price_at_signal` DECIMAL(10,2) NOT NULL,
    `fair_value` DECIMAL(10,2) NULL,
    `premium_pct` DECIMAL(10,4) NULL,
    `gain_loss_pct` DECIMAL(10,4) NULL,
    `analysis_id` INT UNSIGNED NULL,
    `previous_analysis_id` INT UNSIGNED NULL,
    `signal_data` JSON NULL,
    `status` ENUM('active', 'acknowledged', 'acted_upon', 'dismissed') DEFAULT 'active',
    `action_taken` VARCHAR(255) NULL,
    `action_date` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sell_signals_ticker` (`ticker`),
    INDEX `idx_sell_signals_type` (`signal_type`),
    INDEX `idx_sell_signals_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trade Narratives Table
CREATE TABLE `trade_narratives` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `signal_id` INT UNSIGNED NOT NULL,
    `signal_type` ENUM('buy', 'sell') NOT NULL,
    `ticker` VARCHAR(10) NOT NULL,
    `narrative_text` TEXT NOT NULL,
    `narrative_html` TEXT NULL,
    `summary` VARCHAR(500) NOT NULL,
    `sections` JSON NOT NULL,
    `input_data` JSON NOT NULL,
    `llm_model` VARCHAR(50) NOT NULL,
    `llm_prompt_version` VARCHAR(20) NOT NULL,
    `generation_time_ms` INT UNSIGNED NULL,
    `status` ENUM('generated', 'reviewed', 'approved', 'exported') DEFAULT 'generated',
    `reviewed_by` VARCHAR(100) NULL,
    `reviewed_at` TIMESTAMP NULL,
    `export_count` INT UNSIGNED DEFAULT 0,
    `last_exported_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_narrative_signal` (`signal_id`, `signal_type`),
    INDEX `idx_narrative_ticker` (`ticker`),
    INDEX `idx_narrative_status` (`status`),
    FULLTEXT INDEX `idx_narrative_text` (`narrative_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
