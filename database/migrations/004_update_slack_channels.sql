-- ============================================
-- MIGRATION 004: UPDATE SLACK CHANNELS
-- Update default channels to #general
-- ============================================

UPDATE `notification_preferences`
SET `slack_notification_channel` = '#general',
    `slack_portfolio_channel` = '#general'
WHERE `id` > 0;
