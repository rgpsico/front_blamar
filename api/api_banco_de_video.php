<?php

/**
 * API Única para Busca e Gerenciamento de Vídeos
 *
 * Descrição:
 * Endpoint unificado para consulta, busca e gerenciamento de vídeos.
 * Todos os retornos são em JSON.
 *
 * Endpoints suportados (GET, exceto quando indicado):
 *
 * - GET  ?action=search_videos&query=rio
 *         → Busca vídeos por título (pt/en/esp), descrição ou autor
 *
 * - GET  ?action=video_by_id&video_id=123
 *         → Retorna dados completos de um vídeo específico
 *
 * - GET  ?action=videos_by_hotel&hotel_id=ABC123
 *         → Vídeos associados a um hotel (mneu_for)
 *
 * - GET  ?action=videos_by_city&cidade_cod=45
 *         → Vídeos associados a uma cidade (cid)
 *
 * - GET  ?action=list_cities_with_videos
 *         → Lista cidades que possuem vídeos cadastrados
 *
 * - POST ?action=upload_video (multipart/form-data)
 *         → Upload de novo vídeo + metadados
 *
 * - POST ?action=update_video_metadata (JSON body)
 *         → Atualiza título, descrição, autor, ativo etc.
 *
 * - POST ?action=delete_video (JSON body)
 *         → Marca vídeo como inativo (ativo = false)
 *
 * Retornos comuns:
 * - 200: Sucesso
 * - 400: Parâmetro obrigatório ausente / inválido
 * - 404: Nenhum resultado encontrado
 * - 500: Erro interno do servidor
 *
 * Tabelas relacionadas:
 * - banco_imagem.bco_video
 * - sbd95.fornec          (para hotéis)
 * - tarifario.cidade_tpo  (para cidades)
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../util/connection.php';

if (!isset($_GET['action']) || empty(trim($_GET['action']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro "action" é obrigatório']);
    exit;
}

$action = strtolower(trim($_GET['action']));

switch ($action) {

    // --------------------------------------------------
    // BUSCA GERAL DE VÍDEOS
    // --------------------------------------------------
    case 'search_videos':
        if (!isset($_GET['query']) || empty(trim($_GET['query']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetro "query" é obrigatório']);
            exit;
        }

        $busca = pg_escape_string(strtolower(trim($_GET['query'])));

        $sql = "
            SELECT 
                pk_bco_video,
                url,
                thumb,
                titulo_pt,
                titulo_en,
                titulo_esp,
                description,
                autor,
                ativo,
                data_cadastro,
                cid
            FROM banco_imagem.bco_video
            WHERE ativo = true
              AND (
                  LOWER(titulo_pt)   LIKE LOWER('%$busca%')
               OR LOWER(titulo_en)   LIKE LOWER('%$busca%')
               OR LOWER(titulo_esp)  LIKE LOWER('%$busca%')
               OR LOWER(description) LIKE LOWER('%$busca%')
               OR LOWER(autor)       LIKE LOWER('%$busca%')
              )
            ORDER BY data_cadastro DESC
            LIMIT 50
        ";

        $result = pg_query($conn, $sql);

        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na consulta: ' . pg_last_error($conn)]);
            exit;
        }

        $videos = [];
        while ($row = pg_fetch_assoc($result)) {
            $videos[] = $row;
        }

        echo json_encode([
            'action' => 'search_videos',
            'total'  => count($videos),
            'query'  => $busca,
            'videos' => $videos
        ]);
        break;


    // --------------------------------------------------
    // DETALHES DE UM VÍDEO ESPECÍFICO
    // --------------------------------------------------
    case 'video_by_id':
        if (!isset($_GET['video_id']) || !is_numeric($_GET['video_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetro "video_id" obrigatório e numérico']);
            exit;
        }

        $pk = (int)$_GET['video_id'];

        $sql = "
            SELECT 
                v.*,
                c.nome_en AS nome_cidade,
                f.nome_for AS nome_hotel
            FROM banco_imagem.bco_video v
            LEFT JOIN tarifario.cidade_tpo c ON v.cid = c.cidade_cod
            LEFT JOIN sbd95.fornec f ON v.mneu_for = f.mneu_for
            WHERE v.pk_bco_video = $pk
        ";

        $result = pg_query($conn, $sql);

        if (!$result || pg_num_rows($result) === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Vídeo não encontrado']);
            exit;
        }

        $video = pg_fetch_assoc($result);

        echo json_encode([
            'success' => true,
            'video'   => $video
        ]);
        break;


    // --------------------------------------------------
    // VÍDEOS DE UM HOTEL
    // --------------------------------------------------
    case 'videos_by_hotel':
        if (!isset($_GET['hotel_id']) || empty(trim($_GET['hotel_id']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetro "hotel_id" (mneu_for) obrigatório']);
            exit;
        }

        $mneu_for = pg_escape_string(trim($_GET['hotel_id']));

        $sql = "
            SELECT *
            FROM banco_imagem.bco_video
            WHERE mneu_for = '$mneu_for'
              AND ativo = true
            ORDER BY data_cadastro DESC
        ";

        $result = pg_query($conn, $sql);
        $videos = pg_fetch_all($result) ?: [];

        echo json_encode([
            'action'     => 'videos_by_hotel',
            'hotel_id'   => $mneu_for,
            'total'      => count($videos),
            'videos'     => $videos
        ]);
        break;


    // --------------------------------------------------
    // VÍDEOS DE UMA CIDADE
    // --------------------------------------------------
    case 'videos_by_city':
        if (!isset($_GET['cidade_cod']) || !is_numeric($_GET['cidade_cod'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetro "cidade_cod" obrigatório e numérico']);
            exit;
        }

        $cid = (int)$_GET['cidade_cod'];

        $sql = "
            SELECT *
            FROM banco_imagem.bco_video
            WHERE cid = $cid
              AND ativo = true
            ORDER BY data_cadastro DESC
        ";

        $result = pg_query($conn, $sql);
        $videos = pg_fetch_all($result) ?: [];

        echo json_encode([
            'action'     => 'videos_by_city',
            'cidade_cod' => $cid,
            'total'      => count($videos),
            'videos'     => $videos
        ]);
        break;


    // --------------------------------------------------
    // LISTAR CIDADES COM VÍDEOS
    // --------------------------------------------------
    case 'list_cities_with_videos':
        $sql = "
            SELECT DISTINCT 
                c.cidade_cod,
                c.nome_en,
                c.nome_pt,
                COUNT(v.pk_bco_video) AS total_videos
            FROM banco_imagem.bco_video v
            INNER JOIN tarifario.cidade_tpo c ON v.cid = c.cidade_cod
            WHERE v.ativo = true
            GROUP BY c.cidade_cod, c.nome_en, c.nome_pt
            ORDER BY total_videos DESC, c.nome_en
        ";

        $result = pg_query($conn, $sql);

        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => pg_last_error($conn)]);
            exit;
        }

        $cities = pg_fetch_all($result) ?: [];

        echo json_encode([
            'action' => 'list_cities_with_videos',
            'total'  => count($cities),
            'cities' => $cities
        ]);
        break;


    // --------------------------------------------------
    // UPLOAD DE VÍDEO (POST)
    // --------------------------------------------------
    case 'upload_video':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            exit;
        }

        if (empty($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'Arquivo de vídeo obrigatório']);
            exit;
        }

        // Campos obrigatórios / opcionais
        $titulo_pt   = trim($_POST['titulo_pt']   ?? '');
        $titulo_en   = trim($_POST['titulo_en']   ?? '');
        $titulo_esp  = trim($_POST['titulo_esp']  ?? '');
        $description = trim($_POST['description'] ?? '');
        $autor       = trim($_POST['autor']       ?? '');
        $mneu_for    = trim($_POST['mneu_for']    ?? '');   // hotel
        $cid         = !empty($_POST['cid']) ? (int)$_POST['cid'] : null;

        if ($titulo_pt === '' && $titulo_en === '' && $titulo_esp === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Pelo menos um título (pt/en/esp) é obrigatório']);
            exit;
        }

        // Aqui você deve implementar a lógica de:
        // 1. Salvar o arquivo no servidor (ou enviar para storage externo)
        // 2. Gerar thumbnail (opcional)
        // 3. Obter URL final e thumb

        // Exemplo fictício — substitua pela sua lógica real
        $video_filename = 'videos/' . time() . '_' . basename($_FILES['video']['name']);
        $url_final      = 'https://www.blumar.com.br/' . $video_filename;
        $thumb_final    = 'https://www.blumar.com.br/videos/thumbs/' . time() . '.jpg';

        // Movendo arquivo (exemplo local — adapte!)
        $upload_path = __DIR__ . '/../' . $video_filename;
        if (!move_uploaded_file($_FILES['video']['tmp_name'], $upload_path)) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao salvar o vídeo no servidor']);
            exit;
        }

        // Inserir no banco
        $sql = "
            INSERT INTO banco_imagem.bco_video 
            (url, thumb, titulo_pt, titulo_en, titulo_esp, description, autor, ativo, data_cadastro, cid, mneu_for)
            VALUES ($1, $2, $3, $4, $5, $6, $7, true, CURRENT_DATE, $8, $9)
            RETURNING pk_bco_video
        ";

        $params = [
            $url_final,
            $thumb_final,
            $titulo_pt,
            $titulo_en,
            $titulo_esp,
            $description,
            $autor,
            $cid,
            $mneu_for ?: null
        ];

        $res = pg_query_params($conn, $sql, $params);

        if (!$res) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao cadastrar vídeo: ' . pg_last_error($conn)]);
            exit;
        }

        $row = pg_fetch_assoc($res);

        echo json_encode([
            'success'      => true,
            'pk_bco_video' => $row['pk_bco_video'],
            'url'          => $url_final,
            'thumb'        => $thumb_final
        ]);
        break;


    // --------------------------------------------------
    // ATUALIZAR METADADOS DO VÍDEO (POST JSON)
    // --------------------------------------------------
    case 'update_video_metadata':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            exit;
        }

        $input = file_get_contents('php://input');
        $data  = json_decode($input, true);

        if (empty($data['pk_bco_video'])) {
            http_response_code(400);
            echo json_encode(['error' => 'pk_bco_video obrigatório']);
            exit;
        }

        $pk = (int)$data['pk_bco_video'];

        $updates = [];
        $params  = [];

        $fields = [
            'titulo_pt'    => 's',
            'titulo_en'    => 's',
            'titulo_esp'   => 's',
            'description'  => 's',
            'autor'        => 's',
            'ativo'        => 'b',
            'cid'          => 'i',
            'mneu_for'     => 's'
        ];

        foreach ($fields as $field => $type) {
            if (isset($data[$field])) {
                if ($type === 'b') {
                    $val = $data[$field] ? 'true' : 'false';
                    $updates[] = "$field = $val";
                } elseif ($type === 'i') {
                    $val = (int)$data[$field];
                    $updates[] = "$field = $val";
                } else {
                    $updates[] = "$field = $" . (count($params) + 1);
                    $params[]  = $data[$field];
                }
            }
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum campo para atualizar']);
            exit;
        }

        $sql = "UPDATE banco_imagem.bco_video SET " . implode(', ', $updates) . " WHERE pk_bco_video = $pk";

        $res = pg_query_params($conn, $sql, $params);

        if (!$res) {
            http_response_code(500);
            echo json_encode(['error' => pg_last_error($conn)]);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Metadados atualizados']);
        break;


    // --------------------------------------------------
    // DESATIVAR VÍDEO (soft delete)
    // --------------------------------------------------
    case 'delete_video':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            exit;
        }

        $input = file_get_contents('php://input');
        $data  = json_decode($input, true);

        if (empty($data['pk_bco_video'])) {
            http_response_code(400);
            echo json_encode(['error' => 'pk_bco_video obrigatório']);
            exit;
        }

        $pk = (int)$data['pk_bco_video'];

        $sql = "UPDATE banco_imagem.bco_video SET ativo = false WHERE pk_bco_video = $pk";

        $res = pg_query($conn, $sql);

        if (!$res || pg_affected_rows($res) === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Vídeo não encontrado ou já inativo']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Vídeo desativado com sucesso']);
        break;


    default:
        http_response_code(400);
        echo json_encode(['error' => "Ação inválida: $action"]);
        break;
}

pg_close($conn);