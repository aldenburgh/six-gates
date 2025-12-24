-- Moat assessments
CREATE TABLE `moat_assessments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticker` VARCHAR(10) NOT NULL,
    `assessment_date` DATE NOT NULL,
    `primary_moat` VARCHAR(50) NULL,
    `secondary_moats` JSON NULL,
    `durability` ENUM('high', 'medium', 'low', 'none') NOT NULL,
    `moat_evidence` JSON NULL,
    `moat_threats` JSON NULL,
    `assessment_method` ENUM('llm', 'human', 'hybrid') NOT NULL,
    `human_override` VARCHAR(50) NULL,
    `confidence_score` DECIMAL(3,2) NULL,
    `llm_reasoning` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_moat_ticker_date` (`ticker`, `assessment_date`),
    INDEX `idx_moat_durability` (`durability`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reinvestment runway assessments
CREATE TABLE `reinvestment_runway` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticker` VARCHAR(10) NOT NULL,
    `assessment_date` DATE NOT NULL,
    `total_addressable_market` DECIMAL(18,2) NULL,
    `current_revenue` DECIMAL(18,2) NOT NULL,
    `market_penetration` DECIMAL(5,4) NULL,
    `reinvestment_rate` DECIMAL(5,4) NOT NULL,
    `incremental_roic` DECIMAL(5,4) NULL,
    `estimated_runway_years` INT UNSIGNED NULL,
    `growth_category` ENUM('long_runway', 'medium_runway', 'limited_runway', 'mature') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_runway_ticker_date` (`ticker`, `assessment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Complexity assessments
CREATE TABLE `complexity_assessments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticker` VARCHAR(10) NOT NULL,
    `assessment_date` DATE NOT NULL,
    `regulatory_risk` ENUM('low', 'medium', 'high') NOT NULL,
    `technology_disruption` ENUM('low', 'medium', 'high') NOT NULL,
    `business_model_complexity` ENUM('simple', 'moderate', 'complex') NOT NULL,
    `earnings_predictability` ENUM('high', 'medium', 'low') NOT NULL,
    `binary_risks` JSON NULL,
    `too_hard_flag` TINYINT(1) NOT NULL DEFAULT 0,
    `too_hard_reason` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_complexity_ticker_date` (`ticker`, `assessment_date`),
    INDEX `idx_complexity_too_hard` (`too_hard_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quality tier assignments
CREATE TABLE `quality_tiers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticker` VARCHAR(10) NOT NULL,
    `analysis_id` INT UNSIGNED NOT NULL,
    `tier` ENUM('exceptional', 'high_quality', 'good_quality', 'acceptable') NOT NULL,
    `holding_rule` ENUM('hold_forever', 'extended_hold', 'standard', 'short_term') NOT NULL,
    `profit_target` DECIMAL(5,4) NULL,
    `stop_loss` DECIMAL(5,4) NOT NULL,
    `position_size_multiplier` DECIMAL(4,2) NOT NULL,
    `tier_reasoning` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_tier_analysis` (`analysis_id`),
    INDEX `idx_tier_ticker` (`ticker`),
    INDEX `idx_tier_level` (`tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Market context snapshots
CREATE TABLE `market_context` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `snapshot_date` DATE NOT NULL,
    `cape_ratio` DECIMAL(6,2) NULL,
    `cape_percentile` DECIMAL(5,4) NULL,
    `spy_pe` DECIMAL(6,2) NULL,
    `valuation_level` VARCHAR(50) NULL,
    `high_yield_spread` DECIMAL(6,4) NULL,
    `spread_percentile` DECIMAL(5,4) NULL,
    `credit_condition` VARCHAR(50) NULL,
    `vix` DECIMAL(6,2) NULL,
    `put_call_ratio` DECIMAL(5,4) NULL,
    `sentiment` VARCHAR(50) NULL,
    `cycle_position` ENUM('early_cycle', 'mid_cycle', 'late_cycle') NOT NULL,
    `recommended_cash_level` DECIMAL(5,4) NOT NULL,
    `position_size_adjustment` DECIMAL(4,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_context_date` (`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update positions table for quality tier
ALTER TABLE `positions` ADD COLUMN `quality_tier` ENUM('exceptional', 'high_quality', 'good_quality', 'acceptable') NULL;
ALTER TABLE `positions` ADD COLUMN `holding_rule` ENUM('hold_forever', 'extended_hold', 'standard', 'short_term') NULL;
ALTER TABLE `positions` ADD COLUMN `tier_profit_target` DECIMAL(5,4) NULL;
ALTER TABLE `positions` ADD COLUMN `tier_stop_loss` DECIMAL(5,4) NULL;

-- Update analysis_results for new gates (formerly gate_analyses)
ALTER TABLE `analysis_results` ADD COLUMN `gate_1_5_score` DECIMAL(5,2) NULL;
ALTER TABLE `analysis_results` ADD COLUMN `gate_1_5_data` JSON NULL;
ALTER TABLE `analysis_results` ADD COLUMN `gate_2_75_score` DECIMAL(5,2) NULL;
ALTER TABLE `analysis_results` ADD COLUMN `gate_2_75_data` JSON NULL;
ALTER TABLE `analysis_results` ADD COLUMN `gate_3_5_passed` TINYINT(1) NULL;
ALTER TABLE `analysis_results` ADD COLUMN `gate_3_5_data` JSON NULL;
ALTER TABLE `analysis_results` ADD COLUMN `quality_tier` ENUM('exceptional', 'high_quality', 'good_quality', 'acceptable') NULL;
