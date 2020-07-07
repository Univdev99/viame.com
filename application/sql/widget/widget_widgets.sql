DROP TABLE IF EXISTS widget_widgets CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE widget_widgets (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    member_id           bigint NOT NULL DEFAULT 1
    						REFERENCES member_members (id)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE, -- If member id gets deleted set to DEFAULT of 1; if updated, cascade
    						
    name                varchar(256) NOT NULL CHECK (name !~ '[^-a-z0-9]+'),
    display             varchar(256) NOT NULL,
    description			text,
    
    admin_only          boolean DEFAULT 'f',
    
	parameters          text[],
	
	allow_multiple      boolean DEFAULT 't',
	
	status              boolean DEFAULT 't',
	    
	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              boolean DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX widget_widgets_name_x ON widget_widgets (name); -- Unique names only
--CREATE INDEX widget_widgets_id_member_active_x ON widget_widgets (id, member_id, active);

ALTER TABLE public.widget_widgets OWNER TO vmdbuser;
ALTER TABLE public.widget_widgets_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "widget_widgets_updated_trigger" BEFORE UPDATE ON "widget_widgets" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


