-- ============================================================
-- V2 - HOTEL FACTSHEET STRUCTURE (INCENTIVE MODULE)
-- BLUMAR INCENTIVE HOTELS
-- ============================================================


-- ============================================================
-- 1. INC_PROGRAM (HOTEL MAIN DATA)
-- ============================================================

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



-- ============================================================
-- 2. ROOM CATEGORY DETAILS
-- ============================================================

ALTER TABLE incentive.inc_room_category
ADD COLUMN area_m2 NUMERIC(6,2),
ADD COLUMN view_type VARCHAR(50),
ADD COLUMN room_type VARCHAR(50);



-- ============================================================
-- 3. ROOM AMENITIES (NEW TABLE)
-- ============================================================

CREATE TABLE IF NOT EXISTS incentive.inc_room_amenity (
    inc_room_amenity_id SERIAL PRIMARY KEY,
    inc_id INT REFERENCES incentive.inc_program(inc_id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    icon VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE
);



-- ============================================================
-- 4. CONVENTION ROOM EXTRA DATA
-- ============================================================

ALTER TABLE incentive.inc_convention_room
ADD COLUMN height_m NUMERIC(4,2),
ADD COLUMN capacity_theater INT,
ADD COLUMN capacity_cocktail INT;



-- ============================================================
-- 5. DINING EXTRA DATA
-- ============================================================

ALTER TABLE incentive.inc_dining
ADD COLUMN seating_capacity INT;




CREATE TABLE IF NOT EXISTS incentive.inc_room_amenity (
    inc_room_amenity_id SERIAL PRIMARY KEY,
    inc_id INT NOT NULL REFERENCES incentive.inc_program(inc_id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    icon VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW()
);



CREATE TABLE IF NOT EXISTS incentive.inc_convention_room_layout (
    inc_layout_id SERIAL PRIMARY KEY,
    inc_room_id INT NOT NULL REFERENCES incentive.inc_convention_room(inc_room_id) ON DELETE CASCADE,
    layout_type VARCHAR(50) NOT NULL,
    capacity INT,
    created_at TIMESTAMP DEFAULT NOW()
);


CREATE TABLE IF NOT EXISTS incentive.inc_media_type (
    media_type VARCHAR(50) PRIMARY KEY,
    description TEXT
);


ALTER TABLE incentive.inc_media
ADD CONSTRAINT fk_media_type
FOREIGN KEY (media_type)
REFERENCES incentive.inc_media_type(media_type);



CREATE TABLE IF NOT EXISTS incentive.inc_hotel_contact (
    inc_contact_id SERIAL PRIMARY KEY,
    inc_id INT NOT NULL REFERENCES incentive.inc_program(inc_id) ON DELETE CASCADE,
    address TEXT,
    postal_code VARCHAR(20),
    state_code VARCHAR(10),
    phone VARCHAR(50),
    email VARCHAR(150),
    website_url TEXT,
    google_maps_url TEXT,
    latitude NUMERIC(10,8),
    longitude NUMERIC(11,8),
    created_at TIMESTAMP DEFAULT NOW()
);
-- ============================================================
-- END OF MIGRATION
-- ============================================================