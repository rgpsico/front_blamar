<?php 


   
        if ($method !== 'PUT') {
            response(["error" => "Use método PUT"], 405);
        }

        $id = getIntParam('id');
        if (!$id) {
            response(["error" => "ID obrigatório"], 400);
        }

        $user_data = null;
       // requireBearerToken($conn, $user_data, $cod_sis ?? null);

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
    'inc_name', 'inc_description', 'hotel_ref_id', 'hotel_name_snapshot',
    'city_name', 'country_code', 'inc_status', 'inc_is_active',
    'star_rating', 'total_rooms', 'floor_plan_url'
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
    