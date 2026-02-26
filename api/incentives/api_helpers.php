<?php 

/**
 * =========================================================
 * API RESTful - Módulo Incentivos (COMPLETA)
 * =========================================================
 * 
 * Endpoints disponíveis:
 * 
 * LEITURA (Públicas):
 *   GET    ?request=listar_incentives        - Lista todos os incentivos (com paginação e filtros)
 *   GET    ?request=buscar_incentive&id=X    - Busca 1 incentivo completo (com todos os relacionamentos)
 * 
 * ESCRITA (Autenticadas - Bearer Token):
 *   POST   ?request=criar_incentive          - Cria novo incentivo completo (programa + relacionamentos)
 *   PUT    ?request=atualizar_incentive&id=X - Atualiza incentivo completo (programa + relacionamentos)
 *   DELETE ?request=excluir_incentive&id=X   - Exclui incentivo e todos os relacionamentos
 * 
 * Formato JSON esperado no POST/PUT:
 * {
 *   "inc_name": "string (obrigatório)",
 *   "inc_description": "string",
 *   "hotel_ref_id": int,
 *   "hotel_name_snapshot": "string",
 *   "city_name": "string",
 *   "country_code": "BR",
 *   "inc_status": "active|inactive|draft|archived",
 *   "inc_is_active": true|false,
 *   
 *   "media": [
 *     {
 *       "media_type": "banner|gallery|video|map",
 *       "media_url": "string",
 *       "position": int,
 *       "is_active": true|false
 *     }
 *   ],
 *   
 *   "room_categories": [
 *     {
 *       "room_name": "string",
 *       "quantity": int,
 *       "notes": "string",
 *       "position": int,
 *       "is_active": true|false
 *     }
 *   ],
 *   
 *   "dining": [
 *     {
 *       "name": "string",
 *       "description": "string",
 *       "cuisine": "string",
 *       "capacity": int,
 *       "schedule": "string",
 *       "is_michelin": true|false,
 *       "can_be_private": true|false,
 *       "image_url": "string",
 *       "position": int,
 *       "is_active": true|false
 *     }
 *   ],
 *   
 *   "facilities": [
 *     {
 *       "name": "string",
 *       "icon": "string",
 *       "is_active": true|false
 *     }
 *   ],
 *   
 *   "convention": {
 *     "description": "string",
 *     "total_rooms": int,
 *     "has_360": true|false
 *   },
 *   
 *   "convention_rooms": [
 *     {
 *       "name": "string",
 *       "area_m2": numeric,
 *       "capacity_auditorium": int,
 *       "capacity_banquet": int,
 *       "capacity_classroom": int,
 *       "capacity_u_shape": int,
 *       "notes": "string"
 *     }
 *   ],
 *   
 *   "notes": [
 *     {
 *       "language": "PT",
 *       "note": "string"
 *     }
 *   ]
 * }
 */

// =========================================================
// Configurações iniciais
// =========================================================

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");


// Conexão e Middleware


// =========================================================
// FUNÇÕES AUXILIARES - Resposta e Parâmetros
// =========================================================

function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getParam($name, $default = null) {
    return $_GET[$name] ?? $default;
}

function getStringParam($name, $default = null) {
    $v = getParam($name, $default);
    return ($v !== null) ? trim($v) : null;
}

function getIntParam($name, $default = null) {
    $v = getParam($name, $default);
    return is_numeric($v) ? (int)$v : $default;
}

// =========================================================
// FUNÇÕES AUXILIARES - Formatação e Validação
// =========================================================

function formatString($val) {
    return ($val === '' || $val === null) ? null : trim($val);
}

function formatInt($val) {
    return is_numeric($val) ? (int)$val : null;
}

function formatNumeric($val) {
    if ($val === null || $val === '') return null;
    return is_numeric($val) ? $val : null;
}

function formatBoolean($val) {
    // Trata null, string vazia, undefined
    if ($val === null || $val === '' || $val === 'null' || $val === 'undefined') {
        return 'f'; // Retorna 'f' (formato PostgreSQL) em vez de false
    }
    
    // Se já é boolean PHP, converte para formato PostgreSQL
    if (is_bool($val)) {
        return $val ? 't' : 'f';
    }
    
    // Se é string
    if (is_string($val)) {
        $val = strtolower(trim($val));
        if ($val === 'true' || $val === '1' || $val === 't' || $val === 'yes') {
            return 't';
        }
        if ($val === 'false' || $val === '0' || $val === 'f' || $val === 'no' || $val === '') {
            return 'f';
        }
    }
    
    // Se é número
    if (is_numeric($val)) {
        return ((int)$val === 1) ? 't' : 'f';
    }
    
    // Fallback: converte para boolean e depois para 't'/'f'
    return $val ? 't' : 'f';
}

function formatCountry($val) {
    $v = formatString($val);
    if ($v === null) return null;
    $v = strtoupper($v);
    return (strlen($v) === 2) ? $v : null;
}

function formatStatus($val) {
    $allowed = ['active', 'inactive', 'draft', 'archived'];
    return in_array($val, $allowed, true) ? $val : 'active';
}

function formatMediaType($val) {
    $allowed = ['banner', 'gallery', 'video', 'map'];
    return in_array($val, $allowed, true) ? $val : null;
}

function formatLanguage($val) {
    $v = formatString($val);
    if ($v === null) return null;
    $v = strtoupper($v);
    return (strlen($v) === 2) ? $v : null;
}

function formatPosition($val) {
    $int = formatInt($val);
    return ($int !== null && $int >= 0) ? $int : 0;
}

function boolFromPg($v) {
    // Converte formato PostgreSQL ('t'/'f') para boolean PHP
    if ($v === true || $v === 't' || $v === 'true' || $v === 1 || $v === '1') return true;
    if ($v === false || $v === 'f' || $v === 'false' || $v === 0 || $v === '0') return false;
    return false; // default seguro
}

// =========================================================
// FUNÇÕES AUXILIARES - Banco de Dados
// =========================================================

/**
 * Sanitiza array de parâmetros antes de enviar ao PostgreSQL
 * Garante que booleans nunca sejam strings vazias
 */
function sanitizeParams($params) {
    return array_map(function($val) {
        // Se for string vazia e o contexto sugere boolean, converte para false
        if ($val === '' || $val === null) {
            return null;
        }
        return $val;
    }, $params);
}

function execParams($conn, $sql, $params, $errorMessage) {
    // Sanitiza parâmetros antes de enviar
    $params = sanitizeParams($params);
    
    $res = pg_query_params($conn, $sql, $params);
    if (!$res) {
        // Log detalhado do erro para debug
        error_log("SQL Error: " . $errorMessage);
        error_log("SQL: " . $sql);
        error_log("Params: " . print_r($params, true));
        error_log("PG Error: " . pg_last_error($conn));
        
        throw new Exception($errorMessage . ': ' . pg_last_error($conn));
    }
    return $res;
}

// =========================================================
// FUNÇÕES AUXILIARES - Autenticação
// =========================================================

function requireBearerToken($conn, &$user_data, $cod_sis = null) {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['authorization'] ?? $headers['Authorization'] ?? '';

    if (strpos($auth, 'Bearer ') !== 0) {
        response(["error" => "Token Bearer obrigatório"], 401);
    }

    $token = trim(substr($auth, 7));

    if (!validarToken($conn, $cod_sis, $token, $user_data)) {
        response(["error" => "Token inválido ou expirado"], 401);
    }

    return $token;
}

// =========================================================
// FUNÇÕES DE SINCRONIZAÇÃO - Relacionamentos
// =========================================================

/**
 * Sincroniza mídias do incentivo
 */
function syncMedia($conn, $inc_id, $mediaList) {
  
  
    // Remove todas as mídias existentes
    execParams(
        $conn,
        "DELETE FROM incentive.inc_media WHERE inc_id = $1",
        [$inc_id],
        "Erro ao limpar mídias"
    );

    // Insere novas mídias
    foreach ($mediaList as $index => $media) {
        $media_type = formatMediaType($media['media_type'] ?? null);
        $media_url  = formatString($media['media_url'] ?? null);
        $position   = formatPosition($media['position'] ?? 0);
        $is_active  = formatBoolean($media['is_active'] ?? true);

        if (!$media_type) {
            throw new Exception("Mídia {$index}: media_type inválido ou ausente");
        }
        if (!$media_url) {
            throw new Exception("Mídia {$index}: media_url obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_media 
                (inc_id, media_type, media_url, position, is_active) 
             VALUES ($1, $2, $3, $4, $5)",
            [$inc_id, $media_type, $media_url, $position, $is_active],
            "Erro ao inserir mídia"
        );
    }
}

/**
 * Sincroniza categorias de quartos
 */
function syncRoomCategories($conn, $inc_id, $roomCategories) {
    execParams(
        $conn,
        "DELETE FROM incentive.inc_room_category WHERE inc_id = $1",
        [$inc_id],
        "Erro ao limpar categorias de quartos"
    );

    foreach ($roomCategories as $index => $room) {
        $room_name = formatString($room['room_name'] ?? null);
        $quantity  = formatInt($room['quantity'] ?? null);
        $notes     = formatString($room['notes'] ?? null);
        $position  = formatPosition($room['position'] ?? 0);
        $is_active = formatBoolean($room['is_active'] ?? true);

        if (!$room_name) {
            throw new Exception("Categoria de quarto {$index}: room_name obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_room_category 
                (inc_id, room_name, quantity, notes, position, is_active) 
             VALUES ($1, $2, $3, $4, $5, $6)",
            [$inc_id, $room_name, $quantity, $notes, $position, $is_active],
            "Erro ao inserir categoria de quarto"
        );
    }
}

/**
 * Sincroniza dining
 */
function syncDining($conn, $inc_id, $diningList) {
    execParams(
        $conn,
        "DELETE FROM incentive.inc_dining WHERE inc_id = $1",
        [$inc_id],
        "Erro ao limpar dining"
    );

    foreach ($diningList as $index => $dining) {
        $name           = formatString($dining['name'] ?? null);
        $description    = formatString($dining['description'] ?? null);
        $cuisine        = formatString($dining['cuisine'] ?? null);
        $capacity       = formatInt($dining['capacity'] ?? null);
        $schedule       = formatString($dining['schedule'] ?? null);
        $is_michelin    = formatBoolean($dining['is_michelin'] ?? false);
        $can_be_private = formatBoolean($dining['can_be_private'] ?? false);
        $image_url      = formatString($dining['image_url'] ?? null);
        $position       = formatPosition($dining['position'] ?? 0);
        $is_active      = formatBoolean($dining['is_active'] ?? true);

        if (!$name) {
            throw new Exception("Dining {$index}: name obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_dining 
                (inc_id, name, description, cuisine, capacity, schedule, 
                 is_michelin, can_be_private, image_url, position, is_active)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)",
            [
                $inc_id,
                $name,
                $description,
                $cuisine,
                $capacity,
                $schedule,
                $is_michelin,
                $can_be_private,
                $image_url,
                $position,
                $is_active
            ],
            "Erro ao inserir dining"
        );
    }
}

/**
 * Sincroniza facilities
 */
function syncFacilities($conn, $inc_id, $facilities) {
    execParams(
        $conn,
        "DELETE FROM incentive.inc_facility WHERE inc_id = $1",
        [$inc_id],
        "Erro ao limpar facilities"
    );

    foreach ($facilities as $index => $facility) {
        $name      = formatString($facility['name'] ?? null);
        $icon      = formatString($facility['icon'] ?? null);
        $is_active = formatBoolean($facility['is_active'] ?? true);

        if (!$name) {
            throw new Exception("Facility {$index}: name obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_facility 
                (inc_id, name, icon, is_active) 
             VALUES ($1, $2, $3, $4)",
            [$inc_id, $name, $icon, $is_active],
            "Erro ao inserir facility"
        );
    }
}

/**
 * Sincroniza ou remove convention
 */
function upsertConvention($conn, $inc_id, $convention) {
    // Se convention é null ou vazio, remove
    if ($convention === null || empty($convention)) {
        execParams(
            $conn,
            "DELETE FROM incentive.inc_convention WHERE inc_id = $1",
            [$inc_id],
            "Erro ao excluir convention"
        );
        return null;
    }

    // Formata valores com tratamento extra
    $description = formatString($convention['description'] ?? null);
    $total_rooms = formatInt($convention['total_rooms'] ?? null);
    
    // Tratamento ESPECIAL para has_360
    $has_360_raw = $convention['has_360'] ?? false;
    $has_360 = formatBoolean($has_360_raw);
    
    // Debug temporário (remova após testar)
    error_log("DEBUG upsertConvention - has_360_raw: " . var_export($has_360_raw, true));
    error_log("DEBUG upsertConvention - has_360 formatado: " . var_export($has_360, true));

    // Verifica se já existe
    $res = execParams(
        $conn,
        "SELECT inc_convention_id FROM incentive.inc_convention WHERE inc_id = $1 LIMIT 1",
        [$inc_id],
        "Erro ao buscar convention"
    );

    if (pg_num_rows($res) > 0) {
        // UPDATE
        $row = pg_fetch_assoc($res);
        $conv_id = (int)$row['inc_convention_id'];
        
        execParams(
            $conn,
            "UPDATE incentive.inc_convention 
             SET description = $1, total_rooms = $2, has_360 = $3 
             WHERE inc_convention_id = $4",
            [$description, $total_rooms, $has_360, $conv_id],
            "Erro ao atualizar convention"
        );
        
        return $conv_id;
    } else {
        // INSERT
        $resInsert = execParams(
            $conn,
            "INSERT INTO incentive.inc_convention 
                (inc_id, description, total_rooms, has_360) 
             VALUES ($1, $2, $3, $4) 
             RETURNING inc_convention_id",
            [$inc_id, $description, $total_rooms, $has_360],
            "Erro ao inserir convention"
        );
        
        return (int)pg_fetch_result($resInsert, 0, 0);
    }
}

/**
 * Retorna o ID da convention (se existir)
 */
function getConventionId($conn, $inc_id) {
    $res = execParams(
        $conn,
        "SELECT inc_convention_id FROM incentive.inc_convention WHERE inc_id = $1 LIMIT 1",
        [$inc_id],
        "Erro ao buscar convention"
    );
    
    if (pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        return (int)$row['inc_convention_id'];
    }
    
    return null;
}

/**
 * Sincroniza salas de convenção
 */
function syncConventionRooms($conn, $inc_convention_id, $rooms) {
    if (!$inc_convention_id) {
        return;
    }

    execParams(
        $conn,
        "DELETE FROM incentive.inc_convention_room WHERE inc_convention_id = $1",
        [$inc_convention_id],
        "Erro ao limpar salas de convenção"
    );

    foreach ($rooms as $index => $room) {
    $name                = formatString($room['name'] ?? null);
        $area_m2             = formatNumeric($room['area_m2'] ?? null);
        $capacity_auditorium = formatInt($room['capacity_auditorium'] ?? null);
        $capacity_banquet    = formatInt($room['capacity_banquet'] ?? null);
        $capacity_classroom  = formatInt($room['capacity_classroom'] ?? null);
        $capacity_u_shape    = formatInt($room['capacity_u_shape'] ?? null);
        $notes               = formatString($room['notes'] ?? null);

        if (!$name) {
            throw new Exception("Sala de convenção {$index}: name obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_convention_room
                (inc_convention_id, name, area_m2, capacity_auditorium, 
                 capacity_banquet, capacity_classroom, capacity_u_shape, notes)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8)",
            [
                $inc_convention_id,
                $name,
                $area_m2,
                $capacity_auditorium,
                $capacity_banquet,
                $capacity_classroom,
                $capacity_u_shape,
                $notes
            ],
            "Erro ao inserir sala de convenção"
        );
    }
}

/**
 * Sincroniza notas
 */
function syncNotes($conn, $inc_id, $notes) {
    execParams(
        $conn,
        "DELETE FROM incentive.inc_note WHERE inc_id = $1",
        [$inc_id],
        "Erro ao limpar notas"
    );

    foreach ($notes as $index => $note) {
        $language = formatLanguage($note['language'] ?? null);
        $text     = formatString($note['note'] ?? null);

        if (!$text) {
            throw new Exception("Nota {$index}: note obrigatório");
        }

        execParams(
            $conn,
            "INSERT INTO incentive.inc_note 
                (inc_id, language, note) 
             VALUES ($1, $2, $3)",
            [$inc_id, $language, $text],
            "Erro ao inserir nota"
        );
    }
}

// =========================================================
// PROCESSAMENTO DA REQUEST
// =========================================================
