DROP TABLE IF EXISTS system_communities CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE system_communities (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    member_id           bigint NOT NULL DEFAULT 1
    						REFERENCES member_members (id)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE, -- If member id gets deleted set to DEFAULT of 1; if updated, cascade
    						
    name                varchar(256) NOT NULL CHECK (name !~ '[^-a-z0-9_]+'),
    display             varchar(256) NOT NULL,
    summary             text,
    
    meta_title          varchar(256),
    meta_description    varchar(512),
    meta_keywords       varchar(512),
    
    meta_stocks         boolean DEFAULT 'f',
    
    hostname            varchar(256),
    hostname_positive_regexps    text[],
    hostname_negative_regexps    text[],
    
    picture_url         varchar(512),
    
    email               varchar(256),
    noreply_email       varchar(256),
    
    default_modules     text[],
    
	layout			    varchar(256) CHECK (layout !~ '[^-a-z0-9_]+'), -- Delay Reference Check Until After Table Creation
	
	revenue_share       smallint CHECK (revenue_share >= 0 AND revenue_share <= 100), -- Percent Revenue Share
	
	parent_id           bigint,
	
	il_ad_id            varchar(256),   -- Inline Text Ad ID
	greenarrow_list_id  int,            -- GreenArrow List ID
	
	show_on_fail      boolean DEFAULT 'f',
	
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
	
	orderby             real,
	
	acl                 boolean,
	
	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX system_communities_name_x ON system_communities (name); -- Unique names only
--CREATE INDEX system_communities_id_member_active_x ON system_communities (id, member_id, active);

ALTER TABLE public.system_communities OWNER TO vmdbuser;
ALTER TABLE public.system_communities_id_seq OWNER TO vmdbuser;

ALTER TABLE ONLY system_communities ADD CONSTRAINT system_communities_parent_fkey FOREIGN KEY (parent_id) REFERENCES system_communities(id) ON UPDATE CASCADE ON DELETE SET DEFAULT;
ALTER TABLE ONLY system_communities ADD CONSTRAINT system_communities_layout_fkey FOREIGN KEY (layout) REFERENCES system_communities(name) ON UPDATE CASCADE ON DELETE SET DEFAULT;

--Update the updated field on updates
CREATE TRIGGER "system_communities_updated_trigger" BEFORE UPDATE ON "system_communities" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


