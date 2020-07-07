DROP TABLE IF EXISTS quote_data CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE quote_data (
    symbol_id                       bigint NOT NULL
                						REFERENCES quote_symbols (id)
                						ON DELETE CASCADE
                						ON UPDATE CASCADE,
    
    last                            numeric NOT NULL,
    last_size                       int,
    day_high                        numeric,
    day_low                         numeric,
    volume                          bigint,
    average_daily_volume            bigint,
    bid                             numeric,
    bid_size                        int,
    ask                             numeric,
    ask_size                        int,
    previous_close                  numeric,
    open                            numeric,
    
    year_high                       numeric,
    year_low                        numeric,
    
    earnings_share                  real,   
    pe_ratio                        real,
    short_ratio                     real,
    dividend_share                  real,
    dividend_yield                  real,
    stock_float                     bigint,
    market_cap                      bigint,
    
    eps_est_current_year            real,
    eps_est_next_year               real,
    eps_est_next_quarter            real,
    price_eps_est_current_year      real,
    price_eps_est_next_year         real,
    peg_ratio                       real,
    book_value                      real,
    price_book                      real,
    price_sales                     real,
    ebitda                          bigint,
    ma_50                           real,
    ma_200                          real,
    
    
    updated                         timestamp WITH TIME ZONE NOT NULL DEFAULT now()
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX quote_data_symbol_id_x ON quote_data (symbol_id);

ALTER TABLE public.quote_data OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "quote_data_updated_trigger" BEFORE UPDATE ON "quote_data" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


INSERT INTO quote_data (symbol_id, last) SELECT id, 1 FROM quote_symbols WHERE symbol='ZZCASHZZ' and type_id=1 AND exchange_id=1;