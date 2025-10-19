-- Production Database Updates for RSPS and Rewards System
-- Run these on production server at 159.65.51.57

-- 1. Add RSPS Game (ID: 22)
INSERT INTO games (id, title, slug, description, icon_url, is_active, created_at, updated_at)
VALUES (
    22,
    'RSPS (Private Servers)',
    'rsps',
    'RuneScape Private Servers - Find accounts, items, services, and currency for your favorite RSPS',
    NULL,
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    title = 'RSPS (Private Servers)',
    description = 'RuneScape Private Servers - Find accounts, items, services, and currency for your favorite RSPS',
    is_active = 1,
    updated_at = NOW();

-- 2. Create Monthly Referral Rally Event
INSERT INTO events (
    id,
    name,
    slug,
    description,
    type,
    status,
    starts_at,
    ends_at,
    game_id,
    max_participants,
    entry_fee,
    prize_pool,
    prizes,
    requirements,
    is_featured,
    banner_image_url,
    winner_count,
    created_at,
    updated_at
)
VALUES (
    10,
    'Monthly Referral Rally',
    'monthly-referral-rally',
    'Compete to refer the most active users each month! Top 3 referrers win platform credit prizes. Referred users must spend $15+ to count, and active referrals (2+ purchases) count as 2 points!',
    'tournament',
    'active',
    DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00'),
    LAST_DAY(NOW()) + INTERVAL 1 DAY - INTERVAL 1 SECOND,
    NULL,
    NULL,
    0.00,
    20.00,
    '[{"rank":1,"amount":10.00,"type":"bonus_balance","badge":"top_referrer"},{"rank":2,"amount":6.00,"type":"bonus_balance","badge":"top_referrer"},{"rank":3,"amount":4.00,"type":"bonus_balance","badge":null}]',
    '{"min_referral_spend":15.00,"email_verified":true,"active_referral_points":2}',
    1,
    NULL,
    3,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    name = 'Monthly Referral Rally',
    description = 'Compete to refer the most active users each month! Top 3 referrers win platform credit prizes. Referred users must spend $15+ to count, and active referrals (2+ purchases) count as 2 points!',
    prize_pool = 20.00,
    prizes = '[{"rank":1,"amount":10.00,"type":"bonus_balance","badge":"top_referrer"},{"rank":2,"amount":6.00,"type":"bonus_balance","badge":"top_referrer"},{"rank":3,"amount":4.00,"type":"bonus_balance","badge":null}]',
    requirements = '{"min_referral_spend":15.00,"email_verified":true,"active_referral_points":2}',
    is_featured = 1,
    status = 'active',
    updated_at = NOW();

-- 3. Verify the changes
SELECT id, title, slug, is_active FROM games WHERE id = 22;
SELECT id, name, type, status, prize_pool FROM events WHERE id = 10;
