DROP TABLE IF EXISTS acl_members CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE acl_members (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    identifier          varchar(128),
    
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
    acl_counter         bigint NOT NULL DEFAULT 1 CHECK (acl_counter >= 1),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    activation          timestamp WITH TIME ZONE CHECK (activation >= creation),
    expiration          timestamp WITH TIME ZONE NOT NULL DEFAULT (now() + '1 Day') CHECK (expiration >= creation),
    
    member_amount       real CHECK (member_amount > 0),
    member_quantity     smallint CHECK (member_quantity > 0),
    member_interval     member_interval,
    member_auto_renew   boolean DEFAULT 't',
    
    cc_exp_month        smallint CHECK (cc_exp_month >= 1 AND cc_exp_month <= 12),
    cc_exp_year         smallint,
    
    referrer_profile_id bigint
    						REFERENCES profile_profiles (id)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE,
    						
    click_track         varchar(256),
    
    serialized_result   text,
    
	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't', -- 't'=ACTIVE, 'f'=NO MORE But hasn't expired yet NULL=DONE - Forbidden
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter, item_counter, acl_counter) REFERENCES acl_acls (com_id, net_id, via_id, module_id, matrix_counter, item_counter, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE UNIQUE INDEX acl_members_com_net_via_module_matrix_counter_counter_acl_x ON acl_members (com_id, net_id, via_id, module_id, matrix_counter, item_counter, acl_counter, profile_id);

ALTER TABLE public.acl_members OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "acl_members_updated_trigger" BEFORE UPDATE ON "acl_members" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

--Update the acl_acls total_w_member_count field on inserts and deletes
CREATE TRIGGER "acl_members_total_w_member_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "acl_members" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('acl_acls', 'total_w_member_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'matrix_counter', 'matrix_counter', 'item_counter', 'item_counter', 'counter', 'acl_counter');


----------------------------------------------------------------------------------------------------


