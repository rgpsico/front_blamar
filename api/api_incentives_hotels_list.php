<?php

require_once '../util/connection.php';
require_once 'incentives/api_helpers.php';

/**
 * API para LISTAGEM de Hotéis com base nos incentivos (incentive.inc_program)
 *
 * Endpoint:
 * - GET ?request=listar_hoteis_incentives
 *       &filtro_nome=Copacabana
 *       &filtro_cidade=Rio de Janeiro
 *       &filtro_pais=BR
 *       &filtro_ativo=true|false|all
 *       &page=1
 *       &per_page=30
 */

$request = getParam('request');
if (!$request) {
    response(["error" => "Parametro 'request' e obrigatorio"], 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Responde OPTIONS (CORS preflight)
if ($method === 'OPTIONS') {
    response([], 204);
}

try {
    // =====================================================
    // LISTAR HOTEIS (baseado em incentives)
    // =====================================================
    if ($request === 'listar_hoteis_incentives') {
        if ($method !== 'GET') {
            response(["error" => "Use metodo GET"], 405);
        }

        $filtro_nome   = getStringParam('filtro_nome');
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
            $where[]  = "COALESCE(p.hotel_name_snapshot, p.inc_name) ILIKE $" . $idx++;
            $params[] = "%{$filtro_nome}%";
        }
        if ($filtro_cidade) {
            $where[]  = "p.city_name ILIKE $" . $idx++;
            $params[] = "%{$filtro_cidade}%";
        }
        if ($filtro_pais) {
            $where[]  = "p.country_code = $" . $idx++;
            $params[] = strtoupper($filtro_pais);
        }
        if ($filtro_ativo !== 'all') {
            $ativo = filter_var($filtro_ativo, FILTER_VALIDATE_BOOLEAN);
            $where[]  = "p.inc_is_active = $" . $idx++;
            $params[] = $ativo;
        }

        $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

        // TOTAL (numero de hoteis unicos por hotel_ref_id/nomes)
        $sql_count = "
            SELECT COUNT(*) AS total FROM (
                SELECT 1
                FROM incentive.inc_program p
                {$where_sql}
                GROUP BY COALESCE(p.hotel_ref_id::text, p.hotel_name_snapshot, p.inc_id::text)
            ) t
        ";
        $res_count = pg_query_params($conn, $sql_count, $params);
        if (!$res_count) {
            throw new Exception(pg_last_error($conn));
        }
        $total = (int) pg_fetch_result($res_count, 0, 'total');

        // LISTA PAGINADA
        $params_list = $params;
        $params_list[] = $per_page;
        $params_list[] = $offset;

        $limitParam  = '$' . ($idx++);
        $offsetParam = '$' . ($idx++);

        $sql = "
            WITH ranked AS (
                SELECT
                    p.inc_id,
                    p.hotel_ref_id,
                    p.hotel_name_snapshot,
                    p.thumnail,
                    p.inc_name,
                    p.city_name,
                    p.country_code,
                    p.star_rating,
                    p.total_rooms,
                    p.inc_description,
                    p.inc_status,
                    p.inc_is_active,
                    p.created_at,
                    p.updated_at,
                    COALESCE(p.hotel_ref_id::text, p.hotel_name_snapshot, p.inc_id::text) AS hotel_key,
                    ROW_NUMBER() OVER (
                        PARTITION BY COALESCE(p.hotel_ref_id::text, p.hotel_name_snapshot, p.inc_id::text)
                        ORDER BY p.updated_at DESC NULLS LAST, p.inc_id DESC
                    ) AS rn
                FROM incentive.inc_program p
                {$where_sql}
            )
            SELECT
                r.inc_id,
                r.hotel_ref_id,
                COALESCE(r.hotel_name_snapshot, r.inc_name) AS hotel_name,
                r.inc_name,
                r.city_name,
                r.country_code,
                r.star_rating,
                r.total_rooms,
                r.inc_description AS description,
                r.inc_status,
                r.inc_is_active,
                r.created_at,
                r.updated_at,
                COALESCE((
                    SELECT m.media_url
                    FROM incentive.inc_media m
                    WHERE m.inc_id = r.inc_id
                      AND m.is_active = true
                    ORDER BY m.position ASC, m.inc_media_id ASC
                    LIMIT 1
                ), NULL) AS main_image,
                (
                    SELECT COUNT(*)
                    FROM incentive.inc_program p2
                    WHERE COALESCE(p2.hotel_ref_id::text, p2.hotel_name_snapshot, p2.inc_id::text) = r.hotel_key
                ) AS total_incentives
            FROM ranked r
            WHERE r.rn = 1
            ORDER BY hotel_name ASC NULLS LAST, r.inc_id ASC
            LIMIT {$limitParam} OFFSET {$offsetParam}
        ";

        $result = pg_query_params($conn, $sql, $params_list);
        if (!$result) {
            throw new Exception(pg_last_error($conn));
        }

        $rows = pg_fetch_all($result) ?: [];
        foreach ($rows as &$row) {
            $row['inc_id'] = (int) $row['inc_id'];
            $row['hotel_ref_id'] = ($row['hotel_ref_id'] !== null && $row['hotel_ref_id'] !== '') ? (int) $row['hotel_ref_id'] : null;
            $row['star_rating'] = $row['star_rating'] !== null ? (int) $row['star_rating'] : null;
            $row['total_rooms'] = $row['total_rooms'] !== null ? (int) $row['total_rooms'] : null;
            $row['inc_is_active'] = boolFromPg($row['inc_is_active']);
            $row['total_incentives'] = (int) $row['total_incentives'];
            $row['thumnail'] = $row['thumnail'] ?? null;
        }

        response([
            'success' => true,
            'data' => $rows,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $per_page,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $per_page)
            ]
        ]);
    }

    // =====================================================
    // ROTA INVALIDA
    // =====================================================
    response(["error" => "Request invalido"], 400);

} catch (Exception $e) {
    if (isset($conn)) {
        @pg_query($conn, "ROLLBACK");
    }
    error_log("Erro API Incentives Hotels List: " . $e->getMessage());
    response(["error" => "Erro interno no servidor: " . $e->getMessage()], 500);
}
