DROP TABLE IF EXISTS contact_group_members CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE contact_group_members (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    				    
    group_counter_id    bigint NOT NULL,  -- Delay Reference Check Until After Table Creation
    
    member_profile_id   bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              boolean DEFAULT 't', -- NULL=not displayed anymore - Forbidden
    
    FOREIGN KEY (profile_id, group_counter_id) REFERENCES contact_group_groups(profile_id, counter) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (profile_id, member_profile_id) REFERENCES contact_contacts(profile_id, contact_profile_id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE UNIQUE INDEX contact_group_members_profile_group_counter_member_profile_x ON contact_group_members (profile_id, group_counter_id, member_profile_id);
--CREATE INDEX contact_group_members_profile_group_counter_x ON contact_group_members (profile_id, group_counter_id);

ALTER TABLE public.contact_group_members OWNER TO vmdbuser;

-- Moved the following two tables constraints into the table
--ALTER TABLE ONLY contact_group_members ADD CONSTRAINT contact_group_members_profile_group_counter_fkey FOREIGN KEY (profile_id, group_counter_id) REFERENCES contact_group_groups(profile_id, counter) ON UPDATE CASCADE ON DELETE CASCADE;
--ALTER TABLE ONLY contact_group_members ADD CONSTRAINT contact_group_members_profile_member_profile_fkey FOREIGN KEY (profile_id, member_profile_id) REFERENCES contact_contacts(profile_id, contact_profile_id) ON UPDATE CASCADE ON DELETE CASCADE;

--Update the updated field on updates
CREATE TRIGGER "contact_group_members_updated_trigger" BEFORE UPDATE ON "contact_group_members" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

--Update the contact_group_groups total_member_count field on inserts and deletes
CREATE TRIGGER "contact_group_members_total_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "contact_group_members" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('contact_group_groups', 'total_member_count', 'false', 'profile_id', 'profile_id', 'counter', 'group_counter_id');


----------------------------------------------------------------------------------------------------


