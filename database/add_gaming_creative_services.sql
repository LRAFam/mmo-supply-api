-- Add Gaming Creative Services Category
-- For GFX designers, video editors, and other gaming content creators

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

-- Verify the game was added
SELECT id, title, slug, description FROM games WHERE id = 23;
