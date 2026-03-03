-- Schema: incentive (recomendo criar esse schema)
CREATE SCHEMA IF NOT EXISTS incentive;
SET search_path TO incentive, public;

-- ══════════════════════════════════════════════════════════════════════════════
-- 1. Programa principal (incentivo)
-- ══════════════════════════════════════════════════════════════════════════════
CREATE TABLE inc_program (
    inc_id              BIGSERIAL           PRIMARY KEY,
    inc_name            TEXT                NOT NULL,
    inc_description     TEXT,
    hotel_ref_id        BIGINT,                             -- pode vir de outra tabela/sistema
    hotel_name_snapshot TEXT,
    city_name           TEXT,
    country_code        CHAR(2),                            -- ex: BR, US, PT
    inc_status          TEXT                DEFAULT 'active',
    inc_is_active       BOOLEAN             DEFAULT TRUE,
    star_rating         SMALLINT,                           -- 3,4,5...
    total_rooms         INTEGER,
    floor_plan_url      TEXT,
    created_at          TIMESTAMPTZ         DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         DEFAULT NOW()
);

-- ══════════════════════════════════════════════════════════════════════════════
-- 2. Contato / localização do hotel
-- ══════════════════════════════════════════════════════════════════════════════
CREATE TABLE inc_hotel_contact (
    inc_contact_id      BIGSERIAL           PRIMARY KEY,
    inc_id              BIGINT              NOT NULL UNIQUE
        REFERENCES inc_program(inc_id) ON DELETE CASCADE,
    
    address             TEXT,
    postal_code         VARCHAR(20),
    state_code          VARCHAR(10),                        -- UF / estado
    phone               TEXT,
    email               TEXT,
    website_url         TEXT,
    google_maps_url     TEXT,
    latitude            NUMERIC(10,7),
    longitude           NUMERIC(10,7),
    
    created_at          TIMESTAMPTZ         DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         DEFAULT NOW()
);

-- ══════════════════════════════════════════════════════════════════════════════
-- 3. Mídias (fotos, vídeos, etc.)
-- ══════════════════════════════════════════════════════════════════════════════
CREATE TABLE inc_media (
    inc_media_id        BIGSERIAL           PRIMARY KEY,
    inc_id              BIGINT              NOT NULL
        REFERENCES inc_program(inc_id) ON DELETE CASCADE,
    
    media_type          TEXT                NOT NULL,       -- image, video, 360, etc
    media_url           TEXT                NOT NULL,
    position            INTEGER             DEFAULT 999,
    is_active           BOOLEAN             DEFAULT TRUE,
    
    created_at          TIMESTAMPTZ         DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         DEFAULT NOW()
);

-- ══════════════════════════════════════════════════════════════════════════════
-- 4. Categorias de quartos
-- ══════════════════════════════════════════════════════════════════════════════
CREATE TABLE inc_room_category (
    inc_room_id         BIGSERIAL           PRIMARY KEY,
    inc_id              BIGINT              NOT NULL
        REFERENCES inc_program(inc_id) ON DELETE CASCADE,
    
    room_name           TEXT                NOT NULL,
    quantity            INTEGER,
    notes               TEXT,
    position            INTEGER             DEFAULT 999,
    is_active           BOOLEAN             DEFAULT TRUE,
    
    -- campos extras que aparecem no buscar_incentive
    area_m2             NUMERIC(6,2),
    view_type           TEXT,                               -- vista mar, jardim...
    room_type           TEXT,                               -- standard, deluxe, suite...
    
    created_at          TIMESTAMPTZ         DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         DEFAULT NOW()
);

-- ══════════════════════════════════════════════════════════════════════════════
-- 5. Restaurantes / Dining
-- ══════════════════════════════════════════════════════════════════════════════
CREATE TABLE inc_dining (
    inc_dining_id       BIGSERIAL           PRIMARY KEY,
    inc_id              BIGINT              NOT NULL
        REFERENCES inc_program(inc_id) ON DELETE CASCADE,
    
    name                TEXT                NOT NULL,
    description         TEXT,
    cuisine             TEXT,
    capacity            INTEGER,
    seating_capacity    INTEGER,                            -- extra (aparece no buscar)
    schedule            TEXT,
    is_michelin         BOOLEAN             DEFAULT FALSE,
    can_be_private      BOOLEAN             DEFAULT FALSE,
    image_url           TEXT,
    position            INTEGER             DEFAULT 999,
    is_active           BOOLEAN             DEFAULT TRUE,
    
    created_at          TIMESTAMPTZ         DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         DEFAULT NOW()
);

-- ══════════════════════════════════════════════════════════════════════════════
-- 6. Facilities / Comodidades
-- ══════════════════════════════════════════════════════════════════════════════
CREATE TABLE inc_facility (
    inc_facility_id     BIGSERIAL           PRIMARY KEY,
    inc_id              BIGINT              NOT NULL
        REFERENCES inc_program(inc_id) ON DELETE CASCADE,
    
    name                TEXT                NOT NULL,
    icon                TEXT,                               -- nome do ícone ou path
    is_active           BOOLEAN             DEFAULT TRUE,
    
    created_at          TIMESTAMPTZ         DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         DEFAULT NOW()
);

-- ══════════════════════════════════════════════════════════════════════════════
-- 7. Convenções / Espaços para eventos
-- ══════════════════════════════════════════════════════════════════════════════
CREATE TABLE inc_convention (
    inc_convention_id   BIGSERIAL           PRIMARY KEY,
    inc_id              BIGINT              NOT NULL UNIQUE
        REFERENCES inc_program(inc_id) ON DELETE CASCADE,
    
    description         TEXT,
    total_rooms         INTEGER,
    has_360             BOOLEAN             DEFAULT FALSE,
    
    created_at          TIMESTAMPTZ         DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         DEFAULT NOW()
);

-- ══════════════════════════════════════════════════════════════════════════════
-- 8. Salas dentro do espaço de convenção
-- ══════════════════════════════════════════════════════════════════════════════
CREATE TABLE inc_convention_room (
    inc_room_id             BIGSERIAL       PRIMARY KEY,
    inc_convention_id       BIGINT          NOT NULL
        REFERENCES inc_convention(inc_convention_id) ON DELETE CASCADE,
    
    name                    TEXT            NOT NULL,
    area_m2                 NUMERIC(7,2),
    height_m                NUMERIC(5,2),
    
    capacity_theater        INTEGER,
    capacity_cocktail       INTEGER,
    capacity_auditorium     INTEGER,
    capacity_banquet        INTEGER,
    capacity_classroom      INTEGER,
    capacity_u_shape        INTEGER,
    
    notes                   TEXT,
    imagem_planta_hotel     TEXT,                           -- url da planta baixa
    
    created_at              TIMESTAMPTZ     DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     DEFAULT NOW()
);

-- ══════════════════════════════════════════════════════════════════════════════
-- 9. Notas / Observações (multilíngue)
-- ══════════════════════════════════════════════════════════════════════════════
CREATE TABLE inc_note (
    inc_note_id         BIGSERIAL           PRIMARY KEY,
    inc_id              BIGINT              NOT NULL
        REFERENCES inc_program(inc_id) ON DELETE CASCADE,
    
    language            CHAR(2)             NOT NULL,       -- pt, en, es...
    note                TEXT                NOT NULL,
    
    created_at          TIMESTAMPTZ         DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         DEFAULT NOW()
);

-- Índices úteis (melhora performance em filtros e listagens)
CREATE INDEX idx_inc_program_name        ON inc_program(inc_name);
CREATE INDEX idx_inc_program_status      ON inc_program(inc_status);
CREATE INDEX idx_inc_program_country     ON inc_program(country_code);
CREATE INDEX idx_inc_program_city        ON inc_program(city_name);
CREATE INDEX idx_inc_program_active      ON inc_program(inc_is_active);

CREATE INDEX idx_inc_media_incid         ON inc_media(inc_id);
CREATE INDEX idx_inc_room_cat_incid      ON inc_room_category(inc_id);
CREATE INDEX idx_inc_dining_incid        ON inc_dining(inc_id);
CREATE INDEX idx_inc_facility_incid      ON inc_facility(inc_id);
CREATE INDEX idx_inc_note_incid          ON inc_note(inc_id);