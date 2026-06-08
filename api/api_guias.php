<?php

/**
 * API para gerenciamento de Guias e seus Pacotes (OS / Ordens de Serviço)
 *
 * ✅ CORRIGIDO conforme schema real da tabela conteudo_internet.guias:
 *    - PK real: id_guias (não pk_idguia)
 *    - Ativo: coluna "ativar" (não "ativo")
 *    - Não existem: idiomas, observacoes, telefone, data_cadastro, data_atualizacao
 *    - Observações: usar obs_blumar
 *    - Telefone: telefone_celular / telefone_casa / telefone_outro
 *
 * ⚠️ As tabelas os_numero / os_guia / os_file continuam usando fk_idguia
 *    como referência ao id_guias da tabela guias.
 *
 * Endpoints CRUD de Guias:
 * - GET    ?request=listar_guias&filtro_nome=&filtro_ativar=true&limit=100
 * - GET    ?request=buscar_guia&id=123
 * - POST   ?request=criar_guia
 * - PUT    ?request=atualizar_guia&id=123
 * - DELETE ?request=excluir_guia&id=123
 *
 * Endpoints de Pacotes (OS):
 * - GET ?request=listar_pacotes&id_guia=123&idpg=2
 * - GET ?request=buscar_pacote&id_guia=123&pk_osfile=456
 * - GET ?request=resumo_pacotes&id_guia=123
 *
 * idpg = 2 (Pendentes) | 3 (Aceitos) | 4 (Recusados)
 *        5 (Pagos)     | 6 (Cancelados) | 7 (Liberados p/ pagamento)
 */

// ========================================
// 🔧 CONFIGURAÇÕES INICIAIS
// ========================================
date_default_timezone_set('America/Sao_Paulo');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../util/connection.php';

$BASE_URL_FOTO_GUIA = "http://www.blumar.com.br/global/main_site/images/guias/";
$BASE_URL_ANEXO_OS  = "https://webapp.blumar.com.br/tools/os_sistem/downloads/";

// ========================================
// 🛠 HELPERS PADRÃO
// ========================================
function response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function formatString($value)
{
    if ($value === null || $value === '') return null;
    return $value;
}

function formatBoolean($valor)
{
    if ($valor === null || $valor === '') return null;
    return (bool)$valor ? 't' : 'f';
}

function formatInt($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) return null;
    return (int)$value;
}

function formatFloat($value)
{
    if ($value === null || $value === '' || !is_numeric($value)) return null;
    return (float)$value;
}

function formatDate($value)
{
    if ($value === null || $value === '') return null;
    return $value; // espera formato YYYY-MM-DD
}

function formatarBooleansLinha(&$row, array $campos)
{
    foreach ($campos as $campo) {
        if (isset($row[$campo])) {
            $row[$campo] = $row[$campo] === 't';
        }
    }
}

// ========================================
// 🛠 HELPERS ESPECÍFICOS DE PACOTES (OS)
// ========================================

function buildQueryPacotes($id_guia, $idpg)
{
    // Status 5 = PAGOS
    if ($idpg == 5) {
        $sql = "
            SELECT
                f.pk_osfile,
                n.status,
                n.fk_osfile,
                f.file,
                to_char(f.dt_envio, 'DD/MM/YYYY') AS data_envio,
                f.attach
            FROM conteudo_internet.os_numero n
            INNER JOIN conteudo_internet.os_file f
                ON n.fk_osfile = f.pk_osfile
            WHERE n.fk_idguia = $1
              AND n.os_status_financeiro = $2
        ";
        return [$sql, [$id_guia, $idpg]];
    }

    // Status 6 = CANCELADOS
    if ($idpg == 6) {
        $sql = "
            SELECT DISTINCT
                f.pk_osfile,
                f.file,
                to_char(f.dt_envio, 'DD/MM/YYYY') AS data_envio,
                g.dia_srv,
                f.dt_envio,
                f.status,
                f.attach,
                (
                    SELECT MIN(dia_srv)
                    FROM conteudo_internet.os_guia
                    WHERE fk_osfile = f.pk_osfile
                      AND fk_idguia = $1
                ) AS primeira_data
            FROM conteudo_internet.os_file f
            INNER JOIN conteudo_internet.os_guia   g ON f.pk_osfile = g.fk_osfile
            INNER JOIN conteudo_internet.os_numero n ON f.pk_osfile = n.fk_osfile
            WHERE g.fk_idguia = $1
              AND n.ciente_cancelamento = 't'
            ORDER BY primeira_data
        ";
        return [$sql, [$id_guia]];
    }

    // Status 7 = LIBERADOS PARA PAGAMENTO
    if ($idpg == 7) {
        $sql = "
            SELECT DISTINCT
                f.pk_osfile,
                f.file,
                to_char(f.dt_envio, 'DD/MM/YYYY') AS data_envio,
                (
                    SELECT MIN(dia_srv)
                    FROM conteudo_internet.os_guia
                    WHERE fk_osfile = f.pk_osfile
                      AND fk_idguia = $1
                ) AS primeira_data,
                g.dia_srv,
                f.status,
                f.attach
            FROM conteudo_internet.os_file f
            INNER JOIN conteudo_internet.os_guia   g ON f.pk_osfile = g.fk_osfile
            INNER JOIN conteudo_internet.os_numero n ON f.pk_osfile = n.fk_osfile
            WHERE g.fk_idguia = $1
              AND n.os_status_financeiro = '4'
              AND n.ciente_cancelamento = 'f'
            ORDER BY g.dia_srv
        ";
        return [$sql, [$id_guia]];
    }

    // Status 2, 3 ou 4 (Pendente / Aceito / Recusado)
    $sql = "
        SELECT DISTINCT
            f.pk_osfile,
            f.file,
            to_char(f.dt_envio, 'DD/MM/YYYY') AS data_envio,
            (
                SELECT MIN(dia_srv)
                FROM conteudo_internet.os_guia
                WHERE fk_osfile = f.pk_osfile
                  AND fk_idguia = $1
            ) AS primeira_data,
            g.dia_srv,
            f.status,
            f.attach,
            n.os_status_financeiro
        FROM conteudo_internet.os_file f
        INNER JOIN conteudo_internet.os_guia   g ON f.pk_osfile = g.fk_osfile
        INNER JOIN conteudo_internet.os_numero n ON f.pk_osfile = n.fk_osfile
        WHERE g.fk_idguia = $1
          AND n.fk_idguia = $1
          AND g.status_srv = $2
          AND n.ciente_cancelamento = 'f'
          AND n.os_status_financeiro != '5'
          AND g.status_srv_handling   != '6'
        ORDER BY g.dia_srv
    ";
    return [$sql, [$id_guia, $idpg]];
}

function montarInfoFile($conn, $pk_osfile, $id_guia)
{
    $rPax = pg_query_params($conn,
        "SELECT pax FROM sbd95.files WHERE file = (
            SELECT file FROM conteudo_internet.os_file WHERE pk_osfile = $1 LIMIT 1
        ) LIMIT 1",
        [$pk_osfile]
    );
    $pax = ($rPax && pg_num_rows($rPax) > 0) ? pg_fetch_result($rPax, 0, 'pax') : null;

    $rDt = pg_query_params($conn,
        "SELECT to_char(MIN(dia_srv), 'DD/MM/YYYY') AS primeira_data
         FROM conteudo_internet.os_guia
         WHERE fk_osfile = $1 AND fk_idguia = $2",
        [$pk_osfile, $id_guia]
    );
    $primeira_data = ($rDt && pg_num_rows($rDt) > 0)
        ? pg_fetch_result($rDt, 0, 'primeira_data')
        : null;

    return ['pax' => $pax, 'primeira_data' => $primeira_data];
}

function montarOsNumeros($conn, $pk_osfile, $id_guia)
{
    $sql = "
        SELECT
            ciente_obs_hand,
            os_status_guia,
            pk_osnumero,
            os_status_financeiro,
            to_char(dt_pagamento, 'DD/MM/YYYY') AS dt_pagamento,
            alert_updates,
            fk_osupdates,
            ciente_alteracao,
            e_os_anex
        FROM conteudo_internet.os_numero
        WHERE fk_osfile = $1
          AND fk_idguia = $2
        ORDER BY e_os_anex ASC
    ";
    $r = pg_query_params($conn, $sql, [$pk_osfile, $id_guia]);
    $principal = null;
    $anexo     = null;

    if ($r) {
        while ($row = pg_fetch_assoc($r)) {
            formatarBooleansLinha($row, [
                'ciente_obs_hand', 'os_status_guia', 'alert_updates',
                'ciente_alteracao', 'e_os_anex'
            ]);
            if ($row['e_os_anex']) $anexo = $row;
            else                   $principal = $row;
        }
    }
    return ['os_principal' => $principal, 'os_anexo' => $anexo];
}

function montarServicos($conn, $pk_osfile, $id_guia, $temAnexo)
{
    $filtroAnexo = $temAnexo ? "AND g.os_anex = 'true'" : "AND g.os_anex = 'false'";

    $sql = "
        SELECT
            g.pk_osguia,
            to_char(g.dia_srv, 'DD/MM/YYYY') AS dia_servico,
            to_char(g.hora_srv, 'HH24:MI')   AS hora_servico,
            g.descritivo_srv,
            g.status_srv,
            g.os,
            g.codbooking,
            g.os_anex
        FROM conteudo_internet.os_guia g
        INNER JOIN conteudo_internet.os_file f ON g.fk_osfile = f.pk_osfile
        WHERE g.fk_osfile = $1
          AND g.fk_idguia = $2
          AND g.status_srv_handling != '6'
          {$filtroAnexo}
        ORDER BY g.dia_srv, g.id_srv
    ";
    $r = pg_query_params($conn, $sql, [$pk_osfile, $id_guia]);
    $servicos = [];

    if ($r) {
        while ($row = pg_fetch_assoc($r)) {
            $descritivo = $row['descritivo_srv'];

            if (empty($descritivo) && !empty($row['codbooking'])) {
                $rExt = pg_query_params($conn,
                    "SELECT descr_ext FROM sbd95.booking WHERE cod_book = $1 LIMIT 1",
                    [$row['codbooking']]
                );
                if ($rExt && pg_num_rows($rExt) > 0) {
                    $ext = pg_fetch_result($rExt, 0, 'descr_ext');
                    if (!empty($ext)) $descritivo = strtoupper($ext);
                }

                if (empty($descritivo)) {
                    $rNome = pg_query_params($conn,
                        "SELECT s.nome_serv
                         FROM sbd95.booking b
                         INNER JOIN sbd95.servicos s ON b.serv = s.serv
                         WHERE b.cod_book = $1 LIMIT 1",
                        [$row['codbooking']]
                    );
                    if ($rNome && pg_num_rows($rNome) > 0) {
                        $descritivo = pg_fetch_result($rNome, 0, 'nome_serv');
                    }
                }
            }

            $row['descritivo']   = $descritivo;
            $row['os_anex']      = $row['os_anex'] === 't';
            $row['status_srv']   = (int)$row['status_srv'];
            $row['status_label'] = [
                1 => 'Pendente',
                2 => 'Enviado',
                3 => 'Aceito',
                4 => 'Não aceito',
            ][$row['status_srv']] ?? 'Desconhecido';

            $servicos[] = $row;
        }
    }
    return $servicos;
}

function montarAnexos($conn, $pk_osfile, $baseUrl)
{
    $r = pg_query_params($conn,
        "SELECT pk_osattach, attach
         FROM conteudo_internet.os_attach
         WHERE fk_osfile = $1",
        [$pk_osfile]
    );
    $anexos = [];
    if ($r) {
        while ($row = pg_fetch_assoc($r)) {
            if (!empty($row['attach'])) {
                $anexos[] = [
                    'id'   => (int)$row['pk_osattach'],
                    'nome' => $row['attach'],
                    'url'  => $baseUrl . $row['attach'],
                ];
            }
        }
    }
    return $anexos;
}

function montarAlteracao($conn, $fk_osupdates)
{
    if (empty($fk_osupdates)) return null;

    $r = pg_query_params($conn,
        "SELECT to_char(data_update, 'DD/MM/YYYY') AS data_update,
                conteudo_alteracao
         FROM conteudo_internet.os_updates
         WHERE pk_osupdates = $1",
        [$fk_osupdates]
    );
    if ($r && pg_num_rows($r) > 0) {
        return pg_fetch_assoc($r);
    }
    return null;
}

// ========================================
// 🚦 ROTEAMENTO PRINCIPAL
// ========================================
$request = isset($_GET['request']) ? $_GET['request'] : null;
if (!$request) {
    response(["error" => "Parâmetro 'request' é obrigatório"], 400);
}

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($request) {

        // =========================================================
        // 🔹 ROTA 1: Listar Guias (GET)
        // =========================================================
        case 'listar_guias':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $filtro_nome   = isset($_GET['filtro_nome'])   ? trim($_GET['filtro_nome'])   : null;
            $filtro_email  = isset($_GET['filtro_email'])  ? trim($_GET['filtro_email'])  : null;
            $filtro_ativar = isset($_GET['filtro_ativar']) ? $_GET['filtro_ativar']
                            : (isset($_GET['filtro_ativo']) ? $_GET['filtro_ativo'] : 'all');
            $limit         = isset($_GET['limit'])         ? intval($_GET['limit'])      : 100;

            $params = [];
            $where  = [];
            $idx    = 1;

            if ($filtro_nome) {
                $where[]  = "nome ILIKE $" . $idx++;
                $params[] = "%{$filtro_nome}%";
            }
            if ($filtro_email) {
                $where[]  = "email ILIKE $" . $idx++;
                $params[] = "%{$filtro_email}%";
            }
            if ($filtro_ativar && $filtro_ativar !== 'all') {
                $where[]  = "ativar = $" . $idx++;
                $params[] = ($filtro_ativar === 'true' ? 't' : 'f');
            }

            $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";
            $params[]  = $limit;

            $sql = "
                SELECT
                    id_guias,
                    nome,
                    apelido,
                    email,
                    telefone_celular,
                    telefone_casa,
                    telefone_outro,
                    cpf,
                    foto,
                    foto_perfil,
                    embratur,
                    ativar,
                    fk_cod_cidade,
                    categ
                FROM conteudo_internet.guias
                {$where_sql}
                ORDER BY nome
                LIMIT $" . $idx . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception(pg_last_error($conn));

            $guias = [];
            while ($row = pg_fetch_assoc($result)) {
                formatarBooleansLinha($row, ['ativar']);
                $row['foto_url']        = !empty($row['foto'])
                    ? $BASE_URL_FOTO_GUIA . $row['foto']
                    : null;
                $row['foto_perfil_url'] = !empty($row['foto_perfil'])
                    ? $BASE_URL_FOTO_GUIA . $row['foto_perfil']
                    : null;
                $guias[] = $row;
            }

            response($guias);
            break;

        // =========================================================
        // 🔹 ROTA 2: Buscar Guia por ID (GET)
        // =========================================================
        case 'buscar_guia':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            $sql = "
                SELECT
                    id_guias,
                    nome,
                    apelido,
                    descritivo,
                    email,
                    telefone_celular,
                    telefone_casa,
                    telefone_outro,
                    nextel,
                    id_nextel,
                    cpf,
                    cpf3,
                    pis,
                    pis3,
                    identidade,
                    id_emissor,
                    data_exp_id,
                    cnh,
                    validade_cnh,
                    categoria_cnh,
                    data_1_cnh,
                    cnh_org_exp,
                    cnh_data_exp,
                    cnh_uf,
                    foto,
                    foto_perfil,
                    embratur,
                    ativar,
                    fk_cod_cidade,
                    endereco,
                    endereco_numero,
                    endereco_complemento,
                    endereco_bairro,
                    endereco_cep,
                    endereco_uf,
                    to_char(nascimento, 'DD/MM/YYYY') AS nascimento,
                    mun_nasc,
                    uf_nasc,
                    nacion,
                    nome_mae,
                    nome_pai,
                    estado_civil,
                    escolaridade,
                    formacao,
                    rne_num,
                    rne_orgao,
                    rne_data,
                    obs_blumar,
                    operadoras,
                    login,
                    first_logon,
                    categ,
                    procedimentos,
                    cert_vacinacao,
                    all_vacinated
                FROM conteudo_internet.guias
                WHERE id_guias = $1
                LIMIT 1
            ";
            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result || pg_num_rows($result) === 0) {
                response(["error" => "Guia não encontrado"], 404);
            }

            $guia = pg_fetch_assoc($result);
            formatarBooleansLinha($guia, ['ativar', 'first_logon', 'procedimentos', 'all_vacinated']);
            $guia['foto_url']        = !empty($guia['foto'])
                ? $BASE_URL_FOTO_GUIA . $guia['foto']
                : null;
            $guia['foto_perfil_url'] = !empty($guia['foto_perfil'])
                ? $BASE_URL_FOTO_GUIA . $guia['foto_perfil']
                : null;

            response($guia);
            break;

        // =========================================================
        // 🔹 ROTA 3: Criar Guia (POST)
        // =========================================================
        case 'criar_guia':
            if ($method !== 'POST') response(["error" => "Método não permitido. Use POST."], 405);
            if (empty($input)) response(["error" => "Dados são obrigatórios no body JSON"], 400);

            if (empty($input['nome']))  response(["error" => "Campo 'nome' é obrigatório"],  400);

            // Mapeamento campo => tipo (string|bool|int|float|date)
            $mapeamentos = [
                'nome'                 => 'string',
                'apelido'              => 'string',
                'descritivo'           => 'string',
                'email'                => 'string',
                'telefone_celular'     => 'string',
                'telefone_casa'        => 'string',
                'telefone_outro'       => 'string',
                'nextel'               => 'string',
                'id_nextel'            => 'string',
                'cpf'                  => 'string',
                'cpf3'                 => 'float',
                'pis'                  => 'string',
                'pis3'                 => 'float',
                'identidade'           => 'string',
                'id_emissor'           => 'string',
                'data_exp_id'          => 'date',
                'cnh'                  => 'float',
                'validade_cnh'         => 'date',
                'categoria_cnh'        => 'string',
                'data_1_cnh'           => 'date',
                'cnh_org_exp'          => 'string',
                'cnh_data_exp'         => 'date',
                'cnh_uf'               => 'string',
                'foto'                 => 'string',
                'foto_perfil'          => 'string',
                'embratur'             => 'string',
                'ativar'               => 'bool',
                'fk_cod_cidade'        => 'int',
                'endereco'             => 'string',
                'endereco_numero'      => 'string',
                'endereco_complemento' => 'string',
                'endereco_bairro'      => 'string',
                'endereco_cep'         => 'string',
                'endereco_uf'          => 'string',
                'nascimento'           => 'date',
                'mun_nasc'             => 'string',
                'uf_nasc'              => 'string',
                'nacion'               => 'string',
                'nome_mae'             => 'string',
                'nome_pai'             => 'string',
                'estado_civil'         => 'float',
                'escolaridade'         => 'string',
                'formacao'             => 'string',
                'rne_num'              => 'string',
                'rne_orgao'            => 'string',
                'rne_data'             => 'date',
                'obs_blumar'           => 'string',
                'operadoras'           => 'string',
                'login'                => 'string',
                'pass'                 => 'string',
                'first_logon'          => 'bool',
                'categ'                => 'float',
                'procedimentos'        => 'bool',
                'cert_vacinacao'       => 'string',
                'all_vacinated'        => 'bool',
            ];

            // Aceita "ativo" como alias para "ativar"
            if (!isset($input['ativar']) && isset($input['ativo'])) {
                $input['ativar'] = $input['ativo'];
            }

            $cols         = [];
            $placeholders = [];
            $params       = [];
            $idx          = 1;

            foreach ($mapeamentos as $campo => $tipo) {
                if (!array_key_exists($campo, $input)) continue;
                $valor = $input[$campo];

                switch ($tipo) {
                    case 'bool':   $formatted = formatBoolean($valor); break;
                    case 'int':    $formatted = formatInt($valor);     break;
                    case 'float':  $formatted = formatFloat($valor);   break;
                    case 'date':   $formatted = formatDate($valor);    break;
                    default:       $formatted = formatString($valor);
                }

                if ($formatted === null) continue;

                $cols[]         = $campo;
                $placeholders[] = '$' . $idx++;
                $params[]       = $formatted;
            }

            if (empty($cols)) {
                response(["error" => "Nenhum campo válido para inserir"], 400);
            }

            $sql = "
                INSERT INTO conteudo_internet.guias (" . implode(', ', $cols) . ")
                VALUES (" . implode(', ', $placeholders) . ")
                RETURNING id_guias
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Erro ao inserir Guia: " . pg_last_error($conn));
            }

            $row = pg_fetch_assoc($result);
            response([
                'success'  => true,
                'message'  => 'Guia criado com sucesso!',
                'id_guias' => (int)$row['id_guias']
            ], 201);
            break;

        // =========================================================
        // 🔹 ROTA 4: Atualizar Guia (PUT)
        // =========================================================
        case 'atualizar_guia':
            if ($method !== 'PUT') response(["error" => "Método não permitido. Use PUT."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);
            if (empty($input)) response(["error" => "Dados são obrigatórios no body JSON"], 400);

            $mapeamentos = [
                'nome'                 => 'string',
                'apelido'              => 'string',
                'descritivo'           => 'string',
                'email'                => 'string',
                'telefone_celular'     => 'string',
                'telefone_casa'        => 'string',
                'telefone_outro'       => 'string',
                'nextel'               => 'string',
                'id_nextel'            => 'string',
                'cpf'                  => 'string',
                'cpf3'                 => 'float',
                'pis'                  => 'string',
                'pis3'                 => 'float',
                'identidade'           => 'string',
                'id_emissor'           => 'string',
                'data_exp_id'          => 'date',
                'cnh'                  => 'float',
                'validade_cnh'         => 'date',
                'categoria_cnh'        => 'string',
                'data_1_cnh'           => 'date',
                'cnh_org_exp'          => 'string',
                'cnh_data_exp'         => 'date',
                'cnh_uf'               => 'string',
                'foto'                 => 'string',
                'foto_perfil'          => 'string',
                'embratur'             => 'string',
                'ativar'               => 'bool',
                'fk_cod_cidade'        => 'int',
                'endereco'             => 'string',
                'endereco_numero'      => 'string',
                'endereco_complemento' => 'string',
                'endereco_bairro'      => 'string',
                'endereco_cep'         => 'string',
                'endereco_uf'          => 'string',
                'nascimento'           => 'date',
                'mun_nasc'             => 'string',
                'uf_nasc'              => 'string',
                'nacion'               => 'string',
                'nome_mae'             => 'string',
                'nome_pai'             => 'string',
                'estado_civil'         => 'float',
                'escolaridade'         => 'string',
                'formacao'             => 'string',
                'rne_num'              => 'string',
                'rne_orgao'            => 'string',
                'rne_data'             => 'date',
                'obs_blumar'           => 'string',
                'operadoras'           => 'string',
                'login'                => 'string',
                'pass'                 => 'string',
                'first_logon'          => 'bool',
                'categ'                => 'float',
                'procedimentos'        => 'bool',
                'cert_vacinacao'       => 'string',
                'all_vacinated'        => 'bool',
            ];

            // Alias de "ativo" para "ativar"
            if (!isset($input['ativar']) && isset($input['ativo'])) {
                $input['ativar'] = $input['ativo'];
            }

            $set     = [];
            $params  = [];
            $idx     = 1;
            $updated = false;

            foreach ($input as $chave => $valor) {
                $tipo = $mapeamentos[$chave] ?? null;
                if (!$tipo) continue;

                switch ($tipo) {
                    case 'bool':   $formatted = formatBoolean($valor); break;
                    case 'int':    $formatted = formatInt($valor);     break;
                    case 'float':  $formatted = formatFloat($valor);   break;
                    case 'date':   $formatted = formatDate($valor);    break;
                    default:       $formatted = formatString($valor);
                }

                if ($formatted === null) continue;

                $set[]    = "{$chave} = $" . $idx;
                $params[] = $formatted;
                $idx++;
                $updated  = true;
            }

            if (!$updated) {
                response(["success" => false, "message" => "Nenhuma alteração válida realizada"], 200);
            }

            $params[] = $id;

            $sql = "
                UPDATE conteudo_internet.guias
                SET " . implode(', ', $set) . "
                WHERE id_guias = $" . $idx . "
            ";

            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("Erro ao atualizar Guia: " . pg_last_error($conn));
            }

            response([
                'success' => true,
                'message' => 'Guia atualizado com sucesso!',
                'id'      => $id
            ]);
            break;

        // =========================================================
        // 🔹 ROTA 5: Excluir Guia (DELETE)
        // =========================================================
        case 'excluir_guia':
            if ($method !== 'DELETE') response(["error" => "Método não permitido. Use DELETE."], 405);

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) response(["error" => "ID é obrigatório"], 400);

            // ⚠️ Caso prefira soft-delete:
            // UPDATE conteudo_internet.guias SET ativar='f' WHERE id_guias = $1
            $sql    = "DELETE FROM conteudo_internet.guias WHERE id_guias = $1";
            $result = pg_query_params($conn, $sql, [$id]);
            if (!$result) throw new Exception(pg_last_error($conn));

            if (pg_affected_rows($result) > 0) {
                response(["success" => true, "message" => "Guia excluído com sucesso"]);
            } else {
                response(["error" => "Guia não encontrado"], 404);
            }
            break;

        // =========================================================
        // 🔹 ROTA 6: Listar Pacotes do Guia por status (GET)
        // =========================================================
        case 'listar_pacotes':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $id_guia = isset($_GET['id_guia']) ? intval($_GET['id_guia']) : null;
            $idpg    = isset($_GET['idpg'])    ? intval($_GET['idpg'])    : 2;

            if (!$id_guia) response(["error" => "Parâmetro 'id_guia' é obrigatório"], 400);
            if (!in_array($idpg, [2, 3, 4, 5, 6, 7], true)) {
                response(["error" => "idpg inválido. Use 2, 3, 4, 5, 6 ou 7."], 400);
            }

            list($sql, $params) = buildQueryPacotes($id_guia, $idpg);
            $result = pg_query_params($conn, $sql, $params);
            if (!$result) throw new Exception(pg_last_error($conn));

            $pacotes    = [];
            $jaImprimiu = [];

            while ($row = pg_fetch_assoc($result)) {
                $pk_osfile = $idpg == 5 ? $row['fk_osfile'] : $row['pk_osfile'];
                if (in_array($pk_osfile, $jaImprimiu, true)) continue;
                $jaImprimiu[] = $pk_osfile;

                $info     = montarInfoFile($conn, $pk_osfile, $id_guia);
                $osData   = montarOsNumeros($conn, $pk_osfile, $id_guia);
                $temAnexo = $osData['os_anexo'] !== null;

                $financeiroEfetivo = $osData['os_principal']['os_status_financeiro'] ?? null;
                if ($temAnexo && ($osData['os_anexo']['os_status_financeiro'] ?? null) != '5') {
                    $financeiroEfetivo = $osData['os_anexo']['os_status_financeiro'];
                }
                if ($idpg != 5 && $financeiroEfetivo == '5') continue;

                $servicos = montarServicos($conn, $pk_osfile, $id_guia, $temAnexo);
                $anexos   = montarAnexos($conn, $pk_osfile, $BASE_URL_ANEXO_OS);

                $alteracao = null;
                if (!empty($osData['os_principal'])
                    && $osData['os_principal']['alert_updates']
                    && !$osData['os_principal']['ciente_alteracao']) {
                    $alteracao = montarAlteracao(
                        $conn,
                        $osData['os_principal']['fk_osupdates']
                    );
                }

                $pacotes[] = [
                    'pk_osfile'     => (int)$pk_osfile,
                    'file'          => $row['file'],
                    'pax'           => $info['pax'],
                    'data_envio'    => $row['data_envio'],
                    'primeira_data' => $info['primeira_data'],
                    'status_file'   => $row['status'] ?? null,
                    'cancelado'     => ($row['status'] ?? null) == '5',
                    'attach'        => $row['attach'] ?? null,
                    'os_principal'  => $osData['os_principal'],
                    'os_anexo'      => $osData['os_anexo'],
                    'tem_anexo'     => $temAnexo,
                    'servicos'      => $servicos,
                    'anexos'        => $anexos,
                    'alteracao'     => $alteracao,
                ];
            }

            response([
                'idpg'    => $idpg,
                'id_guia' => $id_guia,
                'total'   => count($pacotes),
                'data'    => $pacotes,
            ]);
            break;

        // =========================================================
        // 🔹 ROTA 7: Buscar um Pacote específico (GET)
        // =========================================================
        case 'buscar_pacote':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $id_guia   = isset($_GET['id_guia'])   ? intval($_GET['id_guia'])   : null;
            $pk_osfile = isset($_GET['pk_osfile']) ? intval($_GET['pk_osfile']) : null;

            if (!$id_guia)   response(["error" => "Parâmetro 'id_guia' é obrigatório"],   400);
            if (!$pk_osfile) response(["error" => "Parâmetro 'pk_osfile' é obrigatório"], 400);

            $rFile = pg_query_params($conn,
                "SELECT pk_osfile, file, status,
                        to_char(dt_envio, 'DD/MM/YYYY') AS data_envio, attach
                 FROM conteudo_internet.os_file
                 WHERE pk_osfile = $1 LIMIT 1",
                [$pk_osfile]
            );
            if (!$rFile || pg_num_rows($rFile) === 0) {
                response(["error" => "Pacote (File) não encontrado"], 404);
            }
            $file = pg_fetch_assoc($rFile);

            $info     = montarInfoFile($conn, $pk_osfile, $id_guia);
            $osData   = montarOsNumeros($conn, $pk_osfile, $id_guia);
            $temAnexo = $osData['os_anexo'] !== null;
            $servicos = montarServicos($conn, $pk_osfile, $id_guia, $temAnexo);
            $anexos   = montarAnexos($conn, $pk_osfile, $BASE_URL_ANEXO_OS);

            $alteracao = null;
            if (!empty($osData['os_principal'])
                && $osData['os_principal']['alert_updates']
                && !$osData['os_principal']['ciente_alteracao']) {
                $alteracao = montarAlteracao(
                    $conn,
                    $osData['os_principal']['fk_osupdates']
                );
            }

            response([
                'pk_osfile'     => (int)$file['pk_osfile'],
                'file'          => $file['file'],
                'pax'           => $info['pax'],
                'data_envio'    => $file['data_envio'],
                'primeira_data' => $info['primeira_data'],
                'status_file'   => $file['status'],
                'cancelado'     => $file['status'] == '5',
                'attach'        => $file['attach'],
                'os_principal'  => $osData['os_principal'],
                'os_anexo'      => $osData['os_anexo'],
                'tem_anexo'     => $temAnexo,
                'servicos'      => $servicos,
                'anexos'        => $anexos,
                'alteracao'     => $alteracao,
            ]);
            break;

        // =========================================================
        // 🔹 ROTA 8: Resumo de Pacotes (contagem por status)
        // =========================================================
        case 'resumo_pacotes':
            if ($method !== 'GET') response(["error" => "Método não permitido. Use GET."], 405);

            $id_guia = isset($_GET['id_guia']) ? intval($_GET['id_guia']) : null;
            if (!$id_guia) response(["error" => "Parâmetro 'id_guia' é obrigatório"], 400);

            $resumo = [];
            foreach ([2, 3, 4, 5, 6, 7] as $idpg) {
                list($sql, $params) = buildQueryPacotes($id_guia, $idpg);
                $r = pg_query_params($conn, $sql, $params);
                $count  = 0;
                $vistos = [];
                if ($r) {
                    while ($row = pg_fetch_assoc($r)) {
                        $key = $idpg == 5 ? $row['fk_osfile'] : $row['pk_osfile'];
                        if (!in_array($key, $vistos, true)) {
                            $vistos[] = $key;
                            $count++;
                        }
                    }
                }
                $resumo[$idpg] = $count;
            }

            response([
                'id_guia'             => $id_guia,
                'pendentes_aceitacao' => $resumo[2],
                'aceitos'             => $resumo[3],
                'recusados'           => $resumo[4],
                'pagos'               => $resumo[5],
                'cancelados'          => $resumo[6],
                'liberados_pagamento' => $resumo[7],
            ]);
            break;

        default:
            response(["error" => "Rota inválida: '{$request}'"], 400);
    }
} catch (Exception $e) {
    error_log("Erro na API de Guias: " . $e->getMessage());
    response(["error" => "Erro no servidor: " . $e->getMessage()], 500);
}