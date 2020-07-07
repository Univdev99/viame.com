DROP TABLE IF EXISTS member_openids CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE member_openids (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    url                 varchar(256) NOT NULL,
    
    member_id           bigint NOT NULL
    						REFERENCES member_members (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX member_openids_url_lc_x ON member_openids (lower(url)); -- Unique urls only
--CREATE INDEX member_openids_member_id_active_url_lc_x ON member_openids (member_id, active, lower(url));

ALTER TABLE public.member_openids OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "member_openids_updated_trigger" BEFORE UPDATE ON "member_openids" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "member_openids_counter_trigger" BEFORE INSERT ON "member_openids" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'member_id');


----------------------------------------------------------------------------------------------------


