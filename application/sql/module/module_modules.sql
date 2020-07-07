DROP TABLE IF EXISTS module_modules CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE module_modules (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    member_id           bigint NOT NULL DEFAULT 1
    						REFERENCES member_members (id)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE, -- If member id gets deleted set to DEFAULT of 1; if updated, cascade
    						
    name                varchar(256) NOT NULL CHECK (name !~ '[^-a-z0-9]+'),
    display             varchar(256) NOT NULL,
    description			text,
    
    parameters          text[],
    
	customizable        boolean DEFAULT 't',
	allow_multiple      boolean DEFAULT 'f',
	allow_flow          boolean DEFAULT 't',
	allow_mask          boolean DEFAULT 'f',
	
	system              boolean DEFAULT 'f',
	profile_only        boolean DEFAULT 'f',
	
	item_level_acl      boolean DEFAULT 't',
	
    status              boolean DEFAULT 't',
	
	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              boolean DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX module_modules_name_x ON module_modules (name); -- Unique names only
--CREATE INDEX module_modules_id_member_active_x ON module_modules (id, member_id, active);

ALTER TABLE public.module_modules OWNER TO vmdbuser;
ALTER TABLE public.module_modules_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "module_modules_updated_trigger" BEFORE UPDATE ON "module_modules" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


