<?php

/**
 * API para gerenciamento da tabela tarifario.cadastro_clientes
 *
 * Endpoints principais:
 * - GET  ?request=listar_clientes&filtro_nome=ROGER&filtro_mneu=ABCD&filtro_login=usuario&limit=100
 * - GET  ?request=buscar_cliente&mneu_cli=ABCD
 * - POST ?request=criar_cliente      → body JSON com os campos
 * - PUT  ?request=atualizar_cliente&mneu_cli=ABCD   → body JSON (campos a atualizar)
 * - DELETE ?request=excluir_cliente&mneu_cli=ABCD
 *
 * Retornos padrão:
 * 200 OK, 201 Created, 400 Bad Request, 404 Not Found, 405 Method Not Allowed, 500 Error
 */

// ========================================
// CONFIGURAÇÕES INICIAIS
// ========================================
date_default_timezone_set('America/Sao_Paulo');

$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:8080',
    'http://localhost:3000',
    // 'https://seusistema.com.br',
];

if (in_array($http_origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $http_origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: *"); // ← cuidado: apenas dev
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../util/connection.php'; // sua conexão PDO ou pg_connect

// Função de resposta padrão
function response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Helpers de formatação para PostgreSQL
function toPgBool($value) {
    if ($value === null || $value === '') return null;
    return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 't' : 'f';
}

function toPgString($conn, $value) {
    if ($value === null || $value === '') return 'NULL';
    return pg_escape_literal($conn, $value);
}

function toPgInt($value) {
    if ($value === null || $value === '' || !is_numeric($value)) return 'NULL';
    return (int)$value;
}

function toPgNumeric($value) {
    if ($value === null || $value === '' || !is_numeric($value)) return 'NULL';
    return (float)$value;
}

$request = $_GET['request'] ?? null;
if (!$request) {
    response(["error" => "Parâmetro 'request' é obrigatório"], 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($request) {

        // ────────────────────────────────────────────────
        // LISTAR CLIENTES
        // ────────────────────────────────────────────────
        case 'listar_clientes':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $filtro_nome   = trim($_GET['filtro_nome']   ?? '');
            $filtro_mneu   = trim($_GET['filtro_mneu']   ?? '');
            $filtro_login  = trim($_GET['filtro_login']  ?? '');
            $ativo         = $_GET['ativo'] ?? null;
            $limit         = max(10, min(500, (int)($_GET['limit'] ?? 200)));

            $where = [];
            $params = [];
            $idx = 1;

            if ($filtro_nome !== '') {
                $where[] = "nome_cli ILIKE $" . $idx++;
                $params[] = "%$filtro_nome%";
            }
            if ($filtro_mneu !== '') {
                $where[] = "mneu_cli ILIKE $" . $idx++;
                $params[] = "%$filtro_mneu%";
            }
            if ($filtro_login !== '') {
                $where[] = "login ILIKE $" . $idx++;
                $params[] = "%$filtro_login%";
            }
            if ($ativo !== null && in_array($ativo, ['true','false','1','0'])) {
                $where[] = "ativo = $" . $idx++;
                $params[] = $ativo === 'true' || $ativo === '1' ? 't' : 'f';
            }

            $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

            $sql = "
                SELECT 
                    pk_cad_cli,
                    mneu_cli,
                    nome_cli,
                    ativo,
                    email,
                    login,
                    ativo_cote,
                    usa_allotment,
                    consome_allotment,
                    avulso,
                    ativo_virtuoso,
                    conteudo_riolife,
                    bco_img,
                    mkp_htl_v,
                    mkp_srv_v,
                    mkp_food_v
                FROM tarifario.cadastro_clientes
                $where_sql
                ORDER BY nome_cli
                LIMIT $limit
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception(pg_last_error($conn));

            $clientes = pg_fetch_all($result) ?: [];
            response($clientes);
            break;


        // ────────────────────────────────────────────────
        // BUSCAR UM CLIENTE
        // ────────────────────────────────────────────────
        case 'buscar_cliente':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $mneu_cli = trim($_GET['mneu_cli'] ?? '');
            if (!$mneu_cli) response(["error" => "Parâmetro mneu_cli é obrigatório"], 400);

            $sql = "SELECT * FROM tarifario.cadastro_clientes WHERE mneu_cli = $1 LIMIT 1";
            $result = pg_query_params($conn, $sql, [$mneu_cli]);

            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Cliente não encontrado"], 404);
            }

            $cliente = pg_fetch_assoc($result);

            // Converte 't'/'f' → true/false para o frontend
            foreach ($cliente as $k => $v) {
                if (is_string($v) && in_array($v, ['t','f'])) {
                    $cliente[$k] = $v === 't';
                }
            }

            response($cliente);
            break;


        // ────────────────────────────────────────────────
        // CRIAR CLIENTE
        // ────────────────────────────────────────────────
        case 'criar_cliente':
            if ($method !== 'POST') response(["error" => "Use POST"], 405);
            if (empty($input)) response(["error" => "Body JSON vazio"], 400);

            $mneu_cli = trim($input['mneu_cli'] ?? '');
            if (strlen($mneu_cli) < 1 || strlen($mneu_cli) > 4) {
                response(["error" => "mneu_cli deve ter 1 a 4 caracteres"], 400);
            }

            // Verifica se já existe
            $chk = pg_query_params($conn, "SELECT 1 FROM tarifario.cadastro_clientes WHERE mneu_cli = $1", [$mneu_cli]);
            if (pg_num_rows($chk) > 0) {
                response(["error" => "Já existe cliente com este mneu_cli"], 409);
            }

            $campos = [
                'cod_agrup','mneu_cli','nome_cli','root_srv','mkp_htl_v','mkp_srv_v','mkp_food_v',
                'ativo','descricao_tar','lang','emp','extranet','root_htl','logo','fk_usuario',
                'fk_controle_acesso_ws','login','pass','ativo_cote','fk_depto',
                'mkp_htl','mkp_srv','mkp_food','desativar_tarifario','ativo2tar','root_htl2',
                'email','usa_allotment','consome_allotment','mkp_htl2','mkp_srv2','mkp_food2',
                'mkp_eco','mkp_eco2','mkp_ny','mkp_ny2','mkp_carn','mkp_carn2',
                'mkp_winn','mkp_winn2','avulso','logo_placa','obs_guia','ativo_virtuoso',
                'limite_cred2','limite_cred_file','freq_pgto2','de_freq_pgto2','ate_freq_pgto2',
                'conteudo_riolife','wooba','bco_img'
            ];

            $values = [];
            $placeholders = [];
            $params = [];
            $idx = 1;

            foreach ($campos as $campo) {
                $val = $input[$campo] ?? null;

                if (in_array($campo, ['ativo','extranet','desativar_tarifario','ativo2tar',
                                      'usa_allotment','consome_allotment','avulso',
                                      'ativo_virtuoso','conteudo_riolife','bco_img'])) {
                    $values[] = toPgBool($val);
                }
                elseif (in_array($campo, ['cod_agrup','root_srv','lang','emp','root_htl','fk_controle_acesso_ws',
                                          'fk_depto','root_htl2'])) {
                    $values[] = toPgInt($val);
                }
                elseif (str_starts_with($campo, 'mkp_')) {
                    $values[] = toPgNumeric($val);
                }
                elseif (in_array($campo, ['de_freq_pgto2','ate_freq_pgto2'])) {
                    $values[] = $val ? pg_escape_literal($conn, $val) : 'NULL';
                }
                else {
                    $values[] = toPgString($conn, $val);
                }

                $placeholders[] = '$' . $idx++;
                $params[] = end($values); // para pg_query_params
            }

            $sql = "
                INSERT INTO tarifario.cadastro_clientes 
                (" . implode(", ", $campos) . ")
                VALUES (" . implode(", ", $placeholders) . ")
                RETURNING pk_cad_cli, mneu_cli, nome_cli
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception("Erro ao criar cliente: " . pg_last_error($conn));

            $row = pg_fetch_assoc($result);

            response([
                "success" => true,
                "message" => "Cliente criado com sucesso",
                "data" => $row
            ], 201);
            break;


        // ────────────────────────────────────────────────
        // ATUALIZAR CLIENTE (campos parciais)
        // ────────────────────────────────────────────────
        case 'atualizar_cliente':
            if ($method !== 'PUT') response(["error" => "Use PUT"], 405);

            $mneu_cli = trim($_GET['mneu_cli'] ?? '');
            if (!$mneu_cli) response(["error" => "mneu_cli é obrigatório na query"], 400);

            if (empty($input)) response(["error" => "Nenhum campo para atualizar"], 400);

            $set = [];
            $params = [];
            $idx = 1;

            foreach ($input as $campo => $valor) {
                if ($campo === 'mneu_cli' || $campo === 'pk_cad_cli') continue; // protegidos

                if (in_array($campo, ['ativo','extranet','desativar_tarifario','ativo2tar',
                                      'usa_allotment','consome_allotment','avulso',
                                      'ativo_virtuoso','conteudo_riolife','bco_img'])) {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = toPgBool($valor);
                }
                elseif (in_array($campo, ['cod_agrup','root_srv','lang','emp','root_htl',
                                          'fk_controle_acesso_ws','fk_depto','root_htl2'])) {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = toPgInt($valor);
                }
                elseif (str_starts_with($campo, 'mkp_')) {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = toPgNumeric($valor);
                }
                elseif (in_array($campo, ['de_freq_pgto2','ate_freq_pgto2'])) {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = $valor ? $valor : null;
                }
                else {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = $valor;
                }
            }

            if (empty($set)) response(["message" => "Nenhuma alteração enviada"], 200);

            $params[] = $mneu_cli;
            $sql = "
                UPDATE tarifario.cadastro_clientes
                SET " . implode(", ", $set) . "
                WHERE mneu_cli = $" . $idx . "
                RETURNING pk_cad_cli, mneu_cli, nome_cli
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result || pg_affected_rows($result) === 0) {
                response(["error" => "Cliente não encontrado ou nenhuma alteração"], 404);
            }

            $row = pg_fetch_assoc($result);

            response([
                "success" => true,
                "message" => "Cliente atualizado",
                "data" => $row
            ]);
            break;


        // ────────────────────────────────────────────────
        // EXCLUIR CLIENTE
        // ────────────────────────────────────────────────
        case 'excluir_cliente':
            if ($method !== 'DELETE') response(["error" => "Use DELETE"], 405);

            $mneu_cli = trim($_GET['mneu_cli'] ?? '');
            if (!$mneu_cli) response(["error" => "mneu_cli é obrigatório"], 400);

            $sql = "DELETE FROM tarifario.cadastro_clientes WHERE mneu_cli = $1";
            $result = pg_query_params($conn, $sql, [$mneu_cli]);

            if (!$result) throw new Exception(pg_last_error($conn));

            $affected = pg_affected_rows($result);

            if ($affected === 0) {
                response(["error" => "Cliente não encontrado"], 404);
            }

            response(["success" => true, "message" => "Cliente excluído"]);
            break;


        default:
            response(["error" => "Requisição inválida"], 400);
    }
}
catch (Exception $e) {
    error_log("Erro API cadastro_clientes: " . $e->getMessage());
    response(["error" => "Erro interno do servidor", "detail" => $e->getMessage()], 500);
}
