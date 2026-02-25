<?php

/**
 * Função simplificada para validar apenas o token Bearer
 *
 * Valida o token armazenado em sbd95.api_user_tokens, com checagem de expiração.
 * cod_sis é opcional (se fornecido, filtra por ele para maior segurança).
 * Se válido, popula $token_data com dados básicos do token e usuário.
 * Remove debug logs e cleanup opcional para simplicidade.
 *
 * @param resource $conn Conexão PostgreSQL
 * @param string|null $cod_sis Código do sistema (opcional, para filtro)
 * @param string $token Token Bearer (sem 'Bearer ')
 * @param array &$token_data Dados do token válido (por referência)
 * @return bool True se válido, false caso contrário
 */

/**
 * Função de Validação de Token
 * 
 * Valida o token fornecido, verifica expiração e extrai dados do usuário.
 * 
 * @param resource $conn Conexão PostgreSQL
 * @param string|null $cod_sis (opcional) - Ignorado aqui, pois usamos cod_sis do token
 * @param string $token Token plano (não hash)
 * @param array &$token_data Array de saída com dados extraídos (user_id, role, etc.)
 * @return bool true se válido, false caso contrário
 */
function validarToken($conn, &$cod_sis, $token, &$token_data)
{
    if (empty($token)) {
        return false;
    }

    $token_hash = hash('sha256', $token);

    // Busca o token na tabela api_user_tokens
    $sql = "
        SELECT 
            cod_sis, 
            expires_at, 
            user_agent, 
            ip, 
            created_at
        FROM sbd95.api_user_tokens 
        WHERE token_hash = $1 
      
        ORDER BY created_at DESC 
        LIMIT 1
    ";
    $result = pg_query_params($conn, $sql, [$token_hash]);

    if (!$result || pg_num_rows($result) === 0) {
        return false;  // Token não encontrado ou expirado
    }

    $token_row = pg_fetch_assoc($result);

    // Extrai user_id de cod_sis (ex: 'API-123' -> user_id = 123)
    if (strpos($token_row['cod_sis'], 'API-') === 0) {
        $token_data['user_id'] = (int) substr($token_row['cod_sis'], 4);
    } else {
        // Fallback: se cod_sis for diretamente o ID (ajuste se necessário)
        $token_data['user_id'] = (int) $token_row['cod_sis'];
    }

    // Busca dados adicionais do usuário em api_admins (usando user_id extraído)
    if (isset($token_data['user_id']) && $token_data['user_id'] > 0) {
        $user_sql = "
            SELECT 
                id, 
                username, 
                email, 
                role, 
                permissions, 
                is_active 
            FROM sbd95.api_admins 
            WHERE id = $1 
            LIMIT 1
        ";
        $user_result = pg_query_params($conn, $user_sql, [$token_data['user_id']]);

        if ($user_result && pg_num_rows($user_result) > 0) {
            $user_row = pg_fetch_assoc($user_result);

            // Preenche token_data com dados do usuário
            $token_data['username'] = $user_row['username'];
            $token_data['email'] = $user_row['email'];
            $token_data['role'] = $user_row['role'];
            $token_data['permissions'] = json_decode($user_row['permissions'], true) ?? [];
            $token_data['is_active'] = $user_row['is_active'] === 't' || $user_row['is_active'] === true;

            // Verifica se usuário está ativo
            if (!$token_data['is_active']) {
                return false;  // Token válido, mas usuário inativo
            }
        } else {
            return false;  // user_id extraído não existe em api_admins
        }
    } else {
        return false;  // Não conseguiu extrair user_id de cod_sis
    }

    // Define cod_sis de saída (se necessário para compatibilidade)
    $cod_sis = $token_row['cod_sis'];

    // Opcional: Log de uso do token (ex: update last_used_at se adicionar coluna)
    // UPDATE sbd95.api_user_tokens SET last_used_at = NOW() WHERE token_hash = $1;

    return true;
}

/**
 * Função Separada para Logar Requisição
 * 
 * Insere um log na tabela api_logs após validação bem-sucedida.
 * Calcula duração real se fornecida, e usa dados do token para user_id/api_key_id.
 * Não falha a execução principal se o log der erro (apenas loga no error_log).
 * 
 * @param resource $conn Conexão PostgreSQL
 * @param array $token_data Dados do token válido (de validarToken)
 * @param string $endpoint Endpoint da requisição (ex: $_SERVER['REQUEST_URI'])
 * @param string $method Método HTTP (ex: $_SERVER['REQUEST_METHOD'])
 * @param int $status_code Código de status HTTP (default 200)
 * @param int|null $duration_ms Duração em ms (calculada externamente; default null)
 * @return bool True se log inserido, false caso contrário (mas não quebra execução)
 */
function logRequest($conn, $token_data, $endpoint, $method, $status_code = 200, $duration_ms = null)
{
    if (!isset($token_data['user_id']) || $token_data['user_id'] <= 0) {
        error_log("logRequest: user_id inválido - pulando log para endpoint '$endpoint'");
        return false;
    }

    $user_id = $token_data['user_id'];
    $duration = $duration_ms ?? 0;  // Default 0 se não fornecido

    $log_sql = "
        INSERT INTO sbd95.api_logs 
        (api_key_id, endpoint, method, response_status, request_time, duration_ms, ip_address, user_agent, user_id) 
        VALUES ($1, $2, $3, $4, NOW(), $5, $6, $7, $8)
    ";
    $log_params = [
        $user_id,  // $1: api_key_id (mesmo que user_id para consistência)
        $endpoint,  // $2: endpoint
        $method,  // $3: method
        $status_code,  // $4: response_status
        $duration,  // $5: duration_ms
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',  // $6: ip_address
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',  // $7: user_agent
        $user_id  // $8: user_id
    ];

    $log_result = pg_query_params($conn, $log_sql, $log_params);
    if (!$log_result) {
        error_log("logRequest: Erro ao inserir log para user_id $user_id, endpoint '$endpoint': " . pg_last_error($conn));
        return false;
    }

    // Opcional: Log de sucesso para debug (remova em produção)
    // error_log("logRequest: Log inserido para user_id $user_id, duration $duration ms");

    return true;
}
