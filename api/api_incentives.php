<?php
/**
 * API RESTful - incentive (CRUD inc_program + relacionamentos completos)
 * Versão atualizada com campos novos da migração 2025
 *
 * Endpoints:
 *   GET    ?request=listar_incentives
 *   GET    ?request=buscar_incentive&id=XXX
 *   POST   ?request=criar_incentive       (Bearer Token)
 *   PUT    ?request=atualizar_incentive&id=XXX  (Bearer Token)
 *   DELETE ?request=excluir_incentive&id=XXX    (Bearer Token)
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
// Helpers de formatação
// =============================================
function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getParam($name, $default = null) { return $_GET[$name] ?? $default; }
function getStringParam($name, $default = null) { $v = getParam($name, $default); return $v !== null ? trim($v) : null; }
function getIntParam($name, $default = null) { $v = getParam($name, $default); return is_numeric($v) ? (int)$v : $default; }

function formatString($val)   { return ($val === '' || $val === null) ? null : trim($val); }
function formatInt($val)      { return is_numeric($val) ? (int)$val : null; }
function formatNumeric($val)  { return ($val === null || $val === '' || !is_numeric($val)) ? null : $val; }
function formatBoolean($val)  { return $val === null ? null : filter_var($val, FILTER_VALIDATE_BOOLEAN); }
function formatCountry($val)  { $v = formatString($val); return $v && strlen($v) === 2 ? strtoupper($v) : null; }
function formatStatus($val)   { $allowed = ['active','inactive','draft','archived']; return in_array($val, $allowed) ? $val : 'active'; }
function formatLanguage($val) { $v = formatString($val); return $v && strlen($v) === 2 ? strtoupper($v) : null; }
function formatStarRating($val) { $n = formatInt($val); return ($n >= 1 && $n <= 5) ? $n : null; }
function formatPosition($val) { $n = formatInt($val); return $n !== null ? max(0, $n) : 0; }

function boolFromPg($v) {
    return $v === true || $v === 't' || $v === 'true' || $v === 1 || $v === '1';
}

// =============================================
// Autenticação
// =============================================
function requireBearerToken($conn, &$user_data) {
    $headers = getallheaders() ?: [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (strpos($auth, 'Bearer ') !== 0) {
        response(["error" => "Token Bearer obrigatório"], 401);
    }
    $token = trim(substr($auth, 7));
    if (!validarToken($conn, null, $token, $user_data)) {
        response(["error" => "Token inválido ou expirado"], 401);
    }
    return $token;
}

// =============================================
// Execução segura de queries
// =============================================
function execParams($conn, $sql, $params, $errorMsg) {
    $res = pg_query_params($conn, $sql, $params);
    if (!$res) throw new Exception($errorMsg . ': ' . pg_last_error($conn));
    return $res;
}

// =============================================
// Funções de sincronização (upsert/delete + insert)
// =============================================

function syncMedia($conn, $inc_id, $mediaList) {
    execParams($conn, "DELETE FROM incentive.inc_media WHERE inc_id = $1", [$inc_id], "Erro limpando mídias");
    foreach ($mediaList as $i => $m) {
        $type    = formatString($m['media_type'] ?? null);
        $url     = formatString($m['media_url'] ?? null);
        $pos     = formatPosition($m['position'] ?? 0);
        $active  = formatBoolean($m['is_active'] ?? true);

        if (!$type || !in_array($type, ['banner','gallery','video','map'])) throw new Exception("Mídia $i: media_type inválido");
        if (!$url) throw new Exception("Mídia $i: media_url obrigatório");

        execParams($conn,
            "INSERT INTO incentive.inc_media (inc_id, media_type, media_url, position, is_active) VALUES ($1,$2,$3,$4,$5)",
            [$inc_id, $type, $url, $pos, $active], "Erro inserindo mídia"
        );
    }
}

function syncRoomCategories($conn, $inc_id, $list) {
    execParams($conn, "DELETE FROM incentive.inc_room_category WHERE inc_id = $1", [$inc_id], "Erro limpando categorias");
    foreach ($list as $i => $r) {
        $name     = formatString($r['room_name'] ?? null);
        $qty      = formatInt($r['quantity'] ?? null);
        $area     = formatNumeric($r['area_m2'] ?? null);
        $view     = formatString($r['view_type'] ?? null);
        $type     = formatString($r['room_type'] ?? null);
        $notes    = formatString($r['notes'] ?? null);
        $pos      = formatPosition($r['position'] ?? 0);
        $active   = formatBoolean($r['is_active'] ?? true);

        if (!$name) throw new Exception("Categoria $i: room_name obrigatório");

        execParams($conn,
            "INSERT INTO incentive.inc_room_category 
             (inc_id, room_name, quantity, area_m2, view_type, room_type, notes, position, is_active)
             VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9)",
            [$inc_id, $name, $qty, $area, $view, $type, $notes, $pos, $active],
            "Erro inserindo categoria de quarto"
        );
    }
}

function syncRoomAmenities($conn, $inc_id, $list) {
    execParams($conn, "DELETE FROM incentive.inc_room_amenity WHERE inc_id = $1", [$inc_id], "Erro limpando amenities");
    foreach ($list as $i => $a) {
        $name   = formatString($a['name'] ?? null);
        $icon   = formatString($a['icon'] ?? null);
        $active = formatBoolean($a['is_active'] ?? true);

        if (!$name) throw new Exception("Amenity $i: name obrigatório");

        execParams($conn,
            "INSERT INTO incentive.inc_room_amenity (inc_id, name, icon, is_active) VALUES ($1,$2,$3,$4)",
            [$inc_id, $name, $icon, $active],
            "Erro inserindo room amenity"
        );
    }
}

function syncDining($conn, $inc_id, $list) {
    execParams($conn, "DELETE FROM incentive.inc_dining WHERE inc_id = $1", [$inc_id], "Erro limpando dining");
    foreach ($list as $i => $d) {
        $name      = formatString($d['name'] ?? null);
        $desc      = formatString($d['description'] ?? null);
        $cuisine   = formatString($d['cuisine'] ?? null);
        $cap       = formatInt($d['capacity'] ?? null);
        $seating   = formatInt($d['seating_capacity'] ?? null);
        $sched     = formatString($d['schedule'] ?? null);
        $michelin  = formatBoolean($d['is_michelin'] ?? false);
        $private   = formatBoolean($d['can_be_private'] ?? false);
        $img       = formatString($d['image_url'] ?? null);
        $pos       = formatPosition($d['position'] ?? 0);
        $active    = formatBoolean($d['is_active'] ?? true);

        if (!$name) throw new Exception("Dining $i: name obrigatório");

        execParams($conn,
            "INSERT INTO incentive.inc_dining 
             (inc_id, name, description, cuisine, capacity, seating_capacity, schedule, is_michelin, can_be_private, image_url, position, is_active)
             VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12)",
            [$inc_id, $name, $desc, $cuisine, $cap, $seating, $sched, $michelin, $private, $img, $pos, $active],
            "Erro inserindo dining"
        );
    }
}

function syncFacilities($conn, $inc_id, $list) {
    execParams($conn, "DELETE FROM incentive.inc_facility WHERE inc_id = $1", [$inc_id], "Erro limpando facilities");
    foreach ($list as $i => $f) {
        $name   = formatString($f['name'] ?? null);
        $icon   = formatString($f['icon'] ?? null);
        $active = formatBoolean($f['is_active'] ?? true);
        if (!$name) throw new Exception("Facility $i: name obrigatório");

        execParams($conn,
            "INSERT INTO incentive.inc_facility (inc_id, name, icon, is_active) VALUES ($1,$2,$3,$4)",
            [$inc_id, $name, $icon, $active],
            "Erro inserindo facility"
        );
    }
}

function upsertConvention($conn, $inc_id, $data) {
    if ($data === null) {
        execParams($conn, "DELETE FROM incentive.inc_convention WHERE inc_id = $1", [$inc_id], "Erro excluindo convention");
        return null;
    }

    $desc      = formatString($data['description'] ?? null);
    $total     = formatInt($data['total_rooms'] ?? null);
    $has360    = formatBoolean($data['has_360'] ?? false);

    $res = execParams($conn, "SELECT inc_convention_id FROM incentive.inc_convention WHERE inc_id = $1", [$inc_id], "Erro buscando convention");

    if (pg_num_rows($res) > 0) {
        $conv_id = (int) pg_fetch_result($res, 0, 0);
        execParams($conn,
            "UPDATE incentive.inc_convention SET description = $1, total_rooms = $2, has_360 = $3 WHERE inc_convention_id = $4",
            [$desc, $total, $has360, $conv_id], "Erro atualizando convention"
        );
        return $conv_id;
    }

    $res = execParams($conn,
        "INSERT INTO incentive.inc_convention (inc_id, description, total_rooms, has_360) VALUES ($1,$2,$3,$4) RETURNING inc_convention_id",
        [$inc_id, $desc, $total, $has360], "Erro inserindo convention"
    );
    return (int) pg_fetch_result($res, 0, 0);
}

function getConventionId($conn, $inc_id) {
    $res = execParams($conn, "SELECT inc_convention_id FROM incentive.inc_convention WHERE inc_id = $1", [$inc_id], "");
    return pg_num_rows($res) ? (int) pg_fetch_result($res, 0, 0) : null;
}

function syncConventionRooms($conn, $conv_id, $list) {
    if (!$conv_id) return;
    execParams($conn, "DELETE FROM incentive.inc_convention_room WHERE inc_convention_id = $1", [$conv_id], "Erro limpando salas");

    foreach ($list as $i => $r) {
        $name     = formatString($r['name'] ?? null);
        $area     = formatNumeric($r['area_m2'] ?? null);
        $height   = formatNumeric($r['height_m'] ?? null);
        $theater  = formatInt($r['capacity_theater'] ?? null);
        $cocktail = formatInt($r['capacity_cocktail'] ?? null);
        $aud      = formatInt($r['capacity_auditorium'] ?? null);
        $banq     = formatInt($r['capacity_banquet'] ?? null);
        $class    = formatInt($r['capacity_classroom'] ?? null);
        $u        = formatInt($r['capacity_u_shape'] ?? null);
        $notes    = formatString($r['notes'] ?? null);

        if (!$name) throw new Exception("Sala $i: name obrigatório");

        execParams($conn,
            "INSERT INTO incentive.inc_convention_room 
             (inc_convention_id, name, area_m2, height_m, capacity_theater, capacity_cocktail,
              capacity_auditorium, capacity_banquet, capacity_classroom, capacity_u_shape, notes)
             VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11)",
            [$conv_id, $name, $area, $height, $theater, $cocktail, $aud, $banq, $class, $u, $notes],
            "Erro inserindo sala de convenção"
        );
    }
}

function syncNotes($conn, $inc_id, $list) {
    execParams($conn, "DELETE FROM incentive.inc_note WHERE inc_id = $1", [$inc_id], "Erro limpando notas");
    foreach ($list as $i => $n) {
        $lang = formatLanguage($n['language'] ?? null);
        $text = formatString($n['note'] ?? null);
        if (!$text) throw new Exception("Nota $i: note obrigatório");
        execParams($conn, "INSERT INTO incentive.inc_note (inc_id, language, note) VALUES ($1,$2,$3)",
            [$inc_id, $lang, $text], "Erro inserindo nota"
        );
    }
}

// =============================================
// Processamento da requisição
// =============================================
$request = getParam('request');
if (!$request) response(["error" => "Parâmetro 'request' obrigatório"], 400);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if ($method === 'OPTIONS') response([], 204);

try {
    switch ($request) {

        // ────────────────────────────────────────────────
        // LISTAR (resumo com contagens + banner principal)
        // ────────────────────────────────────────────────
        case 'listar_incentives':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $f_nome   = getStringParam('filtro_nome');
            $f_status = getStringParam('filtro_status');
            $f_cidade = getStringParam('filtro_cidade');
            $f_pais   = getStringParam('filtro_pais');
            $f_ativo  = getParam('filtro_ativo', 'all');

            $page     = max(1, getIntParam('page', 1));
            $per_page = max(1, min(100, getIntParam('per_page', 30)));
            $offset   = ($page - 1) * $per_page;

            $where = $params = [];
            $idx = 1;

            if ($f_nome)   { $where[] = "p.inc_name ILIKE $" . $idx++; $params[] = "%$f_nome%"; }
            if ($f_status) { $where[] = "p.inc_status = $" . $idx++;   $params[] = $f_status; }
            if ($f_pais)   { $where[] = "p.country_code = $" . $idx++; $params[] = strtoupper($f_pais); }
            if ($f_cidade) { $where[] = "p.city_name ILIKE $" . $idx++; $params[] = "%$f_cidade%"; }
            if ($f_ativo !== 'all') {
                $ativo = filter_var($f_ativo, FILTER_VALIDATE_BOOLEAN);
                $where[] = "p.inc_is_active = $" . $idx++;
                $params[] = $ativo;
            }

            $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

            $sql_count = "SELECT COUNT(*) FROM incentive.inc_program p $where_sql";
            $total = (int) pg_fetch_result(pg_query_params($conn, $sql_count, $params), 0);

            $params[] = $per_page;
            $params[] = $offset;

            $sql = "
                SELECT
                    p.inc_id, p.inc_name, p.city_name, p.country_code, p.inc_status, p.inc_is_active,
                    p.star_rating, p.total_rooms,
                    (SELECT media_url FROM incentive.inc_media m 
                     WHERE m.inc_id = p.inc_id AND m.is_active AND m.media_type = 'banner'
                     ORDER BY m.position LIMIT 1) AS banner_url,
                    (SELECT COUNT(*) FROM incentive.inc_media            WHERE inc_id = p.inc_id AND is_active) AS media_count,
                    (SELECT COUNT(*) FROM incentive.inc_room_category     WHERE inc_id = p.inc_id AND is_active) AS room_count,
                    (SELECT COUNT(*) FROM incentive.inc_room_amenity      WHERE inc_id = p.inc_id AND is_active) AS amenity_count,
                    (SELECT COUNT(*) FROM incentive.inc_dining            WHERE inc_id = p.inc_id AND is_active) AS dining_count,
                    (SELECT COUNT(*) FROM incentive.inc_facility          WHERE inc_id = p.inc_id AND is_active) AS facility_count,
                    (SELECT COUNT(*) FROM incentive.inc_note              WHERE inc_id = p.inc_id) AS note_count
                FROM incentive.inc_program p
                $where_sql
                ORDER BY p.inc_name
                LIMIT $" . ($idx++) . " OFFSET $" . ($idx++)
            ;

            $rows = pg_fetch_all(pg_query_params($conn, $sql, $params)) ?: [];
            foreach ($rows as &$r) {
                $r['inc_id']      = (int)$r['inc_id'];
                $r['star_rating'] = $r['star_rating'] ? (int)$r['star_rating'] : null;
                $r['total_rooms'] = $r['total_rooms'] ? (int)$r['total_rooms'] : null;
                $r['inc_is_active'] = boolFromPg($r['inc_is_active']);
                foreach (['media_count','room_count','amenity_count','dining_count','facility_count','note_count'] as $k) {
                    $r[$k] = (int)$r[$k];
                }
            }

            response([
                'data' => $rows,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $per_page,
                    'current_page' => $page,
                    'last_page' => (int)ceil($total / $per_page)
                ]
            ]);
            break;

        // ────────────────────────────────────────────────
        // BUSCAR DETALHADO (com todos relacionamentos)
        // ────────────────────────────────────────────────
        case 'buscar_incentive':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);
            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatório"], 400);

            $res = pg_query_params($conn, "SELECT * FROM incentive.inc_program WHERE inc_id = $1", [$id]);
            if (!$res || !($p = pg_fetch_assoc($res))) {
                response(["error" => "Não encontrado"], 404);
            }

            $program = [
                'inc_id'              => (int)$p['inc_id'],
                'inc_name'            => $p['inc_name'],
                'inc_description'     => $p['inc_description'],
                'hotel_ref_id'        => $p['hotel_ref_id'] ? (int)$p['hotel_ref_id'] : null,
                'hotel_name_snapshot' => $p['hotel_name_snapshot'],
                'city_name'           => $p['city_name'],
                'country_code'        => $p['country_code'],
                'address'             => $p['address'],
                'postal_code'         => $p['postal_code'],
                'state_code'          => $p['state_code'],
                'phone'               => $p['phone'],
                'email'               => $p['email'],
                'website_url'         => $p['website_url'],
                'google_maps_url'     => $p['google_maps_url'],
                'latitude'            => $p['latitude'] ? (float)$p['latitude'] : null,
                'longitude'           => $p['longitude'] ? (float)$p['longitude'] : null,
                'star_rating'         => $p['star_rating'] ? (int)$p['star_rating'] : null,
                'total_rooms'         => $p['total_rooms'] ? (int)$p['total_rooms'] : null,
                'floor_plan_url'      => $p['floor_plan_url'],
                'inc_status'          => $p['inc_status'],
                'inc_is_active'       => boolFromPg($p['inc_is_active']),
                'created_at'          => $p['created_at'],
                'updated_at'          => $p['updated_at'],
            ];

            // Media
            $media = pg_fetch_all(pg_query_params($conn,
                "SELECT * FROM incentive.inc_media WHERE inc_id = $1 ORDER BY position, inc_media_id",
                [$id])) ?: [];
            foreach ($media as &$m) {
                $m['inc_media_id'] = (int)$m['inc_media_id'];
                $m['position'] = (int)$m['position'];
                $m['is_active'] = boolFromPg($m['is_active']);
            }

            // Room Categories
            $rooms = pg_fetch_all(pg_query_params($conn,
                "SELECT * FROM incentive.inc_room_category WHERE inc_id = $1 ORDER BY position, inc_room_id",
                [$id])) ?: [];
            foreach ($rooms as &$r) {
                $r['inc_room_id'] = (int)$r['inc_room_id'];
                $r['quantity'] = $r['quantity'] !== null ? (int)$r['quantity'] : null;
                $r['area_m2'] = $r['area_m2'] !== null ? (float)$r['area_m2'] : null;
                $r['position'] = (int)$r['position'];
                $r['is_active'] = boolFromPg($r['is_active']);
            }

            // Room Amenities
            $amenities = pg_fetch_all(pg_query_params($conn,
                "SELECT * FROM incentive.inc_room_amenity WHERE inc_id = $1 ORDER BY name",
                [$id])) ?: [];
            foreach ($amenities as &$a) {
                $a['inc_room_amenity_id'] = (int)$a['inc_room_amenity_id'];
                $a['is_active'] = boolFromPg($a['is_active']);
            }

            // Dining
            $dining = pg_fetch_all(pg_query_params($conn,
                "SELECT * FROM incentive.inc_dining WHERE inc_id = $1 ORDER BY position, inc_dining_id",
                [$id])) ?: [];
            foreach ($dining as &$d) {
                $d['inc_dining_id'] = (int)$d['inc_dining_id'];
                $d['capacity'] = $d['capacity'] !== null ? (int)$d['capacity'] : null;
                $d['seating_capacity'] = $d['seating_capacity'] !== null ? (int)$d['seating_capacity'] : null;
                $d['is_michelin'] = boolFromPg($d['is_michelin']);
                $d['can_be_private'] = boolFromPg($d['can_be_private']);
                $d['position'] = (int)$d['position'];
                $d['is_active'] = boolFromPg($d['is_active']);
            }

            // Facilities
            $facilities = pg_fetch_all(pg_query_params($conn,
                "SELECT * FROM incentive.inc_facility WHERE inc_id = $1 ORDER BY name",
                [$id])) ?: [];
            foreach ($facilities as &$f) {
                $f['inc_facility_id'] = (int)$f['inc_facility_id'];
                $f['is_active'] = boolFromPg($f['is_active']);
            }

            // Convention
            $convention = null;
            $conv_rooms = [];
            $res_conv = pg_query_params($conn, "SELECT * FROM incentive.inc_convention WHERE inc_id = $1", [$id]);
            if ($res_conv && ($c = pg_fetch_assoc($res_conv))) {
                $convention = [
                    'inc_convention_id' => (int)$c['inc_convention_id'],
                    'description'       => $c['description'],
                    'total_rooms'       => $c['total_rooms'] ? (int)$c['total_rooms'] : null,
                    'has_360'           => boolFromPg($c['has_360']),
                ];

                $conv_rooms = pg_fetch_all(pg_query_params($conn,
                    "SELECT * FROM incentive.inc_convention_room WHERE inc_convention_id = $1 ORDER BY inc_room_id",
                    [$c['inc_convention_id']])) ?: [];
                foreach ($conv_rooms as &$cr) {
                    $cr['inc_room_id'] = (int)$cr['inc_room_id'];
                    foreach (['area_m2','height_m'] as $k) $cr[$k] = $cr[$k] !== null ? (float)$cr[$k] : null;
                    foreach (['capacity_theater','capacity_cocktail','capacity_auditorium','capacity_banquet','capacity_classroom','capacity_u_shape'] as $k) {
                        $cr[$k] = $cr[$k] !== null ? (int)$cr[$k] : null;
                    }
                }
            }

            // Notes
            $notes = pg_fetch_all(pg_query_params($conn,
                "SELECT * FROM incentive.inc_note WHERE inc_id = $1 ORDER BY inc_note_id",
                [$id])) ?: [];
            foreach ($notes as &$n) $n['inc_note_id'] = (int)$n['inc_note_id'];

            response([
                'program' => $program,
                'relations' => [
                    'media'            => $media,
                    'room_categories'  => $rooms,
                    'room_amenities'   => $amenities,
                    'dining'           => $dining,
                    'facilities'       => $facilities,
                    'convention'       => $convention,
                    'convention_rooms' => $conv_rooms,
                    'notes'            => $notes,
                ]
            ]);
            break;

        // ────────────────────────────────────────────────
        // CRIAR
        // ────────────────────────────────────────────────
        case 'criar_incentive':
            if ($method !== 'POST') response(["error" => "Use POST"], 405);
            requireBearerToken($conn, $user_data);

            if (empty($input)) response(["error" => "Body JSON obrigatório"], 400);

            pg_query($conn, "BEGIN");
            try {
                $fields = [
                    'inc_name'            => formatString($input['inc_name'] ?? null),
                    'inc_description'     => formatString($input['inc_description'] ?? null),
                    'hotel_ref_id'        => formatInt($input['hotel_ref_id'] ?? null),
                    'hotel_name_snapshot' => formatString($input['hotel_name_snapshot'] ?? null),
                    'city_name'           => formatString($input['city_name'] ?? null),
                    'country_code'        => formatCountry($input['country_code'] ?? null),
                    'address'             => formatString($input['address'] ?? null),
                    'postal_code'         => formatString($input['postal_code'] ?? null),
                    'state_code'          => formatString($input['state_code'] ?? null),
                    'phone'               => formatString($input['phone'] ?? null),
                    'email'               => formatString($input['email'] ?? null),
                    'website_url'         => formatString($input['website_url'] ?? null),
                    'google_maps_url'     => formatString($input['google_maps_url'] ?? null),
                    'latitude'            => formatNumeric($input['latitude'] ?? null),
                    'longitude'           => formatNumeric($input['longitude'] ?? null),
                    'star_rating'         => formatStarRating($input['star_rating'] ?? null),
                    'total_rooms'         => formatInt($input['total_rooms'] ?? null),
                    'floor_plan_url'      => formatString($input['floor_plan_url'] ?? null),
                    'inc_status'          => formatStatus($input['inc_status'] ?? 'active'),
                    'inc_is_active'       => formatBoolean($input['inc_is_active'] ?? true),
                ];

                if (!$fields['inc_name']) throw new Exception("inc_name é obrigatório");

                $cols = $vals = $params = [];
                $idx = 1;
                foreach ($fields as $k => $v) {
                    if ($v !== null) {
                        $cols[] = $k;
                        $vals[] = '$' . $idx++;
                        $params[] = $v;
                    }
                }

                $sql = "INSERT INTO incentive.inc_program (" . implode(', ', $cols) . ") 
                        VALUES (" . implode(', ', $vals) . ") RETURNING inc_id";
                $newId = (int) pg_fetch_result(execParams($conn, $sql, $params, "Erro criando programa"), 0);

                // Sincronizações
                if (isset($input['media']))            syncMedia($conn, $newId, $input['media']);
                if (isset($input['room_categories']))  syncRoomCategories($conn, $newId, $input['room_categories']);
                if (isset($input['room_amenities']))   syncRoomAmenities($conn, $newId, $input['room_amenities']);
                if (isset($input['dining']))           syncDining($conn, $newId, $input['dining']);
                if (isset($input['facilities']))       syncFacilities($conn, $newId, $input['facilities']);
                if (isset($input['notes']))            syncNotes($conn, $newId, $input['notes']);

                $conv_id = null;
                if (isset($input['convention'])) {
                    $conv_id = upsertConvention($conn, $newId, $input['convention']);
                }
                if (isset($input['convention_rooms'])) {
                    if (!$conv_id) $conv_id = upsertConvention($conn, $newId, []);
                    syncConventionRooms($conn, $conv_id, $input['convention_rooms']);
                }

                pg_query($conn, "COMMIT");
                response(["success" => true, "inc_id" => $newId], 201);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 400);
            }
            break;

        // ────────────────────────────────────────────────
        // ATUALIZAR (parcial)
        // ────────────────────────────────────────────────
        case 'atualizar_incentive':
            if ($method !== 'PUT') response(["error" => "Use PUT"], 405);
            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatório"], 400);
            requireBearerToken($conn, $user_data);

            if (empty($input)) response(["error" => "Nenhum dado para atualizar"], 400);

            pg_query($conn, "BEGIN");
            try {
                $allowed = [
                    'inc_name','inc_description','hotel_ref_id','hotel_name_snapshot','city_name','country_code',
                    'address','postal_code','state_code','phone','email','website_url','google_maps_url',
                    'latitude','longitude','star_rating','total_rooms','floor_plan_url',
                    'inc_status','inc_is_active'
                ];

                $updates = $params = [];
                $idx = 1;

                foreach ($input as $k => $v) {
                    if (!in_array($k, $allowed)) continue;

                    $val = match ($k) {
                        'hotel_ref_id', 'total_rooms', 'star_rating' => formatInt($v),
                        'latitude', 'longitude' => formatNumeric($v),
                        'inc_is_active' => formatBoolean($v),
                        'inc_status' => formatStatus($v),
                        'country_code' => formatCountry($v),
                        default => formatString($v)
                    };

                    if ($val !== null) {
                        $updates[] = "$k = $" . $idx++;
                        $params[] = $val;
                    }
                }

                $hasRelations = isset($input['media']) || isset($input['room_categories']) || isset($input['room_amenities']) ||
                                isset($input['dining']) || isset($input['facilities']) || isset($input['convention']) ||
                                isset($input['convention_rooms']) || isset($input['notes']);

                if (empty($updates) && !$hasRelations) {
                    pg_query($conn, "ROLLBACK");
                    response(["message" => "Nenhuma alteração válida"], 200);
                }

                if ($updates) {
                    $params[] = $id;
                    $sql = "UPDATE incentive.inc_program SET " . implode(', ', $updates) . ", updated_at = NOW() 
                            WHERE inc_id = $" . $idx;
                    execParams($conn, $sql, $params, "Erro atualizando programa");
                }

                if (isset($input['media']))            syncMedia($conn, $id, $input['media']);
                if (isset($input['room_categories']))  syncRoomCategories($conn, $id, $input['room_categories']);
                if (isset($input['room_amenities']))   syncRoomAmenities($conn, $id, $input['room_amenities']);
                if (isset($input['dining']))           syncDining($conn, $id, $input['dining']);
                if (isset($input['facilities']))       syncFacilities($conn, $id, $input['facilities']);
                if (isset($input['notes']))            syncNotes($conn, $id, $input['notes']);

                $conv_id = null;
                if (isset($input['convention'])) {
                    $conv_id = upsertConvention($conn, $id, $input['convention']);
                } elseif (isset($input['convention_rooms'])) {
                    $conv_id = getConventionId($conn, $id) ?? upsertConvention($conn, $id, []);
                }
                if (isset($input['convention_rooms'])) {
                    syncConventionRooms($conn, $conv_id, $input['convention_rooms']);
                }

                pg_query($conn, "COMMIT");
                response(["success" => true, "inc_id" => $id]);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 400);
            }
            break;

        // ────────────────────────────────────────────────
        // EXCLUIR (cascata)
        // ────────────────────────────────────────────────
        case 'excluir_incentive':
            if ($method !== 'DELETE') response(["error" => "Use DELETE"], 405);
            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatório"], 400);
            requireBearerToken($conn, $user_data);

            pg_query($conn, "BEGIN");
            try {
                $conv_id = getConventionId($conn, $id);
                if ($conv_id) {
                    execParams($conn, "DELETE FROM incentive.inc_convention_room WHERE inc_convention_id = $1", [$conv_id], "");
                }

                $tables = [
                    'inc_convention', 'inc_note', 'inc_facility', 'inc_dining',
                    'inc_room_category', 'inc_room_amenity', 'inc_media'
                ];
                foreach ($tables as $t) {
                    execParams($conn, "DELETE FROM incentive.$t WHERE inc_id = $1", [$id], "Erro excluindo $t");
                }

                $res = execParams($conn, "DELETE FROM incentive.inc_program WHERE inc_id = $1", [$id], "Erro excluindo programa");
                if (pg_affected_rows($res) === 0) throw new Exception("Programa não encontrado");

                pg_query($conn, "COMMIT");
                response(["success" => true, "message" => "Excluído com sucesso"]);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 400);
            }
            break;

        default:
            response(["error" => "Rota inválida"], 400);
    }
} catch (Exception $e) {
    if (isset($conn)) pg_query($conn, "ROLLBACK");
    error_log("API Incentive erro: " . $e->getMessage());
    response(["error" => "Erro interno"], 500);
}