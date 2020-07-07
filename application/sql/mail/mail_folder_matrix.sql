DROP TABLE IF EXISTS mail_folder_matrix CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE mail_folder_matrix (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    				    
    folder_counter_id   bigint NOT NULL,  -- Delay Reference Check Until After Table Creation
    
    from_profile_id     bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    from_counter_id     bigint NOT NULL,
    
    active              bool DEFAULT 't',
    
    FOREIGN KEY (profile_id, folder_counter_id) REFERENCES mail_folder_folders(profile_id, counter) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (from_profile_id, from_counter_id) REFERENCES mail_mails(profile_id, counter) MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE UNIQUE INDEX mail_folder_matrix_profile_folder_from_counter_x ON mail_folder_matrix (profile_id, folder_counter_id, from_profile_id, from_counter_id);
--CREATE INDEX mail_folder_matrix_profile_folder_counter_x ON mail_folder_matrix (profile_id, folder_counter_id);

ALTER TABLE public.mail_folder_matrix OWNER TO vmdbuser;

--Update the mail_folder_folders total_mail_count field on inserts and deletes
--CREATE TRIGGER "mail_folder_matrix_total_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "mail_folder_matrix" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('mail_folder_folders', 'total_mail_count', 'false', 'profile_id', 'profile_id', 'counter', 'folder_counter_id');


----------------------------------------------------------------------------------------------------


