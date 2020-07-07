-- Memmbers
INSERT INTO member_members (first_name, middle_name, last_name, gender, dob, postal_code, email, password, active) VALUES ('Arthur', 'Marshall', 'Kang', 'M', '1971-05-17', '91913', 'arthur@levelogic.com', md5('marshall'), 't');

-- Modules

-- Communities
INSERT INTO system_communities (name, display, description) VALUES ('testcomm1', 'Test Community 1', 'This is the description for test community 1.');
INSERT INTO system_communities (member_id, name, display, description) VALUES (1, 'testcomm2', 'Test Community 2', 'This is the description for test community 2.');
INSERT INTO system_communities (member_id, name, display, description) VALUES (1, 'finance', 'Finance Community', 'This is the finance community.');
INSERT INTO system_communities (member_id, name, display, description, parent_id) VALUES (1, 'stocks', 'Stock Community', 'This is the stock community.', 4);
INSERT INTO system_communities (member_id, name, display, description, parent_id) VALUES (1, 'otcjournal', 'OTCJournal Community', 'This is the OTCJournal community.', 5);
INSERT INTO system_communities (member_id, name, display, description, hostname, parent_id) VALUES (1, 'smallcapnetwork', 'SmallCap Network Community', 'The Small Cap Network stock community.', 'community.smallcapnetwork.com/community', 5);
INSERT INTO system_communities (member_id, name, display, description, parent_id) VALUES (1, 'stockupticks', 'StockUpTicks Community', 'This is the StockUpTicks community.', 5);
INSERT INTO system_communities (member_id, name, display, description, hostname) VALUES (2, 'levelogic', 'Levelogic Community', 'This is the Levelogic community.', 'viame.levelogic.com');

UPDATE system_communities SET parent_id=1 WHERE id > 1 AND parent_id ISNULL;

-- Profiles
INSERT INTO profile_profiles (member_id, name, base, default_profile, active) VALUES (2, 'Arthur Marshall Kang', 't', 't', 't');
INSERT INTO profile_profiles (member_id, name, base, default_profile, active) VALUES (2, 'Arthur Kang', 'f', 'f', 't');
INSERT INTO profile_profiles (member_id, name, community_id, base, default_profile, active) VALUES (2, 'Kango The Stock Picker', 5, 'f', 'f', 't');

-- Networks
INSERT INTO network_networks(profile_id, name, public, open) VALUES (1, 'Public Open Network No Password', 't', 't');
INSERT INTO network_networks(profile_id, name, public, open) SELECT id, 'AKs Open Network', 'f', 't' FROM profile_profiles WHERE name='Arthur Marshall Kang';
INSERT INTO network_networks(profile_id, name) SELECT id, 'AKs Private Network' FROM profile_profiles WHERE name='Arthur Marshall Kang';
INSERT INTO network_networks(profile_id, name, password) SELECT id, 'AKs Closed Network', 'password' FROM profile_profiles WHERE name='Arthur Marshall Kang';

-- Module Matrix
-- Load up Modules For 'Arthur Marshall Kang'
INSERT INTO module_matrix(via_id, module_id) SELECT p.id, m.id FROM profile_profiles p, module_modules m WHERE p.name='Arthur Marshall Kang' AND m.name='info';
INSERT INTO module_matrix(via_id, module_id) SELECT p.id, m.id FROM profile_profiles p, module_modules m WHERE p.name='Arthur Marshall Kang' AND m.name='article';
--INSERT INTO module_matrix(via_id, module_id) SELECT p.id, m.id FROM profile_profiles p, module_modules m WHERE p.name='Arthur Marshall Kang' AND m.name='section';
--INSERT INTO module_matrix(via_id, module_id) SELECT p.id, m.id FROM profile_profiles p, module_modules m WHERE p.name='Arthur Marshall Kang' AND m.name='mb';
--INSERT INTO module_matrix(via_id, module_id) SELECT p.id, m.id FROM profile_profiles p, module_modules m WHERE p.name='Arthur Marshall Kang' AND m.name='cb';
--INSERT INTO module_matrix(via_id, module_id) SELECT p.id, m.id FROM profile_profiles p, module_modules m WHERE p.name='Arthur Marshall Kang' AND m.name='quote';
--INSERT INTO module_matrix(via_id, module_id) SELECT p.id, m.id FROM profile_profiles p, module_modules m WHERE p.name='Arthur Marshall Kang' AND m.name='help';
--INSERT INTO module_matrix(via_id, module_id) SELECT p.id, m.id FROM profile_profiles p, module_modules m WHERE p.name='Arthur Marshall Kang' AND m.name='statistic';
--INSERT INTO module_matrix(via_id, module_id) SELECT p.id, m.id FROM profile_profiles p, module_modules m WHERE p.name='Arthur Marshall Kang' AND m.name='log';
-- Load up Modules For net_id
INSERT INTO module_matrix(net_id, module_id) SELECT n.id, m.id FROM network_networks n, module_modules m WHERE n.name='Public Open Network No Password' AND m.name='article';

-- Contacts

INSERT INTO contact_contacts (profile_id, contact_profile_id, status, active) SELECT a.id, b.id, 't', 't' FROM profile_profiles a, profile_profiles b, member_members c WHERE a.member_id=c.id AND b.member_id=c.id AND c.email='arthur@levelogic.com' and a.id <> b.id;