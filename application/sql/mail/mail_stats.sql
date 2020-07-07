DROP TABLE IF EXISTS mail_stats CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE mail_stats (
    profile_id              bigint NOT NULL
        						REFERENCES profile_profiles (id)
        						ON DELETE CASCADE
        						ON UPDATE CASCADE,
    
    possible_new_mail       boolean,
    
    total_new_mail_count    bigint,
    
    updated                 timestamp WITH TIME ZONE NOT NULL DEFAULT now()
);

ALTER TABLE public.mail_stats OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "mail_stats_updated_trigger" BEFORE UPDATE ON "mail_stats" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Function to resync the mail_stats table
--Usage : resync_mail_stats(profile_id) returns number_of_new_messages
DROP FUNCTION IF EXISTS "resync_mail_stats" () CASCADE;
CREATE OR REPLACE FUNCTION "resync_mail_stats" (bigint) RETURNS bigint AS $$
DECLARE
    iprofileid  ALIAS FOR $1;
    
    command     text;
    same_member bool;
    val         bigint;
    rec         RECORD;
  BEGIN
    IF (iprofileid NOTNULL) THEN
        -- Get ACL params
        command := 'SELECT * FROM (SELECT 1 AS orderby, module_id, acl FROM module_matrix WHERE via_id=' || quote_literal(iprofileid) || ' AND module_id=table_key_to_val(''id'', ''module_modules'', ''name'', ''mail'')::bigint UNION SELECT 2 AS orderby, 0 AS module_id, acl FROM profile_profiles WHERE id=' || quote_literal(iprofileid) || ') AS a ORDER BY orderby LIMIT 1';
        EXECUTE command INTO STRICT rec;
        -- Update all status_rejected first
        --command := 'UPDATE mail_mails SET status_rejected=array_distinct(status_rejected || ARRAY[' || quote_literal(iprofileid) || '::bigint]) WHERE (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id)) AND (status_accepted ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_accepted)) AND (status_rejected ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_rejected)) AND (status_read ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_read)) AND (status_deleted ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_deleted)) AND (status_perm_deleted ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_perm_deleted)) AND (self_destruct ISNULL OR self_destruct > now()) AND (template_status ISNULL) AND (check_acl(';
        command := 'UPDATE ONLY mail_mails m SET status_rejected=array_distinct(m.status_rejected || ARRAY[' || quote_literal(iprofileid) || '::bigint]) FROM profile_profiles p1, profile_profiles p2 WHERE (m.profile_id=p1.id AND p2.id=' || quote_literal(iprofileid) || ' AND (p1.member_id <> p2.member_id OR NOT p2.same_member_priv)) AND (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id)) AND (status_accepted ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_accepted)) AND (self_destruct ISNULL OR self_destruct > now()) AND (template_status ISNULL) AND (check_acl(';
        IF (rec.acl ISNULL) THEN
            command := command || 'NULL';
        ELSE
            command := command || quote_literal(rec.acl);
        END IF;
        command := command || ', profile_id, 0, 0, ' || quote_literal(iprofileid) || ', ' || quote_literal(rec.module_id) || ', 0, 0, 20)).allowed=''f''';
        --RAISE NOTICE '%', command;
        EXECUTE command;
        
        -- Get new mail count
        command := 'SELECT COUNT(*) FROM mail_mails WHERE (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id)) AND (status_rejected ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_rejected)) AND (status_read ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_read)) AND (status_deleted ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_deleted)) AND (status_perm_deleted ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_perm_deleted)) AND (self_destruct ISNULL OR self_destruct > now()) AND (template_status ISNULL)';
        EXECUTE command INTO val;
        
        IF (val > 0) THEN
            -- Update mail_mails set status_accepted
            command := 'UPDATE mail_mails SET status_accepted=array_distinct(status_accepted || ARRAY[' || quote_literal(iprofileid) || '::bigint]) WHERE (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id)) AND (status_rejected ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_rejected)) AND (status_read ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_read)) AND (status_deleted ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_deleted)) AND (status_perm_deleted ISNULL OR NOT (ARRAY[' || quote_literal(iprofileid) || '::bigint] <@ status_perm_deleted)) AND (self_destruct ISNULL OR self_destruct > now()) AND (template_status ISNULL)';
            EXECUTE command;
        END IF;
        
        -- Replace this function with MERGE command when 8.4 is released
        -- Update mail_stats with number of new messages
        command := 'UPDATE mail_stats SET possible_new_mail=''f'', total_new_mail_count=' || quote_literal(val) || ' WHERE profile_id=' || quote_literal(iprofileid);
        EXECUTE command;
    
        RETURN val;
        
    END IF;
    
    RETURN NULL;
  END;
$$ LANGUAGE 'plpgsql';


----------------------------------------------------------------------------------------------------


