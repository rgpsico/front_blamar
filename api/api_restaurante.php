<?php
/**
 * API RESTful COMPLETA - conteudo_internet.restaurante (CRUD)
 * Inspirada na API de blog_nacional
 * Compatível com PHP 7.2+
 *
 * Endpoints principais:
 *   GET    ?request=listar_restaurantes             → Lista com filtros
 *   GET    ?request=buscar_restaurante&id=123       → Detalhes de um restaurante
 *   POST   ?request=criar_restaurante               → Cria novo restaurante
 *   PUT    ?request=atualizar_restaurante&id=XXX    → Atualiza (parcial)
 *   DELETE ?request=excluir_restaurante&id=XXX      → Exclui
 *
 * Endpoints auxiliares:
 *   GET    ?request=listar_cidades
 *   GET    ?request=listar_classificacoes           (1 a 5 estrelas)
 *
 * Autenticação (POST/PUT/DELETE):
 *   Header: Authorization: Bearer <token>
 *
 * Fotos: salva apenas o nome do arquivo (ex: "rest-123-fachada.jpg")
 */

ini_set('display_errors', 1);
ini_set('log_errors', 1);

date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once '../util/connection.php';
require_once 'middleware.php'; // Contém validarToken()

$BASE_URL_FOTO = "images/restaurantes/"; // Ajuste conforme sua estrutura

// ========================================
// Funções auxiliares
// ========================================

function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getParam($name, $default = null) {
    return isset($_GET[$name]) ? $_GET[$name] : $default;
}

function getStringParam($name, $default = null) {
    $v = getParam($name, $default);
    return ($v !== null) ? trim($v) : null;
}

function getIntParam($name, $default = null) {
    $v = getParam($name, $default);
    return (is_numeric($v)) ? (int)$v : $default;
}

function formatBoolean($val) {
    if ($val === null || $val === '') return null;
    return filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 't' : 'f';
}

function formatInt($val) {
    return is_numeric($val) ? (int)$val : null;
}

function formatString($val) {
    return ($val === '' || $val === null) ? null : trim($val);
}

function formatClassif($val) {
    $v = formatInt($val);
    return ($v >= 1 && $v <= 5) ? $v : null;
}

function montarFotos(&$row) {
    global $BASE_URL_FOTO;
    $row['foto1_url'] = $row['foto1'] ? $BASE_URL_FOTO . $row['foto1'] : null;
    $row['foto2_url'] = $row['foto2'] ? $BASE_URL_FOTO . $row['foto2'] : null;
}

// ========================================
// INÍCIO DO PROCESSAMENTO
// ========================================

$request = getParam('request');
if (!$request) {
    response(["error" => "Parâmetro 'request' é obrigatório"], 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?: [];

if ($method === 'OPTIONS') {
    response([], 204);
}

try {
    switch ($request) {

        // LISTAR RESTAURANTES (com filtros)
        case 'listar_restaurantes':
    if ($method !== 'GET') {
        response(["error" => "Use GET"], 405);
    }

    // Filtros
    $filtro_nome       = getStringParam('filtro_nome');
    $filtro_cidade     = getIntParam('filtro_cidade');
    $filtro_especial   = getStringParam('filtro_especialidade');
    $filtro_classif    = getIntParam('filtro_classif');
    $filtro_ativo      = getParam('filtro_ativo', 'all');
    $filtro_favorito   = getParam('filtro_favorito'); // 'riolife' ou 'geral'

    // Paginação
    $page     = max(1, getIntParam('page', 1));
    $per_page = max(1, min(100, getIntParam('per_page', 30)));
    $offset   = ($page - 1) * $per_page;

    $where  = [];
    $params = [];
    $idx    = 1;

    if ($filtro_nome) {
        $where[] = "nome ILIKE $" . $idx++;
        $params[] = "%$filtro_nome%";
    }
    if ($filtro_cidade) {
        $where[] = "fk_cod_cidade = $" . $idx++;
        $params[] = $filtro_cidade;
    }
    if ($filtro_especial) {
        $where[] = "especialidade ILIKE $" . $idx++;
        $params[] = "%$filtro_especial%";
    }
    if ($filtro_classif) {
        $where[] = "classif = $" . $idx++;
        $params[] = $filtro_classif;
    }
    if ($filtro_ativo !== 'all') {
        $ativo = ($filtro_ativo === 'true' || $filtro_ativo === '1' || $filtro_ativo === true) ? 't' : 'f';
        $where[] = "ativo = $" . $idx++;
        $params[] = $ativo;
    }
    if ($filtro_favorito === 'riolife') {
        $where[] = "fav_riolife = 't'";
    } elseif ($filtro_favorito === 'geral') {
        $where[] = "selo_fav = 't'";
    }

    $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Contagem total
    $sql_count = "SELECT COUNT(*) AS total FROM conteudo_internet.restaurante $where_sql";
    $res_count = pg_query_params($conn, $sql_count, $params);
    if (!$res_count) {
        throw new Exception(pg_last_error($conn));
    }
    $total = (int) pg_fetch_result($res_count, 0, 'total');

    // Lista paginada
    $params[] = $per_page;
    $params[] = $offset;

    $sql = "
        SELECT *
        FROM conteudo_internet.restaurante
        $where_sql
        ORDER BY nome ASC
        LIMIT $" . ($idx++) . "
        OFFSET $" . ($idx++)
    ;

    $result = pg_query_params($conn, $sql, $params);
    if (!$result) {
        throw new Exception(pg_last_error($conn));
    }

    $restaurantes = [];

    while ($row = pg_fetch_assoc($result)) {
        $r = [
            'id'                => (int)($row['cod_rest'] ?? 0),
            'nome'              => $row['nome'] ?? '',
            'especialidade'     => $row['especialidade'] ?? '',
            'classif'           => (int)($row['classif'] ?? 0),
            'classif_estrelas'  => str_repeat('★', (int)($row['classif'] ?? 0)),
            'cidade_cod'        => (int)($row['fk_cod_cidade'] ?? 0),
            'endereco'          => $row['address'] ?? '',
            'telefone'          => $row['tel'] ?? '',
            'mneu_for'          => $row['mneu_for'] ?? '',
            'cod_serv'          => $row['cod_serv'] ?? '',
            'ativo'             => ($row['ativo'] ?? 'f') === 't',
            'ativo_riolife'     => ($row['ativo_riolife'] ?? 'f') === 't',
            'favorito_riolife'  => ($row['fav_riolife'] ?? 'f') === 't',
            'favorito_geral'    => ($row['selo_fav'] ?? 'f') === 't',
            'instagram'         => $row['url_insta'] ?? '',
            'foto1'             => $row['foto1'] ?? '',
            'foto2'             => $row['foto2'] ?? '',
        ];

        montarFotos($r);

        // Selos – mapeamento correto dos nomes das colunas
        $selos_map = [
            'fav'         => 'selo_fav',
            'wview'       => 'wview',           // ← coluna real é 'wview', NÃO 'selo_wview'
            'boteco'      => 'selo_boteco',
            'budget'      => 'selo_budget',
            'highend'     => 'selo_highend',
            'livemusic'   => 'selo_livemusic',
            'romantic'    => 'selo_romantic',
            'selfservice' => 'selo_selfservice',
            'trendy'      => 'selo_trendy',
            'veggie'      => 'selo_veggie',
            'michelin'    => 'selo_michelin',
        ];

        $selos = [];
        foreach ($selos_map as $chave => $coluna) {
            $selos[$chave] = ($row[$coluna] ?? 'f') === 't';
        }

        $r['selos'] = $selos;

        $restaurantes[] = $r;
    }

    response([
        'data' => $restaurantes,
        'pagination' => [
            'total'        => $total,
            'per_page'     => $per_page,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $per_page)
        ]
    ]);
    break;

        // BUSCAR UM RESTAURANTE
       case 'buscar_restaurante':
    if ($method !== 'GET') {
        response(["error" => "Use GET"], 405);
    }

    $id = getIntParam('id');
    if (!$id) {
        response(["error" => "ID obrigatório"], 400);
    }

    $sql = "SELECT * FROM conteudo_internet.restaurante WHERE cod_rest = $1 LIMIT 1";
    $result = pg_query_params($conn, $sql, [$id]);

    if (!$result || pg_num_rows($result) == 0) {
        response(["error" => "Restaurante não encontrado"], 404);
    }

    $row = pg_fetch_assoc($result);

    $rest = [
        'id'                => (int)($row['cod_rest'] ?? 0),
        'nome'              => $row['nome'] ?? '',
        'especialidade'     => $row['especialidade'] ?? '',
        'descritivo'        => $row['descritivo'] ?? '',
        'descritivo_pt'     => $row['descritivo_pt'] ?? '',
        'descritivo_esp'    => $row['descritivo_esp'] ?? '',
        'classif'           => (int)($row['classif'] ?? 0),
        'cidade_cod'        => (int)($row['fk_cod_cidade'] ?? 0),
        'endereco'          => $row['address'] ?? '',
        'telefone'          => $row['tel'] ?? '',
        'instagram'         => $row['url_insta'] ?? '',
        'foto1'             => $row['foto1'] ?? '',
        'foto2'             => $row['foto2'] ?? '',
        'mneu_for'          => $row['mneu_for'] ?? '',
        'cod_serv'          => $row['cod_serv'] ?? '',
        'ativo'             => ($row['ativo'] ?? 'f') === 't',
        'ativo_riolife'     => ($row['ativo_riolife'] ?? 'f') === 't',
        'favorito_riolife'  => ($row['fav_riolife'] ?? 'f') === 't',
        'welkome'           => ($row['welkome'] ?? 'f') === 't',
    ];

    montarFotos($rest);

    // Selos – usando mapeamento correto (wview sem "selo_")
    $selos_map = [
        'fav'         => 'selo_fav',
        'wview'       => 'wview',           // coluna correta no banco
        'boteco'      => 'selo_boteco',
        'budget'      => 'selo_budget',
        'highend'     => 'selo_highend',
        'livemusic'   => 'selo_livemusic',
        'romantic'    => 'selo_romantic',
        'selfservice' => 'selo_selfservice',
        'trendy'      => 'selo_trendy',
        'veggie'      => 'selo_veggie',
        'michelin'    => 'selo_michelin',
    ];

    $selos = [];
    foreach ($selos_map as $chave => $coluna) {
        $selos[$chave] = ($row[$coluna] ?? 'f') === 't';
    }

    $rest['selos'] = $selos;

    response($rest);
    break;

        // CRIAR RESTAURANTE
        case 'criar_restaurante':
            if ($method !== 'POST') response(["error" => "Use POST"], 405);

            // Autenticação
            $headers = getallheaders();
            $auth = $headers['authorization'] ?? '';
            if (strpos($auth, 'Bearer ') !== 0) {
                response(["error" => "Token Bearer obrigatório"], 401);
            }
            $token = trim(substr($auth, 7));

            $cod_sis = $user_data = null;
            if (!validarToken($conn, $cod_sis, $token, $user_data)) {
                response(["error" => "Token inválido ou expirado"], 401);
            }

            if (empty($input)) response(["error" => "Body JSON obrigatório"], 400);

            pg_query($conn, "BEGIN");

            try {
                $campos = [
                    'nome'             => formatString($input['nome'] ?? ''),
                    'especialidade'    => formatString($input['especialidade'] ?? ''),
                    'descritivo'       => formatString($input['descritivo'] ?? ''),
                    'descritivo_pt'    => formatString($input['descritivo_pt'] ?? ''),
                    'descritivo_esp'   => formatString($input['descritivo_esp'] ?? ''),
                    'classif'          => formatClassif($input['classif'] ?? null),
                    'fk_cod_cidade'    => formatInt($input['cidade_cod'] ?? $input['fk_cod_cidade'] ?? null),
                    'address'          => formatString($input['address'] ?? $input['endereco'] ?? ''),
                    'tel'              => formatString($input['tel'] ?? $input['telefone'] ?? ''),
                    'url_insta'        => formatString($input['url_insta'] ?? $input['instagram'] ?? ''),
                    'foto1'            => formatString($input['foto1'] ?? ''),
                    'foto2'            => formatString($input['foto2'] ?? ''),
                    'mneu_for'         => formatString($input['mneu_for'] ?? ''),
                    'cod_serv'         => formatString($input['cod_serv'] ?? ''),
                    'ativo'            => formatBoolean($input['ativo'] ?? true),
                    'ativo_riolife'    => formatBoolean($input['ativo_riolife'] ?? false),
                    'fav_riolife'      => formatBoolean($input['fav_riolife'] ?? false),
                    'welkome'          => formatBoolean($input['welkome'] ?? false),
                    'selo_fav'         => formatBoolean($input['selos']['fav'] ?? false),
                    'wview'            => formatBoolean($input['selos']['wview'] ?? false),
                    'selo_boteco'      => formatBoolean($input['selos']['boteco'] ?? false),
                    'selo_budget'      => formatBoolean($input['selos']['budget'] ?? false),
                    'selo_highend'     => formatBoolean($input['selos']['highend'] ?? false),
                    'selo_livemusic'   => formatBoolean($input['selos']['livemusic'] ?? false),
                    'selo_romantic'    => formatBoolean($input['selos']['romantic'] ?? false),
                    'selo_selfservice' => formatBoolean($input['selos']['selfservice'] ?? false),
                    'selo_trendy'      => formatBoolean($input['selos']['trendy'] ?? false),
                    'selo_veggie'      => formatBoolean($input['selos']['veggie'] ?? false),
                    'selo_michelin'    => formatBoolean($input['selos']['michelin'] ?? false),
                ];

                if (!$campos['nome'] || !$campos['classif'] || !$campos['fk_cod_cidade']) {
                    throw new Exception("Nome, classificação e cidade são obrigatórios");
                }

                $cols = $placeholders = $values = [];
                $idx = 1;
                foreach ($campos as $col => $val) {
                    if ($val !== null) {
                        $cols[] = $col;
                        $placeholders[] = '$' . $idx++;
                        $values[] = $val;
                    }
                }

                $sql = "INSERT INTO conteudo_internet.restaurante (" . implode(', ', $cols) . ")
                        VALUES (" . implode(', ', $placeholders) . ")
                        RETURNING cod_rest";

                $res = pg_query_params($conn, $sql, $values);
                if (!$res) throw new Exception(pg_last_error($conn));

                $id = (int) pg_fetch_result($res, 0, 0);

                pg_query($conn, "COMMIT");

                response([
                    "success" => true,
                    "message" => "Restaurante criado com sucesso",
                    "id" => $id
                ], 201);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 400);
            }
            break;


        // ATUALIZAR RESTAURANTE (parcial)
        case 'atualizar_restaurante':
            if ($method !== 'PUT') response(["error" => "Use PUT"], 405);

            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatório"], 400);

            // Autenticação
            $headers = getallheaders();
            $auth = $headers['authorization'] ?? '';
            if (strpos($auth, 'Bearer ') !== 0) response(["error" => "Token obrigatório"], 401);
            $token = trim(substr($auth, 7));

            if (!validarToken($conn, $cod_sis, $token, $user_data)) {
                response(["error" => "Token inválido"], 401);
            }

            if (empty($input)) response(["error" => "Nenhum dado para atualizar"], 400);

            pg_query($conn, "BEGIN");

            try {
                $updates = $params = [];
                $idx = 1;

                $allowed = [
                    'nome','especialidade','descritivo','descritivo_pt','descritivo_esp',
                    'classif','fk_cod_cidade','address','tel','url_insta','foto1','foto2',
                    'mneu_for','cod_serv','ativo','ativo_riolife','fav_riolife','welkome'
                ];

                foreach ($input as $key => $val) {
                    if (in_array($key, $allowed)) {
                        $formatted = null;
                        switch ($key) {
                            case 'classif':       $formatted = formatClassif($val); break;
                            case 'fk_cod_cidade': $formatted = formatInt($val); break;
                            case 'ativo':
                            case 'ativo_riolife':
                            case 'fav_riolife':
                            case 'welkome':       $formatted = formatBoolean($val); break;
                            default:              $formatted = formatString($val);
                        }
                        if ($formatted !== null) {
                            $updates[] = "$key = \$$idx";
                            $params[] = $formatted;
                            $idx++;
                        }
                    }
                }

                // Tratamento especial para selos (vem dentro de "selos")
                if (isset($input['selos']) && is_array($input['selos'])) {
                    $selos_map = [
                        'fav'         => 'selo_fav',
                        'wview'       => 'wview',
                        'boteco'      => 'selo_boteco',
                        'budget'      => 'selo_budget',
                        'highend'     => 'selo_highend',
                        'livemusic'   => 'selo_livemusic',
                        'romantic'    => 'selo_romantic',
                        'selfservice' => 'selo_selfservice',
                        'trendy'      => 'selo_trendy',
                        'veggie'      => 'selo_veggie',
                        'michelin'    => 'selo_michelin',
                    ];
                    foreach ($selos_map as $inputKey => $dbField) {
                        if (array_key_exists($inputKey, $input['selos'])) {
                            $val = formatBoolean($input['selos'][$inputKey]);
                            if ($val !== null) {
                                $updates[] = "$dbField = \$$idx";
                                $params[] = $val;
                                $idx++;
                            }
                        }
                    }
                }

                if (empty($updates)) {
                    pg_query($conn, "ROLLBACK");
                    response(["success" => false, "message" => "Nenhuma alteração válida"], 200);
                }

                $params[] = $id;
                $sql = "UPDATE conteudo_internet.restaurante 
                        SET " . implode(', ', $updates) . " 
                        WHERE cod_rest = \$$idx";

                $res = pg_query_params($conn, $sql, $params);
                if (!$res || pg_affected_rows($res) == 0) {
                    throw new Exception("Restaurante não encontrado ou sem alterações");
                }

                pg_query($conn, "COMMIT");

                response([
                    "success" => true,
                    "message" => "Restaurante atualizado",
                    "id" => $id
                ]);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(["error" => $e->getMessage()], 400);
            }
            break;


        // EXCLUIR RESTAURANTE
        case 'excluir_restaurante':
            if ($method !== 'DELETE') response(["error" => "Use DELETE"], 405);

            $id = getIntParam('id');
            if (!$id) response(["error" => "ID obrigatório"], 400);

            // Autenticação
            $headers = getallheaders();
            $auth = $headers['authorization'] ?? '';
            if (strpos($auth, 'Bearer ') !== 0) response(["error" => "Token obrigatório"], 401);
            $token = trim(substr($auth, 7));

            if (!validarToken($conn, $cod_sis, $token, $user_data)) {
                response(["error" => "Token inválido"], 401);
            }

            $sql = "DELETE FROM conteudo_internet.restaurante WHERE cod_rest = $1";
            $res = pg_query_params($conn, $sql, [$id]);

            if (!$res || pg_affected_rows($res) == 0) {
                response(["error" => "Restaurante não encontrado"], 404);
            }

            response(["success" => true, "message" => "Restaurante excluído"]);
            break;


        // LISTAR CIDADES (auxiliar)
        case 'listar_cidades':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $sql = "
                SELECT cidade_cod, nome_pt, nome_en
                FROM tarifario.cidade_tpo
                ORDER BY nome_pt ASC
            ";
            $result = pg_query($conn, $sql);
            if (!$result) response(["error" => "Erro ao listar cidades"], 500);

            $cidades = [];
            while ($row = pg_fetch_assoc($result)) {
                $cidades[] = [
                    'value' => (int)$row['cidade_cod'],
                    'label_pt' => $row['nome_pt'],
                    'label_en' => $row['nome_en']
                ];
            }

            response($cidades);
            break;


        // LISTAR CLASSIFICAÇÕES (1–5)
        case 'listar_classificacoes':
            if ($method !== 'GET') response(["error" => "Use GET"], 405);

            $classifs = [];
            for ($i = 1; $i <= 5; $i++) {
                $classifs[] = [
                    'value' => $i,
                    'label' => "$i " . str_repeat('★', $i)
                ];
            }
            response($classifs);
            break;


        default:
            response(["error" => "Rota inválida"], 400);
    }
} catch (Exception $e) {
    if (isset($conn)) @pg_query($conn, "ROLLBACK");
    error_log("Erro API Restaurantes: " . $e->getMessage());
    response(["error" => "Erro interno no servidor"], 500);
}