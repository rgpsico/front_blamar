<?php

/**
 * API para gerenciamento de Newsletters (conteudo_internet.news)
 *
 * Descrição:
 * Controla a listagem, criação, atualização e exclusão de newsletters,
 * incluindo informações sobre nome, data, título, imagens, blocos de conteúdo,
 * status de ativação (web/home/passion/be), e seus destaques (news_conteudo).
 *
 * Endpoints:
 * - GET  ?request=listar_news&filtro_nome=Newsletter&filtro_ativo=true&filtro_empresa=1&limit=100
 *         → Lista todas as newsletters filtrando por nome, status e empresa.
 *
 * - GET  ?request=buscar_news&id=123
 *         → Busca os detalhes completos de uma newsletter específica, incluindo destaques.
 *
 * - POST ?request=criar_news
 *         → Cria uma nova newsletter.
 *           Body JSON:
 *           {
 *              "nome": "Newsletter Dezembro 2024",
 *              "data_extenso": "Dezembro de 2024",
 *              "titulo": "Ofertas Especiais",
 *              "img_topo": "img/topo.jpg",
 *              "alt_topo": "Banner Topo",
 *              "bloco_livre": "<p>Conteúdo HTML</p>",
 *              "foto_bloco": "img/bloco.jpg",
 *              "alt_livre": "Imagem Bloco",
 *              "chamada1_bloco": "Primeira Chamada",
 *              "chamada_bloco": "Segunda Chamada",
 *              "pdf": "docs/newsletter.pdf",
 *              "more_poducts": "Mais produtos aqui",
 *              "empresa": "1",
 *              "ativo_web": true,
 *              "ativo_home": true,
 *              "titulo_ativo": true,
 *              "recep": false,
 *              "novo_layout": true,
 *              "ativo_passion": false,
 *              "ativo_be": true,
 *              "destaques": [
 *                  {
 *                      "dia_conteudo": 1,
 *                      "titulo_news": "Destaque 1",
 *                      "subtitulo": "Subtítulo",
 *                      "descritivo_conteudo": "Descrição do destaque",
 *                      "img1_conteudo": "img/destaque1.jpg",
 *                      "img_reduz": "img/destaque1_thumb.jpg",
 *                      "alt": "Alt text",
 *                      "link_endereco": "http://example.com",
 *                      "link_ativo": true,
 *                      "img_link": "img/link.jpg",
 *                      "layout_news": "1",
 *                      "expert": "Nome Especialista",
 *                      "exibe_destaque": true
 *                  }
 *              ]
 *           }
 *
 * - PUT  ?request=atualizar_news&id=123
 *         → Atualiza campos específicos de uma newsletter existente, incluindo destaques.
 *           Body JSON (exemplo parcial):
 *           {
 *              "nome": "Newsletter Atualizada",
 *              "ativo_web": false,
 *              "destaques": [...] // Array para upsert (update/insert) de destaques
 *           }
 *
 * - DELETE ?request=excluir_news&id=123
 *           → Remove a newsletter e seus destaques relacionados.
 *
 * Endpoints para destaques (opcional, se não usar nested em news):
 * - POST ?request=criar_destaque&news_id=123 → Cria um novo destaque.
 * - PUT  ?request=atualizar_destaque&id=456 → Atualiza um destaque específico.
 * - DELETE ?request=excluir_destaque&id=456 → Remove um destaque.
 * - GET  ?request=listar_destaques&news_id=123 → Lista destaques de uma newsletter.
 *
 * Endpoints adicionais:
 * - GET  ?request=listar_especialistas → Lista todos os especialistas brasileiros.
 * - POST ?request=duplicar_news&id=123 → Duplica uma newsletter completa com seus destaques.
 * - POST ?request=inserir_comentario_especialista → Insere um comentário de especialista (destaque com expert)
 *
 * Métodos suportados:
 * - GET: listar_news, buscar_news, listar_destaques, listar_especialistas
 * - POST: criar_news, criar_destaque, duplicar_news, inserir_comentario_especialista
 * - PUT: atualizar_news, atualizar_destaque
 * - DELETE: excluir_news, excluir_destaque
 *
 * Tabelas relacionadas:
 * - conteudo_internet.news
 * - conteudo_internet.news_conteudo
 *
 * Retornos:
 * - 200: Sucesso
 * - 201: Criado
 * - 400: Erro de parâmetro
 * - 404: Registro não encontrado
 * - 405: Método não permitido
 * - 500: Erro interno
 */

// ========================================
// 🔧 CONFIGURAÇÕES INICIAIS
// ========================================
date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../util/connection.php';
require_once 'middleware.php';

$BASE_URL_IMAGEM = "https://www.blumar.com.br/"; // Ajuste conforme necessário

// Função padrão de resposta JSON
function response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Função auxiliar para montar array de imagens
function montarImagensNews(&$row, $baseUrl)
{
    $row['imagens'] = [];
    $fotoCampos = [
        'img_topo' => 'Imagem Topo',
        'foto_bloco' => 'Foto Bloco',
        'img_link' => 'Imagem Link'
    ];

    foreach ($fotoCampos as $campo => $descricao) {
        if (!empty($row[$campo])) {
            $row['imagens'][] = [
                'field' => $campo,
                'image_url' => $baseUrl . $row[$campo],
                'alt_text' => $row['alt_' . substr($campo, strpos($campo, '_') + 1)] ?? $descricao
            ];
        }
    }
}

// Função para buscar destaques de uma newsletter
function buscarDestaques($conn, $pk_news, $baseUrl)
{
    $sql = "
        SELECT
            pk_news_conteudo AS id,
            dia_conteudo AS ordem,
            titulo_news AS titulo,
            subtitulo,
            descritivo_conteudo AS descricao,
            img1_conteudo AS imagem,
            img_reduz AS imagem_reduzida,
            alt,
            link_endereco,
            link_ativo,
            img_link,
            layout_news AS layout,
            expert AS especialista,
            exibe_destaque AS exibir
        FROM conteudo_internet.news_conteudo
        WHERE fk_news = $1
        ORDER BY dia_conteudo
    ";

    $result = pg_query_params($conn, $sql, [$pk_news]);
    $destaques = [];

    while ($row = pg_fetch_assoc($result)) {
        // Formatar URLs de imagens
        if (!empty($row['imagem'])) {
            $row['imagem'] = $baseUrl . $row['imagem'];
        }
        if (!empty($row['imagem_reduzida'])) {
            $row['imagem_reduzida'] = $baseUrl . $row['imagem_reduzida'];
        }
        if (!empty($row['img_link'])) {
            $row['img_link'] = $baseUrl . $row['img_link'];
        }

        // Converter booleans
        $row['link_ativo'] = $row['link_ativo'] === 't';
        $row['exibir'] = $row['exibir'] === 't';

        $destaques[] = $row;
    }

    return $destaques;
}

// Helper para formatar strings (NULL se vazio)
function formatString($value)
{
    if ($value === null || $value === '') return null;
    return $value;
}

// Helper para formatar datas (DD/MM/YYYY to YYYY-MM-DD)
function formatDate($dataStr)
{
    if (empty($dataStr)) return null;
    $parts = explode('/', $dataStr);
    if (count($parts) === 3) {
        return sprintf("%04d-%02d-%02d", $parts[2], $parts[1], $parts[0]);
    }
    return null;
}

// Helper para formatar booleanos (de string/json para SQL)
function formatBoolean($valor)
{
    if ($valor === null || $valor === '') return null;
    return (bool)$valor ? 't' : 'f';
}

// Helper para formatar numéricos
function formatNumeric($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) return null;
    return (float)$value;
}

// Helper para formatar inteiros
function formatInt($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) return null;
    return (int)$value;
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
        // 🔹 ROTA 1: Listar Newsletters (GET)
        // =========================================================
        case 'listar_news':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            // Autenticação via middleware
            $user_data = null;
            // if (!handleAutenticacao($conn, 'listar_news', $user_data)) {
            //     break;
            // }

            $filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : null;
            $filtro_ativo_web = isset($_GET['filtro_ativo_web']) ? $_GET['filtro_ativo_web'] : 'all';
            $filtro_ativo_home = isset($_GET['filtro_ativo_home']) ? $_GET['filtro_ativo_home'] : 'all';
            $filtro_ativo_passion = isset($_GET['filtro_ativo_passion']) ? $_GET['filtro_ativo_passion'] : 'all';
            $filtro_ativo_be = isset($_GET['filtro_ativo_be']) ? $_GET['filtro_ativo_be'] : 'all';
            $filtro_empresa = isset($_GET['filtro_empresa']) ? $_GET['filtro_empresa'] : null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 200;

            $params = [];
            $idx = 1;
            $where = [];

            if ($filtro_nome) {
                $where[] = "nome ILIKE $" . $idx++;
                $params[] = "%{$filtro_nome}%";
            }

            if ($filtro_ativo_web && $filtro_ativo_web !== 'all') {
                $where[] = "ativo_web = $" . $idx++;
                $params[] = ($filtro_ativo_web === 'true' ? 't' : 'f');
            }

            if ($filtro_ativo_home && $filtro_ativo_home !== 'all') {
                $where[] = "ativo_home = $" . $idx++;
                $params[] = ($filtro_ativo_home === 'true' ? 't' : 'f');
            }

            if ($filtro_ativo_passion && $filtro_ativo_passion !== 'all') {
                $where[] = "ativo_passion = $" . $idx++;
                $params[] = ($filtro_ativo_passion === 'true' ? 't' : 'f');
            }

            if ($filtro_ativo_be && $filtro_ativo_be !== 'all') {
                $where[] = "ativo_be = $" . $idx++;
                $params[] = ($filtro_ativo_be === 'true' ? 't' : 'f');
            }

            if ($filtro_empresa) {
                $where[] = "empresa = $" . $idx++;
                $params[] = $filtro_empresa;
            }

            $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

            $sql = "
                SELECT
                    pk_news AS id,
                    nome,
                    data,
                    data_extenso,
                    titulo,
                    img_topo,
                    alt_topo,
                    bloco_livre,
                    foto_bloco,
                    alt_livre,
                    chamada1_bloco,
                    chamada_bloco,
                    pdf,
                    more_poducts,
                    empresa,
                    ativo_web,
                    ativo_home,
                    titulo_ativo,
                    recep,
                    novo_layout,
                    ativo_passion,
                    ativo_be,
                    is_header_italiano,
                    cor_pe,
                    lingua
                FROM conteudo_internet.news
                {$where_sql}
                ORDER BY pk_news DESC
                LIMIT $" . $idx . "
            ";

            $params[] = $limit;

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception(pg_last_error($conn));

            $newsletters = [];
            while ($row = pg_fetch_assoc($result)) {
                // Converter booleans
                $row['ativo_web'] = $row['ativo_web'] === 't';
                $row['ativo_home'] = $row['ativo_home'] === 't';
                $row['titulo_ativo'] = $row['titulo_ativo'] === 't';
                $row['recep'] = $row['recep'] === 't';
                $row['novo_layout'] = $row['novo_layout'] === 't';
                $row['ativo_passion'] = $row['ativo_passion'] === 't';
                $row['ativo_be'] = $row['ativo_be'] === 't';
                $row['is_header_italiano'] = $row['is_header_italiano'] === 't';

                // Formatar data
                if ($row['data']) {
                    $row['data_formatada'] = date('d/m/Y', strtotime($row['data']));
                }

                // Montar imagens
                montarImagensNews($row, $BASE_URL_IMAGEM);

                $newsletters[] = $row;
            }

            response($newsletters);
            break;

        // =========================================================
        // 🔹 ROTA 2: Buscar Newsletter específica por ID (GET)
        // =========================================================
        case 'buscar_news':
        case 'buscar_news_completa':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            $sql = "
                SELECT
                    pk_news,
                    pk_news AS id,
                    nome,
                    data,
                    data_extenso,
                    titulo,
                    img_topo,
                    alt_topo,
                    bloco_livre,
                    foto_bloco,
                    alt_livre,
                    chamada1_bloco,
                    chamada_bloco,
                    pdf,
                    more_poducts,
                    empresa,
                    ativo_web,
                    ativo_home,
                    titulo_ativo,
                    recep,
                    novo_layout,
                    ativo_passion,
                    ativo_be,
                    is_header_italiano,
                    cor_pe,
                    lingua
                FROM conteudo_internet.news
                WHERE pk_news = $1
                LIMIT 1
            ";

            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Newsletter não encontrada"], 404);
            }

            $news = pg_fetch_assoc($result);

            // Converter booleans
            $news['ativo_web'] = $news['ativo_web'] === 't';
            $news['ativo_home'] = $news['ativo_home'] === 't';
            $news['titulo_ativo'] = $news['titulo_ativo'] === 't';
            $news['recep'] = $news['recep'] === 't';
            $news['novo_layout'] = $news['novo_layout'] === 't';
            $news['ativo_passion'] = $news['ativo_passion'] === 't';
            $news['ativo_be'] = $news['ativo_be'] === 't';
            $news['is_header_italiano'] = $news['is_header_italiano'] === 't';

            // Formatar data
            if ($news['data']) {
                $news['data_formatada'] = date('d/m/Y', strtotime($news['data']));
            }

            // Montar imagens e destaques
            montarImagensNews($news, $BASE_URL_IMAGEM);
            $news['destaques'] = buscarDestaques($conn, $id, $BASE_URL_IMAGEM);

            response($news);
            break;

        // =========================================================
        // 🔹 ROTA 2.5: Listar Especialistas Brasileiros (GET)
        // =========================================================
        case 'listar_especialistas':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $sql = "
                SELECT
                    pk_br_experts,
                    nome
                FROM conteudo_internet.brazilian_experts
                ORDER BY nome ASC
            ";

            $result = pg_query($conn, $sql);
            if (!$result) {
                throw new Exception("Erro ao buscar especialistas: " . pg_last_error($conn));
            }

            $especialistas = [];
            while ($row = pg_fetch_assoc($result)) {
                $especialistas[] = $row;
            }

            response($especialistas);
            break;

        // =========================================================
        // 🔹 ROTA 3: Criar Newsletter (POST)
        // =========================================================
        case 'criar_news':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            if (empty($input)) response(["error" => "Dados da newsletter são obrigatórios no body JSON"], 400);

            // Campos principais
            $campos = [
                'nome',
                'img_topo',
                'titulo',
                'bloco_livre',
                'ativo_web',
                'ativo_home',
                'foto_bloco',
                'cor_pe',
                'alt_livre',
                'alt_topo',
                'lingua',
                'empresa',
                'titulo_ativo',
                'ativo_passion',
                'ativo_be',
                'is_header_italiano',
                'novo_layout',
                'data_extenso',
                'chamada_bloco',
                'chamada1_bloco',
                'pdf',
                'more_poducts',
                'recep'
            ];

            $params = [];
            $placeholders = [];
            $idx = 1;

            // Data atual
            $data_now = date('Y-m-d');

            foreach ($campos as $campo) {
                $valor = $input[$campo] ?? null;

                // Formatação específica por tipo de campo
                if (in_array($campo, ['ativo_web', 'ativo_home', 'titulo_ativo', 'ativo_passion', 'ativo_be', 'is_header_italiano', 'novo_layout', 'recep'])) {
                    $valor = formatBoolean($valor);
                } elseif ($campo === 'lingua') {
                    $valor = formatInt($valor) ?? 2; // Default: 2
                } elseif ($campo === 'cor_pe') {
                    $valor = formatString($valor) ?? 'F9020E'; // Default cor
                } else {
                    $valor = formatString($valor);
                }

                $params[] = $valor;
                $placeholders[] = '$' . $idx++;
            }

            // Adicionar data
            $params[] = $data_now;
            $placeholders[] = '$' . $idx++;

            $sql = "
                INSERT INTO conteudo_internet.news (" . implode(',', $campos) . ", data)
                VALUES (" . implode(',', $placeholders) . ")
                RETURNING pk_news
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Erro ao inserir newsletter: " . pg_last_error($conn));
            }

            $row = pg_fetch_assoc($result);
            $news_id = $row['pk_news'];

            // Inserir destaques se fornecidos
            if (!empty($input['destaques']) && is_array($input['destaques'])) {
                foreach ($input['destaques'] as $destaque) {
                    $sql_destaque = "
                        INSERT INTO conteudo_internet.news_conteudo
                        (dia_conteudo, titulo_news, descritivo_conteudo, img1_conteudo, fk_news, layout_news, alt, link_endereco, link_ativo, img_link, subtitulo, img_reduz, expert, exibe_destaque)
                        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)
                    ";

                    pg_query_params($conn, $sql_destaque, [
                        formatInt($destaque['dia_conteudo'] ?? $destaque['ordem'] ?? 1),
                        formatString($destaque['titulo_news'] ?? $destaque['titulo'] ?? ''),
                        formatString($destaque['descritivo_conteudo'] ?? $destaque['descricao'] ?? ''),
                        formatString($destaque['img1_conteudo'] ?? $destaque['imagem'] ?? ''),
                        $news_id,
                        formatString($destaque['layout_news'] ?? $destaque['layout'] ?? '1'),
                        formatString($destaque['alt'] ?? ''),
                        formatString($destaque['link_endereco'] ?? ''),
                        formatBoolean($destaque['link_ativo'] ?? false),
                        formatString($destaque['img_link'] ?? ''),
                        formatString($destaque['subtitulo'] ?? ''),
                        formatString($destaque['img_reduz'] ?? $destaque['imagem_reduzida'] ?? ''),
                        formatString($destaque['expert'] ?? $destaque['especialista'] ?? ''),
                        formatBoolean($destaque['exibe_destaque'] ?? $destaque['exibir'] ?? true)
                    ]);
                }
            }

            response([
                'success' => true,
                'message' => 'Newsletter criada com sucesso!',
                'id' => $news_id
            ], 201);
            break;

        // =========================================================
        // 🔹 ROTA 4: Atualizar Newsletter (PUT)
        // =========================================================
        case 'atualizar_news':
        case 'atualizar_news_completa':
            if ($method !== 'PUT') response(["error" => "Método não permitido. Use PUT."], 405);

            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            if (empty($input)) response(["error" => "Dados da newsletter são obrigatórios no body JSON"], 400);

            try {
                // Iniciar transação
                pg_query($conn, "BEGIN");

                // ============================================
                // 1. ATUALIZAR DADOS PRINCIPAIS DA NEWSLETTER
                // ============================================

                $campos_permitidos = [
                    'nome',
                    'titulo',
                    'img_topo',
                    'bloco_livre',
                    'foto_bloco',
                    'ativo_web',
                    'ativo_home',
                    'empresa',
                    'titulo_ativo',
                    'recep',
                    'ativo_passion',
                    'ativo_be',
                    'is_header_italiano',
                    'novo_layout',
                    'chamada_bloco',
                    'chamada1_bloco',
                    'pdf',
                    'data_extenso',
                    'more_poducts',
                    'alt_topo',
                    'alt_livre',
                    'cor_pe',
                    'lingua'
                ];

                $set = [];
                $params = [];
                $idx = 1;
                $updated = false;

                foreach ($input as $chave => $valor) {
                    // Ignorar campos especiais
                    if (in_array($chave, ['id', 'pk_news', 'destaques', 'data', 'data_formatada', 'imagens'])) {
                        continue;
                    }

                    if (in_array($chave, $campos_permitidos)) {
                        // Aplicar formatação apropriada
                        if (in_array($chave, ['ativo_web', 'ativo_home', 'titulo_ativo', 'recep', 'ativo_passion', 'ativo_be', 'is_header_italiano', 'novo_layout'])) {
                            $set[] = "$chave = $" . $idx++;
                            $params[] = formatBoolean($valor);
                            $updated = true;
                        } elseif ($chave === 'lingua') {
                            $set[] = "$chave = $" . $idx++;
                            $params[] = formatInt($valor);
                            $updated = true;
                        } else {
                            $set[] = "$chave = $" . $idx++;
                            $params[] = formatString($valor);
                            $updated = true;
                        }
                    }
                }

                // Executar UPDATE na tabela principal se houver alterações
                if (!empty($set)) {
                    $params[] = $id;
                    $sql = "UPDATE conteudo_internet.news SET " . implode(', ', $set) . " WHERE pk_news = $" . $idx;

                    $result = pg_query_params($conn, $sql, $params);
                    if (!$result) {
                        throw new Exception("Erro ao atualizar newsletter: " . pg_last_error($conn));
                    }
                }

                // ============================================
                // 2. ATUALIZAR DESTAQUES
                // ============================================

                if (isset($input['destaques']) && is_array($input['destaques'])) {
                    // Deletar todos os destaques existentes
                    $delete_destaques = pg_query_params(
                        $conn,
                        "DELETE FROM conteudo_internet.news_conteudo WHERE fk_news = $1",
                        [$id]
                    );

                    if (!$delete_destaques) {
                        throw new Exception("Erro ao deletar destaques antigos: " . pg_last_error($conn));
                    }

                    // Inserir novos destaques
                    foreach ($input['destaques'] as $destaque) {
                        $sql_destaque = "
                            INSERT INTO conteudo_internet.news_conteudo
                            (dia_conteudo, titulo_news, descritivo_conteudo, img1_conteudo, fk_news, layout_news, alt, link_endereco, link_ativo, img_link, subtitulo, img_reduz, expert, exibe_destaque)
                            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)
                        ";

                        $result_destaque = pg_query_params($conn, $sql_destaque, [
                            formatInt($destaque['dia_conteudo'] ?? $destaque['ordem'] ?? 1),
                            formatString($destaque['titulo_news'] ?? $destaque['titulo'] ?? ''),
                            formatString($destaque['descritivo_conteudo'] ?? $destaque['descricao'] ?? ''),
                            formatString($destaque['img1_conteudo'] ?? $destaque['imagem'] ?? ''),
                            $id,
                            formatString($destaque['layout_news'] ?? $destaque['layout'] ?? '1'),
                            formatString($destaque['alt'] ?? ''),
                            formatString($destaque['link_endereco'] ?? ''),
                            formatBoolean($destaque['link_ativo'] ?? false),
                            formatString($destaque['img_link'] ?? ''),
                            formatString($destaque['subtitulo'] ?? ''),
                            formatString($destaque['img_reduz'] ?? $destaque['imagem_reduzida'] ?? ''),
                            formatString($destaque['expert'] ?? $destaque['especialista'] ?? ''),
                            formatBoolean($destaque['exibe_destaque'] ?? $destaque['exibir'] ?? true)
                        ]);

                        if (!$result_destaque) {
                            throw new Exception("Erro ao inserir destaque: " . pg_last_error($conn));
                        }
                    }

                    $updated = true;
                }

                if (!$updated) {
                    pg_query($conn, "ROLLBACK");
                    response(["success" => false, "message" => "Nenhuma alteração realizada"], 200);
                }

                // Commit da transação
                pg_query($conn, "COMMIT");

                response([
                    'success' => true,
                    'message' => 'Newsletter atualizada com sucesso!',
                    'id' => $id
                ]);
            } catch (Exception $e) {
                // Rollback em caso de erro
                pg_query($conn, "ROLLBACK");
                response(['error' => 'Erro no servidor: ' . $e->getMessage()], 500);
            }
            break;

        // =========================================================
        // 🔹 ROTA 5: Excluir Newsletter (DELETE)
        // =========================================================
        case 'excluir_news':
            if ($method !== 'DELETE') response(["error" => "Método não permitido. Use DELETE."], 405);

            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            // Deleta destaques primeiro
            pg_query_params($conn, "DELETE FROM conteudo_internet.news_conteudo WHERE fk_news = $1", [$id]);

            $sql = "DELETE FROM conteudo_internet.news WHERE pk_news = $1";
            $result = pg_query_params($conn, $sql, [$id]);

            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }

            $affected_rows = pg_affected_rows($result);
            if ($affected_rows > 0) {
                response(["success" => true, "message" => "Newsletter excluída com sucesso"]);
            } else {
                response(["error" => "Newsletter não encontrada"], 404);
            }
            break;

        // =========================================================
        // 🔹 ROTAS PARA DESTAQUES - OPCIONAIS
        // =========================================================
        case 'listar_destaques':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $news_id = isset($_GET['news_id']) ? $_GET['news_id'] : null;
            if (!$news_id) response(["error" => "news_id é obrigatório"], 400);

            $destaques = buscarDestaques($conn, $news_id, $BASE_URL_IMAGEM);
            response($destaques);
            break;

        case 'criar_destaque':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            $news_id = isset($_GET['news_id']) ? $_GET['news_id'] : null;
            if (!$news_id || empty($input)) response(["error" => "news_id e dados do destaque são obrigatórios"], 400);

            $sql = "
                INSERT INTO conteudo_internet.news_conteudo
                (dia_conteudo, titulo_news, descritivo_conteudo, img1_conteudo, fk_news, layout_news, alt, link_endereco, link_ativo, img_link, subtitulo, img_reduz, expert, exibe_destaque)
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)
                RETURNING pk_news_conteudo
            ";

            $result = pg_query_params($conn, $sql, [
                formatInt($input['dia_conteudo'] ?? $input['ordem'] ?? 1),
                formatString($input['titulo_news'] ?? $input['titulo'] ?? ''),
                formatString($input['descritivo_conteudo'] ?? $input['descricao'] ?? ''),
                formatString($input['img1_conteudo'] ?? $input['imagem'] ?? ''),
                $news_id,
                formatString($input['layout_news'] ?? $input['layout'] ?? '1'),
                formatString($input['alt'] ?? ''),
                formatString($input['link_endereco'] ?? ''),
                formatBoolean($input['link_ativo'] ?? false),
                formatString($input['img_link'] ?? ''),
                formatString($input['subtitulo'] ?? ''),
                formatString($input['img_reduz'] ?? $input['imagem_reduzida'] ?? ''),
                formatString($input['expert'] ?? $input['especialista'] ?? ''),
                formatBoolean($input['exibe_destaque'] ?? $input['exibir'] ?? true)
            ]);

            if (!$result) throw new Exception(pg_last_error($conn));

            $row = pg_fetch_assoc($result);
            response(['success' => true, 'id' => $row['pk_news_conteudo']], 201);
            break;

        case 'atualizar_destaque':
            if ($method !== 'PUT') response(["error" => "Método não permitido. Use PUT."], 405);

            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id || empty($input)) response(["error" => "ID e dados do destaque são obrigatórios"], 400);

            $set = [];
            $params = [];
            $idx = 1;

            $campos_destaque = [
                'dia_conteudo' => 'ordem',
                'titulo_news' => 'titulo',
                'subtitulo' => 'subtitulo',
                'descritivo_conteudo' => 'descricao',
                'img1_conteudo' => 'imagem',
                'img_reduz' => 'imagem_reduzida',
                'alt' => 'alt',
                'link_endereco' => 'link_endereco',
                'link_ativo' => 'link_ativo',
                'img_link' => 'img_link',
                'layout_news' => 'layout',
                'expert' => 'especialista',
                'exibe_destaque' => 'exibir'
            ];

            foreach ($campos_destaque as $campo_db => $campo_input) {
                if (isset($input[$campo_db]) || isset($input[$campo_input])) {
                    $valor = $input[$campo_db] ?? $input[$campo_input];
                    $set[] = "$campo_db = $" . $idx++;

                    if (in_array($campo_db, ['link_ativo', 'exibe_destaque'])) {
                        $params[] = formatBoolean($valor);
                    } elseif ($campo_db === 'dia_conteudo') {
                        $params[] = formatInt($valor);
                    } else {
                        $params[] = formatString($valor);
                    }
                }
            }

            if (empty($set)) response(["success" => false, "message" => "Nenhuma alteração"], 200);

            $params[] = $id;
            $sql = "UPDATE conteudo_internet.news_conteudo SET " . implode(', ', $set) . " WHERE pk_news_conteudo = $" . $idx;

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception(pg_last_error($conn));

            response(['success' => true, 'message' => 'Destaque atualizado']);
            break;

        case 'excluir_destaque':
            if ($method !== 'DELETE') response(["error" => "Método não permitido. Use DELETE."], 405);

            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            $sql = "DELETE FROM conteudo_internet.news_conteudo WHERE pk_news_conteudo = $1";
            $result = pg_query_params($conn, $sql, [$id]);

            if (!$result) throw new Exception(pg_last_error($conn));

            $affected = pg_affected_rows($result);
            if ($affected > 0) {
                response(["success" => true, "message" => "Destaque excluído"]);
            } else {
                response(["error" => "Destaque não encontrado"], 404);
            }
            break;

        // =========================================================
        // 🔹 ROTA: Inserir Comentário de Especialista (POST)
        // =========================================================
        case 'inserir_comentario_especialista':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            if (empty($input)) response(["error" => "Dados do comentário são obrigatórios no body JSON"], 400);

            // Validações básicas
            if (!isset($input['pk_news']) || empty($input['pk_news'])) {
                response(["error" => "pk_news é obrigatório"], 400);
            }

            if (!isset($input['expert']) || empty($input['expert']) || $input['expert'] === '0') {
                response(["error" => "Especialista é obrigatório"], 400);
            }

            if (!isset($input['titulo_news']) || empty(trim($input['titulo_news']))) {
                response(["error" => "Título é obrigatório"], 400);
            }

            try {
                $sql = "
                    INSERT INTO conteudo_internet.news_conteudo
                    (dia_conteudo, titulo_news, subtitulo, descritivo_conteudo, img1_conteudo,
                     fk_news, layout_news, alt, link_endereco, link_ativo, img_link,
                     img_reduz, expert, exibe_destaque)
                    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)
                    RETURNING pk_news_conteudo
                ";

                $result = pg_query_params($conn, $sql, [
                    formatInt($input['dia_conteudo'] ?? 1),
                    formatString($input['titulo_news']),
                    formatString($input['subtitulo'] ?? $input['sub_titulo_news'] ?? ''),
                    formatString($input['descritivo_conteudo'] ?? ''),
                    formatString($input['img1_conteudo'] ?? ''),
                    formatInt($input['pk_news']),
                    formatString($input['layout_news'] ?? '1'),
                    formatString($input['alt'] ?? ''),
                    formatString($input['link_endereco'] ?? ''),
                    formatBoolean($input['link_ativo'] ?? false),
                    formatString($input['img_link'] ?? ''),
                    formatString($input['img_reduz'] ?? ''),
                    formatString($input['expert']),
                    formatBoolean($input['exibe_destaque'] ?? true)
                ]);

                if (!$result) {
                    throw new Exception("Erro ao inserir comentário de especialista: " . pg_last_error($conn));
                }

                $row = pg_fetch_assoc($result);
                response([
                    'success' => true,
                    'message' => 'Comentário do especialista inserido com sucesso!',
                    'id' => $row['pk_news_conteudo']
                ], 201);

            } catch (Exception $e) {
                error_log("Erro ao inserir comentário de especialista: " . $e->getMessage());
                response(['error' => 'Erro ao inserir comentário: ' . $e->getMessage()], 500);
            }
            break;

        // =========================================================
        // 🔹 ROTA 6: Duplicar Newsletter (POST)
        // =========================================================
        case 'duplicar_news':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);

            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) response(["error" => "ID da newsletter é obrigatório"], 400);

            try {
                // 1. Buscar newsletter original
                $sql_original = "
                    SELECT
                        nome,
                        data_extenso,
                        titulo,
                        img_topo,
                        alt_topo,
                        bloco_livre,
                        foto_bloco,
                        alt_livre,
                        chamada1_bloco,
                        chamada_bloco,
                        pdf,
                        more_poducts,
                        empresa,
                        ativo_web,
                        ativo_home,
                        titulo_ativo,
                        recep,
                        novo_layout,
                        ativo_passion,
                        ativo_be,
                        is_header_italiano,
                        cor_pe,
                        lingua
                    FROM conteudo_internet.news
                    WHERE pk_news = $1
                ";

                $result = pg_query_params($conn, $sql_original, [$id]);
                if (!$result || pg_num_rows($result) === 0) {
                    response(["error" => "Newsletter original não encontrada"], 404);
                }

                $original = pg_fetch_assoc($result);

                // 2. Criar nova newsletter com sufixo " - Cópia"
                $novo_nome = $original['nome'] . ' - Cópia';
                $data_now = date('Y-m-d');

                $sql_insert = "
                    INSERT INTO conteudo_internet.news (
                        nome, data, data_extenso, titulo, img_topo, alt_topo,
                        bloco_livre, foto_bloco, alt_livre, chamada1_bloco,
                        chamada_bloco, pdf, more_poducts, empresa, ativo_web,
                        ativo_home, titulo_ativo, recep, novo_layout,
                        ativo_passion, ativo_be, is_header_italiano, cor_pe, lingua
                    ) VALUES (
                        $1, $2, $3, $4, $5, $6, $7, $8, $9, $10,
                        $11, $12, $13, $14, $15, $16, $17, $18, $19,
                        $20, $21, $22, $23, $24
                    )
                    RETURNING pk_news
                ";

                $params_insert = [
                    $novo_nome,
                    $data_now,
                    $original['data_extenso'],
                    $original['titulo'],
                    $original['img_topo'],
                    $original['alt_topo'],
                    $original['bloco_livre'],
                    $original['foto_bloco'],
                    $original['alt_livre'],
                    $original['chamada1_bloco'],
                    $original['chamada_bloco'],
                    $original['pdf'],
                    $original['more_poducts'],
                    $original['empresa'],
                    $original['ativo_web'],
                    $original['ativo_home'],
                    $original['titulo_ativo'],
                    $original['recep'],
                    $original['novo_layout'],
                    $original['ativo_passion'],
                    $original['ativo_be'],
                    $original['is_header_italiano'],
                    $original['cor_pe'],
                    $original['lingua']
                ];

                $result_insert = pg_query_params($conn, $sql_insert, $params_insert);
                if (!$result_insert) {
                    throw new Exception("Erro ao duplicar newsletter: " . pg_last_error($conn));
                }

                $row_nova = pg_fetch_assoc($result_insert);
                $novo_id = $row_nova['pk_news'];

                // 3. Copiar destaques da newsletter original
                $sql_destaques = "
                    SELECT
                        dia_conteudo, titulo_news, subtitulo,
                        link_endereco, img_link, link_ativo, descritivo_conteudo,
                        img1_conteudo, img_reduz, alt, layout_news,
                        exibe_destaque, expert
                    FROM conteudo_internet.news_conteudo
                    WHERE fk_news = $1
                    ORDER BY dia_conteudo
                ";

                $result_destaques = pg_query_params($conn, $sql_destaques, [$id]);
                $destaques_copiados = 0;

                if ($result_destaques && pg_num_rows($result_destaques) > 0) {
                    $sql_insert_destaque = "
                        INSERT INTO conteudo_internet.news_conteudo (
                            fk_news, dia_conteudo, titulo_news,
                            subtitulo, link_endereco, img_link, link_ativo,
                            descritivo_conteudo, img1_conteudo, img_reduz,
                            alt, layout_news, exibe_destaque, expert
                        ) VALUES (
                            $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14
                        )
                    ";

                    while ($destaque = pg_fetch_assoc($result_destaques)) {
                        $params_destaque = [
                            $novo_id,
                            $destaque['dia_conteudo'],
                            $destaque['titulo_news'],
                            $destaque['subtitulo'],
                            $destaque['link_endereco'],
                            $destaque['img_link'],
                            $destaque['link_ativo'],
                            $destaque['descritivo_conteudo'],
                            $destaque['img1_conteudo'],
                            $destaque['img_reduz'],
                            $destaque['alt'],
                            $destaque['layout_news'],
                            $destaque['exibe_destaque'],
                            $destaque['expert']
                        ];

                        $result_dest = pg_query_params($conn, $sql_insert_destaque, $params_destaque);
                        if ($result_dest) {
                            $destaques_copiados++;
                        }
                    }
                }

                response([
                    "success" => true,
                    "message" => "Newsletter duplicada com sucesso",
                    "novo_id" => $novo_id,
                    "destaques_copiados" => $destaques_copiados
                ], 201);
            } catch (Exception $e) {
                error_log("Erro ao duplicar newsletter: " . $e->getMessage());
                response(['error' => 'Erro ao duplicar newsletter: ' . $e->getMessage()], 500);
            }
            break;

        default:
            response(["error" => "Rota inválida"], 400);
    }
} catch (Exception $e) {
    error_log("Erro na API de Newsletter: " . $e->getMessage());
    response(["error" => "Erro no servidor: " . $e->getMessage()], 500);
}
