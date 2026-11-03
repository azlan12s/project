-- Run this ONCE on your existing database if it was installed before this update,
-- so old emoji icons stored in the categories table are cleared too.
UPDATE categories SET icon = '';
