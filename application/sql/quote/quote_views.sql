DROP VIEW IF EXISTS quote_view_symbol_matrix;
CREATE OR REPLACE VIEW quote_view_symbol_matrix (id, symbol, name, type, typedisp, typedisps, exch, exchdisp, internal_symbol, delayed_symbol, realtime_symbol, seconds_since_data_updated, active)
    AS
SELECT
    qs.id, qs.symbol, qs.name, qt.type, qt.display, qt.display_plural, qe.code, qe.exchange,
    COALESCE(qe.int_prefix, '') || COALESCE(qt.int_prefix, '') || qs.symbol || COALESCE(qt.int_suffix, '') || COALESCE(qe.int_suffix, ''),
    COALESCE (
        qs.delayed_symbol_override,
        COALESCE(qe.delayed_df_prefix, '') || COALESCE(qt.delayed_df_prefix, '') || qs.symbol || COALESCE(qt.delayed_df_suffix, '') || COALESCE(qe.delayed_df_suffix, '')
    ),
    COALESCE (
        qs.realtime_symbol_override,
        COALESCE(qe.realtime_df_prefix, '') || COALESCE(qt.realtime_df_prefix, '') || qs.symbol || COALESCE(qt.realtime_df_suffix, '') || COALESCE(qe.realtime_df_suffix, '')
    ),
    CEIL(EXTRACT('epoch' FROM now() - qd.updated)),
    qs.active
FROM quote_symbols AS qs
INNER JOIN quote_types AS qt ON (qs.type_id=qt.id)
INNER JOIN quote_exchanges AS qe ON (qs.exchange_id=qe.id)
LEFT JOIN quote_data AS qd ON (qs.id=qd.symbol_id)
--WHERE qs.active='t' AND qt.active='t' AND qe.active='t' AND (qd.active ISNULL OR qd.active='t')
ORDER BY qs.orderby, qe.orderby, qt.orderby
;

--DROP VIEW IF EXISTS quote_view_symbol_matrix;
--CREATE OR REPLACE VIEW quote_view_symbol_matrix (id, symbol, name, type, typedisp, typedisps, exch, exchdisp, internal_symbol, delayed_symbol, realtime_symbol, seconds_since_data_updated)
--    AS
--SELECT
--    qs.id, qs.symbol, qs.name, qt.type, qt.display, qt.display_plural, qe.code, qe.exchange,
--    COALESCE (
--        qe.int_prefix || qt.int_prefix || qs.symbol || qt.int_suffix || qe.int_suffix,
--        qt.int_prefix || qs.symbol || qt.int_suffix,
--        qe.int_prefix || qs.symbol || qe.int_suffix,
--        COALESCE(qe.int_prefix, '') || COALESCE(qt.int_prefix, '') || qs.symbol || COALESCE(qt.int_suffix, '') || COALESCE(qe.int_suffix, ''),
--        COALESCE(qt.int_prefix, '') || qs.symbol || COALESCE(qt.int_suffix, ''),
--        COALESCE(qe.int_prefix, '') || qs.symbol || COALESCE(qe.int_suffix, ''),
--        qs.symbol
--    ),
--    COALESCE (
--        qs.delayed_symbol_override,
--        qe.delayed_df_prefix || qt.delayed_df_prefix || qs.symbol || qt.delayed_df_suffix || qe.delayed_df_suffix,
--        qt.delayed_df_prefix || qs.symbol || qt.delayed_df_suffix,
--        qe.delayed_df_prefix || qs.symbol || qe.delayed_df_suffix,
--        COALESCE(qe.delayed_df_prefix, '') || COALESCE(qt.delayed_df_prefix, '') || qs.symbol || COALESCE(qt.delayed_df_suffix, '') || COALESCE(qe.delayed_df_suffix, ''),
--        COALESCE(qt.delayed_df_prefix, '') || qs.symbol || COALESCE(qt.delayed_df_suffix, ''),
--        COALESCE(qe.delayed_df_prefix, '') || qs.symbol || COALESCE(qe.delayed_df_suffix, ''),
--        qs.symbol
--    ),
--    COALESCE (
--        qs.realtime_symbol_override,
--        qe.realtime_df_prefix || qt.realtime_df_prefix || qs.symbol || qt.realtime_df_suffix || qe.realtime_df_suffix,
--        qt.realtime_df_prefix || qs.symbol || qt.realtime_df_suffix,
--        qe.realtime_df_prefix || qs.symbol || qe.realtime_df_suffix,
--        COALESCE(qe.realtime_df_prefix, '') || COALESCE(qt.realtime_df_prefix, '') || qs.symbol || COALESCE(qt.realtime_df_suffix, '') || COALESCE(qe.realtime_df_suffix, ''),
--        COALESCE(qt.realtime_df_prefix, '') || qs.symbol || COALESCE(qt.realtime_df_suffix, ''),
--        COALESCE(qe.realtime_df_prefix, '') || qs.symbol || COALESCE(qe.realtime_df_suffix, ''),
--        qs.symbol
--    ),
--    CEIL(EXTRACT('epoch' FROM now() - qd.updated))
--FROM quote_symbols AS qs
--INNER JOIN quote_types AS qt ON (qs.type_id=qt.id)
--INNER JOIN quote_exchanges AS qe ON (qs.exchange_id=qe.id)
--LEFT JOIN quote_data AS qd ON (qs.id=qd.symbol_id)
----WHERE qs.active='t' AND qt.active='t' AND qe.active='t' AND (qd.active ISNULL OR qd.active='t')
--ORDER BY qs.orderby, qe.orderby, qt.orderby
--;

--ALTER VIEW public.quote_view_symbol_matrix OWNER TO vmdbuser;
-- Remove next line and uncomment previous with upgrade to 8.4
ALTER TABLE public.quote_view_symbol_matrix OWNER TO vmdbuser;


DROP VIEW IF EXISTS quote_view_symbol_matrix_no_qd;
CREATE OR REPLACE VIEW quote_view_symbol_matrix_no_qd (id, symbol, name, type, typedisp, typedisps, exch, exchdisp, internal_symbol, delayed_symbol, realtime_symbol, active)
    AS
SELECT
    qs.id, qs.symbol, qs.name, qt.type, qt.display, qt.display_plural, qe.code, qe.exchange,
    COALESCE(qe.int_prefix, '') || COALESCE(qt.int_prefix, '') || qs.symbol || COALESCE(qt.int_suffix, '') || COALESCE(qe.int_suffix, ''),
    COALESCE (
        qs.delayed_symbol_override,
        COALESCE(qe.delayed_df_prefix, '') || COALESCE(qt.delayed_df_prefix, '') || qs.symbol || COALESCE(qt.delayed_df_suffix, '') || COALESCE(qe.delayed_df_suffix, '')
    ),
    COALESCE (
        qs.realtime_symbol_override,
        COALESCE(qe.realtime_df_prefix, '') || COALESCE(qt.realtime_df_prefix, '') || qs.symbol || COALESCE(qt.realtime_df_suffix, '') || COALESCE(qe.realtime_df_suffix, '')
    ),
    qs.active
FROM quote_symbols AS qs
INNER JOIN quote_types AS qt ON (qs.type_id=qt.id)
INNER JOIN quote_exchanges AS qe ON (qs.exchange_id=qe.id)
ORDER BY qs.orderby, qe.orderby, qt.orderby
;

ALTER TABLE public.quote_view_symbol_matrix_no_qd OWNER TO vmdbuser;


--SELECT * FROM quote_view_symbol_matrix;


DROP VIEW IF EXISTS quote_view_data;
-- OLD WAY
--CREATE OR REPLACE VIEW quote_view_data
--    AS
--SELECT
--    qs.id,
--    COALESCE(qe.int_prefix, '') || COALESCE(qt.int_prefix, '') || qs.symbol || COALESCE(qt.int_suffix, '') || COALESCE(qe.int_suffix, '') AS symbol,
--    qs.name, qt.type, qt.display, qt.display_plural, qe.code, qe.exchange, qd.*
--FROM quote_symbols AS qs
--INNER JOIN quote_types AS qt ON (qs.type_id=qt.id)
--INNER JOIN quote_exchanges AS qe ON (qs.exchange_id=qe.id)
---LEFT JOIN quote_data AS qd ON (qs.id=qd.symbol_id)
--ORDER BY qs.orderby, qe.orderby, qt.orderby
--;
CREATE OR REPLACE VIEW quote_view_data
    AS
SELECT
    qvsmnq.*, qd.*, CEIL(EXTRACT('epoch' FROM now() - qd.updated)) AS seconds_since_data_updated
FROM quote_view_symbol_matrix_no_qd AS qvsmnq
LEFT JOIN quote_data AS qd ON (qvsmnq.id=qd.symbol_id)
;


--DROP VIEW IF EXISTS quote_view_data;
--CREATE OR REPLACE VIEW quote_view_data
--    AS
--SELECT
--    qs.id,
--    COALESCE (
--        qe.int_prefix || qt.int_prefix || qs.symbol || qt.int_suffix || qe.int_suffix,
--        qt.int_prefix || qs.symbol || qt.int_suffix,
--        qe.int_prefix || qs.symbol || qe.int_suffix,
--        COALESCE(qe.int_prefix, '') || COALESCE(qt.int_prefix, '') || qs.symbol || COALESCE(qt.int_suffix, '') || COALESCE(qe.int_suffix, ''),
--        COALESCE(qt.int_prefix, '') || qs.symbol || COALESCE(qt.int_suffix, ''),
--        COALESCE(qe.int_prefix, '') || qs.symbol || COALESCE(qe.int_suffix, ''),
--        qs.symbol
--    ) AS symbol,
--    qs.name, qt.type, qt.display, qt.display_plural, qe.code, qe.exchange, qd.*
--FROM quote_symbols AS qs
--INNER JOIN quote_types AS qt ON (qs.type_id=qt.id)
--INNER JOIN quote_exchanges AS qe ON (qs.exchange_id=qe.id)
--LEFT JOIN quote_data AS qd ON (qs.id=qd.symbol_id)
----WHERE qs.active='t' AND qt.active='t' AND qe.active='t' AND (qd.active ISNULL OR qd.active='t')
--ORDER BY qs.orderby, qe.orderby, qt.orderby
--;

--ALTER VIEW public.quote_view_data OWNER TO vmdbuser;
-- Remove next line and uncomment previous with upgrade to 8.4
ALTER TABLE public.quote_view_data OWNER TO vmdbuser;