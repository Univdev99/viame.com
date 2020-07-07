DROP TABLE IF EXISTS log_webhooks CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE log_webhooks (
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    identifier          varchar(512),
    
    ip_address          inet,
    
    serialized_get      text,
    serialized_post     text,
    
    serialized_result   text,
    
    active              bool DEFAULT 't'
);


ALTER TABLE public.log_webhooks OWNER TO vmdbuser;


----------------------------------------------------------------------------------------------------


