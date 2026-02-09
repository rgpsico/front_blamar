<?php

ob_start();

/**
 * API para gerenciamento de permiss�es
 * Arquivo: permissao_role.php
 * Schema: auth
 * Tabelas principais:
 *   - auth.auth_permissions
 *   - auth.auth_profile_permissions (relacionamento N:N)
 *
 * Endpoints principais:
 *   GET    ?request=listar_permissions&filtro_nome=users.create&limit=100
 *   GET    ?request=listar_permissions_paginate&page=1&per_page=20&filtro_nome=...
 *   GET    ?request=buscar_permission&id=12
 *   POST   ?request=criar_permission
 *   PUT    ?request=atualizar_permission&id=12
 *   DELETE ?request=excluir_permission&id=12
 *
 * M�todos suportados:
 *   GET    ? listar_*, buscar_*
 *   POST   ? criar_*
 *   PUT    ? atualizar_*
 *   DELETE ? excluir_*
 *
 * Cabe�alhos obrigat�rios em rotas protegidas:
 *   Authorization: Bearer <token>
 */

date_default_timezone_set('America/Sao_Paulo');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../util/connection.php';
require_once 'middleware.php'; // seu middleware de autentica��o/token

// ========================================
// FUNCOES AUXILIARES
// ========================================

function response($data, $code = 200) {
    http_response_code($code);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function permissionExistsByName($conn, $name, $ignoreId = null) {
    if ($ignoreId) {
        $sql = "SELECT 1 FROM auth.auth_permissions WHERE name = $1 AND id <> $2 LIMIT 1";
        $result = pg_query_params($conn, $sql, [$name, $ignoreId]);
    } else {
        $sql = "SELECT 1 FROM auth.auth_permissions WHERE name = $1 LIMIT 1";
        $result = pg_query_params($conn, $sql, [$name]);
    }
    return pg_num_rows($result) > 0;
}

// ========================================
// LOGICA PRINCIPAL
// ========================================

$request = $_GET['request'] ?? null;
if (!$request) {
    response(["error" => "Par�metro 'request' � obrigat�rio"], 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($request) {

        // ------------------------------
        // LISTAR PERMISSOES (resumido)
        // ------------------------------
        case 'listar_permissions':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

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
                FROM auth.auth_permissions
                $where_sql
                ORDER BY name
                LIMIT $$idx
            ";

            $result = pg_query_params($conn, $sql, $params);
            $permissions = pg_fetch_all($result) ?: [];

            response($permissions);
            break;

        // ------------------------------
        // LISTAR PERMISSOES (paginado)
        // ------------------------------
        case 'listar_permissions_paginate':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $page = max(1, (int)($_GET['page'] ?? 1));
            $per_page = max(1, min(200, (int)($_GET['per_page'] ?? 20)));
            $offset = ($page - 1) * $per_page;
            $filtro_nome = trim($_GET['filtro_nome'] ?? '');

            $params = [];
            $where = [];
            $idx = 1;

            if ($filtro_nome) {
                $where[] = "name ILIKE $" . $idx++;
                $params[] = "%$filtro_nome%";
            }

            $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

            $count_sql = "SELECT COUNT(*) FROM auth.auth_permissions $where_sql";
            $count_result = pg_query_params($conn, $count_sql, $params);
            $total = (int)pg_fetch_result($count_result, 0, 0);

            $params[] = $per_page;
            $params[] = $offset;
            $limit_idx = $idx++;
            $offset_idx = $idx++;

            $sql = "
                SELECT id, name, description, created_at
                FROM auth.auth_permissions
                $where_sql
                ORDER BY name
                LIMIT $$limit_idx OFFSET $$offset_idx
            ";

            $result = pg_query_params($conn, $sql, $params);
            $rows = pg_fetch_all($result) ?: [];
            $last_page = $per_page ? (int)ceil($total / $per_page) : 1;

            response([
                'data' => $rows,
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'last_page' => $last_page,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $per_page, $total),
                'has_more_pages' => $page < $last_page
            ]);
            break;

        // ------------------------------
        // BUSCAR PERMISSAO
        // ------------------------------
        case 'buscar_permission':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) response(["error" => "ID inv�lido"], 400);

            $sql = "SELECT * FROM auth.auth_permissions WHERE id = $1";
            $result = pg_query_params($conn, $sql, [$id]);
            if (pg_num_rows($result) === 0) {
                response(["error" => "Permiss�o n�o encontrada"], 404);
            }

            $permission = pg_fetch_assoc($result);
            response($permission);
            break;

        // ------------------------------
        // CRIAR PERMISSAO
        // ------------------------------
        case 'criar_permission':
            if ($method !== 'POST') response(["error" => "Use POST"], 405);
            if (empty($input)) response(["error" => "Body JSON obrigat�rio"], 400);

            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');

            if (empty($name)) response(["error" => "Campo 'name' � obrigat�rio"], 400);

            if (permissionExistsByName($conn, $name)) {
                response(["error" => "Nome j� existe"], 409);
            }

            $sql = "INSERT INTO auth.auth_permissions (name, description) VALUES ($1, $2) RETURNING id";
            $result = pg_query_params($conn, $sql, [$name, $description ?: null]);
            $permission_id = pg_fetch_result($result, 0, 'id');

            response([
                'success' => true,
                'message' => 'Permiss�o criada com sucesso',
                'permission_id' => $permission_id
            ], 201);
            break;

        // ------------------------------
        // ATUALIZAR PERMISSAO
        // ------------------------------
        case 'atualizar_permission':
            if ($method !== 'PUT') response(["error" => "Use PUT"], 405);

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) response(["error" => "ID obrigat�rio"], 400);
            if (empty($input)) response(["error" => "Body JSON obrigat�rio"], 400);

            $updates = [];
            $params = [];
            $idx = 1;

            if (isset($input['name'])) {
                $name = trim($input['name']);
                if ($name === '') response(["error" => "Campo 'name' � obrigat�rio"], 400);
                if (permissionExistsByName($conn, $name, $id)) {
                    response(["error" => "Nome j� existe"], 409);
                }
                $updates[] = "name = $" . $idx++;
                $params[] = $name;
            }
            if (array_key_exists('description', $input)) {
                $updates[] = "description = $" . $idx++;
                $params[] = trim($input['description']) ?: null;
            }

            if (empty($updates)) {
                response(["success" => false, "message" => "Nenhuma alteração realizada"]);
            }

            $params[] = $id;
            $sql = "UPDATE auth.auth_permissions SET " . implode(', ', $updates) . " WHERE id = $" . $idx;
            $result = pg_query_params($conn, $sql, $params);
            if ($result === false) {
                response(["error" => "Erro ao atualizar permissão: " . pg_last_error($conn)], 500);
            }
            $affected = pg_affected_rows($result);

            if ($affected <= 0) {
                $check = pg_query_params($conn, "SELECT 1 FROM auth.auth_permissions WHERE id = $1", [$id]);
                if ($check && pg_num_rows($check) > 0) {
                    response(["success" => false, "message" => "Nenhuma alteração realizada"]);
                }
                response(["error" => "Permissão não encontrada"], 404);
            }

            response([
                'success' => true,
                'message' => 'Permissão atualizada',
                'id' => $id
            ]);
            break;

        // ------------------------------
        // EXCLUIR PERMISSAO
        // ------------------------------
        case 'excluir_permission':
            if ($method !== 'DELETE') response(["error" => "Use DELETE"], 405);

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) response(["error" => "ID obrigat�rio"], 400);

            pg_query($conn, "BEGIN");
            pg_query_params($conn, "DELETE FROM auth.auth_profile_permissions WHERE permission_id = $1", [$id]);
            $result = pg_query_params($conn, "DELETE FROM auth.auth_permissions WHERE id = $1", [$id]);
            $affected = pg_affected_rows($result);
            pg_query($conn, "COMMIT");

            if ($affected > 0) {
                response(['success' => true, 'message' => 'Permiss�o exclu�da']);
            } else {
                response(['error' => 'Permiss�o n�o encontrada'], 404);
            }
            break;

        default:
            response(["error" => "Rota inv�lida"], 400);
    }
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    error_log("Erro API Permissoes: " . $e->getMessage());
    response(["error" => "Erro interno: " . $e->getMessage()], 500);
}
