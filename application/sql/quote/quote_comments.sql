DROP TABLE IF EXISTS quote_comments CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE quote_comments (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    symbol_id           bigint NOT NULL
    						REFERENCES quote_symbols (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    counter             bigint NOT NULL DEFAULT 1 CHECK (counter >= 1),
    
    profile_id          bigint NOT NULL
    						REFERENCES profile_profiles (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    ip_address          inet,
    
    content             varchar(256) NOT NULL,
    active              bool DEFAULT 't'
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX quote_comments_symbol_counter_x ON quote_comments (symbol_id, counter);

ALTER TABLE public.quote_comments OWNER TO vmdbuser;

-- Dynamic counter increment
CREATE TRIGGER "quote_comments_counter_trigger" BEFORE INSERT ON "quote_comments" FOR EACH ROW EXECUTE PROCEDURE "dynamic_incrementer" ('counter', 'symbol_id');


----------------------------------------------------------------------------------------------------


