<?php
date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../util/connection.php';

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    exit;
}

function fail($message, $status = 400) {
    respond(['error' => $message], $status);
}

function toIntOrNull($value) {
    if ($value === null || $value === '') return null;
    return is_numeric($value) ? (int)$value : null;
}

function resolveVenueCityId($conn, $input) {
    $rawDirect = $input['fk_cod_cidade'] ?? null;
    $direct = toIntOrNull($rawDirect);
    if ($direct !== null) return $direct;

    $rawAlt = $input['city_id'] ?? null;
    $directAlt = toIntOrNull($rawAlt);
    if ($directAlt !== null) return $directAlt;

    $cityName = trim((string)($input['city_name'] ?? $input['city'] ?? ''));
    if ($cityName === '') $cityName = trim((string)$rawDirect);
    if ($cityName === '') $cityName = trim((string)$rawAlt);
    if ($cityName === '') return null;

    $sql = "
        SELECT *
        FROM tarifario.cidade_tpo
        WHERE cidade_cod::text ILIKE $1
           OR nome_en ILIKE $1
           OR nome_pt ILIKE $1
        ORDER BY
            CASE
                WHEN lower(cidade_cod::text) = lower($2) THEN 0
                WHEN lower(nome_en) = lower($2) THEN 0
                WHEN lower(nome_pt) = lower($2) THEN 1
                ELSE 2
            END,
            nome_en
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, ["%{$cityName}%", $cityName]);
    if ($res && pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        $cidadeCod = toIntOrNull($row['cidade_cod'] ?? null);
        if ($cidadeCod !== null) return $cidadeCod;
        $codCid = toIntOrNull($row['cod_cid'] ?? null);
        if ($codCid !== null) return $codCid;
        $pkCidade = toIntOrNull($row['pk_cidade_tpo'] ?? null);
        if ($pkCidade !== null) return $pkCidade;
        $cid = toIntOrNull($row['cid'] ?? null);
        if ($cid !== null) return $cid;

        return null;
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    fail('Use método PUT', 405);
}

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    fail('ID inválido', 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (!$input || !is_array($input)) {
    fail('Body JSON inválido', 400);
}

if (!isset($conn) || !$conn) {
    fail('Conexão indisponível', 500);
}

pg_query($conn, "BEGIN");
try {
    $check = pg_query_params($conn, "SELECT venue_id FROM incentive.venues WHERE venue_id = $1", [$id]);
    if (!$check || pg_num_rows($check) === 0) {
        throw new Exception('Venue não encontrado');
    }

    $updates = [];
    $params = [];
    $idx = 1;

    if (array_key_exists('nome', $input)) {
        $updates[] = "nome = $" . $idx++;
        $params[] = $input['nome'];
    }
    if (array_key_exists('especialidade', $input)) {
        $updates[] = "especialidade = $" . $idx++;
        $params[] = $input['especialidade'];
    }
    if (array_key_exists('ativo', $input)) {
        $updates[] = "ativo = $" . $idx++;
        $params[] = filter_var($input['ativo'], FILTER_VALIDATE_BOOLEAN) ? 'TRUE' : 'FALSE';
    }
    if (array_key_exists('price_range', $input)) {
        $updates[] = "price_range = $" . $idx++;
        $params[] = $input['price_range'];
    }
    if (array_key_exists('capacity_min', $input)) {
        $updates[] = "capacity_min = $" . $idx++;
        $params[] = toIntOrNull($input['capacity_min']);
    }
    if (array_key_exists('capacity_max', $input)) {
        $updates[] = "capacity_max = $" . $idx++;
        $params[] = toIntOrNull($input['capacity_max']);
    }

    $resolvedCityId = resolveVenueCityId($conn, $input);
    $cityRawInput = trim((string)($input['fk_cod_cidade'] ?? $input['city_id'] ?? $input['city_name'] ?? $input['city'] ?? ''));
    if ($cityRawInput !== '' && $resolvedCityId === null) {
        throw new Exception('Cidade inválida: não foi possível resolver fk_cod_cidade para ID numérico');
    }
    if ($resolvedCityId !== null || array_key_exists('fk_cod_cidade', $input) || array_key_exists('city_id', $input)) {
        $updates[] = "fk_cod_cidade = $" . $idx++;
        $params[] = $resolvedCityId;
    }

    if (!empty($updates)) {
        $params[] = $id;
        $sql = "UPDATE incentive.venues SET " . implode(', ', $updates) . " WHERE venue_id = $" . $idx;
        $res = pg_query_params($conn, $sql, $params);
        if (!$res) {
            throw new Exception(pg_last_error($conn));
        }
    }

    $translations = $input['translations'] ?? null;
    if (!is_array($translations)) {
        $description = (string)($input['description'] ?? '');
        $short = (string)($input['short_description'] ?? '');
        $insight = (string)($input['insight'] ?? '');
        if ($description !== '' || $short !== '' || $insight !== '') {
            $translations = [
                'pt' => [
                    'descritivo' => $description,
                    'short_description' => $short,
                    'insight' => $insight
                ]
            ];
        }
    }

    if (is_array($translations)) {
        $del = pg_query_params($conn, "DELETE FROM incentive.venues_translations WHERE venue_id = $1", [$id]);
        if (!$del) {
            throw new Exception(pg_last_error($conn));
        }

        $sqlT = "
            INSERT INTO incentive.venues_translations
            (venue_id, language, descritivo, short_description, insight)
            VALUES ($1, $2, $3, $4, $5)
        ";
        foreach ($translations as $lang => $t) {
            if (!in_array($lang, ['pt', 'en', 'es'], true)) {
                continue;
            }
            $ins = pg_query_params($conn, $sqlT, [
                $id,
                $lang,
                $t['descritivo'] ?? null,
                $t['short_description'] ?? null,
                $t['insight'] ?? null
            ]);
            if (!$ins) {
                throw new Exception(pg_last_error($conn));
            }
        }
    }

    pg_query($conn, "COMMIT");
    respond([
        'success' => true,
        'message' => 'Dados do venue atualizados com sucesso',
        'venue_id' => $id
    ], 200);
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    fail('Erro interno do servidor: ' . $e->getMessage(), 500);
}
