<?php

/**
 * API para gerenciamento da tabela sistema.sistema_atualizacoes
 *
 * Endpoints:
 * - GET  ?request=listar_atualizacoes&filtro_modulo=Banco&filtro_tipo=feature&publico=true&limit=50
 *         → Lista atualizações com filtros opcionais por módulo e tipo.
 *
 * - GET  ?request=buscar_atualizacao&id=10
 *         → Busca os detalhes de uma atualização específica pelo ID.
 *
 * - POST ?request=criar_atualizacao
 *         → Cria uma nova atualização.
 *           Body JSON:
 *           {
 *              "titulo": "Adicionado filtro por hotel",
 *              "descricao": "Agora é possível filtrar vídeos por hotel no Banco de Vídeo",
 *              "modulo": "Banco de Vídeo",
 *              "tipo": "feature",
 *              "publico": true,
 *              "created_by": "Roger Neves"
 *           }
 *
 * - PUT  ?request=atualizar_atualizacao&id=10
 *         → Atualiza campos específicos de uma atualização existente.
 *
 * - DELETE ?request=excluir_atualizacao&id=10
 *         → Remove uma atualização.
 *
 * Métodos suportados:
 * - GET: listar_atualizacoes, buscar_atualizacao
 * - POST: criar_atualizacao
 * - PUT: atualizar_atualizacao
 * - DELETE: excluir_atualizacao
 *
 * Retornos:
 * - 200: Sucesso
 * - 201: Criado
 * - 400: Erro de parâmetro
 * - 404: Não encontrado
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

// Função padrão de resposta JSON
function response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper para tratar booleanos
function tratarBoolean($valor)
{
    if ($valor === '' || $valor === null) {
        return 'NULL';
    }
    if (is_bool($valor)) {
        return $valor ? 'TRUE' : 'FALSE';
    }
    $raw = strtolower((string)$valor);
    if (in_array($raw, ['true', '1', 's', 'sim', 'yes', 't'])) {
        return 'TRUE';
    }
    return 'FALSE';
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
        // 🔹 ROTA 1: Listar atualizações (GET)
        // =========================================================
        case 'listar_atualizacoes':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $filtro_modulo = isset($_GET['filtro_modulo']) ? trim($_GET['filtro_modulo']) : null;
            $filtro_tipo = isset($_GET['filtro_tipo']) ? trim($_GET['filtro_tipo']) : null;
            $filtro_publico = isset($_GET['publico']) ? $_GET['publico'] : null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 200;

            $params = [];
            $idx = 1;
            $where = [];

            if ($filtro_modulo) {
                $where[] = "modulo ILIKE $" . $idx++;
                $params[] = "%{$filtro_modulo}%";
            }
            if ($filtro_tipo) {
                $where[] = "tipo ILIKE $" . $idx++;
                $params[] = "%{$filtro_tipo}%";
            }
            if ($filtro_publico !== null && $filtro_publico !== '') {
                $where[] = "publico = $" . $idx++;
                $params[] = (tratarBoolean($filtro_publico) === 'TRUE');
            }

            $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";
            $params[] = $limit;

            $sql = "
                SELECT
                    id,
                    titulo,
                    descricao,
                    modulo,
                    tipo,
                    publico,
                    created_by,
                    created_at
                FROM sistema.sistema_atualizacoes
                {$where_sql}
                ORDER BY created_at DESC
                LIMIT $" . $idx . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception(pg_last_error($conn));

            $atualizacoes = [];
            while ($row = pg_fetch_assoc($result)) {
                $row['publico'] = $row['publico'] === 't';
                $atualizacoes[] = $row;
            }

            response($atualizacoes);
            break;

        // =========================================================
        // 🔹 ROTA 2: Buscar atualização por ID (GET)
        // =========================================================
        case 'buscar_atualizacao':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "id é obrigatório"], 400);

            $sql = "
               SELECT
                   id,
                   titulo,
                   descricao,
                   modulo,
                   tipo,
                   publico,
                   created_by,
                   created_at
               FROM sistema.sistema_atualizacoes
               WHERE id = $1
               LIMIT 1
            ";
            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Atualização não encontrada"], 404);
            }

            $atualizacao = pg_fetch_assoc($result);
            $atualizacao['publico'] = $atualizacao['publico'] === 't';

            response($atualizacao);
            break;

        // =========================================================
        // 🔹 ROTA 3: Criar atualização (POST)
        // =========================================================
        case 'criar_atualizacao':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            if (empty($input)) response(["error" => "Dados da atualização são obrigatórios no body JSON"], 400);

            $titulo = $input['titulo'] ?? null;
            $descricao = $input['descricao'] ?? null;

            if (!$titulo || !$descricao) {
                response(["error" => "Campos obrigatórios: titulo, descricao"], 400);
            }

            $campos = [
                'titulo',
                'descricao',
                'modulo',
                'tipo',
                'publico',
                'created_by'
            ];

            $params = [];
            $placeholders = [];
            $idx = 1;

            foreach ($campos as $campo) {
                $valor = $input[$campo] ?? null;

                if ($campo === 'publico') {
                    $params[] = (tratarBoolean($valor) === 'TRUE');
                } else {
                    $params[] = $valor;
                }
                $placeholders[] = '$' . $idx++;
            }

            $sql = "
                INSERT INTO sistema.sistema_atualizacoes (" . implode(',', $campos) . ")
                VALUES (" . implode(',', $placeholders) . ")
                RETURNING id
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Erro ao inserir atualização: " . pg_last_error($conn));
            }

            $row = pg_fetch_assoc($result);
            $id = $row['id'];

            response([
                'success' => true,
                'message' => 'Atualização inserida com sucesso!',
                'id' => $id
            ], 201);
            break;

        // =========================================================
        // 🔹 ROTA 4: Atualizar atualização (PUT)
        // =========================================================
        case 'atualizar_atualizacao':
            if ($method !== 'PUT') response(["error" => "Método não permitido. Use PUT."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "id é obrigatório"], 400);

            if (empty($input)) response(["error" => "Dados da atualização são obrigatórios no body JSON"], 400);

            $set = [];
            $params = [];
            $idx = 1;

            foreach ($input as $chave => $valor) {
                if ($chave === 'id') continue;

                $campo = $chave;

                if ($campo === 'publico') {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = (tratarBoolean($valor) === 'TRUE');
                } else {
                    $set[] = "$campo = $" . $idx++;
                    $params[] = $valor;
                }
            }

            if (empty($set)) {
                response(["success" => false, "message" => "Nenhuma alteração realizada"], 200);
            }

            $params[] = $id;
            $sql = "
                UPDATE sistema.sistema_atualizacoes
                SET " . implode(', ', $set) . "
                WHERE id = $" . $idx . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Database update failed: " . pg_last_error($conn));
            }

            $affected_rows = pg_affected_rows($result);
            if ($affected_rows > 0) {
                response([
                    'success' => true,
                    'message' => 'Atualização atualizada com sucesso! Linhas afetadas: ' . $affected_rows
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
        // 🔹 ROTA 5: Excluir atualização (DELETE)
        // =========================================================
        case 'excluir_atualizacao':
            if ($method !== 'DELETE') response(["error" => "Método não permitido. Use DELETE."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "id é obrigatório"], 400);

            $sql = "DELETE FROM sistema.sistema_atualizacoes WHERE id = $1";
            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $affected_rows = pg_affected_rows($result);
            if ($affected_rows > 0) {
                response(["success" => true, "message" => "Atualização excluída com sucesso"]);
            } else {
                response(["error" => "Atualização não encontrada"], 404);
            }
            break;

        default:
            response(["error" => "Rota inválida"], 400);
    }
} catch (Exception $e) {
    error_log("Erro na API de sistema_atualizacoes: " . $e->getMessage());
    response(["error" => "Erro no servidor: " . $e->getMessage()], 500);
}
