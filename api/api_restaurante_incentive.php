<?php
/**
 * API para gerenciamento de Restaurants / Cardápios / Imagens
 * Tabelas principais: 
 * - incentive.restaurants
 * - incentive.restaurant_images
 * - incentive.restaurant_menus
 * - incentive.restaurant_menu_sections
 * - incentive.restaurant_menu_items
 * Versão adaptada da API de Lodges (compatível com PHP 7.2)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tratamento de preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../util/connection.php';

// ========================================
// Funções auxiliares
// ========================================

function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function formatString($value) {
    return ($value === null || $value === '') ? null : $value;
}

function formatInt($value) {
    return ($value === null || $value === '' || !is_numeric($value)) ? null : (int)$value;
}

function formatBool($value) {
    if ($value === null) return null;
    return (bool)$value ? 't' : 'f';
}

function getUserIdFromSession() {
    session_start();
    if (!isset($_SESSION['user'])) {
        return null;
    }

    $user = $_SESSION['user'];
    if (is_array($user)) {
        $candidate = isset($user['id']) ? $user['id'] : (isset($user['usuario']) ? $user['usuario'] : null);
        return is_numeric($candidate) ? (int)$candidate : null;
    }

    return is_numeric($user) ? (int)$user : null;
}

function logAcao($conn, $usuario, $acao, $fk_conteudo = '4') {
    if (!is_numeric($usuario)) {
        return;
    }

    $data_now = date('Y-m-d');
    $sql = "
        INSERT INTO conteudo_internet.log_adm_conteudo
        (usuario, acao, data, fk_conteudo)
        VALUES ($1, $2, $3, $4)
    ";
    @pg_query_params($conn, $sql, array((int)$usuario, $acao, $data_now, $fk_conteudo));
}

function slugExists($conn, $slug, $ignoreId = null) {
    if (!$slug) {
        return false;
    }

    if ($ignoreId !== null) {
        $result = pg_query_params(
            $conn,
            "SELECT id FROM incentive.restaurants WHERE slug = $1 AND id <> $2 LIMIT 1",
            array($slug, $ignoreId)
        );
    } else {
        $result = pg_query_params(
            $conn,
            "SELECT id FROM incentive.restaurants WHERE slug = $1 LIMIT 1",
            array($slug)
        );
    }

    if (!$result) {
        throw new Exception(pg_last_error($conn));
    }

    return pg_num_rows($result) > 0;
}

// ========================================
// Processamento da requisição
// ========================================

$request = isset($_GET['request']) ? $_GET['request'] : null;
if (!$request) {
    response(array("error" => "Parâmetro 'request' é obrigatório"), 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input_raw = file_get_contents('php://input');
$input = json_decode($input_raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = array();
}

try {
    switch ($request) {

        // ========================================
        // RESTAURANTS (principal)
        // ========================================

        case 'listar_restaurantes_paginate':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);

            $page     = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
            $per_page = max(1, min(100, (int)(isset($_GET['per_page']) ? $_GET['per_page'] : 20)));
            $offset   = ($page - 1) * $per_page;

            $filtro_nome      = trim(isset($_GET['filtro_nome']) ? $_GET['filtro_nome'] : '');
            $filtro_city_code = trim(isset($_GET['filtro_city_code']) ? $_GET['filtro_city_code'] : '');
            $filtro_active    = isset($_GET['filtro_active']) ? filter_var($_GET['filtro_active'], FILTER_VALIDATE_BOOLEAN) : null;

            $where  = array();
            $params = array();
            $idx    = 1;

            if ($filtro_nome) {
                $where[]  = "name ILIKE \$$idx";
                $params[] = "%$filtro_nome%";
                $idx++;
            }
            if ($filtro_city_code) {
                $where[]  = "city_code = \$$idx";
                $params[] = $filtro_city_code;
                $idx++;
            }
            if ($filtro_active !== null) {
                $where[]  = "is_active = \$$idx";
                $params[] = $filtro_active ? 't' : 'f';
                $idx++;
            }

            $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            // COUNT total
            $sql_count = "SELECT COUNT(*) AS total FROM incentive.restaurants $where_sql";
            $res_count = pg_query_params($conn, $sql_count, $params);
            if (!$res_count) throw new Exception(pg_last_error($conn));
            $total = (int)pg_fetch_result($res_count, 0, 'total');

            $sql = "
                SELECT 
                    id,
                    name,
                    slug,
                    city_code,
                    short_description,
                    capacity,
                    has_private_area,
                    has_view,
                    is_active
                FROM incentive.restaurants
                $where_sql
                ORDER BY name
                LIMIT \$" . $idx . " OFFSET \$" . ($idx + 1) . "
            ";
            $params_pag = array_merge($params, array($per_page, $offset));

            $result = pg_query_params($conn, $sql, $params_pag);
            if (!$result) throw new Exception(pg_last_error($conn));

            $restaurants = array();
            while ($row = pg_fetch_assoc($result)) {
                $restaurants[] = $row;
            }

            response(array(
                "success"      => true,
                "data"         => $restaurants,
                "current_page" => $page,
                "per_page"     => $per_page,
                "total"        => $total,
                "last_page"    => (int)ceil($total / $per_page),
                "from"         => $offset + 1,
                "to"           => min($offset + $per_page, $total)
            ));
            break;

        case 'buscar_restaurante':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);

            $id = formatInt(isset($_GET['id']) ? $_GET['id'] : null);
            if (!$id) response(array("error" => "id obrigatório"), 400);

            $sql = "
                SELECT 
                    id, name, slug, city_code, short_description, description,
                    capacity, has_private_area, has_view, address,
                    latitude, longitude, is_active,
                    created_at, updated_at
                FROM incentive.restaurants
                WHERE id = $1
            ";

            $result = pg_query_params($conn, $sql, array($id));
            if (!$result || pg_num_rows($result) == 0) {
                response(array("error" => "Restaurante não encontrado"), 404);
            }

            $restaurant = pg_fetch_assoc($result);

            // Buscar imagens (com cover e position)
            $sql_images = "
                SELECT id, image_url, is_cover, position 
                FROM incentive.restaurant_images 
                WHERE restaurant_id = $1 
                ORDER BY is_cover DESC, position ASC, id ASC
            ";
            $res_images = pg_query_params($conn, $sql_images, array($id));
            $restaurant['images'] = pg_fetch_all($res_images) ?: array();

            response(array("success" => true, "data" => $restaurant));
            break;

        case 'criar_restaurante':
            if ($method !== 'POST') response(array("error" => "Use POST"), 405);
            if (empty($input)) response(array("error" => "Dados obrigatórios (JSON)"), 400);

            $fields = array(
                'name'             => formatString(isset($input['name']) ? $input['name'] : ''),
                'slug'             => formatString(isset($input['slug']) ? $input['slug'] : ''),
                'city_code'        => formatString(isset($input['city_code']) ? $input['city_code'] : ''),
                'short_description'=> formatString(isset($input['short_description']) ? $input['short_description'] : ''),
                'description'      => formatString(isset($input['description']) ? $input['description'] : ''),
                'capacity'         => formatInt(isset($input['capacity']) ? $input['capacity'] : null),
                'has_private_area' => formatBool(isset($input['has_private_area']) ? $input['has_private_area'] : false),
                'has_view'         => formatBool(isset($input['has_view']) ? $input['has_view'] : false),
                'address'          => formatString(isset($input['address']) ? $input['address'] : ''),
                'latitude'         => formatString(isset($input['latitude']) ? $input['latitude'] : ''),
                'longitude'        => formatString(isset($input['longitude']) ? $input['longitude'] : ''),
                'is_active'        => formatBool(isset($input['is_active']) ? $input['is_active'] : true)
            );

            if (!$fields['name'] || !$fields['slug'] || !$fields['city_code']) {
                response(array("error" => "name, slug e city_code são obrigatórios"), 400);
            }

            if (slugExists($conn, $fields['slug'])) {
                response(array(
                    "error" => "JÃ¡ existe um restaurante com este slug.",
                    "field" => "slug",
                    "slug" => $fields['slug']
                ), 409);
            }

            $cols = implode(', ', array_keys($fields));
            $placeholders_array = array();
            for ($i = 1; $i <= count($fields); $i++) {
                $placeholders_array[] = '$' . $i;
            }
            $placeholders = implode(', ', $placeholders_array);
            $values = array_values($fields);

            $sql = "INSERT INTO incentive.restaurants ($cols) VALUES ($placeholders) RETURNING id";

            $result = pg_query_params($conn, $sql, $values);
            if (!$result) throw new Exception(pg_last_error($conn));

            $id = pg_fetch_result($result, 0, 0);

            $usuario = getUserIdFromSession();
            logAcao($conn, $usuario, "Inseriu novo restaurante - $id - " . $fields['name']);

            response(array("success" => true, "message" => "Restaurante inserido com sucesso", "id" => $id), 201);
            break;

        case 'atualizar_restaurante':
            if ($method !== 'PUT') response(array("error" => "Use PUT"), 405);

            $id = formatInt(isset($_GET['id']) ? $_GET['id'] : null);
            if (!$id) response(array("error" => "id obrigatório"), 400);
            if (empty($input)) response(array("error" => "Dados obrigatórios (JSON)"), 400);

            pg_query($conn, "BEGIN");

            $set = array("updated_at = NOW()");
            $params = array();
            $idx = 1;

            $mapeamento = array(
                'name'              => 'name',
                'slug'              => 'slug',
                'city_code'         => 'city_code',
                'short_description' => 'short_description',
                'description'       => 'description',
                'capacity'          => 'capacity',
                'has_private_area'  => 'has_private_area',
                'has_view'          => 'has_view',
                'address'           => 'address',
                'latitude'          => 'latitude',
                'longitude'         => 'longitude',
                'is_active'         => 'is_active'
            );

            foreach ($input as $key => $val) {
                $campo = isset($mapeamento[$key]) ? $mapeamento[$key] : $key;
                if ($campo !== 'id' && $campo !== 'created_at' && $campo !== 'updated_at') {
                    if ($campo === 'has_private_area' || $campo === 'has_view' || $campo === 'is_active') {
                        $set[] = "$campo = \$$idx";
                        $params[] = formatBool($val);
                    } else {
                        $set[] = "$campo = \$$idx";
                        $params[] = formatString($val);
                    }
                    $idx++;
                }
            }

            if (count($set) === 1) { // só updated_at
                pg_query($conn, "ROLLBACK");
                response(array("success" => false, "message" => "Nenhuma alteração enviada"), 200);
            }

            $params[] = $id;
            $sql = "UPDATE incentive.restaurants SET " . implode(', ', $set) . " WHERE id = \$$idx";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                pg_query($conn, "ROLLBACK");
                throw new Exception(pg_last_error($conn));
            }

            pg_query($conn, "COMMIT");

            $usuario = getUserIdFromSession();
            logAcao($conn, $usuario, "Atualizou restaurante - $id");

            response(array("success" => true, "message" => "Restaurante atualizado com sucesso", "id" => $id));
            break;

        case 'excluir_restaurante':
            if ($method !== 'DELETE') response(array("error" => "Use DELETE"), 405);

            $id = formatInt(isset($_GET['id']) ? $_GET['id'] : null);
            if (!$id) response(array("error" => "id obrigatório"), 400);

            $sql = "DELETE FROM incentive.restaurants WHERE id = $1";
            $result = pg_query_params($conn, $sql, array($id));

            if (!$result) throw new Exception(pg_last_error($conn));

            $affected = pg_affected_rows($result);
            if ($affected > 0) {
                $usuario = getUserIdFromSession();
                logAcao($conn, $usuario, "Excluiu restaurante - $id");
                response(array("success" => true, "message" => "Restaurante excluído"));
            } else {
                response(array("error" => "Restaurante não encontrado"), 404);
            }
            break;

        // ========================================
        // MENUS + SECTIONS + ITEMS (hierarquia análoga aos itinerários)
        // ========================================

        // Lista todos os menus de um restaurante específico
        case 'listar_imagens':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);

            $restaurant_id = formatInt(isset($_GET['restaurant_id']) ? $_GET['restaurant_id'] : null);
            if (!$restaurant_id) response(array("error" => "restaurant_id obrigatÃ³rio"), 400);

            $sql = "
                SELECT id, restaurant_id, image_url, is_cover, position, created_at
                FROM incentive.restaurant_images
                WHERE restaurant_id = $1
                ORDER BY is_cover DESC, position ASC, id ASC
            ";
            $result = pg_query_params($conn, $sql, array($restaurant_id));
            if (!$result) throw new Exception(pg_last_error($conn));

            response(array(
                "success" => true,
                "data" => pg_fetch_all($result) ?: array()
            ));
            break;

        case 'criar_imagem':
            if ($method !== 'POST') response(array("error" => "Use POST"), 405);
            if (empty($input)) response(array("error" => "Dados obrigatÃ³rios (JSON)"), 400);

            $restaurant_id = formatInt(isset($input['restaurant_id']) ? $input['restaurant_id'] : null);
            $image_url = formatString(isset($input['image_url']) ? $input['image_url'] : '');
            $is_cover = formatBool(isset($input['is_cover']) ? $input['is_cover'] : false);
            $position = formatInt(isset($input['position']) ? $input['position'] : 0);

            if (!$restaurant_id || !$image_url) {
                response(array("error" => "restaurant_id e image_url sÃ£o obrigatÃ³rios"), 400);
            }

            pg_query($conn, "BEGIN");

            if ($is_cover === 't') {
                $clearCover = pg_query_params(
                    $conn,
                    "UPDATE incentive.restaurant_images SET is_cover = FALSE WHERE restaurant_id = $1",
                    array($restaurant_id)
                );
                if (!$clearCover) {
                    pg_query($conn, "ROLLBACK");
                    throw new Exception(pg_last_error($conn));
                }
            }

            $sql = "
                INSERT INTO incentive.restaurant_images (restaurant_id, image_url, is_cover, position)
                VALUES ($1, $2, $3, $4)
                RETURNING id
            ";
            $result = pg_query_params($conn, $sql, array($restaurant_id, $image_url, $is_cover, $position ?: 0));
            if (!$result) {
                pg_query($conn, "ROLLBACK");
                throw new Exception(pg_last_error($conn));
            }

            pg_query($conn, "COMMIT");

            $id = pg_fetch_result($result, 0, 0);
            $usuario = getUserIdFromSession();
            logAcao($conn, $usuario, "Criou imagem ($id) para restaurante ($restaurant_id)");

            response(array("success" => true, "message" => "Imagem criada com sucesso", "id" => (int)$id), 201);
            break;

        case 'atualizar_imagem':
            if ($method !== 'PUT') response(array("error" => "Use PUT"), 405);

            $id = formatInt(isset($_GET['id']) ? $_GET['id'] : null);
            if (!$id) response(array("error" => "id obrigatÃ³rio"), 400);
            if (empty($input)) response(array("error" => "Dados obrigatÃ³rios (JSON)"), 400);

            $imgResult = pg_query_params(
                $conn,
                "SELECT restaurant_id FROM incentive.restaurant_images WHERE id = $1 LIMIT 1",
                array($id)
            );
            if (!$imgResult || pg_num_rows($imgResult) === 0) {
                response(array("error" => "Imagem nÃ£o encontrada"), 404);
            }
            $restaurant_id = formatInt(pg_fetch_result($imgResult, 0, 'restaurant_id'));

            pg_query($conn, "BEGIN");

            $set = array();
            $params = array();
            $idx = 1;

            if (array_key_exists('image_url', $input)) {
                $set[] = "image_url = \$$idx";
                $params[] = formatString($input['image_url']);
                $idx++;
            }
            if (array_key_exists('is_cover', $input)) {
                $is_cover = formatBool($input['is_cover']);
                if ($is_cover === 't') {
                    $clearCover = pg_query_params(
                        $conn,
                        "UPDATE incentive.restaurant_images SET is_cover = FALSE WHERE restaurant_id = $1 AND id <> $2",
                        array($restaurant_id, $id)
                    );
                    if (!$clearCover) {
                        pg_query($conn, "ROLLBACK");
                        throw new Exception(pg_last_error($conn));
                    }
                }
                $set[] = "is_cover = \$$idx";
                $params[] = $is_cover;
                $idx++;
            }
            if (array_key_exists('position', $input)) {
                $set[] = "position = \$$idx";
                $params[] = formatInt($input['position']) ?: 0;
                $idx++;
            }

            if (empty($set)) {
                pg_query($conn, "ROLLBACK");
                response(array("success" => false, "message" => "Nenhuma alteraÃ§Ã£o enviada"), 200);
            }

            $slugToValidate = array_key_exists('slug', $input) ? formatString($input['slug']) : null;
            if ($slugToValidate && slugExists($conn, $slugToValidate, $id)) {
                pg_query($conn, "ROLLBACK");
                response(array(
                    "error" => "JÃ¡ existe um restaurante com este slug.",
                    "field" => "slug",
                    "slug" => $slugToValidate
                ), 409);
            }

            $params[] = $id;
            $sql = "UPDATE incentive.restaurant_images SET " . implode(', ', $set) . " WHERE id = \$$idx";
            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                pg_query($conn, "ROLLBACK");
                throw new Exception(pg_last_error($conn));
            }

            pg_query($conn, "COMMIT");

            $usuario = getUserIdFromSession();
            logAcao($conn, $usuario, "Atualizou imagem - $id");

            response(array("success" => true, "message" => "Imagem atualizada com sucesso", "id" => $id));
            break;

        case 'excluir_imagem':
            if ($method !== 'DELETE') response(array("error" => "Use DELETE"), 405);

            $id = formatInt(isset($_GET['id']) ? $_GET['id'] : null);
            if (!$id) response(array("error" => "id obrigatÃ³rio"), 400);

            $result = pg_query_params($conn, "DELETE FROM incentive.restaurant_images WHERE id = $1", array($id));
            if (!$result) throw new Exception(pg_last_error($conn));

            if (pg_affected_rows($result) > 0) {
                $usuario = getUserIdFromSession();
                logAcao($conn, $usuario, "Excluiu imagem - $id");
                response(array("success" => true, "message" => "Imagem excluÃ­da com sucesso"));
            } else {
                response(array("error" => "Imagem nÃ£o encontrada"), 404);
            }
            break;

        case 'listar_menus':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);
            $restaurant_id = formatInt(isset($_GET['restaurant_id']) ? $_GET['restaurant_id'] : null);
            if (!$restaurant_id) response(array("error" => "restaurant_id obrigatório"), 400);

            $sql = "SELECT id, restaurant_id, title, created_at 
                    FROM incentive.restaurant_menus 
                    WHERE restaurant_id = $1 ORDER BY id ASC";
            $result = pg_query_params($conn, $sql, array($restaurant_id));
            if (!$result) throw new Exception(pg_last_error($conn));

            $menus = pg_fetch_all($result) ?: array();
            response(array("success" => true, "data" => $menus));
            break;

        // Busca um menu completo com sections e items aninhados
        case 'buscar_menu':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);
            $id = formatInt(isset($_GET['id']) ? $_GET['id'] : null);
            if (!$id) response(array("error" => "id do menu obrigatório"), 400);

            // Menu principal
            $sql_menu = "SELECT * FROM incentive.restaurant_menus WHERE id = $1";
            $res_menu = pg_query_params($conn, $sql_menu, array($id));
            if (!$res_menu || pg_num_rows($res_menu) == 0) {
                response(array("error" => "Menu não encontrado"), 404);
            }
            $menu = pg_fetch_assoc($res_menu);

            // Sections
            $sql_sections = "
                SELECT id, name, position 
                FROM incentive.restaurant_menu_sections 
                WHERE menu_id = $1 
                ORDER BY position ASC, id ASC
            ";
            $res_sections = pg_query_params($conn, $sql_sections, array($id));
            $sections = pg_fetch_all($res_sections) ?: array();

            // Items por section
            foreach ($sections as &$section) {
                $sql_items = "
                    SELECT id, name, description, position 
                    FROM incentive.restaurant_menu_items 
                    WHERE section_id = $1 
                    ORDER BY position ASC, id ASC
                ";
                $res_items = pg_query_params($conn, $sql_items, array($section['id']));
                $section['items'] = pg_fetch_all($res_items) ?: array();
            }
            unset($section);

            $menu['sections'] = $sections;

            response(array("success" => true, "data" => $menu));
            break;

        // Cria um menu e, opcionalmente, suas sections + items
        case 'criar_menu':
            if ($method !== 'POST') response(array("error" => "Use POST"), 405);
            
            $restaurant_id = formatInt($input['restaurant_id'] ?? null);
            $title         = formatString($input['title'] ?? null);

            if (!$restaurant_id || !$title) {
                response(array("error" => "restaurant_id e title são obrigatórios"), 400);
            }

            pg_query($conn, "BEGIN");

            $sql = "INSERT INTO incentive.restaurant_menus (restaurant_id, title) 
                    VALUES ($1, $2) RETURNING id";
            $result = pg_query_params($conn, $sql, array($restaurant_id, $title));

            if (!$result) {
                pg_query($conn, "ROLLBACK");
                throw new Exception(pg_last_error($conn));
            }

            $menu_id = pg_fetch_result($result, 0, 0);

            // Sections + Items (opcional)
            if (isset($input['sections']) && is_array($input['sections'])) {
                foreach ($input['sections'] as $section) {
                    $section_name = formatString($section['name'] ?? null);
                    $section_pos  = formatInt($section['position'] ?? 0);

                    if (!$section_name) continue;

                    $res_section = pg_query_params($conn,
                        "INSERT INTO incentive.restaurant_menu_sections (menu_id, name, position) 
                         VALUES ($1, $2, $3) RETURNING id",
                        array($menu_id, $section_name, $section_pos)
                    );

                    if (!$res_section) {
                        pg_query($conn, "ROLLBACK");
                        throw new Exception("Erro ao inserir section: " . pg_last_error($conn));
                    }

                    $section_id = pg_fetch_result($res_section, 0, 0);

                    if (isset($section['items']) && is_array($section['items'])) {
                        foreach ($section['items'] as $item) {
                            $item_name  = formatString($item['name'] ?? null);
                            $item_desc  = formatString($item['description'] ?? null);
                            $item_pos   = formatInt($item['position'] ?? 0);

                            if (!$item_name) continue;

                            $res_item = pg_query_params($conn,
                                "INSERT INTO incentive.restaurant_menu_items (section_id, name, description, position) 
                                 VALUES ($1, $2, $3, $4)",
                                array($section_id, $item_name, $item_desc, $item_pos)
                            );

                            if (!$res_item) {
                                pg_query($conn, "ROLLBACK");
                                throw new Exception("Erro ao inserir item: " . pg_last_error($conn));
                            }
                        }
                    }
                }
            }

            pg_query($conn, "COMMIT");

            $usuario = getUserIdFromSession();
            logAcao($conn, $usuario, "Criou menu ($menu_id) para restaurante ($restaurant_id)");

            response(array("success" => true, "message" => "Menu criado com sucesso", "id" => (int)$menu_id), 201);
            break;

        // Atualiza menu e recria sections + items se enviados
        case 'atualizar_menu':
            if ($method !== 'PUT') response(array("error" => "Use PUT"), 405);

            $id = formatInt(isset($_GET['id']) ? $_GET['id'] : null);
            if (!$id) response(array("error" => "id obrigatório"), 400);

            pg_query($conn, "BEGIN");

            $set = array("updated_at = NOW()"); // restaurant_menus não tem updated_at, mas podemos adicionar se quiser
            $params = array();
            $idx = 1;

            if (isset($input['title'])) {
                $set[] = "title = \$$idx";
                $params[] = formatString($input['title']);
                $idx++;
            }

            if (count($set) > 1) {
                $params[] = $id;
                $sql = "UPDATE incentive.restaurant_menus 
                        SET " . implode(', ', $set) . " 
                        WHERE id = \$$idx";
                $result = pg_query_params($conn, $sql, $params);
                if (!$result) {
                    pg_query($conn, "ROLLBACK");
                    throw new Exception(pg_last_error($conn));
                }
            }

            // Recria sections + items se enviados
            if (isset($input['sections']) && is_array($input['sections'])) {

                // Apaga tudo anterior
                pg_query_params($conn,
                    "DELETE FROM incentive.restaurant_menu_items 
                     WHERE section_id IN (SELECT id FROM incentive.restaurant_menu_sections WHERE menu_id = $1)",
                    array($id)
                );
                pg_query_params($conn,
                    "DELETE FROM incentive.restaurant_menu_sections WHERE menu_id = $1",
                    array($id)
                );

                foreach ($input['sections'] as $section) {
                    $section_name = formatString($section['name'] ?? null);
                    $section_pos  = formatInt($section['position'] ?? 0);

                    if (!$section_name) continue;

                    $res_section = pg_query_params($conn,
                        "INSERT INTO incentive.restaurant_menu_sections (menu_id, name, position) 
                         VALUES ($1, $2, $3) RETURNING id",
                        array($id, $section_name, $section_pos)
                    );

                    if (!$res_section) {
                        pg_query($conn, "ROLLBACK");
                        throw new Exception("Erro ao atualizar section: " . pg_last_error($conn));
                    }

                    $section_id = pg_fetch_result($res_section, 0, 0);

                    if (isset($section['items']) && is_array($section['items'])) {
                        foreach ($section['items'] as $item) {
                            $item_name = formatString($item['name'] ?? null);
                            $item_desc = formatString($item['description'] ?? null);
                            $item_pos  = formatInt($item['position'] ?? 0);

                            if (!$item_name) continue;

                            pg_query_params($conn,
                                "INSERT INTO incentive.restaurant_menu_items (section_id, name, description, position) 
                                 VALUES ($1, $2, $3, $4)",
                                array($section_id, $item_name, $item_desc, $item_pos)
                            );
                        }
                    }
                }
            }

            pg_query($conn, "COMMIT");

            $usuario = getUserIdFromSession();
            logAcao($conn, $usuario, "Atualizou menu - $id");

            response(array("success" => true, "message" => "Menu atualizado com sucesso", "id" => $id));
            break;

        case 'excluir_menu':
            if ($method !== 'DELETE') response(array("error" => "Use DELETE"), 405);
            $id = formatInt(isset($_GET['id']) ? $_GET['id'] : null);
            if (!$id) response(array("error" => "id obrigatório"), 400);

            $sql = "DELETE FROM incentive.restaurant_menus WHERE id = $1";
            $result = pg_query_params($conn, $sql, array($id));

            if (!$result) throw new Exception(pg_last_error($conn));

            if (pg_affected_rows($result) > 0) {
                $usuario = getUserIdFromSession();
                logAcao($conn, $usuario, "Excluiu menu - $id");
                response(array("success" => true, "message" => "Menu excluído com sucesso"));
            } else {
                response(array("error" => "Menu não encontrado"), 404);
            }
            break;

        // ========================================
        // CIDADES (mantido da API original)
        // ========================================

        case 'listar_cidades_sbd':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);
            
            $busca = formatString(isset($_GET['busca']) ? $_GET['busca'] : null);
            
            $sql = "SELECT cid, nome_cid 
                    FROM sbd95.cidades";
            $params = array();
            
            if ($busca) {
                $sql .= " WHERE lower(nome_cid) LIKE lower($1)";
                $params[] = '%' . $busca . '%';
            }
            
            $sql .= " ORDER BY nome_cid ASC";
            
            $result = empty($params) 
                ? pg_query($conn, $sql) 
                : pg_query_params($conn, $sql, $params);
                
            if (!$result) throw new Exception(pg_last_error($conn));
            
            $cidades = pg_fetch_all($result) ?: array();
            response(array("success" => true, "data" => $cidades));
            break;

        case 'listar_cidades_tpo':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);
            
            $busca = formatString(isset($_GET['busca']) ? $_GET['busca'] : null);
            
            $sql = "SELECT tpocidcod, nome_pt, nome_en 
                    FROM tarifario.cidade_tpo";
            $params = array();
            
            if ($busca) {
                $sql .= " WHERE lower(nome_pt) LIKE lower($1) 
                        OR lower(nome_en) LIKE lower($1)";
                $params[] = '%' . $busca . '%';
            }
            
            $sql .= " ORDER BY nome_pt ASC";
            
            $result = empty($params) 
                ? pg_query($conn, $sql) 
                : pg_query_params($conn, $sql, $params);
                
            if (!$result) throw new Exception(pg_last_error($conn));
            
            $cidades = pg_fetch_all($result) ?: array();
            response(array("success" => true, "data" => $cidades));
            break;

        default:
            response(array("error" => "Rota inválida"), 400);
    }
} catch (Exception $e) {
    if (isset($conn) && pg_transaction_status($conn) !== PGSQL_TRANSACTION_IDLE) {
        pg_query($conn, "ROLLBACK");
    }
    error_log("Erro API Restaurants: " . $e->getMessage());
    response(array("error" => "Erro interno: " . $e->getMessage()), 500);
}
