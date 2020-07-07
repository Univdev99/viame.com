DROP TABLE IF EXISTS acl_promos CASCADE; -- CASCADE will drop all references to this table

DROP TYPE IF EXISTS acl_promos_discount_type;
CREATE TYPE acl_promos_discount_type AS ENUM ('$', '%');

CREATE TABLE acl_promos (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    com_id              bigint NOT NULL DEFAULT 0
    						REFERENCES system_communities (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,  -- If community id gets deleted cascade; if updated, cascade
    net_id              bigint NOT NULL DEFAULT 0
    						REFERENCES network_networks (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,  -- If community id gets deleted cascade; if updated, cascade
    via_id              bigint NOT NULL DEFAULT 0
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted cascade; if updated, cascade
    
    module_id           bigint NOT NULL DEFAULT 0
    						REFERENCES module_modules (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    matrix_counter      bigint NOT NULL DEFAULT 0 CHECK (matrix_counter >= 0),
    item_counter        bigint NOT NULL DEFAULT 0 CHECK (item_counter >= 0),
    acl_counter         bigint NOT NULL DEFAULT 0 CHECK (acl_counter >= 0),
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    
    code                varchar(32) NOT NULL,
    title               varchar(512),
    display             varchar(512),
    description         varchar(512),
    
    w_member_initial_amount_discount        real CHECK (w_member_initial_amount_discount > 0),
    w_member_initial_amount_discount_type   acl_promos_discount_type,
    w_member_trial_amount_discount          real CHECK (w_member_trial_amount_discount > 0),
    w_member_trial_amount_discount_type     acl_promos_discount_type,
    w_member_amount_discount                real CHECK (w_member_amount_discount > 0),
    w_member_amount_discount_type           acl_promos_discount_type,
        
    activation          timestamp WITH TIME ZONE CHECK (activation >= creation),
    expiration          timestamp WITH TIME ZONE CHECK (expiration >= creation),
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't',
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
);

-- 
CREATE UNIQUE INDEX acl_promos_com_net_via_module_matrix_counter_acl_counter_counter_x ON acl_promos (com_id, net_id, via_id, module_id, matrix_counter, item_counter, acl_counter, counter);
-- No Duplicate Codes Per SPACE
CREATE UNIQUE INDEX acl_promos_com_net_via_code_x ON acl_promos (com_id, net_id, via_id, lower(code));

ALTER TABLE public.acl_promos OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "acl_promos_updated_trigger" BEFORE UPDATE ON "acl_promos" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "acl_promos_counter_trigger" BEFORE INSERT ON "acl_promos" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'module_id', 'matrix_counter', 'item_counter', 'acl_counter');
