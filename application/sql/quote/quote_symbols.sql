DROP TABLE IF EXISTS quote_symbols CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE quote_symbols (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    symbol              varchar(16) NOT NULL CHECK (symbol = upper(symbol)),
    
    name                varchar(256) NOT NULL DEFAULT '',
    
    type_id             bigint NOT NULL
    						REFERENCES quote_types (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    exchange_id         bigint NOT NULL
    						REFERENCES quote_exchanges (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    
    delayed_symbol_override    varchar(32),
    realtime_symbol_override   varchar(32),
    
    description         text,
    
    total_followers_count   bigint DEFAULT 0 CHECK (total_followers_count >= 0),
    
    featured            bool,
    logo_url            varchar(256),
    website_url         varchar(512),
    report_url          varchar(512),
    
    orderby             real,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX quote_symbols_type_id_exchange_id_symbol_x ON quote_symbols (type_id, exchange_id, symbol);

ALTER TABLE public.quote_symbols OWNER TO vmdbuser;
ALTER TABLE public.quote_symbols_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "quote_symbols_updated_trigger" BEFORE UPDATE ON "quote_symbols" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


INSERT INTO quote_symbols (symbol, name, type_id, exchange_id) SELECT 'ZZCASHZZ', 'Cash', qt.id, qe.id FROM quote_types AS qt, quote_exchanges AS qe WHERE qt.type='Z' AND qe.code='ZZZ';