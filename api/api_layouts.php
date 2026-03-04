<?php

require_once '../util/connection.php';
require_once 'middleware.php';
require_once 'incentives/api_helpers.php';

function syncConventionRoomLayouts($conn, $inc_room_id, $layouts) {
    if (!$inc_room_id) {
        return;
    }

    execParams(
        $conn,
        "DELETE FROM incentive.inc_convention_room_layout WHERE inc_room_id = $1",
        [$inc_room_id],
        "Erro ao limpar layouts da sala"
    );

    if (!is_array($layouts)) {
        return;
    }

    foreach ($layouts as $index => $layout) {
        $layout_type = formatString($layout['layout_type'] ?? null);
        $capacity    = formatInt($layout['capacity'] ?? null);

        if (!$layout_type && $capacity === null) {
            continue;
        }

        if (!$layout_type) {
            throw new Exception("Layout {$index}: layout_type obrigatorio");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_convention_room_layout (inc_room_id, layout_type, capacity)
             VALUES ($1, $2, $3)",
            [$inc_room_id, $layout_type, $capacity],
            "Erro ao inserir layout"
        );
    }
}

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if ($method === 'OPTIONS') {
    response([], 204);
}

$id = getIntParam('id');
if (!$id) {
    response(["error" => "ID obrigatorio"], 400);
}

$user_data = null;
requireBearerToken($conn, $user_data, $cod_sis ?? null);

try {
    if ($method === 'GET') {
        $sql_rooms = "
            SELECT cr.inc_room_id, cr.name
            FROM incentive.inc_convention_room cr
            JOIN incentive.inc_convention c
              ON c.inc_convention_id = cr.inc_convention_id
            WHERE c.inc_id = $1
            ORDER BY cr.inc_room_id ASC
        ";
        $res_rooms = pg_query_params($conn, $sql_rooms, [$id]);
        if (!$res_rooms) throw new Exception(pg_last_error($conn));
        $rooms = pg_fetch_all($res_rooms) ?: [];

        $room_map = [];
        foreach ($rooms as $r) {
            $rid = (int)$r['inc_room_id'];
            $room_map[$rid] = [
                'inc_room_id' => $rid,
                'name' => $r['name'],
                'layouts' => []
            ];
        }

        $sql_layouts = "
            SELECT l.inc_layout_id, l.inc_room_id, l.layout_type, l.capacity
            FROM incentive.inc_convention_room_layout l
            JOIN incentive.inc_convention_room cr ON cr.inc_room_id = l.inc_room_id
            JOIN incentive.inc_convention c ON c.inc_convention_id = cr.inc_convention_id
            WHERE c.inc_id = $1
            ORDER BY l.inc_room_id ASC, l.inc_layout_id ASC
        ";
        $res_layouts = pg_query_params($conn, $sql_layouts, [$id]);
        if (!$res_layouts) throw new Exception(pg_last_error($conn));
        $layouts = pg_fetch_all($res_layouts) ?: [];

        foreach ($layouts as $l) {
            $rid = (int)$l['inc_room_id'];
            if (!isset($room_map[$rid])) {
                continue;
            }
            $room_map[$rid]['layouts'][] = [
                'inc_layout_id' => (int)$l['inc_layout_id'],
                'inc_room_id' => $rid,
                'layout_type' => $l['layout_type'],
                'capacity' => $l['capacity'] !== null ? (int)$l['capacity'] : null
            ];
        }

        response([
            'success' => true,
            'data' => array_values($room_map)
        ]);
    }

    if ($method !== 'PUT') {
        response(["error" => "Use metodo PUT"], 405);
    }

    if (!isset($input['convention_rooms']) || !is_array($input['convention_rooms'])) {
        response(["error" => "convention_rooms obrigatorio"], 400);
    }

    $sql_allowed = "
        SELECT cr.inc_room_id
        FROM incentive.inc_convention_room cr
        JOIN incentive.inc_convention c
          ON c.inc_convention_id = cr.inc_convention_id
        WHERE c.inc_id = $1
    ";
    $res_allowed = pg_query_params($conn, $sql_allowed, [$id]);
    if (!$res_allowed) throw new Exception(pg_last_error($conn));
    $allowed_rows = pg_fetch_all($res_allowed) ?: [];
    $allowed = [];
    foreach ($allowed_rows as $row) {
        $allowed[(int)$row['inc_room_id']] = true;
    }

    $updated = 0;
    foreach ($input['convention_rooms'] as $room) {
        $room_id = isset($room['inc_room_id']) ? (int)$room['inc_room_id'] : 0;
        if (!$room_id || !isset($allowed[$room_id])) {
            continue;
        }
        syncConventionRoomLayouts($conn, $room_id, $room['layouts'] ?? []);
        $updated++;
    }

    response([
        'success' => true,
        'message' => 'Layouts salvos com sucesso',
        'updated' => $updated
    ]);
} catch (Exception $e) {
    error_log("Erro API Layouts: " . $e->getMessage());
    response(["error" => "Erro interno no servidor: " . $e->getMessage()], 500);
}
