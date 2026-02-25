<?php
/**
 * =========================================================
 * API RESTful - Módulo Incentivos (COMPLETA)
 * =========================================================
 * 
 * Endpoints disponíveis:
 * 
 * LEITURA (Públicas):
 *   GET    ?request=listar_incentives        - Lista todos os incentivos (com paginação e filtros)
 *   GET    ?request=buscar_incentive&id=X    - Busca 1 incentivo completo (com todos os relacionamentos)
 * 
 * ESCRITA (Autenticadas - Bearer Token):
 *   POST   ?request=criar_incentive          - Cria novo incentivo completo (programa + relacionamentos)
 *   PUT    ?request=atualizar_incentive&id=X - Atualiza incentivo completo (programa + relacionamentos)
 *   DELETE ?request=excluir_incentive&id=X   - Exclui incentivo e todos os relacionamentos
 * 
 * Formato JSON esperado no POST/PUT:
 * {
 *   "inc_name": "string (obrigatório)",
 *   "inc_description": "string",
 *   "hotel_ref_id": int,
 *   "hotel_name_snapshot": "string",
 *   "city_name": "string",
 *   "country_code": "BR",
 *   "inc_status": "active|inactive|draft|archived",
 *   "inc_is_active": true|false,
 *   
 *   "media": [
 *     {
 *       "media_type": "banner|gallery|video|map",
 *       "media_url": "string",
 *       "position": int,
 *       "is_active": true|false
 *     }
 *   ],
 *   
 *   "room_categories": [
 *     {
 *       "room_name": "string",
 *       "quantity": int,
 *       "notes": "string",
 *       "position": int,
 *       "is_active": true|false
 *     }
 *   ],
 *   
 *   "dining": [
 *     {
 *       "name": "string",
 *       "description": "string",
 *       "cuisine": "string",
 *       "capacity": int,
 *       "schedule": "string",
 *       "is_michelin": true|false,
 *       "can_be_private": true|false,
 *       "image_url": "string",
 *       "position": int,
 *       "is_active": true|false
 *     }
 *   ],
 *   
 *   "facilities": [
 *     {
 *       "name": "string",
 *       "icon": "string",
 *       "is_active": true|false
 *     }
 *   ],
 *   
 *   "convention": {
 *     "description": "string",
 *     "total_rooms": int,
 *     "has_360": true|false
 *   },
 *   
 *   "convention_rooms": [
 *     {
 *       "name": "string",
 *       "area_m2": numeric,
 *       "capacity_auditorium": int,
 *       "capacity_banquet": int,
 *       "capacity_classroom": int,
 *       "capacity_u_shape": int,
 *       "notes": "string"
 *     }
 *   ],
 *   
 *   "notes": [
 *     {
 *       "language": "PT",
 *       "note": "string"
 *     }
 *   ]
 * }
 */

// =========================================================
// Configurações iniciais
// =========================================================

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Conexão e Middleware
require_once 'util/connection.php';
require_once 'middleware.php';

// =========================================================
// FUNÇÕES AUXILIARES - Resposta e Parâmetros
// =========================================================

function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getParam($name, $default = null) {
    return $_GET[$name] ?? $default;
}

function getStringParam($name, $default = null) {
    $v = getParam($name, $default);
    return ($v !== null) ? trim($v) : null;
}

function getIntParam($name, $default = null) {
    $v = getParam($name, $default);
    return is_numeric($v) ? (int)$v : $default;
}

// =========================================================
// FUNÇÕES AUXILIARES - Formatação e Validação
// =========================================================

function formatString($val) {
    return ($val === '' || $val === null) ? null : trim($val);
}

function formatInt($val) {
    return is_numeric($val) ? (int)$val : null;
}

function formatNumeric($val) {
    if ($val === null || $val === '') return null;
    return is_numeric($val) ? $val : null;
}

function formatBoolean($val) {
    // Trata null, string vazia, undefined
    if ($val === null || $val === '' || $val === 'null' || $val === 'undefined') {
        return 'f'; // Retorna 'f' (formato PostgreSQL) em vez de false
    }
    
    // Se já é boolean PHP, converte para formato PostgreSQL
    if (is_bool($val)) {
        return $val ? 't' : 'f';
    }
    
    // Se é string
    if (is_string($val)) {
        $val = strtolower(trim($val));
        if ($val === 'true' || $val === '1' || $val === 't' || $val === 'yes') {
            return 't';
        }
        if ($val === 'false' || $val === '0' || $val === 'f' || $val === 'no' || $val === '') {
            return 'f';
        }
    }
    
    // Se é número
    if (is_numeric($val)) {
        return ((int)$val === 1) ? 't' : 'f';
    }
    
    // Fallback: converte para boolean e depois para 't'/'f'
    return $val ? 't' : 'f';
}

function formatCountry($val) {
    $v = formatString($val);
    if ($v === null) return null;
    $v = strtoupper($v);
    return (strlen($v) === 2) ? $v : null;
}

function formatStatus($val) {
    $allowed = ['active', 'inactive', 'draft', 'archived'];
    return in_array($val, $allowed, true) ? $val : 'active';
}

function formatMediaType($val) {
    $allowed = ['banner', 'gallery', 'video', 'map'];
    return in_array($val, $allowed, true) ? $val : null;
}

function formatLanguage($val) {
    $v = formatString($val);
    if ($v === null) return null;
    $v = strtoupper($v);
    return (strlen($v) === 2) ? $v : null;
}

function formatPosition($val) {
    $int = formatInt($val);
    return ($int !== null && $int >= 0) ? $int : 0;
}

function boolFromPg($v) {
    // Converte formato PostgreSQL ('t'/'f') para boolean PHP
    if ($v === true || $v === 't' || $v === 'true' || $v === 1 || $v === '1') return true;
    if ($v === false || $v === 'f' || $v === 'false' || $v === 0 || $v === '0') return false;
    return false; // default seguro
}

// =========================================================
// FUNÇÕES AUXILIARES - Banco de Dados
// =========================================================

/**
 * Sanitiza array de parâmetros antes de enviar ao PostgreSQL
 * Garante que booleans nunca sejam strings vazias
 */
function sanitizeParams($params) {
    return array_map(function($val) {
        // Se for string vazia e o contexto sugere boolean, converte para false
        if ($val === '' || $val === null) {
            return null;
        }
        return $val;
    }, $params);
}

function execParams($conn, $sql, $params, $errorMessage) {
    // Sanitiza parâmetros antes de enviar
    $params = sanitizeParams($params);
    
    $res = pg_query_params($conn, $sql, $params);
    if (!$res) {
        // Log detalhado do erro para debug
        error_log("SQL Error: " . $errorMessage);
        error_log("SQL: " . $sql);
        error_log("Params: " . print_r($params, true));
        error_log("PG Error: " . pg_last_error($conn));
        
        throw new Exception($errorMessage . ': ' . pg_last_error($conn));
    }
    return $res;
}

// =========================================================
// FUNÇÕES AUXILIARES - Autenticação
// =========================================================

function requireBearerToken($conn, &$user_data, $cod_sis = null) {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['authorization'] ?? $headers['Authorization'] ?? '';

    if (strpos($auth, 'Bearer ') !== 0) {
        response(["error" => "Token Bearer obrigatório"], 401);
    }

    $token = trim(substr($auth, 7));

    if (!validarToken($conn, $cod_sis, $token, $user_data)) {
        response(["error" => "Token inválido ou expirado"], 401);
    }

    return $token;
}

// =========================================================
// FUNÇÕES DE SINCRONIZAÇÃO - Relacionamentos
// =========================================================

/**
 * Sincroniza mídias do incentivo
 */
function syncMedia($conn, $inc_id, $mediaList) {
    // Remove todas as mídias existentes
    execParams(
        $conn,
        "DELETE FROM incentive.inc_media WHERE inc_id = $1",
        [$inc_id],
        "Erro ao limpar mídias"
    );

    // Insere novas mídias
    foreach ($mediaList as $index => $media) {
        $media_type = formatMediaType($media['media_type'] ?? null);
        $media_url  = formatString($media['media_url'] ?? null);
        $position   = formatPosition($media['position'] ?? 0);
        $is_active  = formatBoolean($media['is_active'] ?? true);

        if (!$media_type) {
            throw new Exception("Mídia {$index}: media_type inválido ou ausente");
        }
        if (!$media_url) {
            throw new Exception("Mídia {$index}: media_url obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_media 
                (inc_id, media_type, media_url, position, is_active) 
             VALUES ($1, $2, $3, $4, $5)",
            [$inc_id, $media_type, $media_url, $position, $is_active],
            "Erro ao inserir mídia"
        );
    }
}

/**
 * Sincroniza categorias de quartos
 */
function syncRoomCategories($conn, $inc_id, $roomCategories) {
    execParams(
        $conn,
        "DELETE FROM incentive.inc_room_category WHERE inc_id = $1",
        [$inc_id],
        "Erro ao limpar categorias de quartos"
    );

    foreach ($roomCategories as $index => $room) {
        $room_name = formatString($room['room_name'] ?? null);
        $quantity  = formatInt($room['quantity'] ?? null);
        $notes     = formatString($room['notes'] ?? null);
        $position  = formatPosition($room['position'] ?? 0);
        $is_active = formatBoolean($room['is_active'] ?? true);

        if (!$room_name) {
            throw new Exception("Categoria de quarto {$index}: room_name obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_room_category 
                (inc_id, room_name, quantity, notes, position, is_active) 
             VALUES ($1, $2, $3, $4, $5, $6)",
            [$inc_id, $room_name, $quantity, $notes, $position, $is_active],
            "Erro ao inserir categoria de quarto"
        );
    }
}

/**
 * Sincroniza dining
 */
function syncDining($conn, $inc_id, $diningList) {
    execParams(
        $conn,
        "DELETE FROM incentive.inc_dining WHERE inc_id = $1",
        [$inc_id],
        "Erro ao limpar dining"
    );

    foreach ($diningList as $index => $dining) {
        $name           = formatString($dining['name'] ?? null);
        $description    = formatString($dining['description'] ?? null);
        $cuisine        = formatString($dining['cuisine'] ?? null);
        $capacity       = formatInt($dining['capacity'] ?? null);
        $schedule       = formatString($dining['schedule'] ?? null);
        $is_michelin    = formatBoolean($dining['is_michelin'] ?? false);
        $can_be_private = formatBoolean($dining['can_be_private'] ?? false);
        $image_url      = formatString($dining['image_url'] ?? null);
        $position       = formatPosition($dining['position'] ?? 0);
        $is_active      = formatBoolean($dining['is_active'] ?? true);

        if (!$name) {
            throw new Exception("Dining {$index}: name obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_dining 
                (inc_id, name, description, cuisine, capacity, schedule, 
                 is_michelin, can_be_private, image_url, position, is_active)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)",
            [
                $inc_id,
                $name,
                $description,
                $cuisine,
                $capacity,
                $schedule,
                $is_michelin,
                $can_be_private,
                $image_url,
                $position,
                $is_active
            ],
            "Erro ao inserir dining"
        );
    }
}

/**
 * Sincroniza facilities
 */
function syncFacilities($conn, $inc_id, $facilities) {
    execParams(
        $conn,
        "DELETE FROM incentive.inc_facility WHERE inc_id = $1",
        [$inc_id],
        "Erro ao limpar facilities"
    );

    foreach ($facilities as $index => $facility) {
        $name      = formatString($facility['name'] ?? null);
        $icon      = formatString($facility['icon'] ?? null);
        $is_active = formatBoolean($facility['is_active'] ?? true);

        if (!$name) {
            throw new Exception("Facility {$index}: name obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_facility 
                (inc_id, name, icon, is_active) 
             VALUES ($1, $2, $3, $4)",
            [$inc_id, $name, $icon, $is_active],
            "Erro ao inserir facility"
        );
    }
}

/**
 * Sincroniza ou remove convention
 */
function upsertConvention($conn, $inc_id, $convention) {
    // Se convention é null ou vazio, remove
    if ($convention === null || empty($convention)) {
        execParams(
            $conn,
            "DELETE FROM incentive.inc_convention WHERE inc_id = $1",
            [$inc_id],
            "Erro ao excluir convention"
        );
        return null;
    }

    // Formata valores com tratamento extra
    $description = formatString($convention['description'] ?? null);
    $total_rooms = formatInt($convention['total_rooms'] ?? null);
    
    // Tratamento ESPECIAL para has_360
    $has_360_raw = $convention['has_360'] ?? false;
    $has_360 = formatBoolean($has_360_raw);
    
    // Debug temporário (remova após testar)
    error_log("DEBUG upsertConvention - has_360_raw: " . var_export($has_360_raw, true));
    error_log("DEBUG upsertConvention - has_360 formatado: " . var_export($has_360, true));

    // Verifica se já existe
    $res = execParams(
        $conn,
        "SELECT inc_convention_id FROM incentive.inc_convention WHERE inc_id = $1 LIMIT 1",
        [$inc_id],
        "Erro ao buscar convention"
    );

    if (pg_num_rows($res) > 0) {
        // UPDATE
        $row = pg_fetch_assoc($res);
        $conv_id = (int)$row['inc_convention_id'];
        
        execParams(
            $conn,
            "UPDATE incentive.inc_convention 
             SET description = $1, total_rooms = $2, has_360 = $3 
             WHERE inc_convention_id = $4",
            [$description, $total_rooms, $has_360, $conv_id],
            "Erro ao atualizar convention"
        );
        
        return $conv_id;
    } else {
        // INSERT
        $resInsert = execParams(
            $conn,
            "INSERT INTO incentive.inc_convention 
                (inc_id, description, total_rooms, has_360) 
             VALUES ($1, $2, $3, $4) 
             RETURNING inc_convention_id",
            [$inc_id, $description, $total_rooms, $has_360],
            "Erro ao inserir convention"
        );
        
        return (int)pg_fetch_result($resInsert, 0, 0);
    }
}

/**
 * Retorna o ID da convention (se existir)
 */
function getConventionId($conn, $inc_id) {
    $res = execParams(
        $conn,
        "SELECT inc_convention_id FROM incentive.inc_convention WHERE inc_id = $1 LIMIT 1",
        [$inc_id],
        "Erro ao buscar convention"
    );
    
    if (pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        return (int)$row['inc_convention_id'];
    }
    
    return null;
}

/**
 * Sincroniza salas de convenção
 */
function syncConventionRooms($conn, $inc_convention_id, $rooms) {
    if (!$inc_convention_id) {
        return;
    }

    execParams(
        $conn,
        "DELETE FROM incentive.inc_convention_room WHERE inc_convention_id = $1",
        [$inc_convention_id],
        "Erro ao limpar salas de convenção"
    );

    foreach ($rooms as $index => $room) {
        $name                = formatString($room['name'] ?? null);
        $area_m2             = formatNumeric($room['area_m2'] ?? null);
        $capacity_auditorium = formatInt($room['capacity_auditorium'] ?? null);
        $capacity_banquet    = formatInt($room['capacity_banquet'] ?? null);
        $capacity_classroom  = formatInt($room['capacity_classroom'] ?? null);
        $capacity_u_shape    = formatInt($room['capacity_u_shape'] ?? null);
        $notes               = formatString($room['notes'] ?? null);

        if (!$name) {
            throw new Exception("Sala de convenção {$index}: name obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_convention_room
                (inc_convention_id, name, area_m2, capacity_auditorium, 
                 capacity_banquet, capacity_classroom, capacity_u_shape, notes)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8)",
            [
                $inc_convention_id,
                $name,
                $area_m2,
                $capacity_auditorium,
                $capacity_banquet,
                $capacity_classroom,
                $capacity_u_shape,
                $notes
            ],
            "Erro ao inserir sala de convenção"
        );
    }
}

/**
 * Sincroniza notas
 */
function syncNotes($conn, $inc_id, $notes) {
    execParams(
        $conn,
        "DELETE FROM incentive.inc_note WHERE inc_id = $1",
        [$inc_id],
        "Erro ao limpar notas"
    );

    foreach ($notes as $index => $note) {
        $language = formatLanguage($note['language'] ?? null);
        $text     = formatString($note['note'] ?? null);

        if (!$text) {
            throw new Exception("Nota {$index}: note obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_note 
                (inc_id, language, note) 
             VALUES ($1, $2, $3)",
            [$inc_id, $language, $text],
            "Erro ao inserir nota"
        );
    }
}

// =========================================================
// PROCESSAMENTO DA REQUEST
// =========================================================

$request = getParam('request');
if (!$request) {
    response(["error" => "Parâmetro 'request' é obrigatório"], 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Responde OPTIONS (CORS preflight)
if ($method === 'OPTIONS') {
    response([], 204);
}

try {

    // =====================================================
    // LISTAR INCENTIVOS (com paginação, filtros e contagens)
    // =====================================================
   if ($request === 'listar_incentives') {
    if ($method !== 'GET') {
        response(["error" => "Método não permitido. Use GET"], 405);
    }

    // =============================================
    // Captura e sanitiza filtros
    // =============================================
    $filtro_nome   = getStringParam('filtro_nome');
    $filtro_status = getStringParam('filtro_status');
    $filtro_cidade = getStringParam('filtro_cidade');
    $filtro_pais   = getStringParam('filtro_pais');
    $filtro_ativo  = getParam('filtro_ativo', 'all');

    $page     = max(1, getIntParam('page', 1));
    $per_page = max(1, min(100, getIntParam('per_page', 30)));

    // Validação extra (opcional, mas recomendada)
    if ($per_page > 100) {
        $per_page = 100; // limite máximo rígido
    }

    $offset = ($page - 1) * $per_page;

    // =============================================
    // Monta cláusula WHERE e parâmetros
    // =============================================
    $where  = [];
    $params = [];
    $param_idx = 1;

    if ($filtro_nome !== null && $filtro_nome !== '') {
        $where[] = "p.inc_name ILIKE $" . $param_idx++;
        $params[] = '%' . trim($filtro_nome) . '%';
    }

    if ($filtro_status !== null && $filtro_status !== '') {
        $where[] = "p.inc_status = $" . $param_idx++;
        $params[] = $filtro_status;
    }

    if ($filtro_pais !== null && $filtro_pais !== '') {
        $where[] = "p.country_code = $" . $param_idx++;
        $params[] = strtoupper(trim($filtro_pais));
    }

    if ($filtro_cidade !== null && $filtro_cidade !== '') {
        $where[] = "p.city_name ILIKE $" . $param_idx++;
        $params[] = '%' . trim($filtro_cidade) . '%';
    }

    if ($filtro_ativo !== 'all') {
        $ativo = filter_var($filtro_ativo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($ativo !== null) {
            $where[] = "p.inc_is_active = $" . $param_idx++;
            $params[] = $ativo;  // true/false nativo (pg_query_params converte)
        }
        // Se filtro_ativo for inválido, ignora silenciosamente (ou pode logar)
    }

    $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // =============================================
    // Contagem total (para paginação)
    // =============================================
    $sql_count = "SELECT COUNT(*) AS total FROM incentive.inc_program p $where_sql";
    $res_count = pg_query_params($conn, $sql_count, $params);

    if (!$res_count) {
        error_log("Erro na contagem de incentivos: " . pg_last_error($conn));
        response(["error" => "Erro ao consultar total de registros"], 500);
    }

    $total = (int) pg_fetch_result($res_count, 0, 'total');

    // =============================================
    // Prepara parâmetros para a query paginada
    // =============================================
    $params_list = $params;
    $params_list[] = $per_page;
    $params_list[] = $offset;

    $limit_idx  = $param_idx++;
    $offset_idx = $param_idx++;

    // =============================================
    // Query principal com CTE + agregações JSON
    // =============================================
    $sql = "
        WITH base AS (
            SELECT
                p.inc_id,
                p.inc_name,
                p.inc_description,
                p.hotel_ref_id,
                p.hotel_name_snapshot,
                p.city_name,
                p.country_code,
                p.inc_status,
                p.inc_is_active,
                p.created_at,
                p.updated_at
            FROM incentive.inc_program p
            $where_sql
            ORDER BY p.inc_name ASC, p.inc_id ASC
            LIMIT \$$limit_idx OFFSET \$$offset_idx
        )
        SELECT
            b.*,

            COALESCE((
                SELECT json_agg(
                    json_build_object(
                        'inc_media_id',  m.inc_media_id,
                        'media_type',    m.media_type,
                        'media_url',     m.media_url,
                        'position',      m.position,
                        'is_active',     m.is_active
                    )
                    ORDER BY m.position ASC, m.inc_media_id ASC
                )
                FROM incentive.inc_media m
                WHERE m.inc_id = b.inc_id
            ), '[]'::json) AS media,

            COALESCE((
                SELECT json_agg(
                    json_build_object(
                        'inc_room_id',   rc.inc_room_id,
                        'room_name',     rc.room_name,
                        'quantity',      rc.quantity,
                        'notes',         rc.notes,
                        'position',      rc.position,
                        'is_active',     rc.is_active
                    )
                    ORDER BY rc.position ASC, rc.inc_room_id ASC
                )
                FROM incentive.inc_room_category rc
                WHERE rc.inc_id = b.inc_id
            ), '[]'::json) AS room_categories,

            COALESCE((
                SELECT json_agg(
                    json_build_object(
                        'inc_dining_id',   d.inc_dining_id,
                        'name',            d.name,
                        'description',     d.description,
                        'cuisine',         d.cuisine,
                        'capacity',        d.capacity,
                        'schedule',        d.schedule,
                        'is_michelin',     d.is_michelin,
                        'can_be_private',  d.can_be_private,
                        'image_url',       d.image_url,
                        'position',        d.position,
                        'is_active',       d.is_active
                    )
                    ORDER BY d.position ASC, d.inc_dining_id ASC
                )
                FROM incentive.inc_dining d
                WHERE d.inc_id = b.inc_id
            ), '[]'::json) AS dining,

            COALESCE((
                SELECT json_agg(
                    json_build_object(
                        'inc_facility_id', f.inc_facility_id,
                        'name',            f.name,
                        'icon',            f.icon,
                        'is_active',       f.is_active
                    )
                    ORDER BY f.inc_facility_id ASC
                )
                FROM incentive.inc_facility f
                WHERE f.inc_id = b.inc_id
            ), '[]'::json) AS facilities,

            COALESCE((
                SELECT json_agg(
                    json_build_object(
                        'inc_note_id', n.inc_note_id,
                        'language',    n.language,
                        'note',        n.note
                    )
                    ORDER BY n.inc_note_id ASC
                )
                FROM incentive.inc_note n
                WHERE n.inc_id = b.inc_id
            ), '[]'::json) AS notes,

            (
                SELECT CASE WHEN c.inc_convention_id IS NULL THEN NULL
                    ELSE json_build_object(
                        'inc_convention_id', c.inc_convention_id,
                        'description',       c.description,
                        'total_rooms',       c.total_rooms,
                        'has_360',           c.has_360,
                        'rooms', COALESCE((
                            SELECT json_agg(
                                json_build_object(
                                    'inc_room_id',          cr.inc_room_id,
                                    'name',                 cr.name,
                                    'area_m2',              cr.area_m2,
                                    'capacity_auditorium',  cr.capacity_auditorium,
                                    'capacity_banquet',     cr.capacity_banquet,
                                    'capacity_classroom',   cr.capacity_classroom,
                                    'capacity_u_shape',     cr.capacity_u_shape,
                                    'notes',                cr.notes
                                )
                                ORDER BY cr.inc_room_id ASC
                            )
                            FROM incentive.inc_convention_room cr
                            WHERE cr.inc_convention_id = c.inc_convention_id
                        ), '[]'::json)
                    )
                END
                FROM incentive.inc_convention c
                WHERE c.inc_id = b.inc_id
                LIMIT 1
            ) AS convention

        FROM base b
        ORDER BY b.inc_name ASC, b.inc_id ASC
    ";

    $result = pg_query_params($conn, $sql, $params_list);

    if (!$result) {
        error_log("Erro na query de listagem de incentivos: " . pg_last_error($conn));
        response(["error" => "Erro interno ao listar incentivos"], 500);
    }

    $rows = pg_fetch_all($result) ?: [];

    // =============================================
    // Normalização de tipos no PHP
    // =============================================
    foreach ($rows as &$row) {
        $row['inc_id']          = (int)$row['inc_id'];
        $row['hotel_ref_id']    = $row['hotel_ref_id'] ? (int)$row['hotel_ref_id'] : null;
        $row['inc_is_active']   = boolFromPg($row['inc_is_active']);

        // Decodifica JSONs agregados
        $row['media']           = json_decode($row['media']           ?? '[]', true) ?: [];
        $row['room_categories'] = json_decode($row['room_categories'] ?? '[]', true) ?: [];
        $row['dining']          = json_decode($row['dining']          ?? '[]', true) ?: [];
        $row['facilities']      = json_decode($row['facilities']      ?? '[]', true) ?: [];
        $row['notes']           = json_decode($row['notes']           ?? '[]', true) ?: [];
        $row['convention']      = $row['convention'] ? json_decode($row['convention'], true) : null;

        // Normaliza booleans dentro dos arrays (por segurança)
        foreach ($row['media'] as &$m) {
            $m['is_active'] = boolFromPg($m['is_active']);
        }
        foreach ($row['room_categories'] as &$rc) {
            $rc['is_active'] = boolFromPg($rc['is_active']);
        }
        foreach ($row['dining'] as &$d) {
            $d['is_active']     = boolFromPg($d['is_active']);
            $d['is_michelin']   = boolFromPg($d['is_michelin']);
            $d['can_be_private']= boolFromPg($d['can_be_private']);
        }
        foreach ($row['facilities'] as &$f) {
            $f['is_active'] = boolFromPg($f['is_active']);
        }
        if ($row['convention']) {
            $row['convention']['has_360'] = boolFromPg($row['convention']['has_360']);
            foreach ($row['convention']['rooms'] ?? [] as &$room) {
                // rooms não têm boolean, mas mantém por consistência
            }
        }
    }
    unset($row); // boa prática

    // =============================================
    // Resposta final
    // =============================================
    response([
        'success'    => true,
        'data'       => $rows,
        'pagination' => [
            'total'        => $total,
            'per_page'     => $per_page,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $per_page),
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $per_page, $total)
        ]
    ]);
}

    // =====================================================
    // BUSCAR 1 INCENTIVO (completo com relacionamentos)
    // =====================================================
  elseif ($request === 'buscar_incentive') {
    if ($method !== 'GET') {
        response(["error" => "Método não permitido. Use GET"], 405);
    }

    $id = getIntParam('id');
    if (!$id || $id <= 0) {
        response(["error" => "ID do incentivo inválido ou ausente"], 400);
    }

    // =============================================
    // Programa principal
    // =============================================
    $sql_program = "SELECT * FROM incentive.inc_program WHERE inc_id = $1 LIMIT 1";
    $res_program = pg_query_params($conn, $sql_program, [$id]);

    if (!$res_program) {
        error_log("Erro SQL ao buscar inc_program ID $id: " . pg_last_error($conn));
        response(["error" => "Erro interno ao consultar o incentivo"], 500);
    }

    if (pg_num_rows($res_program) === 0) {
        response(["error" => "Incentivo não encontrado"], 404);
    }

    $p = pg_fetch_assoc($res_program);

    $program = [
        'inc_id'              => (int)$p['inc_id'],
        'inc_name'            => $p['inc_name'] ?? '',
        'inc_description'     => $p['inc_description'] ?? '',
        'hotel_ref_id'        => $p['hotel_ref_id'] ? (int)$p['hotel_ref_id'] : null,
        'hotel_name_snapshot' => $p['hotel_name_snapshot'] ?? '',
        'city_name'           => $p['city_name'] ?? '',
        'country_code'        => $p['country_code'] ?? null,
        'inc_status'          => $p['inc_status'] ?? 'active',
        'inc_is_active'       => boolFromPg($p['inc_is_active']),
        'created_at'          => $p['created_at'] ?? null,
        'updated_at'          => $p['updated_at'] ?? null,
    ];

    // =============================================
    // Mídias
    // =============================================
    $sql_media = "
        SELECT inc_media_id, media_type, media_url, position, is_active
        FROM incentive.inc_media
        WHERE inc_id = $1
        ORDER BY position ASC, inc_media_id ASC
    ";
    $res_media = pg_query_params($conn, $sql_media, [$id]);
    $media = pg_fetch_all($res_media) ?: [];

    foreach ($media as &$m) {
        $m['inc_media_id'] = (int)$m['inc_media_id'];
        $m['position']     = (int)($m['position'] ?? 0);
        $m['is_active']    = boolFromPg($m['is_active']);
    }
    unset($m);

    // =============================================
    // Categorias de quartos
    // =============================================
    $sql_rooms = "
        SELECT inc_room_id, room_name, quantity, notes, position, is_active
        FROM incentive.inc_room_category
        WHERE inc_id = $1
        ORDER BY position ASC, inc_room_id ASC
    ";
    $res_rooms = pg_query_params($conn, $sql_rooms, [$id]);
    $room_categories = pg_fetch_all($res_rooms) ?: [];

    foreach ($room_categories as &$r) {
        $r['inc_room_id'] = (int)$r['inc_room_id'];
        $r['quantity']    = $r['quantity'] !== null ? (int)$r['quantity'] : null;
        $r['position']    = (int)($r['position'] ?? 0);
        $r['is_active']   = boolFromPg($r['is_active']);
    }
    unset($r);

    // =============================================
    // Dining
    // =============================================
    $sql_dining = "
        SELECT inc_dining_id, name, description, cuisine, capacity, schedule,
               is_michelin, can_be_private, image_url, position, is_active
        FROM incentive.inc_dining
        WHERE inc_id = $1
        ORDER BY position ASC, inc_dining_id ASC
    ";
    $res_dining = pg_query_params($conn, $sql_dining, [$id]);
    $dining = pg_fetch_all($res_dining) ?: [];

    foreach ($dining as &$d) {
        $d['inc_dining_id']   = (int)$d['inc_dining_id'];
        $d['capacity']        = $d['capacity'] !== null ? (int)$d['capacity'] : null;
        $d['is_michelin']     = boolFromPg($d['is_michelin']);
        $d['can_be_private']  = boolFromPg($d['can_be_private']);
        $d['position']        = (int)($d['position'] ?? 0);
        $d['is_active']       = boolFromPg($d['is_active']);
    }
    unset($d);

    // =============================================
    // Facilities
    // =============================================
    $sql_fac = "
        SELECT inc_facility_id, name, icon, is_active
        FROM incentive.inc_facility
        WHERE inc_id = $1
        ORDER BY inc_facility_id ASC
    ";
    $res_fac = pg_query_params($conn, $sql_fac, [$id]);
    $facilities = pg_fetch_all($res_fac) ?: [];

    foreach ($facilities as &$f) {
        $f['inc_facility_id'] = (int)$f['inc_facility_id'];
        $f['is_active']       = boolFromPg($f['is_active']);
    }
    unset($f);

    // =============================================
    // Convention + Salas
    // =============================================
    $sql_conv = "
        SELECT inc_convention_id, description, total_rooms, has_360
        FROM incentive.inc_convention
        WHERE inc_id = $1
        LIMIT 1
    ";
    $res_conv = pg_query_params($conn, $sql_conv, [$id]);

    $convention = null;
    $convention_rooms = [];

    if ($res_conv && pg_num_rows($res_conv) > 0) {
        $c = pg_fetch_assoc($res_conv);

        $convention = [
            'inc_convention_id' => (int)$c['inc_convention_id'],
            'description'       => $c['description'] ?? '',
            'total_rooms'       => $c['total_rooms'] !== null ? (int)$c['total_rooms'] : null,
            'has_360'           => boolFromPg($c['has_360']),
            'rooms'             => [] // será preenchido abaixo
        ];

        $conv_id = $convention['inc_convention_id'];

        $sql_conv_rooms = "
            SELECT inc_room_id, name, area_m2, capacity_auditorium, capacity_banquet,
                   capacity_classroom, capacity_u_shape, notes
            FROM incentive.inc_convention_room
            WHERE inc_convention_id = $1
            ORDER BY inc_room_id ASC
        ";
        $res_conv_rooms = pg_query_params($conn, $sql_conv_rooms, [$conv_id]);
        $convention_rooms = pg_fetch_all($res_conv_rooms) ?: [];

        foreach ($convention_rooms as &$cr) {
            $cr['inc_room_id']          = (int)$cr['inc_room_id'];
            $cr['area_m2']              = $cr['area_m2'] !== null ? (float)$cr['area_m2'] : null;
            $cr['capacity_auditorium']  = $cr['capacity_auditorium'] !== null ? (int)$cr['capacity_auditorium'] : null;
            $cr['capacity_banquet']     = $cr['capacity_banquet'] !== null ? (int)$cr['capacity_banquet'] : null;
            $cr['capacity_classroom']   = $cr['capacity_classroom'] !== null ? (int)$cr['capacity_classroom'] : null;
            $cr['capacity_u_shape']     = $cr['capacity_u_shape'] !== null ? (int)$cr['capacity_u_shape'] : null;
        }
        unset($cr);

        $convention['rooms'] = $convention_rooms;
    }

    // =============================================
    // Notes
    // =============================================
    $sql_notes = "
        SELECT inc_note_id, language, note
        FROM incentive.inc_note
        WHERE inc_id = $1
        ORDER BY inc_note_id ASC
    ";
    $res_notes = pg_query_params($conn, $sql_notes, [$id]);
    $notes = pg_fetch_all($res_notes) ?: [];

    foreach ($notes as &$n) {
        $n['inc_note_id'] = (int)$n['inc_note_id'];
    }
    unset($n);

    // =============================================
    // Resposta final
    // =============================================
    response([
        'success' => true,
        'data' => [
            'inc_id'              => $program['inc_id'],
            'inc_name'            => $program['inc_name'],
            'inc_description'     => $program['inc_description'],
            'hotel_ref_id'        => $program['hotel_ref_id'],
            'hotel_name_snapshot' => $program['hotel_name_snapshot'],
            'city_name'           => $program['city_name'],
            'country_code'        => $program['country_code'],
            'inc_status'          => $program['inc_status'],
            'inc_is_active'       => $program['inc_is_active'],
            'created_at'          => $program['created_at'],
            'updated_at'          => $program['updated_at'],
            'media'               => $media,
            'room_categories'     => $room_categories,
            'dining'              => $dining,
            'facilities'          => $facilities,
            'convention'          => $convention ?? [
                'inc_convention_id' => null,
                'description'       => '',
                'total_rooms'       => null,
                'has_360'           => false,
                'rooms'             => []
            ],
            'notes'               => $notes,
        ]
    ]);
}

    // =====================================================
    // CRIAR INCENTIVO (completo)
    // =====================================================
    elseif ($request === 'criar_incentive') {
        if ($method !== 'POST') {
            response(["error" => "Use método POST"], 405);
        }

        $user_data = null;
        requireBearerToken($conn, $user_data, $cod_sis ?? null);

        if (empty($input)) {
            response(["error" => "Body JSON obrigatório"], 400);
        }

        pg_query($conn, "BEGIN");
        try {
            // Valida campos obrigatórios
            $inc_name = formatString($input['inc_name'] ?? '');
            if (!$inc_name) {
                throw new Exception("O campo inc_name é obrigatório");
            }

            // Prepara campos do programa
            $fields = [
                'inc_name'            => $inc_name,
                'inc_description'     => formatString($input['inc_description'] ?? null),
                'hotel_ref_id'        => formatInt($input['hotel_ref_id'] ?? null),
                'hotel_name_snapshot' => formatString($input['hotel_name_snapshot'] ?? null),
                'city_name'           => formatString($input['city_name'] ?? null),
                'country_code'        => formatCountry($input['country_code'] ?? null),
                'inc_status'          => formatStatus($input['inc_status'] ?? 'active'),
                'inc_is_active'       => formatBoolean($input['inc_is_active'] ?? true),
            ];

            // Monta INSERT
            $cols = $vals = $params = [];
            $idx = 1;
            foreach ($fields as $col => $val) {
                $cols[]   = $col;
                $vals[]   = '$' . $idx++;
                $params[] = $val;
            }

            $sql = "
                INSERT INTO incentive.inc_program (" . implode(', ', $cols) . ")
                VALUES (" . implode(', ', $vals) . ")
                RETURNING inc_id
            ";
            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $newId = (int)pg_fetch_result($result, 0, 0);

            // Sincroniza relacionamentos
            if (isset($input['media']) && is_array($input['media'])) {
                syncMedia($conn, $newId, $input['media']);
            }

            if (isset($input['room_categories']) && is_array($input['room_categories'])) {
                syncRoomCategories($conn, $newId, $input['room_categories']);
            }

            if (isset($input['dining']) && is_array($input['dining'])) {
                syncDining($conn, $newId, $input['dining']);
            }

            if (isset($input['facilities']) && is_array($input['facilities'])) {
                syncFacilities($conn, $newId, $input['facilities']);
            }

            // Convention e salas
            $conv_id = null;
            if (isset($input['convention'])) {
                $conv_id = upsertConvention($conn, $newId, $input['convention']);
            }

            if (isset($input['convention_rooms']) && is_array($input['convention_rooms'])) {
                if (!$conv_id) {
                    // Cria convention vazio se necessário
                    $conv_id = upsertConvention($conn, $newId, [
                        'description' => '',
                        'total_rooms' => null,
                        'has_360' => false
                    ]);
                }
                syncConventionRooms($conn, $conv_id, $input['convention_rooms']);
            }

            if (isset($input['notes']) && is_array($input['notes'])) {
                syncNotes($conn, $newId, $input['notes']);
            }

            pg_query($conn, "COMMIT");

            response([
                "success" => true,
                "message" => "Incentivo criado com sucesso!",
                "inc_id"  => $newId
            ], 201);

        } catch (Exception $e) {
            pg_query($conn, "ROLLBACK");
            response(["error" => $e->getMessage()], 400);
        }
    }

    // =====================================================
    // ATUALIZAR INCENTIVO (completo)
    // =====================================================
    elseif ($request === 'atualizar_incentive') {
        if ($method !== 'PUT') {
            response(["error" => "Use método PUT"], 405);
        }

        $id = getIntParam('id');
        if (!$id) {
            response(["error" => "ID obrigatório"], 400);
        }

        $user_data = null;
        requireBearerToken($conn, $user_data, $cod_sis ?? null);

        if (empty($input)) {
            response(["error" => "Nenhum campo para atualizar"], 400);
        }

        pg_query($conn, "BEGIN");
        try {
            // Verifica se existe
            $checkSql = "SELECT inc_id FROM incentive.inc_program WHERE inc_id = $1";
            $checkRes = pg_query_params($conn, $checkSql, [$id]);
            if (!$checkRes || pg_num_rows($checkRes) === 0) {
                throw new Exception("Incentivo não encontrado");
            }

            // Campos permitidos para UPDATE
            $allowed = [
                'inc_name', 'inc_description', 'hotel_ref_id', 
                'hotel_name_snapshot', 'city_name', 'country_code', 
                'inc_status', 'inc_is_active'
            ];

            $updates = [];
            $params  = [];
            $idx     = 1;

            foreach ($input as $key => $val) {
                if (!in_array($key, $allowed, true)) continue;

                $formatted = null;
                switch ($key) {
                    case 'hotel_ref_id':  $formatted = formatInt($val); break;
                    case 'inc_is_active': $formatted = formatBoolean($val); break;
                    case 'inc_status':    $formatted = formatStatus($val); break;
                    case 'country_code':  $formatted = formatCountry($val); break;
                    default:              $formatted = formatString($val);
                }

                $updates[] = "$key = $" . $idx++;
                $params[]  = $formatted;
            }

            // Atualiza programa (se houver campos)
            if (!empty($updates)) {
                $params[] = $id;
                $sql = "
                    UPDATE incentive.inc_program
                    SET " . implode(', ', $updates) . ", updated_at = NOW()
                    WHERE inc_id = $" . $idx
                ;
                $result = pg_query_params($conn, $sql, $params);
                if (!$result) {
                    throw new Exception(pg_last_error($conn));
                }
            }

            // Sincroniza relacionamentos (se fornecidos)
            if (isset($input['media']) && is_array($input['media'])) {
                syncMedia($conn, $id, $input['media']);
            }

            if (isset($input['room_categories']) && is_array($input['room_categories'])) {
                syncRoomCategories($conn, $id, $input['room_categories']);
            }

            if (isset($input['dining']) && is_array($input['dining'])) {
                syncDining($conn, $id, $input['dining']);
            }

            if (isset($input['facilities']) && is_array($input['facilities'])) {
                syncFacilities($conn, $id, $input['facilities']);
            }

            // Convention
            $conv_id = getConventionId($conn, $id);
            if (isset($input['convention'])) {
                $conv_id = upsertConvention($conn, $id, $input['convention']);
            }

            if (isset($input['convention_rooms']) && is_array($input['convention_rooms'])) {
                if (!$conv_id) {
                    $conv_id = upsertConvention($conn, $id, [
                        'description' => '',
                        'total_rooms' => null,
                        'has_360' => false
                    ]);
                }
                syncConventionRooms($conn, $conv_id, $input['convention_rooms']);
            }

            if (isset($input['notes']) && is_array($input['notes'])) {
                syncNotes($conn, $id, $input['notes']);
            }

            pg_query($conn, "COMMIT");

            response([
                "success" => true,
                "message" => "Incentivo atualizado com sucesso!",
                "inc_id"  => $id
            ]);

        } catch (Exception $e) {
            pg_query($conn, "ROLLBACK");
            response(["error" => $e->getMessage()], 400);
        }
    }

    // =====================================================
    // EXCLUIR INCENTIVO
    // =====================================================
    elseif ($request === 'excluir_incentive') {
        if ($method !== 'DELETE') {
            response(["error" => "Use método DELETE"], 405);
        }

        $id = getIntParam('id');
        if (!$id) {
            response(["error" => "ID obrigatório"], 400);
        }

        $user_data = null;
        requireBearerToken($conn, $user_data, $cod_sis ?? null);

        $sql = "DELETE FROM incentive.inc_program WHERE inc_id = $1";
        $result = pg_query_params($conn, $sql, [$id]);

        if (!$result || pg_affected_rows($result) == 0) {
            response(["error" => "Incentivo não encontrado"], 404);
        }

        response([
            "success" => true,
            "message" => "Incentivo excluído com sucesso"
        ]);
    }

    // =====================================================
    // ROTA INVÁLIDA
    // =====================================================
    else {
        response(["error" => "Request inválido"], 400);
    }

} catch (Exception $e) {
    if (isset($conn)) {
        @pg_query($conn, "ROLLBACK");
    }
    error_log("Erro API Incentivos: " . $e->getMessage());
    response(["error" => "Erro interno no servidor: " . $e->getMessage()], 500);
}