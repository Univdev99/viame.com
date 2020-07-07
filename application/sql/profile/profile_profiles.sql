DROP TABLE IF EXISTS profile_profiles CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE profile_profiles (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    member_id           bigint NOT NULL
    						REFERENCES member_members (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    same_member_priv    bool NOT NULL DEFAULT 't',
    
    name                varchar(98) NOT NULL,
    
    meta_title          varchar(256),
    meta_description    varchar(512),
    meta_keywords       varchar(512),
    
    community_id		bigint NOT NULL DEFAULT 1
    						REFERENCES system_communities (id)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE,
    community_ids       bigint[],
    
    base				bool NOT NULL DEFAULT 'f',
    default_profile		bool NOT NULL DEFAULT 'f',
    
    picture_url         varchar(512),
    
    total_contact_count bigint DEFAULT 0 CHECK (total_contact_count >= 0),
    
    total_followers_count   bigint DEFAULT 0 CHECK (total_followers_count >= 0),
    total_following_count   bigint DEFAULT 0 CHECK (total_following_count >= 0),
    
    show_on_fail        boolean DEFAULT 'f',
    
    editor_preference   smallint,
    
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
	
	google_news_approved            boolean DEFAULT 'f',
	profile_google_news_approved    boolean DEFAULT 'f',
	
	site_admin          boolean DEFAULT 'f',
	
    acl                 boolean,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

CREATE UNIQUE INDEX profile_profiles_member_name_lc_x ON profile_profiles (member_id, lower(name));
--CREATE INDEX profile_profiles_member_active_x ON profile_profiles (member_id, active);

ALTER TABLE public.profile_profiles OWNER TO vmdbuser;
ALTER TABLE public.profile_profiles_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "profile_profiles_updated_trigger" BEFORE UPDATE ON "profile_profiles" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


