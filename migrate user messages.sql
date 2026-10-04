-- File contains sql to be ran on new site migration

-- Messages

-- Insert the first user, skipping any rows that already exist
INSERT IGNORE INTO message_users (groupid, userid)
SELECT id, userid
FROM message_group
WHERE userid IS NOT NULL;

-- Insert the second user, skipping any rows that already exist
INSERT IGNORE INTO message_users (groupid, userid)
SELECT id, profileid
FROM message_group
WHERE profileid IS NOT NULL;

-- Users and profiles
INSERT INTO user_profiles (userid, picture, banner, twitter, bsky, description)
SELECT id, picture, banner, twitter, bsky, description
FROM users
WHERE deactive IS NULL;