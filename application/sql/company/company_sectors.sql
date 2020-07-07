DROP TABLE IF EXISTS company_sectors CASCADE; -- CASCADE will drop all references to this table

CREATE TABLE company_sectors (
    id                  bigserial PRIMARY KEY,
    creation            timestamp WITH TIME ZONE NOT NULL DEFAULT now(),

    industry_id         bigint NOT NULL
    						REFERENCES company_industries (id)
    						ON DELETE CASCADE
    						ON UPDATE CASCADE,
    						
    sector              varchar(256) NOT NULL,
    
    orderby             real,
    
    updated             timestamp WITH TIME ZONE NOT NULL DEFAULT now(),
    active              bool DEFAULT 't' -- NULL=created but not yet active and not deactivated
);

-- Index on ID is automatically created by PRIMARY KEY
CREATE UNIQUE INDEX company_sectors_sector_x ON company_sectors (sector);
--CREATE INDEX company_sectors_id_code_x ON company_sectors (id, sector);

ALTER TABLE public.company_sectors OWNER TO vmdbuser;
ALTER TABLE public.company_sectors_id_seq OWNER TO vmdbuser;

--Update the updated field on updates
CREATE TRIGGER "company_sectors_updated_trigger" BEFORE UPDATE ON "company_sectors" FOR EACH ROW EXECUTE PROCEDURE "current_updated" ();


----------------------------------------------------------------------------------------------------


INSERT INTO company_sectors (industry, sector) SELECT id, 'Agricultural Chemicals' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Aluminum' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Chemicals - Major Diversified' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Copper' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Gold' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Independent Oil & Gas' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Industrial Metals & Minerals' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Major Integrated Oil & Gas' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Nonmetallic Mineral Mining' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Oil & Gas Drilling & Exploration' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Oil & Gas Equipment & Services' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Oil & Gas Pipelines' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Oil & Gas Refining & Marketing' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Silver' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Specialty Chemicals' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Steel & Iron' FROM company_industries WHERE industry='Basic Materials';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Synthetics' FROM company_industries WHERE industry='Basic Materials';

INSERT INTO company_sectors (industry, sector) SELECT id, 'Conglomerates' FROM company_industries WHERE industry='Conglomerates';

INSERT INTO company_sectors (industry, sector) SELECT id, 'Appliances' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Auto Manufacturers - Major' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Auto Parts' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Beverages - Brewers' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Beverages - Soft Drinks' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Beverages - Wineries & Distillers' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Business Equipment' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Cigarettes' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Cleaning Products' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Confectioners' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Dairy Products' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Electronic Equipment' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Farm Products' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Food - Major Diversified' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Home Furnishings & Fixtures' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Housewares & Accessories' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Meat Products' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Office Supplies' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Packaging & Containers' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Paper & Paper Products' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Personal Products' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Photographic Equipment & Supplies' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Processed & Packaged Goods' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Recreational Goods, Other' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Recreational Vehicles' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Rubber & Plastics' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Sporting Goods' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Textile - Apparel Clothing' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Textile - Apparel Footwear & Accessories' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Tobacco Products, Other' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Toys & Games' FROM company_industries WHERE industry='Consumer Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Trucks & Other Vehicles' FROM company_industries WHERE industry='Consumer Goods';

INSERT INTO company_sectors (industry, sector) SELECT id, 'Accident & Health Insurance' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Asset Management' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Closed-End Fund - Debt' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Closed-End Fund - Equity' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Closed-End Fund - Foreign' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Credit Services' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Diversified Investments' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Foreign Money Center Banks' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Foreign Regional Banks' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Insurance Brokers' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Investment Brokerage - National' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Investment Brokerage - Regional' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Life Insurance' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Money Center Banks' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Mortgage Investment' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Property & Casualty Insurance' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Property Management' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'REIT - Diversified' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'REIT - Healthcare Facilities' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'REIT - Hotel/Motel' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'REIT - Industrial' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'REIT - Office' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'REIT - Residential' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'REIT - Retail' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Real Estate Development' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Regional - Mid-Atlantic Banks' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Regional - Midwest Banks' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Regional - Northeast Banks' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Regional - Pacific Banks' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Regional - Southeast Banks' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Regional - Southwest Banks' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Savings & Loans' FROM company_industries WHERE industry='Financial';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Surety & Title Insurance' FROM company_industries WHERE industry='Financial';

INSERT INTO company_sectors (industry, sector) SELECT id, 'Biotechnology' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Diagnostic Substances' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Drug Delivery' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Drug Manufacturers - Major' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Drug Manufacturers - Other' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Drug Related Products' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Drugs - Generic' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Health Care Plans' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Home Health Care' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Hospitals' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Long-Term Care Facilities' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Medical Appliances & Equipment' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Medical Instruments & Supplies' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Medical Laboratories & Research' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Medical Practitioners' FROM company_industries WHERE industry='Healthcare';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Specialized Health Services' FROM company_industries WHERE industry='Healthcare';

INSERT INTO company_sectors (industry, sector) SELECT id, 'Aerospace/Defense - Major Diversified' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Aerospace/Defense Products & Services' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Cement' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Diversified Machinery' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Farm & Construction Machinery' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'General Building Materials' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'General Contractors' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Heavy Construction' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Industrial Electrical Equipment' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Industrial Equipment & Components' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Lumber, Wood Production' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Machine Tools & Accessories' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Manufactured Housing' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Metal Fabrication' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Pollution & Treatment Controls' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Residential Construction' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Small Tools & Accessories' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Textile Industrial' FROM company_industries WHERE industry='Industrial Goods';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Waste Management' FROM company_industries WHERE industry='Industrial Goods';

INSERT INTO company_sectors (industry, sector) SELECT id, 'Advertising Agencies' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Air Delivery & Freight Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Air Services, Other' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Apparel Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Auto Dealerships' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Auto Parts Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Auto Parts Wholesale' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Basic Materials Wholesale' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Broadcasting - Radio' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Broadcasting - TV' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Building Materials Wholesale' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Business Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'CATV Systems' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Catalog & Mail Order Houses' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Computers Wholesale' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Consumer Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Department Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Discount, Variety Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Drug Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Drugs Wholesale' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Education & Training Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Electronics Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Electronics Wholesale' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Entertainment - Diversified' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Food Wholesale' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Gaming Activities' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'General Entertainment' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Grocery Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Home Furnishing Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Home Improvement Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Industrial Equipment Wholesale' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Jewelry Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Lodging' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Major Airlines' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Management Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Marketing Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Medical Equipment Wholesale' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Movie Production, Theaters' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Music & Video Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Personal Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Publishing - Books' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Publishing - Newspapers' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Publishing - Periodicals' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Railroads' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Regional Airlines' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Rental & Leasing Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Research Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Resorts & Casinos' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Restaurants' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Security & Protection Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Shipping' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Specialty Eateries' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Specialty Retail, Other' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Sporting Activities' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Sporting Goods Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Staffing & Outsourcing Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Technical Services' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Toy & Hobby Stores' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Trucking' FROM company_industries WHERE industry='Services';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Wholesale, Other' FROM company_industries WHERE industry='Services';

INSERT INTO company_sectors (industry, sector) SELECT id, 'Application Software' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Business Software & Services' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Communication Equipment' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Computer Based Systems' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Computer Peripherals' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Data Storage Devices' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Diversified Communication Services' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Diversified Computer Systems' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Diversified Electronics' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Healthcare Information Services' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Information & Delivery Services' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Information Technology Services' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Internet Information Providers' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Internet Service Providers' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Internet Software & Services' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Long Distance Carriers' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Multimedia & Graphics Software' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Networking & Communication Devices' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Personal Computers' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Printed Circuit Boards' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Processing Systems & Products' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Scientific & Technical Instruments' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Security Software & Services' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Semiconductor - Broad Line' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Semiconductor - Integrated Circuits' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Semiconductor - Specialized' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Semiconductor Equipment & Materials' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Semiconductor- Memory Chips' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Technical & System Software' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Telecom Services - Domestic' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Telecom Services - Foreign' FROM company_industries WHERE industry='Technology';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Wireless Communications' FROM company_industries WHERE industry='Technology';

INSERT INTO company_sectors (industry, sector) SELECT id, 'Diversified Utilities' FROM company_industries WHERE industry='Utilities';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Electric Utilities' FROM company_industries WHERE industry='Utilities';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Foreign Utilities' FROM company_industries WHERE industry='Utilities';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Gas Utilities' FROM company_industries WHERE industry='Utilities';
INSERT INTO company_sectors (industry, sector) SELECT id, 'Water Utilities' FROM company_industries WHERE industry='Utilities';