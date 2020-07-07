DROP TABLE IF EXISTS network_members CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE network_members (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    network_id          bigint NOT NULL
    						REFERENCES network_networks (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    member_profile_id   bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,

    request_message     varchar(256),
    status              bool,
    
	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=not displayed anymore - Forbidden
);

CREATE UNIQUE INDEX network_members_network_member_profile_x ON network_members (network_id, member_profile_id);
--CREATE INDEX network_members_network_member_profile_active_x ON network_members (network_id, member_profile_id, active);

ALTER TABLE public.network_members OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "network_members_updated_trigger" BEFORE UPDATE ON "network_members" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

--Update the network_networks member_total_count field 
CREATE TRIGGER "network_members_total_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "network_members" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('network_networks', 'total_member_count', 'true', 'id', 'network_id');


----------------------------------------------------------------------------------------------------


