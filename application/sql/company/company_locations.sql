DROP TABLE IF EXISTS company_locations CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE company_locations (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    
    company_id          bigint NOT NULL
    						REFERENCES company_companies (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    						
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 0),
    					
    name                varchar(256),
    description         text,
    
    main_location       boolean,
    
    address1            varchar(256) NOT NULL,
    address2            varchar(256),
    city                varchar(256) NOT NULL,
    state               varchar(256) NOT NULL,
    postal_code         varchar(32) NOT NULL,
    "country"           varchar(8) NOT NULL DEFAULT 'US'
                            REFERENCES system_countries (code)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE,
    
    orderby             real,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX company_locations_company_id_counter_x ON company_locations (company_id, counter);
--CREATE INDEX company_locations_id_code_x ON company_locations (id, company);

ALTER TABLE public.company_locations OWNER TO vmdbuser;
ALTER TABLE public.company_locations_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "company_locations_updated_trigger" BEFORE UPDATE ON "company_locations" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "company_locations_counter_trigger" BEFORE INSERT ON "company_locations" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'company_id');

--Update the total_item_count in module_matrix
CREATE TRIGGER "company_locations_total_locations_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "company_locations" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('company_companies', 'total_locations_count', 'false', 'id', 'company_id');


----------------------------------------------------------------------------------------------------


