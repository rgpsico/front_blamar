CREATE TABLE incentive.venues (
    venue_id BIGSERIAL PRIMARY KEY,
    nome TEXT,
    especialidade TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    fk_cod_cidade INT,
    price_range TEXT,
    capacity_min INT,
    capacity_max INT,
    product_link_url TEXT,
    created_at TIMESTAMP DEFAULT now()
);



CREATE TABLE incentive.venues_translations (
    id BIGSERIAL PRIMARY KEY,
    venue_id BIGINT REFERENCES incentive.venues(venue_id) ON DELETE CASCADE,
    language VARCHAR(5) NOT NULL,
    descritivo TEXT,
    short_description TEXT,
    insight TEXT
);



CREATE TABLE incentive.venues_images (
    image_id BIGSERIAL PRIMARY KEY,
    venue_id BIGINT REFERENCES incentive.venues(venue_id) ON DELETE CASCADE,
    image_url TEXT,
    ordem INT,
    tipo VARCHAR(50)
);


CREATE TABLE incentive.venues_location (
    location_id BIGSERIAL PRIMARY KEY,
    venue_id BIGINT REFERENCES incentive.venues(venue_id) ON DELETE CASCADE,
    address_line TEXT,
    city TEXT,
    state TEXT,
    country TEXT,
    latitude NUMERIC,
    longitude NUMERIC
);


ALTER TABLE incentive.venues_location
ADD COLUMN google_maps_url TEXT;

alter table incentive.venues
add column fk_cod_cidade int;
