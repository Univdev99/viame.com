DROP TABLE IF EXISTS mail_mails CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE mail_mails (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    to_profile_id       bigint[] NOT NULL DEFAULT '{}',
    cc_profile_id       bigint[] NOT NULL DEFAULT '{}',
    bcc_profile_id      bigint[] NOT NULL DEFAULT '{}',
    
    status_accepted     bigint[] NOT NULL DEFAULT '{}',
    status_rejected     bigint[] NOT NULL DEFAULT '{}',
    status_read         bigint[] NOT NULL DEFAULT '{}',
    status_deleted      bigint[] NOT NULL DEFAULT '{}',
    status_perm_deleted bigint[] NOT NULL DEFAULT '{}',
    
    priority            int,
    						
    subject             varchar(256) NOT NULL,
    content             text NOT NULL,
    
    search              tsvector,
    
    self_destruct       timestamp WITH TIME ZONE CHECK (self_destruct >= creation),
    
    template_status     bool,
    display_in_trash    bool,
    
    active              bool DEFAULT 't'
    
    CONSTRAINT valid_space CHECK ((to_profile_id NOTNULL AND array_upper(to_profile_id, 1) > 0) OR (cc_profile_id NOTNULL AND array_upper(cc_profile_id, 1) > 0) OR (bcc_profile_id NOTNULL AND array_upper(bcc_profile_id, 1) > 0))
);

CREATE UNIQUE INDEX mail_mails_profile_id_counter_x ON mail_mails (profile_id, counter);

CREATE INDEX mail_mails_search_index ON mail_mails USING gin(search);

ALTER TABLE public.mail_mails OWNER TO vmdbuser;

-- Dynamic counter increment
CREATE TRIGGER "mail_mails_counter_trigger" BEFORE INSERT ON "mail_mails" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'profile_id');

-- Update the search field
CREATE TRIGGER "mail_mails_update_search_trigger" BEFORE INSERT OR UPDATE ON "mail_mails" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'content', 'A', 'subject', 'B');

-- Replace this function with MERGE command when 8.4 is released
DROP FUNCTION IF EXISTS "update_mail_stats" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "update_mail_stats" () RETURNS trigger AS $$
DECLARE
    command     text;
    
  BEGIN
    command := 'INSERT INTO mail_stats SELECT list_array(array_distinct(' || quote_literal(NEW.to_profile_id) || '::bigint[] || '  || quote_literal(NEW.cc_profile_id) || '::bigint[] || '  || quote_literal(NEW.bcc_profile_id) || '::bigint[])) EXCEPT SELECT profile_id FROM mail_stats';
    
    EXECUTE command;
    
    command := 'UPDATE mail_stats SET possible_new_mail=''t'' WHERE ARRAY[profile_id] <@ array_distinct(' || quote_literal(NEW.to_profile_id) || '::bigint[] || '  || quote_literal(NEW.cc_profile_id) || '::bigint[] || '  || quote_literal(NEW.bcc_profile_id) || '::bigint[])';
    
    EXECUTE command;
    
    RETURN NEW;
  END;
$$ LANGUAGE 'plpgsql';

--Update the mail_stats table
CREATE TRIGGER "mail_mails_update_stats_trigger" AFTER INSERT ON "mail_mails" FOR EACH ROW EXECUTE PROCEDURE "update_mail_stats" ();


----------------------------------------------------------------------------------------------------


