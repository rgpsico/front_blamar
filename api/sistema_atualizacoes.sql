CREATE TABLE sistema_atualizacoes (
    id BIGSERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    modulo VARCHAR(100), -- ex: Banco de Vídeo, Incentive, Auth
    tipo VARCHAR(50), -- feature, fix, melhoria, refatoracao
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT NOW()
);
