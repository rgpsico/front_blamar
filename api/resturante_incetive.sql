CREATE TABLE incentive.restaurants (
  id                SERIAL PRIMARY KEY,
  
  name              VARCHAR(255) NOT NULL,
  slug              VARCHAR(255) UNIQUE NOT NULL,

  city_code         VARCHAR(3) NOT NULL, -- sbd95.cidades.cid (SEM FK)

  short_description TEXT,
  description       TEXT,

  capacity          INTEGER,
  has_private_area  BOOLEAN DEFAULT FALSE,
  has_view          BOOLEAN DEFAULT FALSE,

  address           TEXT,
  latitude          NUMERIC(10,8),
  longitude         NUMERIC(11,8),

  is_active         BOOLEAN DEFAULT TRUE,

  created_at        TIMESTAMP DEFAULT NOW(),
  updated_at        TIMESTAMP DEFAULT NOW()
);

CREATE TABLE incentive.restaurant_images (
  id             SERIAL PRIMARY KEY,
  restaurant_id  INTEGER NOT NULL, -- lógico
  image_url      TEXT NOT NULL,
  is_cover       BOOLEAN DEFAULT FALSE,
  position       INTEGER DEFAULT 0,

  created_at     TIMESTAMP DEFAULT NOW()
);


CREATE TABLE incentive.restaurant_menus (
  id             SERIAL PRIMARY KEY,
  restaurant_id  INTEGER NOT NULL,

  title          VARCHAR(100),

  created_at     TIMESTAMP DEFAULT NOW()
);



CREATE TABLE incentive.restaurant_menu_sections (
  id          SERIAL PRIMARY KEY,
  menu_id     INTEGER NOT NULL,

  name        VARCHAR(100) NOT NULL,
  position    INTEGER DEFAULT 0
);



CREATE TABLE incentive.restaurant_menu_items (
  id          SERIAL PRIMARY KEY,
  section_id  INTEGER NOT NULL,

  name        VARCHAR(255) NOT NULL,
  description TEXT,
  position    INTEGER DEFAULT 0
);




CREATE TABLE incentive.restaurant_tags (
  id      SERIAL PRIMARY KEY,
  name    VARCHAR(100) NOT NULL,
  icon    VARCHAR(100)
);



CREATE TABLE incentive.restaurant_tag_relations (
  restaurant_id INTEGER,
  tag_id        INTEGER,

  PRIMARY KEY (restaurant_id, tag_id)
);




CREATE TABLE incentive.restaurant_notes (
  id             SERIAL PRIMARY KEY,
  restaurant_id  INTEGER,

  note           TEXT,
  created_at     TIMESTAMP DEFAULT NOW()
);



