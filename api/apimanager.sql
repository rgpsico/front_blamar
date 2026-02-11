ALTER TABLE sbd95.api_admins
ADD COLUMN cod_sis VARCHAR(16);

ALTER TABLE sbd95.api_admins
ADD CONSTRAINT uq_api_admins_cod_sis UNIQUE (cod_sis);

CREATE INDEX idx_api_admins_cod_sis
ON sbd95.api_admins (cod_sis);
