<?php
/**
 * API para gerenciamento de venues (esquema incentive) - Versão corrigida 2025
 * Corrige erro: bind message supplies X parameters, but prepared statement requires Y
 */

date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../util/connection.php'; // sua conexão pg_connect

const BASE_URL_IMAGEM = ""; // deixe vazio para nao prefixar URLs ja completas

function montarUrlImagem($imageUrl) {
    $raw = trim((string)$imageUrl);
    if ($raw === '') {
        return '';
    }
    // Corrige casos legados com prefixo duplicado:
    // https://dominio/https://outro...
    // https://dominio//https://outro...
    if (preg_match('~^(https?://)~i', $raw)) {
        $secondHttpsPos = stripos($raw, 'https://', 8);
        $secondHttpPos = stripos($raw, 'http://', 8);
        $candidates = array_filter([$secondHttpsPos, $secondHttpPos], function ($v) {
            return $v !== false && $v > 0;
        });
        if (!empty($candidates)) {
            $cutPos = min($candidates);
            $raw = substr($raw, $cutPos);
        }
    }
    if (preg_match('~^https?://[^/]+/+((https?://).+)$~i', $raw, $m)) {
        $raw = $m[1];
    }
    if (preg_match('/^https?:\/\//i', $raw) || strpos($raw, '//') === 0) {
        return $raw;
    }
    if (BASE_URL_IMAGEM === '') {
        return $raw;
    }
    return rtrim(BASE_URL_IMAGEM, '/') . '/' . ltrim($raw, '/');
}

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    exit;
}

function error($message, $status = 400) {
    respond(["error" => $message], $status);
}

// Leitura de input
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$request = $_GET['request'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

if (!$request) {
    error("Parâmetro 'request' é obrigatório", 400);
}

$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : (isset($conn) ? $conn : null);
if (!$conn) {
    error("Conexao com banco indisponivel", 500);
}
$inTransaction = false;

try {
    switch ($request) {

        // LISTAR VENUES
        case 'listar_venues':
            if ($method !== 'GET') error("Use GET", 405);

            $nome   = trim($_GET['nome']   ?? '');
            $ativo  = $_GET['ativo']  ?? 'all';
            $cidade = trim($_GET['cidade'] ?? '');
            $limit  = max(1, min(500, (int)($_GET['limit'] ?? 100)));

            $where = [];
            $params = [];

            if ($nome !== '') {
                $where[] = "v.nome ILIKE $" . (count($params) + 1);
                $params[] = "%$nome%";
            }
            if ($cidade !== '') {
                $where[] = "l.city ILIKE $" . (count($params) + 1);
                $params[] = "%$cidade%";
            }
            if ($ativo !== 'all') {
                $where[] = "v.ativo = $" . (count($params) + 1);
                $params[] = filter_var($ativo, FILTER_VALIDATE_BOOLEAN) ? 'TRUE' : 'FALSE';
            }

            $whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

            // Sempre usamos placeholder para LIMIT
            $sql = "
                SELECT 
                    v.venue_id,
                    v.nome,
                    v.especialidade,
                    v.price_range,
                    v.capacity_min,
                    v.capacity_max,
                    v.ativo,
                    v.created_at,
                    l.address_line,
                    l.city,
                    l.state,
                    l.country,
                    l.latitude,
                    l.longitude
                FROM incentive.venues v
                LEFT JOIN incentive.venues_location l ON l.venue_id = v.venue_id
                $whereSql
                ORDER BY v.nome
                LIMIT $" . (count($params) + 1) . "
            ";

            $params[] = $limit;

            // debug (descomente se precisar)
            // error_log("LISTAR SQL: " . $sql);
            // error_log("LISTAR PARAMS: " . print_r($params, true));

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Erro na consulta: " . pg_last_error($conn));
            }

            $venues = [];
            $venue_ids = [];

            while ($row = pg_fetch_assoc($result)) {
                $id = (int)$row['venue_id'];
                $venue_ids[] = $id;

                $venues[$id] = [
                    'venue_id'       => $id,
                    'nome'           => $row['nome'],
                    'especialidade'  => $row['especialidade'],
                    'price_range'    => $row['price_range'],
                    'capacity_min'   => (int)$row['capacity_min'],
                    'capacity_max'   => (int)$row['capacity_max'],
                    'ativo'          => $row['ativo'] === 't',
                    'created_at'     => $row['created_at'],
                    'location'       => [
                        'address_line' => $row['address_line'],
                        'city'         => $row['city'],
                        'state'        => $row['state'],
                        'country'      => $row['country'],
                        'latitude'     => $row['latitude'] ? (float)$row['latitude'] : null,
                        'longitude'    => $row['longitude'] ? (float)$row['longitude'] : null,
                    ],
                    'translations'   => [],
                    'images'         => []
                ];
            }

            // Imagens (se houver resultados)
            if ($venue_ids) {
                $sqlImgs = "
                    SELECT venue_id, image_url, ordem, tipo 
                    FROM incentive.venues_images 
                    WHERE venue_id = ANY($1::bigint[])
                    ORDER BY venue_id, ordem
                ";
                $arrayLiteral = '{' . implode(',', $venue_ids) . '}';
                $resImgs = pg_query_params($conn, $sqlImgs, [$arrayLiteral]);

                while ($img = pg_fetch_assoc($resImgs)) {
                    $vid = (int)$img['venue_id'];
                    $venues[$vid]['images'][] = [
                        'url'   => montarUrlImagem($img['image_url']),
                        'ordem' => (int)$img['ordem'],
                        'tipo'  => $img['tipo'] ?: 'gallery',
                    ];
                }
            }

            respond(array_values($venues));
            break;


        // OBTER UM VENUE
        case 'obter_venue':
            if ($method !== 'GET') error("Use GET", 405);

            $id = (int)($_GET['id'] ?? 0);
            if ($id < 1) error("ID inválido", 400);

            $sql = "
                SELECT v.*, l.*
                FROM incentive.venues v
                LEFT JOIN incentive.venues_location l ON l.venue_id = v.venue_id
                WHERE v.venue_id = $1
            ";
            $res = pg_query_params($conn, $sql, [$id]);
            if (!$res || pg_num_rows($res) === 0) error("Venue não encontrado", 404);

            $venue = pg_fetch_assoc($res);

            // Traduções
            $sqlTrans = "SELECT * FROM incentive.venues_translations WHERE venue_id = $1";
            $transRes = pg_query_params($conn, $sqlTrans, [$id]);
            $translations = [];
            while ($t = pg_fetch_assoc($transRes)) {
                $translations[$t['language']] = [
                    'descritivo'        => $t['descritivo'],
                    'short_description' => $t['short_description'],
                    'insight'           => $t['insight'],
                ];
            }

            // Imagens
            $sqlImgs = "SELECT * FROM incentive.venues_images WHERE venue_id = $1 ORDER BY ordem";
            $imgRes = pg_query_params($conn, $sqlImgs, [$id]);
            $images = [];
            while ($img = pg_fetch_assoc($imgRes)) {
                $images[] = [
                    'url'   => montarUrlImagem($img['image_url']),
                    'ordem' => (int)$img['ordem'],
                    'tipo'  => $img['tipo'] ?: 'gallery',
                ];
            }

            $response = [
                'venue_id'      => (int)$venue['venue_id'],
                'nome'          => $venue['nome'],
                'especialidade' => $venue['especialidade'],
                'price_range'   => $venue['price_range'],
                'capacity_min'  => (int)$venue['capacity_min'],
                'capacity_max'  => (int)$venue['capacity_max'],
                'ativo'         => $venue['ativo'] === 't',
                'created_at'    => $venue['created_at'],
                'location'      => [
                    'address_line' => $venue['address_line'],
                    'city'         => $venue['city'],
                    'state'        => $venue['state'],
                    'country'      => $venue['country'],
                    'latitude'     => $venue['latitude'] ? (float)$venue['latitude'] : null,
                    'longitude'    => $venue['longitude'] ? (float)$venue['longitude'] : null,
                ],
                'translations'  => $translations,
                'images'        => $images,
            ];

            respond($response);
            break;


        // CRIAR VENUE (POST)
        case 'criar_venue':
            if ($method !== 'POST') error("Use POST", 405);

            if (empty($input['nome'])) error("Campo 'nome' é obrigatório");

            pg_query($conn, "BEGIN");
            $inTransaction = true;

            $sqlV = "
                INSERT INTO incentive.venues 
                (nome, especialidade, ativo, fk_cod_cidade, price_range, capacity_min, capacity_max, product_link_url)
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
                RETURNING venue_id
            ";
            $paramsV = [
                $input['nome'] ?? null,
                $input['especialidade'] ?? null,
                isset($input['ativo']) ? (filter_var($input['ativo'], FILTER_VALIDATE_BOOLEAN) ? 'TRUE' : 'FALSE') : 'TRUE',
                $input['fk_cod_cidade'] ?? null,
                $input['price_range'] ?? null,
                $input['capacity_min'] ?? null,
                $input['capacity_max'] ?? null,
                $input['product_link_url'] ?? null,
            ];

            $resV = pg_query_params($conn, $sqlV, $paramsV);
            if (!$resV) throw new Exception(pg_last_error($conn));
            $venue_id = (int) pg_fetch_result($resV, 0, 0);

            // Localização
            if (!empty($input['location']) && is_array($input['location'])) {
                $loc = $input['location'];
                $sqlL = "
                    INSERT INTO incentive.venues_location 
                    (venue_id, address_line, city, state, country, latitude, longitude)
                    VALUES ($1, $2, $3, $4, $5, $6, $7)
                ";
                pg_query_params($conn, $sqlL, [
                    $venue_id,
                    $loc['address_line'] ?? null,
                    $loc['city'] ?? null,
                    $loc['state'] ?? null,
                    $loc['country'] ?? null,
                    $loc['latitude'] ?? null,
                    $loc['longitude'] ?? null,
                ]);
            }

            // Traduções
            if (!empty($input['translations']) && is_array($input['translations'])) {
                $sqlT = "
                    INSERT INTO incentive.venues_translations 
                    (venue_id, language, descritivo, short_description, insight) 
                    VALUES ($1, $2, $3, $4, $5)
                ";
                foreach ($input['translations'] as $lang => $data) {
                    if (!in_array($lang, ['pt','en','es'])) continue;
                    pg_query_params($conn, $sqlT, [
                        $venue_id,
                        $lang,
                        $data['descritivo']        ?? null,
                        $data['short_description'] ?? null,
                        $data['insight']           ?? null,
                    ]);
                }
            }

            // Imagens
            if (!empty($input['images']) && is_array($input['images'])) {
                $sqlI = "
                    INSERT INTO incentive.venues_images 
                    (venue_id, image_url, ordem, tipo) 
                    VALUES ($1, $2, $3, $4)
                ";
                $ordem = 1;
                foreach ($input['images'] as $img) {
                    if (empty($img['image_url'])) continue;
                    $cleanImageUrl = montarUrlImagem($img['image_url']);
                    pg_query_params($conn, $sqlI, [
                        $venue_id,
                        $cleanImageUrl,
                        $img['ordem'] ?? $ordem++,
                        $img['tipo']  ?? 'gallery',
                    ]);
                }
            }

            pg_query($conn, "COMMIT");
            $inTransaction = false;

            respond([
                "success" => true,
                "message" => "Venue criado com sucesso",
                "venue_id" => $venue_id
            ], 201);
            break;


        // ATUALIZAR VENUE (PUT ou PATCH)
        case 'atualizar_venue':
            if (!in_array($method, ['PUT', 'PATCH'])) error("Use PUT ou PATCH", 405);

            $id = (int)($_GET['id'] ?? 0);
            if ($id < 1) error("ID inválido", 400);

            pg_query($conn, "BEGIN");
            $inTransaction = true;

            $updated = false;

            // Campos principais
            $allowed = ['nome','especialidade','ativo','fk_cod_cidade','price_range','capacity_min','capacity_max','product_link_url'];
            $sets = [];
            $params = [];
            $idx = 1;

            foreach ($allowed as $field) {
                if (array_key_exists($field, $input)) {
                    $sets[] = "$field = $" . $idx++;
                    $value = $input[$field];
                    if ($field === 'ativo') {
                        $params[] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'TRUE' : 'FALSE';
                    } else {
                        $params[] = $value;
                    }
                    $updated = true;
                }
            }

            if ($sets) {
                $params[] = $id;
                $sql = "UPDATE incentive.venues SET " . implode(", ", $sets) . " WHERE venue_id = $" . $idx;
                if (!pg_query_params($conn, $sql, $params)) {
                    throw new Exception(pg_last_error($conn));
                }
            }

            // Localização (substitui)
            if (!empty($input['location']) && is_array($input['location'])) {
                $loc = $input['location'];
                pg_query_params($conn, "DELETE FROM incentive.venues_location WHERE venue_id = $1", [$id]);

                $sqlL = "
                    INSERT INTO incentive.venues_location 
                    (venue_id, address_line, city, state, country, latitude, longitude)
                    VALUES ($1, $2, $3, $4, $5, $6, $7)
                ";
                pg_query_params($conn, $sqlL, [
                    $id,
                    $loc['address_line'] ?? null,
                    $loc['city'] ?? null,
                    $loc['state'] ?? null,
                    $loc['country'] ?? null,
                    $loc['latitude'] ?? null,
                    $loc['longitude'] ?? null,
                ]);
                $updated = true;
            }

            // Traduções (substitui)
            if (!empty($input['translations']) && is_array($input['translations'])) {
                pg_query_params($conn, "DELETE FROM incentive.venues_translations WHERE venue_id = $1", [$id]);

                $sqlT = "
                    INSERT INTO incentive.venues_translations 
                    (venue_id, language, descritivo, short_description, insight) 
                    VALUES ($1, $2, $3, $4, $5)
                ";
                foreach ($input['translations'] as $lang => $data) {
                    if (!in_array($lang, ['pt','en','es'])) continue;
                    pg_query_params($conn, $sqlT, [
                        $id,
                        $lang,
                        $data['descritivo']        ?? null,
                        $data['short_description'] ?? null,
                        $data['insight']           ?? null,
                    ]);
                }
                $updated = true;
            }

            // Imagens (substitui)
            if (!empty($input['images']) && is_array($input['images'])) {
                pg_query_params($conn, "DELETE FROM incentive.venues_images WHERE venue_id = $1", [$id]);

                $sqlI = "
                    INSERT INTO incentive.venues_images 
                    (venue_id, image_url, ordem, tipo) 
                    VALUES ($1, $2, $3, $4)
                ";
                $ordem = 1;
                foreach ($input['images'] as $img) {
                    if (empty($img['image_url'])) continue;
                    $cleanImageUrl = montarUrlImagem($img['image_url']);
                    pg_query_params($conn, $sqlI, [
                        $id,
                        $cleanImageUrl,
                        $img['ordem'] ?? $ordem++,
                        $img['tipo']  ?? 'gallery',
                    ]);
                }
                $updated = true;
            }

            pg_query($conn, "COMMIT");
            $inTransaction = false;

            if (!$updated) {
                respond(["message" => "Nenhuma alteração enviada", "updated" => false], 200);
            }

            respond([
                "success" => true,
                "message" => "Venue atualizado com sucesso",
                "venue_id" => $id
            ], 200);
            break;


        // EXCLUIR VENUE
        case 'excluir_venue':
            if ($method !== 'DELETE') error("Use DELETE", 405);

            $id = (int)($_GET['id'] ?? 0);
            if ($id < 1) error("ID inválido", 400);

            $result = pg_query_params($conn, "DELETE FROM incentive.venues WHERE venue_id = $1", [$id]);
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $affected = pg_affected_rows($result);
            if ($affected > 0) {
                respond(["success" => true, "message" => "Venue excluído"]);
            } else {
                error("Venue não encontrado", 404);
            }
            break;


        default:
            error("Rota desconhecida: $request", 400);
    }
} catch (Exception $e) {
    if ($inTransaction) {
        pg_query($conn, "ROLLBACK");
    }
    error_log("ERRO API VENUES: " . $e->getMessage());
    error("Erro interno do servidor: " . $e->getMessage(), 500);
}
