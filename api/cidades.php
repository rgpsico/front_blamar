<?php

/**
 * API para gerenciamento da tabela tarifario.cidade_tpo
 *
 * Descrição:
 * Controla a listagem, criação, atualização e exclusão de cidades turísticas,
 * incluindo nomes multilíngues, descrições, fotos, meta tags, conteúdo temático
 * (destinos, atrações, weather, etc.) e configurações específicas (COVID, FIT, etc.).
 *
 * Endpoints:
 * - GET  ?request=listar_cidades&filtro_nome=Rio&filtro_regiao=3&limit=100
 *         → Lista todas as cidades filtrando por nome, região geográfica ou código.
 *
 * - GET  ?request=buscar_cidade&cidade_cod=123
 *         → Busca os detalhes completos de uma cidade específica pelo código.
 *
 * - POST ?request=criar_cidade
 *         → Cria uma nova cidade.
 *           Body JSON:
 *           {
 *              "nome_pt": "Rio de Janeiro",
 *              "nome_en": "Rio de Janeiro",
 *              "nome_esp": "Río de Janeiro",
 *              "descritivo_pt": "Descrição em português",
 *              "regiao": 3,
 *              "cidade_cod": "RJ001",
 *              "foto1": "uploads/cidades/rio_foto1.jpg",
 *              "foto2": "uploads/cidades/rio_foto2.jpg",
 *              "destino_pt": "Conteúdo destino PT",
 *              "title": "Título meta tag",
 *              "description": "Descrição meta tag"
 *           }
 *
 * - PUT  ?request=atualizar_cidade&cidade_cod=123
 *         → Atualiza campos específicos de uma cidade existente.
 *           Body JSON (exemplo parcial):
 *           {
 *              "nome_pt": "Rio de Janeiro Atualizado",
 *              "descritivo_en": "Updated English description",
 *              "vai_tour": true,
 *              "average_temp": "25°C"
 *           }
 *
 * - DELETE ?request=excluir_cidade&cidade_cod=123
 *           → Remove a cidade e conteúdos relacionados (se aplicável).
 *
 * Métodos suportados:
 * - GET: listar_cidades, buscar_cidade
 * - POST: criar_cidade
 * - PUT: atualizar_cidade
 * - DELETE: excluir_cidade
 *
 * Tabelas relacionadas:
 * - tarifario.cidade_tpo
 *
 * Retornos:
 * - 200: Sucesso
 * - 201: Criado
 * - 400: Erro de parâmetro
 * - 404: Cidade não encontrada
 * - 405: Método não permitido
 * - 500: Erro interno
 */

// ========================================
// 🔧 CONFIGURAÇÕES INICIAIS
// ========================================
date_default_timezone_set('America/Sao_Paulo');

$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:8080',
    'http://localhost:3000',
    // 'https://seusite.com.br',   ← add production domain later
];

if (in_array($http_origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $http_origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    // For dev you can keep *, but restrict in production
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400"); // cache preflight 24h

// Very important: answer OPTIONS immediately and STOP
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ────────────────────────────────────────────────
//  Normal code continues here...
// ────────────────────────────────────────────────



require_once '../util/connection.php';

$BASE_URL_IMAGEM = "https://www.blumar.com.br/"; // Ajuste se necessário para cidades

// Opções de região (usado em mapeamentos)
$regiao_options = [
    0 => 'Selecionar',
    1 => 'Norte',
    2 => 'Nordeste',
    3 => 'Sudeste',
    4 => 'Centro-Oeste',
    5 => 'Sul'
];

// Função padrão de resposta JSON
function response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Função auxiliar para montar array de imagens (adaptado para foto1-3, etc.)
function montarImagensCidade(&$row, $baseUrl)
{
    $row['imagens'] = [];
    $fotoCampos = ['foto1', 'foto2', 'foto3', 'cid_foto1', 'cid_foto2', 'cid_foto3', 'cid_foto4', 'cid_foto5', 'cid_foto6'];
    foreach ($fotoCampos as $index => $campo) {
        if (!empty($row[$campo])) {
            $row['imagens'][] = [
                'image_url' => $baseUrl . $row[$campo],
                'is_primary' => $index === 0,
                'alt_text' => $index === 0 ? 'Foto principal' : 'Foto adicional'
            ];
        }
    }
    // Limpa campos legados se quiser (opcional)
    // unset($row['foto1'], $row['foto2'], ...);
}

// Função para montar URLs de outros campos (ex: mapa_local, foto_capa)
function montarUrlsExtras(&$row, $baseUrl)
{
    $urlCampos = ['mapa_local', 'foto_capa', 'img_cid', 'foto_nova_cote', 'foto_webservices', 'youtube'];
    foreach ($urlCampos as $campo) {
        if (!empty($row[$campo])) {
            $row[$campo] = $baseUrl . $row[$campo];
        }
    }
}

// Helper para formatar strings (NULL se vazio)
function formatString($value)
{
    if ($value === null || $value === '') return 'NULL';
    return "'" . pg_escape_string($value) . "'";
}

// Helper para formatar inteiros (NULL se inválido)
function formatInt($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) return 'NULL';
    return (int)$value;
}

// Helper para formatar numéricos (float)
function formatNumeric($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) return 'NULL';
    return (float)$value;
}

// Helper para tratar booleanos
function tratarBoolean($valor)
{
    if ($valor === '' || $valor === null) {
        return 'NULL';
    }
    if (in_array(strtolower($valor), ['true', '1', 's', 'sim', 'yes', 't'])) {
        return 'TRUE';
    }
    return 'FALSE';
}

// Helper para escapar strings para SQL
function escapeStringSql($conn, $value)
{
    $raw = $value ?? '';
    return pg_escape_literal($conn, $raw);
}

// Helper para formatar text or null
function formatTextOrNull($conn, $str)
{
    $raw = $str ?? '';
    return (strlen($raw) === 0) ? 'NULL' : escapeStringSql($conn, $raw);
}

// Helper para formatar int or null
function formatIntOrNull($str)
{
    $raw = $str ?? '';
    return (strlen($raw) === 0) ? 'NULL' : (int)$raw;
}

// Helper para formatar numeric or null
function formatNumericOrNull($str)
{
    $raw = $str ?? '';
    return (strlen($raw) === 0) ? 'NULL' : (float)$raw;
}

$request = isset($_GET['request']) ? $_GET['request'] : null;
if (!$request) {
    response(["error" => "Parâmetro 'request' é obrigatório"], 400);
}

// Verificação de método HTTP
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? []; // Para POST/PUT

try {
    switch ($request) {
        // =========================================================
        // 🔹 ROTA 1: Listar cidades (GET)
        // =========================================================
        case 'listar_cidades':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : null;
            $filtro_regiao = isset($_GET['filtro_regiao']) ? intval($_GET['filtro_regiao']) : null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 200;

            $params = [];
            $idx = 1;
            $where = [];

            if ($filtro_nome) {
                $where[] = "nome_en ILIKE $" . $idx++;
                $params[] = "%{$filtro_nome}%";
            }
            if ($filtro_regiao && $filtro_regiao > 0) {
                $where[] = "regiao = $" . $idx++;
                $params[] = $filtro_regiao;
            }

            $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";
            $params[] = $limit;

            $sql = "
                SELECT 
                    *
                FROM tarifario.cidade_tpo
                {$where_sql}
                ORDER BY nome_en
                LIMIT $" . $idx . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception(pg_last_error($conn));

            $cidades = [];
            while ($row = pg_fetch_assoc($result)) {
                // Mapeamentos para frontend (ex: nomes multilíngues)
                $row['name'] = $row['nome_en'];
                $row['description'] = $row['descritivo_pt'];
                $row['short_description'] = $row['nome_pt']; // Exemplo de mapeamento

                // Montar imagens e extras
                montarImagensCidade($row, $BASE_URL_IMAGEM);
                montarUrlsExtras($row, $BASE_URL_IMAGEM);

                $cidades[] = $row;
            }

            response($cidades);
            break;

        // =========================================================
        // 🔹 ROTA 2: Buscar cidade específica por código (GET)
        // =========================================================
        case 'buscar_cidade':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $cidade_cod = isset($_GET['cidade_cod']) ? $_GET['cidade_cod'] : null;
            if (!$cidade_cod) response(["error" => "cidade_cod é obrigatório"], 400);

            $sql = "
               SELECT *
               FROM tarifario.cidade_tpo
               WHERE cidade_cod = $1
               LIMIT 1
            ";
            $result = pg_query_params($conn, $sql, [$cidade_cod]);
            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Cidade não encontrada"], 404);
            }

            $cidade = pg_fetch_assoc($result);

            // Mapeamentos para frontend
            $cidade['name'] = $cidade['nome_en'];
            $cidade['name_pt'] = $cidade['nome_pt'];
            $cidade['name_esp'] = $cidade['nome_esp'];
            $cidade['description'] = $cidade['descritivo_pt'];
            $cidade['description_en'] = $cidade['descritivo_en'];
            $cidade['description_esp'] = $cidade['descritivo_esp'];
            $cidade['vai_tour'] = $cidade['vai_tour'] === 't';
            $cidade['bco_img_riolife'] = $cidade['bco_img_riolife'] === 't';
            $cidade['regiao_label'] = $cidade['regiao'] ? $regiao_options[$cidade['regiao']] ?? 'Desconhecida' : 'N/A';

            // Montar imagens e extras
            montarImagensCidade($cidade, $BASE_URL_IMAGEM);
            montarUrlsExtras($cidade, $BASE_URL_IMAGEM);

            response($cidade);
            break;

        // =========================================================
        // 🔹 ROTA 3: Criar cidade (POST)
        // =========================================================
        case 'criar_cidade':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            if (empty($input)) response(["error" => "Dados da cidade são obrigatórios no body JSON"], 400);

            // Campos principais (baseados na tabela)
            $campos = [
                'nome_pt',
                'nome_en',
                'nome_esp',
                'descritivo_pt',
                'descritivo_en',
                'descritivo_esp',
                'foto1',
                'foto2',
                'foto3',
                'tpocidcod',
                'cidade_cod',
                'destino_pt',
                'destino_en',
                'destino_esp',
                'inf_rev_pt',
                'inf_rev_en',
                'inf_rev_esp',
                'inf_tras_pt',
                'inf_tras_en',
                'inf_tras_esp',
                'aereo_pt',
                'aereo_en',
                'aereo_esp',
                'voucher_pt',
                'voucher_en',
                'voucher_esp',
                'cond_gerais_pt',
                'cond_gerais_en',
                'cond_gerais_esp',
                'regiao',
                'cid',
                'title',
                'description',
                'keywords',
                'titulo_pg',
                'vai_tour',
                'bco_img_riolife',
                'average_temp',
                'rainy_season',
                'dry_season',
                'best_time',
                'about',
                'covid_19_pt',
                'covid_19_en'
                // Adicione mais campos conforme necessário (ex: meta tags, fotos BB)
            ];

            $params = [];
            $placeholders = [];
            $idx = 1;

            foreach ($campos as $campo) {
                $valor = $input[$campo] ?? null;

                // Casts
                if ($campo === 'vai_tour' || $campo === 'bco_img_riolife') {
                    $params[] = !empty($valor) ? 't' : 'f';
                } elseif ($campo === 'regiao' || $campo === 'cid') {
                    $params[] = !empty($valor) && is_numeric($valor) ? (int)$valor : null;
                } else {
                    $params[] = $valor;
                }
                $placeholders[] = '$' . $idx++;
            }

            $sql = "
                INSERT INTO tarifario.cidade_tpo (" . implode(',', $campos) . ")
                VALUES (" . implode(',', $placeholders) . ")
                RETURNING pk_cidade_tpo, cidade_cod
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Erro ao inserir cidade: " . pg_last_error($conn));
            }

            $row = pg_fetch_assoc($result);
            $cidade_id = $row['pk_cidade_tpo'];
            $cidade_cod = $row['cidade_cod'];

            response([
                'success' => true,
                'message' => 'Cidade inserida com sucesso!',
                'cidade_id' => $cidade_id,
                'cidade_cod' => $cidade_cod
            ], 201);
            break;

        // =========================================================
        // 🔹 ROTA 5: Atualizar cidade (PUT)
        // =========================================================
        case 'atualizar_cidade':
            if ($method !== 'PUT') response(["error" => "Método não permitido. Use PUT."], 405);

            $cidade_cod = isset($_GET['cidade_cod']) ? $_GET['cidade_cod'] : null;
            if (!$cidade_cod) response(["error" => "cidade_cod é obrigatório"], 400);

            if (empty($input)) response(["error" => "Dados da cidade são obrigatórios no body JSON"], 400);

            // Mapeamento de campos (simples, sem muitos mapeamentos extras)
            $set = [];
            $params = [];
            $idx = 1;

            foreach ($input as $chave => $valor) {
                if ($chave === 'cidade_cod') continue; // Não altera o código

                $campo = $chave; // Assume mapeamento 1:1

                if ($campo === 'vai_tour' || $campo === 'bco_img_riolife') {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = (!empty($valor) && $valor !== 'false') ? 't' : 'f';
                } elseif (in_array($campo, ['regiao', 'cid'])) {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = !empty($valor) && is_numeric($valor) ? (int)$valor : null;
                } else {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = $valor;
                }
            }

            if (empty($set)) {
                response(["success" => false, "message" => "Nenhuma alteração realizada"], 200);
            }

            $params[] = $cidade_cod;
            $sql = "
                UPDATE tarifario.cidade_tpo
                SET " . implode(', ', $set) . "
                WHERE cidade_cod = $" . $idx . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Database update failed: " . pg_last_error($conn));
            }

            $affected_rows = pg_affected_rows($result);
            if ($affected_rows > 0) {
                response([
                    'success' => true,
                    'message' => 'Cidade atualizada com sucesso! Linhas afetadas: ' . $affected_rows
                ]);
            } else {
                response([
                    'success' => false,
                    'message' => 'Nenhuma linha atualizada',
                    'affected_rows' => $affected_rows
                ], 200);
            }
            break;

        // =========================================================
        // 🔹 ROTA 6: Excluir cidade (DELETE)
        // =========================================================
        case 'excluir_cidade':
            if ($method !== 'DELETE') response(["error" => "Método não permitido. Use DELETE."], 405);

            $cidade_cod = isset($_GET['cidade_cod']) ? $_GET['cidade_cod'] : null;
            if (!$cidade_cod) response(["error" => "cidade_cod é obrigatório"], 400);

            $sql = "DELETE FROM tarifario.cidade_tpo WHERE cidade_cod = $1";
            $result = pg_query_params($conn, $sql, [$cidade_cod]);
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $affected_rows = pg_affected_rows($result);
            if ($affected_rows > 0) {
                response(["success" => true, "message" => "Cidade excluída com sucesso"]);
            } else {
                response(["error" => "Cidade não encontrada"], 404);
            }
            break;

        default:
            response(["error" => "Rota inválida"], 400);
    }
} catch (Exception $e) {
    error_log("Erro na API de cidades: " . $e->getMessage());
    response(["error" => "Erro no servidor: " . $e->getMessage()], 500);
}
