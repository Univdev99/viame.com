DROP TABLE IF EXISTS company_industries CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE company_industries (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),

    industry            varchar(256) NOT NULL,
    
    orderby             real,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX company_industries_industry_x ON company_industries (industry);
--CREATE INDEX company_industries_id_code_x ON company_industries (id, industry);

ALTER TABLE public.company_industries OWNER TO vmdbuser;
ALTER TABLE public.company_industries_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "company_industries_updated_trigger" BEFORE UPDATE ON "company_industries" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


INSERT INTO company_industries (industry) VALUES ('Basic Materials');
INSERT INTO company_industries (industry) VALUES ('Conglomerates');
INSERT INTO company_industries (industry) VALUES ('Consumer Goods');
INSERT INTO company_industries (industry) VALUES ('Financial');
INSERT INTO company_industries (industry) VALUES ('Healthcare');
INSERT INTO company_industries (industry) VALUES ('Industrial Goods');
INSERT INTO company_industries (industry) VALUES ('Services');
INSERT INTO company_industries (industry) VALUES ('Technology');
INSERT INTO company_industries (industry) VALUES ('Utilities');