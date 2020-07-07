DROP TABLE IF EXISTS quote_types CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE quote_types (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    type                varchar(8) NOT NULL CHECK (type = upper(type)),
    
    display             varchar(128) NOT NULL DEFAULT '',
    display_plural      varchar(128) NOT NULL DEFAULT '',
    
    int_prefix          varchar(8),
    int_suffix          varchar(8),
    
    delayed_df_prefix   varchar(8),
    delayed_df_suffix   varchar(8),
    
    realtime_df_prefix  varchar(8),
    realtime_df_suffix  varchar(8),
    
    orderby             real,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX quote_types_type_x ON quote_types (type);

--CREATE INDEX quote_types_id_code_x ON quote_types (id, code);

ALTER TABLE public.quote_types OWNER TO vmdbuser;
ALTER TABLE public.quote_types_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "quote_types_updated_trigger" BEFORE UPDATE ON "quote_types" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


INSERT INTO quote_types (type, display, display_plural, orderby) VALUES ('Z', 'Unknown', 'Unknown', 100);
INSERT INTO quote_types (type, display, display_plural, orderby) VALUES ('S', 'Stock', 'Stocks', 10);
INSERT INTO quote_types (type, display, display_plural, orderby) VALUES ('M', 'Fund', 'Funds', 20);
INSERT INTO quote_types (type, display, display_plural, orderby, int_prefix, delayed_df_prefix, realtime_df_prefix) VALUES ('I', 'Index', 'Indices', 80, '^', '^', '^');
INSERT INTO quote_types (type, display, display_plural, orderby) VALUES ('E', 'ETF', 'ETFs', 30);
INSERT INTO quote_types (type, display, display_plural, orderby) VALUES ('F', 'Future', 'Futures', 50);
INSERT INTO quote_types (type, display, display_plural, orderby) VALUES ('O', 'Option', 'Options', 40);
INSERT INTO quote_types (type, display, display_plural, orderby) VALUES ('C', 'Currency', 'Currencies', 70);