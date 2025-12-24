<?php

return [
    'gate_1' => [
        'buyback_timing_threshold' => 0.10,
        'insider_net_buy_months' => 12,
        'goodwill_impairment_flag' => 0.05,
    ],

    'gate_1_5' => [  // NEW: Moat Assessment
        'require_moat_for_exceptional' => true,
        'min_durability_for_hold_forever' => 'high',
        'llm_confidence_threshold' => 0.7,
        'auto_assess_with_llm' => true,
    ],

    'gate_2' => [
        'min_roic_wacc_spread' => 0.03,
        'margin_decline_threshold' => -0.02,
        'roic_lookback_years' => 5,
    ],

    'gate_2_5' => [
        'max_net_debt_ebitda' => 3.0,
        'min_interest_coverage' => 2.5,
        'max_debt_due_24m_pct' => 0.30,
        'stress_test_revenue_drop' => 0.30,
    ],

    'gate_2_75' => [  // NEW: Reinvestment Runway
        'min_incremental_roic' => 0.10,
        'long_runway_reinvestment' => 0.50,
        'medium_runway_reinvestment' => 0.30,
    ],

    'gate_3' => [
        'min_fcf_conversion' => 0.80,
        'max_receivables_divergence' => 0.10,
        'max_accruals_ratio' => 0.10,
    ],

    'gate_3_5' => [  // NEW: Complexity Filter
        'max_binary_risks' => 1,
        'min_earnings_predictability' => 'medium',
        'auto_exclude_sectors' => [],
    ],

    'gate_4' => [
        'peg_attractive' => 1.0,
        'peg_acceptable' => 1.5,
        'pegy_attractive' => 1.5,
        'ev_fcf_attractive_percentile' => 0.25,
    ],

    'gate_5' => [
        'estimate_revision_positive' => 0.02,
        'estimate_revision_negative' => -0.02,
    ],

    'circuit_breaker' => [
        'max_5day_drop' => 0.10,
        'max_relative_underperformance' => 0.08,
        'volume_anomaly_threshold' => 3.0,
        'market_selloff_threshold' => -0.08,
        'opportunity_score_threshold' => 0.7,
    ],

    'quality_tiers' => [  // NEW
        'exceptional' => [
            'min_roic_spread' => 0.15,
            'min_moat_durability' => 'high',
            'min_runway' => 'long_runway',
            'profit_target' => null,
            'stop_loss' => 0.40,
            'position_multiplier' => 1.5,
        ],
        'high_quality' => [
            'min_roic_spread' => 0.10,
            'min_moat_durability' => 'medium',
            'min_runway' => 'medium_runway',
            'profit_target' => 0.50,
            'stop_loss' => 0.30,
            'position_multiplier' => 1.25,
        ],
        'good_quality' => [
            'min_roic_spread' => 0.05,
            'min_moat_durability' => 'low',
            'profit_target' => 0.25,
            'stop_loss' => 0.20,
            'position_multiplier' => 1.0,
        ],
        'acceptable' => [
            'min_roic_spread' => 0.03,
            'profit_target' => 0.15,
            'stop_loss' => 0.15,
            'position_multiplier' => 0.75,
        ],
    ],

    'market' => [  // NEW
        'pe_high' => 25,
        'pe_low' => 15,
        'volatility_high' => 30,
        'cape_overvalued_percentile' => 0.75,
        'cape_undervalued_percentile' => 0.25,
        'vix_fear_threshold' => 30,
        'vix_complacency_threshold' => 15,
        'late_cycle_cash' => 0.30,
        'mid_cycle_cash' => 0.15,
        'early_cycle_cash' => 0.05,
        'update_frequency' => 'daily',
    ],

    'position_sizing' => [  // NEW
        'maximum' => 0.15,
        'large' => 0.10,
        'standard' => 0.05,
        'small' => 0.03,
        'starter' => 0.01,
        'absolute_max' => 0.20,
    ],
];
