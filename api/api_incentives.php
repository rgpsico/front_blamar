<?php

require_once '../util/connection.php';
require_once 'middleware.php';
require_once 'incentives/api_helpers.php';

require_once 'incentives/api_sinc.php';


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
                $ativo = filter_var($filtro_ativo, FILTER_VALIDATE_BOOLEAN);
                $where[]  = "p.inc_is_active = $" . $idx++;
                $params[] = $ativo; // boolean nativo (pg_query_params aceita)
            }

            $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

            // TOTAL
            $sql_count = "SELECT COUNT(*) AS total FROM incentive.inc_program p {$where_sql}";
            $res_count = pg_query_params($conn, $sql_count, $params);
            if (!$res_count) throw new Exception(pg_last_error($conn));
            $total = (int) pg_fetch_result($res_count, 0, 'total');

            // LISTA
            $params_list = $params;
            $params_list[] = $per_page;
            $params_list[] = $offset;

            $limitParam  = '$' . ($idx++);
            $offsetParam = '$' . ($idx++);

            /**
             * Estratégia:
             * - Primeiro seleciona a "página" de programas (CTE base)
             * - Depois, para cada programa, gera JSON das relações com subselects.
             * Isso evita duplicação de linhas por JOIN e mantém paginado correto.
             */
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
                    {$where_sql}
                    ORDER BY p.inc_name, p.inc_id
                    LIMIT {$limitParam} OFFSET {$offsetParam}
                )
                SELECT
                    b.*,

                    -- =========================
                    -- MEDIA (array)
                    -- =========================
                    COALESCE((
                        SELECT json_agg(
                            json_build_object(
                                'inc_media_id', m.inc_media_id,
                                'inc_id', m.inc_id,
                                'media_type', m.media_type,
                                'media_url', m.media_url,
                                'position', m.position,
                                'is_active', m.is_active
                            )
                            ORDER BY m.position ASC, m.inc_media_id ASC
                        )
                        FROM incentive.inc_media m
                        WHERE m.inc_id = b.inc_id
                    ), '[]'::json) AS media,

                    -- =========================
                    -- ROOM CATEGORIES (array)
                    -- =========================
                    COALESCE((
                        SELECT json_agg(
                            json_build_object(
                                'inc_room_id', rc.inc_room_id,
                                'inc_id', rc.inc_id,
                                'room_name', rc.room_name,
                                'quantity', rc.quantity,
                                'notes', rc.notes,
                                'position', rc.position,
                                'is_active', rc.is_active
                            )
                            ORDER BY rc.position ASC, rc.inc_room_id ASC
                        )
                        FROM incentive.inc_room_category rc
                        WHERE rc.inc_id = b.inc_id
                    ), '[]'::json) AS room_categories,

                    -- =========================
                    -- DINING (array)
                    -- =========================
                    COALESCE((
                        SELECT json_agg(
                            json_build_object(
                                'inc_dining_id', d.inc_dining_id,
                                'inc_id', d.inc_id,
                                'name', d.name,
                                'description', d.description,
                                'cuisine', d.cuisine,
                                'capacity', d.capacity,
                                'schedule', d.schedule,
                                'is_michelin', d.is_michelin,
                                'can_be_private', d.can_be_private,
                                'image_url', d.image_url,
                                'position', d.position,
                                'is_active', d.is_active
                            )
                            ORDER BY d.position ASC, d.inc_dining_id ASC
                        )
                        FROM incentive.inc_dining d
                        WHERE d.inc_id = b.inc_id
                    ), '[]'::json) AS dining,

                    -- =========================
                    -- FACILITIES (array)
                    -- =========================
                    COALESCE((
                        SELECT json_agg(
                            json_build_object(
                                'inc_facility_id', f.inc_facility_id,
                                'inc_id', f.inc_id,
                                'name', f.name,
                                'icon', f.icon,
                                'is_active', f.is_active
                            )
                            ORDER BY f.inc_facility_id ASC
                        )
                        FROM incentive.inc_facility f
                        WHERE f.inc_id = b.inc_id
                    ), '[]'::json) AS facilities,

                    -- =========================
                    -- NOTES (array)
                    -- =========================
                    COALESCE((
                        SELECT json_agg(
                            json_build_object(
                                'inc_note_id', n.inc_note_id,
                                'inc_id', n.inc_id,
                                'language', n.language,
                                'note', n.note
                            )
                            ORDER BY n.inc_note_id ASC
                        )
                        FROM incentive.inc_note n
                        WHERE n.inc_id = b.inc_id
                    ), '[]'::json) AS notes,

                    -- =========================
                    -- CONVENTION (objeto) + ROOMS (array)
                    -- =========================
                    (
                        SELECT
                            CASE
                                WHEN c.inc_convention_id IS NULL THEN NULL
                                ELSE json_build_object(
                                    'inc_convention_id', c.inc_convention_id,
                                    'inc_id', c.inc_id,
                                    'description', c.description,
                                    'total_rooms', c.total_rooms,
                                    'has_360', c.has_360,
                                    'rooms', COALESCE((
                                        SELECT json_agg(
                                            json_build_object(
                                                'inc_room_id', cr.inc_room_id,
                                                'inc_convention_id', cr.inc_convention_id,
                                                'name', cr.name,
                                                'area_m2', cr.area_m2,
                                                'capacity_auditorium', cr.capacity_auditorium,
                                                'capacity_banquet', cr.capacity_banquet,
                                                'capacity_classroom', cr.capacity_classroom,
                                                'capacity_u_shape', cr.capacity_u_shape,
                                                'notes', cr.notes
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
                ORDER BY b.inc_name, b.inc_id
            ";

            $result = pg_query_params($conn, $sql, $params_list);
            if (!$result) throw new Exception(pg_last_error($conn));

            $rows = pg_fetch_all($result) ?: [];

            // TIPAGEM/normalização no PHP
            foreach ($rows as &$row) {
                $row['inc_id'] = (int)$row['inc_id'];
                $row['hotel_ref_id'] = ($row['hotel_ref_id'] !== null && $row['hotel_ref_id'] !== '') ? (int)$row['hotel_ref_id'] : null;
                $row['inc_is_active'] = boolFromPg($row['inc_is_active']);

                // As colunas json_agg chegam como string JSON no pg_fetch_all. Decodifica:
                $row['media']           = json_decode($row['media'] ?? '[]', true) ?: [];
                $row['room_categories'] = json_decode($row['room_categories'] ?? '[]', true) ?: [];
                $row['dining']          = json_decode($row['dining'] ?? '[]', true) ?: [];
                $row['facilities']      = json_decode($row['facilities'] ?? '[]', true) ?: [];
                $row['notes']           = json_decode($row['notes'] ?? '[]', true) ?: [];
                $row['convention']      = ($row['convention'] === null) ? null : (json_decode($row['convention'], true) ?: null);

                // Opcional: normalizar booleans dentro dos arrays (Postgres manda true/false como boolean no JSON)
                // Em geral, o json_decode já entrega boolean certo, então não precisa.
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
    // BUSCAR 1 INCENTIVO (completo com relacionamentos)
    // =====================================================
   elseif ($request === 'buscar_incentive') {
    if ($method !== 'GET') {
        response(["error" => "Use método GET"], 405);
    }

    $id = getIntParam('id');
    if (!$id) {
        response(["error" => "ID obrigatório"], 400);
    }

    // Programa principal
    $sql_program = "SELECT * FROM incentive.inc_program WHERE inc_id = $1 LIMIT 1";
    $res_program = pg_query_params($conn, $sql_program, [$id]);
    
    if (!$res_program || pg_num_rows($res_program) === 0) {
        response(["error" => "Incentivo não encontrado"], 404);
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

    // =============================================
    // CONTATO DO HOTEL (tabela inc_hotel_contact)
    // =============================================
    $sql_contact = "
        SELECT 
            inc_contact_id, 
            address, 
            postal_code, 
            state_code, 
            phone, 
            email, 
            website_url, 
            google_maps_url, 
            latitude, 
            longitude
        FROM incentive.inc_hotel_contact
        WHERE inc_id = $1
        LIMIT 1
    ";
    $res_contact = pg_query_params($conn, $sql_contact, [$id]);
    $hotel_contact = null;

    if ($res_contact && pg_num_rows($res_contact) > 0) {
        $c = pg_fetch_assoc($res_contact);
        $hotel_contact = [
            'inc_contact_id'   => (int)$c['inc_contact_id'],
            'address'          => $c['address'],
            'postal_code'      => $c['postal_code'],
            'state_code'       => $c['state_code'],
            'phone'            => $c['phone'],
            'email'            => $c['email'],
            'website_url'      => $c['website_url'],
            'google_maps_url'  => $c['google_maps_url'],
            'latitude'         => $c['latitude'] !== null ? (float)$c['latitude'] : null,
            'longitude'        => $c['longitude'] !== null ? (float)$c['longitude'] : null,
        ];
    }

    // Mídias
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

    // Categorias de quartos
    $sql_rooms = "
        SELECT inc_room_id, room_name, quantity, notes, position, is_active,
               area_m2, view_type, room_type
        FROM incentive.inc_room_category
        WHERE inc_id = $1
        ORDER BY position ASC, inc_room_id ASC
    ";
    $res_rooms = pg_query_params($conn, $sql_rooms, [$id]);
    $room_categories = pg_fetch_all($res_rooms) ?: [];
    foreach ($room_categories as &$r) {
        $r['inc_room_id'] = (int)$r['inc_room_id'];
        $r['quantity']    = $r['quantity'] ? (int)$r['quantity'] : null;
        $r['position']    = (int)$r['position'];
        $r['is_active']   = boolFromPg($r['is_active']);
        $r['area_m2']     = $r['area_m2'] !== null ? (float)$r['area_m2'] : null;
    }

    // Dining
    $sql_dining = "
        SELECT
            inc_dining_id, name, description, cuisine, capacity, schedule,
            is_michelin, can_be_private, image_url, position, is_active,
            seating_capacity
        FROM incentive.inc_dining
        WHERE inc_id = $1
        ORDER BY position ASC, inc_dining_id ASC
    ";
    $res_dining = pg_query_params($conn, $sql_dining, [$id]);
    $dining = pg_fetch_all($res_dining) ?: [];
    foreach ($dining as &$d) {
        $d['inc_dining_id']     = (int)$d['inc_dining_id'];
        $d['capacity']          = $d['capacity'] ? (int)$d['capacity'] : null;
        $d['seating_capacity']  = $d['seating_capacity'] ? (int)$d['seating_capacity'] : null;
        $d['is_michelin']       = boolFromPg($d['is_michelin']);
        $d['can_be_private']    = boolFromPg($d['can_be_private']);
        $d['position']          = (int)$d['position'];
        $d['is_active']         = boolFromPg($d['is_active']);
    }

    // Facilities
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

    // Convention + Salas
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
            'total_rooms'       => $c['total_rooms'] ? (int)$c['total_rooms'] : null,
            'has_360'           => boolFromPg($c['has_360']),
        ];

        $sql_conv_rooms = "
            SELECT
                inc_room_id, name, area_m2, height_m,
                capacity_theater, capacity_cocktail,
                capacity_auditorium, capacity_banquet, 
                capacity_classroom, capacity_u_shape,
                notes
            FROM incentive.inc_convention_room
            WHERE inc_convention_id = $1
            ORDER BY inc_room_id ASC
        ";
        $res_conv_rooms = pg_query_params($conn, $sql_conv_rooms, [$convention['inc_convention_id']]);
        $convention_rooms = pg_fetch_all($res_conv_rooms) ?: [];
        foreach ($convention_rooms as &$cr) {
            $cr['inc_room_id']          = (int)$cr['inc_room_id'];
            $cr['area_m2']              = $cr['area_m2'] !== null ? (float)$cr['area_m2'] : null;
            $cr['height_m']             = $cr['height_m'] !== null ? (float)$cr['height_m'] : null;
            $cr['capacity_theater']     = $cr['capacity_theater'] ? (int)$cr['capacity_theater'] : null;
            $cr['capacity_cocktail']    = $cr['capacity_cocktail'] ? (int)$cr['capacity_cocktail'] : null;
            $cr['capacity_auditorium']  = $cr['capacity_auditorium'] ? (int)$cr['capacity_auditorium'] : null;
            $cr['capacity_banquet']     = $cr['capacity_banquet'] ? (int)$cr['capacity_banquet'] : null;
            $cr['capacity_classroom']   = $cr['capacity_classroom'] ? (int)$cr['capacity_classroom'] : null;
            $cr['capacity_u_shape']     = $cr['capacity_u_shape'] ? (int)$cr['capacity_u_shape'] : null;
        }
    }

    // Notes
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

    // Resposta final com todos os relacionamentos, incluindo hotel_contact
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
            
            // Novo: Contato do hotel
            'hotel_contact'       => $hotel_contact,

            'media'               => $media,
            'room_categories'     => $room_categories,
            'dining'              => $dining,
            'facilities'          => $facilities,
            'convention'          => $convention ?: [
                'description' => '',
                'total_rooms' => null,
                'has_360'     => false
            ],
            'convention_rooms'    => $convention_rooms,
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
   
        require_once 'incentives/api_hotel_update.php';
    }

    // =====================================================
    // EXCLUIR INCENTIVO
    // =====================================================
    elseif ($request === 'excluir_incentive') {
        if ($method !== 'DELETE') {
            response(["error" => "Use m??todo DELETE"], 405);
        }

        $id = getIntParam('id');
        if (!$id) {
            response(["error" => "ID obrigat??rio"], 400);
        }

        $user_data = null;
        requireBearerToken($conn, $user_data, $cod_sis ?? null);

        pg_query($conn, "BEGIN");
        try {
            // Verifica se existe
            $checkSql = "SELECT inc_id FROM incentive.inc_program WHERE inc_id = $1";
            $checkRes = pg_query_params($conn, $checkSql, [$id]);
            if (!$checkRes || pg_num_rows($checkRes) === 0) {
                throw new Exception("Incentivo n??o encontrado");
            }

            // Remove filhos diretos
            execParams($conn, "DELETE FROM incentive.inc_media WHERE inc_id = $1", [$id], "Erro ao excluir m??dias");
            execParams($conn, "DELETE FROM incentive.inc_room_category WHERE inc_id = $1", [$id], "Erro ao excluir quartos");
            execParams($conn, "DELETE FROM incentive.inc_dining WHERE inc_id = $1", [$id], "Erro ao excluir dining");
            execParams($conn, "DELETE FROM incentive.inc_facility WHERE inc_id = $1", [$id], "Erro ao excluir facilities");
            execParams($conn, "DELETE FROM incentive.inc_note WHERE inc_id = $1", [$id], "Erro ao excluir notes");

            // Convention e salas
            $conv_id = getConventionId($conn, $id);
            if ($conv_id) {
                execParams(
                    $conn,
                    "DELETE FROM incentive.inc_convention_room WHERE inc_convention_id = $1",
                    [$conv_id],
                    "Erro ao excluir salas de conven????o"
                );
                execParams(
                    $conn,
                    "DELETE FROM incentive.inc_convention WHERE inc_convention_id = $1",
                    [$conv_id],
                    "Erro ao excluir convention"
                );
            }

            // Remove o programa
            $result = execParams(
                $conn,
                "DELETE FROM incentive.inc_program WHERE inc_id = $1",
                [$id],
                "Erro ao excluir incentivo"
            );

            if (!$result || pg_affected_rows($result) == 0) {
                throw new Exception("Incentivo n??o encontrado");
            }

            pg_query($conn, "COMMIT");

            response([
                "success" => true,
                "message" => "Incentivo exclu??do com sucesso"
            ]);
        } catch (Exception $e) {
            pg_query($conn, "ROLLBACK");
            response(["error" => $e->getMessage()], 400);
        }
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