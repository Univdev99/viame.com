DROP TABLE IF EXISTS system_sessions CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE system_sessions (
    id                  char(32) NOT NULL PRIMARY KEY,
    
    modified            bigint,
    lifetime            bigint,
    
    data                text
);

-- Index on ID is automatically created by PRIMARY KEY

ALTER TABLE public.system_sessions OWNER TO vmdbuser;


----------------------------------------------------------------------------------------------------

