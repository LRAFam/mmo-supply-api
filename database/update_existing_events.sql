-- Update Existing Events to Bootstrap-Friendly Budget
-- Changes all prizes to bonus_balance (platform credit) and reduces prize pools

-- Event 31: Referral Competition - Reduce from $100 to $20 (same as Monthly Referral Rally)
-- This is redundant with our new Monthly Referral Rally (ID: 10), so we'll disable it
UPDATE events
SET status = 'draft',
    description = 'DEPRECATED: Replaced by Monthly Referral Rally event. This event will be removed in a future update.'
WHERE id = 31;

-- Event 32: Discord Boost Rewards - Reduce from $60 to $15/month
UPDATE events
SET prizes = '[{"rank":"instant","description":"Instant: $2.50 Credits (All Boosters)","wallet_amount":2.50,"type":"bonus_balance"},{"rank":1,"description":"Monthly Draw: $7.50","wallet_amount":7.50,"type":"bonus_balance"},{"rank":2,"description":"Monthly Draw: $3.75","wallet_amount":3.75,"type":"bonus_balance"},{"rank":3,"description":"Monthly Draw: $1.25","wallet_amount":1.25,"type":"bonus_balance"}]',
    description = 'Boost our Discord server and get instant $2.50 credits + enter monthly $15 giveaway! Support the community and get rewarded.',
    updated_at = NOW()
WHERE id = 32;

-- Event 33: First Purchase Bonus - Replace with our new tiered welcome bonus system
-- Mark as draft since it's handled by config/rewards.php now
UPDATE events
SET status = 'draft',
    description = 'DEPRECATED: First purchase bonuses now handled automatically via tiered welcome bonus system ($2-10 based on spend). This event will be removed in a future update.'
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

-- Event 37: New Year Extravaganza - TOO EXPENSIVE ($150+), mark as draft for future when revenue allows
UPDATE events
SET status = 'draft',
    description = 'Ring in the new year with our biggest celebration yet! COMING SOON: Prize pool scales with platform growth. Currently scheduled for when monthly revenue reaches Growth tier ($500+/mo).',
    updated_at = NOW()
WHERE id = 37;

-- Event 38: PVP Championship Series - TOO EXPENSIVE ($200+), mark as draft
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

-- Event 40: Black Friday Bonanza - TOO EXPENSIVE ($600+), mark as draft for future
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

-- Summary of changes
SELECT
    id,
    name,
    status,
    CASE
        WHEN status = 'draft' THEN 'Disabled (too expensive or redundant)'
        ELSE 'Updated to bootstrap budget'
    END as action,
    prizes
FROM events
WHERE id IN (31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41)
ORDER BY id;
