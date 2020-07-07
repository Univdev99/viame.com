DROP TABLE IF EXISTS article_articles CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE article_articles (
    content             text NOT NULL,
    
    --CONSTRAINT valid_space CHECK ((com_id > 0 AND net_id = 0 AND via_id = 0) OR (com_id = 0 AND net_id > 0 AND via_id = 0) OR (com_id = 0 AND net_id = 0 AND via_id > 0)),
    FOREIGN KEY (com_id, net_id, via_id, module_id, matrix_counter) REFERENCES module_matrix (com_id, net_id, via_id, module_id, counter) MATCH SIMPLE ON DELETE CASCADE ON UPDATE CASCADE
)
INHERITS (module_template);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX article_articles_com_net_via_matrix_counter_counter_x ON article_articles (com_id, net_id, via_id, matrix_counter, counter);
--CREATE INDEX article_articles_com_matrix_counter_profile_active_x ON article_articles (com_id, matrix_counter, profile_id, active);
--CREATE INDEX article_articles_net_matrix_counter_profile_active_x ON article_articles (net_id, matrix_counter, profile_id, active);
--CREATE INDEX article_articles_via_matrix_counter_profile_active_x ON article_articles (via_id, matrix_counter, profile_id, active);

CREATE INDEX article_articles_search_index ON article_articles USING gin(search);

ALTER TABLE public.article_articles OWNER TO vmdbuser;

-- Dynamic check module_matrix
CREATE TRIGGER "article_articles_check_module_matrix_trigger" BEFORE INSERT ON "article_articles" FOR EACH ROW EXECUTE PROCEDURE "check_module_matrix" ();

--Update the updated field on updates
CREATE TRIGGER "article_articles_updated_trigger" BEFORE UPDATE ON "article_articles" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

-- Dynamic counter increment
CREATE TRIGGER "article_articles_counter_trigger" BEFORE INSERT ON "article_articles" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'com_id', 'net_id', 'via_id', 'matrix_counter');

-- Update the search field
CREATE TRIGGER "article_articles_update_search_trigger" BEFORE INSERT OR UPDATE ON "article_articles" FOR EACH ROW EXECUTE PROCEDURE "update_search" ('search', 'content', 'A', 'title', 'B', 'heading', 'C', 'summary', 'C', 'meta_title', 'D', 'meta_description', 'D', 'meta_keywords', 'D');

--Update the total_item_count in module_matrix
CREATE TRIGGER "article_articles_total_item_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "article_articles" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('module_matrix', 'total_item_count', 'false', 'com_id', 'com_id', 'net_id', 'net_id', 'via_id', 'via_id', 'module_id', 'module_id', 'counter', 'matrix_counter');


----------------------------------------------------------------------------------------------------


