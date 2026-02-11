<?php

/**
 * API de Gerenciamento de Usuários API
 *
 * Descrição:
 * Endpoint para gerenciamento de usuários/admins dedicados à API (tabela sbd95.api_admins).
 * Todos os endpoints requerem autenticação via token Bearer (gerado em auth.php).
 * Usa middleware para validação de token e roles (master para criar/atualizar/excluir).
 *
 * Endpoints:
 * - POST ?request=criar_usuario_api (REQUER MASTER)
 *         → Cria novo usuário/admin API.
 *           Body JSON: { "username": "newadmin", "email": "new@ex.com", "password": "senha", "role": "viewer", "permissions": [...] }
 *
 * - GET  ?request=listar_usuarios_api (REQUER AUTH)
 *         → Lista usuários/admins da API.
 *
 * - GET  ?request=buscar_usuario_api&id=1 (REQUER AUTH)
 *         → Busca usuário específico.
 *
 * - PUT  ?request=atualizar_usuario_api&id=1 (REQUER MASTER)
 *         → Atualiza usuário API (role, permissions, etc.).
 *           Body JSON: { "role": "full", "permissions": [...], "is_active": true }
 *
 * - DELETE ?request=excluir_usuario_api&id=1 (REQUER MASTER)
 *         → Exclui usuário API.
 *
 * Métodos suportados:
 * - POST: criar_usuario_api
 * - GET: listar_usuarios_api, buscar_usuario_api
 * - PUT: atualizar_usuario_api
 * - DELETE: excluir_usuario_api
 *
 * Tabelas relacionadas:
 * - sbd95.api_admins (usuários API)
 * - sbd95.api_user_tokens (para validação de token)
 *
 * Retornos:
 * - 200: Sucesso
 * - 201: Criado
 * - 400: Erro de parâmetro
 * - 401: Não autenticado
 * - 403: Acesso negado (não master)
 * - 404: Não encontrado
 * - 405: Método não permitido
 * - 500: Erro interno
 */

date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key");
header("Content-Type: application/json; charset=UTF-8");

require_once '../util/connection.php';
require_once 'middleware.php';  // Inclui funções de auth e validação (handleAutenticacao, etc.)

// Função padrão de resposta JSON
function response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$request = isset($_GET['request']) ? $_GET['request'] : null;
if (!$request) {
    response(["error" => "Parâmetro 'request' é obrigatório"], 400);
}

// Verificação de método HTTP
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Validação de autenticação global para todos os endpoints
$user_data = null;

// if (!handleAutenticacao($conn, $request, $user_data)) {
//     response(["error" => "Autenticação requerida"], 401);
// }

// // Verifica se é auth de api_admin (para endpoints de gerenciamento)
// if (!isset($user_data['auth_type']) || $user_data['auth_type'] !== 'api_admin') {
//     response(["error" => "Acesso negado. Autentique via login_admin."], 403);
// }

try {
    switch ($request) {

        // =========================================================
        // 🔹 ROTA: Criar Usuário/Admin API (POST) - REQUER MASTER
        // =========================================================
        case 'criar_usuario_api':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            // Só master pode criar
            // if ($user_data && isset($user_data['role']) && $user_data['role'] !== 'master') {
            //     response(["error" => "Acesso negado. Apenas master pode criar usuários API."], 403);
            //     break;
            // }

            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $cod_sis = trim($input['cod_sis'] ?? '');
            $password = $input['password'] ?? '';
            $role = $input['role'] ?? 'viewer';
            $permissions = json_encode($input['permissions'] ?? []);  // Ex: [{"endpoint": "listar_hoteis", "methods": ["GET"]}]

            if (empty($username) || empty($email) || empty($password) || !in_array($role, ['master', 'full', 'limited', 'viewer'])) {
                response(["error" => "username, email, password e role válida são obrigatórios"], 400);
                break;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "
                INSERT INTO sbd95.api_admins (username, email, cod_sis, password_hash, role, permissions)
                VALUES ($1, $2, $3, $4, $5, $6::jsonb)
                RETURNING id
            ";
            $params = [$username, $email, ($cod_sis !== '' ? $cod_sis : null), $hash, $role, $permissions];

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                response(["error" => "Erro ao criar usuário: " . pg_last_error($conn)], 500);
                break;
            }

            $row = pg_fetch_assoc($result);
            response([
                "success" => true,
                "message" => "Usuário API criado com sucesso!",
                "user_id" => $row['id'],
                "username" => $username,
                "role" => $role
            ], 201);
            break;

        // =========================================================
        // 🔹 ROTA: Buscar Requisições do Usuário API (GET) - REQUER AUTH
        // =========================================================
        case 'buscar_requisicoes_usuario':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            // Extrai o token do header Authorization
            $headers = getallheaders();
            $auth_header = trim($headers['Authorization'] ?? '');
            if (strpos($auth_header, 'Bearer ') !== 0) {
                response(["error" => "Token Bearer é obrigatório"], 401);
            }
            $token = substr($auth_header, 7);

            $cod_sis = null;
            $token_data = null;
            if (!validarToken($conn, $cod_sis, $token, $token_data)) {
                response(["error" => "Token inválido ou expirado"], 401);
            }

            // Opcional: Verificar permissões (ex: master pode ver qualquer um, outros só os próprios)
            $auth_user_id = $token_data['user_id'];
            if ($token_data['role'] !== 'master' && $auth_user_id != $user_id) {
                response(["error" => "Acesso negado. Você só pode buscar suas próprias requisições."], 403);
            }

            // Validar se o ID foi enviado (agora usa do GET, com checagem acima)
            if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
                response(["error" => "user_id não fornecido."], 400);
            }

            $user_id = (int)$_GET['user_id'];  // Usa do GET, validado por permissão
            $limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 1000)) : 100;  // Limite seguro
            $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

            // Buscar requisições do usuário através da tabela api_logs
            // Agora usa user_id diretamente (sem JOIN, a menos que precise de username de api_admins)
            $sql = "
                    SELECT 
                        al.id,
                        al.endpoint,
                        al.method,
                        al.response_status as status_code,
                        al.request_time as created_at,
                        al.duration_ms as response_time_ms,
                        al.ip_address,
                        al.user_agent
                        -- Removido aa.username - se precisar, adicione INNER JOIN sbd95.api_admins aa ON al.user_id = aa.id
                    FROM sbd95.api_logs al
                    WHERE al.user_id = $1
                    ORDER BY al.request_time DESC
                    LIMIT $2 OFFSET $3
                ";

            $result = pg_query_params($conn, $sql, [$user_id, $limit, $offset]);

            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $requisicoes = pg_fetch_all($result) ?? [];

            // Buscar total de requisições
            $sql_count = "
                    SELECT COUNT(*) as total 
                    FROM sbd95.api_logs al
                    WHERE al.user_id = $1
                ";
            $result_count = pg_query_params($conn, $sql_count, [$user_id]);
            if (!$result_count) {
                throw new Exception(pg_last_error($conn));
            }
            $total_row = pg_fetch_assoc($result_count);
            $total = (int)($total_row['total'] ?? 0);

            // Estatísticas adicionais
            $sql_stats = "
                    SELECT 
                        COUNT(*) as total_requests,
                        COUNT(CASE WHEN al.response_status >= 200 AND al.response_status < 300 THEN 1 END) as success_count,
                        COUNT(CASE WHEN al.response_status >= 400 THEN 1 END) as error_count,
                        ROUND(AVG(al.duration_ms)::numeric, 2) as avg_response_time,
                        MAX(al.request_time) as last_request,
                        MIN(al.request_time) as first_request,
                        COUNT(DISTINCT al.endpoint) as unique_endpoints,
                        COUNT(DISTINCT al.ip_address) as unique_ips
                    FROM sbd95.api_logs al
                    WHERE al.user_id = $1
                ";
            $result_stats = pg_query_params($conn, $sql_stats, [$user_id]);
            if (!$result_stats) {
                throw new Exception(pg_last_error($conn));
            }
            $stats = pg_fetch_assoc($result_stats) ?? [];

            // Buscar endpoints mais usados
            $sql_top_endpoints = "
                    SELECT 
                        al.endpoint,
                        al.method,
                        COUNT(*) as count
                    FROM sbd95.api_logs al
                    WHERE al.user_id = $1
                    GROUP BY al.endpoint, al.method
                    ORDER BY count DESC
                    LIMIT 5
                ";
            $result_top = pg_query_params($conn, $sql_top_endpoints, [$user_id]);
            if (!$result_top) {
                throw new Exception(pg_last_error($conn));
            }
            $top_endpoints = pg_fetch_all($result_top) ?? [];

            // Log da própria busca (usa auth_user_id, e agora popula user_id também)
            $start_time = microtime(true);
            $log_sql = "INSERT INTO sbd95.api_logs (api_key_id, endpoint, method, response_status, request_time, duration_ms, ip_address, user_agent, user_id) VALUES ($1, $2, $3, $4, NOW(), $5, $6, $7, $8)";
            $duration = round((microtime(true) - $start_time) * 1000);  // Duração aproximada
            $log_result = pg_query_params($conn, $log_sql, [$auth_user_id, $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], 200, $duration, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $auth_user_id]);
            if (!$log_result) {
                error_log("Erro ao logar busca de requisições: " . pg_last_error($conn));
            }

            response([
                "success" => true,
                "requisicoes" => $requisicoes,
                "total" => $total,
                "stats" => [
                    "total_requests" => (int)($stats['total_requests'] ?? 0),
                    "success_count" => (int)($stats['success_count'] ?? 0),
                    "error_count" => (int)($stats['error_count'] ?? 0),
                    "avg_response_time" => (float)($stats['avg_response_time'] ?? 0),
                    "last_request" => $stats['last_request'] ?? null,
                    "first_request" => $stats['first_request'] ?? null,
                    "unique_endpoints" => (int)($stats['unique_endpoints'] ?? 0),
                    "unique_ips" => (int)($stats['unique_ips'] ?? 0)
                ],
                "top_endpoints" => $top_endpoints,
                "pagination" => [
                    "limit" => $limit,
                    "offset" => $offset,
                    "has_more" => ($offset + $limit) < $total
                ]
            ]);
            break;
        // =========================================================
        // 🔹 ROTA: Listar Usuários/Admins API (GET) - REQUER AUTH
        // =========================================================
        case 'listar_usuarios_api':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            // Extrai o token do header Authorization (ex: Bearer <token>)
            $headers = getallheaders();
            $auth_header = trim($headers['Authorization'] ?? '');
            if (strpos($auth_header, 'Bearer ') !== 0) {
                response(["error" => "Token Bearer é obrigatório"], 401);
            }
            $token = substr($auth_header, 7);

            // Não usa cod_sis - valida SOMENTE pelo token (passa null para a função)
            $cod_sis = null;  // Ignora cod_sis completamente

            $token_data = null;
            if (!validarToken($conn, $cod_sis, $token, $token_data)) {
                response(["error" => "Token inválido ou expirado"], 401);
            }

            $user_id_from_token = $token_data['user_id'] ?? null;  // From validarToken, com fallback NULL

            // Se chegou aqui, token é válido - prossegue com a listagem
            // (Opcional: Se quiser manter filtro por auth_type 'api_admin', adicione aqui:
            // if (!isset($token_data['auth_type']) || $token_data['auth_type'] !== 'api_admin') {
            //     response(["error" => "Acesso negado. Use token de admin."], 403);
            // }
            // Mas como pediu só token, removi essa checagem para permitir qualquer token válido)

            // Inicia medição de duração AQUI (antes da query principal)
            $start_time = microtime(true);

            $sql = "
        SELECT id, username, email, cod_sis, role, is_active, created_at
        FROM sbd95.api_admins
        ORDER BY created_at DESC
    ";
            $result = pg_query($conn, $sql);
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $usuarios = pg_fetch_all($result) ?? [];

            // Calcula duração real da query/response
            $duration = round((microtime(true) - $start_time) * 1000);  // Duração em ms

            // Log da requisição (após query, com checagem lógica de integridade)
            if ($user_id_from_token > 0) {  // Só loga se user_id válido (>0)
                // Checagem lógica: Confirma se o ID existe em api_admins (evita logs "órfãos")
                $check_sql = "SELECT id FROM sbd95.api_admins WHERE id = $1 LIMIT 1";
                $check_result = pg_query_params($conn, $check_sql, [$user_id_from_token]);
                if (pg_num_rows($check_result) > 0) {
                    $log_sql = "INSERT INTO sbd95.api_logs (api_key_id, endpoint, method, response_status, request_time, duration_ms, ip_address, user_agent, user_id) VALUES ($1, $2, $3, $4, NOW(), $5, $6, $7, $8)";
                    $log_result = pg_query_params($conn, $log_sql, [
                        $user_id_from_token,  // $1: api_key_id (lógica para api_admins.id)
                        $_SERVER['REQUEST_URI'],  // $2: endpoint
                        $_SERVER['REQUEST_METHOD'],  // $3: method
                        200,  // $4: response_status
                        $duration,  // $5: duration_ms
                        $_SERVER['REMOTE_ADDR'],  // $6: ip_address
                        $_SERVER['HTTP_USER_AGENT'],  // $7: user_agent
                        $user_id_from_token  // $8: user_id
                    ]);
                    if (!$log_result) {
                        error_log("Erro ao logar requisição: " . pg_last_error($conn) . " | user_id: " . $user_id_from_token);
                    } else {
                        error_log("Log salvo: user_id=" . $user_id_from_token . ", duration=" . $duration . "ms");  // Debug temporário
                    }
                } else {
                    error_log("Pulado log: user_id_from_token ($user_id_from_token) não existe em api_admins (legado sem FK)");
                }
            } else {
                error_log("Pulado log: user_id_from_token inválido (" . ($user_id_from_token ?? 'NULL') . ")");  // Debug
            }

            response([
                "success" => true,
                "usuarios" => $usuarios,
                "total" => count($usuarios)
            ]);
            break;

        case 'listar_requisicoes':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            // Extrai o token do header Authorization
            $headers = getallheaders();
            $auth_header = trim($headers['Authorization'] ?? '');
            if (strpos($auth_header, 'Bearer ') !== 0) {
                response(["error" => "Token Bearer é obrigatório"], 401);
            }
            $token = substr($auth_header, 7);

            $cod_sis = null;
            $token_data = null;
            if (!validarToken($conn, $cod_sis, $token, $token_data)) {
                response(["error" => "Token inválido ou expirado"], 401);
            }

            // Verificar permissões: Apenas 'master' pode listar todas as requisições
            if ($token_data['role'] !== 'master') {
                response(["error" => "Acesso negado. Apenas usuários 'master' podem listar todas as requisições."], 403);
            }

            $auth_user_id = $token_data['user_id'];
            $limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 1000)) : 100;  // Limite seguro
            $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

            // Filtros opcionais
            $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : null;
            $data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : null;
            $metodo = isset($_GET['metodo']) ? strtoupper(trim($_GET['metodo'])) : null;
            $status = isset($_GET['status']) ? (int)$_GET['status'] : null;

            // Base da query para todas as requisições
            $sql_base = "
        SELECT 
            al.id,
            al.endpoint,
            al.method,
            al.response_status as status_code,
            al.request_time as created_at,
            al.duration_ms as response_time_ms,
            al.ip_address,
            al.user_agent,
            al.user_id,
            aa.username
        FROM sbd95.api_logs al
        LEFT JOIN sbd95.api_admins aa ON al.user_id = aa.id  -- LEFT para logs sem user (se houver)
    ";

            // Adicionar filtros opcionais
            $params = [];
            $conditions = [];

            if ($data_inicio) {
                $conditions[] = "al.request_time >= $" . (count($params) + 1);
                $params[] = $data_inicio . ' 00:00:00';
            }
            if ($data_fim) {
                $conditions[] = "al.request_time <= $" . (count($params) + 1);
                $params[] = $data_fim . ' 23:59:59';
            }
            if ($metodo && in_array($metodo, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])) {
                $conditions[] = "al.method = $" . (count($params) + 1);
                $params[] = $metodo;
            }
            if ($status) {
                $conditions[] = "al.response_status = $" . (count($params) + 1);
                $params[] = $status;
            }

            $where_clause = '';
            if (!empty($conditions)) {
                $where_clause = ' WHERE ' . implode(' AND ', $conditions);
            }

            // Query principal com ordenação e paginação
            $sql = $sql_base . $where_clause . " ORDER BY al.request_time DESC LIMIT $" . (count($params) + 1) . " OFFSET $" . (count($params) + 2);
            $params[] = $limit;
            $params[] = $offset;

            $result = pg_query_params($conn, $sql, $params);

            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $requisicoes = pg_fetch_all($result) ?? [];

            // Buscar total de requisições (mesma base, sem LIMIT/OFFSET)
            $sql_count = "SELECT COUNT(*) as total FROM (" . $sql_base . $where_clause . ") AS subquery";
            $result_count = pg_query_params($conn, $sql_count, array_slice($params, 0, -2));  // Remove LIMIT/OFFSET
            if (!$result_count) {
                throw new Exception(pg_last_error($conn));
            }
            $total_row = pg_fetch_assoc($result_count);
            $total = (int)($total_row['total'] ?? 0);

            // Estatísticas adicionais (agregadas com filtros aplicados)
            $sql_stats = "
        SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN al.response_status >= 200 AND al.response_status < 300 THEN 1 END) as success_count,
            COUNT(CASE WHEN al.response_status >= 400 THEN 1 END) as error_count,
            ROUND(AVG(al.duration_ms)::numeric, 2) as avg_response_time,
            MAX(al.request_time) as last_request,
            MIN(al.request_time) as first_request,
            COUNT(DISTINCT al.endpoint) as unique_endpoints,
            COUNT(DISTINCT al.ip_address) as unique_ips
        FROM sbd95.api_logs al
    " . $where_clause;
            $result_stats = pg_query_params($conn, $sql_stats, array_slice($params, 0, -2));
            if (!$result_stats) {
                throw new Exception(pg_last_error($conn));
            }
            $stats = pg_fetch_assoc($result_stats) ?? [];

            // Buscar endpoints mais usados (com filtros)
            $sql_top_endpoints = "
        SELECT 
            al.endpoint,
            al.method,
            COUNT(*) as count
        FROM sbd95.api_logs al
    " . $where_clause . "
        GROUP BY al.endpoint, al.method
        ORDER BY count DESC
        LIMIT 5
    ";
            $result_top = pg_query_params($conn, $sql_top_endpoints, array_slice($params, 0, -2));
            if (!$result_top) {
                throw new Exception(pg_last_error($conn));
            }
            $top_endpoints = pg_fetch_all($result_top) ?? [];

            // Log da própria busca (usa auth_user_id)
            $log_start_time = microtime(true);
            $log_sql = "INSERT INTO sbd95.api_logs (api_key_id, endpoint, method, response_status, request_time, duration_ms, ip_address, user_agent, user_id) VALUES ($1, $2, $3, $4, NOW(), $5, $6, $7, $8)";
            $log_duration = round((microtime(true) - $log_start_time) * 1000);
            $log_result = pg_query_params($conn, $log_sql, [$auth_user_id, $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], 200, $log_duration, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $auth_user_id]);
            if (!$log_result) {
                error_log("Erro ao logar listagem de requisições: " . pg_last_error($conn));
            }

            response([
                "success" => true,
                "requisicoes" => $requisicoes,
                "total" => $total,
                "stats" => [
                    "total_requests" => (int)($stats['total_requests'] ?? 0),
                    "success_count" => (int)($stats['success_count'] ?? 0),
                    "error_count" => (int)($stats['error_count'] ?? 0),
                    "avg_response_time" => (float)($stats['avg_response_time'] ?? 0),
                    "last_request" => $stats['last_request'] ?? null,
                    "first_request" => $stats['first_request'] ?? null,
                    "unique_endpoints" => (int)($stats['unique_endpoints'] ?? 0),
                    "unique_ips" => (int)($stats['unique_ips'] ?? 0)
                ],
                "top_endpoints" => $top_endpoints,
                "pagination" => [
                    "limit" => $limit,
                    "offset" => $offset,
                    "has_more" => ($offset + $limit) < $total,
                    "page" => floor($offset / $limit) + 1,
                    "total_pages" => ceil($total / $limit)
                ],
                "filtros_aplicados" => [  // Opcional: feedback sobre filtros
                    "data_inicio" => $data_inicio,
                    "data_fim" => $data_fim,
                    "metodo" => $metodo,
                    "status" => $status
                ]
            ]);
            break;

        // =========================================================
        // 🔹 ROTA: Buscar Usuário API (GET) - REQUER AUTH
        // =========================================================
        case 'buscar_usuario_api':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            // Validar se o ID foi enviado
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                response(["error" => "ID do usuário não fornecido."], 400);
            }

            $id = (int)$_GET['id'];

            // Buscar usuário por ID
            $sql = "
                SELECT id, username, email, cod_sis, role, permissions, is_active, created_at, updated_at
                FROM sbd95.api_admins
                WHERE id = $1
            ";

            $result = pg_query_params($conn, $sql, [$id]);

            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $usuario = pg_fetch_assoc($result);

            if (!$usuario) {
                response([
                    "success" => false,
                    "error" => "Usuário não encontrado."
                ], 404);
            }

            // Decodificar permissions de JSON para array
            if (isset($usuario['permissions'])) {
                $usuario['permissions'] = json_decode($usuario['permissions'], true) ?? [];
            }

            // Converter is_active para boolean
            $usuario['is_active'] = ($usuario['is_active'] === 't' || $usuario['is_active'] === true);

            response([
                "success" => true,
                "usuario" => $usuario
            ]);
            break;

        // =========================================================
        // 🔹 ROTA: Atualizar Usuário/Admin API (PUT) - REQUER MASTER
        // =========================================================
        case 'atualizar_usuario_api':
            if ($method !== 'PUT') response(["error" => "Método não permitido. Use PUT."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            // Só master pode atualizar
            if ($user_data && isset($user_data['role']) && $user_data['role'] !== 'master') {
                response(["error" => "Acesso negado. Apenas master pode atualizar usuários API."], 403);
                break;
            }

            $username = isset($input['username']) ? trim($input['username']) : null;
            $email = isset($input['email']) ? trim($input['email']) : null;
            $password = $input['password'] ?? null;
            $cod_sis = array_key_exists('cod_sis', $input) ? trim($input['cod_sis']) : null;
            $role = $input['role'] ?? null;
            $permissions = array_key_exists('permissions', $input) ? json_encode($input['permissions'] ?? []) : null;
            $is_active = isset($input['is_active']) ? ($input['is_active'] ? 'true' : 'false') : null;

            if (
                $username === null &&
                $email === null &&
                $password === null &&
                $cod_sis === null &&
                $role === null &&
                $permissions === null &&
                $is_active === null
            ) {
                response(["error" => "Pelo menos um campo deve ser fornecido"], 400);
                break;
            }

            $set = [];
            $params = [];
            $idx = 1;

            if ($username !== null && $username !== '') {
                $set[] = "username = $" . $idx++;
                $params[] = $username;
            }
            if ($email !== null && $email !== '') {
                $set[] = "email = $" . $idx++;
                $params[] = $email;
            }
            if ($password !== null && $password !== '') {
                $set[] = "password_hash = $" . $idx++;
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($cod_sis !== null) {
                $set[] = "cod_sis = $" . $idx++;
                $params[] = ($cod_sis !== '' ? $cod_sis : null);
            }
            if ($role) {
                $set[] = "role = $" . $idx++;
                $params[] = $role;
            }
            if ($permissions !== null) {
                $set[] = "permissions = $" . $idx++ . "::jsonb";
                $params[] = $permissions;
            }
            if ($is_active !== null) {
                $set[] = "is_active = $" . $idx++;
                $params[] = $is_active;
            }

            $set[] = "updated_at = now()";
            $params[] = $id;
            $sql = "UPDATE sbd95.api_admins SET " . implode(', ', $set) . " WHERE id = $" . $idx;

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                response(["error" => "Erro ao atualizar: " . pg_last_error($conn)], 500);
                break;
            }

            $affected = pg_affected_rows($result);
            if ($affected === 0) {
                response(["error" => "Usuário não encontrado"], 404);
                break;
            }

            response([
                "success" => true,
                "message" => "Usuário API atualizado com sucesso!",
                "user_id" => $id
            ]);
            break;

        // =========================================================
        // 🔹 ROTA: Excluir Usuário/Admin API (DELETE) - REQUER MASTER
        // =========================================================
        case 'excluir_usuario_api':
            if ($method !== 'DELETE') response(["error" => "Método não permitido. Use DELETE."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            // Só master pode excluir
            if ($user_data && isset($user_data['role']) && $user_data['role'] !== 'master') {
                response(["error" => "Acesso negado. Apenas master pode excluir usuários API."], 403);
                break;
            }

            $sql = "DELETE FROM sbd95.api_admins WHERE id = $1";
            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result) {
                response(["error" => "Erro ao excluir: " . pg_last_error($conn)], 500);
                break;
            }

            $affected = pg_affected_rows($result);
            if ($affected === 0) {
                response(["error" => "Usuário não encontrado"], 404);
                break;
            }

            response([
                "success" => true,
                "message" => "Usuário API excluído com sucesso!",
                "user_id" => $id
            ]);
            break;



        default:
            response(["error" => "Rota inválida"], 400);
    }
} catch (Exception $e) {
    error_log("Erro na API de Gerenciamento de Usuários API: " . $e->getMessage());
    response(["error" => "Erro no servidor: " . $e->getMessage()], 500);
}
