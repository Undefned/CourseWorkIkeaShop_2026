-- Optional, minimal addition — not required for the endpoints to work.
--
-- `products` has is_active, but `projects` does not, so a "finished but
-- hidden" project can't be modeled the way a discontinued product can.
-- The current /api/projects and /api/projects/{slug} endpoints simply
-- don't filter on activity for projects (there's nothing to filter on).
-- Apply this only if you want that same soft-hide capability for
-- portfolio items; if you do, uncomment the WHERE clauses noted in
-- Backend/Api/index.php as well.

ALTER TABLE projects ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT TRUE;
