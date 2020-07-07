DROP TABLE IF EXISTS company_companies CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE company_companies (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    						
    name                varchar(256) NOT NULL,
    
    sector_id           bigint
    						REFERENCES company_sectors (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    						
    website             varchar(256),
    
    summary             text,
    
    total_locations_count   bigint DEFAULT 0 CHECK (total_locations_count >= 0),
    
    orderby             real,
    
    search              tsvector,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
--CREATE UNIQUE INDEX company_companies_company_x ON company_companies (name);
--CREATE INDEX company_companies_id_code_x ON company_companies (id, company);

ALTER TABLE public.company_companies OWNER TO vmdbuser;
ALTER TABLE public.company_companies_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "company_companies_updated_trigger" BEFORE UPDATE ON "company_companies" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Update the search field
CREATE TRIGGER "company_companies_update_search_trigger" BEFORE INSERT OR UPDATE ON "company_companies" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'summary', 'A', 'name', 'B');


----------------------------------------------------------------------------------------------------


