-- 1. Insert the first user, skipping any rows that already exist
INSERT IGNORE INTO message_users (groupid, userid)
SELECT id, userid
FROM message_group
WHERE userid IS NOT NULL;

-- 2. Insert the second user, skipping any rows that already exist
INSERT IGNORE INTO message_users (groupid, userid)
SELECT id, profileid
FROM message_group
WHERE profileid IS NOT NULL;