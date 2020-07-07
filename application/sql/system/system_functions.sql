-- Function to make updated current on updates
DROP FUNCTION IF EXISTS "current_updated" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "current_updated" () RETURNS trigger AS $$
  BEGIN
    NEW.updated := now();
    RETURN NEW;
  END;
$$ LANGUAGE 'plpgsql';


-- Function to strip tags and tokenize fields
DROP FUNCTION IF EXISTS "tokenize" (text) CASCADE;
CREATE OR REPLACE FUNCTION "tokenize" (text) RETURNS text AS $$
    if ($_[0]) {
        $_[0] =~ s{<(?:(!--)|(\?)|(?i:( TITLE|SCRIPT|APPLET|OBJECT|STYLE))|([!/A-Za-z]))(?(4)(?:(?![\s=]["`'])[^>]|[\s=]`[^`]*`|[\s=]'[^']*'|[\s=]"[^"]*")*|.*?)(?(1)(?<=--))(?(2)(?<=\?))(?(3)</(?i:\3)(?:\s[^>]*)?)>}{ }gs;
        $_[0] =~ s{&[[:ascii:]]+;}{}gs; # Remove HTML entities
        $_[0] =~ s{[^[:alnum:]']+}{ }gs; # Remove Non AlphaNumberic characters
        $_[0] =~ s{\s[[:digit:]]+\s}{ }gs; # Remove digit groups
        while ($_[0] =~ /\s[[:alnum:]']{0,2}\s/) { $_[0] =~ s/\s[[:alnum:]']{0,2}\s/ /gs; } # Remove words less that 3 characters
        $_[0] =~ s{\s+}{ }g; # Remove multiple whitespace
        $_[0] =~ s{^\s+(.*)\s+$}{$1}g; # Remove beginning or ending whitespace
        #$_[0] =~ s{'}{''}g; # Double quote
    }
    
    return $_[0];
$$ LANGUAGE 'plperl';


--Dynamic Function to Update Search Field
--Usage : update_search('searchfield', 'field1', 'weight1A', 'field2', 'weight2B', 'field3', 'weight3D', etc...)
--  Weights are optional; value is A, B, C, or D - Otherwise, it is considered a field
DROP FUNCTION IF EXISTS "update_search" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "update_search" () RETURNS trigger AS $$
    if ($_TD->{argc} > 1 && ($_TD->{new}{$_TD->{args}[0]} eq $_TD->{old}{$_TD->{args}[0]})) { # Allow Manual Override
        my(@fields);
        my($updated) = 0;
        for (my($i) = 1; $i < $_TD->{argc}; $i++) {
            my($data) = $_TD->{new}{$_TD->{args}[$i]};
            # Fix Data
            $data =~ s{<(?:(!--)|(\?)|(?i:( TITLE|SCRIPT|APPLET|OBJECT|STYLE))|([!/A-Za-z]))(?(4)(?:(?![\s=]["`'])[^>]|[\s=]`[^`]*`|[\s=]'[^']*'|[\s=]"[^"]*")*|.*?)(?(1)(?<=--))(?(2)(?<=\?))(?(3)</(?i:\3)(?:\s[^>]*)?)>}{ }gs;
            $data =~ s{&[[:ascii:]]+;}{}gs; # Remove HTML entities
            $data =~ s{[^[:alnum:]']+}{ }gs; # Remove Non AlphaNumberic characters
            $data =~ s{\s[[:digit:]]+\s}{ }gs; # Remove digit groups
            while ($data =~ /\s[[:alnum:]']{0,2}\s/) { $data =~ s/\s[[:alnum:]']{0,2}\s/ /gs; } # Remove words less that 3 characters
            $data =~ s{\s+}{ }g; # Remove multiple whitespace
            $data =~ s{^\s+(.*)\s+$}{$1}g; # Remove beginning or ending whitespace
            $data =~ s{'}{''}g; # Double quote
            
            if ($_TD->{old}{$_TD->{args}[$i]} ne $_TD->{new}{$_TD->{args}[$i]}) {
                $updated = 1;
            }
            
            my($partial);
            $partial = "to_tsvector('$data')";
            
            if ($_TD->{args}[$i+1] =~ /^[A-D]$/) {
                $partial = "setweight($partial, '$_TD->{args}[$i+1]')";
                $i++;
            }
            push (@fields, $partial);
        }
        
        my($command) = 'SELECT ' . (join(' || ', @fields)) . " AS $_TD->{args}[0]";
        
        if ($updated) {
            $rv = spi_exec_query($command, 1);
            if ($rv->{rows}[0]->{$_TD->{args}[0]}) {
                $_TD->{new}{$_TD->{args}[0]} = ($rv->{rows}[0]->{$_TD->{args}[0]});
                return 'MODIFY';
            }
        }
    }
    return;
$$ LANGUAGE 'plperl';


-- Table Key to Val Translation Function
--Usage : table_key_to_val(val_column, table, key_column, key_column_value)
DROP FUNCTION IF EXISTS "table_key_to_val" (text, text, text, text) CASCADE;
CREATE OR REPLACE FUNCTION "table_key_to_val" (text, text, text, text) RETURNS text AS $$
  DECLARE
    ival        ALIAS FOR $1;
    itable      ALIAS FOR $2;
    ikey        ALIAS FOR $3;
    ikeyval     ALIAS FOR $4;
    
    command     text;
    val         text;
  BEGIN
    IF (itable NOTNULL AND ikey NOTNULL) THEN
        command := 'SELECT ' || quote_ident(ival) || ' FROM ' || quote_ident(itable) || ' WHERE ' || quote_ident(ikey) || '=' || quote_literal(ikeyval) || ' LIMIT 1';
        
        --RAISE NOTICE '%', command;
        
        EXECUTE command INTO val;
        
        RETURN val;
    END IF;
    
    RETURN NULL;
  END;
$$ LANGUAGE 'plpgsql' STABLE;


-- Dynamic Function to Increment a Total Counter
-- Usage : update_total_count (parent_table, total_count_field, has_status_column, where_field_1, value_field_1, where_field_2, value_field_2, ...)
DROP FUNCTION IF EXISTS "update_total_count" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "update_total_count" () RETURNS trigger AS $$
    if ($_TD->{argc} > 0) {
        my($has_status_column) = 0;
        if ($_TD->{args}[2] eq 'true' || $_TD->{args}[2] eq 't') { $has_status_column = 1; }
        
        my($table_name);
        my($module_id) = $_TD->{old}{'module_id'};
        if ($_TD->{new}{'module_id'}) {
            $module_id = $_TD->{new}{'module_id'};
        }
        
        if ($_TD->{args}[0] eq 'DYNAMIC' && $module_id) {
            $rv = spi_exec_query("SELECT name AS module FROM module_modules WHERE id=$module_id", 1);
            $module = $rv->{rows}[0]->{'module'};
            $table_name = "$module\_$module\s";
        }
        else {
            $table_name = $_TD->{args}[0];
        }
        
        if ($table_name) {
            my($increment);
            if ($_TD->{event} eq 'UPDATE' && (($_TD->{new}{'active'} ne $_TD->{old}{'active'}) || ($has_status_column && $_TD->{new}{'status'} ne $_TD->{old}{'status'}))) {
                if (($_TD->{new}{'active'} eq 't' && $_TD->{old}{'active'} ne 't' && (!$has_status_column || $_TD->{new}{'status'} eq 't')) ||
                    ($has_status_column && $_TD->{new}{'status'} eq 't' && $_TD->{old}{'status'} ne 't' && $_TD->{new}{'active'} eq 't')) {
                    $increment = "+ 1";
                }
                elsif (($_TD->{new}{'active'} ne 't' && $_TD->{old}{'active'} eq 't' && (!$has_status_column || $_TD->{old}{'status'} eq 't')) ||
                    ($has_status_column && $_TD->{new}{'status'} ne 't' && $_TD->{old}{'status'} eq 't' && $_TD->{old}{'active'} eq 't')) {
                    $increment = "- 1";
                }
            }
            elsif ($_TD->{event} eq 'INSERT' && $_TD->{new}{'active'} eq 't' && (!$has_status_column || $_TD->{new}{'status'} eq 't')) {
                $increment = "+ 1";
            }
            elsif ($_TD->{event} eq 'DELETE' && $_TD->{old}{'active'} eq 't' && (!$has_status_column || $_TD->{old}{'status'} eq 't'))  {
                $increment = "- 1";
            }
            
            if ($increment) {
                my(%wheres_h);
                my($command_executed) = 0;
                if ($_TD->{argc} > 3) {
                    for (my($i) = 3; $i < $_TD->{argc}; $i+=2) {
                        if ($_TD->{args}[$i] eq 'SELF' || $_TD->{args}[$i] eq 'ANDSELF') {
                            if ($_TD->{args}[$i] eq 'ANDSELF') {
                                # Flush an Update to the Parent Before Changing the Table Name
                                my(@wheres_a);
                                foreach (keys(%wheres_h)) {
                                    push(@wheres_a, ($_ . $wheres_h{$_}));
                                }
                                
                                if (@wheres_a) {
                                    my($where) = join(' AND ', @wheres_a);
                                    $command = "UPDATE $table_name SET $_TD->{args}[1]=$_TD->{args}[1] $increment WHERE ($where)";
                                    #elog(NOTICE, $command);
                                    $rv = spi_exec_query($command, 1);
                                    $command_executed = 1;
                                }
                            }
                            
                            $i++;
                            
                            if ($_TD->{event} eq 'DELETE' ? $_TD->{old}{'parent_id'} : $_TD->{new}{'parent_id'}) {
                                $table_name = $_TD->{table_name};
                                $command_executed = 0;
                            }
                            else {
                                last;
                            }
                        }
                        
                        my($val) = $_TD->{new}{$_TD->{args}[$i+1]};
                        if ($_TD->{event} eq 'DELETE') {
                            $val = $_TD->{old}{$_TD->{args}[$i+1]};
                        }
                        
                        if (length($val)) {
                            $wheres_h{$_TD->{args}[$i]} = "=$val";
                        }
                        else {
                            $wheres_h{$_TD->{args}[$i]} = " ISNULL";
                        }
                    }
                }
                
                my(@wheres_a);
                foreach (keys(%wheres_h)) {
                    push(@wheres_a, ($_ . $wheres_h{$_}));
                }
                
                if (@wheres_a && !$command_executed) {
                    my($where) = join(' AND ', @wheres_a);
                    $command = "UPDATE $table_name SET $_TD->{args}[1]=$_TD->{args}[1] $increment WHERE ($where)";
                    #elog(NOTICE, $command);
                    $rv = spi_exec_query($command, 1);
                }
            }
        }
    }
    return;
$$ LANGUAGE 'plperl';


-- Dynamic Function to Increment a Counter
-- Matched values are not quoted so only use number types
DROP FUNCTION IF EXISTS "dynamic_incrementer" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "dynamic_incrementer" () RETURNS trigger AS $$
    if ($_TD->{argc} > 0) {
        my($command) = "SELECT max($_TD->{args}[0]) AS $_TD->{args}[0] FROM $_TD->{table_name}";
        
        if ($_TD->{argc} > 1) {
            $command .= " WHERE (";
            for (my($i) = 1; $i < $_TD->{argc}; $i++) {
                if ($i > 1) {
                    $command .= ' AND ';
                }
                $command .= "$_TD->{args}[$i]";
                if (length($_TD->{new}{$_TD->{args}[$i]})) {
                    $command .= "=$_TD->{new}{$_TD->{args}[$i]}";
                }
                else {
                    $command .= " ISNULL";
                }
            }
            $command .= ')';
        }
        #elog(NOTICE, $command);
        $rv = spi_exec_query($command, 1);
        if ($rv->{rows}[0]->{$_TD->{args}[0]} > 0) {
            $_TD->{new}{$_TD->{args}[0]} = ($rv->{rows}[0]->{$_TD->{args}[0]} + 1);
            return 'MODIFY';
        }
    }
    return;
$$ LANGUAGE 'plperl';


-- Dynamic Function to Not Allow Updates on Certain Fields
-- Usage : disallow_updates(silentlyreplace, field1, field2, etc...)
DROP FUNCTION IF EXISTS "disallow_updates" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "disallow_updates" () RETURNS trigger AS $$
    if ($_TD->{argc} > 1) {
        my($modified) = 0;
        for (my($i) = 1; $i < $_TD->{argc}; $i++) {
            if ($_TD->{new}{$_TD->{args}[$i]} ne $_TD->{old}{$_TD->{args}[$i]}) {
                if ($_TD->{args}[0]) {
                    $_TD->{new}{$_TD->{args}[$i]} = $_TD->{old}{$_TD->{args}[$i]};
                    $modified = 1;
                }
                else {
                    return 'SKIP';
                }
            }
        }
        
        if ($modified) {
            return 'MODIFY';
        }
        else {
            return;
        }
    }
    return 'SKIP';
$$ LANGUAGE 'plperl';

-- Dynamic Function to Return a Recursive Set
-- Usage : recursive_find(table, recursive_fieldname, indexfield, starting_index, include_starting_index, optionalfixedfield1, optionalfixedvalue1, optionalfixedfield2, optionalfixedvalue2)
DROP FUNCTION IF EXISTS "recursive_find" (text, text, text, bigint, boolean, text, text, text, text, text, text) CASCADE;
CREATE OR REPLACE FUNCTION "recursive_find" (text, text, text, bigint, boolean, text, text, text, text, text, text) RETURNS SETOF bigint AS $$
  DECLARE
    rtable      ALIAS FOR $1;
    rfield      ALIAS FOR $2;
    rid         ALIAS FOR $3;
    rstart      ALIAS FOR $4;
    rinclusive  ALIAS FOR $5;
    
    optfield1   ALIAS FOR $6;
    optval1     ALIAS FOR $7;
    optfield2   ALIAS FOR $8;
    optval2     ALIAS FOR $9;
    optfield3   ALIAS FOR $10;
    optval3     ALIAS FOR $11;
    
    command     text;
    rnext       bigint;
  BEGIN
    IF (rinclusive) THEN
        RETURN NEXT rstart;
    END IF;
    

    command := 'SELECT ' || quote_ident(rfield) || ' FROM ' || quote_ident(rtable) || ' WHERE ' || quote_ident(rid) || '=' || rstart;
    IF (optfield1 NOTNULL) THEN
        command := command || ' AND ' || quote_ident(optfield1);
        IF (optval1 NOTNULL) THEN
            command := command || '=' || quote_literal(optval1);
        ELSE
            command := command || ' ISNULL';
        END IF;
    END IF;
    IF (optfield2 NOTNULL) THEN
        command := command || ' AND ' || quote_ident(optfield2);
        IF (optval2 NOTNULL) THEN
            command := command || '=' || quote_literal(optval2);
        ELSE
            command := command || ' ISNULL';
        END IF;
    END IF;
    IF (optfield3 NOTNULL) THEN
        command := command || ' AND ' || quote_ident(optfield3);
        IF (optval3 NOTNULL) THEN
            command := command || '=' || quote_literal(optval3);
        ELSE
            command := command || ' ISNULL';
        END IF;
    END IF;
    
    --RAISE NOTICE '%', command;
    
    FOR rnext IN EXECUTE command LOOP
        IF (rnext NOTNULL) THEN
            RETURN QUERY SELECT * FROM recursive_find(rtable, rfield, rid, rnext, 't', optfield1, optval1, optfield2, optval2, optfield3, optval3);
        END IF;
    END LOOP;

    RETURN;
  END;
$$ LANGUAGE 'plpgsql' STABLE;

DROP FUNCTION IF EXISTS "recursive_find" (text, text, text, bigint, boolean, text, text, text, text) CASCADE;
CREATE OR REPLACE FUNCTION "recursive_find" (text, text, text, bigint, boolean, text, text, text, text) RETURNS SETOF bigint AS $$
  BEGIN
    RETURN QUERY SELECT * FROM recursive_find($1, $2, $3, $4, $5, $6, $7, $8, $9, null, null);
    RETURN;
  END;
$$ LANGUAGE 'plpgsql' STABLE;

DROP FUNCTION IF EXISTS "recursive_find" (text, text, text, bigint, boolean, text, text) CASCADE;
CREATE OR REPLACE FUNCTION "recursive_find" (text, text, text, bigint, boolean, text, text) RETURNS SETOF bigint AS $$
  BEGIN
    RETURN QUERY SELECT * FROM recursive_find($1, $2, $3, $4, $5, $6, $7, null, null, null, null);
    RETURN;
  END;
$$ LANGUAGE 'plpgsql' STABLE;

DROP FUNCTION IF EXISTS "recursive_find" (text, text, text, bigint, boolean) CASCADE;
CREATE OR REPLACE FUNCTION "recursive_find" (text, text, text, bigint, boolean) RETURNS SETOF bigint AS $$
  BEGIN
    RETURN QUERY SELECT * FROM recursive_find($1, $2, $3, $4, $5, null, null, null, null, null, null);
    RETURN;
  END;
$$ LANGUAGE 'plpgsql' STABLE;

-- Array Based Recursive Find
DROP FUNCTION IF EXISTS "recursive_find" (text, text, text, bigint[], boolean, text, text, text, text, text, text) CASCADE;
CREATE OR REPLACE FUNCTION "recursive_find" (text, text, text, bigint[], boolean, text, text, text, text, text, text) RETURNS SETOF bigint AS $$
  DECLARE
  r             record;
  
  BEGIN
    FOR r IN SELECT * FROM list_array($4) LOOP
      RETURN QUERY SELECT * FROM recursive_find($1, $2, $3, r.list_array, $5, $6, $7, $8, $9, $10, $11);
    END LOOP;
    RETURN;
  END;
$$ LANGUAGE 'plpgsql' STABLE;

DROP FUNCTION IF EXISTS "recursive_find" (text, text, text, bigint[], boolean, text, text, text, text) CASCADE;
CREATE OR REPLACE FUNCTION "recursive_find" (text, text, text, bigint[], boolean, text, text, text, text) RETURNS SETOF bigint AS $$
  BEGIN
    RETURN QUERY SELECT * FROM recursive_find($1, $2, $3, $4, $5, $6, $7, $8, $9, null, null);
    RETURN;
  END;
$$ LANGUAGE 'plpgsql' STABLE;

DROP FUNCTION IF EXISTS "recursive_find" (text, text, text, bigint[], boolean, text, text) CASCADE;
CREATE OR REPLACE FUNCTION "recursive_find" (text, text, text, bigint[], boolean, text, text) RETURNS SETOF bigint AS $$
  BEGIN
    RETURN QUERY SELECT * FROM recursive_find($1, $2, $3, $4, $5, $6, $7, null, null, null, null);
    RETURN;
  END;
$$ LANGUAGE 'plpgsql' STABLE;

DROP FUNCTION IF EXISTS "recursive_find" (text, text, text, bigint[], boolean) CASCADE;
CREATE OR REPLACE FUNCTION "recursive_find" (text, text, text, bigint[], boolean) RETURNS SETOF bigint AS $$
  BEGIN
    RETURN QUERY SELECT * FROM recursive_find($1, $2, $3, $4, $5, null, null, null, null, null, null);
    RETURN;
  END;
$$ LANGUAGE 'plpgsql' STABLE;


DROP FUNCTION IF EXISTS idx(ANYARRAY, ANYELEMENT);
CREATE OR REPLACE FUNCTION idx(ANYARRAY, ANYELEMENT) RETURNS int AS $$
    SELECT i FROM (
        SELECT GENERATE_SERIES(array_lower($1,1), array_upper($1,1))
    ) g(i)
    WHERE $1[i] = $2
    LIMIT 1;
$$ LANGUAGE SQL IMMUTABLE;

DROP FUNCTION IF EXISTS array_distinct(ANYARRAY);
CREATE OR REPLACE FUNCTION array_distinct(ANYARRAY) RETURNS ANYARRAY AS $$
	SELECT ARRAY(
		SELECT DISTINCT $1[s]
		    FROM
		    GENERATE_SERIES(array_lower($1,1), array_upper($1,1)) AS s
	);
$$ LANGUAGE SQL IMMUTABLE;

DROP FUNCTION IF EXISTS array_intersect(ANYARRAY, ANYARRAY);
CREATE OR REPLACE FUNCTION array_intersect(ANYARRAY, ANYARRAY) RETURNS ANYARRAY AS $$
	SELECT ARRAY(
		SELECT DISTINCT $1[s]
		    FROM
		    GENERATE_SERIES(array_lower($1,1), array_upper($1,1)) AS s
		    
		INTERSECT
		
		SELECT DISTINCT $2[t]
		    FROM
		    GENERATE_SERIES(array_lower($2,1), array_upper($2,1)) AS t
	);
$$ LANGUAGE SQL IMMUTABLE;

DROP FUNCTION IF EXISTS array_except(ANYARRAY, ANYARRAY);
CREATE OR REPLACE FUNCTION array_except(ANYARRAY, ANYARRAY) RETURNS ANYARRAY AS $$
	SELECT ARRAY(
		SELECT DISTINCT $1[s]
		    FROM
		    GENERATE_SERIES(array_lower($1,1), array_upper($1,1)) AS s
		
		EXCEPT
		
		SELECT DISTINCT $2[t]
		    FROM
		    GENERATE_SERIES(array_lower($2,1), array_upper($2,1)) AS t
	);
$$ LANGUAGE SQL IMMUTABLE;

DROP FUNCTION IF EXISTS array_reverse(ANYARRAY);
CREATE OR REPLACE FUNCTION array_reverse(ANYARRAY) RETURNS ANYARRAY AS $$
	SELECT ARRAY(
		SELECT list_array_reverse($1)
	);
$$ LANGUAGE SQL IMMUTABLE;

DROP FUNCTION IF EXISTS list_array(ANYARRAY);
CREATE OR REPLACE FUNCTION list_array(ANYARRAY) RETURNS SETOF ANYELEMENT AS $$
    SELECT $1[s]
		FROM
		GENERATE_SERIES(array_lower($1,1), array_upper($1,1)) AS s
$$ LANGUAGE SQL IMMUTABLE;

DROP FUNCTION IF EXISTS list_array_reverse(ANYARRAY);
CREATE OR REPLACE FUNCTION list_array_reverse(ANYARRAY) RETURNS SETOF ANYELEMENT AS $$
    SELECT $1[s]
		FROM
		GENERATE_SERIES(array_upper($1,1), array_lower($1,1), -1) AS s
$$ LANGUAGE SQL IMMUTABLE;

DROP AGGREGATE IF EXISTS array_accum(ANYARRAY);
CREATE AGGREGATE array_accum(
    BASETYPE = ANYARRAY,
    SFUNC = array_cat,
    STYPE = ANYARRAY
);

DROP AGGREGATE IF EXISTS array_accum(ANYELEMENT);
CREATE AGGREGATE array_accum (ANYELEMENT)
(
    INITCOND = '{}',
    SFUNC = array_append,
    STYPE = ANYARRAY
);

DROP FUNCTION IF EXISTS "find_contacts" (bigint, int, int, bigint[], bigint[]) CASCADE;
CREATE OR REPLACE FUNCTION "find_contacts" (bigint, int, int, bigint[], bigint[], OUT contact_profile_id bigint, OUT dos bigint, OUT reltree bigint[]) RETURNS SETOF RECORD AS $$
  DECLARE
    in_profile_id       ALIAS FOR $1;
    in_maxdos           ALIAS FOR $2;
    in_dos              ALIAS FOR $3;
    in_processed        ALIAS FOR $4;
    in_reltree          ALIAS FOR $5;
    
    processed           bigint[] := in_processed || in_profile_id;
    
    rec                 RECORD;
    
  BEGIN
    IF in_maxdos > 0 THEN
        dos := in_dos;
        reltree := '{}';
        
        IF in_dos > 1 THEN
            reltree := in_reltree || in_profile_id;
        END IF;
        
        FOR rec IN SELECT * FROM contact_contacts c WHERE c.status='t' AND c.active='t' AND c.profile_id=in_profile_id AND NOT (ARRAY[c.contact_profile_id] && processed) LOOP
            contact_profile_id := rec.contact_profile_id;
            RETURN NEXT;

            processed := processed || contact_profile_id;
            
            IF in_maxdos > 1 THEN
                RETURN QUERY SELECT * FROM find_contacts(contact_profile_id, (in_maxdos - 1), (in_dos + 1), processed, reltree);
            END IF;
        END LOOP;
    END IF;
    
    RETURN;
  END;
$$ LANGUAGE 'plpgsql' STABLE;

DROP FUNCTION IF EXISTS "find_contacts" (bigint, int) CASCADE;
CREATE OR REPLACE FUNCTION "find_contacts" (bigint, int, OUT contact_profile_id bigint, OUT dos bigint, OUT reltree bigint[]) RETURNS SETOF RECORD AS $$
  SELECT * FROM find_contacts($1, $2, 1, '{}', '{}');
$$ LANGUAGE 'SQL' STABLE;

--SELECT DISTINCT ON (contact_profile_id) * FROM (SELECT * FROM find_contacts(2,4) ORDER BY dos) AS t;