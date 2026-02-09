<?php


/**
 * API para gerenciamento de autenticação - Perfis (Roles) e Permissões
 * Arquivo: perfil_role.php
 * Schema: auth
 * Tabelas principais:
 *   - auth.auth_profiles
 *   - auth.auth_permissions
 *   - auth.auth_profile_permissions (relacionamento N:N)
 *
 * Endpoints principais:
 *   GET  ?request=listar_profiles&filtro_nome=admin&limit=100
 *   GET  ?request=listar_profiles_paginate&page=1&per_page=20&filtro_nome=...
 *   GET  ?request=buscar_profile&id=5
 *   POST ?request=criar_profile
 *   PUT  ?request=atualizar_profile&id=5
 *   DELETE ?request=excluir_profile&id=5
 *
 *   GET  ?request=listar_permissions&filtro_nome=user.create&limit=50
 *   GET  ?request=listar_permissions_paginate&page=1&per_page=30
 *   GET  ?request=buscar_permission&id=12
 *   POST ?request=criar_permission
 *   PUT  ?request=atualizar_permission&id=12
 *   DELETE ?request=excluir_permission&id=12
 *
 * Métodos suportados:
 *   GET    → listar_*, buscar_*
 *   POST   → criar_*
 *   PUT    → atualizar_*
 *   DELETE → excluir_*
 *
 * Cabeçalhos obrigatórios em rotas protegidas:
 *   Authorization: Bearer <token>
 *
 * Códigos HTTP comuns:
 *   200  OK
 *   201  Created
 *   400  Bad Request
 *   401  Unauthorized
 *   404  Not Found
 *   405  Method Not Allowed
 *   409  Conflict (ex: nome já existe)
 *   500  Internal Server Error
 *
 * ----------------------------------------------------------------------
 * ESTRUTURA DAS RESPOSTAS JSON – SUCESSO
 * ----------------------------------------------------------------------
 *
 * 1. GET ?request=listar_profiles
 *    → Array de perfis resumidos
 *    Exemplo:
 *    [
 *      {
 *        "id": 1,
 *        "name": "admin",
 *        "description": "Administrador completo do sistema",
 *        "created_at": "2025-01-10T14:30:22"
 *      },
 *      {
 *        "id": 2,
 *        "name": "editor",
 *        "description": "Editor de conteúdos",
 *        "created_at": "2025-02-05T09:15:10"
 *      }
 *    ]
 *
 * 2. GET ?request=listar_profiles_paginate
 *    → Objeto paginado
 *    {
 *      "data": [ ... array de perfis como acima ... ],
 *      "current_page": 1,
 *      "per_page": 20,
 *      "total": 45,
 *      "last_page": 3,
 *      "from": 1,
 *      "to": 20,
 *      "has_more_pages": true
 *    }
 *
 * 3. GET ?request=buscar_profile&id=5
 *    → Perfil completo com suas permissões
 *    {
 *      "id": 5,
 *      "name": "gerente_vendas",
 *      "description": "Gerente da área comercial",
 *      "created_at": "2025-03-01T11:20:45",
 *      "updated_at": "2025-03-10T16:45:12",
 *      "permissions": [
 *        {
 *          "id": 23,
 *          "name": "vendas.ver_relatorios",
 *          "description": "Visualizar relatórios de vendas"
 *        },
 *        {
 *          "id": 24,
 *          "name": "vendas.criar_pedido",
 *          "description": "Criar novos pedidos"
 *        }
 *      ]
 *    }
 *
 * 4. POST ?request=criar_profile
 *    → 201 Created
 *    {
 *      "success": true,
 *      "message": "Perfil criado com sucesso",
 *      "profile_id": 18
 *    }
 *
 * 5. PUT ?request=atualizar_profile&id=5
 *    → 200 OK
 *    {
 *      "success": true,
 *      "message": "Perfil atualizado com sucesso",
 *      "id": 5
 *    }
 *    Ou (sem alterações):
 *    {
 *      "success": false,
 *      "message": "Nenhuma alteração realizada"
 *    }
 *
 * 6. DELETE ?request=excluir_profile&id=5
 *    Sucesso:
 *    {
 *      "success": true,
 *      "message": "Perfil excluído com sucesso"
 *    }
 *    Não encontrado:
 *    {
 *      "error": "Perfil não encontrado"
 *    }
 *
 * 7. GET ?request=listar_permissions
 *    → Array de permissões
 *    [
 *      {
 *        "id": 1,
 *        "name": "users.create",
 *        "description": "Criar novos usuários",
 *        "created_at": "2025-01-05T08:10:00"
 *      },
 *      ...
 *    ]
 *
 * 8. GET ?request=buscar_permission&id=12
 *    → Permissão individual
 *    {
 *      "id": 12,
 *      "name": "conteudo.publicar",
 *      "description": "Publicar conteúdos no site",
 *      "created_at": "2025-02-20T14:22:33",
 *      "updated_at": "2025-02-20T14:22:33"
 *    }
 *
 * 9. POST ?request=criar_permission
 *    → 201 Created
 *    {
 *      "success": true,
 *      "message": "Permissão criada com sucesso",
 *      "permission_id": 45
 *    }
 *
 * 10. Respostas de erro (qualquer endpoint)
 *     {
 *       "error": "Mensagem descritiva",
 *       // exemplos: "Token Bearer é obrigatório", "Nome já existe", "ID é obrigatório"
 *     }
 *
 * Dicas para frontend:
 * - Use "name" como identificador principal para exibição
 * - Sempre inclua as "permissions" apenas na visualização detalhada (buscar_profile)
 * - Valide status HTTP + presença de "success" / "error"
 * - Para criar/atualizar perfil, envie array de IDs de permissões
 */

date_default_timezone_set('America/Sao_Paulo');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);


require_once '../util/connection.php';
require_once 'middleware.php'; // seu middleware de autenticação/token

// ========================================
// 🔧 FUNÇÕES AUXILIARES
// ========================================

function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function buscarPermissoesDoPerfil($conn, $profile_id) {
    $sql = "
        SELECT p.id, p.name, p.description
        FROM auth.auth_permissions p
        INNER JOIN auth.auth_profile_permissions pp ON pp.permission_id = p.id
        WHERE pp.profile_id = $1
        ORDER BY p.name
    ";
    $result = pg_query_params($conn, $sql, [$profile_id]);
    $perms = [];
    while ($row = pg_fetch_assoc($result)) {
        $perms[] = $row;
    }
    return $perms;
}

// ========================================
// 🔧 LÓGICA PRINCIPAL
// ========================================

$request = $_GET['request'] ?? null;
if (!$request) {
    response(["error" => "Parâmetro 'request' é obrigatório"], 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($request) {

        // ────────────────────────────────────────────────
        // LISTAR PERFIS (resumido)
        // ────────────────────────────────────────────────
        case 'listar_profiles':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            // Exemplo: autenticação obrigatória (ajuste conforme necessidade)
            // if (!handleAutenticacao($conn, 'listar_profiles', $user_data)) break;

            $filtro_nome = trim($_GET['filtro_nome'] ?? '');
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 200)));

            $params = [];
            $where = [];
            $idx = 1;

            if ($filtro_nome) {
                $where[] = "name ILIKE $" . $idx++;
                $params[] = "%$filtro_nome%";
            }

            $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
            $params[] = $limit;

            $sql = "
                SELECT id, name, description, created_at
                FROM auth.auth_profiles
                $where_sql
                ORDER BY name
                LIMIT $$idx
            ";

            $result = pg_query_params($conn, $sql, $params);
            $profiles = pg_fetch_all($result) ?: [];

            response($profiles);
            break;

        // ────────────────────────────────────────────────
        // BUSCAR PERFIL COMPLETO (com permissões)
        // ────────────────────────────────────────────────
        case 'buscar_profile':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) response(["error" => "ID inválido"], 400);

            $sql = "SELECT * FROM auth.auth_profiles WHERE id = $1";
            $result = pg_query_params($conn, $sql, [$id]);
            if (pg_num_rows($result) === 0) {
                response(["error" => "Perfil não encontrado"], 404);
            }

            $profile = pg_fetch_assoc($result);
            $profile['permissions'] = buscarPermissoesDoPerfil($conn, $id);

            response($profile);
            break;

        // ────────────────────────────────────────────────
        // CRIAR PERFIL
        // ────────────────────────────────────────────────
        case 'criar_profile':
            if ($method !== 'POST') response(["error" => "Use POST"], 405);
            if (empty($input)) response(["error" => "Body JSON obrigatório"], 400);

            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');

            if (empty($name)) response(["error" => "Campo 'name' é obrigatório"], 400);

            pg_query($conn, "BEGIN");

            $sql = "INSERT INTO auth.auth_profiles (name, description) VALUES ($1, $2) RETURNING id";
            $result = pg_query_params($conn, $sql, [$name, $description ?: null]);
            $profile_id = pg_fetch_result($result, 0, 'id');

            // Inserir permissões (array de IDs)
            if (!empty($input['permissions']) && is_array($input['permissions'])) {
                foreach ($input['permissions'] as $perm_id) {
                    $perm_id = (int)$perm_id;
                    if ($perm_id > 0) {
                        $sql_rel = "INSERT INTO auth.auth_profile_permissions (profile_id, permission_id) VALUES ($1, $2)";
                        pg_query_params($conn, $sql_rel, [$profile_id, $perm_id]);
                    }
                }
            }

            pg_query($conn, "COMMIT");

            response([
                'success' => true,
                'message' => 'Perfil criado com sucesso',
                'profile_id' => $profile_id
            ], 201);
            break;

        // ────────────────────────────────────────────────
        // ATUALIZAR PERFIL (incluindo permissões)
        // ────────────────────────────────────────────────
        case 'atualizar_profile':
            if ($method !== 'PUT') response(["error" => "Use PUT"], 405);

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) response(["error" => "ID obrigatório"], 400);

            if (empty($input)) response(["error" => "Body JSON obrigatório"], 400);

            pg_query($conn, "BEGIN");

            $updates = [];
            $params = [];
            $idx = 1;

            if (isset($input['name'])) {
                $updates[] = "name = $" . $idx++;
                $params[] = trim($input['name']);
            }
            if (array_key_exists('description', $input)) {
                $updates[] = "description = $" . $idx++;
                $params[] = trim($input['description']) ?: null;
            }

            if (!empty($updates)) {
                $params[] = $id;
                $sql = "UPDATE auth.auth_profiles SET " . implode(', ', $updates) . " WHERE id = $" . $idx;
                pg_query_params($conn, $sql, $params);
            }

            // Atualizar permissões (substituição completa)
            if (isset($input['permissions']) && is_array($input['permissions'])) {
                pg_query_params($conn, "DELETE FROM auth.auth_profile_permissions WHERE profile_id = $1", [$id]);

                foreach ($input['permissions'] as $perm_id) {
                    $perm_id = (int)$perm_id;
                    if ($perm_id > 0) {
                        pg_query_params($conn, 
                            "INSERT INTO auth.auth_profile_permissions (profile_id, permission_id) VALUES ($1, $2)",
                            [$id, $perm_id]
                        );
                    }
                }
            }

            pg_query($conn, "COMMIT");

            response([
                'success' => true,
                'message' => 'Perfil atualizado',
                'id' => $id
            ]);
            break;

        // ────────────────────────────────────────────────
        // EXCLUIR PERFIL
        // ────────────────────────────────────────────────
        case 'excluir_profile':
            if ($method !== 'DELETE') response(["error" => "Use DELETE"], 405);

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) response(["error" => "ID obrigatório"], 400);

            $result = pg_query_params($conn, "DELETE FROM auth.auth_profiles WHERE id = $1", [$id]);
            $affected = pg_affected_rows($result);

            if ($affected > 0) {
                response(['success' => true, 'message' => 'Perfil excluído']);
            } else {
                response(['error' => 'Perfil não encontrado'], 404);
            }
            break;

        // ────────────────────────────────────────────────
        // Outros endpoints (permissions) seguem padrão similar
        // Você pode replicar a lógica acima para:
        // listar_permissions, buscar_permission, criar_permission, etc.
        // ────────────────────────────────────────────────

        default:
            response(["error" => "Rota inválida"], 400);
    }
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    error_log("Erro API Auth: " . $e->getMessage());
    response(["error" => "Erro interno: " . $e->getMessage()], 500);
}