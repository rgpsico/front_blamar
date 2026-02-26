ALTER TABLE sbd95.api_admins
ADD COLUMN cod_sis VARCHAR(16);

ALTER TABLE sbd95.api_admins
ADD CONSTRAINT uq_api_admins_cod_sis UNIQUE (cod_sis);

CREATE INDEX idx_api_admins_cod_sis
ON sbd95.api_admins (cod_sis);


ALTER TABLE incentive.inc_program
ADD COLUMN address TEXT,
ADD COLUMN postal_code VARCHAR(20),
ADD COLUMN state_code VARCHAR(10),
ADD COLUMN phone VARCHAR(50),
ADD COLUMN email VARCHAR(150),
ADD COLUMN website_url TEXT,
ADD COLUMN google_maps_url TEXT,
ADD COLUMN latitude NUMERIC(10,8),
ADD COLUMN longitude NUMERIC(11,8),
ADD COLUMN star_rating INT,
ADD COLUMN total_rooms INT,
ADD COLUMN floor_plan_url TEXT;



ALTER TABLE incentive.inc_room_category
ADD COLUMN area_m2 NUMERIC(6,2),
ADD COLUMN view_type VARCHAR(50),
ADD COLUMN room_type VARCHAR(50);


CREATE TABLE incentive.inc_room_amenity (
    inc_room_amenity_id SERIAL PRIMARY KEY,
    inc_id INT REFERENCES incentive.inc_program(inc_id),
    name VARCHAR(150),
    icon VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE
);


ALTER TABLE incentive.inc_convention_room
ADD COLUMN height_m NUMERIC(4,2),
ADD COLUMN capacity_theater INT,
ADD COLUMN capacity_cocktail INT;