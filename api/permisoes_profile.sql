CREATE SCHEMA IF NOT EXISTS auth;
CREATE TABLE auth.auth_profiles (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  description VARCHAR(255),
  created_at TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE TABLE auth.auth_permissions (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  description VARCHAR(255),
  created_at TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE TABLE auth.auth_profile_permissions (
  profile_id BIGINT NOT NULL,
  permission_id BIGINT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT NOW(),

  PRIMARY KEY (profile_id, permission_id),

  CONSTRAINT fk_profile
    FOREIGN KEY (profile_id)
    REFERENCES auth.auth_profiles(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_permission
    FOREIGN KEY (permission_id)
    REFERENCES auth.auth_permissions(id)
    ON DELETE CASCADE
);
CREATE INDEX idx_profile_permissions_profile 
  ON auth.auth_profile_permissions (profile_id);

CREATE INDEX idx_profile_permissions_permission 
  ON auth.auth_profile_permissions (permission_id);
