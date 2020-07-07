DROP TABLE IF EXISTS system_currencies CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE system_currencies (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    
    code                varchar(8) NOT NULL CHECK (code = upper(code)),
    currency            varchar(128) NOT NULL,
    
    orderby             real,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX system_currencies_code_x ON system_currencies (code);
--CREATE INDEX system_currencies_id_code_x ON system_currencies (id, code);

ALTER TABLE public.system_currencies OWNER TO vmdbuser;
ALTER TABLE public.system_currencies_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "system_currencies_updated_trigger" BEFORE UPDATE ON "system_currencies" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('AFN', 'Afghani', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ALL', 'Albanian Lek', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('DZD', 'Algerian Dinar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('AOA', 'Angolan Kwanza', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ARS', 'Argentine Peso', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('AMD', 'Armenian Dram', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('AWG', 'Aruban Florin', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('AUD', 'Australian Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('AZN', 'Azerbaijanian Manat', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BSD', 'Bahamian Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BHD', 'Bahraini Dinar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BDT', 'Bangladeshi Taka', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BBD', 'Barbados Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BYR', 'Belarussian Ruble', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BZD', 'Belize Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BMD', 'Bermudan Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BOB', 'Boliviano', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BAM', 'Bosnia-Herzegovina Convertible Mark', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BWP', 'Botswanan Pula', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BRL', 'Brazilian Real', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('GBP', 'British Pound Sterling', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BND', 'Brunei Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BGN', 'Bulgarian Lev', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BUK', 'Burmese Kyat', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('BIF', 'Burundi Franc', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('KHR', 'Cambodian Riel', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('CAD', 'Canadian Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('CVE', 'Cape Verde Escudo', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('KYD', 'Cayman Islands Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('XOF', 'CFA Franc BCEAO', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('XAF', 'CFA Franc BEAC', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('XPF', 'CFP Franc', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('CLP', 'Chilean Peso', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('CNY', 'Chinese Yuan Renminbi', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('COP', 'Colombian Peso', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('KMF', 'Comoro Franc', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('CDF', 'Congolese Franc Congolais', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('CRC', 'Costa Rican Colon', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('HRK', 'Croatian Kuna', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('CUP', 'Cuban Peso', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('CZK', 'Czech Republic Koruna', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('DKK', 'Danish Krone', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('DJF', 'Djibouti Franc', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('DOP', 'Dominican Peso', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('XCD', 'East Caribbean Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('DDM', 'East German Ostmark', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('EGP', 'Egyptian Pound', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SVC', 'El Salvador Colon', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ERN', 'Eritrean Nakfa', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('EEK', 'Estonian Kroon', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ETB', 'Ethiopian Birr', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('EUR', 'Euro', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('FKP', 'Falkland Islands Pound', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('FJD', 'Fiji Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('GMD', 'Gambia Dalasi', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('GEL', 'Georgian Lari', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('GHS', 'Ghana Cedi', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('GIP', 'Gibraltar Pound', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('GTQ', 'Guatemala Quetzal', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('GNF', 'Guinea Franc', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('GWP', 'Guinea-Bissau Peso', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('GYD', 'Guyana Dollar', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('HTG', 'Haitian Gourde', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('HNL', 'Honduras Lempira', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('HKD', 'Hong Kong Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('HUF', 'Hungarian Forint', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ISK', 'Icelandic Krona', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('INR', 'Indian Rupee', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('IDR', 'Indonesian Rupiah', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('IRR', 'Iranian Rial', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('IQD', 'Iraqi Dinar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('JMD', 'Jamaican Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('JPY', 'Japanese Yen', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('JOD', 'Jordanian Dinar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('KZT', 'Kazakhstan Tenge', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('KES', 'Kenyan Shilling', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('KWD', 'Kuwaiti Dinar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('KGS', 'Kyrgystan Som', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('LAK', 'Laotian Kip', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('LVL', 'Latvian Lats', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('LBP', 'Lebanese Pound', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('LRD', 'Liberian Dollar', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('LYD', 'Libyan Dinar', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('LTL', 'Lithuanian Lita', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MOP', 'Macao Pataca', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MKD', 'Macedonian Denar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MGA', 'Madagascar Ariary', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MWK', 'Malawi Kwacha', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MYR', 'Malaysian Ringgit', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MVR', 'Maldive Islands Rufiyaa', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MRO', 'Mauritania Ouguiya', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MUR', 'Mauritius Rupee', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MXN', 'Mexican Peso', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MDL', 'Moldovan Leu', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MNT', 'Mongolian Tugrik', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MAD', 'Moroccan Dirham', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MZN', 'Mozambique Metical', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('MMK', 'Myanmar Kyat', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('NPR', 'Nepalese Rupee', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ANG', 'Netherlands Antillan Guilder', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ILS', 'New Israeli Sheqel', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('NZD', 'New Zealand Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('NIO', 'Nicaraguan Cordoba Oro', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('NGN', 'Nigerian Naira', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('KPW', 'North Korean Won', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('NOK', 'Norwegian Krone', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('CSD', 'Old Serbian Dinar', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('OMR', 'Oman Rial', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('PKR', 'Pakistan Rupee', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('PAB', 'Panamanian Balboa', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('PGK', 'Papua New Guinea Kina', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('PYG', 'Paraguay Guarani', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('PEN', 'Peruvian Sol Nuevo', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('PHP', 'Philippine Peso', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('PLN', 'Polish Zloty', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('QAR', 'Qatari Rial', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('RON', 'Romanian Leu', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('RUB', 'Russian Ruble', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('RWF', 'Rwandan Franc', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SHP', 'Saint Helena Pound', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('STD', 'Sao Tome and Principe Dobra', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SAR', 'Saudi Riyal', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('RSD', 'Serbian Dinar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SCR', 'Seychelles Rupee', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SLL', 'Sierra Leone Leone', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SGD', 'Singapore Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SKK', 'Slovak Koruna', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SBD', 'Solomon Islands Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SOS', 'Somali Shilling', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ZAR', 'South African Rand', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('KRW', 'South Korean Won', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SUR', 'Soviet Rouble', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('LKR', 'Sri Lanka Rupee', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SDG', 'Sudanese Pound', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SRD', 'Surinam Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SZL', 'Swaziland Lilangeni', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SEK', 'Swedish Krona', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('CHF', 'Swiss Franc', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('SYP', 'Syrian Pound', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('TWD', 'Taiwan New Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('TJS', 'Tajikistan Somoni', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('TZS', 'Tanzanian Shilling', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('THB', 'Thai Baht', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('TPE', 'Timor Escudo', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('TOP', 'Tonga Pa''anga', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('TTD', 'Trinidad and Tobago Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('TND', 'Tunisian Dinar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('TRY', 'Turkish Lira', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('TMM', 'Turkmenistan Manat', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('UGX', 'Ugandan Shilling', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('UAH', 'Ukrainian Hryvnia', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('AED', 'United Arab Emirates Dirham', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('XXX', 'Unknown or Invalid Currency', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('UYU', 'Uruguay Peso Uruguayo', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('USD', 'US Dollar', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('UZS', 'Uzbekistan Sum', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('VUV', 'Vanuatu Vatu', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('VEF', 'Venezuelan Bolivar Fuerte', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('VND', 'Vietnamese Dong', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('WST', 'Western Samoa Tala', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('YER', 'Yemeni Rial', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('YUM', 'Yugoslavian Noviy Dinar', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ZRN', 'Zairean New Zaire', 10, 'f');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ZMK', 'Zambian Kwacha', 10, 't');
INSERT INTO system_currencies (code, currency, orderby, active) VALUES ('ZWD', 'Zimbabwe Dollar', 10, 't');