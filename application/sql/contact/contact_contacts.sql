DROP TABLE IF EXISTS contact_contacts CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE contact_contacts (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    contact_profile_id  bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,

    display             varchar(98),
    description			text,

    message             varchar(256),
    auto_reciprocate    boolean DEFAULT 't',
    status              boolean,
    
	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              boolean DEFAULT 't' -- NULL=not displayed anymore - Forbidden
);

CREATE UNIQUE INDEX contact_contacts_profile_contact_profile_x ON contact_contacts (profile_id, contact_profile_id);
--CREATE INDEX contact_contacts_profile_status_active_x ON contact_contacts (profile_id, status, active);
--CREATE INDEX contact_contacts_profile_contact_profile_status_active_x ON contact_contacts (profile_id, contact_profile_id, status, active);

ALTER TABLE public.contact_contacts OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "contact_contacts_updated_trigger" BEFORE UPDATE ON "contact_contacts" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

--Update the profile_profiles total_contact_count field on inserts and deletes
CREATE TRIGGER "profile_contacts_total_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "contact_contacts" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('profile_profiles', 'total_contact_count', 'true', 'id', 'profile_id');


----------------------------------------------------------------------------------------------------


