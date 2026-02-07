-- =============================================
-- Truncate Routes and Nodes Tables
-- =============================================
-- This script truncates the routes, nodes, and node_route_cross tables
-- Use this for testing purposes to reset all route and node data
-- WARNING: This will permanently delete all data in these tables!
-- =============================================

SET FOREIGN_KEY_CHECKS = 0;

-- Truncate the cross-reference table first (has foreign keys to both routes and nodes)
TRUNCATE TABLE node_route_cross;

-- Truncate the nodes table
TRUNCATE TABLE nodes;

-- Truncate the routes table
TRUNCATE TABLE routes;

SET FOREIGN_KEY_CHECKS = 1;

-- Success message
SELECT 'Successfully truncated routes, nodes, and node_route_cross tables' AS status;

