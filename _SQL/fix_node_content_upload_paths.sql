-- Fix old upload paths inside nodes.content after moving from /HavuGamification/ to subdomain root.
-- Example: /HavuGamification/uploads/file.jpg -> /uploads/file.jpg

START TRANSACTION;

-- Preview how many rows are candidates.
SELECT COUNT(*) AS candidate_rows
FROM nodes
WHERE content LIKE '%/HavuGamification/%';

-- Apply replacement.
UPDATE nodes
SET content = REPLACE(content, '/HavuGamification/', '/')
WHERE content LIKE '%/HavuGamification/%';

-- Verify how many rows were changed in this UPDATE.
SELECT ROW_COUNT() AS updated_rows;

COMMIT;

-- If needed before COMMIT, you can run ROLLBACK instead.
