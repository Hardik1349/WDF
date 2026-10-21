-- Practical 12: add poster support to the events table from Practical 8.
-- Select the studenthub database before running this script.
USE studenthub;

-- Run this migration only once. It adds a nullable poster path to existing events.
ALTER TABLE events ADD COLUMN poster_path VARCHAR(255) NULL AFTER category;
