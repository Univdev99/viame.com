DROP TABLE IF EXISTS quote_follow_matrix CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE quote_follow_matrix (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    symbol_id           bigint NOT NULL
    						REFERENCES quote_symbols (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              boolean DEFAULT 't'
);

CREATE UNIQUE INDEX quote_follow_matrix_profile_symbol_x ON quote_follow_matrix (profile_id, symbol_id);

ALTER TABLE public.quote_follow_matrix OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "quote_follow_matrix_updated_trigger" BEFORE UPDATE ON "quote_follow_matrix" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();

--Update the quote_symbols total_followers_count field on inserts and deletes
CREATE TRIGGER "quote_follow_total_followers_count_trigger" AFTER INSERT OR UPDATE OR DELETE ON "quote_follow_matrix" FOR EACH ROW EXECUTE PROCEDURE "update_total_count" ('quote_symbols', 'total_followers_count', 'false', 'id', 'symbol_id');


----------------------------------------------------------------------------------------------------


