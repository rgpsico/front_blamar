<?php
/**
 * API RESTful - Gerenciamento de Funcionários (RH)
 * Compatível com PHP 7.2 legado
 * Alinhado com schema real de conteudo_internet.usuario (fornecido em 26/01/2026)
 */

date_default_timezone_set('America/Sao_Paulo');

// Headers CORS + JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Conexão (ajuste o caminho)
require_once '../util/connection.php';

function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Trata preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    response(array(), 200);
}

$request = isset($_GET['request']) ? $_GET['request'] : null;
if (!$request) {
    response(array('error' => "Parâmetro 'request' obrigatório"), 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$raw_input = file_get_contents('php://input');
$input = $raw_input ? json_decode($raw_input, true) : array();

// ID flexível
$id = isset($_GET['id']) ? $_GET['id'] : 
      (isset($input['id']) ? $input['id'] : 
      (isset($input['pk_usuario']) ? $input['pk_usuario'] : null));

try {
    switch ($request) {

        // ────────────────────────────────────────────────
        // LISTAR FUNCIONÁRIOS (com filtros e paginação)
        // ────────────────────────────────────────────────
        case 'listar_funcionarios':
        case 'listar_funcionarios_paginate':
            if ($method !== 'GET') {
                response(array('error' => 'Use GET'), 405);
            }

            $paginate = ($request === 'listar_funcionarios_paginate');

            $page     = max(1, isset($_GET['page'])     ? (int)$_GET['page']     : 1);
            $per_page = max(1, min(100, isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20));
            $offset   = ($page - 1) * $per_page;

            // Filtros básicos
            $f_nome  = trim(isset($_GET['filtro_nome']) ? $_GET['filtro_nome'] : '');
            $f_cpf   = trim(isset($_GET['filtro_cpf'])  ? $_GET['filtro_cpf']  : '');
            $f_ativo = trim(isset($_GET['filtro_ativo']) ? $_GET['filtro_ativo'] : 'all');

            $where  = array();
            $params = array();
            $idx    = 1;

            if ($f_nome !== '') {
                $where[] = "nome ILIKE $" . $idx++;
                $params[] = "%$f_nome%";
            }
            if ($f_cpf !== '') {
                $where[] = "cpf ILIKE $" . $idx++;
                $params[] = "%$f_cpf%";
            }
            if ($f_ativo !== 'all' && $f_ativo !== '') {
                $ativo_val = in_array(strtolower($f_ativo), array('true','1','sim','ativo','t')) ? 'true' : 'false';
                $where[] = "ativo = $" . $idx++;
                $params[] = $ativo_val;
            }

            $where_sql = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

            // Contagem para paginação
            $total = null;
            if ($paginate) {
                $sql_count = "SELECT COUNT(*) FROM conteudo_internet.usuario" . $where_sql;
                $res_count = pg_query_params($conn, $sql_count, $params);
                if (!$res_count) response(array('error' => pg_last_error($conn)), 500);
                $total = (int) pg_fetch_result($res_count, 0, 0);
            }

            // Ordenação
            $ordem   = isset($_GET['ordem'])   ? $_GET['ordem']   : 'nome';
            $direcao = strtoupper(isset($_GET['direcao']) ? $_GET['direcao'] : 'ASC');
            $campo_ordem = in_array($ordem, array('nome','apelido','cpf')) ? $ordem : 'nome';
            $direcao     = in_array($direcao, array('ASC','DESC')) ? $direcao : 'ASC';

            $limit_sql = '';
            if ($paginate) {
                $limit_sql = " LIMIT $" . $idx++ . " OFFSET $" . $idx++;
                $params[] = $per_page;
                $params[] = $offset;
            }

            $sql = "
                SELECT 
                    pk_usuario AS id,
                    u.cod_sis,
                    nome,
                    apelido,
                    cpf,
                    email_pessoal,
                    email,
                    aup.profile_id,
                    ap.name AS profile_name,
                    ativo,
                    TO_CHAR(aniversario, 'YYYY-MM-DD') AS aniversario
                FROM conteudo_internet.usuario u
                LEFT JOIN auth.auth_user_profiles aup
                    ON aup.cod_sis = u.cod_sis::varchar
                LEFT JOIN auth.auth_profiles ap
                    ON ap.id = aup.profile_id
                $where_sql
                ORDER BY $campo_ordem $direcao
                $limit_sql
            ";

            $res = pg_query_params($conn, $sql, $params);
            if (!$res) response(array('error' => pg_last_error($conn)), 500);

            $data = array();
            while ($row = pg_fetch_assoc($res)) {
                $row['ativo'] = ($row['ativo'] === 't' || $row['ativo'] === true);
                $data[] = $row;
            }

            if ($paginate) {
                response(array(
                    'data'         => $data,
                    'current_page' => $page,
                    'per_page'     => $per_page,
                    'total'        => $total ? $total : count($data),
                    'last_page'    => $total ? max(1, ceil($total / $per_page)) : 1
                ));
            }

            response($data);
            break;

        // ────────────────────────────────────────────────
        // DADOS COMPLETOS DE UM FUNCIONÁRIO
        // ────────────────────────────────────────────────
        case 'get_funcionario_completo':
            if ($method !== 'GET') response(array('error' => 'Use GET'), 405);
            if (!$id) response(array('error' => 'pk_usuario obrigatório'), 400);

            // Dados básicos - colunas reais da tabela usuario
            $sql_basic = "
                SELECT 
                    pk_usuario AS id,
                    u.cod_sis,
                    nome,
                    apelido,
                    aniversario,
                    nacionalidade,
                    naturalidade,
                    estado_civil,
                    sexo,
                    \"end\" AS endereco,
                    endereco_numero,
                    endereco_complemento,
                    endereco_uf,
                    bairro,
                    cidade,
                    cep,
                    telefone_residencial,
                    celular,
                    email_pessoal,
                    email,
                    skype,
                    cpf,
                    pis,
                    ctps,
                    ctps_serie,
                    ctps_uf,
                    data_ctps,
                    titulo,
                    zona,
                    secao,
                    cert_reservista,
                    serie,
                    rm,
                    carteira_motorista,
                    data_cart_moto,
                    categoria_mot,
                    banco,
                    agencia,
                    conta_corrente,
                    digito_contacorrente,
                    carteira_19,
                    data_chegada,
                    pais_origem,
                    naturalizado,
                    filhos_br,
                    observacao,
                    ativo,
                    numero_dependentes,
                    nome_pai,
                    nome_mae,
                    nome_conjuge,
                    exame_adm,
                    exame_per,
                    exame_demiss,
                    data_inicio,
                    data_saida,
                    assinou_software,
                    aup.profile_id
                FROM conteudo_internet.usuario u
                LEFT JOIN auth.auth_user_profiles aup
                    ON aup.cod_sis = u.cod_sis::varchar
                WHERE pk_usuario = $1
            ";
            $res_basic = pg_query_params($conn, $sql_basic, array($id));
            if (pg_num_rows($res_basic) == 0) {
                response(array('error' => 'Funcionário não encontrado'), 404);
            }
            $func = pg_fetch_assoc($res_basic);

            // Contratação / períodos (campos que estão na tabela)
            $contratacao = array(
                'data_inicio'     => $func['data_inicio'],
                'data_saida'      => $func['data_saida'],
                'period_estag1'   => isset($func['period_estag1']) ? $func['period_estag1'] : null,
                'period_estag2'   => isset($func['period_estag2']) ? $func['period_estag2'] : null,
                'aditivo1'        => isset($func['aditivo1']) ? $func['aditivo1'] : null,
                'aditivo2'        => isset($func['aditivo2']) ? $func['aditivo2'] : null,
                'exame_adm'       => $func['exame_adm'],
                'exame_demiss'    => $func['exame_demiss'],
                // Se houver tabela separada para contratação completa, busque aqui
            );

            // Advertências (tabela separada)
            $sql_adv = "
                SELECT 
                    pk_advertencia AS id,
                    tipo,
                    motivo,
                    TO_CHAR(dt_advertencia, 'YYYY-MM-DD') AS data
                FROM conteudo_internet.usuario_advertencia
                WHERE fk_usuario = $1
                ORDER BY dt_advertencia DESC
            ";
            $res_adv = pg_query_params($conn, $sql_adv, array($id));
            $advertencias = array();
            while ($row = pg_fetch_assoc($res_adv)) {
                $advertencias[] = $row;
            }

            // Empréstimos (tabela separada)
            $sql_emp = "
                SELECT 
                    pk_emprestimo AS id,
                    TO_CHAR(dt_inicio, 'YYYY-MM-DD') AS data_inicio,
                    TO_CHAR(dt_fim, 'YYYY-MM-DD') AS data_fim,
                    valor,
                    numero_prestacoes,
                    motivo,
                    autorizado_por
                FROM conteudo_internet.usuario_emprestimo
                WHERE fk_usuario = $1
                ORDER BY dt_inicio DESC
            ";
            $res_emp = pg_query_params($conn, $sql_emp, array($id));
            $emprestimos = array();
            while ($row = pg_fetch_assoc($res_emp)) {
                $emprestimos[] = $row;
            }

            // Benefícios (se estiver em tabela separada, ajuste nome)
            // Caso benefícios estejam na mesma tabela, use $func['...']

            // Dependentes (já na tabela usuario)
            $dependentes = array(
                'numero_dependentes' => $func['numero_dependentes'],
                'nome_pai'           => $func['nome_pai'],
                'nome_mae'           => $func['nome_mae'],
                'nome_conjuge'       => $func['nome_conjuge']
                // Filhos em tabela separada (se existir)
            );

            $sql_filhos = "
                SELECT pk_dependentes AS id, nome_filho
                FROM conteudo_internet.usuario_dependentes
                WHERE fk_usuario = $1
                ORDER BY nome_filho
            ";
            $res_filhos = pg_query_params($conn, $sql_filhos, array($id));
            $filhos = array();
            while ($row = pg_fetch_assoc($res_filhos)) {
                $filhos[] = $row;
            }

            response(array(
                'funcionario'   => $func,
                'contratacao'   => $contratacao,
                'advertencias'  => $advertencias,
                'emprestimos'   => $emprestimos,
                'dependentes'   => array(
                    'info'   => $dependentes,
                    'filhos' => $filhos
                )
            ));
            break;

        // ────────────────────────────────────────────────
        // SALVAR ADVERTÊNCIA (exemplo mantido)
        // ────────────────────────────────────────────────
        case 'salvar_advertencia':
            // ... (o mesmo código de antes, já compatível)
            // Pode copiar do exemplo anterior se precisar
            response(array('message' => 'Endpoint de advertência mantido - implemente conforme necessário'));
            break;

        // ------------------------------------------------------------------
        // EDITAR COD_SIS (somente o codigo do funcionario)
        // ------------------------------------------------------------------
case 'editar_cod_sis':
    if ($method !== 'PUT' && $method !== 'POST') {
        response(array('error' => 'Use PUT'), 405);
    }

    if (!$id) {
        response(array('error' => 'id/pk_usuario obrigatorio'), 400);
    }

    $cod_sis = isset($input['cod_sis']) ? trim($input['cod_sis']) : '';
    if ($cod_sis === '') {
        response(array('error' => 'cod_sis obrigatorio'), 400);
    }
    if (strlen($cod_sis) > 16) {
        response(array('error' => 'cod_sis deve ter no maximo 16 caracteres'), 400);
    }

    // Inicia transação (se ainda não começou)
    pg_query($conn, 'BEGIN');

    try {
        // 1. Atualiza a tabela auth_user_profiles (se existir o old_cod_sis)
        if ($old_cod_sis) {
            $sql_profile = "UPDATE auth.auth_user_profiles SET cod_sis = $1 WHERE cod_sis = $2";
            $res_profile = pg_query_params($conn, $sql_profile, array($cod_sis, $old_cod_sis));
            if (!$res_profile) {
                throw new Exception(pg_last_error($conn));
            }
        }

        // 2. Atualiza TODOS os registros na tabela api_user_tokens que usam o old_cod_sis
        if ($old_cod_sis) {
            $sql_tokens = "UPDATE sbd95.api_user_tokens SET cod_sis = $1 WHERE cod_sis = $2";
            $res_tokens = pg_query_params($conn, $sql_tokens, array($cod_sis, $old_cod_sis));
            if (!$res_tokens) {
                throw new Exception(pg_last_error($conn));
            }
        }

        // Se chegou até aqui → commit
        pg_query($conn, 'COMMIT');

        response(array(
            'success'     => true,
            'id'          => (int)$id,
            'cod_sis'     => $cod_sis,
            'old_cod_sis' => $old_cod_sis
        ));

    } catch (Exception $e) {
        pg_query($conn, 'ROLLBACK');
        response(array('error' => 'Erro ao atualizar cod_sis: ' . $e->getMessage()), 500);
    }
    break;

    
        default:
            response(array('error' => "Rota desconhecida: $request"), 404);
    }
} catch (Exception $e) {
    response(array('error' => 'Erro interno: ' . $e->getMessage()), 500);
}
