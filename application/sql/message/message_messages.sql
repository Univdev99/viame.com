DROP TABLE IF EXISTS message_messages CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE message_messages (
    content             text NOT NULL,
    
    total_replies_count     bigint DEFAULT 0 CHECK (total_replies_count >= 0),
    
    parent_counter      bigint CHECK (parent_counter ISNULL OR parent_counter < counter),
    
    status              bool,
    
    --CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
)
INHERITS (module_template);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX message_messages_com_net_via_matrix_counter_counter_x ON message_messages (com_id, net_id, via_id, matrix_counter, counter);
--CREATE INDEX message_messages_com_matrix_counter_profile_active_x ON message_messages (com_id, matrix_counter, profile_id, active);
--CREATE INDEX message_messages_net_matrix_counter_profile_active_x ON message_messages (net_id, matrix_counter, profile_id, active);
--CREATE INDEX message_messages_via_matrix_counter_profile_active_x ON message_messages (via_id, matrix_counter, profile_id, active);

CREATE INDEX message_messages_search_index ON message_messages USING gin(search);

ALTER TABLE public.message_messages OWNER TO vmdbuser;

-- Dynamic check module_matrix
CREATE TRIGGER "message_messages_check_module_matrix_trigger" BEFORE INSERT ON "message_messages" FOR EACH ROW EXECUTE PROCEDURE "check_module_matrix" ();

--Update the updated field on updates
CREATE TRIGGER "message_messages_updated_trigger" BEFORE UPDATE ON "message_messages" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "message_messages_counter_trigger" BEFORE INSERT ON "message_messages" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'matrix_counter');

-- Update the search field
CREATE TRIGGER "message_messages_update_search_trigger" BEFORE INSERT OR UPDATE ON "message_messages" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'content', 'A', 'title', 'B', 'heading', 'C', 'summary', 'C', 'meta_title', 'D', 'meta_description', 'D', 'meta_keywords', 'D');

--Update the total_item_count in module_matrix
CREATE TRIGGER "message_messages_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "message_messages" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('module_matrix', 'total_item_count', 'true', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'counter', 'matrix_counter');


--Update the total_reply_count in message_messages
-- NOTE: Have to use a separate trigger because of the different field names, unlike system_comments where we use ANDSELF
CREATE TRIGGER "message_messages_total_replies_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "message_messages" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('message_messages', 'total_replies_count', 'true', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'counter', 'parent_counter');


----------------------------------------------------------------------------------------------------


