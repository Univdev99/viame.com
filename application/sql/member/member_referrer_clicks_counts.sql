DROP TABLE IF EXISTS member_referrer_clicks_counts CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE member_referrer_clicks_counts (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL DEFAULT 0
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    ip_address          inet,
    
    active              bool DEFAULT 't'
);

ALTER TABLE public.member_referrer_clicks_counts OWNER TO vmdbuser;


----------------------------------------------------------------------------------------------------


