DROP TABLE IF EXISTS mail_folder_folders CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE mail_folder_folders (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    name                varchar(98) NOT NULL,
    description			text,
    
    parent_id           bigint, --  CHECK (parent_id < counter) Delay Reference Check Until After Table Creation
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

CREATE UNIQUE INDEX mail_folder_folders_profile_counter_x ON mail_folder_folders (profile_id, counter);
CREATE UNIQUE INDEX mail_folder_folders_profile_name_lc_x ON mail_folder_folders (profile_id, lower(name));

ALTER TABLE public.mail_folder_folders OWNER TO vmdbuser;

ALTER TABLE ONLY mail_folder_folders ADD CONSTRAINT mail_folder_folders_parent_fkey FOREIGN KEY (profile_id, parent_id) REFERENCES mail_folder_folders(profile_id, counter) ON UPDATE CASCADE ON DELETE CASCADE;

--Update the updated field on updates
CREATE TRIGGER "mail_folder_folders_updated_trigger" BEFORE UPDATE ON "mail_folder_folders" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "mail_folder_folders_counter_trigger" BEFORE INSERT ON "mail_folder_folders" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'profile_id');


----------------------------------------------------------------------------------------------------


