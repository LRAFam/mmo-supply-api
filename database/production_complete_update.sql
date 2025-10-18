-- COMPLETE PRODUCTION DATABASE UPDATE
-- Bootstrap-Friendly Rewards System Implementation
-- Run this on production: mysql -u forge -p forge < production_complete_update.sql

-- ==================================================
-- PART 1: Add New Game Categories
-- ==================================================

-- Add RSPS (Private Servers)
INSERT INTO games (id, title, slug, description, logo, icon, provider_count, created_at, updated_at)
VALUES (
    22,
    'RSPS (Private Servers)',
    'rsps',
    'RuneScape Private Servers - Find accounts, items, services, and currency for your favorite RSPS',
    NULL,
    NULL,
    0,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    title = 'RSPS (Private Servers)',
    description = 'RuneScape Private Servers - Find accounts, items, services, and currency for your favorite RSPS',
    updated_at = NOW();

-- Add Gaming Creative Services
INSERT INTO games (id, title, slug, description, logo, icon, provider_count, created_at, updated_at)
VALUES (
    23,
    'Gaming Creative Services',
    'gaming-creative-services',
    'Professional creative services for gamers and content creators - GFX design, video editing, streaming assets, Discord services, and more',
    NULL,
    NULL,
    0,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    title = 'Gaming Creative Services',
    description = 'Professional creative services for gamers and content creators - GFX design, video editing, streaming assets, Discord services, and more',
    updated_at = NOW();

-- ==================================================
-- PART 2: Create Monthly Referral Rally Event
-- ==================================================

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
    prizes,
    rules,
    requirements,
    is_featured,
    banner_image,
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
    '[{"rank":1,"amount":10.00,"type":"bonus_balance","badge":"top_referrer","description":"🥇 Most Referrals: $10","wallet_amount":10.00},{"rank":2,"amount":6.00,"type":"bonus_balance","badge":"top_referrer","description":"🥈 2nd Place: $6","wallet_amount":6.00},{"rank":3,"amount":4.00,"type":"bonus_balance","badge":null,"description":"🥉 3rd Place: $4","wallet_amount":4.00}]',
    '["Refer friends using your unique referral link from /referrals","Referred users must spend $15+ to count","Active referrals (2+ purchases) count as 2 points","Live leaderboard at /referrals","Winners announced at event end"]',
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
    prizes = '[{"rank":1,"amount":10.00,"type":"bonus_balance","badge":"top_referrer","description":"🥇 Most Referrals: $10","wallet_amount":10.00},{"rank":2,"amount":6.00,"type":"bonus_balance","badge":"top_referrer","description":"🥈 2nd Place: $6","wallet_amount":6.00},{"rank":3,"amount":4.00,"type":"bonus_balance","badge":null,"description":"🥉 3rd Place: $4","wallet_amount":4.00}]',
    rules = '["Refer friends using your unique referral link from /referrals","Referred users must spend $15+ to count","Active referrals (2+ purchases) count as 2 points","Live leaderboard at /referrals","Winners announced at event end"]',
    requirements = '{"min_referral_spend":15.00,"email_verified":true,"active_referral_points":2}',
    is_featured = 1,
    status = 'active',
    updated_at = NOW();

-- ==================================================
-- PART 3: Update Existing Events to Bootstrap Budget
-- ==================================================

-- Event 31: Referral Competition - DISABLE (redundant with new Monthly Referral Rally)
UPDATE events
SET status = 'draft',
    description = 'DEPRECATED: Replaced by Monthly Referral Rally event. This event will be removed in a future update.',
    updated_at = NOW()
WHERE id = 31;

-- Event 32: Discord Boost Rewards - Reduce from $60 to $15/month
UPDATE events
SET prizes = '[{"rank":"instant","description":"Instant: $2.50 Credits (All Boosters)","wallet_amount":2.50,"type":"bonus_balance"},{"rank":1,"description":"Monthly Draw: $7.50","wallet_amount":7.50,"type":"bonus_balance"},{"rank":2,"description":"Monthly Draw: $3.75","wallet_amount":3.75,"type":"bonus_balance"},{"rank":3,"description":"Monthly Draw: $1.25","wallet_amount":1.25,"type":"bonus_balance"}]',
    description = 'Boost our Discord server and get instant $2.50 credits + enter monthly $15 giveaway! Support the community and get rewarded with platform credits.',
    updated_at = NOW()
WHERE id = 32;

-- Event 33: First Purchase Bonus - DISABLE (replaced by tiered welcome bonus system)
UPDATE events
SET status = 'draft',
    description = 'DEPRECATED: First purchase bonuses now handled automatically via tiered welcome bonus system ($2-10 based on spend). This event will be removed in a future update.',
    updated_at = NOW()
WHERE id = 33;

-- Event 34: Weekend Warriors Drop Party - Reduce from $60 to $15
UPDATE events
SET prizes = '[{"rank":"1-5","description":"$2.50 Credits","wallet_amount":2.50,"type":"bonus_balance"},{"rank":"6-10","description":"$1.25 Credits","wallet_amount":1.25,"type":"bonus_balance"},{"rank":"11-20","description":"$0.50 Credits","wallet_amount":0.50,"type":"bonus_balance"}]',
    description = 'Every weekend we celebrate our community! Join us for platform credit prizes and community fun.',
    winner_count = 20,
    updated_at = NOW()
WHERE id = 34;

-- Event 35: Flash Giveaway Friday - Reduce from $50 to $10
UPDATE events
SET prizes = '[{"rank":"1-10","description":"$1 Credits","wallet_amount":1,"type":"bonus_balance"}]',
    description = 'Quick random giveaways every Friday! Be online and active to win instant platform credit prizes.',
    updated_at = NOW()
WHERE id = 35;

-- Event 36: Review & Win - Reduce from $65 to $15
UPDATE events
SET prizes = '[{"rank":1,"description":"$7.50 Credits","wallet_amount":7.50,"type":"bonus_balance"},{"rank":"2-5","description":"$1.87 Credits","wallet_amount":1.87,"type":"bonus_balance"}]',
    description = 'Leave reviews on your purchases to enter monthly prize draw! Quality feedback gets rewarded with platform credits.',
    updated_at = NOW()
WHERE id = 36;

-- Event 37: New Year Extravaganza - DISABLE (too expensive for bootstrap phase)
UPDATE events
SET status = 'draft',
    description = 'Ring in the new year with our biggest celebration yet! COMING SOON: Prize pool scales with platform growth. Currently scheduled for when monthly revenue reaches Growth tier ($500+/mo).',
    updated_at = NOW()
WHERE id = 37;

-- Event 38: PVP Championship Series - DISABLE (too expensive for bootstrap phase)
UPDATE events
SET status = 'draft',
    description = 'Test your skills in the ultimate PVP tournament! COMING SOON: Prize pool scales with platform growth. Currently scheduled for when monthly revenue reaches Scale tier ($2000+/mo).',
    updated_at = NOW()
WHERE id = 38;

-- Event 39: Community Treasure Hunt - Reduce from $200+ to $25
UPDATE events
SET prizes = '[{"rank":1,"description":"First Finder: $10 + Exclusive Badge","wallet_amount":10,"type":"bonus_balance"},{"rank":"2-5","description":"$2.50 Credits","wallet_amount":2.50,"type":"bonus_balance"},{"rank":"6-15","description":"$1 Credits","wallet_amount":1,"type":"bonus_balance"}]',
    winner_count = 15,
    description = 'Follow the clues, solve the puzzles, and find the hidden treasures! Platform credit prizes for the clever hunters.',
    updated_at = NOW()
WHERE id = 39;

-- Event 40: Black Friday Bonanza - DISABLE (too expensive for bootstrap phase)
UPDATE events
SET status = 'draft',
    description = 'The biggest shopping event of the year! COMING SOON: Massive discounts and prize pool scale with platform growth. Scheduled for when monthly revenue reaches Established tier ($10,000+/mo).',
    updated_at = NOW()
WHERE id = 40;

-- Event 41: Monthly Seller Spotlight - Reduce from $150+ to $20
UPDATE events
SET prizes = '[{"rank":1,"description":"Top Seller: $10 + Featured Profile","wallet_amount":10,"type":"bonus_balance"},{"rank":2,"description":"$5 + Featured Profile","wallet_amount":5,"type":"bonus_balance"},{"rank":3,"description":"$3 + Featured Profile","wallet_amount":3,"type":"bonus_balance"},{"rank":"4-5","description":"$1 Credits","wallet_amount":1,"type":"bonus_balance"}]',
    winner_count = 5,
    description = 'Recognition and rewards for our top sellers! Compete for the top spot and earn platform credits plus exclusive profile features.',
    updated_at = NOW()
WHERE id = 41;

-- ==================================================
-- VERIFICATION QUERIES
-- ==================================================

SELECT '=== NEW GAME CATEGORIES ===' as section;
SELECT id, title, slug, provider_count FROM games WHERE id IN (22, 23) ORDER BY id;

SELECT '=== NEW MONTHLY REFERRAL RALLY EVENT ===' as section;
SELECT id, name, type, status, is_featured FROM events WHERE id = 10;

SELECT '=== UPDATED EVENTS SUMMARY ===' as section;
SELECT
    id,
    name,
    status,
    CASE
        WHEN id IN (31, 33, 37, 38, 40) THEN 'DISABLED (too expensive or redundant)'
        ELSE 'ACTIVE (updated to bootstrap budget)'
    END as action,
    LEFT(prizes, 100) as prizes_preview
FROM events
WHERE id IN (31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41)
ORDER BY id;

SELECT '=== ACTIVE EVENTS COUNT ===' as section;
SELECT
    COUNT(*) as total_active_events,
    SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(prizes, '"wallet_amount":', -1), ',', 1) AS DECIMAL(10,2))) as estimated_monthly_cost
FROM events
WHERE status = 'active';
