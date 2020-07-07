DROP TABLE IF EXISTS scneol_members CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE scneol_members (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    first_name          varchar(32),
    last_name           varchar(32),
    
    email               varchar(256) NOT NULL,
    
    mobile_number       varchar(256),
    confirm_code        varchar(32),
    confirmed           boolean,
    
    ip_address          inet,
    
    signup_entrance     varchar(256),
    click_track         varchar(256),
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
--CREATE UNIQUE INDEX scneol_members_email_lc_x ON scneol_members (lower(email)); -- Unique emails only
--CREATE INDEX scneol_members_id_active_email_lc_x ON scneol_members (id, active, lower(email));

ALTER TABLE public.scneol_members OWNER TO vmdbuser;
ALTER TABLE public.scneol_members_id_seq OWNER TO vmdbuser;

--ALTER TABLE ONLY scneol_members ADD CONSTRAINT scneol_members_referrer_member_fkey FOREIGN KEY (referrer_member_id) REFERENCES scneol_members(id) ON UPDATE CASCADE ON DELETE SET DEFAULT;

--Update the updated field on updates
CREATE TRIGGER "scneol_members_updated_trigger" BEFORE UPDATE ON "scneol_members" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

--Disallow updating certain fields
--CREATE TRIGGER "scneol_members_disallow_updates_trigger" BEFORE UPDATE ON "scneol_members" FOR EACH ROW EXECUTE PROCEDURE "disallow_updates" (0, 'first_name', 'middle_name', 'last_name', 'referrer_profile_id', 'community_id');


----------------------------------------------------------------------------------------------------


