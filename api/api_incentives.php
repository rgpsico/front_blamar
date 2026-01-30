<?php
/**
 * API RESTful - incentive (CRUD inc_program + leitura com relacionamentos)
 *
 * Endpoints:
 *   GET    ?request=listar_incentives
 *   GET    ?request=buscar_incentive&id=XXX
 *   POST   ?request=criar_incentive
 *   PUT    ?request=atualizar_incentive&id=XXX
 *   DELETE ?request=excluir_incentive&id=XXX
 *
 * Autenticacao (rotas escrita): Bearer Token
 */

ini_set('display_errors', 1);
ini_set('log_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, authorization, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once '../util/connection.php';
require_once 'middleware.php'; // validarToken($conn, $cod_sis, $token, $user_data)

// =============================================
// Helpers
// =============================================
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
    return (is_numeric($v)) ? (int)$v : $default;
}

function formatString($val)   { return ($val === '' || $val === null) ? null : trim($val); }
function formatInt($val)      { return is_numeric($val) ? (int)$val : null; }
function formatBoolean($val)  { return ($val === null) ? null : (filter_var($val, FILTER_VALIDATE_BOOLEAN) ? true : false); }

function formatCountry($val) {
    $v = formatString($val);
    if ($v === null) return null;
    $v = strtoupper($v);
    return (strlen($v) === 2) ? $v : null;
}

function formatStatus($val) {
    $allowed = ['active','inactive','draft','archived'];
    return in_array($val, $allowed, true) ? $val : 'active';
}

function formatLanguage($val) {
    $v = formatString($val);
    if ($v === null) return null;
    $v = strtoupper($v);
    return (strlen($v) === 2) ? $v : null;
}

function formatNumeric($val) {
    if ($val === null || $val === '') return null;
    if (!is_numeric($val)) return null;
    return $val;
}

function boolFromPg($v) {
    // Postgres vem 't'/'f' ou boolean
    if ($v === true || $v === 't' || $v === 'true' || $v === 1 || $v === '1') return true;
    return false;
}

function requireBearerToken($conn, &$user_data, $cod_sis = null) {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['authorization'] ?? $headers['Authorization'] ?? '';

    if (strpos($auth, 'Bearer ') !== 0) {
        response(["error" => "Token Bearer obrigatorio"], 401);
    }

    $token = trim(substr($auth, 7));

    if (!validarToken($conn, $cod_sis, $token, $user_data)) {
        response(["error" => "Token invalido ou expirado"], 401);
    }

    return $token;
}

// =============================================
// Helpers de midia
// =============================================
function formatMediaType($val) {
    $v = formatString($val);
    if ($v === null) return null;
    $v = strtolower($v);

    $allowed = ['banner','gallery','video','map'];
    return in_array($v, $allowed, true) ? $v : null;
}

function formatPosition($val) {
    if ($val === null || $val === '') return null;
    if (!is_numeric($val)) return null;
    $n = (int)$val;
    return max(0, $n);
}

function execParams($conn, $sql, $params, $errorMessage) {
    $res = pg_query_params($conn, $sql, $params);
    if (!$res) throw new Exception($errorMessage . ': ' . pg_last_error($conn));
    return $res;
}

function syncMedia($conn, $inc_id, $mediaList) {
    execParams($conn, "DELETE FROM incentive.inc_media WHERE inc_id = $1", [$inc_id], "Erro ao limpar midias");

    foreach ($mediaList as $index => $media) {
        $media_type = formatMediaType($media['media_type'] ?? null);
        $media_url  = formatString($media['media_url'] ?? null);
        $position   = formatPosition($media['position'] ?? 0) ?? 0;
        $is_active  = formatBoolean($media['is_active'] ?? true);

        if (!$media_type) throw new Exception("Midia {$index}: media_type invalido");
        if (!$media_url)  throw new Exception("Midia {$index}: media_url obrigatorio");

        execParams(
            $conn,
            "INSERT INTO incentive.inc_media (inc_id, media_type, media_url, position, is_active) VALUES ($1, $2, $3, $4, $5)",
            [$inc_id, $media_type, $media_url, $position, $is_active ?? true],
            "Erro ao inserir midia"
        );
    }
}

function syncRoomCategories($conn, $inc_id, $roomCategories) {
    execParams($conn, "DELETE FROM incentive.inc_room_category WHERE inc_id = $1", [$inc_id], "Erro ao limpar categorias de quartos");

    foreach ($roomCategories as $index => $room) {
        $room_name = formatString($room['room_name'] ?? null);
        $quantity  = formatInt($room['quantity'] ?? null);
        $notes     = formatString($room['notes'] ?? null);
        $position  = formatPosition($room['position'] ?? 0) ?? 0;
        $is_active = formatBoolean($room['is_active'] ?? true);

        if (!$room_name) throw new Exception("Quarto {$index}: room_name obrigatorio");

        execParams(
            $conn,
            "INSERT INTO incentive.inc_room_category (inc_id, room_name, quantity, notes, position, is_active) VALUES ($1, $2, $3, $4, $5, $6)",
            [$inc_id, $room_name, $quantity, $notes, $position, $is_active ?? true],
            "Erro ao inserir categoria de quarto"
        );
    }
}

function syncDining($conn, $inc_id, $diningList) {
    execParams($conn, "DELETE FROM incentive.inc_dining WHERE inc_id = $1", [$inc_id], "Erro ao limpar dining");

    foreach ($diningList as $index => $dining) {
        $name            = formatString($dining['name'] ?? null);
        $description     = formatString($dining['description'] ?? null);
        $cuisine         = formatString($dining['cuisine'] ?? null);
        $capacity        = formatInt($dining['capacity'] ?? null);
        $schedule        = formatString($dining['schedule'] ?? null);
        $is_michelin     = formatBoolean($dining['is_michelin'] ?? false);
        $can_be_private  = formatBoolean($dining['can_be_private'] ?? false);
        $image_url       = formatString($dining['image_url'] ?? null);
        $position        = formatPosition($dining['position'] ?? 0) ?? 0;
        $is_active       = formatBoolean($dining['is_active'] ?? true);

        if (!$name) throw new Exception("Dining {$index}: name obrigatorio");

        execParams(
            $conn,
            "INSERT INTO incentive.inc_dining (inc_id, name, description, cuisine, capacity, schedule, is_michelin, can_be_private, image_url, position, is_active)\n             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)",
            [
                $inc_id,
                $name,
                $description,
                $cuisine,
                $capacity,
                $schedule,
                $is_michelin ?? false,
                $can_be_private ?? false,
                $image_url,
                $position,
                $is_active ?? true
            ],
            "Erro ao inserir dining"
        );
    }
}

function syncFacilities($conn, $inc_id, $facilities) {
    execParams($conn, "DELETE FROM incentive.inc_facility WHERE inc_id = $1", [$inc_id], "Erro ao limpar facilities");

    foreach ($facilities as $index => $facility) {
        $name      = formatString($facility['name'] ?? null);
        $icon      = formatString($facility['icon'] ?? null);
        $is_active = formatBoolean($facility['is_active'] ?? true);

        if (!$name) throw new Exception("Facility {$index}: name obrigatorio");

        execParams(
            $conn,
            "INSERT INTO incentive.inc_facility (inc_id, name, icon, is_active) VALUES ($1, $2, $3, $4)",
            [$inc_id, $name, $icon, $is_active ?? true],
            "Erro ao inserir facility"
        );
    }
}
function upsertConvention($conn, $inc_id, $convention) {
    if ($convention === null) {
        execParams($conn, "DELETE FROM incentive.inc_convention WHERE inc_id = $1", [$inc_id], "Erro ao excluir convention");
        return null;
    }

    $description = formatString($convention['description'] ?? null);
    $total_rooms = formatInt($convention['total_rooms'] ?? null);
    $has_360     = formatBoolean($convention['has_360'] ?? false);

    $res = execParams(
        $conn,
        "SELECT inc_convention_id FROM incentive.inc_convention WHERE inc_id = $1 LIMIT 1",
        [$inc_id],
        "Erro ao buscar convention"
    );

    if (pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        $conv_id = (int)$row['inc_convention_id'];
        execParams(
            $conn,
            "UPDATE incentive.inc_convention SET description = $1, total_rooms = $2, has_360 = $3 WHERE inc_convention_id = $4",
            [$description, $total_rooms, $has_360 ?? false, $conv_id],
            "Erro ao atualizar convention"
        );
        return $conv_id;
    }

    $resInsert = execParams(
        $conn,
        "INSERT INTO incentive.inc_convention (inc_id, description, total_rooms, has_360) VALUES ($1, $2, $3, $4) RETURNING inc_convention_id",
        [$inc_id, $description, $total_rooms, $has_360 ?? false],
        "Erro ao inserir convention"
    );
    return (int) pg_fetch_result($resInsert, 0, 0);
}

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

function syncConventionRooms($conn, $inc_convention_id, $rooms) {
    if (!$inc_convention_id) return;

    execParams(
        $conn,
        "DELETE FROM incentive.inc_convention_room WHERE inc_convention_id = $1",
        [$inc_convention_id],
        "Erro ao limpar salas"
    );

    foreach ($rooms as $index => $room) {
        $name                = formatString($room['name'] ?? null);
        $area_m2             = formatNumeric($room['area_m2'] ?? null);
        $capacity_auditorium = formatInt($room['capacity_auditorium'] ?? null);
        $capacity_banquet    = formatInt($room['capacity_banquet'] ?? null);
        $capacity_classroom  = formatInt($room['capacity_classroom'] ?? null);
        $capacity_u_shape    = formatInt($room['capacity_u_shape'] ?? null);
        $notes               = formatString($room['notes'] ?? null);

        if (!$name) throw new Exception("Sala {$index}: name obrigatorio");

        execParams(
            $conn,
            "INSERT INTO incentive.inc_convention_room\n                (inc_convention_id, name, area_m2, capacity_auditorium, capacity_banquet, capacity_classroom, capacity_u_shape, notes)\n             VALUES ($1, $2, $3, $4, $5, $6, $7, $8)",
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
            "Erro ao inserir sala"
        );
    }
}

function syncNotes($conn, $inc_id, $notes) {
    execParams($conn, "DELETE FROM incentive.inc_note WHERE inc_id = $1", [$inc_id], "Erro ao limpar notas");

    foreach ($notes as $index => $note) {
        $language = formatLanguage($note['language'] ?? null);
        $text     = formatString($note['note'] ?? null);

        if (!$text) throw new Exception("Nota {$index}: note obrigatorio");

        execParams(
            $conn,
            "INSERT INTO incentive.inc_note (inc_id, language, note) VALUES ($1, $2, $3)",
            [$inc_id, $language, $text],
            "Erro ao inserir nota"
        );
    }
}

// =============================================
// Request parsing
// =============================================
$request = getParam('request');
if (!$request) response(["error" => "Parametro 'request' e obrigatorio"], 400);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if ($method === 'OPTIONS') response([], 204);

try {

    switch ($request) {

        // ======================================
        // LISTAR (resumo) - com contagens e 1 banner opcional
        // ======================================
        case 'listar_incentives':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $filtro_nome   = getStringParam('filtro_nome');
            $filtro_status = getStringParam('filtro_status');
            $filtro_cidade = getStringParam('filtro_cidade');
            $filtro_pais   = getStringParam('filtro_pais');
            $filtro_ativo  = getParam('filtro_ativo', 'all');

            $page     = max(1, getIntParam('page', 1));
            $per_page = max(1, min(100, getIntParam('per_page', 30)));
            $offset   = ($page - 1) * $per_page;

            $where  = [];
            $params = [];
            $idx    = 1;

            if ($filtro_nome) {
                $where[]  = "p.inc_name ILIKE $" . $idx++;
                $params[] = "%{$filtro_nome}%";
            }
            if ($filtro_status) {
                $where[]  = "p.inc_status = $" . $idx++;
                $params[] = $filtro_status;
            }
            if ($filtro_pais) {
                $where[]  = "p.country_code = $" . $idx++;
                $params[] = strtoupper($filtro_pais);
            }
            if ($filtro_cidade) {
                $where[]  = "p.city_name ILIKE $" . $idx++;
                $params[] = "%{$filtro_cidade}%";
            }
            if ($filtro_ativo !== 'all') {
                $ativo = filter_var($filtro_ativo, FILTER_VALIDATE_BOOLEAN) ? true : false;
                $where[]  = "p.inc_is_active = $" . $idx++;
                $params[] = $ativo;
            }

            $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

            $sql_count = "SELECT COUNT(*) AS total FROM incentive.inc_program p {$where_sql}";
            $res_count = pg_query_params($conn, $sql_count, $params);
            if (!$res_count) throw new Exception(pg_last_error($conn));
            $total = (int) pg_fetch_result($res_count, 0, 'total');

            $params_list = $params;
            $params_list[] = $per_page;
            $params_list[] = $offset;

            $limitParam  = '$' . ($idx++);
            $offsetParam = '$' . ($idx++);

            $sql = "
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
                    p.updated_at,

                    (SELECT COUNT(*) FROM incentive.inc_media m WHERE m.inc_id = p.inc_id AND m.is_active = TRUE) AS media_count,
                    (SELECT COUNT(*) FROM incentive.inc_room_category rc WHERE rc.inc_id = p.inc_id AND rc.is_active = TRUE) AS room_category_count,
                    (SELECT COUNT(*) FROM incentive.inc_dining d WHERE d.inc_id = p.inc_id AND d.is_active = TRUE) AS dining_count,
                    (SELECT COUNT(*) FROM incentive.inc_facility f WHERE f.inc_id = p.inc_id AND f.is_active = TRUE) AS facility_count,
                    (SELECT COUNT(*) FROM incentive.inc_note n WHERE n.inc_id = p.inc_id) AS note_count,

                    (
                        SELECT m.media_url
                        FROM incentive.inc_media m
                        WHERE m.inc_id = p.inc_id
                          AND m.is_active = TRUE
                          AND m.media_type = 'banner'
                        ORDER BY m.position ASC, m.inc_media_id ASC
                        LIMIT 1
                    ) AS banner_url

                FROM incentive.inc_program p
                {$where_sql}
                ORDER BY p.inc_name, p.inc_id
                LIMIT {$limitParam} OFFSET {$offsetParam}
            ";

            $result = pg_query_params($conn, $sql, $params_list);
            if (!$result) throw new Exception(pg_last_error($conn));

            $rows = pg_fetch_all($result) ?: [];
            foreach ($rows as &$row) {
                $row['inc_id']             = (int)$row['inc_id'];
                $row['hotel_ref_id']       = $row['hotel_ref_id'] !== null ? (int)$row['hotel_ref_id'] : null;
                $row['inc_is_active']      = boolFromPg($row['inc_is_active']);
                $row['media_count']        = (int)$row['media_count'];
                $row['room_category_count']= (int)$row['room_category_count'];
                $row['dining_count']       = (int)$row['dining_count'];
                $row['facility_count']     = (int)$row['facility_count'];
                $row['note_count']         = (int)$row['note_count'];
            }

            response([
                'data' => $rows,
                'pagination' => [
                    'total'        => $total,
                    'per_page'     => $per_page,
                    'current_page' => $page,
                    'last_page'    => (int) ceil($total / $per_page)
                ]
            ]);
            break;

        // ======================================
        // BUSCAR 1 (com relacionamentos)
        // ======================================
        case 'buscar_incentive':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatorio"], 400);

            $sql_program = "SELECT * FROM incentive.inc_program WHERE inc_id = $1 LIMIT 1";
            $res_program = pg_query_params($conn, $sql_program, [$id]);
            if (!$res_program || pg_num_rows($res_program) === 0) {
                response(["error" => "Programa nao encontrado"], 404);
            }
            $p = pg_fetch_assoc($res_program);

            $program = [
                'inc_id'              => (int)$p['inc_id'],
                'inc_name'            => $p['inc_name'],
                'inc_description'     => $p['inc_description'],
                'hotel_ref_id'        => $p['hotel_ref_id'] ? (int)$p['hotel_ref_id'] : null,
                'hotel_name_snapshot' => $p['hotel_name_snapshot'],
                'city_name'           => $p['city_name'],
                'country_code'        => $p['country_code'],
                'inc_status'          => $p['inc_status'],
                'inc_is_active'       => boolFromPg($p['inc_is_active']),
                'created_at'          => $p['created_at'],
                'updated_at'          => $p['updated_at'],
            ];

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
                $m['position']     = (int)$m['position'];
                $m['is_active']    = boolFromPg($m['is_active']);
            }

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
                $r['position']    = (int)$r['position'];
                $r['is_active']   = boolFromPg($r['is_active']);
            }

            $sql_dining = "
                SELECT
                    inc_dining_id, name, description, cuisine, capacity, schedule,
                    is_michelin, can_be_private, image_url, position, is_active
                FROM incentive.inc_dining
                WHERE inc_id = $1
                ORDER BY position ASC, inc_dining_id ASC
            ";
            $res_dining = pg_query_params($conn, $sql_dining, [$id]);
            $dining = pg_fetch_all($res_dining) ?: [];
            foreach ($dining as &$d) {
                $d['inc_dining_id']  = (int)$d['inc_dining_id'];
                $d['capacity']       = $d['capacity'] !== null ? (int)$d['capacity'] : null;
                $d['is_michelin']    = boolFromPg($d['is_michelin']);
                $d['can_be_private'] = boolFromPg($d['can_be_private']);
                $d['position']       = (int)$d['position'];
                $d['is_active']      = boolFromPg($d['is_active']);
            }

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
                    'description'       => $c['description'],
                    'total_rooms'       => $c['total_rooms'] !== null ? (int)$c['total_rooms'] : null,
                    'has_360'           => boolFromPg($c['has_360']),
                ];

                $sql_conv_rooms = "
                    SELECT
                        inc_room_id, name, area_m2,
                        capacity_auditorium, capacity_banquet, capacity_classroom, capacity_u_shape,
                        notes
                    FROM incentive.inc_convention_room
                    WHERE inc_convention_id = $1
                    ORDER BY inc_room_id ASC
                ";
                $res_conv_rooms = pg_query_params($conn, $sql_conv_rooms, [$convention['inc_convention_id']]);
                $convention_rooms = pg_fetch_all($res_conv_rooms) ?: [];
                foreach ($convention_rooms as &$cr) {
                    $cr['inc_room_id'] = (int)$cr['inc_room_id'];
                    $cr['capacity_auditorium'] = $cr['capacity_auditorium'] !== null ? (int)$cr['capacity_auditorium'] : null;
                    $cr['capacity_banquet']    = $cr['capacity_banquet'] !== null ? (int)$cr['capacity_banquet'] : null;
                    $cr['capacity_classroom']  = $cr['capacity_classroom'] !== null ? (int)$cr['capacity_classroom'] : null;
                    $cr['capacity_u_shape']    = $cr['capacity_u_shape'] !== null ? (int)$cr['capacity_u_shape'] : null;
                }
            }

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

            response([
                'program' => $program,
                'relations' => [
                    'media'            => $media,
                    'room_categories'   => $room_categories,
                    'dining'            => $dining,
                    'facilities'        => $facilities,
                    'convention'        => $convention,
                    'convention_rooms'  => $convention_rooms,
                    'notes'             => $notes,
                ]
            ]);
            break;
        // ======================================
        // CRIAR (inc_program + relacionamentos opcionais)
        // ======================================
        case 'criar_incentive':
            if ($method !== 'POST') response(["error" => "Use POST"], 405);

            $user_data = null;
            requireBearerToken($conn, $user_data, $cod_sis ?? null);

            if (empty($input)) response(["error" => "Body JSON obrigatorio"], 400);

            pg_query($conn, "BEGIN");
            try {
                $fields = [
                    'inc_name'            => formatString($input['inc_name'] ?? ''),
                    'inc_description'     => formatString($input['inc_description'] ?? null),
                    'hotel_ref_id'        => formatInt($input['hotel_ref_id'] ?? null),
                    'hotel_name_snapshot' => formatString($input['hotel_name_snapshot'] ?? null),
                    'city_name'           => formatString($input['city_name'] ?? null),
                    'country_code'        => formatCountry($input['country_code'] ?? null),
                    'inc_status'          => formatStatus($input['inc_status'] ?? 'active'),
                    'inc_is_active'       => formatBoolean($input['inc_is_active'] ?? true),
                ];

                if (!$fields['inc_name']) throw new Exception("O campo inc_name e obrigatorio");

                $cols = $vals = $params = [];
                $idx = 1;
                foreach ($fields as $col => $val) {
                    if ($val !== null) {
                        $cols[] = $col;
                        $vals[] = '$' . $idx++;
                        $params[] = $val;
                    }
                }

                $sql = "
                    INSERT INTO incentive.inc_program (" . implode(', ', $cols) . ")
                    VALUES (" . implode(', ', $vals) . ")
                    RETURNING inc_id
                ";
                $result = pg_query_params($conn, $sql, $params);
                if (!$result) throw new Exception(pg_last_error($conn));

                $newId = (int) pg_fetch_result($result, 0, 0);

                $hasMedia           = array_key_exists('media', $input);
                $hasRoomCategories  = array_key_exists('room_categories', $input);
                $hasDining          = array_key_exists('dining', $input);
                $hasFacilities      = array_key_exists('facilities', $input);
                $hasConvention      = array_key_exists('convention', $input);
                $hasConventionRooms = array_key_exists('convention_rooms', $input);
                $hasNotes           = array_key_exists('notes', $input);

                if ($hasMedia) {
                    $media = is_array($input['media']) ? $input['media'] : [];
                    syncMedia($conn, $newId, $media);
                }
                if ($hasRoomCategories) {
                    $room_categories = is_array($input['room_categories']) ? $input['room_categories'] : [];
                    syncRoomCategories($conn, $newId, $room_categories);
                }
                if ($hasDining) {
                    $dining = is_array($input['dining']) ? $input['dining'] : [];
                    syncDining($conn, $newId, $dining);
                }
                if ($hasFacilities) {
                    $facilities = is_array($input['facilities']) ? $input['facilities'] : [];
                    syncFacilities($conn, $newId, $facilities);
                }

                $conv_id = null;
                if ($hasConvention) {
                    $conv_id = upsertConvention($conn, $newId, $input['convention']);
                } elseif ($hasConventionRooms) {
                    $conv_id = upsertConvention($conn, $newId, []);
                }
                if ($hasConventionRooms) {
                    $rooms = is_array($input['convention_rooms']) ? $input['convention_rooms'] : [];
                    if (!$conv_id) throw new Exception("Convention necessario para salas");
                    syncConventionRooms($conn, $conv_id, $rooms);
                }

                if ($hasNotes) {
                    $notes = is_array($input['notes']) ? $input['notes'] : [];
                    syncNotes($conn, $newId, $notes);
                }

                pg_query($conn, "COMMIT");

                response([
                    "success" => true,
                    "message" => "Programa criado com sucesso!",
                    "inc_id"  => $newId
                ], 201);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 400);
            }
            break;

        // ======================================
        // ATUALIZAR (parcial) - inc_program + relacionamentos opcionais
        // ======================================
        case 'atualizar_incentive':
            if ($method !== 'PUT') response(["error" => "Use PUT"], 405);

            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatorio"], 400);

            $user_data = null;
            requireBearerToken($conn, $user_data, $cod_sis ?? null);

            if (empty($input)) response(["error" => "Nenhum campo para atualizar"], 400);

            pg_query($conn, "BEGIN");
            try {
                $allowed = [
                    'inc_name','inc_description','hotel_ref_id','hotel_name_snapshot',
                    'city_name','country_code','inc_status','inc_is_active'
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

                    if ($formatted !== null) {
                        $updates[] = "$key = $" . $idx++;
                        $params[]  = $formatted;
                    }
                }

                $hasMedia           = array_key_exists('media', $input);
                $hasRoomCategories  = array_key_exists('room_categories', $input);
                $hasDining          = array_key_exists('dining', $input);
                $hasFacilities      = array_key_exists('facilities', $input);
                $hasConvention      = array_key_exists('convention', $input);
                $hasConventionRooms = array_key_exists('convention_rooms', $input);
                $hasNotes           = array_key_exists('notes', $input);

                $hasRelations = $hasMedia || $hasRoomCategories || $hasDining || $hasFacilities || $hasConvention || $hasConventionRooms || $hasNotes;

                if (empty($updates) && !$hasRelations) {
                    pg_query($conn, "ROLLBACK");
                    response(["message" => "Nenhuma alteracao valida"], 200);
                }

                if (!empty($updates)) {
                    $params[] = $id;
                    $sql = "
                        UPDATE incentive.inc_program
                        SET " . implode(', ', $updates) . ", updated_at = NOW()
                        WHERE inc_id = $" . $idx
                    ;

                    $result = pg_query_params($conn, $sql, $params);
                    if (!$result || pg_affected_rows($result) == 0) {
                        throw new Exception("Programa nao encontrado ou sem alteracoes");
                    }
                }

                if ($hasMedia) {
                    $media = is_array($input['media']) ? $input['media'] : [];
                    syncMedia($conn, $id, $media);
                }
                if ($hasRoomCategories) {
                    $room_categories = is_array($input['room_categories']) ? $input['room_categories'] : [];
                    syncRoomCategories($conn, $id, $room_categories);
                }
                if ($hasDining) {
                    $dining = is_array($input['dining']) ? $input['dining'] : [];
                    syncDining($conn, $id, $dining);
                }
                if ($hasFacilities) {
                    $facilities = is_array($input['facilities']) ? $input['facilities'] : [];
                    syncFacilities($conn, $id, $facilities);
                }

                $conv_id = null;
                if ($hasConvention) {
                    $conv_id = upsertConvention($conn, $id, $input['convention']);
                } elseif ($hasConventionRooms) {
                    $conv_id = getConventionId($conn, $id);
                    if (!$conv_id) {
                        $conv_id = upsertConvention($conn, $id, []);
                    }
                }
                if ($hasConventionRooms) {
                    $rooms = is_array($input['convention_rooms']) ? $input['convention_rooms'] : [];
                    if (!$conv_id) throw new Exception("Convention necessario para salas");
                    syncConventionRooms($conn, $conv_id, $rooms);
                }

                if ($hasNotes) {
                    $notes = is_array($input['notes']) ? $input['notes'] : [];
                    syncNotes($conn, $id, $notes);
                }

                pg_query($conn, "COMMIT");
                response([
                    "success" => true,
                    "message" => "Programa atualizado com sucesso!",
                    "inc_id"  => $id
                ]);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 400);
            }
            break;

        // ======================================
        // EXCLUIR - inc_program e filhos
        // ======================================
        case 'excluir_incentive':
            if ($method !== 'DELETE') response(["error" => "Use DELETE"], 405);

            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatorio"], 400);

            $user_data = null;
            requireBearerToken($conn, $user_data, $cod_sis ?? null);

            pg_query($conn, "BEGIN");
            try {
                $conv_id = getConventionId($conn, $id);
                if ($conv_id) {
                    execParams(
                        $conn,
                        "DELETE FROM incentive.inc_convention_room WHERE inc_convention_id = $1",
                        [$conv_id],
                        "Erro ao excluir salas"
                    );
                }

                execParams($conn, "DELETE FROM incentive.inc_convention WHERE inc_id = $1", [$id], "Erro ao excluir convention");
                execParams($conn, "DELETE FROM incentive.inc_note WHERE inc_id = $1", [$id], "Erro ao excluir notas");
                execParams($conn, "DELETE FROM incentive.inc_facility WHERE inc_id = $1", [$id], "Erro ao excluir facilities");
                execParams($conn, "DELETE FROM incentive.inc_dining WHERE inc_id = $1", [$id], "Erro ao excluir dining");
                execParams($conn, "DELETE FROM incentive.inc_room_category WHERE inc_id = $1", [$id], "Erro ao excluir quartos");
                execParams($conn, "DELETE FROM incentive.inc_media WHERE inc_id = $1", [$id], "Erro ao excluir midias");

                $res = execParams(
                    $conn,
                    "DELETE FROM incentive.inc_program WHERE inc_id = $1",
                    [$id],
                    "Erro ao excluir programa"
                );

                if (pg_affected_rows($res) == 0) {
                    throw new Exception("Programa nao encontrado");
                }

                pg_query($conn, "COMMIT");
                response(["success" => true, "message" => "Programa excluido com sucesso"]);
            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 400);
            }
            break;

        default:
            response(["error" => "Rota/instrucao invalida"], 400);
    }

} catch (Exception $e) {
    if (isset($conn)) @pg_query($conn, "ROLLBACK");
    error_log("Erro API Incentives: " . $e->getMessage());
    response(["error" => "Erro interno no servidor"], 500);
}
