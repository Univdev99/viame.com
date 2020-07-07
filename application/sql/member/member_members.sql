DROP TABLE IF EXISTS member_members CASCADE; -- CASCADE will drop all references to this table

DROP TYPE IF EXISTS gender;
CREATE TYPE gender AS ENUM ('M', 'F');

CREATE TABLE member_members (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    first_name          varchar(32),
    middle_name         varchar(32),
    last_name           varchar(32),
    
    phone               varchar(32),
    
    gender              gender,
    dob                 date CHECK (dob < now()),
    postal_code         varchar(32),
    
    email               varchar(256) NOT NULL,
    
    password            varchar(64) NOT NULL, -- Stored as varchar but should be submitted as md5
    password_salt       varchar(64) NOT NULL DEFAULT md5(now()::text), -- Unique password hash
    
    timezone            varchar(64) NOT NULL DEFAULT 'US/Pacific'
                            REFERENCES system_timezones (timezone)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE,
    "country"           varchar(8) NOT NULL DEFAULT 'US'
                            REFERENCES system_countries (code)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE,
    						
    currency           varchar(8) NOT NULL DEFAULT 'USD'
                            REFERENCES system_currencies (code)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE,
    						
    language            varchar(8) NOT NULL DEFAULT 'en'
                            REFERENCES system_languages (code)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE,
    
    ip_address          inet,
    
    signup_entrance     varchar(256),
    click_track         varchar(256),
    
    revenue_share                   smallint CHECK (revenue_share >= 0 AND revenue_share <= 100), -- Percent Revenue Share On Initial
    revenue_share_recurring         smallint CHECK (revenue_share_recurring >= 0 AND revenue_share_recurring <= 100), -- Percent Revenue Share On Recurring
    revenue_share_recurring_count   smallint, -- Maximum number of recurring transactions to share
    
    --referrer_member_id bigint CHECK (referrer_member_id < id),
    referrer_profile_id bigint,
                        -- Delay Reference Check Until Last
    community_id        bigint NOT NULL DEFAULT 1,
                        -- Delay Reference Check Until Last
    referrer_community_id        bigint,
                        -- Delay Reference Check Until Last
                        
    site_admin          boolean DEFAULT 'f',
    
    acl                 boolean,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX member_members_email_lc_x ON member_members (lower(email)); -- Unique emails only
--CREATE INDEX member_members_id_active_email_lc_x ON member_members (id, active, lower(email));

ALTER TABLE public.member_members OWNER TO vmdbuser;
ALTER TABLE public.member_members_id_seq OWNER TO vmdbuser;

--ALTER TABLE ONLY member_members ADD CONSTRAINT member_members_referrer_member_fkey FOREIGN KEY (referrer_member_id) REFERENCES member_members(id) ON UPDATE CASCADE ON DELETE SET DEFAULT;

--Update the updated field on updates
CREATE TRIGGER "member_members_updated_trigger" BEFORE UPDATE ON "member_members" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

--Disallow updating certain fields
--CREATE TRIGGER "member_members_disallow_updates_trigger" BEFORE UPDATE ON "member_members" FOR EACH ROW EXECUTE PROCEDURE "disallow_updates" (0, 'first_name', 'middle_name', 'last_name', 'referrer_profile_id', 'community_id');


----------------------------------------------------------------------------------------------------


