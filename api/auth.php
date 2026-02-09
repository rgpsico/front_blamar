<?php

/**
 * API de Autenticação Simples
 *
 * Descrição:
 * Endpoint para autenticação de usuários gerais e administradores da API.
 * Gera tokens JWT-like (string aleatória segura) armazenados na tabela sbd95.api_user_tokens.
 * Tokens válidos por 24 horas (ou 30 dias no caso de token genérico por email).
 *
 * Endpoints:
 * - POST ?request=login_admin
 *         → Login para administradores (tabela sbd95.api_admins)
 *
 * - POST ?request=autenticar
 *         → Login genérico para usuários da API (também usa sbd95.api_admins)
 *
 * - POST ?request=gerar_token_email
 *         → Gera token temporário vinculado apenas ao email (útil para reset de senha, magic link, etc.)
 *
 * Métodos suportados:
 *   POST → login_admin, autenticar, gerar_token_email
 *   OPTIONS → para CORS preflight
 *
 * Cabeçalhos recomendados:
 *   Content-Type: application/json
 *
 * Códigos HTTP esperados:
 *   200  OK (sucesso com token)
 *   201  Created (não usado aqui, mas possível em futuras rotas)
 *   400  Bad Request (campos faltando/inválidos)
 *   401  Unauthorized (credenciais erradas)
 *   403  Forbidden (usuário inativo)
 *   404  Not Found (email não encontrado – apenas em gerar_token_email)
 *   405  Method Not Allowed
 *   500  Internal Server Error
 *
 * ----------------------------------------------------------------------
 * PAYLOAD DE REQUISIÇÃO E RESPOSTA ESPERADA – DETALHADO
 * ----------------------------------------------------------------------
 *
 * 1. POST ?request=login_admin
 *    Payload de requisição (body JSON):
 *    {
 *      "username": "admin_master",
 *      "password": "SenhaForte123!"
 *    }
 *
 *    Resposta de sucesso (200 OK):
 *    {
 *      "success": true,
 *      "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6",
 *      "user": {
 *        "id": 1,
 *        "username": "admin_master",
 *        "email": "admin@blumar.com.br",
 *        "role": "superadmin",
 *        "permissions": ["beach_houses:write", "abt:read", "abt:write", "users:manage"],
 *        "created_at": "2024-05-10 14:30:00"
 *      },
 *      "auth_type": "api_admin",
 *      "expires_in": 86400,
 *      "message": "Login de admin realizado com sucesso!"
 *    }
 *
 *    Respostas de erro comuns:
 *    401 → { "error": "Credenciais inválidas" }
 *    403 → { "error": "Usuário inativo. Contate o administrador." }
 *    400 → { "error": "Username e password são obrigatórios" }
 *
 *
 * 2. POST ?request=autenticar
 *    Payload de requisição (body JSON):
 *    {
 *      "login": "usuario_api",
 *      "senha": "MinhaSenha456"
 *    }
 *
 *    Resposta de sucesso (200 OK):
 *    {
 *      "success": true,
 *      "token": "f9e8d7c6b5a4z3y2x1w0v9u8t7s6r5q4p3o2n1m0l9k8j7i6h5g4",
 *      "user": {
 *        "id": 42,
 *        "cod_sis": "usuario_api",
 *        "nome": "usuario_api",
 *        "apelido": "usuario_api",
 *        "email": "user@exemplo.com",
 *        "nivel": "editor",
 *        "departamento": null
 *      },
 *      "auth_type": "api_user",
 *      "expires_in": 86400,
 *      "message": "Autenticação realizada com sucesso!"
 *    }
 *
 *    Erros: mesmos códigos e formatos de login_admin
 *
 *
 * 3. POST ?request=gerar_token_email
 *    Payload de requisição (body JSON):
 *    {
 *      "email": "joao.silva@exemplo.com"
 *    }
 *
 *    Resposta de sucesso (200 OK):
 *    {
 *      "success": true,
 *      "token": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
 *      "user_id": 15,
 *      "expires_in": 2592000,
 *      "message": "Token genérico criado para joao.silva@exemplo.com"
 *    }
 *
 *    Respostas de erro:
 *    400 → { "error": "Email inválido." }
 *    404 → { "error": "Usuário com email 'joao.silva@exemplo.com' não encontrado." }
 *    500 → { "error": "Erro ao salvar token." }
 *
 * Observações importantes:
 * - Todos os tokens são armazenados hasheados (SHA-256) na tabela sbd95.api_user_tokens
 * - Expiração padrão: 24 horas (86400 segundos) para login normal
 * - Expiração estendida: 30 dias (2592000 segundos) para token por email
 * - cod_sis na tabela de tokens usa prefixos:
 *     - "API-{id}" para logins normais
 *     - "GEN-{id}" para tokens gerados por email
 * - Use o token no header: Authorization: Bearer <token>
 * - Em rotas protegidas (ex: APIs de beach_houses/abt), valide o token via função validarToken()
 *
 * Recomendações para frontend:
 * - Armazene o token em localStorage ou httpOnly cookie seguro
 * - Inclua em todas as requisições subsequentes: Authorization: Bearer {token}
 * - Trate 401/403 exibindo tela de login novamente
 */

// ========================================
// 🔧 CONFIGURAÇÕES INICIAIS
// ========================================
date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../util/connection.php';

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

try {
    switch ($request) {

        // =========================================================
        // 🔹 ROTA: Login Admin API (POST) - Autenticação de Admins
        // =========================================================
        case 'login_admin':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            $username = isset($input['username']) ? trim($input['username']) : null;
            $password = isset($input['password']) ? $input['password'] : null;

            if (!$username || !$password) {
                response(["error" => "Username e password são obrigatórios"], 400);
            }

            // Buscar admin na tabela api_admins
            $sql = "
                SELECT 
                    id, 
                    username, 
                    email, 
                    password_hash, 
                    role, 
                    permissions, 
                    is_active,
                    created_at
                FROM sbd95.api_admins 
                WHERE username = $1
                LIMIT 1
            ";
            $result = pg_query_params($conn, $sql, [$username]);

            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Credenciais inválidas"], 401);
            }

            $admin = pg_fetch_assoc($result);

            // Verificar se está ativo
            if ($admin['is_active'] === 'f' || $admin['is_active'] === false) {
                response(["error" => "Usuário inativo. Contate o administrador."], 403);
            }

            // Verificar senha
            if (!password_verify($password, $admin['password_hash'])) {
                response(["error" => "Credenciais inválidas"], 401);
            }

            // Decodificar permissions
            $permissions = json_decode($admin['permissions'], true) ?? [];

            // Gera token seguro (64 caracteres)
            $token = bin2hex(random_bytes(32));

            // Armazena token na tabela api_user_tokens (ajustado para colunas existentes)
            $token_hash = hash('sha256', $token);
            $expires_at = date('Y-m-d H:i:s', time() + 86400); // 24 horas
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            // Para api_admins, usamos o ID como referência
            // Ajuste: como api_user_tokens usa cod_sis (varchar 16), vamos adaptar
            // Você pode criar uma nova coluna ou usar um prefixo como "API-{id}"
            $cod_sis_ref = 'API-' . $admin['id']; // Prefixo para identificar que é admin da API

            $sql_insert = "
                INSERT INTO sbd95.api_user_tokens 
                    (cod_sis, token_hash, expires_at, user_agent, ip)
                VALUES ($1, $2, $3, $4, $5)
            ";

            $params_insert = [
                $cod_sis_ref,
                $token_hash,
                $expires_at,
                $user_agent,
                $ip
            ];

            $result_insert = pg_query_params($conn, $sql_insert, $params_insert);
            if (!$result_insert) {
                throw new Exception("Erro ao armazenar token: " . pg_last_error($conn));
            }

            // Dados do usuário para retornar
            $user_data = [
                'id' => (int)$admin['id'],
                'username' => $admin['username'],
                'email' => $admin['email'],
                'role' => $admin['role'],
                'permissions' => $permissions,
                'created_at' => $admin['created_at']
            ];

            response([
                "success" => true,
                "token" => $token,
                "user" => $user_data,
                "auth_type" => "api_admin",
                "expires_in" => 86400,
                "message" => "Login de admin realizado com sucesso!"
            ]);
            break;

        // =========================================================
        // 🔹 ROTA: Autenticar Usuário da API (POST) - Usa api_admins
        // =========================================================
        case 'autenticar':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            $username = isset($input['login']) ? trim($input['login']) : null;
            $password = isset($input['senha']) ? $input['senha'] : null;

            if (!$username || !$password) {
                response(["error" => "Login e senha são obrigatórios"], 400);
            }

            // Buscar usuário na tabela api_admins (substitui a lógica antiga de func e conteudo_internet)
            $sql = "
                SELECT 
                    id, 
                    username, 
                    email, 
                    password_hash, 
                    role, 
                    permissions, 
                    is_active,
                    created_at
                FROM sbd95.api_admins 
                WHERE username = $1
                LIMIT 1
            ";
            $result = pg_query_params($conn, $sql, [$username]);

            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Credenciais inválidas"], 401);
            }

            $user = pg_fetch_assoc($result);

            // Verificar se está ativo
            if ($user['is_active'] === 'f' || $user['is_active'] === false) {
                response(["error" => "Usuário inativo. Contate o administrador."], 403);
            }

            // Verificar senha
            if (!password_verify($password, $user['password_hash'])) {
                response(["error" => "Credenciais inválidas"], 401);
            }

            // Decodificar permissions
            $permissions = json_decode($user['permissions'], true) ?? [];

            // Gera token seguro (64 caracteres)
            $token = bin2hex(random_bytes(32));

            // Armazena token na tabela api_user_tokens (ajustado para colunas existentes)
            $token_hash = hash('sha256', $token);
            $expires_at = date('Y-m-d H:i:s', time() + 86400); // 24 horas
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            // Para usuários da API, usamos o ID como referência
            $cod_sis_ref = 'API-' . $user['id']; // Prefixo para identificar usuário da API

            $sql_insert = "
                INSERT INTO sbd95.api_user_tokens 
                    (cod_sis, token_hash, expires_at, user_agent, ip)
                VALUES ($1, $2, $3, $4, $5)
            ";

            $params_insert = [
                $cod_sis_ref,
                $token_hash,
                $expires_at,
                $user_agent,
                $ip
            ];

            $result_insert = pg_query_params($conn, $sql_insert, $params_insert);
            if (!$result_insert) {
                throw new Exception("Erro ao armazenar token: " . pg_last_error($conn));
            }

            // Tenta resolver cod_sis e pk_usuario no legado (conteudo_internet.usuario)
            $cod_sis_real = null;
            $pk_usuario_real = null;
            $res_func = pg_query_params(
                $conn,
                "SELECT pk_usuario, cod_sis FROM conteudo_internet.usuario WHERE email = $1 OR email_pessoal = $1 OR apelido = $2 OR nome = $2 LIMIT 1",
                [$user['email'], $user['username']]
            );
            if ($res_func && pg_num_rows($res_func) > 0) {
                $row_func = pg_fetch_assoc($res_func);
                $pk_usuario_real = $row_func['pk_usuario'];
                $cod_sis_real = $row_func['cod_sis'];
            }

            // Dados do usuário para retornar (compatibilidade legado)
            $user_data = [
                'id' => (int)$user['id'],
                'pk_usuario' => $pk_usuario_real ? (int)$pk_usuario_real : null,
                'cod_sis' => $cod_sis_real ?: $user['username'],
                'nome' => $user['username'],
                'apelido' => $user['username'],
                'email' => $user['email'],
                'nivel' => $user['role'],
                'departamento' => null
            ];

            response([
                "success" => true,
                "token" => $token,
                "user" => $user_data,
                "auth_type" => "api_user",
                "expires_in" => 86400,
                "message" => "Autenticação realizada com sucesso!"
            ]);
            break;

        case 'gerar_token_email':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $email = isset($input['email']) ? trim(strtolower($input['email'])) : null;

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                response(["error" => "Email inválido."], 400);
            }

            // Busca usuário por email em api_admins (ajuste tabela se necessário)
            $sql = "SELECT id FROM sbd95.api_admins WHERE LOWER(email) = $1 LIMIT 1";
            $result = pg_query_params($conn, $sql, [$email]);
            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Usuário com email '$email' não encontrado."], 404);
            }

            $user_row = pg_fetch_assoc($result);
            $user_id = (int)$user_row['id'];

            // Gera token genérico (ex: hash simples de email + timestamp + user_id)
            $token_raw = $email . time() . $user_id . random_bytes(16);  // Aleatório seguro
            $token = hash('sha256', $token_raw);  // Token fixo de 64 chars

            // Armazena token em api_user_tokens (similar ao auth original)
            $token_hash = hash('sha256', $token);  // Hash para DB
            $expires_at = date('Y-m-d H:i:s', time() + 86400 * 30);  // 30 dias para genérico
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $cod_sis_ref = 'GEN-' . $user_id;  // Prefixo para tokens genéricos

            $sql_insert = "INSERT INTO sbd95.api_user_tokens (cod_sis, token_hash, expires_at, user_agent, ip) VALUES ($1, $2, $3, $4, $5)";
            $result_insert = pg_query_params($conn, $sql_insert, [$cod_sis_ref, $token_hash, $expires_at, $user_agent, $ip]);

            if (!$result_insert) {
                response(["error" => "Erro ao salvar token."], 500);
            }

            response([
                "success" => true,
                "token" => $token,  // Retorna o token plano para JS
                "user_id" => $user_id,
                "expires_in" => 86400 * 30,  // 30 dias
                "message" => "Token genérico criado para $email"
            ]);
            break;

        default:
            response(["error" => "Rota inválida"], 400);
    }
} catch (Exception $e) {
    error_log("Erro na API de Autenticação: " . $e->getMessage());
    response(["error" => "Erro no servidor: " . $e->getMessage()], 500);
}
