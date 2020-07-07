DROP TABLE IF EXISTS contact_group_groups CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE contact_group_groups (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    name                varchar(98) NOT NULL,
    description			text,
    
    parent_id           bigint, --  CHECK (parent_id < counter) Delay Reference Check Until After Table Creation
    
    total_member_count  bigint DEFAULT 0 CHECK (total_member_count >= 0),
    
    acl                 boolean,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              boolean DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

CREATE UNIQUE INDEX contact_group_groups_profile_counter_x ON contact_group_groups (profile_id, counter);
CREATE UNIQUE INDEX contact_group_groups_profile_name_lc_x ON contact_group_groups (profile_id, lower(name));

ALTER TABLE public.contact_group_groups OWNER TO vmdbuser;

ALTER TABLE ONLY contact_group_groups ADD CONSTRAINT contact_group_groups_parent_fkey FOREIGN KEY (profile_id, parent_id) REFERENCES contact_group_groups(profile_id, counter) ON UPDATE CASCADE ON DELETE CASCADE;

--Update the updated field on updates
CREATE TRIGGER "contact_group_groups_updated_trigger" BEFORE UPDATE ON "contact_group_groups" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "contact_group_groups_counter_trigger" BEFORE INSERT ON "contact_group_groups" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'profile_id');


----------------------------------------------------------------------------------------------------


