<?php
/**
 * API RESTful COMPLETA - conteudo_internet.blog_nacional (CRUD)
 * Compatível com PHP 7.2+
 *
 * Descrição:
 * API completa para gerenciamento de posts do Blog Nacional da Blumar.
 * Suporta listagem com filtros avançados, busca por ID, criação, atualização parcial e exclusão.
 * Fotos são salvas apenas como nome do arquivo (ex: "praias-nordeste-capa.jpg").
 * Upload pode ser feito enviando o nome do arquivo (backend não processa base64 por simplicidade em 7.2).
 *
 * Endpoints:
 *   GET    ?request=listar_posts               → Lista com filtros
 *   GET    ?request=buscar_post&id=42          → Detalhes de um post
 *   GET    ?request=listar_classificacoes      → Categorias fixas
 *   GET    ?request=listar_cidades&classif=2   → Cidades com posts ativos
 *   GET    ?request=listar_regioes             → Regiões disponíveis
 *   POST   ?request=criar_post                 → Cria novo post
 *   PUT    ?request=atualizar_post&id=XXX      → Atualiza post (parcial)
 *   DELETE ?request=excluir_post&id=XXX        → Exclui post
 *
 * Autenticação (rotas POST/PUT/DELETE):
 *   Header: authorization: Bearer <token>
 *   Validação via função validarToken() em middleware.php
 *
 * Códigos HTTP:
 *   200 OK, 201 Created, 400 Bad Request, 401 Unauthorized,
 *   404 Not Found, 405 Method Not Allowed, 500 Internal Server Error
 *
 * Respostas de erro sempre: { "error": "mensagem" }
 */

/* ======================================== */
/* CONFIGURAÇÕES INICIAIS                   */
/* ======================================== */

ini_set('display_errors', 1);
ini_set('log_errors', 1);
// ini_set('error_log', '/caminho/para/php_errors.log'); // Descomente e ajuste se quiser log em arquivo

date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once '../util/connection.php';
require_once 'middleware.php'; // Deve conter validarToken() e logRequest() se usar

$BASE_URL_FOTO = "images/"; // Ajuste para caminho real ou URL completa

// Função de resposta JSON
function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Fallback para getallheaders() em ambientes CGI/FastCGI
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Helpers para GET
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

// Helpers de formatação para SQL
function formatDate($date) {
    return ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) ? $date : null;
}

function formatBoolean($val) {
    if ($val === null || $val === '') return null;
    return filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 't' : 'f';
}

function formatInt($val) {
    return (is_numeric($val)) ? (int)$val : null;
}

function formatString($val) {
    return ($val === '' || $val === null) ? null : trim($val);
}

// Monta URLs de fotos
function montarFotos(&$row) {
    global $BASE_URL_FOTO;
    $row['cover_photo'] = !empty($row['foto_capa']) ? $BASE_URL_FOTO . $row['foto_capa'] : null;
    $row['top_photo']   = !empty($row['foto_topo']) ? $BASE_URL_FOTO . $row['foto_topo'] : null;
}

// Adiciona nome legível da classificação
function formatarClassificacao(&$row) {
    $map = array(
        1 => 'Dicas de Viagem',
        2 => 'Destinos',
        3 => 'Experiências',
        4 => 'Gastronomia',
        5 => 'Cultura',
        6 => 'Luxo',
        7 => 'Família'
    );
    $row['classif_nome'] = isset($map[$row['classif']]) ? $map[$row['classif']] : 'Outros';
}

// ========================================
// FAQ helpers
// ========================================
function normalizeFaq($faqInput) {
    if (!is_array($faqInput)) return array();
    $faq = array();
    foreach ($faqInput as $item) {
        if (!is_array($item)) continue;
        $pergunta = isset($item['pergunta']) ? trim($item['pergunta']) : '';
        $resposta = isset($item['resposta']) ? trim($item['resposta']) : '';
        if ($pergunta === '' && $resposta === '') continue;
        $faq[] = array('pergunta' => $pergunta, 'resposta' => $resposta);
    }
    return $faq;
}

function saveFaqForPost($conn, $postId, $faq) {
    $deleteSql = "DELETE FROM conteudo_internet.blog_nacional_faq WHERE post_id = $1";
    $delResult = pg_query_params($conn, $deleteSql, array($postId));
    if (!$delResult) throw new Exception(pg_last_error($conn));

    if (empty($faq)) return;

    $sql = "
        INSERT INTO conteudo_internet.blog_nacional_faq (post_id, pergunta, resposta, ordem)
        VALUES ($1, $2, $3, $4)
    ";

    $ordem = 1;
    foreach ($faq as $item) {
        $params = array($postId, $item['pergunta'], $item['resposta'], $ordem++);
        $result = pg_query_params($conn, $sql, $params);
        if (!$result) throw new Exception(pg_last_error($conn));
    }
}

function fetchFaqByPostIds($conn, $postIds) {
    $faqMap = array();
    if (empty($postIds)) return $faqMap;

    $placeholders = array();
    $params = array();
    $idx = 1;
    foreach ($postIds as $postId) {
        $placeholders[] = '$' . $idx++;
        $params[] = $postId;
    }

    $sql = "
        SELECT post_id, pergunta, resposta, ordem
        FROM conteudo_internet.blog_nacional_faq
        WHERE post_id IN (" . implode(', ', $placeholders) . ")
        ORDER BY post_id, ordem ASC, id ASC
    ";
    $result = pg_query_params($conn, $sql, $params);
    if (!$result) return $faqMap;

    while ($row = pg_fetch_assoc($result)) {
        $pid = (int)$row['post_id'];
        if (!isset($faqMap[$pid])) $faqMap[$pid] = array();
        $faqMap[$pid][] = array(
            'pergunta' => $row['pergunta'],
            'resposta' => $row['resposta'],
            'ordem' => (int)$row['ordem']
        );
    }
    return $faqMap;
}

function fetchFaqByPostId($conn, $postId) {
    $sql = "
        SELECT pergunta, resposta, ordem
        FROM conteudo_internet.blog_nacional_faq
        WHERE post_id = $1
        ORDER BY ordem ASC, id ASC
    ";
    $result = pg_query_params($conn, $sql, array($postId));
    if (!$result) return array();

    $faq = array();
    while ($row = pg_fetch_assoc($result)) {
        $faq[] = array(
            'pergunta' => $row['pergunta'],
            'resposta' => $row['resposta'],
            'ordem' => (int)$row['ordem']
        );
    }
    return $faq;
}

// ========================================
// INÍCIO DO PROCESSAMENTO
// ========================================
$request = getParam('request');
if (!$request) {
    response(array("error" => "Parâmetro 'request' é obrigatório"), 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: array();

if ($method === 'OPTIONS') {
    response(array(), 204);
}

try {
    switch ($request) {
        // PROXY DE IMAGEM (para evitar CORS em edicao)
        case 'proxy_image':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);
            $url = getStringParam('url');
            if (!$url) {
                response(array("error" => "URL obrigatoria"), 400);
            }
            $parts = parse_url($url);
            $allowedHosts = array('www.blumar.com.br', 'blumar.com.br');
            if (!$parts || empty($parts['host']) || !in_array($parts['host'], $allowedHosts)) {
                response(array("error" => "Host nao permitido"), 400);
            }
            if (empty($parts['path']) || strpos($parts['path'], '/blog/') !== 0) {
                response(array("error" => "Caminho nao permitido"), 400);
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $data = curl_exec($ch);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode < 200 || $httpCode >= 300 || $data === false) {
                response(array("error" => "Falha ao buscar imagem"), 502);
            }

            header("Access-Control-Allow-Origin: *");
            header("Content-Type: " . ($contentType ?: "image/jpeg"));
            header("Cache-Control: public, max-age=3600");
            echo $data;
            exit;

        // LISTAR POSTS
        case 'listar_posts':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);

    $filtro_titulo   = getStringParam('filtro_titulo');
    $filtro_ativo    = getParam('filtro_ativo', 'all');
    $filtro_classif  = getIntParam('filtro_classif');
    $filtro_citie    = getIntParam('filtro_citie');
    $filtro_regiao   = getIntParam('filtro_regiao');

    // ---------- PAGINAÇÃO ----------
    $page     = max(1, (int)getIntParam('page', 1));
    $per_page = max(1, min(100, (int)getIntParam('per_page', 30)));
    $offset   = ($page - 1) * $per_page;

    $where  = array();
    $params = array();
    $idx    = 1;

    if ($filtro_titulo) {
        $where[] = "titulo ILIKE $" . $idx++;
        $params[] = "%" . $filtro_titulo . "%";
    }
    if ($filtro_classif !== null) {
        $where[] = "classif = $" . $idx++;
        $params[] = $filtro_classif;
    }
    if ($filtro_citie !== null) {
        $where[] = "citie = $" . $idx++;
        $params[] = $filtro_citie;
    }
    if ($filtro_regiao !== null) {
        $where[] = "regiao = $" . $idx++;
        $params[] = $filtro_regiao;
    }
    if ($filtro_ativo !== 'all') {
        $ativo = ($filtro_ativo === 'true' || $filtro_ativo === '1') ? 't' : 'f';
        $where[] = "ativo = $" . $idx++;
        $params[] = $ativo;
    }

    $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

    // ---------- TOTAL ----------
    $sql_count = "
        SELECT COUNT(*) AS total
        FROM conteudo_internet.blog_nacional
        {$where_sql}
    ";

    $result_count = pg_query_params($conn, $sql_count, $params);
    if (!$result_count) throw new Exception(pg_last_error($conn));

    $total = (int) pg_fetch_result($result_count, 0, 'total');

    // ---------- LISTAGEM ----------
    $params[] = $per_page;
    $params[] = $offset;

    $sql = "
        SELECT pk_blognacional, titulo, descritivo_blumar, descritivo_be,
               data_post, foto_capa, foto_topo, url_video, meta_description,
               classif, citie, regiao, ativo
        FROM conteudo_internet.blog_nacional
        {$where_sql}
        ORDER BY data_post DESC, pk_blognacional DESC
        LIMIT $" . $idx++ . "
        OFFSET $" . $idx++ . "
    ";

    $result = pg_query_params($conn, $sql, $params);
    if (!$result) throw new Exception(pg_last_error($conn));

    $posts = array();
    $postIds = array();
    while ($row = pg_fetch_assoc($result)) {
        $row['id']               = (int)$row['pk_blognacional'];
        $row['title']            = $row['titulo'];
        $row['description']      = $row['descritivo_blumar'];
        $row['description_be']   = $row['descritivo_be'];
        $row['post_date']        = $row['data_post'];
        $row['video_url']        = $row['url_video'];
        $row['meta_description'] = $row['meta_description'];
        $row['classification']   = (int)$row['classif'];
        $row['city_code']        = $row['citie'] ? (int)$row['citie'] : null;
        $row['region_id']        = $row['regiao'] ? (int)$row['regiao'] : null;
        $row['is_active']        = ($row['ativo'] === 't');

        montarFotos($row);
        formatarClassificacao($row);

        unset(
            $row['pk_blognacional'], $row['titulo'], $row['descritivo_blumar'],
            $row['descritivo_be'], $row['foto_capa'], $row['foto_topo'],
            $row['url_video'], $row['meta_description'], $row['classif'],
            $row['citie'], $row['regiao'], $row['ativo']
        );

        $posts[] = $row;
        $postIds[] = $row['id'];
    }

    $faqMap = fetchFaqByPostIds($conn, $postIds);
    foreach ($posts as &$post) {
        $pid = $post['id'];
        $post['faq'] = isset($faqMap[$pid]) ? $faqMap[$pid] : array();
    }
    unset($post);

    response(array(
        'data' => $posts,
        'pagination' => array(
            'total'       => $total,
            'per_page'    => $per_page,
            'current_page'=> $page,
            'last_page'   => (int) ceil($total / $per_page)
        )
    ));
    break;


        // BUSCAR POST POR ID
        case 'buscar_post':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);

            $id = getIntParam('id');
            if (!$id) response(array("error" => "ID obrigatório"), 400);

            $sql = "SELECT * FROM conteudo_internet.blog_nacional WHERE pk_blognacional = $1 LIMIT 1";
            $result = pg_query_params($conn, $sql, array($id));
            if (!$result || pg_num_rows($result) == 0) {
                response(array("error" => "Post não encontrado"), 404);
            }

            $row = pg_fetch_assoc($result);

            $post = array(
                'id'               => (int)$row['pk_blognacional'],
                'title'            => $row['titulo'],
                'description'      => $row['descritivo_blumar'],
                'description_be'   => $row['descritivo_be'],
                'post_date'        => $row['data_post'],
                'cover_photo'      => $row['foto_capa'] ? $BASE_URL_FOTO . $row['foto_capa'] : null,
                'top_photo'        => $row['foto_topo'] ? $BASE_URL_FOTO . $row['foto_topo'] : null,
                'video_url'        => $row['url_video'],
                'meta_description' => $row['meta_description'],
                'classification'   => (int)$row['classif'],
                'city_code'        => $row['citie'] ? (int)$row['citie'] : null,
                'region_id'        => $row['regiao'] ? (int)$row['regiao'] : null,
                'is_active'        => ($row['ativo'] === 't')
            );

            formatarClassificacao($post);
            montarFotos($post);

            $post['faq'] = fetchFaqByPostId($conn, $post['id']);

            response($post);
            break;

        // CRIAR POST
        case 'criar_post':
            if ($method !== 'POST') response(array("error" => "Use POST"), 405);

            $headers = getallheaders();
            $auth_header = isset($headers['authorization']) ? $headers['authorization'] : '';
            if (strpos($auth_header, 'Bearer ') !== 0) {
                response(array("error" => "Token Bearer obrigatóriosss"), 401);
            }
            $token = trim(substr($auth_header, 7));

            $cod_sis = null;
            $user_data = null;
            if (!validarToken($conn, $cod_sis, $token, $user_data)) {
                response(array("error" => "Token inválido ou expirado"), 401);
            }

            if (empty($input)) response(array("error" => "Body JSON obrigatório"), 400);

            pg_query($conn, "BEGIN");

            try {
                $campos = array(
                    'titulo'             => formatString(isset($input['titulo']) ? $input['titulo'] : ''),
                    'descritivo_blumar'  => formatString(isset($input['descritivo_blumar']) ? $input['descritivo_blumar'] : $input['description']),
                    'descritivo_be'      => formatString(isset($input['descritivo_be']) ? $input['descritivo_be'] : ''),
                    'data_post'          => formatDate(isset($input['data_post']) ? $input['data_post'] : date('Y-m-d')),
                    'foto_capa'          => formatString(isset($input['foto_capa']) ? $input['foto_capa'] : ''),
                    'foto_topo'          => formatString(isset($input['foto_topo']) ? $input['foto_topo'] : ''),
                    'classif'            => formatInt(isset($input['classif']) ? $input['classif'] : null),
                    'citie'              => formatInt(isset($input['citie']) ? $input['citie'] : null),
                    'regiao'             => formatInt(isset($input['regiao']) ? $input['regiao'] : null),
                    'url_video'          => formatString(isset($input['url_video']) ? $input['url_video'] : ''),
                    'meta_description'   => formatString(isset($input['meta_description']) ? $input['meta_description'] : ''),
                    'ativo'              => formatBoolean(isset($input['ativo']) ? $input['ativo'] : true)
                );

                if (!$campos['titulo'] || !$campos['data_post'] || !$campos['classif']) {
                    throw new Exception("Título, data_post e classif são obrigatórios");
                }

                $cols = array();
                $placeholders = array();
                $values = array();
                $idx = 1;

                foreach ($campos as $col => $val) {
                    if ($val !== null) {
                        $cols[] = $col;
                        $placeholders[] = '$' . $idx;
                        $values[] = $val;
                        $idx++;
                    }
                }

                $sql = "INSERT INTO conteudo_internet.blog_nacional (" . implode(', ', $cols) . ") 
                        VALUES (" . implode(', ', $placeholders) . ") 
                        RETURNING pk_blognacional";

                $result = pg_query_params($conn, $sql, $values);
                if (!$result) throw new Exception(pg_last_error($conn));

                $id = pg_fetch_result($result, 0, 0);

                $faq = normalizeFaq(isset($input['faq']) ? $input['faq'] : array());
                saveFaqForPost($conn, $id, $faq);

                pg_query($conn, "COMMIT");

                response(array(
                    "success" => true,
                    "message" => "Post criado com sucesso!",
                    "id" => (int)$id
                ), 201);

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(array("error" => $e->getMessage()), 400);
            }
            break;

        // ATUALIZAR POST
        case 'atualizar_post':
            if ($method !== 'PUT') response(array("error" => "Use PUT"), 405);

            $id = getIntParam('id');
            if (!$id) response(array("error" => "ID obrigatório"), 400);

            $headers = getallheaders();
            $auth_header = isset($headers['authorization']) ? $headers['authorization'] : '';
            if (strpos($auth_header, 'Bearer ') !== 0) {
                response(array("error" => "Token Bearer obrigatório"), 401);
            }
            $token = trim(substr($auth_header, 7));

            $cod_sis = null;
            $user_data = null;
            if (!validarToken($conn, $cod_sis, $token, $user_data)) {
                response(array("error" => "Token inválido"), 401);
            }

            if (empty($input)) response(array("error" => "Nenhum campo para atualizar"), 400);

            pg_query($conn, "BEGIN");

            try {
                $updates = array();
                $params = array();
                $idx = 1;

                $allowed = array('titulo', 'descritivo_blumar', 'descritivo_be', 'data_post', 'foto_capa', 'foto_topo',
                                 'classif', 'citie', 'regiao', 'url_video', 'meta_description', 'ativo');

                foreach ($input as $key => $val) {
                    if (in_array($key, $allowed)) {
                        $formatted = null;
                        switch ($key) {
                            case 'data_post':   $formatted = formatDate($val); break;
                            case 'classif':
                            case 'citie':
                            case 'regiao':      $formatted = formatInt($val); break;
                            case 'ativo':       $formatted = formatBoolean($val); break;
                            default:            $formatted = formatString($val);
                        }
                        if ($formatted !== null) {
                            $updates[] = $key . " = $" . $idx;
                            $params[] = $formatted;
                            $idx++;
                        }
                    }
                }

                if (empty($updates)) {
                    pg_query($conn, "ROLLBACK");
                    response(array("success" => false, "message" => "Nenhuma alteração válida"), 200);
                }

                $params[] = $id;
                $sql = "UPDATE conteudo_internet.blog_nacional SET " . implode(', ', $updates) . " 
                        WHERE pk_blognacional = $" . $idx;

                $result = pg_query_params($conn, $sql, $params);
                if (!$result || pg_affected_rows($result) == 0) {
                    throw new Exception("Post não encontrado ou sem alterações");
                }

                if (array_key_exists('faq', $input)) {
                    $faq = normalizeFaq($input['faq']);
                    saveFaqForPost($conn, $id, $faq);
                }

                pg_query($conn, "COMMIT");

                response(array(
                    "success" => true,
                    "message" => "Post atualizado com sucesso!",
                    "id" => $id
                ));

            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                response(array("error" => $e->getMessage()), 400);
            }
            break;

        // EXCLUIR POST
        case 'excluir_post':
            if ($method !== 'DELETE') response(array("error" => "Use DELETE"), 405);

            $id = getIntParam('id');
            if (!$id) response(array("error" => "ID obrigatório"), 400);

            $headers = getallheaders();
            $auth_header = isset($headers['authorization']) ? $headers['authorization'] : '';
            if (strpos($auth_header, 'Bearer ') !== 0) {
                response(array("error" => "Token obrigatório"), 401);
            }
            $token = trim(substr($auth_header, 7));

            $cod_sis = null;
            $user_data = null;
            if (!validarToken($conn, $cod_sis, $token, $user_data)) {
                response(array("error" => "Token inválido"), 401);
            }

            $sqlFaq = "DELETE FROM conteudo_internet.blog_nacional_faq WHERE post_id = $1";
            $resultFaq = pg_query_params($conn, $sqlFaq, array($id));
            if (!$resultFaq) {
                response(array("error" => "Erro ao excluir FAQ"), 500);
            }

            $sql = "DELETE FROM conteudo_internet.blog_nacional WHERE pk_blognacional = $1";
            $result = pg_query_params($conn, $sql, array($id));

            if (!$result || pg_affected_rows($result) == 0) {
                response(array("error" => "Post não encontrado"), 404);
            }

            response(array("success" => true, "message" => "Post excluído com sucesso"));
            break;

        // AUXILIARES
        case 'listar_classificacoes':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);
            $classifs = array(
                array('value' => 1, 'label' => 'Dicas de Viagem'),
                array('value' => 2, 'label' => 'Destinos'),
                array('value' => 3, 'label' => 'Experiências'),
                array('value' => 4, 'label' => 'Gastronomia'),
                array('value' => 5, 'label' => 'Cultura'),
                array('value' => 6, 'label' => 'Luxo'),
                array('value' => 7, 'label' => 'Família')
            );
            response($classifs);
            break;

        case 'listar_regioes':
            if ($method !== 'GET') response(array("error" => "Use GET"), 405);

            $classif = getIntParam('classif');
            $where = "WHERE ativo = 't'";
            $params = array();
            $idx = 1;

            if ($classif) {
                $where .= " AND classif = $" . $idx++;
                $params[] = $classif;
            }

            $sql = "SELECT DISTINCT regiao FROM conteudo_internet.blog_nacional $where ORDER BY regiao";
            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                response(array("error" => "Erro ao buscar regioes"), 500);
            }

            $regioes_map = array(
                '1' => 'Norte',
                '2' => 'Nordeste',
                '3' => 'Sudeste',
                '4' => 'Centro-Oeste',
                '5' => 'Sul'
            );

            $regioes = array();
            while ($row = pg_fetch_assoc($result)) {
                $reg = $row['regiao'];
                if (isset($regioes_map[$reg])) {
                    $regioes[] = array(
                        'codigo' => (int)$reg,
                        'nome'   => $regioes_map[$reg]
                    );
                }
            }

            response($regioes);
            break;

        default:
            response(array("error" => "Rota inválida"), 400);
    }
} catch (Exception $e) {
    if (isset($conn)) @pg_query($conn, "ROLLBACK");
    error_log("Erro API Blog Nacional: " . $e->getMessage());
    response(array("error" => "Erro interno no servidor"), 500);
}
