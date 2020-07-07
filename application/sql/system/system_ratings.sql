DROP TABLE IF EXISTS system_ratings CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE system_ratings (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    com_id              bigint NOT NULL DEFAULT 0
    						REFERENCES system_communities (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,  -- If community id gets deleted set to DEFAULT; if updated, cascade
    net_id              bigint NOT NULL DEFAULT 0
    						REFERENCES network_networks (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,  -- If community id gets deleted set to DEFAULT; if updated, cascade
    via_id              bigint NOT NULL DEFAULT 0
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
         
    module_id           bigint NOT NULL DEFAULT 0
    						REFERENCES module_modules (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    matrix_counter      bigint NOT NULL DEFAULT 1 CHECK (matrix_counter >= 1),
    
    item_counter        bigint NOT NULL DEFAULT 1 CHECK (item_counter >= 1),
    
    -- For secondary functions (system_comments); these two reference secondary table
    table_name          varchar(96) NOT NULL DEFAULT '',
    counter             bigint NOT NULL DEFAULT 0 CHECK (counter >= 0),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE SET DEFAULT
    						ON UPDATE CASCADE, -- If profile id gets deleted set to DEFAULT; if updated, cascade
    						
    rating              smallint NOT NULL,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't'
    
    CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0))
    --FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX system_ratings_x ON system_ratings (com_id, net_id, via_id, module_id, matrix_counter, item_counter, profile_id, table_name, counter);
--CREATE INDEX system_ratings_com_matrix_counter_profile_active_x ON system_ratings (com_id, matrix_counter, profile_id, active);
--CREATE INDEX system_ratings_net_matrix_counter_profile_active_x ON system_ratings (net_id, matrix_counter, profile_id, active);
--CREATE INDEX system_ratings_via_matrix_counter_profile_active_x ON system_ratings (via_id, matrix_counter, profile_id, active);

ALTER TABLE public.system_ratings OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "system_ratings_updated_trigger" BEFORE UPDATE ON "system_ratings" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
--CREATE TRIGGER "system_ratings_counter_trigger" BEFORE INSERT ON "system_ratings" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'module_id', 'matrix_counter', 'item_counter');

-- Dynamic Function to Update a Rating or Other Average
-- Usage : update_rating (parent_table, total_ratings_count_field, rating_field, rating_column, where_field_1, value_field_1, where_field_2, value_field_2, ...)
DROP FUNCTION IF EXISTS "update_rating" () CASCADE; -- ALSO DROPS ALL TRIGGERS TO THIS FUNCTION
CREATE OR REPLACE FUNCTION "update_rating" () RETURNS trigger AS $$
    if ($_TD->{argc} > 0) {
        my($table_name);
        my($module_id) = $_TD->{old}{'module_id'};
        if ($_TD->{new}{'module_id'}) {
            $module_id = $_TD->{new}{'module_id'};
        }
        
        if ($_TD->{args}[0] eq 'DYNAMIC' && (($_TD->{new}{'table_name'} && defined($_TD->{new}{'counter'})) || ($_TD->{old}{'table_name'} && defined($_TD->{old}{'counter'})))) {
            $table_name = ($_TD->{event} eq 'DELETE' ? $_TD->{old}{'table_name'} : $_TD->{new}{'table_name'});
        }
        elsif ($_TD->{args}[0] eq 'DYNAMIC' && $module_id) {
            my($rv) = spi_exec_query("SELECT name AS module FROM module_modules WHERE id=$module_id", 1);
            my($module) = $rv->{rows}[0]->{'module'};
            $table_name = "$module\_$module\s";
        }
        else {
            $table_name = $_TD->{args}[0];
        }
        
        if ($table_name) {
            my($update);
            if ($_TD->{event} eq 'UPDATE') {
                if ($_TD->{new}{'active'} eq 't' && $_TD->{old}{'active'} ne 't') {
                    $update = "$_TD->{args}[2]=(((COALESCE($_TD->{args}[2], 0)*$_TD->{args}[1]) + $_TD->{new}{$_TD->{args}[3]}) / ($_TD->{args}[1] + 1)), $_TD->{args}[1]=$_TD->{args}[1]+1";
                }
                elsif ($_TD->{new}{'active'} ne 't' && $_TD->{old}{'active'} eq 't') {
                    $update = "$_TD->{args}[2]=(((COALESCE($_TD->{args}[2], 0)*$_TD->{args}[1]) - $_TD->{new}{$_TD->{args}[3]}) / NULLIF(($_TD->{args}[1] - 1), 0)), $_TD->{args}[1]=$_TD->{args}[1]-1";
                }
                else {
                    $update = "$_TD->{args}[2]=(((COALESCE($_TD->{args}[2], 0)*$_TD->{args}[1]) - $_TD->{old}{$_TD->{args}[3]} + $_TD->{new}{$_TD->{args}[3]}) / $_TD->{args}[1])";
                }
            }
            elsif ($_TD->{event} eq 'INSERT' && $_TD->{new}{'active'} eq 't') {
                $update = "$_TD->{args}[2]=(((COALESCE($_TD->{args}[2], 0)*$_TD->{args}[1]) + $_TD->{new}{$_TD->{args}[3]}) / ($_TD->{args}[1] + 1)), $_TD->{args}[1]=$_TD->{args}[1]+1";
            }
            elsif ($_TD->{event} eq 'DELETE' && $_TD->{old}{'active'} eq 't')  {
                $update = "$_TD->{args}[2]=(((COALESCE($_TD->{args}[2], 0)*$_TD->{args}[1]) - $_TD->{old}{$_TD->{args}[3]}) / NULLIF(($_TD->{args}[1] - 1), 0)), $_TD->{args}[1]=$_TD->{args}[1]-1";
            }
            
            if ($update) {
                my(%wheres_h);
                if ($_TD->{argc} > 4) {
                    for (my($i) = 4; $i < $_TD->{argc}; $i+=2) {
                        
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
                
                # Self Override
                if ($_TD->{args}[0] eq 'DYNAMIC' && (($_TD->{new}{'table_name'} && defined($_TD->{new}{'counter'})) || ($_TD->{old}{'table_name'} && defined($_TD->{old}{'counter'})))) {
                    $wheres_h{'item_counter'} = '=' . ($_TD->{event} eq 'DELETE' ? $_TD->{old}{'item_counter'} : $_TD->{new}{'item_counter'});
                    $wheres_h{'counter'} = '=' . ($_TD->{event} eq 'DELETE' ? $_TD->{old}{'counter'} : $_TD->{new}{'counter'});
                }
                
                my(@wheres_a);
                foreach (keys(%wheres_h)) {
                    push(@wheres_a, ($_ . $wheres_h{$_}));
                }
                
                if (@wheres_a) {
                    my($where);
                    $where = join(' AND ', @wheres_a);
                    if ($where) {
                        $command = "UPDATE $table_name SET $update WHERE ($where)";
                        #elog(NOTICE, $_TD->{event});
                        #elog(NOTICE, $command);
                        $rv = spi_exec_query($command, 1);
                    }
                }
            }
        }
    }
    return;
$$ LANGUAGE 'plperl';

--Update the total_item_count in module_matrix
CREATE TRIGGER "system_ratings_total_rating_trigger" AFTER INSERT OR UPDATE OR DELETE ON "system_ratings" FOR EACH ROW EXECUTE PROCEDURE "update_rating" ('DYNAMIC', 'total_ratings_count', 'rating', 'rating', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'matrix_counter', 'matrix_counter', 'counter', 'item_counter');


----------------------------------------------------------------------------------------------------


--insert into system_ratings (via_id, module_id, matrix_counter, item_counter, profile_id, rating) values (2, 14, 2, 2, 2, 5);