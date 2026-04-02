<?php
/**
 * API RESTful COMPLETA - incentive.entertainments (CRUD)
 * Baseada na estrutura da API de restaurantes (conteudo_internet.restaurante)
 * Compatível com PHP 7.2+
 *
 * Endpoints principais:
 *   GET    ?request=listar_entertainment                    → Lista com filtros e paginação
 *   GET    ?request=buscar_entertainment&id=123             → Detalhes de um entertainment
 *   POST   ?request=criar_entertainment                     → Cria novo
 *   PUT    ?request=atualizar_entertainment&id=XXX          → Atualiza (parcial)
 *   DELETE ?request=excluir_entertainment&id=XXX            → Exclui
 *
 * Endpoints auxiliares:
 *   GET    ?request=listar_categorias                       → Categorias (music, show, etc)
 *   GET    ?request=listar_locations                        → Locations filtradas por city_id
 *   GET    ?request=listar_tipos                            → Tipos distintos cadastrados
 *
 * Autenticação (POST/PUT/DELETE):
 *   Header: Authorization: Bearer <token>
 *
 * Imagens: salva URL completa em entertainment_images
 */

ini_set('display_errors', 1);
ini_set('log_errors', 1);

date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once '../util/connection.php';
require_once 'middleware.php'; // Contém validarToken()

// ============================================================
// Funções auxiliares
// ============================================================

function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getParam($name, $default = null) {
    return isset($_GET[$name]) ? $_GET[$name] : $default;
}

function getStringParam($name, $default = null) {
    $v = getParam($name, $default);
    return ($v !== null) ? trim($v) : null;
}

function getIntParam($name, $default = null) {
    $v = getParam($name, $default);
    return is_numeric($v) ? (int)$v : $default;
}

function formatString($val) {
    return ($val === '' || $val === null) ? null : trim($val);
}

function formatInt($val) {
    return is_numeric($val) ? (int)$val : null;
}

function formatBool($val) {
    if ($val === null || $val === '') return null;
    return filter_var($val, FILTER_VALIDATE_BOOLEAN) ? true : false;
}

function getBearerToken() {
    $headers = getallheaders();
    $auth = $headers['authorization'] ?? $headers['Authorization'] ?? '';
    if (strpos($auth, 'Bearer ') !== 0) return null;
    return trim(substr($auth, 7));
}

function requireAuth($conn, &$cod_sis, &$user_data) {
    $token = getBearerToken();
    if (!$token) {
        response(["error" => "Token Bearer obrigatório"], 401);
    }
    $cod_sis = null;
    $user_data = null;
    if (!validarToken($conn, $cod_sis, $token, $user_data)) {
        response(["error" => "Token inválido ou expirado"], 401);
    }
}

/**
 * Monta o array de imagens de um entertainment
 */
function buscarImagens($conn, $entertainment_id) {
    $sql = "
        SELECT id, url, caption, position
        FROM incentive.entertainment_images
        WHERE entertainment_id = $1
        ORDER BY position ASC, id ASC
    ";
    $res = pg_query_params($conn, $sql, [$entertainment_id]);
    if (!$res) return [];

    $imagens = [];
    while ($row = pg_fetch_assoc($res)) {
        $imagens[] = [
            'id'       => (int)$row['id'],
            'url'      => $row['url'],
            'caption'  => $row['caption'],
            'position' => (int)$row['position'],
        ];
    }
    return $imagens;
}

/**
 * Formata uma row da tabela entertainments em array de resposta
 */
function formatarEntertainment($row, $conn = null, $com_imagens = false) {
    $item = [
        'id'              => (int)$row['id'],
        'title'           => $row['title'] ?? '',
        'slug'            => $row['slug'] ?? '',
        'category_id'     => (int)($row['category_id'] ?? 0),
        'category_slug'   => $row['category_slug'] ?? null,    // via JOIN
        'city_id'         => (int)($row['city_id'] ?? 0),
        'cidade_nome'     => $row['cidade_nome'] ?? null,      // via JOIN
        'location_id'     => $row['location_id'] ? (int)$row['location_id'] : null,
        'location_name'   => $row['location_name'] ?? null,    // via JOIN
        'type'            => $row['type'] ?? '',
        'short_desc'      => $row['short_desc'] ?? '',
        'description'     => $row['description'] ?? '',
        'cover_image_url' => $row['cover_image_url'] ?? null,
        'price_range'     => $row['price_range'] ?? null,
        'personal_note'   => $row['personal_note'] ?? null,
        'is_active'       => ($row['is_active'] ?? 'f') === 't' || $row['is_active'] === true,
        'created_at'      => $row['created_at'] ?? null,
        'updated_at'      => $row['updated_at'] ?? null,
    ];

    if ($com_imagens && $conn) {
        $item['images'] = buscarImagens($conn, $item['id']);
    }

    return $item;
}

// ============================================================
// INÍCIO DO PROCESSAMENTO
// ============================================================

$request = getParam('request');
if (!$request) {
    response(["error" => "Parâmetro 'request' é obrigatório"], 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?: [];

if ($method === 'OPTIONS') {
    response([], 204);
}

try {
    switch ($request) {

        // ============================================================
        // LISTAR ENTERTAINMENTS (com filtros + paginação)
        // ============================================================
        case 'listar_entertainment':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            // Filtros
            $filtro_title    = getStringParam('filtro_title');
            $filtro_city     = getIntParam('filtro_city');
            $filtro_category = getIntParam('filtro_category');
            $filtro_type     = getStringParam('filtro_type');
            $filtro_location = getIntParam('filtro_location');
            $filtro_ativo    = getParam('filtro_ativo', 'true'); // default: só ativos

            // Paginação
            $page     = max(1, getIntParam('page', 1));
            $per_page = max(1, min(100, getIntParam('per_page', 30)));
            $offset   = ($page - 1) * $per_page;

            $where  = [];
            $params = [];
            $idx    = 1;

            if ($filtro_title) {
                $where[] = "e.title ILIKE \$$idx";
                $params[] = "%$filtro_title%";
                $idx++;
            }
            if ($filtro_city) {
                $where[] = "e.city_id = \$$idx";
                $params[] = $filtro_city;
                $idx++;
            }
            if ($filtro_category) {
                $where[] = "e.category_id = \$$idx";
                $params[] = $filtro_category;
                $idx++;
            }
            if ($filtro_type) {
                $where[] = "LOWER(e.type) = LOWER(\$$idx)";
                $params[] = $filtro_type;
                $idx++;
            }
            if ($filtro_location) {
                $where[] = "e.location_id = \$$idx";
                $params[] = $filtro_location;
                $idx++;
            }
            if ($filtro_ativo !== 'all') {
                $ativo = ($filtro_ativo === 'false' || $filtro_ativo === '0') ? 'false' : 'true';
                $where[] = "e.is_active = $ativo";
            }

            $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

            // Contagem total
            $sql_count = "
                SELECT COUNT(*) AS total
                FROM incentive.entertainments e
                $where_sql
            ";
            $res_count = pg_query_params($conn, $sql_count, $params);
            if (!$res_count) throw new Exception(pg_last_error($conn));
            $total = (int) pg_fetch_result($res_count, 0, 'total');

            // Lista com JOINs para nomes
            $params[] = $per_page;
            $params[] = $offset;

            $sql = "
                SELECT
                    e.*,
                    ec.slug            AS category_slug,
                    l.name             AS location_name,
                    ct.nome_pt         AS cidade_nome
                FROM incentive.entertainments e
                LEFT JOIN incentive.entertainment_categories ec ON ec.id = e.category_id
                LEFT JOIN incentive.locations l                  ON l.id  = e.location_id
                LEFT JOIN tarifario.cidade_tpo ct                ON ct.cidade_cod = e.city_id
                $where_sql
                ORDER BY e.title ASC
                LIMIT \$$idx
                OFFSET \$" . ($idx + 1) . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception(pg_last_error($conn));

            $items = [];
            while ($row = pg_fetch_assoc($result)) {
                $items[] = formatarEntertainment($row);
            }

            response([
                'data' => $items,
                'pagination' => [
                    'total'        => $total,
                    'per_page'     => $per_page,
                    'current_page' => $page,
                    'last_page'    => (int) ceil($total / max(1, $per_page)),
                ]
            ]);
            break;


        // ============================================================
        // BUSCAR UM ENTERTAINMENT (com imagens)
        // ============================================================
        case 'buscar_entertainment':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatório"], 400);

            $sql = "
                SELECT
                    e.*,
                    ec.slug            AS category_slug,
                    l.name             AS location_name,
                    ct.nome_pt         AS cidade_nome
                FROM incentive.entertainments e
                LEFT JOIN incentive.entertainment_categories ec ON ec.id = e.category_id
                LEFT JOIN incentive.locations l                  ON l.id  = e.location_id
                LEFT JOIN tarifario.cidade_tpo ct                ON ct.cidade_cod = e.city_id
                WHERE e.id = $1
                LIMIT 1
            ";

            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Entertainment não encontrado"], 404);
            }

            $row  = pg_fetch_assoc($result);
            $item = formatarEntertainment($row, $conn, true); // true = incluir imagens

            response($item);
            break;


        // ============================================================
        // CRIAR ENTERTAINMENT
        // ============================================================
        case 'criar_entertainment':
            if ($method !== 'POST') response(["error" => "Use POST"], 405);

            $cod_sis = $user_data = null;
            requireAuth($conn, $cod_sis, $user_data);

            if (empty($input)) response(["error" => "Body JSON obrigatório"], 400);

            pg_query($conn, "BEGIN");

            try {
                // Campos obrigatórios
                $title       = formatString($input['title'] ?? '');
                $category_id = formatInt($input['category_id'] ?? null);
                $city_id     = formatInt($input['city_id'] ?? null);
                $type        = formatString($input['type'] ?? '');

                if (!$title)       throw new Exception("Campo 'title' é obrigatório");
                if (!$category_id) throw new Exception("Campo 'category_id' é obrigatório");
                if (!$city_id)     throw new Exception("Campo 'city_id' é obrigatório");
                if (!$type)        throw new Exception("Campo 'type' é obrigatório");

                // Gera slug se não informado
                $slug = formatString($input['slug'] ?? '');
                if (!$slug) {
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
                    $slug = trim($slug, '-');
                    // Garante unicidade
                    $chk = pg_query_params($conn, "SELECT COUNT(*) FROM incentive.entertainments WHERE slug LIKE $1", [$slug . '%']);
                    $cnt = (int) pg_fetch_result($chk, 0, 0);
                    if ($cnt > 0) $slug .= '-' . ($cnt + 1);
                }

                $campos = [
                    'title'           => $title,
                    'slug'            => $slug,
                    'category_id'     => $category_id,
                    'city_id'         => $city_id,
                    'location_id'     => formatInt($input['location_id'] ?? null),
                    'type'            => $type,
                    'short_desc'      => formatString($input['short_desc'] ?? ''),
                    'description'     => formatString($input['description'] ?? ''),
                    'cover_image_url' => formatString($input['cover_image_url'] ?? ''),
                    'price_range'     => formatString($input['price_range'] ?? ''),
                    'personal_note'   => formatString($input['personal_note'] ?? ''),
                    'is_active'       => formatBool($input['is_active'] ?? true),
                ];

                // Remove nulls
                $cols = $placeholders = $values = [];
                $idx = 1;
                foreach ($campos as $col => $val) {
                    if ($val !== null) {
                        $cols[]         = $col;
                        $placeholders[] = '$' . $idx++;
                        $values[]       = ($val === true) ? 'true' : (($val === false) ? 'false' : $val);
                    }
                }

                $sql = "
                    INSERT INTO incentive.entertainments (" . implode(', ', $cols) . ")
                    VALUES (" . implode(', ', $placeholders) . ")
                    RETURNING id
                ";

                $res = pg_query_params($conn, $sql, $values);
                if (!$res) throw new Exception(pg_last_error($conn));

                $new_id = (int) pg_fetch_result($res, 0, 0);

                // Salva imagens extras (array de {url, caption, position})
                if (!empty($input['images']) && is_array($input['images'])) {
                    foreach ($input['images'] as $pos => $img) {
                        $img_url     = formatString($img['url'] ?? '');
                        $img_caption = formatString($img['caption'] ?? '');
                        $img_pos     = formatInt($img['position'] ?? $pos);
                        if (!$img_url) continue;
                        pg_query_params($conn,
                            "INSERT INTO incentive.entertainment_images (entertainment_id, url, caption, position)
                             VALUES ($1, $2, $3, $4)",
                            [$new_id, $img_url, $img_caption, $img_pos ?? $pos]
                        );
                    }
                }

                pg_query($conn, "COMMIT");

                response([
                    "success" => true,
                    "message" => "Entertainment criado com sucesso",
                    "id"      => $new_id,
                    "slug"    => $slug,
                ], 201);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 400);
            }
            break;


        // ============================================================
        // ATUALIZAR ENTERTAINMENT (parcial)
        // ============================================================
        case 'atualizar_entertainment':
            if ($method !== 'PUT') response(["error" => "Use PUT"], 405);

            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatório"], 400);

            $cod_sis = $user_data = null;
            requireAuth($conn, $cod_sis, $user_data);

            if (empty($input)) response(["error" => "Nenhum dado para atualizar"], 400);

            pg_query($conn, "BEGIN");

            try {
                $updates = $params = [];
                $idx = 1;

                $allowed_strings = [
                    'title', 'slug', 'type', 'short_desc', 'description',
                    'cover_image_url', 'price_range', 'personal_note',
                ];
                $allowed_ints = ['category_id', 'city_id', 'location_id'];
                $allowed_bools = ['is_active'];

                foreach ($allowed_strings as $field) {
                    if (array_key_exists($field, $input)) {
                        $val = formatString($input[$field]);
                        if ($val !== null) {
                            $updates[] = "$field = \$$idx";
                            $params[]  = $val;
                            $idx++;
                        }
                    }
                }
                foreach ($allowed_ints as $field) {
                    if (array_key_exists($field, $input)) {
                        $val = formatInt($input[$field]);
                        if ($val !== null) {
                            $updates[] = "$field = \$$idx";
                            $params[]  = $val;
                            $idx++;
                        }
                    }
                }
                foreach ($allowed_bools as $field) {
                    if (array_key_exists($field, $input)) {
                        $val = formatBool($input[$field]);
                        if ($val !== null) {
                            $updates[] = "$field = \$$idx";
                            $params[]  = $val ? 'true' : 'false';
                            $idx++;
                        }
                    }
                }

                // Atualiza updated_at
                $updates[] = "updated_at = NOW()";

                if (count($updates) <= 1) { // só o updated_at
                    pg_query($conn, "ROLLBACK");
                    response(["success" => false, "message" => "Nenhuma alteração válida"], 200);
                }

                $params[] = $id;
                $sql = "
                    UPDATE incentive.entertainments
                    SET " . implode(', ', $updates) . "
                    WHERE id = \$$idx
                ";

                $res = pg_query_params($conn, $sql, $params);
                if (!$res || pg_affected_rows($res) === 0) {
                    throw new Exception("Entertainment não encontrado ou sem alterações");
                }

                // Atualiza imagens — se vier 'images', substitui todas
                if (isset($input['images']) && is_array($input['images'])) {
                    pg_query_params($conn,
                        "DELETE FROM incentive.entertainment_images WHERE entertainment_id = $1",
                        [$id]
                    );
                    foreach ($input['images'] as $pos => $img) {
                        $img_url     = formatString($img['url'] ?? '');
                        $img_caption = formatString($img['caption'] ?? '');
                        $img_pos     = formatInt($img['position'] ?? $pos);
                        if (!$img_url) continue;
                        pg_query_params($conn,
                            "INSERT INTO incentive.entertainment_images (entertainment_id, url, caption, position)
                             VALUES ($1, $2, $3, $4)",
                            [$id, $img_url, $img_caption, $img_pos ?? $pos]
                        );
                    }
                }

                pg_query($conn, "COMMIT");

                response([
                    "success" => true,
                    "message" => "Entertainment atualizado",
                    "id"      => $id,
                ]);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 400);
            }
            break;


        // ============================================================
        // EXCLUIR ENTERTAINMENT
        // ============================================================
        case 'excluir_entertainment':
            if ($method !== 'DELETE') response(["error" => "Use DELETE"], 405);

            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatório"], 400);

            $cod_sis = $user_data = null;
            requireAuth($conn, $cod_sis, $user_data);

            pg_query($conn, "BEGIN");

            try {
                // Remove imagens primeiro (sem FK cascade)
                pg_query_params($conn,
                    "DELETE FROM incentive.entertainment_images WHERE entertainment_id = $1",
                    [$id]
                );

                $res = pg_query_params($conn,
                    "DELETE FROM incentive.entertainments WHERE id = $1",
                    [$id]
                );

                if (!$res || pg_affected_rows($res) === 0) {
                    throw new Exception("Entertainment não encontrado");
                }

                pg_query($conn, "COMMIT");

                response(["success" => true, "message" => "Entertainment excluído"]);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 404);
            }
            break;


        // ============================================================
        // LISTAR CATEGORIAS
        // ============================================================
        case 'listar_categorias':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $result = pg_query($conn, "
                SELECT id, slug, created_at
                FROM incentive.entertainment_categories
                ORDER BY slug ASC
            ");
            if (!$result) throw new Exception(pg_last_error($conn));

            $cats = [];
            while ($row = pg_fetch_assoc($result)) {
                $cats[] = [
                    'id'         => (int)$row['id'],
                    'slug'       => $row['slug'],
                    'created_at' => $row['created_at'],
                ];
            }
            response($cats);
            break;


        // ============================================================
        // LISTAR LOCATIONS (filtrado por city_id)
        // ============================================================
        case 'listar_locations':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $city_id = getIntParam('city_id');

            if ($city_id) {
                $sql    = "SELECT id, name, city_id FROM incentive.locations WHERE city_id = $1 ORDER BY name ASC";
                $result = pg_query_params($conn, $sql, [$city_id]);
            } else {
                $result = pg_query($conn, "SELECT id, name, city_id FROM incentive.locations ORDER BY name ASC");
            }

            if (!$result) throw new Exception(pg_last_error($conn));

            $locs = [];
            while ($row = pg_fetch_assoc($result)) {
                $locs[] = [
                    'id'      => (int)$row['id'],
                    'name'    => $row['name'],
                    'city_id' => (int)$row['city_id'],
                ];
            }
            response($locs);
            break;


        // ============================================================
        // LISTAR TIPOS (valores distintos em use)
        // ============================================================
        case 'listar_tipos':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $result = pg_query($conn, "
                SELECT DISTINCT type
                FROM incentive.entertainments
                WHERE type IS NOT NULL AND type <> ''
                ORDER BY type ASC
            ");
            if (!$result) throw new Exception(pg_last_error($conn));

            $tipos = [];
            while ($row = pg_fetch_assoc($result)) {
                $tipos[] = $row['type'];
            }
            response($tipos);
            break;


        default:
            response(["error" => "Rota inválida: '$request'"], 400);
    }

} catch (Exception $e) {
    if (isset($conn)) @pg_query($conn, "ROLLBACK");
    error_log("Erro API Entertainment: " . $e->getMessage());
    response(["error" => "Erro interno no servidor"], 500);
}
