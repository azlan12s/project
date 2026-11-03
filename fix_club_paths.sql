-- Live database par ek baar chala dein (phpMyAdmin > SQL) taake purane /club/ wale saved paths theek ho jayein.
-- Dobara chalane se koi nuqsan nahi.
UPDATE character_profiles SET image = REPLACE(image, '/club/', '/') WHERE image LIKE '/club/%';
UPDATE merchandise_items  SET image = REPLACE(image, '/club/', '/') WHERE image LIKE '/club/%';
UPDATE events             SET image = REPLACE(image, '/club/', '/') WHERE image LIKE '/club/%';
UPDATE contents SET thumbnail = REPLACE(thumbnail, '/club/', '/') WHERE thumbnail LIKE '/club/%';
UPDATE contents SET media_url = REPLACE(media_url, '/club/', '/') WHERE media_url LIKE '/club/%';
UPDATE users    SET avatar    = REPLACE(avatar,    '/club/', '/') WHERE avatar    LIKE '/club/%';
