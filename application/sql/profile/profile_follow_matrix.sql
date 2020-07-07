DROP TABLE IF EXISTS profile_follow_matrix CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE profile_follow_matrix (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    follow_profile_id   bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,

    status              boolean DEFAULT 't',
    
	updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              boolean DEFAULT 't'
);

CREATE UNIQUE INDEX profile_follow_matrix_profile_follow_profile_x ON profile_follow_matrix (profile_id, follow_profile_id);

ALTER TABLE public.profile_follow_matrix OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "profile_follow_matrix_updated_trigger" BEFORE UPDATE ON "profile_follow_matrix" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

--Update the profile_profiles total_followers_count and total_following_count field on inserts and deletes
CREATE TRIGGER "profile_follow_total_followers_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "profile_follow_matrix" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('profile_profiles', 'total_followers_count', 'true', 'id', 'follow_profile_id');

CREATE TRIGGER "profile_follow_total_following_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "profile_follow_matrix" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('profile_profiles', 'total_following_count', 'true', 'id', 'profile_id');


----------------------------------------------------------------------------------------------------


