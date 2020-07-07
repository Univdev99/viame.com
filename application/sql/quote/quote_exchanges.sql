DROP TABLE IF EXISTS quote_exchanges CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE quote_exchanges (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    code                varchar(8) NOT NULL CHECK (code = upper(code)),
    
    exchange            varchar(128),
    
    google_exchange_pre varchar(128),
    
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
CREATE UNIQUE INDEX quote_exchanges_code_x ON quote_exchanges (code);

--CREATE INDEX quote_exchanges_id_code_x ON quote_exchanges (id, code);

ALTER TABLE public.quote_exchanges OWNER TO vmdbuser;
ALTER TABLE public.quote_exchanges_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "quote_exchanges_updated_trigger" BEFORE UPDATE ON "quote_exchanges" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


INSERT INTO quote_exchanges (code, exchange) VALUES ('ZZZ', 'Unknown');
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('AMS', 'Amsterdam', '.AS', '.AS', '.AS', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('ASE', 'AMEX', NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('ASQ', 'AMEX', NULL, NULL, NULL, 12);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('ASX', 'Australian', '.AX', '.AX', '.AX', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('BAR', 'Barcelona', '.BC', '.BC', '.BC', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('BER', 'Berlin', '.BE', '.BE', '.BE', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('BRU', 'Brussels', '.BR', '.BR', '.BR', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('BSE', 'Bombay', '.BO', '.BO', '.BO', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('BUE', 'Buenos Aires', '.BA', '.BA', '.BA', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('CAI', 'Cairo', NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('CBT', 'CBOT', '.CBT', '.CBT', '.CBT', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('CCY', 'Currency', '=X', '=X', '=X', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('CME', NULL, '.CME', '.CME', '.CME', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('CMX', 'Comex', '.CMX', '.CMX', '.CMX', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('CPH', 'Copenhagen', '.CO', '.CO', '.CO', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('DJI', NULL, NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('DUS', 'Dusseldorf', '.DU', '.DU', '.DU', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('EBS', 'Swiss', '.SW', '.SW', '.SW', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('ENX', 'Euronext', '.NX', '.NX', '.NX', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('EUX', 'EUREX', '.EX', '.EX', '.EX', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('FGI', NULL, '.FGI', '.FGI', '.FGI', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('FRA', 'Frankfurt', '.F', '.F', '.F', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('FSI', 'London', '.L', '.L', '.L', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('GER', 'XETRA', '.DE', '.DE', '.DE', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('HAM', 'Hamburg', '.HM', '.HM', '.HM', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('HAN', 'Hanover', '.HA', '.HA', '.HA', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('HKG', 'Hong Kong', '.HK', '.HK', '.HK', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('IOB', NULL, '.IL', '.IL', '.IL', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('ISE', 'Irish', '.IR', '.IR', '.IR', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('JKT', 'Jakarta', '.JK', '.JK', '.JK', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('KLS', 'Kuala Lumpur', '.KL', '.KL', '.KL', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('KOE', 'KOSDAQ', '.KQ', '.KQ', '.KQ', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('KSC', 'Korea', '.KS', '.KS', '.KS', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('LIS', 'Lisbon', '.LS', '.LS', '.LS', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('LSE', 'London', '.L', '.L', '.L', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('MAD', 'Madrid', '.MA', '.MA', '.MA', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('MCE', NULL, '.MC', '.MC', '.MC', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('MDD', NULL, '.MDD', '.MDD', '.MDD', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('MEX', 'Mexico', '.MX', '.MX', '.MX', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('MIL', 'Milan', '.MI', '.MI', '.MI', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('MUN', 'Munich', '.MU', '.MU', '.MU', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('NAS', 'Nasdaq', NULL, NULL, NULL, 16);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('NCM', 'NasdaqCM', NULL, NULL, NULL, 14);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('NGM', 'NasdaqGM', NULL, NULL, NULL, 12);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('NMS', 'NasdaqGS', NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('NSI', 'NSE', '.NS', '.NS', '.NS', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('NYB', 'NYBOT', '.NYB', '.NYB', '.NYB', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('NYQ', 'NYSE', NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('NYM', 'NY Mercantile', '.NYM', '.NYM', '.NYM', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('NZE', 'New Zealand', '.NZ', '.NZ', '.NZ', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('OBB', 'OTC BB', NULL, '.OB', '.OB', 20);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('OPR', NULL, '.X', '.X', '.X', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('OSA', 'Osaka', NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('OSL', 'Oslo', '.OL', '.OL', '.OL', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('PAR', 'Paris', '.PA', '.PA', '.PA', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('PCX', 'NYSEArca', NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('PHS', 'Philippine', NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('PNK', 'Pink Sheets', NULL, '.PK', '.PK', 30);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('RUS', NULL, '.RS', '.RS', '.RS', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('SAO', 'Sao Paolo', '.SA', '.SA', '.SA', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('SES', 'Singapore', '.SI', '.SI', '.SI', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('SHH', 'Shanghai', '.SS', '.SS', '.SS', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('SHZ', 'Shenzhen', '.SZ', '.SZ', '.SZ', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('SNP', NULL, NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('STO', 'Stockholm', '.ST', '.ST', '.ST', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('STU', 'Stuttgart', '.SG', '.SG', '.SG', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('TAI', 'Taiwan', '.TW', '.TW', '.TW', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('TLO', NULL, '.TI', '.TI', '.TI', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('TLV', 'Tel Aviv', '.TA', '.TA', '.TA', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('TOR', 'Toronto', '.TO', '.TO', '.TO', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('TWO', 'Taiwan', '.TWO', '.TWO', '.TWO', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('VAL', NULL, '.VA', '.VA', '.VA', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('VAN', 'CDNX', '.V', '.V', '.V', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('VIE', 'Vienna', '.VI', '.VI', '.VI', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('VTX', NULL, '.VX', '.VX', '.VX', 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('WCB', 'Chicago Options', NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('XPH', 'Philadelphia', NULL, NULL, NULL, 10);
INSERT INTO quote_exchanges (code, exchange, int_suffix, delayed_df_suffix, realtime_df_suffix, orderby) VALUES ('ZRH', 'Zurich', '.Z', '.Z', '.Z', 10);