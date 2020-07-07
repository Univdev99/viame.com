DROP TABLE IF EXISTS member_leads CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE member_leads (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    first_name          varchar(32),
    last_name           varchar(32),
    
    email               varchar(256),
    
    phone               varchar(32),
    phone_type          varchar(32),
    phone_confirm_code  varchar(32),
    phone_confirmed     boolean,
    
    ip_address          inet,
    
    signup_entrance     varchar(256),
    click_track         varchar(256),
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool -- NULL=created but not yet active and not deactivated
);

ALTER TABLE public.member_leads OWNER TO vmdbuser;
ALTER TABLE public.member_leads_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "member_leads_updated_trigger" BEFORE UPDATE ON "member_leads" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


