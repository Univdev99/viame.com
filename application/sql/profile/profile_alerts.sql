DROP TABLE IF EXISTS profile_alerts CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE profile_alerts (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    						
    type                int NOT NULL DEFAULT 1, -- Must be numeric or dynamic_incrementer will fail because values are not quoted
    
    counter             int NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    identifier          varchar(256) NOT NULL,
    
    confirm_code        varchar(32),
    confirmed           boolean,
    
    status              boolean DEFAULT 't',
    
	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              boolean DEFAULT 't'
);

CREATE UNIQUE INDEX profile_alerts_profile_type_counter_x ON profile_alerts (profile_id, type, counter);

ALTER TABLE public.profile_alerts OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "profile_alerts_updated_trigger" BEFORE UPDATE ON "profile_alerts" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "profile_alerts_counter_trigger" BEFORE INSERT ON "profile_alerts" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'profile_id', 'type');


----------------------------------------------------------------------------------------------------


