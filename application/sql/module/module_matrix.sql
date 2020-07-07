DROP TABLE IF EXISTS module_matrix CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE module_matrix (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    com_id              bigint NOT NULL DEFAULT 0
    						REFERENCES system_communities (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    net_id              bigint NOT NULL DEFAULT 0
    						REFERENCES network_networks (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    via_id              bigint NOT NULL DEFAULT 0
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    module_id           bigint NOT NULL DEFAULT 0
    						REFERENCES module_modules (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 0),

    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    						
    display             varchar(256),
    secondary           varchar(256),
    secondary_url       varchar(512),
    
    content             text,
    
    meta_title          varchar(256),
    meta_description    varchar(512),
    meta_keywords       varchar(512),
    
    parameter_values    text[],
    
	allow_out_flow              boolean DEFAULT 't',
	allow_in_flow               boolean DEFAULT 't',
	
	allow_community_outflow     bigint[],
	allow_community_inflow      bigint[][],
	deny_community_inflow       bigint[][],
	allow_network_outflow       bigint[],
	allow_network_inflow        bigint[][],
	deny_network_inflow         bigint[][],
	allow_profile_outflow       bigint[],
	allow_profile_inflow        bigint[][],
	deny_profile_inflow         bigint[][],
	
	deny_via_user_inflow        bigint[],
	
	allow_community_mask        bigint[],
	community_mask              bigint
            						REFERENCES system_communities (id)
            						ON DELETE CASCADE
            						ON UPDATE CASCADE,
	allow_network_mask          bigint[],
	network_mask                bigint
            						REFERENCES network_networks (id)
            						ON DELETE CASCADE
            						ON UPDATE CASCADE,
	allow_profile_mask          bigint[],
	profile_mask                bigint
            						REFERENCES profile_profiles (id)
            						ON DELETE CASCADE
            						ON UPDATE CASCADE,
	mask_counter                bigint,
	
	total_item_count    bigint DEFAULT 0 CHECK (total_item_count >= 0),
	
	publicly_searchable boolean DEFAULT 't',
	interactive         boolean DEFAULT 't',
	moderated           boolean DEFAULT 'f',
	show_on_fail        boolean DEFAULT 'f',
	
	display_orderby     real,
	display_stack       varchar(256),
	
	widget              boolean DEFAULT 't',
	widget_hide_summary boolean,
	widget_max          int,
	
	google_news_approved        boolean DEFAULT 'f',
	
	orderby             real,
	
	acl                 boolean,

	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't', -- NULL=created but not yet active and not deactivated
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0))
);

CREATE UNIQUE INDEX module_matrix_com_net_via_module_counter_x ON module_matrix (com_id, net_id, via_id, module_id, counter);
--CREATE INDEX module_matrix_com_module_counter_active_x ON module_matrix (com_id, module_id, counter, active);
--CREATE INDEX module_matrix_net_module_counter_active_x ON module_matrix (net_id, module_id, counter, active);
--CREATE INDEX module_matrix_via_module_counter_active_x ON module_matrix (via_id, module_id, counter, active);

ALTER TABLE public.module_matrix OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "module_matrix_updated_trigger" BEFORE UPDATE ON "module_matrix" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamically update the counter
CREATE TRIGGER "module_matrix_counter_trigger" BEFORE INSERT ON "module_matrix" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'module_id');

-- Function to check to see if a matrix_counter exists in the module_matrix table
DROP FUNCTION IF EXISTS "check_module_matrix" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "check_module_matrix" () RETURNS trigger AS $$
  DECLARE
    module      text;
    command     text;
    valid       bool;
    
  BEGIN
    SELECT SUBSTR(TG_TABLE_NAME, 0, STRPOS(TG_TABLE_NAME, '_')) INTO STRICT module;
    
    command := 'SELECT ''t''::bool, id FROM module_matrix, module_modules WHERE module_id=id AND name=' || quote_literal(module);
    
    command := command || ' AND com_id ';
    IF (NEW.com_id ISNULL) THEN
        command := command || 'ISNULL';
    ELSE
        command := command || '=' || quote_literal(NEW.com_id);
    END IF;
    
    command := command || ' AND net_id ';
    IF (NEW.net_id ISNULL) THEN
        command := command || 'ISNULL';
    ELSE
        command := command || '=' || quote_literal(NEW.net_id);
    END IF;
    
    command := command || ' AND via_id ';
    IF (NEW.via_id ISNULL) THEN
        command := command || 'ISNULL';
    ELSE
        command := command || '=' || quote_literal(NEW.via_id);
    END IF;
    
    command := command || ' AND counter=' || quote_literal(NEW.matrix_counter);
    
    --RAISE NOTICE '%', command;
    
    EXECUTE command INTO valid, NEW.module_id;
    
    IF (valid) THEN
        RETURN NEW;
    ELSE
        RAISE EXCEPTION  'Invalid module matrix counter.';
        RETURN NULL;
    END IF;
    
    -- Default to return true; Can't hurt...
    RETURN NEW;
  END;
$$ LANGUAGE 'plpgsql';


-- Function to insert a default value into module_matrix
DROP FUNCTION IF EXISTS "insert_default_module_matrix" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "insert_default_module_matrix" () RETURNS trigger AS $$
  BEGIN
    
    IF ((TG_ARGV[0] NOTNULL) AND (NEW.id > 0)) THEN
      EXECUTE 'INSERT INTO module_matrix (' || quote_ident(TG_ARGV[0]) || ', counter) VALUES (' || NEW.id || ', 0)';
    END IF;
    
    RETURN NULL;
  END;
$$ LANGUAGE 'plpgsql';
--Insert defaults for module matrix
CREATE TRIGGER "insert_default_com_module_matrix_trigger" AFTER INSERT ON "system_communities" FOR EACH ROW EXECUTE PROCEDURE "insert_default_module_matrix" ('com_id');
CREATE TRIGGER "insert_default_net_module_matrix_trigger" AFTER INSERT ON "network_networks" FOR EACH ROW EXECUTE PROCEDURE "insert_default_module_matrix" ('net_id');
CREATE TRIGGER "insert_default_via_module_matrix_trigger" AFTER INSERT ON "profile_profiles" FOR EACH ROW EXECUTE PROCEDURE "insert_default_module_matrix" ('via_id');


----------------------------------------------------------------------------------------------------


