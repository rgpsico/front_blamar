<?php

/**
 * API para gerenciamento da tabela conteudo_internet.venues
 *
 * Descrição:
 * Controla a listagem, criação, atualização e exclusão de venues (locais de eventos),
 * incluindo informações sobre cidade, imagens, capacidade, preço e localização.
 *
 * Endpoints:
 * - GET  ?request=listar_venues&filtro_nome=Copacabana&filtro_ativo=true&filtro_data=2023-01-01&limit=100
 *         → Lista todos os venues filtrando por nome, status (ativo/inativo), data de cadastro e/ou cidade.
 *
 * - GET  ?request=buscar_venue&id=123
 *         → Busca os detalhes completos de um venue específico.
 *
 * - POST ?request=criar_venue
 *         → Cria um novo venue. 
 *           Body JSON:
 *           {
 *              "name": "Copacabana Palace",
 *              "description": "Hotel de luxo icônico no Rio.",
 *              "short_description": "Hotel 5 estrelas",
 *              "city": 151,
 *              "is_active": true,
 *              "capacity_max": 500,
 *              "latitude": -22.971,
 *              "longitude": -43.182,
 *              "images": [
 *                  {"image_url": "uploads/venues/foto1.jpg", "is_primary": true, "alt_text": "Fachada"}
 *              ]
 *           }
 *
 * - PUT  ?request=atualizar_venue&id=123
 *         → Atualiza campos específicos de um venue existente.
 *           Body JSON (exemplo parcial):
 *           {
 *              "name": "Novo nome do venue",
 *              "description": "Descrição atualizada",
 *              "is_active": false
 *           }
 *
 * - DELETE ?request=excluir_venue&id=123
 *           → Remove o venue e suas imagens relacionadas.
 *
 * - GET ?request=listar_cidades
 *         → Lista todas as cidades disponíveis para cadastro de venues.
 *
 * Métodos suportados:
 * - GET: listar_venues, buscar_venue, listar_cidades
 * - POST: criar_venue
 * - PUT: atualizar_venue
 * - DELETE: excluir_venue
 *
 * Tabelas relacionadas:
 * - conteudo_internet.venues
 * - conteudo_internet.venue_images
 * - sbd95.cidades
 *
 * Retornos:
 * - 200: Sucesso
 * - 201: Criado
 * - 400: Erro de parâmetro
 * - 404: Registro não encontrado
 * - 405: Método não permitido
 * - 500: Erro interno
 */

// ========================================
// 🔧 CONFIGURAÇÕES INICIAIS
// ========================================
date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../util/connection.php';

$BASE_URL_IMAGEM = "https://www.blumar.com.br/"; // Ajuste se necessário para venues

// Função padrão de resposta JSON
function response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Função auxiliar para montar array de imagens (adaptado para foto1-5)
function montarImagensVenue(&$row, $baseUrl)
{
    $row['imagens'] = [];
    $fotoCampos = ['foto1', 'foto2', 'foto3', 'foto4', 'foto5'];
    foreach ($fotoCampos as $index => $campo) {
        if (!empty($row[$campo])) {
            $row['imagens'][] = [
                'image_url' => $baseUrl . $row[$campo],
                'is_primary' => $index === 0,
                'alt_text' => $index === 0 ? 'Foto principal' : 'Foto secundária'
            ];
        }
    }
    // Limpa campos legados se quiser (opcional)
    // unset($row['foto1'], $row['foto2'], ...);
}

// Função para montar URLs de outros campos (ex: floor_plan_image)
function montarUrlsExtras(&$row, $baseUrl)
{
    if (!empty($row['floor_plan_image'])) {
        $row['floor_plan_image'] = $baseUrl . $row['floor_plan_image'];
    }
    if (!empty($row['product_link_url'])) {
        $row['product_link_url'] = $baseUrl . $row['product_link_url']; // Se aplicável
    }
}

// Helper para formatar strings (NULL se vazio)
function formatString($value)
{
    if ($value === null || $value === '') return 'NULL';
    return "'" . pg_escape_string($value) . "'";
}

// Helper para formatar inteiros (NULL se inválido)
function formatInt($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) return 'NULL';
    return (int)$value;
}

// Helper para formatar numéricos (float)
function formatNumeric($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) return 'NULL';
    return (float)$value;
}

// Helper para tratar booleanos
function tratarBoolean($valor)
{
    if ($valor === '' || $valor === null) {
        return 'NULL';
    }
    if (in_array(strtolower($valor), ['true', '1', 's', 'sim', 'yes', 't'])) {
        return 'TRUE';
    }
    return 'FALSE';
}

// Helper para escapar strings para SQL
function escapeStringSql($conn, $value)
{
    $raw = $value ?? '';
    return pg_escape_literal($conn, $raw);
}

// Helper para formatar text or null
function formatTextOrNull($conn, $str)
{
    $raw = $str ?? '';
    return (strlen($raw) === 0) ? 'NULL' : escapeStringSql($conn, $raw);
}

// Helper para formatar int or null
function formatIntOrNull($str)
{
    $raw = $str ?? '';
    return (strlen($raw) === 0) ? 'NULL' : (int)$raw;
}

// Helper para formatar numeric or null
function formatNumericOrNull($str)
{
    $raw = $str ?? '';
    return (strlen($raw) === 0) ? 'NULL' : (float)$raw;
}

$request = isset($_GET['request']) ? $_GET['request'] : null;
if (!$request) {
    response(["error" => "Parâmetro 'request' é obrigatório"], 400);
}

// Verificação de método HTTP
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? []; // Para POST/PUT

try {
    switch ($request) {

        // =========================================================
        // 🔹 ROTA 1: Listar venues (GET)
        // =========================================================
        case 'listar_venues':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : null;
            $filtro_ativo = isset($_GET['filtro_ativo']) ? $_GET['filtro_ativo'] : 'all';
            $filtro_data = isset($_GET['filtro_data']) ? $_GET['filtro_data'] : null;
            $cidade = isset($_GET['cidade']) ? $_GET['cidade'] : null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 200;

            $params = [];
            $idx = 1;
            $where = [];

            if ($filtro_nome) {
                $where[] = "v.nome ILIKE $" . $idx++;
                $params[] = "%{$filtro_nome}%";
            }
            if ($cidade) {
                $where[] = "c.nome_cid ILIKE $" . $idx++;
                $params[] = "%{$cidade}%";
            }
            if ($filtro_ativo && $filtro_ativo !== 'all') {
                $where[] = "v.ativo = $" . $idx++;
                $params[] = ($filtro_ativo === 'true' ? 't' : 'f');
            }
            if ($filtro_data) {
                $where[] = "DATE(v.data_cadastro) >= $" . $idx++;
                $params[] = $filtro_data;
            }

            $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";
            $params[] = $limit;

            $sql = "
                SELECT 
                    v.*, 
                    c.nome_cid AS city_name
                FROM conteudo_internet.venues v
                LEFT JOIN sbd95.cidades c 
                    ON c.cod_cid = v.fk_cod_cidade
                {$where_sql}
                ORDER BY v.nome
                LIMIT $" . $idx . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception(pg_last_error($conn));

            $venues = [];
            while ($row = pg_fetch_assoc($result)) {
                // Mapeamentos para frontend
                $row['name'] = $row['nome'];
                $row['description'] = $row['descritivo_pt'];
                $row['short_description'] = $row['especialidade'];
                $row['is_active'] = $row['ativo'] === 't'; // Boolean para consistência, mas 'ativo' permanece como string
                $row['city'] = $row['city_name'];

                // Montar imagens e extras
                montarImagensVenue($row, $BASE_URL_IMAGEM);
                montarUrlsExtras($row, $BASE_URL_IMAGEM);

                $venues[] = $row;
            }

            response($venues);
            break;

        // =========================================================
        // 🔹 ROTA 2: Buscar venue específico por ID (GET)
        // =========================================================
        case 'buscar_venue':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            $sql = "
                SELECT v.*, c.nome_cid AS city_name
                FROM conteudo_internet.venues v
                LEFT JOIN sbd95.cidades c
                       ON c.cod_cid = v.fk_cod_cidade
               WHERE v.cod_venues = $1
               LIMIT 1
            ";
            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Venue não encontrado"], 404);
            }

            $venue = pg_fetch_assoc($result);

            // Mapeamentos para frontend
            $venue['name'] = $venue['nome'];
            $venue['description'] = $venue['descritivo_pt'];
            $venue['short_description'] = $venue['especialidade'] ?? $venue['short_description_pt'];
            $venue['insight'] = $venue['insight_pt'];
            $venue['is_active'] = $venue['ativo'] === 't';
            $venue['city'] = $venue['city_name'] ?: $venue['city'];
            $venue['state'] = $venue['state'];
            $venue['country'] = $venue['country'];
            $venue['price_range'] = $venue['price_range'];
            $venue['capacity_min'] = (int)$venue['capacity_min'];
            $venue['capacity_max'] = (int)$venue['capacity_max'];
            $venue['address_line'] = $venue['address_line'];
            $venue['latitude'] = (float)$venue['latitude'];
            $venue['longitude'] = (float)$venue['longitude'];

            // Montar imagens e extras
            montarImagensVenue($venue, $BASE_URL_IMAGEM);
            montarUrlsExtras($venue, $BASE_URL_IMAGEM);

            response($venue);
            break;

        // =========================================================
        // 🔹 ROTA 3: Criar venue (POST)
        // =========================================================
        case 'criar_venue':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            if (empty($input)) response(["error" => "Dados do venue são obrigatórios no body JSON"], 400);

            // Campos principais (adaptados do schema legado)
            $campos = [
                'mneu_for',
                'nome',
                'especialidade',
                'descritivo_pt',
                'foto1',
                'foto2',
                'ativo',
                'fk_cod_cidade',
                'descritivo_en',
                'descritivo_esp',
                'short_description_pt',
                'short_description_en',
                'short_description_es',
                'insight_pt',
                'insight_en',
                'insight_es',
                'price_range',
                'capacity_min',
                'capacity_max',
                'address_line',
                'city',
                'state',
                'country',
                'latitude',
                'longitude',
                'foto3',
                'foto4',
                'foto5',
                'floor_plan_image',
                'product_link_url'
            ];

            $params = [];
            $placeholders = [];
            $idx = 1;

            foreach ($campos as $campo) {
                $valor = $input[$campo] ?? null;
                // Mapeamentos de entrada
                if ($campo === 'nome' && isset($input['name'])) $valor = $input['name'];
                if ($campo === 'descritivo_pt' && isset($input['description'])) $valor = $input['description'];
                if ($campo === 'especialidade' && isset($input['short_description'])) $valor = $input['short_description'];
                if ($campo === 'ativo' && isset($input['is_active'])) $valor = (bool)$input['is_active'];
                if ($campo === 'address_line' && isset($input['address'])) $valor = $input['address'];
                if ($campo === 'price_range' && isset($input['price_per_hour'])) $valor = $input['price_per_hour'];
                if ($campo === 'capacity_max' && isset($input['capacity'])) $valor = $input['capacity'];
                if ($campo === 'fk_cod_cidade' && isset($input['city'])) $valor = $input['city']; // Assume código

                // Casts
                if ($campo === 'ativo') {
                    $params[] = !empty($valor) ? 't' : 'f';
                } elseif (in_array($campo, ['capacity_min', 'capacity_max', 'fk_cod_cidade'])) {
                    $params[] = !empty($valor) ? (int)$valor : null;
                } elseif (in_array($campo, ['latitude', 'longitude'])) {
                    $params[] = !empty($valor) ? (float)$valor : null;
                } else {
                    $params[] = $valor;
                }
                $placeholders[] = '$' . $idx++;
            }

            $sql = "
                INSERT INTO conteudo_internet.venues (" . implode(',', $campos) . ")
                VALUES (" . implode(',', $placeholders) . ")
                RETURNING cod_venues
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Erro ao inserir venue: " . pg_last_error($conn));
            }

            $row = pg_fetch_assoc($result);
            $venue_id = $row['cod_venues'];

            // Inserção de imagens (priorize 'images' array, fallback foto1-5)
            if (!empty($input['images']) && is_array($input['images'])) {
                foreach ($input['images'] as $img) {
                    if (empty($img['image_url'])) continue;
                    $is_primary = isset($img['is_primary']) ? (bool)$img['is_primary'] : false;
                    $alt_text = $img['alt_text'] ?? null;
                    $sql_img = "
                        INSERT INTO conteudo_internet.venue_images (venue_id, image_url, is_primary, alt_text)
                        VALUES ($1, $2, $3, $4)
                    ";
                    pg_query_params($conn, $sql_img, [$venue_id, $img['image_url'], $is_primary, $alt_text]);
                }
            } else {
                // Fallback legado
                if (!empty($input['foto1'])) {
                    $sql_img = "
                        INSERT INTO conteudo_internet.venue_images (venue_id, image_url, is_primary, alt_text)
                        VALUES ($1, $2, TRUE, $3)
                    ";
                    pg_query_params($conn, $sql_img, [$venue_id, $input['foto1'], 'Foto principal']);
                }
                $fotoSec = ['foto2', 'foto3', 'foto4', 'foto5'];
                foreach ($fotoSec as $f) {
                    if (!empty($input[$f])) {
                        $sql_img = "
                            INSERT INTO conteudo_internet.venue_images (venue_id, image_url, is_primary, alt_text)
                            VALUES ($1, $2, FALSE, $3)
                        ";
                        pg_query_params($conn, $sql_img, [$venue_id, $input[$f], 'Foto secundária']);
                    }
                }
            }

            response([
                'success' => true,
                'message' => 'Venue inserido com sucesso!',
                'venue_id' => $venue_id
            ], 201);
            break;

        // =========================================================
        // 🔹 ROTA 4: Atualizar venue (PUT)
        // =========================================================
        case 'atualizar_venue':
            if ($method !== 'PUT') response(["error" => "Método não permitido. Use PUT."], 405);

            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            if (empty($input)) response(["error" => "Dados do venue são obrigatórios no body JSON"], 400);

            // Mapeamento de campos
            $mapeamentos = [
                'name' => 'nome',
                'description' => 'descritivo_pt',
                'short_description' => 'especialidade',
                'is_active' => 'ativo',
                'address' => 'address_line',
                'city_name' => 'city',
                'state' => 'state',
                'country' => 'country',
                'latitude' => 'latitude',
                'longitude' => 'longitude',
                'price' => 'price_range',
                'price_per_hour' => 'price_range',
                'capacity' => 'capacity_max',
                'foto1' => 'foto1',
                'foto2' => 'foto2',
                'foto3' => 'foto3',
                'foto4' => 'foto4',
                'foto5' => 'foto5',
                'floor_plan_image' => 'floor_plan_image',
                'product_link_url' => 'product_link_url',
                'mneu_for' => 'mneu_for',
                'descritivo_en' => 'descritivo_en',
                'descritivo_esp' => 'descritivo_esp',
                'short_description_pt' => 'short_description_pt',
                'insight' => 'insight_pt'
            ];

            $tipos_campos = [
                'ativo' => 'boolean',
                'latitude' => 'numeric',
                'longitude' => 'numeric',
                'capacity_max' => 'integer',
                'capacity_min' => 'integer',
                'fk_cod_cidade' => 'integer'
            ];

            $campos_invalidos = ['rating', 'review_count', 'rating_count', 'images', 'city'];

            $set = [];
            $params = [];
            $idx = 1;
            $usedFields = [];

            foreach ($input as $chave_original => $valor) {
                if (in_array($chave_original, ['id', 'cod_venues', 'city_name'])) continue;
                if (in_array($chave_original, $campos_invalidos)) continue;
                if (is_array($valor)) continue;

                $campo = $mapeamentos[$chave_original] ?? $chave_original;
                if (in_array($campo, $usedFields)) continue;
                $usedFields[] = $campo;

                $tipo = $tipos_campos[$campo] ?? null;
                if ($tipo === 'boolean') {
                    $valor_cast = (!empty($valor) && $valor !== 'false' && $valor !== '0') ? 't' : 'f';
                } elseif (in_array($tipo, ['numeric', 'integer'])) {
                    $valor_cast = ($valor !== '' && $valor !== null) ? ($tipo === 'integer' ? (int)$valor : (float)$valor) : null;
                } else {
                    $valor_cast = $valor;
                }

                $set[] = "$campo = $" . $idx++;
                $params[] = $valor_cast;
            }

            // Mapeamento especial para fk_cod_cidade
            if (!empty($input['city']) && is_numeric($input['city'])) {
                $campo = 'fk_cod_cidade';
                if (!in_array($campo, $usedFields)) {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = (int)$input['city'];
                    $usedFields[] = $campo;
                }
            }

            // Manipulação de imagens (limpa e re-insere se necessário)
            $imagesUpdated = false;
            if (!empty($input['images']) && is_array($input['images'])) {
                // Limpa imagens antigas
                pg_query_params($conn, "DELETE FROM conteudo_internet.venue_images WHERE venue_id = $1", [$id]);
                // Insere novas
                foreach ($input['images'] as $img) {
                    if (empty($img['image_url'])) continue;
                    $is_primary = isset($img['is_primary']) ? (bool)$img['is_primary'] : false;
                    $alt_text = $img['alt_text'] ?? null;
                    $sql_img = "
                        INSERT INTO conteudo_internet.venue_images (venue_id, image_url, is_primary, alt_text)
                        VALUES ($1, $2, $3, $4)
                    ";
                    pg_query_params($conn, $sql_img, [$id, $img['image_url'], $is_primary, $alt_text]);
                }
                $imagesUpdated = true;
            } elseif (isset($input['foto1']) || isset($input['foto2']) || isset($input['foto3']) || isset($input['foto4']) || isset($input['foto5'])) {
                // Fallback: atualiza foto1-5 diretamente no venue
                $fotoMapping = ['foto1', 'foto2', 'foto3', 'foto4', 'foto5'];
                foreach ($fotoMapping as $fotoField) {
                    if (isset($input[$fotoField]) && !in_array($fotoField, $usedFields)) {
                        $set[] = "$fotoField = $" . $idx++;
                        $params[] = $input[$fotoField];
                        $usedFields[] = $fotoField;
                    }
                }
                $imagesUpdated = true;
            }

            if (empty($set) && !$imagesUpdated) {
                response(["success" => false, "message" => "Nenhuma alteração realizada"], 200);
            }

            $params[] = $id;
            $sql = "
                UPDATE conteudo_internet.venues
                SET " . implode(', ', $set) . "
                WHERE cod_venues = $" . $idx . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Database update failed: " . pg_last_error($conn));
            }

            $affected_rows = pg_affected_rows($result);
            if ($affected_rows > 0 || $imagesUpdated) {
                response([
                    'success' => true,
                    'message' => 'Venue atualizado com sucesso! Linhas afetadas: ' . $affected_rows
                ]);
            } else {
                response([
                    'success' => false,
                    'message' => 'Nenhuma linha atualizada',
                    'affected_rows' => $affected_rows
                ], 200);
            }
            break;

        // =========================================================
        // 🔹 ROTA 5: Excluir venue (DELETE)
        // =========================================================
        case 'excluir_venue':
            if ($method !== 'DELETE') response(["error" => "Método não permitido. Use DELETE."], 405);

            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            // Deleta relacionados primeiro
            pg_query_params($conn, "DELETE FROM conteudo_internet.venue_images WHERE venue_id = $1", [$id]);
            pg_query_params($conn, "DELETE FROM conteudo_internet.venue_amenities WHERE venue_id = $1", [$id]);

            $sql = "DELETE FROM conteudo_internet.venues WHERE cod_venues = $1";
            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $affected_rows = pg_affected_rows($result);
            if ($affected_rows > 0) {
                response(["success" => true, "message" => "Venue excluído com sucesso"]);
            } else {
                response(["error" => "Venue não encontrado"], 404);
            }
            break;

        // =========================================================
        // 🔹 ROTA AUX: Listar cidades para select (GET)
        // =========================================================
        case 'listar_cidades':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $sql = "SELECT cod_cid AS id, nome_cid AS name FROM sbd95.cidades ORDER BY nome_cid ASC";
            $result = pg_query($conn, $sql);
            $cidades = [];
            if ($result) {
                while ($row = pg_fetch_assoc($result)) {
                    $cidades[] = $row;
                }
            }

            response($cidades);
            break;

        default:
            response(["error" => "Rota inválida"], 400);
    }
} catch (Exception $e) {
    error_log("Erro na API de venues: " . $e->getMessage());
    response(["error" => "Erro no servidor: " . $e->getMessage()], 500);
}
