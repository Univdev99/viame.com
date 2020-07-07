DROP TABLE IF EXISTS member_social_logins CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE member_social_logins (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    member_id           bigint NOT NULL
    						REFERENCES member_members (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    provider            varchar(256) NOT NULL,
    
    id                  varchar(256) NOT NULL,
    
    --counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX member_social_logins_provider_id_lc_x ON member_social_logins (provider, lower(id)); -- Unique provider/ids only

ALTER TABLE public.member_social_logins OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "member_social_logins_updated_trigger" BEFORE UPDATE ON "member_social_logins" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
--CREATE TRIGGER "member_social_logins_counter_trigger" BEFORE INSERT ON "member_social_logins" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'member_id', 'provider');


----------------------------------------------------------------------------------------------------


