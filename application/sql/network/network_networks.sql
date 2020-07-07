DROP TABLE IF EXISTS network_networks CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE network_networks (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    						
    name                varchar(98) NOT NULL,
    summary             text,
    category            text,
    
    meta_title          varchar(256),
    meta_description    varchar(512),
    meta_keywords       varchar(512),
    
    community_id		bigint NOT NULL DEFAULT 1
    						REFERENCES system_communities (id)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE,
    						
    public              boolean DEFAULT 'f',
    open                boolean DEFAULT 't',
    password            varchar(256),
    allow_requests      boolean DEFAULT 't',
    
    parent_id           bigint, --  CHECK (parent_id < id) Delay Reference Check Until After Table Creation
    
    picture_url         varchar(512),
    
    total_member_count  bigint DEFAULT 0 CHECK (total_member_count >= 0),
    
    show_on_fail        boolean DEFAULT 'f',
    
    page_width          varchar(10),
	page_layout         varchar(10),
	page_sublayout      varchar(10),
	page_theme          varchar(256),
	page_style          text,
	
	grid_hd             text[] NOT NULL DEFAULT '{}',
	grid_ft             text[] NOT NULL DEFAULT '{}',
	grid_cx             text[] NOT NULL DEFAULT '{}',
	grid_c1             text[] NOT NULL DEFAULT '{}',
	grid_c2             text[] NOT NULL DEFAULT '{}',
	grid_c3             text[] NOT NULL DEFAULT '{}',
	grid_c4             text[] NOT NULL DEFAULT '{}',
	
	google_news_approved        boolean DEFAULT 'f',
	
    acl                 boolean,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

CREATE UNIQUE INDEX network_networks_profile_name_lc_x ON network_networks (profile_id, lower(name));
--CREATE INDEX network_networks_profile_active_x ON network_networks (profile_id, active);

ALTER TABLE public.network_networks OWNER TO vmdbuser;
ALTER TABLE public.network_networks_id_seq OWNER TO vmdbuser;

ALTER TABLE ONLY network_networks ADD CONSTRAINT network_networks_parent_fkey FOREIGN KEY (parent_id) REFERENCES network_networks(id) ON UPDATE CASCADE ON DELETE SET DEFAULT;

--Update the updated field on updates
CREATE TRIGGER "network_networks_updated_trigger" BEFORE UPDATE ON "network_networks" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


