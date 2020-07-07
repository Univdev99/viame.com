--
-- 10 : Read                    Everyone    
-- 20 : Interact                Registered  boolean     Base
-- 30 : Moderate                Group       bigint[]    Groups
-- 40 : Write                   Profile     bigint[]    Profiles
-- 50 : Edit                    DOS         int         Degrees
-- 60 : Delete                  Private     text        Password
-- 70 : Administer              Member      real        Amount
--                                          text        Interval

DROP TABLE IF EXISTS acl_acls CASCADE; -- CASCADE will drop all references to this table

DROP TYPE IF EXISTS registration_status;
CREATE TYPE registration_status AS ENUM ('E', 'R', 'B');
DROP TYPE IF EXISTS member_interval;
CREATE TYPE member_interval AS ENUM ('Minute', 'Day', 'Week', 'Month', 'Year');

CREATE TABLE acl_acls (
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
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    
    title               varchar(512) NOT NULL,
    display             varchar(512),
    
    orderby             real,
    
    greenarrow_list_id  int,            -- GreenArrow List ID
    
    w_registration_status        registration_status,
    w_groups            bigint[],
    w_contact_profiles  bigint[],
    w_arb_profiles      bigint[],
    w_arb_profiles_ex   bigint[],
    w_dos               smallint CHECK (w_dos > 0),
    w_password                  text,
    total_w_password_count      bigint DEFAULT 0 CHECK (total_w_password_count >= 0),
    
    w_member_start              date CHECK (w_member_start >= creation),
    w_member_initial_amount     real CHECK (w_member_initial_amount > 0),
    w_member_trial_amount       real CHECK (w_member_trial_amount >= 0),
    w_member_trial_quantity     smallint CHECK (w_member_trial_quantity > 0),
    w_member_trial_interval     member_interval,
    w_member_trial_cycles       smallint CHECK (w_member_trial_cycles > 0), -- NOT IN USE RIGHT NOW
    w_member_amount             real CHECK (w_member_amount > 0),
    w_member_quantity           smallint CHECK (w_member_quantity > 0),
    w_member_interval           member_interval,
    w_member_cycles             smallint CHECK (w_member_cycles > 0), -- NOT IN USE RIGHT NOW
    w_member_auto_renew         boolean,
    total_w_member_count        bigint DEFAULT 0 CHECK (total_w_member_count >= 0),
    
    
    privilege           smallint NOT NULL,
    
    filter              text,
    
    description         text,
    
    recursive           boolean DEFAULT 't',
    
    invisible           boolean DEFAULT 'f',
    
    expiration          timestamp WITH TIME ZONE CHECK (expiration >= creation),
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't',
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
);

-- 
CREATE UNIQUE INDEX acl_acls_com_net_via_module_matrix_counter_counter_x ON acl_acls (com_id, net_id, via_id, module_id, matrix_counter, item_counter, counter);

ALTER TABLE public.acl_acls OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "acl_acls_updated_trigger" BEFORE UPDATE ON "acl_acls" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "acl_acls_counter_trigger" BEFORE INSERT ON "acl_acls" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'module_id', 'matrix_counter', 'item_counter');

-- Trigger to not update or delete items that have members in the acl_members table
-- Function to make updated current on updates
DROP FUNCTION IF EXISTS "acl_with_members_check" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "acl_with_members_check" () RETURNS trigger AS $$
  DECLARE
    members_exist       boolean;
    command             text;
    
  BEGIN
    command := 'SELECT ''t''::boolean FROM acl_members WHERE ';
    command := command || 'com_id=' || quote_literal(OLD.com_id) || ' AND ';
    command := command || 'net_id=' || quote_literal(OLD.net_id) || ' AND ';
    command := command || 'via_id=' || quote_literal(OLD.via_id) || ' AND ';
    command := command || 'module_id=' || quote_literal(OLD.module_id) || ' AND ';
    command := command || 'matrix_counter=' || quote_literal(OLD.matrix_counter) || ' AND ';
    command := command || 'item_counter=' || quote_literal(OLD.item_counter) || ' AND ';
    command := command || 'acl_counter=' || quote_literal(OLD.counter) || ' AND ';
    command := command || 'expiration >= ''now'' AND ';
    --command := command || 'active=''t''';
    command := command || 'active NOTNULL';
    
    EXECUTE command INTO members_exist;
    
    IF (members_exist) THEN
      RETURN NULL;
    ELSIF (TG_OP = 'DELETE') THEN
      RETURN OLD;
    END IF;
    
    RETURN NEW;
  END;
$$ LANGUAGE 'plpgsql';
--CREATE TRIGGER "acl_acls_with_members_trigger" BEFORE UPDATE OR DELETE ON "acl_acls" FOR EACH ROW EXECUTE PROCEDURE "acl_with_members_check" ();
CREATE TRIGGER "acl_acls_with_members_trigger" BEFORE DELETE ON "acl_acls" FOR EACH ROW EXECUTE PROCEDURE "acl_with_members_check" ();

DROP FUNCTION IF EXISTS "check_acl" (boolean, bigint, bigint, bigint, bigint, bigint, bigint, bigint, int) CASCADE;
CREATE OR REPLACE FUNCTION "check_acl" (boolean, bigint, bigint, bigint, bigint, bigint, bigint, bigint, int, OUT allowed bool, OUT privilege int, OUT filter text, OUT recursive bool) RETURNS RECORD AS $$
  DECLARE
    in_do_check         ALIAS FOR $1;
    in_profile_id       ALIAS FOR $2;
    in_com_id           ALIAS FOR $3;
    in_net_id           ALIAS FOR $4;
    in_via_id           ALIAS FOR $5;
    in_module_id        ALIAS FOR $6;
    in_matrix_counter   ALIAS FOR $7;
    in_item_counter     ALIAS FOR $8;
    in_min_privilege    ALIAS FOR $9;
    
    command             text;
    com_where           text;
    
    rec                 RECORD;
    
  BEGIN
    allowed             := in_do_check;
    privilege           := null;
    filter              := null;
    recursive           := null;
    
    IF (in_do_check NOTNULL) AND (in_profile_id NOTNULL) THEN
        
        command := 'SELECT max(privilege), filter, recursive, COALESCE(orderby, 0) AS orderby FROM acl_acls acl WHERE (acl.active NOTNULL) AND (acl.expiration ISNULL OR now() <= acl.expiration) ';
        
        IF (in_min_privilege > 0) THEN
            command := command || 'AND (acl.privilege >= ' || quote_literal(in_min_privilege) || ') ';
        END IF;
        
        com_where := '(com_id';
        IF (in_com_id NOTNULL) THEN
            com_where := com_where || '=' || quote_literal(in_com_id);
        ELSE
            com_where := com_where || ' ISNULL';
        END IF;
        com_where := com_where || ' AND ';
    
        com_where := com_where || 'net_id';
        IF (in_net_id NOTNULL) THEN
            com_where := com_where || '=' || quote_literal(in_net_id);
        ELSE
            com_where := com_where || ' ISNULL';
        END IF;
        com_where := com_where || ' AND ';
        
        com_where := com_where || 'via_id';
        IF (in_via_id NOTNULL) THEN
            com_where := com_where || '=' || quote_literal(in_via_id);
        ELSE
            com_where := com_where || ' ISNULL';
        END IF;
        com_where := com_where || ' AND ';
        
        com_where := com_where || 'module_id';
        IF (in_module_id NOTNULL) THEN
            com_where := com_where || '=' || quote_literal(in_module_id);
        ELSE
            com_where := com_where || ' ISNULL';
        END IF;
        com_where := com_where || ' AND ';
        
        com_where := com_where || 'matrix_counter';
        IF (in_matrix_counter NOTNULL) THEN
            com_where := com_where || '=' || quote_literal(in_matrix_counter);
        ELSE
            com_where := com_where || ' ISNULL';
        END IF;
        com_where := com_where || ' AND ';
        
        com_where := com_where || 'item_counter';
        IF (in_item_counter NOTNULL) THEN
            com_where := com_where || '=' || quote_literal(in_item_counter);
        ELSE
            com_where := com_where || ' ISNULL';
        END IF;
        com_where := com_where || ')';
        
        command := command || 'AND ' || com_where || ' AND (acl.w_arb_profiles_ex ISNULL OR NOT acl.w_arb_profiles_ex @> ARRAY[' || in_profile_id || '::bigint])' || ' AND (';
            command := command || '(acl.w_registration_status=''E'') OR ';
            command := command || '(acl.w_registration_status=''R'' AND ' || quote_literal(in_profile_id) || ' > 0) OR ';
            command := command || '(acl.w_contact_profiles @> ARRAY[' || in_profile_id || '::bigint]) OR ';
            command := command || '(acl.w_arb_profiles @> ARRAY[' || in_profile_id || '::bigint]) OR ';
            
            command := command || '(acl.w_registration_status=''B'' AND (SELECT base FROM profile_profiles WHERE id=' || quote_literal(in_profile_id) || ')) OR ';
            
            command := command || '((acl.w_groups NOTNULL) AND (SELECT ''t''::bool WHERE ' || quote_literal(in_profile_id) || ' IN (SELECT member_profile_id FROM contact_group_members WHERE profile_id=acl.profile_id AND group_counter_id IN (SELECT * FROM recursive_find(''contact_group_groups'', ''counter'', ''parent_id'', acl.w_groups, ''t'', ''active'', ''t'', ''profile_id'', acl.profile_id::text))))) OR ';
            
            command := command || '((acl.w_password NOTNULL) AND (SELECT ''t''::bool WHERE ' || quote_literal(in_profile_id) || ' IN (SELECT profile_id FROM acl_passwords WHERE active=''t'' AND password=acl.w_password AND ' || com_where || '))) OR ';
            
            --command := command || '((acl.w_member_amount > 0) AND (SELECT ''t''::bool WHERE ' || quote_literal(in_profile_id) || ' IN (SELECT profile_id FROM acl_members WHERE active=''t'' AND (activation ISNULL OR now() >= activation) AND (expiration ISNULL OR now() <= expiration) <= expiration) AND ' || com_where || '))) OR ';
            command := command || '((acl.w_member_amount > 0) AND (SELECT ''t''::bool WHERE ' || quote_literal(in_profile_id) || ' IN (SELECT profile_id FROM acl_members WHERE active NOTNULL AND (activation ISNULL OR date(now()) >= date(activation)) AND (expiration ISNULL OR date(now()) <= date(expiration)) AND ' || com_where || '))) OR ';
            
            command := command || '((acl.w_dos > 0) AND (SELECT ''t''::bool WHERE ' || quote_literal(in_profile_id) || ' IN (SELECT contact_profile_id FROM find_contacts(acl.profile_id, acl.w_dos))))';
        command := command || ') GROUP BY filter, recursive, orderby ORDER BY max(privilege) DESC, orderby DESC';
        
        --RAISE NOTICE '%', command;
        
        EXECUTE command INTO privilege, filter, recursive;
        
    END IF;
    
    -- Set Default
    IF (in_do_check) AND (privilege ISNULL) THEN
        allowed := null;
    ELSEIF (privilege NOTNULL) THEN
        allowed := 't'::bool;
    END IF;
    
    RETURN;
  END;
$$ LANGUAGE 'plpgsql' STABLE;


----------------------------------------------------------------------------------------------------


--INSERT INTO acl_acls(com_id, net_id, via_id, module_id, matrix_counter, item_counter, profile_id, w_registration_status, w_groups, w_contact_profiles, w_dos, w_password, w_member_amount, w_member_interval, privilege, orderby, expiration) VALUES ();

/* TESTS

SELECT 'No ACL - Default False','f'::bool, * FROM check_acl('f', 5, null, null, 2, null, null, null, null);
SELECT 'No ACL - Default NULL',null, * FROM check_acl('t', 5, null, null, 2, null, null, null, null);

INSERT INTO acl_acls(via_id, profile_id, w_registration_status, privilege) VALUES (2, 2, 'E', 10); -- Everyone can read the first article
SELECT 'Everyone has 10','t'::bool, * FROM check_acl('f', 5, null, null, 2, null, null, null, null);

DELETE FROM acl_acls;

INSERT INTO acl_acls(via_id, profile_id, w_registration_status, privilege) VALUES (2, 2, 'R', 10); -- Everyone can read the first article
SELECT 'Registered has 10','t'::bool, * FROM check_acl('f', 5, null, null, 2, null, null, null, null);

UPDATE acl_acls SET w_registration_status='B';
SELECT 'Base has 10','t'::bool, * FROM check_acl('f', 5, null, null, 2, null, null, null, null);
SELECT 'Base has 10','f'::bool, * FROM check_acl('f', 4, null, null, 2, null, null, null, null);

DELETE FROM acl_acls;
INSERT INTO acl_acls(via_id, profile_id, w_contact_profiles, privilege) VALUES (2, 2, ARRAY[3,4], 10); -- Everyone can read the first article
SELECT 'In Profiles has 10','t'::bool, * FROM check_acl('f', 3, null, null, 2, null, null, null, null);
SELECT 'In Profiles has 10','t'::bool, * FROM check_acl('f', 4, null, null, 2, null, null, null, null);
SELECT 'In Profiles has 10','f'::bool, * FROM check_acl('f', 5, null, null, 2, null, null, null, null);

DELETE FROM acl_acls;
INSERT INTO acl_acls(via_id, profile_id, w_dos, privilege) VALUES (2, 2, 1, 10); -- Everyone can read the first article
SELECT '1 DOS has 10','t'::bool, * FROM check_acl('f', 3, null, null, 2, null, null, null, null);
SELECT '1 DOS has 10','t'::bool, * FROM check_acl('f', 4, null, null, 2, null, null, null, null);
SELECT '1 DOS has 10','f'::bool, * FROM check_acl('f', 5, null, null, 2, null, null, null, null);

DELETE FROM acl_acls;
INSERT INTO acl_acls(via_id, profile_id, w_dos, privilege) VALUES (2, 2, 2, 10); -- Everyone can read the first article
SELECT '2 DOS has 10','t'::bool, * FROM check_acl('f', 3, null, null, 2, null, null, null, null);
SELECT '2 DOS has 10','t'::bool, * FROM check_acl('f', 4, null, null, 2, null, null, null, null);
SELECT '2 DOS has 10','t'::bool, * FROM check_acl('f', 5, null, null, 2, null, null, null, null);

DELETE FROM acl_acls;
INSERT INTO acl_acls(via_id, profile_id, w_groups, privilege) VALUES (2, 2, ARRAY[1], 10); -- Everyone can read the first article
SELECT 'Group 1 has 10','t'::bool, * FROM check_acl('f', 3, null, null, 2, null, null, null, null);
SELECT 'Group 1 has 10','t'::bool, * FROM check_acl('f', 4, null, null, 2, null, null, null, null);

DELETE FROM acl_acls;
INSERT INTO acl_acls(via_id, profile_id, w_groups, privilege) VALUES (2, 2, ARRAY[2], 10); -- Everyone can read the first article
SELECT 'Group 2 has 10','t'::bool, * FROM check_acl('f', 3, null, null, 2, null, null, null, null);
SELECT 'Group 2 has 10','f'::bool, * FROM check_acl('f', 4, null, null, 2, null, null, null, null);

DELETE FROM acl_acls;
INSERT INTO acl_acls(via_id, profile_id, w_password, privilege) VALUES (2, 2, 'password', 10); -- Everyone can read the first article
SELECT 'Password','f'::bool, * FROM check_acl('f', 3, null, null, 2, null, null, null, null);
INSERT INTO acl_passwords(via_id, profile_id, password) VALUES (2, 3, 'wrongpassword');
SELECT 'Password','f'::bool, * FROM check_acl('f', 3, null, null, 2, null, null, null, null);
UPDATE acl_passwords SET password='password';
SELECT 'Password','t'::bool, * FROM check_acl('f', 3, null, null, 2, null, null, null, null);

DELETE FROM acl_acls;
DELETE FROM acl_passwords;
INSERT INTO acl_acls(via_id, profile_id, w_member_amount, w_member_quantity, w_member_interval, privilege) VALUES (2, 2, 9.99, 1, 'month', 10); -- Everyone can read the first article
SELECT 'Member','f'::bool, * FROM check_acl('f', 3, null, null, 2, null, null, null, null);
INSERT INTO acl_members(via_id, profile_id, expiration) VALUES (2, 3, now() + '1 day');
SELECT 'Member','t'::bool, * FROM check_acl('f', 3, null, null, 2, null, null, null, null);

*/