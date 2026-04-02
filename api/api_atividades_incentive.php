<?php

/**
 * API para gerenciamento das tabelas incentive_activities e incentive_activities_fotos
 *
 * Descrição:
 * Controla a listagem, criação, atualização e exclusão de Activities do módulo Incentive,
 * incluindo informações sobre cidade, fotos, filtros (classic, favourite, out of the box) e status ativo.
 *
 * Endpoints:
 * - GET  ?request=listar_activities&filtro_nome=Sugar&filtro_ativo=true&filtro_cidade=1&filtro_classic=true&limit=100
 *         → Lista todas as activities com filtros opcionais.
 *
 * - GET  ?request=buscar_activity&id=123
 *         → Busca os detalhes completos de uma activity específica (inclui fotos).
 *
 * - POST ?request=criar_activity
 *         → Cria uma nova activity.
 *           Body JSON:
 *           {
 *              "cidade_id": 1,
 *              "nome": "Sunset on top of Sugar Loaf",
 *              "slug": "sunset-sugar-loaf",
 *              "descricao_curta": "Resumo da atividade...",
 *              "descricao_longa": "Descrição completa...",
 *              "nota_equipe": "Personal note from the team...",
 *              "capacidade_min": 50,
 *              "capacidade_max": 550,
 *              "price_range": 3,
 *              "latitude": -22.9488,
 *              "longitude": -43.1577,
 *              "mapa_google": "https://www.google.com/maps/embed?pb=...",
 *              "is_classic": false,
 *              "is_favourite": true,
 *              "is_out_of_box": false,
 *              "is_active": true
 *           }
 *
 * - PUT  ?request=atualizar_activity&id=123
 *         → Atualiza campos específicos de uma activity existente (partial update).
 *
 * - DELETE ?request=excluir_activity&id=123
 *           → Remove a activity e suas fotos (CASCADE).
 *
 * - GET  ?request=listar_fotos&activity_id=123
 *         → Lista todas as fotos de uma activity.
 *
 * - POST ?request=adicionar_foto&activity_id=123
 *         → Adiciona uma foto à activity.
 *           Body JSON:
 *           {
 *              "url": "foto.jpg",
 *              "is_capa": true,
 *              "ordem": 0
 *           }
 *
 * - PUT  ?request=atualizar_foto&id=5
 *         → Atualiza dados de uma foto.
 *
 * - DELETE ?request=excluir_foto&id=5
 *           → Remove uma foto.
 *
 * - GET ?request=listar_cidades
 *         → Lista todas as cidades disponíveis (join com sbd95.cidades).
 *
 * Tabelas relacionadas:
 * - incentive.activities
 * - incentive.activities_fotos
 * - sbd95.cidades (join lógico por cidade_id BIGINT)
 *
 * Retornos:
 * - 200: Sucesso
 * - 201: Criado
 * - 400: Erro de parâmetro
 * - 404: Registro não encontrado
 * - 405: Método não permitido
 * - 500: Erro interno
 */

// ========================================
// 🔧 CONFIGURAÇÕES INICIAIS
// ========================================
date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../util/connection.php';

$BASE_URL_IMAGEM = "http://www.blumar.com.br/global/main_site/images/incentive_activities/";

// Função padrão de resposta JSON
function response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Monta array de fotos da activity a partir da tabela de fotos
function montarFotos($conn, $activity_id, $baseUrl)
{
    $sql = "
        SELECT id, url, is_capa, ordem
        FROM incentive.activities_fotos
        WHERE activity_id = $1
        ORDER BY ordem ASC, id ASC
    ";
    $result = pg_query_params($conn, $sql, [$activity_id]);
    $fotos = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $fotos[] = [
                'id'         => (int)$row['id'],
                'url'        => $baseUrl . $row['url'],
                'is_capa'    => $row['is_capa'] === 't',
                'ordem'      => (int)$row['ordem']
            ];
        }
    }
    return $fotos;
}

// Formata price_range numérico em cifrões legíveis
function formatarPriceRange(&$row)
{
    $pr = (int)($row['price_range'] ?? 0);
    $row['price_range_label'] = $pr > 0 ? str_repeat('$', $pr) : null;
}

// Formata campos booleanos do PostgreSQL ('t'/'f') para bool PHP
function formatarBooleans(&$row)
{
    $bools = ['is_classic', 'is_favourite', 'is_out_of_box', 'is_active'];
    foreach ($bools as $campo) {
        if (isset($row[$campo])) {
            $row[$campo] = $row[$campo] === 't';
        }
    }
}

// Helper: NULL se vazio
function formatString($value)
{
    if ($value === null || $value === '') return null;
    return $value;
}

// Helper: booleano para SQL ('t'/'f')
function formatBoolean($valor)
{
    if ($valor === null || $valor === '') return null;
    return (bool)$valor ? 't' : 'f';
}

// Helper: inteiro ou NULL
function formatInt($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) return null;
    return (int)$value;
}

// Helper: float/numeric ou NULL
function formatFloat($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) return null;
    return (float)$value;
}

$request = isset($_GET['request']) ? $_GET['request'] : null;
if (!$request) {
    response(["error" => "Parâmetro 'request' é obrigatório"], 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($request) {

        // =========================================================
        // 🔹 ROTA 1: Listar Activities (GET)
        // =========================================================
        case 'listar_activities':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $filtro_nome      = isset($_GET['filtro_nome'])      ? trim($_GET['filtro_nome'])      : null;
            $filtro_ativo     = isset($_GET['filtro_ativo'])     ? $_GET['filtro_ativo']           : 'all';
            $filtro_cidade    = isset($_GET['filtro_cidade'])    ? trim($_GET['filtro_cidade'])  : null;
            $filtro_classic   = isset($_GET['filtro_classic'])   ? $_GET['filtro_classic']         : null;
            $filtro_favourite = isset($_GET['filtro_favourite']) ? $_GET['filtro_favourite']       : null;
            $filtro_outofbox  = isset($_GET['filtro_outofbox'])  ? $_GET['filtro_outofbox']        : null;
            $limit            = isset($_GET['limit'])            ? intval($_GET['limit'])          : 100;

            $params = [];
            $where  = [];
            $idx    = 1;

            if ($filtro_nome) {
                $where[]  = "a.nome ILIKE $" . $idx++;
                $params[] = "%{$filtro_nome}%";
            }
            if ($filtro_cidade) {
                $where[]  = "a.cidade_id = $" . $idx++;
                $params[] = $filtro_cidade;
            }
            if ($filtro_ativo && $filtro_ativo !== 'all') {
                $where[]  = "a.is_active = $" . $idx++;
                $params[] = ($filtro_ativo === 'true' ? 't' : 'f');
            }
            if ($filtro_classic !== null) {
                $where[]  = "a.is_classic = $" . $idx++;
                $params[] = ($filtro_classic === 'true' ? 't' : 'f');
            }
            if ($filtro_favourite !== null) {
                $where[]  = "a.is_favourite = $" . $idx++;
                $params[] = ($filtro_favourite === 'true' ? 't' : 'f');
            }
            if ($filtro_outofbox !== null) {
                $where[]  = "a.is_out_of_box = $" . $idx++;
                $params[] = ($filtro_outofbox === 'true' ? 't' : 'f');
            }

            $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";
            $params[]  = $limit;

            $sql = "
                SELECT
                    a.*,
                    c.nome_cid AS cidade_nome,
                    f.url      AS foto_capa_url
                FROM incentive.activities a
                LEFT JOIN sbd95.cidades c ON a.cidade_id = c.cid
                LEFT JOIN incentive.activities_fotos f
                    ON f.activity_id = a.id AND f.is_capa = TRUE
                {$where_sql}
                ORDER BY a.nome
                LIMIT $" . $idx . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception(pg_last_error($conn));

            $activities = [];
            while ($row = pg_fetch_assoc($result)) {
                formatarBooleans($row);
                formatarPriceRange($row);
                $row['foto_capa_url'] = $row['foto_capa_url']
                    ? $BASE_URL_IMAGEM . $row['foto_capa_url']
                    : null;
                $activities[] = $row;
            }

            response($activities);
            break;

        // =========================================================
        // 🔹 ROTA 2: Buscar Activity por ID (GET)
        // =========================================================
        case 'buscar_activity':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            $sql = "
                SELECT
                    a.*,
                    c.nome_cid AS cidade_nome
                FROM incentive.activities a
                LEFT JOIN sbd95.cidades c ON a.cidade_id = c.cid
                WHERE a.id = $1
                LIMIT 1
            ";
            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Activity não encontrada"], 404);
            }

            $activity = pg_fetch_assoc($result);
            formatarBooleans($activity);
            formatarPriceRange($activity);

            // Busca todas as fotos separadamente
            $activity['fotos'] = montarFotos($conn, $id, $BASE_URL_IMAGEM);

            response($activity);
            break;

        // =========================================================
        // 🔹 ROTA 3: Criar Activity (POST)
        // =========================================================
        case 'criar_activity':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);
            if (empty($input)) response(["error" => "Dados são obrigatórios no body JSON"], 400);

            // Campos obrigatórios
            if (empty($input['nome'])) {
                response(["error" => "Campo obrigatório 'nome' não fornecido"], 400);
            }

            $campos = [
                'cidade_id', 'nome', 'slug', 'descricao_curta', 'descricao_longa',
                'nota_equipe', 'capacidade_min', 'capacidade_max', 'price_range',
                'latitude', 'longitude', 'mapa_google',
                'is_classic', 'is_favourite', 'is_out_of_box', 'is_active'
            ];

            $cols         = [];
            $placeholders = [];
            $params       = [];
            $idx          = 1;

            foreach ($campos as $campo) {
                $valor = $input[$campo] ?? null;

                if (in_array($campo, ['is_classic', 'is_favourite', 'is_out_of_box', 'is_active'])) {
                    $formatted = formatBoolean($valor ?? false);
                } elseif (in_array($campo, ['capacidade_min', 'capacidade_max', 'price_range'])) {
                    $formatted = formatInt($valor);
                } elseif (in_array($campo, ['latitude', 'longitude'])) {
                    $formatted = formatFloat($valor);
                } else {
                    $formatted = formatString($valor);
                }

                // Pula campos nulos (deixa o DEFAULT do banco agir)
                if ($formatted === null) continue;

                $cols[]         = $campo;
                $placeholders[] = '$' . $idx++;
                $params[]       = $formatted;
            }

            $sql = "
                INSERT INTO incentive.activities (" . implode(', ', $cols) . ")
                VALUES (" . implode(', ', $placeholders) . ")
                RETURNING id
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Erro ao inserir Activity: " . pg_last_error($conn));
            }

            $row         = pg_fetch_assoc($result);
            $activity_id = $row['id'];

            response([
                'success'     => true,
                'message'     => 'Activity criada com sucesso!',
                'activity_id' => $activity_id
            ], 201);
            break;

        // =========================================================
        // 🔹 ROTA 4: Atualizar Activity (PUT)
        // =========================================================
        case 'atualizar_activity':
            if ($method !== 'PUT') response(["error" => "Método não permitido. Use PUT."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);
            if (empty($input)) response(["error" => "Dados são obrigatórios no body JSON"], 400);

            $mapeamentos = [
                'cidade_id'     => ['campo' => 'cidade_id',     'tipo' => 'string'],
                'nome'          => ['campo' => 'nome',          'tipo' => 'string'],
                'slug'          => ['campo' => 'slug',          'tipo' => 'string'],
                'descricao_curta' => ['campo' => 'descricao_curta', 'tipo' => 'string'],
                'descricao_longa' => ['campo' => 'descricao_longa', 'tipo' => 'string'],
                'nota_equipe'   => ['campo' => 'nota_equipe',   'tipo' => 'string'],
                'capacidade_min'=> ['campo' => 'capacidade_min','tipo' => 'int'],
                'capacidade_max'=> ['campo' => 'capacidade_max','tipo' => 'int'],
                'price_range'   => ['campo' => 'price_range',   'tipo' => 'int'],
                'latitude'      => ['campo' => 'latitude',      'tipo' => 'float'],
                'longitude'     => ['campo' => 'longitude',     'tipo' => 'float'],
                'mapa_google'   => ['campo' => 'mapa_google',   'tipo' => 'string'],
                'is_classic'    => ['campo' => 'is_classic',    'tipo' => 'bool'],
                'is_favourite'  => ['campo' => 'is_favourite',  'tipo' => 'bool'],
                'is_out_of_box' => ['campo' => 'is_out_of_box', 'tipo' => 'bool'],
                'is_active'     => ['campo' => 'is_active',     'tipo' => 'bool'],
            ];

            $set     = [];
            $params  = [];
            $idx     = 1;
            $updated = false;

            foreach ($input as $chave => $valor) {
                $map = $mapeamentos[$chave] ?? null;
                if (!$map) continue;

                if ($map['tipo'] === 'int')    $formatted = formatInt($valor);
                elseif ($map['tipo'] === 'float')  $formatted = formatFloat($valor);
                elseif ($map['tipo'] === 'bool')   $formatted = formatBoolean($valor);
                else                               $formatted = formatString($valor);

                if ($formatted === null) continue;

                $set[]    = "{$map['campo']} = $" . $idx;
                $params[] = $formatted;
                $idx++;
                $updated  = true;
            }

            if (!$updated) {
                response(["success" => false, "message" => "Nenhuma alteração válida realizada"], 200);
            }

            // Atualiza atualizado_em automaticamente
            $set[]    = "atualizado_em = NOW()";
            $params[] = $id;

            $sql = "
                UPDATE incentive.activities
                SET " . implode(', ', $set) . "
                WHERE id = $" . $idx . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Erro ao atualizar Activity: " . pg_last_error($conn));
            }

            response([
                'success' => true,
                'message' => 'Activity atualizada com sucesso!',
                'id'      => $id
            ]);
            break;

        // =========================================================
        // 🔹 ROTA 5: Excluir Activity (DELETE)
        // =========================================================
        case 'excluir_activity':
            if ($method !== 'DELETE') response(["error" => "Método não permitido. Use DELETE."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            // Fotos são removidas automaticamente pelo ON DELETE CASCADE
            $sql    = "DELETE FROM incentive.activities WHERE id = $1";
            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result) throw new Exception(pg_last_error($conn));

            if (pg_affected_rows($result) > 0) {
                response(["success" => true, "message" => "Activity excluída com sucesso"]);
            } else {
                response(["error" => "Activity não encontrada"], 404);
            }
            break;

        // =========================================================
        // 🔹 ROTA 6: Listar Fotos de uma Activity (GET)
        // =========================================================
        case 'listar_fotos':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : null;
            if (!$activity_id) response(["error" => "activity_id é obrigatório"], 400);

            $fotos = montarFotos($conn, $activity_id, $BASE_URL_IMAGEM);
            response($fotos);
            break;

        // =========================================================
        // 🔹 ROTA 7: Adicionar Foto (POST)
        // =========================================================
        case 'adicionar_foto':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            $activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : null;
            if (!$activity_id) response(["error" => "activity_id é obrigatório"], 400);
            if (empty($input['url'])) response(["error" => "Campo 'url' é obrigatório"], 400);

            $is_capa = formatBoolean($input['is_capa'] ?? false);
            $ordem   = formatInt($input['ordem'] ?? 0) ?? 0;
            $url     = formatString($input['url']);

            // Se for capa, remove a flag das outras fotos desta activity
            if ($is_capa === 't') {
                pg_query_params($conn,
                    "UPDATE incentive.activities_fotos SET is_capa = FALSE WHERE activity_id = $1",
                    [$activity_id]
                );
            }

            $sql = "
                INSERT INTO incentive.activities_fotos (activity_id, url, is_capa, ordem)
                VALUES ($1, $2, $3, $4)
                RETURNING id
            ";
            $result = pg_query_params($conn, $sql, [$activity_id, $url, $is_capa, $ordem]);
            if (!$result) {
                throw new Exception("Erro ao inserir foto: " . pg_last_error($conn));
            }

            $row = pg_fetch_assoc($result);
            response([
                'success'  => true,
                'message'  => 'Foto adicionada com sucesso!',
                'foto_id'  => $row['id']
            ], 201);
            break;

        // =========================================================
        // 🔹 ROTA 8: Atualizar Foto (PUT)
        // =========================================================
        case 'atualizar_foto':
            if ($method !== 'PUT') response(["error" => "Método não permitido. Use PUT."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "ID da foto é obrigatório"], 400);
            if (empty($input)) response(["error" => "Dados são obrigatórios no body JSON"], 400);

            $set     = [];
            $params  = [];
            $idx     = 1;
            $updated = false;

            if (isset($input['url'])) {
                $set[]    = "url = $" . $idx++;
                $params[] = formatString($input['url']);
                $updated  = true;
            }
            if (isset($input['ordem'])) {
                $set[]    = "ordem = $" . $idx++;
                $params[] = formatInt($input['ordem']);
                $updated  = true;
            }
            if (isset($input['is_capa'])) {
                // Busca activity_id desta foto para limpar a flag das outras
                $r = pg_query_params($conn,
                    "SELECT activity_id FROM incentive.activities_fotos WHERE id = $1",
                    [$id]
                );
                if ($r && pg_num_rows($r) > 0) {
                    $f = pg_fetch_assoc($r);
                    if ($input['is_capa']) {
                        pg_query_params($conn,
                            "UPDATE incentive.activities_fotos SET is_capa = FALSE WHERE activity_id = $1",
                            [$f['activity_id']]
                        );
                    }
                }
                $set[]    = "is_capa = $" . $idx++;
                $params[] = formatBoolean($input['is_capa']);
                $updated  = true;
            }

            if (!$updated) {
                response(["success" => false, "message" => "Nenhuma alteração válida realizada"], 200);
            }

            $params[] = $id;
            $sql = "UPDATE incentive.activities_fotos SET " . implode(', ', $set) . " WHERE id = $" . $idx;

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Erro ao atualizar foto: " . pg_last_error($conn));
            }

            response(['success' => true, 'message' => 'Foto atualizada com sucesso!', 'id' => $id]);
            break;

        // =========================================================
        // 🔹 ROTA 9: Excluir Foto (DELETE)
        // =========================================================
        case 'excluir_foto':
            if ($method !== 'DELETE') response(["error" => "Método não permitido. Use DELETE."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "ID da foto é obrigatório"], 400);

            $sql    = "DELETE FROM incentive.activities_fotos WHERE id = $1";
            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result) throw new Exception(pg_last_error($conn));

            if (pg_affected_rows($result) > 0) {
                response(["success" => true, "message" => "Foto excluída com sucesso"]);
            } else {
                response(["error" => "Foto não encontrada"], 404);
            }
            break;

        // =========================================================
        // 🔹 ROTA AUX: Listar Cidades (GET)
        // =========================================================
        case 'listar_cidades':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $sql    = "SELECT cid AS id, nome_cid AS name FROM sbd95.cidades ORDER BY nome_cid ASC";
            $result = pg_query($conn, $sql);
            $cidades = [];
            if ($result) {
                while ($row = pg_fetch_assoc($result)) {
                    $cidades[] = $row;
                }
            }

            response($cidades);
            break;

        default:
            response(["error" => "Rota inválida: '{$request}'"], 400);
    }
} catch (Exception $e) {
    error_log("Erro na API de Activities: " . $e->getMessage());
    response(["error" => "Erro no servidor: " . $e->getMessage()], 500);
}