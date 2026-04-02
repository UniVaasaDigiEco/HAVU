-- Add challenge_data column to nodes table
ALTER TABLE nodes ADD COLUMN challenge_data JSON NULL DEFAULT NULL;
