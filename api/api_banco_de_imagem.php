<?php

/**
 * API Única para Busca de Hotéis e Imagens
 *
 * Descrição:
 * Essa API unifica consultas de hotéis e imagens em um único endpoint.
 * Ela permite buscar hotéis por nome ou cidade, recuperar as imagens
 * associadas a um hotel específico e listar as cidades disponíveis.
 * Todos os retornos são em JSON.
 *
 * Endpoints:
 * - GET  ?action=search_hotels&query=rio
 *         → Busca hotéis por nome, produto ou palavra-chave.
 *
 * - GET  ?action=hotels_by_city&city=sao paulo
 *         → Retorna todos os hotéis de uma cidade específica.
 *
 * - GET  ?action=hotel_images&hotel_id=123
 *         → Retorna as imagens associadas a um hotel com base no campo mneu_for.
 *
 * - GET  ?action=list_cities
 *         → Lista todas as cidades com hotéis cadastrados.
 *
 * - GET  ?action=search_images&query=palavra
 *         → Busca imagens por legenda ou palavras-chave associadas.
 *
 * - GET  ?action=search_by_name&termo=praia
 *         → Busca imagens por nome do arquivo, legenda, autor ou palavras-chave.
 *
 * Métodos suportados:
 * - GET: search_hotels, hotels_by_city, hotel_images, list_cities, search_images, search_by_name
 *
 * Tabelas relacionadas:
 * - sbd95.fornec (dados cadastrais do hotel)
 * - conteudo_internet.ci_hotel (descrições e mídias)
 * - banco_imagem.bco_img (imagens associadas a hotéis e cidades)
 * - sbd95.cidades (lista de cidades)
 *
 * Retornos:
 * - 200: Sucesso
 * - 400: Parâmetro obrigatório ausente
 * - 404: Nenhum resultado encontrado
 * - 500: Erro interno do servidor
 *
 * Exemplo de resposta (GET ?action=search_hotels&query=rio):
 * [
 *   {
 *     "codigo": "HOTEL_RIO_PALACE",
 *     "nome": "Rio Palace Hotel",
 *     "cidade": "Rio de Janeiro",
 *     "categoria": "Hotel",
 *     "estrelas": 5,
 *     "descricao": "Hotel 5 estrelas próximo à praia de Copacabana.",
 *     "imagem_principal": "https://www.blumar.com.br/uploads/hoteis/fachadas/rio_palace.jpg"
 *   }
 * ]
 *
 * Exemplo de resposta (GET ?action=hotel_images&hotel_id=123):
 * [
 *   {
 *     "image_url": "https://www.blumar.com.br/uploads/hoteis/rio_palace_1.jpg",
 *     "legenda": "Vista da piscina",
 *     "palavras_chave": "piscina, luxo, lazer",
 *     "autor": "Gabriel Paiva"
 *   }
 * ]
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../util/connection.php';

if (!isset($_GET['action']) || empty(trim($_GET['action']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro "action" é obrigatório (search_hotels, hotels_by_city, hotel_images, list_cities, search_images)']);
    exit;
}

$action = strtolower(trim($_GET['action']));

switch ($action) {
    case 'search_hotels':
        if (!isset($_GET['query']) || empty(trim($_GET['query']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetro "query" é obrigatório']);
            exit;
        }

        $busca = pg_escape_string(strtolower(trim($_GET['query'])));

        $pega_htls = "SELECT DISTINCT banco_imagem.bco_img.mneu_for,
            sbd95.fornec.nome_for as nome_for,
            nome_produto
        FROM banco_imagem.bco_img
        INNER JOIN sbd95.fornec ON banco_imagem.bco_img.mneu_for = sbd95.fornec.mneu_for
        WHERE nome_produto ILIKE LOWER('%" . $busca . "%')
        ORDER BY nome_for";

        $result_htls = pg_exec($conn, $pega_htls);

        if (!$result_htls) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na consulta: ' . pg_last_error($conn)]);
            exit;
        }

        $hotels = [];
        for ($row = 0; $row < pg_numrows($result_htls); $row++) {
            $hotels[] = [
                'mneu_for' => pg_result($result_htls, $row, 'mneu_for'),
                'nome_for' => pg_result($result_htls, $row, 'nome_for'),
                'nome_produto' => pg_result($result_htls, $row, 'nome_produto')
            ];
        }

        echo json_encode([
            'action' => 'search_hotels',
            'total' => count($hotels),
            'query' => $busca,
            'hotels' => $hotels
        ]);
        break;
    case 'get_hotel_path':

        header('Content-Type: application/json; charset=utf-8');

        if (empty($_GET['mneu_for'])) {
            echo json_encode(['success' => false, 'error' => 'mneu_for obrigatório']);
            exit;
        }

        $mneu_for = pg_escape_string($_GET['mneu_for']);

        // pegamos um caminho REAL de qualquer imagem do hotel
        $sql = "
        SELECT 
            COALESCE(tam_4, tam_3, tam_2, tam_1, zip) AS caminho
        FROM banco_imagem.bco_img
        WHERE mneu_for = '$mneu_for'
        ORDER BY pk_bco_img ASC
        LIMIT 1
    ";

        $res = pg_query($conn, $sql);

        if (pg_num_rows($res) == 0) {
            echo json_encode(['success' => false, 'error' => 'Nenhuma imagem encontrada para este hotel']);
            exit;
        }

        $row = pg_fetch_assoc($res);
        $caminho = $row['caminho'];

        // remove prefixo
        $caminho = str_replace("bancoimagemfotos/", "", $caminho);

        // remove nome do arquivo
        $destino = preg_replace('#/[^/]+$#', '/', $caminho);

        echo json_encode([
            'success' => true,
            'destino' => $destino
        ]);
        exit;

    case 'get_image_path':

        header('Content-Type: application/json; charset=utf-8');

        if (empty($_GET['pk_bco_img'])) {
            echo json_encode(['success' => false, 'error' => 'pk_bco_img obrigatório']);
            exit;
        }

        $pk = (int)$_GET['pk_bco_img']; // sempre inteiro para segurança

        $sql = "
        SELECT 
            COALESCE(tam_4, tam_3, tam_2, tam_1, zip) AS arquivo_completo
        FROM banco_imagem.bco_img
        WHERE pk_bco_img = $pk
    ";

        $res = pg_query($conn, $sql);

        if (pg_num_rows($res) == 0) {
            echo json_encode(['success' => false, 'error' => 'Imagem não encontrada']);
            exit;
        }

        $row = pg_fetch_assoc($res);
        $caminho_completo = $row['arquivo_completo'];

        if (empty($caminho_completo)) {
            echo json_encode(['success' => false, 'error' => 'Nenhum arquivo associado a esta imagem']);
            exit;
        }

        // Remove o prefixo físico do servidor (ajuste conforme seu ambiente)
        // Exemplo: se no banco está "bancoimagemfotos/hotel/vila_de_alter/..." 
        // queremos só: hotel/vila_de_alter_pousada_boutique/imagens/nome_arquivo.jpg
        $arquivo_relativo = str_replace('bancoimagemfotos/', '', $caminho_completo);

        echo json_encode([
            'success' => true,
            'pk_bco_img' => $pk,
            'arquivo_relativo' => $arquivo_relativo,           // <-- isso que o JS precisa!
            'apenas_pasta'     => dirname($arquivo_relativo) . '/'  // opcional, se quiser usar depois
        ]);
        exit;
    case 'get_image_data':
        $pk = isset($_GET['pk_bco_img']) ? (int)$_GET['pk_bco_img'] : 0;

        // Query completa recuperando todos os dados necessários para a tela de visualização
        $sql = "
            SELECT 
                b.pk_bco_img,
                b.mneu_for,
                b.fk_cidcod,
                b.tam_1,
                b.tam_2,
                b.tam_3,
                b.tam_4,
                b.tam_5,
                b.zip,
                b.legenda,
                b.legenda_pt,
                b.legenda_esp,
                b.autor,
                b.origem,
                b.autorizacao,
                b.data_cadastro,
                b.palavras_chave,
                b.tp_produto,
                b.ativo_cli,
                b.nome_produto,
                b.av3,
                b.av,
                b.dt_validade,
                b.fachada,
                b.nacional,
                b.ordem,
                b.id_hotel,
                b.id_service,
                b.id_city,
                b.id_special_destination,
                -- Subquery para pegar nome do Fornecedor (Hotel)
                (SELECT nome_for FROM sbd95.fornec WHERE mneu_for = b.mneu_for) AS nome_for,
                -- Subquery para pegar nome do Hotel da tabela ci_hotel
                (SELECT nome_htl FROM conteudo_internet.ci_hotel WHERE mneu_for = b.mneu_for LIMIT 1) AS nome_htl,
                -- Subquery para pegar nome da Cidade
                (SELECT nome_en FROM tarifario.cidade_tpo WHERE cidade_cod = b.fk_cidcod) AS nome_cidade
            FROM banco_imagem.bco_img b
            WHERE b.pk_bco_img = $1
        ";

        $result = pg_query_params($conn, $sql, array($pk));

        if ($result && pg_num_rows($result) > 0) {
            $img = pg_fetch_assoc($result);
            
            // Lógica do Título (igual ao seu PHP original)
            $titulo = '';
            $prefixo = '';
            
            if ($img['tp_produto'] == '1') {
                $prefixo = 'Hotel';
                $titulo = !empty($img['nome_for']) ? $img['nome_for'] : $img['nome_htl'];
            } elseif ($img['tp_produto'] == '10') {
                $prefixo = 'City';
                $titulo = $img['nome_cidade'];
            }

            // Adiciona campos calculados ao JSON de resposta
            $img['display_titulo'] = $titulo;
            $img['display_prefixo'] = $prefixo;

            echo json_encode([
                'success' => true,
                'data' => $img
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Imagem não encontrada'
            ]);
        }
        break;




    // ================================================
    // TRANSFERIR / ALTERAR TIPO DE PRODUTO DA IMAGEM
    // Ex: ?action=transfer_image_type&pk_bco_img=12345&novo_tipo=5
    // ================================================
    case 'transfer_image_type':
    case 'update_image_product_type':

        header('Content-Type: application/json; charset=utf-8');

        // -----------------------------------------
        // 1. VALIDAR PARAMETROS
        // -----------------------------------------
        if (empty($_GET['pk_bco_img']) || !isset($_GET['novo_tipo'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parâmetros pk_bco_img e novo_tipo são obrigatórios']);
            exit;
        }

        $pk_bco_img     = (int)$_GET['pk_bco_img'];
        $novo_tipo      = (int)$_GET['novo_tipo'];
        $cidade_destino = !empty($_GET['cidade_destino']) ? pg_escape_string(trim($_GET['cidade_destino'])) : null;
        $mneu_for_destino = !empty($_GET['mneu_for_destino']) ? pg_escape_string($_GET['mneu_for_destino']) : null;
        $path_destino = !empty($_GET['path_destino']) ? pg_escape_string($_GET['path_destino']) : null;

        // -----------------------------------------
        // 2. BUSCA REGISTRO DA IMAGEM
        // -----------------------------------------
        $sql = "
        SELECT b.*, 
               c.nome_en AS cidade_nome, 
               c.cidade_cod, 
               f.nome_for
        FROM banco_imagem.bco_img b
        LEFT JOIN tarifario.cidade_tpo c ON b.fk_cidcod = c.cidade_cod
        LEFT JOIN sbd95.fornec f ON b.mneu_for = f.mneu_for
        WHERE b.pk_bco_img = $pk_bco_img
    ";

        $res = pg_query($conn, $sql);

        if (pg_num_rows($res) == 0) {
            echo json_encode(['success' => false, 'error' => 'Imagem não encontrada']);
            exit;
        }

        $img = pg_fetch_assoc($res);

        $logs = [];
        $erros = [];

        // NORMALIZA cidade
        $cidade_origem = strtolower(preg_replace('/[^a-z0-9_]/i', '_', trim($img['cidade_nome'])));

        // -----------------------------------------
        // 3. DEFINIR O NOVO PATH BASE
        // -----------------------------------------

        if ($novo_tipo == 10) {

            if (!$cidade_destino) {
                echo json_encode(['success' => false, 'error' => 'cidade_destino é obrigatório para tipo 10']);
                exit;
            }

            // Busca o cidade_cod pelo nome da cidade
            $sqlCid = "
            SELECT cidade_cod
            FROM tarifario.cidade_tpo
            WHERE nome_pt ILIKE '$cidade_destino'
            LIMIT 1
        ";

            $resCid = pg_query($conn, $sqlCid);
            if (pg_num_rows($resCid) == 0) {
                echo json_encode(['success' => false, 'error' => 'Cidade não encontrada: ' . $cidade_destino]);
                exit;
            }

            $rowCid = pg_fetch_assoc($resCid);
            $cidade_cod_destino = $rowCid['cidade_cod'];
        } else if ($novo_tipo == 1 && $mneu_for_destino) {
            // mneu_for_destino is the hotel CODE (max 12 chars), path_destino is for file paths
        } else {
            echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
            exit;
        }

        // -----------------------------------------
        // 4. BUSCAR CAMINHOS ATUAIS E CONSTRUIR NOVOS
        // -----------------------------------------
        $sqlPaths = "SELECT tam_1, tam_2, tam_3, tam_4, tam_5 FROM banco_imagem.bco_img WHERE pk_bco_img = $pk_bco_img";
        $resPaths = pg_query($conn, $sqlPaths);
        $paths = pg_fetch_assoc($resPaths);

        // Função auxiliar para atualizar caminho
        function atualizarCaminho($caminhoAntigo, $novo_tipo, $path_destino, $cidade_destino)
        {
            if (empty($caminhoAntigo)) return null;

            // Remove prefixo bancoimagemfotos se existir
            $caminhoAntigo = str_replace('bancoimagemfotos/', '', $caminhoAntigo);
            $nomeArquivo = basename($caminhoAntigo);

            if ($novo_tipo == 1) {
                // Movendo para HOTEL - $path_destino = "alta_floresta/cristalino_lodge"
                return "bancoimagemfotos/hotel/$path_destino/$nomeArquivo";
            } else if ($novo_tipo == 10) {
                // Movendo para CIDADE
                $cidadeSlug = strtolower(preg_replace('/[^a-z0-0_]/i', '_', $cidade_destino));
                return "bancoimagemfotos/cidade/$cidadeSlug/$nomeArquivo";
            }
            return $caminhoAntigo;
        }

        // Atualiza todos os caminhos
        $novos_caminhos = [];
        foreach (['tam_1', 'tam_2', 'tam_3', 'tam_4', 'tam_5'] as $campo) {
            if (!empty($paths[$campo])) {
                // Use $path_destino for file paths (full path), not $mneu_for_destino
                $novos_caminhos[$campo] = atualizarCaminho($paths[$campo], $novo_tipo, $path_destino, $cidade_destino);
            }
        }

        // -----------------------------------------
        // 5. ATUALIZAR TIPO PRODUTO E RELACIONAMENTOS
        // -----------------------------------------
        pg_query($conn, "UPDATE banco_imagem.bco_img SET tp_produto = $novo_tipo WHERE pk_bco_img = $pk_bco_img");

        if ($novo_tipo == 10) {
            // Movendo para CIDADE: atualiza fk_cidcod e limpa mneu_for
            pg_query($conn, "UPDATE banco_imagem.bco_img SET fk_cidcod = $cidade_cod_destino, mneu_for = NULL WHERE pk_bco_img = $pk_bco_img");
        } else if ($novo_tipo == 1) {
            // Movendo para HOTEL: atualiza mneu_for com o código do hotel (max 12 chars)
            // $mneu_for_destino = hotel code, NOT the full path
            pg_query($conn, "UPDATE banco_imagem.bco_img SET mneu_for = '$mneu_for_destino' WHERE pk_bco_img = $pk_bco_img");
        }

        // -----------------------------------------
        // 6. ATUALIZAR CAMINHOS DOS ARQUIVOS
        // -----------------------------------------
        if (!empty($novos_caminhos)) {
            $updates_caminhos = [];
            foreach ($novos_caminhos as $campo => $valor) {
                $updates_caminhos[] = "$campo = '" . pg_escape_string($valor) . "'";
            }
            if (!empty($updates_caminhos)) {
                $sql_update_paths = "UPDATE banco_imagem.bco_img SET " . implode(', ', $updates_caminhos) . " WHERE pk_bco_img = $pk_bco_img";
                pg_query($conn, $sql_update_paths);
            }
        }

        echo json_encode([
            "success" => true,
            "pk_bco_img" => $pk_bco_img,
            "tipo_novo" => $novo_tipo,
            "cidade_destino" => $novo_tipo == 10 ? $cidade_destino : null,
            "mneu_for_code" => $novo_tipo == 1 ? $mneu_for_destino : null,
            "path_destino" => $novo_tipo == 1 ? $path_destino : null,
            "caminhos_atualizados" => $novos_caminhos
        ]);

        break;



    case 'transfer_image_type_cidade_para_hotel':
        header('Content-Type: application/json; charset=utf-8');
        // -----------------------------------------
        // 1. VALIDAR PARAMETROS
        // -----------------------------------------
        if (empty($_GET['pk_bco_img']) || !isset($_GET['novo_tipo'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parâmetros pk_bco_img e novo_tipo são obrigatórios']);
            exit;
        }

        $pk_bco_img     = (int)$_GET['pk_bco_img'];
        $novo_tipo      = (int)$_GET['novo_tipo'];
        $cidade_destino = !empty($_GET['cidade_destino']) ? pg_escape_string(trim($_GET['cidade_destino'])) : null;
        $mneu_for_destino = !empty($_GET['mneu_for_destino']) ? pg_escape_string($_GET['mneu_for_destino']) : null;
        $path_destino = !empty($_GET['path_destino']) ? pg_escape_string($_GET['path_destino']) : null;

        // -----------------------------------------
        // 2. BUSCA REGISTRO DA IMAGEM
        // -----------------------------------------
        $sql = "
        SELECT b.*, 
               c.nome_en AS cidade_nome, 
               c.cidade_cod, 
               f.nome_for
        FROM banco_imagem.bco_img b
        LEFT JOIN tarifario.cidade_tpo c ON b.fk_cidcod = c.cidade_cod
        LEFT JOIN sbd95.fornec f ON b.mneu_for = f.mneu_for
        WHERE b.pk_bco_img = $pk_bco_img
    ";

        $res = pg_query($conn, $sql);

        if (pg_num_rows($res) == 0) {
            echo json_encode(['success' => false, 'error' => 'Imagem não encontrada']);
            exit;
        }

        $img = pg_fetch_assoc($res);

        $logs = [];
        $erros = [];

        // NORMALIZA cidade
        $cidade_origem = strtolower(preg_replace('/[^a-z0-9_]/i', '_', trim($img['cidade_nome'])));

        // -----------------------------------------
        // 3. DEFINIR O NOVO PATH BASE
        // -----------------------------------------

        if ($novo_tipo == 10) {

            if (!$cidade_destino) {
                echo json_encode(['success' => false, 'error' => 'cidade_destino é obrigatório para tipo 10']);
                exit;
            }

            // Busca o cidade_cod pelo nome da cidade
            $sqlCid = "
            SELECT cidade_cod
            FROM tarifario.cidade_tpo
            WHERE nome_pt ILIKE '$cidade_destino'
            LIMIT 1
        ";

            $resCid = pg_query($conn, $sqlCid);
            if (pg_num_rows($resCid) == 0) {
                echo json_encode(['success' => false, 'error' => 'Cidade não encontrada: ' . $cidade_destino]);
                exit;
            }

            $rowCid = pg_fetch_assoc($resCid);
            $cidade_cod_destino = $rowCid['cidade_cod'];
        } else if ($novo_tipo == 1 && $mneu_for_destino) {
            // mneu_for_destino is the hotel CODE (max 12 chars), path_destino is for file paths
        } else {
            echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
            exit;
        }

        // -----------------------------------------
        // 4. BUSCAR CAMINHOS ATUAIS E CONSTRUIR NOVOS
        // -----------------------------------------
        $sqlPaths = "SELECT tam_1, tam_2, tam_3, tam_4, tam_5 FROM banco_imagem.bco_img WHERE pk_bco_img = $pk_bco_img";
        $resPaths = pg_query($conn, $sqlPaths);
        $paths = pg_fetch_assoc($resPaths);

        // Função auxiliar para atualizar caminho
        function atualizarCaminho($caminhoAntigo, $novo_tipo, $path_destino, $cidade_destino)
        {
            if (empty($caminhoAntigo)) return null;

            // Remove prefixo bancoimagemfotos se existir
            $caminhoAntigo = str_replace('bancoimagemfotos/', '', $caminhoAntigo);
            $nomeArquivo = basename($caminhoAntigo);

            if ($novo_tipo == 1) {
                // Movendo para HOTEL - $path_destino = "alta_floresta/cristalino_lodge"
                return "bancoimagemfotos/hotel/$path_destino/$nomeArquivo";
            } else if ($novo_tipo == 10) {
                // Movendo para CIDADE
                $cidadeSlug = strtolower(preg_replace('/[^a-z0-0_]/i', '_', $cidade_destino));
                return "bancoimagemfotos/cidade/$cidadeSlug/$nomeArquivo";
            }
            return $caminhoAntigo;
        }

        // Atualiza todos os caminhos
        $novos_caminhos = [];
        foreach (['tam_1', 'tam_2', 'tam_3', 'tam_4', 'tam_5'] as $campo) {
            if (!empty($paths[$campo])) {
                // Use $path_destino for file paths (full path), not $mneu_for_destino
                $novos_caminhos[$campo] = atualizarCaminho($paths[$campo], $novo_tipo, $path_destino, $cidade_destino);
            }
        }

        // -----------------------------------------
        // 5. ATUALIZAR TIPO PRODUTO E RELACIONAMENTOS
        // -----------------------------------------
        pg_query($conn, "UPDATE banco_imagem.bco_img SET tp_produto = $novo_tipo WHERE pk_bco_img = $pk_bco_img");

        if ($novo_tipo == 10) {
            // Movendo para CIDADE: atualiza fk_cidcod e limpa mneu_for
            pg_query($conn, "UPDATE banco_imagem.bco_img SET fk_cidcod = $cidade_cod_destino, mneu_for = NULL WHERE pk_bco_img = $pk_bco_img");
        } else if ($novo_tipo == 1) {
            // Movendo para HOTEL: atualiza mneu_for com o código do hotel (max 12 chars)
            // $mneu_for_destino = hotel code, NOT the full path
            pg_query($conn, "UPDATE banco_imagem.bco_img SET mneu_for = '$mneu_for_destino' WHERE pk_bco_img = $pk_bco_img");
        }

        // -----------------------------------------
        // 6. ATUALIZAR CAMINHOS DOS ARQUIVOS
        // -----------------------------------------
        if (!empty($novos_caminhos)) {
            $updates_caminhos = [];
            foreach ($novos_caminhos as $campo => $valor) {
                $updates_caminhos[] = "$campo = '" . pg_escape_string($valor) . "'";
            }
            if (!empty($updates_caminhos)) {
                $sql_update_paths = "UPDATE banco_imagem.bco_img SET " . implode(', ', $updates_caminhos) . " WHERE pk_bco_img = $pk_bco_img";
                pg_query($conn, $sql_update_paths);
            }
        }

        echo json_encode([
            "success" => true,
            "pk_bco_img" => $pk_bco_img,
            "tipo_novo" => $novo_tipo,
            "cidade_destino" => $novo_tipo == 10 ? $cidade_destino : null,
            "mneu_for_code" => $novo_tipo == 1 ? $mneu_for_destino : null,
            "path_destino" => $novo_tipo == 1 ? $path_destino : null,
            "caminhos_atualizados" => $novos_caminhos
        ]);

        break;

    case 'testar_caminho_imagem':

        header('Content-Type: application/json; charset=utf-8');

        if (empty($_GET['path'])) {
            echo json_encode(["error" => "Informe ?path=/caminho/no/banco"]);
            exit;
        }

        $base_path = '\\\\127.0.0.1\\Z$\\wwwinternet\\bancoimagemfotos\\';

        // Caminho vindo do banco (ex: hotel/angra/vila_gale/apto1.jpg)
        $path_banco = trim($_GET['path']);

        // Remove possível duplicação
        $path_banco = ltrim(str_replace('bancoimagemfotos', '', $path_banco), '/\\');

        // Converte para caminho UNC
        $caminho_fisico = $base_path . str_replace('/', '\\', $path_banco);

        $existe = file_exists($caminho_fisico);

        echo json_encode([
            "path_banco"     => $path_banco,
            "caminho_fisico" => $caminho_fisico,
            "existe"         => $existe ? "SIM" : "NÃO"
        ]);

        break;

    case 'listar_pasta':

        header('Content-Type: application/json; charset=utf-8');

        if (empty($_GET['folder'])) {
            echo json_encode(['success' => false, 'error' => 'Parâmetro "folder" é obrigatório']);
            exit;
        }

        // Pasta pedida pelo usuário
        $folder = trim($_GET['folder'], "/\\");

        // Caminho base real no servidor
        $base_path = '\\\\127.0.0.1\\Z$\\wwwinternet\\bancoimagemfotos\\';

        // Monta caminho físico completo
        $folder_real = $base_path . str_replace('/', '\\', $folder) . '\\';

        // Verifica se existe
        if (!is_dir($folder_real)) {
            echo json_encode([
                'success' => false,
                'error' => 'Pasta não encontrada no disco',
                'folder_real' => $folder_real
            ]);
            exit;
        }

        // Lê arquivos
        $arquivos = [];
        $it = new DirectoryIterator($folder_real);

        foreach ($it as $fileinfo) {
            if ($fileinfo->isDot()) continue;

            if ($fileinfo->isFile()) {
                $arquivos[] = $fileinfo->getFilename();
            }
        }

        echo json_encode([
            'success' => true,
            'folder' => $folder,
            'folder_real' => $folder_real,
            'total' => count($arquivos),
            'arquivos' => $arquivos
        ]);

        break;


    case 'listar_pastas_cidade':
        header('Content-Type: application/json; charset=utf-8');

        $base = 'https://www.blumar.com.br/bancoimagemfotos/cidade/';

        if (!is_dir($base)) {
            echo json_encode(['error' => 'Pasta cidade não encontrada']);
            exit;
        }

        $pastas = array();

        $dirs = scandir($base);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || !is_dir($base . $dir)) {
                continue;
            }

            $arquivos = glob($base . $dir . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
            $total = is_array($arquivos) ? count($arquivos) : 0;

            $pastas[] = array(
                'nome'           => $dir,
                'caminho'        => $dir,
                'total_arquivos' => $total
            );
        }

        // Ordenação alfabética (PHP 7.2)
        $nomes = array();
        foreach ($pastas as $chave => $pasta) {
            $nomes[$chave] = $pasta['nome'];
        }
        array_multisort($nomes, SORT_STRING | SORT_FLAG_CASE, $pastas);

        echo json_encode(array(
            'success'      => true,
            'total_pastas' => count($pastas),
            'pastas'       => $pastas
        ), JSON_UNESCAPED_UNICODE);

        break;

    case 'imagens_cidade':
        if (!isset($_GET['cidade_cod'])) {
            http_response_code(400);
            echo json_encode(['error' => 'cidade_cod obrigatório']);
            exit;
        }

        $cod = pg_escape_string($_GET['cidade_cod']);

        $sql = "SELECT pk_bco_img, tam_1, tam_2, tam_3, tam_4, legenda, tp_produto
            FROM banco_imagem.bco_img
            WHERE fk_cidcod = '$cod' AND tp_produto = '10'
            ORDER BY pk_bco_img DESC";

        $res = pg_query($conn, $sql);
        $images = [];

        while ($row = pg_fetch_assoc($res)) {
            $img = [
                'pk_bco_img' => $row['pk_bco_img'],
                'tp_produto' => $row['tp_produto'],
                'urls' => []
            ];
            foreach (['tam_4', 'tam_3', 'tam_2', 'tam_1'] as $t) {
                if (!empty($row[$t])) {
                    $img['urls'][$t] = 'https://www.blumar.com.br/' . str_replace(' ', '%20', $row[$t]);
                }
            }
            $images[] = $img;
        }

        echo json_encode(['images' => $images]);
        break;
    case 'hotels_by_city':
        $cidade_busca = isset($_GET['city']) ? trim($_GET['city']) : '';
        $cidade_cod_in = isset($_GET['cidade_cod']) ? trim($_GET['cidade_cod']) : '';

        if (empty($cidade_busca) && empty($cidade_cod_in)) {
            http_response_code(400);
            echo json_encode(['error' => 'Par?metro "city" ou "cidade_cod" ? obrigat?rio']);
            exit;
        }

        $fk_cidcod = null;

        if (!empty($cidade_cod_in)) {
            $fk_cidcod = pg_escape_string($cidade_cod_in);
        } else {
            $cidade_busca = pg_escape_string(strtolower($cidade_busca));

            // Passo 1: Buscar fk_cidcod da cidade
            $pega_cidade = "SELECT DISTINCT cidade_cod
        FROM tarifario.cidade_tpo
        WHERE nome_en ILIKE LOWER('%" . $cidade_busca . "%')
        LIMIT 1";

            $result_cidade = pg_exec($conn, $pega_cidade);
            if (!$result_cidade || pg_numrows($result_cidade) == 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Cidade n?o encontrada']);
                exit;
            }

            $fk_cidcod = pg_result($result_cidade, 0, 'cidade_cod');
        }

        // Passo 2: Buscar hot?is associados ? cidade
        $pega_htls_cidade = "SELECT DISTINCT b.mneu_for,
            f.nome_for,
            b.nome_produto
        FROM banco_imagem.bco_img b
        INNER JOIN sbd95.fornec f ON b.mneu_for = f.mneu_for
        WHERE b.fk_cidcod = '" . $fk_cidcod . "'
        AND b.tp_produto != '10'  -- Assumindo que '10' ? para cidades/imagens gen?ricas; ajuste se necess?rio
        ORDER BY f.nome_for";

        $result_htls = pg_exec($conn, $pega_htls_cidade);

        if (!$result_htls) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na consulta: ' . pg_last_error($conn)]);
            exit;
        }

        $hotels = [];
        for ($row = 0; $row < pg_numrows($result_htls); $row++) {
            $hotels[] = [
                'mneu_for' => pg_result($result_htls, $row, 'mneu_for'),
                'nome_for' => pg_result($result_htls, $row, 'nome_for'),
                'nome_produto' => pg_result($result_htls, $row, 'nome_produto'),
                'cidade_cod' => $fk_cidcod
            ];
        }

        echo json_encode([
            'action' => 'hotels_by_city',
            'total' => count($hotels),
            'city' => $cidade_busca,
            'cidade_cod' => $fk_cidcod,
            'hotels' => $hotels
        ]);
        break;

    case 'hotel_images':
        if (!isset($_GET['hotel_id']) || empty(trim($_GET['hotel_id']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetro "hotel_id" (mneu_for) é obrigatório']);
            exit;
        }

        $mneu_for = pg_escape_string(trim($_GET['hotel_id']));

        $pega_img_htl = "SELECT pk_bco_img, mneu_for, tam_1, tam_2, tam_3, tam_4, tam_5, zip, legenda, autor, ordem, nacional, fachada
        FROM banco_imagem.bco_img
        WHERE mneu_for = '" . $mneu_for . "'
        AND tp_produto = '1'
        ORDER BY ordem, legenda";

        $result_img_htl = pg_exec($conn, $pega_img_htl);

        if (!$result_img_htl) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na consulta: ' . pg_last_error($conn)]);
            exit;
        }

        $images = [];
        for ($row = 0; $row < pg_numrows($result_img_htl); $row++) {
            $tam_1 = pg_result($result_img_htl, $row, 'tam_1');
            $tam_2 = pg_result($result_img_htl, $row, 'tam_2');
            $tam_3 = pg_result($result_img_htl, $row, 'tam_3');
            $tam_4 = pg_result($result_img_htl, $row, 'tam_4');
            $tam_5 = pg_result($result_img_htl, $row, 'tam_5');

            // Processa URLs (como no seu código)
            $base_url = 'https://www.blumar.com.br/';
            $img_data = [
                'pk_bco_img' => pg_result($result_img_htl, $row, 'pk_bco_img'),
                'tp_produto' => '1',
                'legenda' => pg_result($result_img_htl, $row, 'legenda'),
                'autor' => pg_result($result_img_htl, $row, 'autor'),
                'ordem' => pg_result($result_img_htl, $row, 'ordem'),
                'nacional' => pg_result($result_img_htl, $row, 'nacional'),
                'fachada' => pg_result($result_img_htl, $row, 'fachada'),
                'urls' => []
            ];

            if (!empty($tam_4)) {
                $tam_4_clean = str_replace(' ', '%20', $tam_4);
                $img_data['urls']['tam_4'] = $base_url . $tam_4_clean;
            }
            if (!empty($tam_3)) {
                $tam_3_clean = str_replace(' ', '%20', $tam_3);
                $img_data['urls']['tam_3'] = $base_url . $tam_3_clean;
            }
            if (!empty($tam_2)) {
                $tam_2_clean = str_replace(' ', '%20', $tam_2);
                $img_data['urls']['tam_2'] = $base_url . $tam_2_clean;
            }
            if (!empty($tam_1)) {
                $tam_1_clean = str_replace(' ', '%20', $tam_1);
                $img_data['urls']['tam_1'] = $base_url . $tam_1_clean;
            }

            // Preview URL (prioriza maior)
            $img_data['preview_url'] = $img_data['urls']['tam_4'] ??
                $img_data['urls']['tam_3'] ??
                $img_data['urls']['tam_2'] ??
                $img_data['urls']['tam_1'] ??
                'https://via.placeholder.com/600x400?text=Sem+Imagem';

            $images[] = $img_data;
        }

        echo json_encode([
            'action' => 'hotel_images',
            'hotel_id' => $mneu_for,
            'total_images' => count($images),
            'images' => $images
        ]);
        break;

    case 'list_cities':
        $pega_cidades = "SELECT cidade_cod, nome_en
        FROM tarifario.cidade_tpo
        ORDER BY nome_en";

        $result_cidades = pg_exec($conn, $pega_cidades);

        if (!$result_cidades) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na consulta: ' . pg_last_error($conn)]);
            exit;
        }

        $cities = [];
        for ($row = 0; $row < pg_numrows($result_cidades); $row++) {
            $cities[] = [
                'cidade_cod' => pg_result($result_cidades, $row, 'cidade_cod'),
                'nome_en' => pg_result($result_cidades, $row, 'nome_en')
            ];
        }

        echo json_encode([
            'action' => 'list_cities',
            'total' => count($cities),
            'cities' => $cities
        ]);
        break;

    case 'search_images':
        if (!isset($_GET['query']) || empty(trim($_GET['query']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetro "query" é obrigatório']);
            exit;
        }

        $busca = pg_escape_string(strtolower(trim($_GET['query'])));

        // Busca em legenda OU palavras_chave (ILIKE para case-insensitive e partial match)
        $pega_img_general = "SELECT pk_bco_img, tam_1, tam_2, tam_3, tam_4, legenda, palavras_chave, tp_produto
        FROM banco_imagem.bco_img
        WHERE (legenda ILIKE LOWER('%" . $busca . "%') OR palavras_chave ILIKE LOWER('%" . $busca . "%'))
        ORDER BY legenda
        LIMIT 20";  // Limite para resultados; ajuste se necessário

        $result_img_general = pg_exec($conn, $pega_img_general);

        if (!$result_img_general) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na consulta: ' . pg_last_error($conn)]);
            exit;
        }

        $images = [];
        for ($row = 0; $row < pg_numrows($result_img_general); $row++) {
            $tam_1 = pg_result($result_img_general, $row, 'tam_1');
            $tam_2 = pg_result($result_img_general, $row, 'tam_2');
            $tam_3 = pg_result($result_img_general, $row, 'tam_3');
            $tam_4 = pg_result($result_img_general, $row, 'tam_4');
            $legenda = pg_result($result_img_general, $row, 'legenda');
            $palavras_chave = pg_result($result_img_general, $row, 'palavras_chave');
            $tp_produto = pg_result($result_img_general, $row, 'tp_produto');

            // Processa URLs (similar ao hotel_images)
            $base_url = 'https://www.blumar.com.br/';
            $img_data = [
                'pk_bco_img' => pg_result($result_img_general, $row, 'pk_bco_img'),
                'legenda' => $legenda,
                'palavras_chave' => $palavras_chave,
                'tp_produto' => $tp_produto,
                'urls' => []
            ];

            if (!empty($tam_4)) {
                $tam_4_clean = str_replace(' ', '%20', $tam_4);
                $img_data['urls']['tam_4'] = $base_url . $tam_4_clean;
            }
            if (!empty($tam_3)) {
                $tam_3_clean = str_replace(' ', '%20', $tam_3);
                $img_data['urls']['tam_3'] = $base_url . $tam_3_clean;
            }
            if (!empty($tam_2)) {
                $tam_2_clean = str_replace(' ', '%20', $tam_2);
                $img_data['urls']['tam_2'] = $base_url . $tam_2_clean;
            }
            if (!empty($tam_1)) {
                $tam_1_clean = str_replace(' ', '%20', $tam_1);
                $img_data['urls']['tam_1'] = $base_url . $tam_1_clean;
            }

            $images[] = $img_data;
        }

        echo json_encode([
            'action' => 'search_images',
            'total_images' => count($images),
            'query' => $busca,
            'images' => $images
        ]);
        break;

    // ================================================
    // NOVO: IMAGENS GENÉRICAS DA CIDADE (tp_produto = 10)
    // Ex: ?action=city_generic_images&cidade_cod=50
    // ================================================
    case 'city_generic_images':
        if (!isset($_GET['cidade_cod']) || empty(trim($_GET['cidade_cod']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetro "cidade_cod" é obrigatório']);
            exit;
        }

        $cidade_cod = pg_escape_string(trim($_GET['cidade_cod']));

        // Busca todas as imagens da cidade com tp_produto = 10
        $sql = "SELECT pk_bco_img, tam_1, tam_2, tam_3, tam_4, legenda, palavras_chave
            FROM banco_imagem.bco_img
            WHERE 
              AND tp_produto = '10'
            ORDER BY pk_bco_img DESC";

        $result = pg_query($conn, $sql);

        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na consulta: ' . pg_last_error($conn)]);
            exit;
        }

        $images = [];
        while ($row = pg_fetch_assoc($result)) {
            $img = [
                'pk_bco_img'     => $row['pk_bco_img'],
                'legenda'        => $row['legenda'] ?? '',
                'palavras_chave' => $row['palavras_chave'] ?? '',
                'tp_produto'     => '10',
                'urls'           => []
            ];

            // Monta URLs reais no formato correto: /bancoimagemfotos/cidade/nomecidade/arquivo.jpg
            foreach (['tam_4', 'tam_3', 'tam_2', 'tam_1'] as $tam) {
                if (!empty($row[$tam])) {
                    $path = trim($row[$tam]);
                    // Remove espaços e garante barra inicial
                    $path = ltrim(str_replace(' ', '%20', $path), '/');
                    $img['urls'][$tam] = "https://www.blumar.com.br/" . $path;
                }
            }

            // Prioriza a maior disponível
            $img['preview_url'] = $img['urls']['tam_4'] ??
                $img['urls']['tam_3'] ??
                $img['urls']['tam_2'] ??
                $img['urls']['tam_1'] ??
                'https://via.placeholder.com/600x400?text=Sem+Imagem';

            $images[] = $img;
        }

        echo json_encode([
            'action'      => 'city_generic_images',
            'cidade_cod'  => $cidade_cod,
            'total'       => count($images),
            'images'      => $images
        ]);
        break;

    case 'update_metadata':
        header('Content-Type: application/json; charset=utf-8');

        // L? o JSON do corpo da requisi??o
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (empty($data['pk_bco_img'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'pk_bco_img ? obrigat?rio']);
            exit;
        }

        $pk = (int)$data['pk_bco_img'];
        $tp_produto = isset($data['tp_produto']) ? (int)$data['tp_produto'] : null;
        $legenda = isset($data['legenda']) ? pg_escape_string(trim($data['legenda'])) : null;
        $legenda_pt = isset($data['legenda_pt']) ? pg_escape_string(trim($data['legenda_pt'])) : null;
        $legenda_esp = isset($data['legenda_esp']) ? pg_escape_string(trim($data['legenda_esp'])) : null;
        $autor = isset($data['autor']) ? pg_escape_string(trim($data['autor'])) : null;
        $origem = isset($data['origem']) ? pg_escape_string(trim($data['origem'])) : null;
        $autorizacao = isset($data['autorizacao']) ? pg_escape_string(trim($data['autorizacao'])) : null;
        $palavras_chave = isset($data['palavras_chave']) ? pg_escape_string(trim($data['palavras_chave'])) : null;
        $nome_produto = isset($data['nome_produto']) ? pg_escape_string(trim($data['nome_produto'])) : null;
        $id_hotel = isset($data['id_hotel']) ? pg_escape_string(trim($data['id_hotel'])) : null; // mneu_for
        $id_hotel_ref = isset($data['id_hotel_ref']) ? (int)$data['id_hotel_ref'] : null;
        $id_service = isset($data['id_service']) ? (int)$data['id_service'] : null;
        $id_city = isset($data['id_city']) ? (int)$data['id_city'] : null;
        $id_special_destination = isset($data['id_special_destination']) ? (int)$data['id_special_destination'] : null;
        $fk_cidcod = isset($data['fk_cidcod']) ? (int)$data['fk_cidcod'] : null;
        $ordem = isset($data['ordem']) ? (int)$data['ordem'] : null;
        $data_cadastro = isset($data['data_cadastro']) ? pg_escape_string(trim($data['data_cadastro'])) : null;
        $dt_validade = isset($data['dt_validade']) ? pg_escape_string(trim($data['dt_validade'])) : null;
        $novo_caminho = isset($data['novo_caminho']) ? pg_escape_string(trim($data['novo_caminho'])) : null;
        $tam_1 = isset($data['tam_1']) ? pg_escape_string(trim($data['tam_1'])) : null;
        $tam_2 = isset($data['tam_2']) ? pg_escape_string(trim($data['tam_2'])) : null;
        $tam_3 = isset($data['tam_3']) ? pg_escape_string(trim($data['tam_3'])) : null;
        $tam_4 = isset($data['tam_4']) ? pg_escape_string(trim($data['tam_4'])) : null;
        $tam_5 = isset($data['tam_5']) ? pg_escape_string(trim($data['tam_5'])) : null;
        $zip = isset($data['zip']) ? pg_escape_string(trim($data['zip'])) : null;

        $normalize_bool = function ($value) {
            if ($value === null) return null;
            if ($value === true || $value === 1 || $value === '1' || $value === 't' || $value === 'true') return 't';
            return 'f';
        };

        $fachada = isset($data['fachada']) ? $normalize_bool($data['fachada']) : null;
        $nacional = isset($data['nacional']) ? $normalize_bool($data['nacional']) : null;
        $ativo_cli = isset($data['ativo_cli']) ? $normalize_bool($data['ativo_cli']) : null;
        $av = isset($data['av']) ? $normalize_bool($data['av']) : null;
        $av3 = isset($data['av3']) ? $normalize_bool($data['av3']) : null;

        // Monta o UPDATE dinamicamente
        $updates = [];
        if ($tp_produto !== null) $updates[] = "tp_produto = $tp_produto";
        if ($legenda !== null) $updates[] = "legenda = '$legenda'";
        if ($legenda_pt !== null) $updates[] = "legenda_pt = '$legenda_pt'";
        if ($legenda_esp !== null) $updates[] = "legenda_esp = '$legenda_esp'";
        if ($autor !== null) $updates[] = "autor = '$autor'";
        if ($origem !== null) $updates[] = "origem = '$origem'";
        if ($autorizacao !== null) $updates[] = "autorizacao = '$autorizacao'";
        if ($palavras_chave !== null) $updates[] = "palavras_chave = '$palavras_chave'";
        if ($nome_produto !== null) $updates[] = "nome_produto = '$nome_produto'";
        if ($ordem !== null) $updates[] = "ordem = $ordem";
        if ($fachada !== null) $updates[] = "fachada = '$fachada'";
        if ($nacional !== null) $updates[] = "nacional = '$nacional'";
        if ($ativo_cli !== null) $updates[] = "ativo_cli = '$ativo_cli'";
        if ($av !== null) $updates[] = "av = '$av'";
        if ($av3 !== null) $updates[] = "av3 = '$av3'";
        if ($id_hotel !== null) $updates[] = "mneu_for = '$id_hotel'";
        if ($id_hotel_ref !== null) $updates[] = "id_hotel = $id_hotel_ref";
        if ($id_service !== null) $updates[] = "id_service = $id_service";
        if ($id_city !== null) $updates[] = "id_city = $id_city";
        if ($id_special_destination !== null) $updates[] = "id_special_destination = $id_special_destination";
        if ($fk_cidcod !== null) $updates[] = "fk_cidcod = $fk_cidcod";
        if ($data_cadastro !== null && $data_cadastro !== '') $updates[] = "data_cadastro = '$data_cadastro'";
        if ($dt_validade !== null && $dt_validade !== '') $updates[] = "dt_validade = '$dt_validade'";
        if ($tam_1 !== null) $updates[] = "tam_1 = '$tam_1'";
        if ($tam_2 !== null) $updates[] = "tam_2 = '$tam_2'";
        if ($tam_3 !== null) $updates[] = "tam_3 = '$tam_3'";
        if ($tam_4 !== null) $updates[] = "tam_4 = '$tam_4'";
        if ($tam_5 !== null) $updates[] = "tam_5 = '$tam_5'";
        if ($zip !== null) $updates[] = "zip = '$zip'";

        // Se novo_caminho foi fornecido, atualiza todos os campos de tamanho
        if ($novo_caminho !== null && !empty($novo_caminho)) {
            // Adiciona o prefixo bancoimagemfotos/ se n?o existir
            if (strpos($novo_caminho, 'bancoimagemfotos/') !== 0) {
                $novo_caminho = 'bancoimagemfotos/' . $novo_caminho;
            }

            // Atualiza todos os tamanhos com o mesmo caminho
            $updates[] = "tam_1 = '$novo_caminho'";
            $updates[] = "tam_2 = '$novo_caminho'";
            $updates[] = "tam_3 = '$novo_caminho'";
            $updates[] = "tam_4 = '$novo_caminho'";
            $updates[] = "tam_5 = '$novo_caminho'";
            $updates[] = "zip = '$novo_caminho'";
        }

        if (empty($updates)) {
            echo json_encode(['success' => false, 'error' => 'Nenhum campo para atualizar']);
            exit;
        }

        $sql = "UPDATE banco_imagem.bco_img SET " . implode(', ', $updates) . " WHERE pk_bco_img = $pk";

        $result = pg_query($conn, $sql);

        if ($result) {
            echo json_encode([
                'success' => true,
                'pk_bco_img' => $pk,
                'message' => 'Metadados atualizados com sucesso'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao atualizar: ' . pg_last_error($conn)
            ]);
        }
        break;

    // ================================================
    // BUSCAR IMAGENS POR NOME DO ARQUIVO/LEGENDA/AUTOR
    // Ex: ?action=search_by_name&termo=praia
    // Busca em: tam_1, tam_2, tam_3, tam_4, legenda, autor, palavras_chave
    // ================================================
    case 'search_by_name':
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_GET['termo']) || empty(trim($_GET['termo']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Par?metro "termo" ? obrigat?rio']);
            exit;
        }

        $termo = pg_escape_string(strtolower(trim($_GET['termo'])));
        $termo_like = '%' . $termo . '%';

        // Busca em legenda, autor, palavras-chave, caminho, hotel e cidade
        $sql = "SELECT
                    b.mneu_for,
                    b.pk_bco_img,
                    b.tp_produto,
                    b.legenda,
                    b.autor,
                    b.ordem,
                    b.fachada,
                    b.nacional,
                    b.palavras_chave,
                    b.fk_cidcod,
                    b.tam_1,
                    b.tam_2,
                    b.tam_3,
                    b.tam_4,
                    COALESCE(b.tam_4, b.tam_3, b.tam_2, b.tam_1) as caminho_principal,
                    f.nome_for AS nome_hotel,
                    c.nome_en AS nome_cidade
                FROM banco_imagem.bco_img b
                LEFT JOIN sbd95.fornec f ON b.mneu_for = f.mneu_for
                LEFT JOIN tarifario.cidade_tpo c ON b.fk_cidcod = c.cidade_cod
                WHERE (
                    LOWER(b.legenda) LIKE LOWER('$termo_like')
                    OR LOWER(b.autor) LIKE LOWER('$termo_like')
                    OR LOWER(b.palavras_chave) LIKE LOWER('$termo_like')
                    OR LOWER(b.tam_1) LIKE LOWER('$termo_like')
                    OR LOWER(b.tam_2) LIKE LOWER('$termo_like')
                    OR LOWER(b.tam_3) LIKE LOWER('$termo_like')
                    OR LOWER(b.tam_4) LIKE LOWER('$termo_like')
                    OR LOWER(COALESCE(b.tam_4, b.tam_3, b.tam_2, b.tam_1)) LIKE LOWER('$termo_like')
                    OR LOWER(f.nome_for) LIKE LOWER('$termo_like')
                    OR LOWER(c.nome_en) LIKE LOWER('$termo_like')
                )
                ORDER BY b.ordem ASC, b.legenda ASC
                LIMIT 200";

        $result = pg_query($conn, $sql);

        if (!$result) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro na consulta: ' . pg_last_error($conn)
            ]);
            exit;
        }

        $images = [];
        $base_url = 'https://www.blumar.com.br/';

        while ($row = pg_fetch_assoc($result)) {
            $img = [
                'mneu_for' => $row['mneu_for'],
                'fk_cidcod' => $row['fk_cidcod'],
                'pk_bco_img' => $row['pk_bco_img'],
                'tp_produto' => $row['tp_produto'],
                'legenda' => $row['legenda'] ?? '',
                'autor' => $row['autor'] ?? '',
                'ordem' => $row['ordem'] ?? 0,
                'fachada' => $row['fachada'] ?? 'f',
                'nacional' => $row['nacional'] ?? 'f',
                'palavras_chave' => $row['palavras_chave'] ?? '',
                'nome_hotel' => $row['nome_hotel'] ?? '',
                'nome_cidade' => $row['nome_cidade'] ?? '',
                'urls' => []
            ];

            // Processa URLs
            foreach (['tam_4', 'tam_3', 'tam_2', 'tam_1'] as $tam) {
                if (!empty($row[$tam])) {
                    $path = str_replace(' ', '%20', trim($row[$tam]));
                    $img['urls'][$tam] = $base_url . $path;
                }
            }

            // Preview URL (prioriza maior)
            $img['preview_url'] = $img['urls']['tam_4'] ??
                $img['urls']['tam_3'] ??
                $img['urls']['tam_2'] ??
                $img['urls']['tam_1'] ??
                'https://via.placeholder.com/600x400?text=Sem+Imagem';

            $images[] = $img;
        }

        echo json_encode([
            'success' => true,
            'action' => 'search_by_name',
            'termo' => $termo,
            'total' => count($images),
            'images' => $images
        ]);
        break;

    // ================================================
    // UPLOAD DE IMAGEM
    // Ex: POST ?action=upload_image (multipart/form-data)
    // Campos: titulo, descricao, arquivo
    // ================================================
    case 'upload_image':
        header('Content-Type: application/json; charset=utf-8');

    
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Arquivo obrigatório']);
            exit;
        }

        $titulo = isset($_POST['titulo']) ? pg_escape_string(trim($_POST['titulo'])) : '';
        $descricao = isset($_POST['descricao']) ? pg_escape_string(trim($_POST['descricao'])) : '';
        $pasta = isset($_POST['pasta']) ? trim($_POST['pasta']) : '';
        $fk_cidcod = isset($_POST['fk_cidcod']) ? pg_escape_string(trim($_POST['fk_cidcod'])) : '';

        if ($titulo === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Título obrigatório']);
            exit;
        }

        // === SALVA LOCALMENTE ===
        $baseDir = dirname(__DIR__) . '/bancoimagemfotos/uploads';
        if (!is_dir($baseDir)) mkdir($baseDir, 0775, true);

        $safeFolder = preg_replace('/[^a-zA-Z0-9_\/-]/', '_', $pasta);
        $uploadDir = $safeFolder ? ($baseDir . '/' . $safeFolder) : $baseDir;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

        $originalName = $_FILES['arquivo']['name'];
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $fileName = $safeName . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $destPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Falha ao salvar local']);
            exit;
        }

        // === PEGA TODAS AS VARIAÇÕES ===
        $baseSemExt = pathinfo($destPath, PATHINFO_FILENAME);
        $dir = dirname($destPath);
        $arquivos = glob($dir . '/' . $baseSemExt . '*');

        // === ENVIA TODAS AO FLASK ===
        $enviados = [];

        foreach ($arquivos as $arquivoLocal) {

        $ch = curl_init("http://10.3.2.146:5000/api/upload_from_erp_enviar_para_cidade");

        $post = [       
            'cidade_nome' => $_POST['cidade_nome'] ?? '',
            'file'        => new CURLFile(realpath($arquivoLocal)),
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer 123456"
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || $httpCode !== 200) {
            error_log("Erro CURL (HTTP $httpCode): " . curl_error($ch) . " - Resposta: " . $response);
        } else {
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON inválido do Flask: " . $response);
                } elseif (!empty($data['success'])) {
                    $path = $data['original'] ?? '';
                    if ($path && strpos($path, 'bancoimagemfotos/') !== 0) {
                        $path = 'bancoimagemfotos/' . ltrim($path, '/');
                    }
                    $enviados[] = $path;
                } else {
                    error_log("Falha no upload Flask: " . $response);
                }
        }

        curl_close($ch);
    }


        if (count($enviados) === 0) {
            echo json_encode(['success'=>false,'error'=>'Nenhuma imagem foi enviada ao banco']);
            exit;
        }

        // === SALVA NO BANCO USANDO CAMINHOS REAIS ===
        $tam_1 = $enviados[0] ?? null;
        $tam_2 = $enviados[1] ?? null;
        $tam_3 = $enviados[2] ?? null;
        $tam_4 = $enviados[3] ?? null;
        $tam_5 = $enviados[4] ?? null;
        $zip   = $enviados[0] ?? null;

        $sql = "INSERT INTO banco_imagem.bco_img
            (tam_1, tam_2, tam_3, tam_4, tam_5, zip, legenda, palavras_chave, tp_produto, fk_cidcod)
            VALUES ($1,$2,$3,$4,$5,$6,$7,$8,10,$9)
            RETURNING pk_bco_img";

        $res = pg_query_params($conn, $sql, [
            $tam_1, $tam_2, $tam_3, $tam_4, $tam_5, $zip,
            $titulo, $descricao, $fk_cidcod
        ]);

        $row = pg_fetch_assoc($res);

        echo json_encode([
            'success' => true,
            'pk_bco_img' => $row['pk_bco_img'],
            'paths' => $enviados
        ]);
        break;



        case 'upload_image_hotel':
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Arquivo obrigatório']);
            exit;
        }

        $titulo = isset($_POST['titulo']) ? pg_escape_string(trim($_POST['titulo'])) : '';
        $descricao = isset($_POST['descricao']) ? pg_escape_string(trim($_POST['descricao'])) : '';
        $cidade_nome = isset($_POST['cidade_nome']) ? trim($_POST['cidade_nome']) : '';
        $hotel_nome = isset($_POST['hotel_nome']) ? trim($_POST['hotel_nome']) : '';
        $mneu_for = isset($_POST['mneu_for']) ? pg_escape_string(trim($_POST['mneu_for'])) : '';

        if ($titulo === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Título obrigatório']);
            exit;
        }

        if ($cidade_nome === '' || $hotel_nome === '' || $mneu_for === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'cidade_nome, hotel_nome e mneu_for são obrigatórios']);
            exit;
        }

        // === SALVA LOCALMENTE ===
        $baseDir = dirname(__DIR__) . '/bancoimagemfotos/uploads';
        if (!is_dir($baseDir)) mkdir($baseDir, 0775, true);

        $safeFolder = preg_replace('/[^a-zA-Z0-9_\/-]/', '_', "hotel/$cidade_nome/$hotel_nome");
        $uploadDir = $safeFolder ? ($baseDir . '/' . $safeFolder) : $baseDir;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

        $originalName = $_FILES['arquivo']['name'];
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $fileName = $safeName . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $destPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Falha ao salvar local']);
            exit;
        }

        // === PEGA TODAS AS VARIAÇÕES ===
        $baseSemExt = pathinfo($destPath, PATHINFO_FILENAME);
        $dir = dirname($destPath);
        $arquivos = glob($dir . '/' . $baseSemExt . '*');

        // === ENVIA TODAS AO FLASK ===
        $enviados = [];

        foreach ($arquivos as $arquivoLocal) {

            $ch = curl_init("http://10.3.2.146:5000/api/upload_from_erp_enviar_para_hotel");

            $post = [
                'cidade_nome' => $cidade_nome,
                'hotel_nome'  => $hotel_nome,
                'file'        => new CURLFile(realpath($arquivoLocal)),
            ];

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $post,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer 123456"
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false || $httpCode !== 200) {
                error_log("Erro CURL (HTTP $httpCode): " . curl_error($ch) . " - Resposta: " . $response);
            } else {
                $data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON inválido do Flask: " . $response);
                } elseif (!empty($data['success'])) {
                    $path = $data['original'] ?? '';
                    if ($path && strpos($path, 'bancoimagemfotos/') !== 0) {
                        $path = 'bancoimagemfotos/' . ltrim($path, '/');
                    }
                    $enviados[] = $path;
                } else {
                    error_log("Falha no upload Flask: " . $response);
                }
            }

            curl_close($ch);
        }

        if (count($enviados) === 0) {
            echo json_encode(['success'=>false,'error'=>'Nenhuma imagem foi enviada ao banco']);
            exit;
        }

        // === SALVA NO BANCO USANDO CAMINHOS REAIS ===
        $tam_1 = $enviados[0] ?? null;
        $tam_2 = $enviados[1] ?? null;
        $tam_3 = $enviados[2] ?? null;
        $tam_4 = $enviados[3] ?? null;
        $tam_5 = $enviados[4] ?? null;
        $zip   = $enviados[0] ?? null;

        $sql = "INSERT INTO banco_imagem.bco_img
            (tam_1, tam_2, tam_3, tam_4, tam_5, zip, legenda, palavras_chave, tp_produto, mneu_for)
            VALUES ($1,$2,$3,$4,$5,$6,$7,$8,1,$9)
            RETURNING pk_bco_img";

        $res = pg_query_params($conn, $sql, [
            $tam_1, $tam_2, $tam_3, $tam_4, $tam_5, $zip,
            $titulo, $descricao, $mneu_for
        ]);

        $row = pg_fetch_assoc($res);

        echo json_encode([
            'success' => true,
            'pk_bco_img' => $row['pk_bco_img'],
            'paths' => $enviados
        ]);
        break;


    // ================================================
    // LISTAR PASTAS DE UPLOAD
    // Ex: GET ?action=list_upload_folders
    // ================================================
    case 'list_upload_folders':
        header('Content-Type: application/json; charset=utf-8');
        $baseDir = dirname(__DIR__) . '/bancoimagemfotos/uploads';
        if (!is_dir($baseDir)) {
            echo json_encode(['success' => true, 'folders' => []]);
            exit;
        }
        $items = array_filter(scandir($baseDir), function ($name) use ($baseDir) {
            if ($name === '.' || $name === '..') return false;
            return is_dir($baseDir . '/' . $name);
        });
        sort($items, SORT_NATURAL | SORT_FLAG_CASE);
        echo json_encode(['success' => true, 'folders' => array_values($items)]);
        break;

    // ================================================
    // CRIAR PASTA DE UPLOAD
    // Ex: POST ?action=create_upload_folder { "name": "minha_pasta" }
    // ================================================
    // case 'create_upload_folder':
    //     header('Content-Type: application/json; charset=utf-8');
    //     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    //         http_response_code(405);
    //         echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    //         exit;
    //     }
    //     $input = file_get_contents('php://input');
    //     $data = json_decode($input, true);
    //     $name = isset($data['name']) ? trim($data['name']) : '';
    //     if ($name === '') {
    //         http_response_code(400);
    //         echo json_encode(['success' => false, 'error' => 'Nome da pasta é obrigatório']);
    //         exit;
    //     }
    //     $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    //     $baseDir = dirname(__DIR__) . '/bancoimagemfotos/uploads';
    //     if (!is_dir($baseDir)) {
    //         mkdir($baseDir, 0775, true);
    //     }
    //     $folderPath = $baseDir . '/' . $safeName;
    //     if (!is_dir($folderPath)) {
    //         mkdir($folderPath, 0775, true);
    //     }
    //     echo json_encode(['success' => true, 'folder' => $safeName]);
    //     break;


    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ação inválida: ' . $action . '. Use: search_hotels, hotels_by_city, hotel_images, list_cities, search_images, search_by_name']);
        break;
}
