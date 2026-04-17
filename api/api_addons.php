<?php

/**
 * API de Add-ons adaptada ao schema real:
 * - incentive.addon
 * - incentive.addon_imagem
 * - incentive.addon_localizacao
 *
 * A resposta preserva o contrato esperado pelo frontend Vue.
 */

date_default_timezone_set('America/Sao_Paulo');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../util/connection.php';

$BASE_URL_IMAGEM = 'http://www.blumar.com.br/global/main_site/images/incentive_addons/';

function response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail($message, $code = 400)
{
    response(['error' => $message], $code);
}

function parseBody()
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function normalizeBool($value, $default = null)
{
    if ($value === null || $value === '') {
        return $default;
    }
    if (is_bool($value)) {
        return $value;
    }
    if (is_string($value)) {
        $value = strtolower(trim($value));
        if (in_array($value, ['1', 'true', 't', 'yes', 'sim'], true)) {
            return true;
        }
        if (in_array($value, ['0', 'false', 'f', 'no', 'nao', 'não'], true)) {
            return false;
        }
    }
    return (bool)$value;
}

function normalizeInt($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return null;
    }
    return (int)$value;
}

function normalizeFloat($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return null;
    }
    return (float)$value;
}

function normalizeString($value)
{
    if ($value === null) {
        return null;
    }
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function slugify($value)
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$value);
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim((string)$value, '-');
}

function buildMapUrl($latitude, $longitude)
{
    if ($latitude === null || $longitude === null) {
        return null;
    }
    return 'https://maps.google.com/maps?q=' . rawurlencode($latitude . ',' . $longitude) . '&z=15&output=embed';
}

function fotoUrlCompleta($url, $baseUrl)
{
    $raw = trim((string)$url);
    if ($raw === '') {
        return null;
    }
    if (preg_match('/^https?:\/\//i', $raw) || strpos($raw, '//') === 0) {
        return $raw;
    }
    return $baseUrl . ltrim($raw, '/');
}

function montarFotos($conn, $addonId, $baseUrl)
{
    $sql = "
        SELECT
            id,
            url_imagem AS url,
            principal AS is_capa,
            ordem
        FROM incentive.addon_imagem
        WHERE fk_addon_id = $1
        ORDER BY ordem ASC, id ASC
    ";
    $result = pg_query_params($conn, $sql, [$addonId]);
    if (!$result) {
        throw new Exception(pg_last_error($conn));
    }

    $fotos = [];
    while ($row = pg_fetch_assoc($result)) {
        $fotos[] = [
            'id' => (int)$row['id'],
            'url' => fotoUrlCompleta($row['url'], $baseUrl),
            'is_capa' => normalizeBool($row['is_capa'], false),
            'ordem' => normalizeInt($row['ordem']) ?? 0,
        ];
    }
    return $fotos;
}

function buscarLocalizacao($conn, $addonId)
{
    $sql = "
        SELECT id, nome, endereco, latitude, longitude
        FROM incentive.addon_localizacao
        WHERE fk_addon_id = $1
        ORDER BY id ASC
        LIMIT 1
    ";
    $result = pg_query_params($conn, $sql, [$addonId]);
    if (!$result) {
        throw new Exception(pg_last_error($conn));
    }
    return pg_num_rows($result) > 0 ? pg_fetch_assoc($result) : null;
}

function formatAddonRow($row, $baseUrl, $detail = false)
{
    $titulo = (string)($row['nome'] ?? $row['titulo'] ?? '');
    $descricao = (string)($row['descricao'] ?? '');
    $latitude = isset($row['latitude']) ? normalizeFloat($row['latitude']) : null;
    $longitude = isset($row['longitude']) ? normalizeFloat($row['longitude']) : null;
    $isActive = normalizeBool($row['is_active'] ?? $row['ativo'], true);
    $isFavourite = normalizeBool($row['is_favourite'] ?? $row['destaque'], false);

    $payload = [
        'id' => normalizeInt($row['id']),
        'cidade_id' => normalizeInt($row['cidade_id'] ?? $row['fk_cidade_id']),
        'cidade_nome' => $row['cidade_nome'] ?? null,
        'nome' => $titulo,
        'slug' => slugify($titulo),
        'descricao_curta' => $descricao,
        'descricao_longa' => $descricao,
        'nota_equipe' => null,
        'price_range' => null,
        'price_range_label' => '',
        'latitude' => $latitude,
        'longitude' => $longitude,
        'mapa_google' => buildMapUrl($latitude, $longitude),
        'is_classic' => false,
        'is_favourite' => $isFavourite,
        'is_out_of_box' => false,
        'is_active' => $isActive,
        'foto_capa_url' => fotoUrlCompleta($row['foto_capa_url'] ?? null, $baseUrl),
    ];

    if ($detail) {
        $payload['fotos'] = [];
    }

    return $payload;
}

function upsertLocalizacao($conn, $addonId, $payload)
{
    $latitude = normalizeFloat($payload['latitude'] ?? null);
    $longitude = normalizeFloat($payload['longitude'] ?? null);

    $existing = buscarLocalizacao($conn, $addonId);

    if ($latitude === null && $longitude === null) {
        if ($existing) {
            $delete = pg_query_params($conn, 'DELETE FROM incentive.addon_localizacao WHERE id = $1', [$existing['id']]);
            if (!$delete) {
                throw new Exception('Erro ao excluir localizacao: ' . pg_last_error($conn));
            }
        }
        return;
    }

    if ($existing) {
        $result = pg_query_params(
            $conn,
            'UPDATE incentive.addon_localizacao SET latitude = $1, longitude = $2 WHERE id = $3',
            [$latitude, $longitude, $existing['id']]
        );
        if (!$result) {
            throw new Exception('Erro ao atualizar localizacao: ' . pg_last_error($conn));
        }
        return;
    }

    $result = pg_query_params(
        $conn,
        'INSERT INTO incentive.addon_localizacao (fk_addon_id, latitude, longitude) VALUES ($1, $2, $3)',
        [$addonId, $latitude, $longitude]
    );
    if (!$result) {
        throw new Exception('Erro ao inserir localizacao: ' . pg_last_error($conn));
    }
}

$request = isset($_GET['request']) ? trim((string)$_GET['request']) : '';
if ($request === '') {
    fail("Parametro 'request' e obrigatorio", 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = parseBody();

try {
    switch ($request) {
        case 'listar_addons':
            if ($method !== 'GET') {
                fail('Metodo nao permitido. Use GET.', 405);
            }

            $filtroNome = isset($_GET['filtro_nome']) ? trim((string)$_GET['filtro_nome']) : null;
            $filtroAtivo = $_GET['filtro_ativo'] ?? 'all';
            $filtroCidade = isset($_GET['filtro_cidade']) ? trim((string)$_GET['filtro_cidade']) : null;
            $filtroClassic = $_GET['filtro_classic'] ?? null;
            $filtroFavourite = $_GET['filtro_favourite'] ?? null;
            $filtroOutOfBox = $_GET['filtro_outofbox'] ?? null;
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 100)));

            if ($filtroClassic === 'true' || $filtroOutOfBox === 'true') {
                response([]);
            }

            $where = [];
            $params = [];
            $idx = 1;

            if ($filtroNome) {
                $where[] = 'a.titulo ILIKE $' . $idx++;
                $params[] = '%' . $filtroNome . '%';
            }
            if ($filtroCidade) {
                $where[] = 'a.fk_cidade_id = $' . $idx++;
                $params[] = (int)$filtroCidade;
            }
            if ($filtroAtivo !== null && $filtroAtivo !== '' && $filtroAtivo !== 'all') {
                $where[] = 'a.ativo = $' . $idx++;
                $params[] = normalizeBool($filtroAtivo, true) ? 't' : 'f';
            }
            if ($filtroFavourite !== null && $filtroFavourite !== '') {
                $where[] = 'a.destaque = $' . $idx++;
                $params[] = normalizeBool($filtroFavourite, false) ? 't' : 'f';
            }

            $params[] = $limit;
            $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "
                SELECT
                    a.id,
                    a.titulo AS nome,
                    a.descricao,
                    a.fk_cidade_id AS cidade_id,
                    a.ativo AS is_active,
                    a.destaque AS is_favourite,
                    c.nome_cid AS cidade_nome,
                    l.latitude,
                    l.longitude,
                    img.url_imagem AS foto_capa_url
                FROM incentive.addon a
                LEFT JOIN sbd95.cidades c ON a.fk_cidade_id = c.cod_cid
                LEFT JOIN LATERAL (
                    SELECT latitude, longitude
                    FROM incentive.addon_localizacao loc
                    WHERE loc.fk_addon_id = a.id
                    ORDER BY loc.id ASC
                    LIMIT 1
                ) l ON TRUE
                LEFT JOIN LATERAL (
                    SELECT url_imagem
                    FROM incentive.addon_imagem i
                    WHERE i.fk_addon_id = a.id
                    ORDER BY i.principal DESC, i.ordem ASC, i.id ASC
                    LIMIT 1
                ) img ON TRUE
                {$whereSql}
                ORDER BY a.titulo ASC
                LIMIT $" . $idx;

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $addons = [];
            while ($row = pg_fetch_assoc($result)) {
                $addons[] = formatAddonRow($row, $BASE_URL_IMAGEM);
            }

            response($addons);
            break;

        case 'buscar_addon':
            if ($method !== 'GET') {
                fail('Metodo nao permitido. Use GET.', 405);
            }

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                fail('ID e obrigatorio', 400);
            }

            $sql = "
                SELECT
                    a.id,
                    a.titulo AS nome,
                    a.descricao,
                    a.fk_cidade_id AS cidade_id,
                    a.ativo AS is_active,
                    a.destaque AS is_favourite,
                    c.nome_cid AS cidade_nome,
                    l.latitude,
                    l.longitude,
                    img.url_imagem AS foto_capa_url
                FROM incentive.addon a
                LEFT JOIN sbd95.cidades c ON a.fk_cidade_id = c.cod_cid
                LEFT JOIN LATERAL (
                    SELECT latitude, longitude
                    FROM incentive.addon_localizacao loc
                    WHERE loc.fk_addon_id = a.id
                    ORDER BY loc.id ASC
                    LIMIT 1
                ) l ON TRUE
                LEFT JOIN LATERAL (
                    SELECT url_imagem
                    FROM incentive.addon_imagem i
                    WHERE i.fk_addon_id = a.id
                    ORDER BY i.principal DESC, i.ordem ASC, i.id ASC
                    LIMIT 1
                ) img ON TRUE
                WHERE a.id = $1
                LIMIT 1
            ";

            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }
            if (pg_num_rows($result) === 0) {
                fail('Add-on nao encontrado', 404);
            }

            $addon = formatAddonRow(pg_fetch_assoc($result), $BASE_URL_IMAGEM, true);
            $addon['fotos'] = montarFotos($conn, $id, $BASE_URL_IMAGEM);

            response($addon);
            break;

        case 'criar_addon':
            if ($method !== 'POST') {
                fail('Metodo nao permitido. Use POST.', 405);
            }

            $nome = normalizeString($input['nome'] ?? null);
            if ($nome === null) {
                fail("Campo obrigatorio 'nome' nao fornecido", 400);
            }

            $descricaoLonga = normalizeString($input['descricao_longa'] ?? null);
            $descricaoCurta = normalizeString($input['descricao_curta'] ?? null);
            $descricao = $descricaoLonga ?? $descricaoCurta;
            $cidadeId = normalizeInt($input['cidade_id'] ?? null);
            $isActive = normalizeBool($input['is_active'] ?? true, true);
            $isFavourite = normalizeBool($input['is_favourite'] ?? false, false);

            pg_query($conn, 'BEGIN');

            $insert = pg_query_params(
                $conn,
                "
                INSERT INTO incentive.addon (
                    titulo,
                    descricao,
                    cidade,
                    fk_cidade_id,
                    ativo,
                    destaque,
                    atualizado_em
                )
                VALUES ($1, $2, NULL, $3, $4, $5, NOW())
                RETURNING id
                ",
                [$nome, $descricao, $cidadeId, $isActive ? 't' : 'f', $isFavourite ? 't' : 'f']
            );

            if (!$insert) {
                pg_query($conn, 'ROLLBACK');
                throw new Exception('Erro ao inserir add-on: ' . pg_last_error($conn));
            }

            $row = pg_fetch_assoc($insert);
            $addonId = (int)$row['id'];

            upsertLocalizacao($conn, $addonId, $input);

            pg_query($conn, 'COMMIT');

            response([
                'success' => true,
                'message' => 'Add-on criado com sucesso!',
                'addon_id' => $addonId,
            ], 201);
            break;

        case 'atualizar_addon':
            if ($method !== 'PUT') {
                fail('Metodo nao permitido. Use PUT.', 405);
            }

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                fail('ID e obrigatorio', 400);
            }

            $set = [];
            $params = [];
            $idx = 1;

            if (array_key_exists('nome', $input)) {
                $set[] = 'titulo = $' . $idx++;
                $params[] = normalizeString($input['nome']);
            }
            if (array_key_exists('descricao_curta', $input) || array_key_exists('descricao_longa', $input)) {
                $descricao = normalizeString($input['descricao_longa'] ?? null) ?? normalizeString($input['descricao_curta'] ?? null);
                $set[] = 'descricao = $' . $idx++;
                $params[] = $descricao;
            }
            if (array_key_exists('cidade_id', $input)) {
                $set[] = 'fk_cidade_id = $' . $idx++;
                $params[] = normalizeInt($input['cidade_id']);
            }
            if (array_key_exists('is_active', $input)) {
                $set[] = 'ativo = $' . $idx++;
                $params[] = normalizeBool($input['is_active'], true) ? 't' : 'f';
            }
            if (array_key_exists('is_favourite', $input)) {
                $set[] = 'destaque = $' . $idx++;
                $params[] = normalizeBool($input['is_favourite'], false) ? 't' : 'f';
            }

            pg_query($conn, 'BEGIN');

            if (count($set) > 0) {
                $set[] = 'atualizado_em = NOW()';
                $params[] = $id;

                $sql = 'UPDATE incentive.addon SET ' . implode(', ', $set) . ' WHERE id = $' . $idx;
                $update = pg_query_params($conn, $sql, $params);
                if (!$update) {
                    pg_query($conn, 'ROLLBACK');
                    throw new Exception('Erro ao atualizar add-on: ' . pg_last_error($conn));
                }
            }

            if (
                array_key_exists('latitude', $input) ||
                array_key_exists('longitude', $input)
            ) {
                upsertLocalizacao($conn, $id, $input);
            }

            pg_query($conn, 'COMMIT');

            response([
                'success' => true,
                'message' => 'Add-on atualizado com sucesso!',
                'id' => $id,
            ]);
            break;

        case 'excluir_addon':
            if ($method !== 'DELETE') {
                fail('Metodo nao permitido. Use DELETE.', 405);
            }

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                fail('ID e obrigatorio', 400);
            }

            $result = pg_query_params($conn, 'DELETE FROM incentive.addon WHERE id = $1', [$id]);
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }
            if (pg_affected_rows($result) === 0) {
                fail('Add-on nao encontrado', 404);
            }

            response(['success' => true, 'message' => 'Add-on excluido com sucesso']);
            break;

        case 'listar_fotos':
            if ($method !== 'GET') {
                fail('Metodo nao permitido. Use GET.', 405);
            }

            $addonId = (int)($_GET['addon_id'] ?? 0);
            if ($addonId <= 0) {
                fail('addon_id e obrigatorio', 400);
            }

            response(montarFotos($conn, $addonId, $BASE_URL_IMAGEM));
            break;

        case 'adicionar_foto':
            if ($method !== 'POST') {
                fail('Metodo nao permitido. Use POST.', 405);
            }

            $addonId = (int)($_GET['addon_id'] ?? 0);
            if ($addonId <= 0) {
                fail('addon_id e obrigatorio', 400);
            }

            $url = normalizeString($input['url'] ?? null);
            if ($url === null) {
                fail("Campo 'url' e obrigatorio", 400);
            }

            $isCapa = normalizeBool($input['is_capa'] ?? false, false);
            $ordem = normalizeInt($input['ordem'] ?? 0) ?? 0;

            pg_query($conn, 'BEGIN');

            if ($isCapa) {
                $clear = pg_query_params($conn, 'UPDATE incentive.addon_imagem SET principal = FALSE WHERE fk_addon_id = $1', [$addonId]);
                if (!$clear) {
                    pg_query($conn, 'ROLLBACK');
                    throw new Exception('Erro ao atualizar capa: ' . pg_last_error($conn));
                }
            }

            $insert = pg_query_params(
                $conn,
                '
                INSERT INTO incentive.addon_imagem (fk_addon_id, url_imagem, principal, ordem)
                VALUES ($1, $2, $3, $4)
                RETURNING id
                ',
                [$addonId, $url, $isCapa ? 't' : 'f', $ordem]
            );

            if (!$insert) {
                pg_query($conn, 'ROLLBACK');
                throw new Exception('Erro ao inserir foto: ' . pg_last_error($conn));
            }

            $row = pg_fetch_assoc($insert);
            pg_query($conn, 'COMMIT');

            response([
                'success' => true,
                'message' => 'Foto adicionada com sucesso!',
                'foto_id' => (int)$row['id'],
            ], 201);
            break;

        case 'atualizar_foto':
            if ($method !== 'PUT') {
                fail('Metodo nao permitido. Use PUT.', 405);
            }

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                fail('ID da foto e obrigatorio', 400);
            }

            $fotoResult = pg_query_params(
                $conn,
                'SELECT id, fk_addon_id FROM incentive.addon_imagem WHERE id = $1 LIMIT 1',
                [$id]
            );
            if (!$fotoResult) {
                throw new Exception(pg_last_error($conn));
            }
            if (pg_num_rows($fotoResult) === 0) {
                fail('Foto nao encontrada', 404);
            }
            $fotoAtual = pg_fetch_assoc($fotoResult);
            $addonId = (int)$fotoAtual['fk_addon_id'];

            $set = [];
            $params = [];
            $idx = 1;

            if (array_key_exists('url', $input)) {
                $set[] = 'url_imagem = $' . $idx++;
                $params[] = normalizeString($input['url']);
            }
            if (array_key_exists('ordem', $input)) {
                $set[] = 'ordem = $' . $idx++;
                $params[] = normalizeInt($input['ordem']) ?? 0;
            }
            if (array_key_exists('is_capa', $input)) {
                $set[] = 'principal = $' . $idx++;
                $params[] = normalizeBool($input['is_capa'], false) ? 't' : 'f';
            }

            if (count($set) === 0) {
                response(['success' => false, 'message' => 'Nenhuma alteracao valida realizada']);
            }

            pg_query($conn, 'BEGIN');

            if (array_key_exists('is_capa', $input) && normalizeBool($input['is_capa'], false)) {
                $clear = pg_query_params($conn, 'UPDATE incentive.addon_imagem SET principal = FALSE WHERE fk_addon_id = $1', [$addonId]);
                if (!$clear) {
                    pg_query($conn, 'ROLLBACK');
                    throw new Exception('Erro ao limpar capa anterior: ' . pg_last_error($conn));
                }
            }

            $params[] = $id;
            $sql = 'UPDATE incentive.addon_imagem SET ' . implode(', ', $set) . ' WHERE id = $' . $idx;
            $update = pg_query_params($conn, $sql, $params);
            if (!$update) {
                pg_query($conn, 'ROLLBACK');
                throw new Exception('Erro ao atualizar foto: ' . pg_last_error($conn));
            }

            pg_query($conn, 'COMMIT');

            response(['success' => true, 'message' => 'Foto atualizada com sucesso!', 'id' => $id]);
            break;

        case 'excluir_foto':
            if ($method !== 'DELETE') {
                fail('Metodo nao permitido. Use DELETE.', 405);
            }

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                fail('ID da foto e obrigatorio', 400);
            }

            $result = pg_query_params($conn, 'DELETE FROM incentive.addon_imagem WHERE id = $1', [$id]);
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }
            if (pg_affected_rows($result) === 0) {
                fail('Foto nao encontrada', 404);
            }

            response(['success' => true, 'message' => 'Foto excluida com sucesso']);
            break;

        case 'listar_cidades':
            if ($method !== 'GET') {
                fail('Metodo nao permitido. Use GET.', 405);
            }

            $result = pg_query(
                $conn,
                "SELECT cod_cid AS id, nome_cid AS name FROM sbd95.cidades ORDER BY nome_cid ASC"
            );
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $cidades = [];
            while ($row = pg_fetch_assoc($result)) {
                $cidades[] = $row;
            }

            response($cidades);
            break;

        default:
            fail("Rota invalida: '{$request}'", 400);
    }
} catch (Exception $e) {
    @pg_query($conn, 'ROLLBACK');
    error_log('Erro na API de Add-ons: ' . $e->getMessage());
    response(['error' => 'Erro no servidor: ' . $e->getMessage()], 500);
}
