-- Some refere checks

ALTER TABLE ONLY member_members ADD CONSTRAINT member_members_referrer_profile_id_fkey FOREIGN KEY (referrer_profile_id) REFERENCES profile_profiles(id) ON UPDATE CASCADE ON DELETE SET DEFAULT;

ALTER TABLE ONLY member_members ADD CONSTRAINT member_members_community_id_fkey FOREIGN KEY (community_id) REFERENCES system_communities(id) ON UPDATE CASCADE ON DELETE SET DEFAULT;

ALTER TABLE ONLY member_members ADD CONSTRAINT member_members_referrer_community_id_fkey FOREIGN KEY (referrer_community_id) REFERENCES system_communities(id) ON UPDATE CASCADE ON DELETE SET DEFAULT;