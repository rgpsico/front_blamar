<?php 

function upsertHotelContact($conn, $inc_id, $contact) {
    if ($contact === null) {
        execParams($conn, "DELETE FROM incentive.inc_hotel_contact WHERE inc_id = $1", [$inc_id], "Erro ao excluir contato");
        return null;
    }

    $address        = formatString($contact['address'] ?? null);
    $postal_code    = formatString($contact['postal_code'] ?? null);
    $state_code     = formatString($contact['state_code'] ?? null);
    $phone          = formatString($contact['phone'] ?? null);
    $email          = formatString($contact['email'] ?? null);
    $website_url    = formatString($contact['website_url'] ?? null);
    $google_maps_url= formatString($contact['google_maps_url'] ?? null);
    $latitude       = formatNumeric($contact['latitude'] ?? null);
    $longitude      = formatNumeric($contact['longitude'] ?? null);

    $res = execParams(
        $conn,
        "SELECT inc_contact_id FROM incentive.inc_hotel_contact WHERE inc_id = $1 LIMIT 1",
        [$inc_id],
        "Erro ao buscar contato"
    );

    if (pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        $inc_contact_id = (int)$row['inc_contact_id'];

        execParams(
            $conn,
            "UPDATE incentive.inc_hotel_contact
             SET address=$1, postal_code=$2, state_code=$3, phone=$4, email=$5, website_url=$6,
                 google_maps_url=$7, latitude=$8, longitude=$9
             WHERE inc_contact_id=$10",
            [$address,$postal_code,$state_code,$phone,$email,$website_url,$google_maps_url,$latitude,$longitude,$inc_contact_id],
            "Erro ao atualizar contato"
        );

        return $inc_contact_id;
    }

    $ins = execParams(
        $conn,
        "INSERT INTO incentive.inc_hotel_contact
            (inc_id, address, postal_code, state_code, phone, email, website_url, google_maps_url, latitude, longitude)
         VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10)
         RETURNING inc_contact_id",
        [$inc_id,$address,$postal_code,$state_code,$phone,$email,$website_url,$google_maps_url,$latitude,$longitude],
        "Erro ao inserir contato"
    );

    return (int) pg_fetch_result($ins, 0, 0);
}


   
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

         
            if (isset($input['hotel_contact']) && is_array($input['hotel_contact'])) {
                upsertHotelContact($conn, $id, $input['hotel_contact']);
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
    