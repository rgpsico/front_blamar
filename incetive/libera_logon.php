<?php


ini_set('display_errors', 1);
error_reporting(~0);
if (isset($_SESSION)) {
} else {
	session_start();
}
require('util/connection.php');


$login = trim(pg_escape_string(strtolower($_POST["login"])));
$pass = trim(pg_escape_string(strtolower($_POST["pass"])));
$imgbnk = trim(pg_escape_string(strtolower($_POST["imgbnk"])));


$endereco = "";

//se for infos de logon do banco redireciono para la
if ($login == 'bcoimg' and $pass == 'jps22') {
	// header( 'Location: https://www.blumar.com.br/bancoimagemfotos/banco_imagem/index.php' ) ; 
	header('Location: https://www.blumar.com.br/base-de-imagens-user/');
}

//verifico se as infos e logon estão no nadastro de clientes
/* $tar_client ="SELECT
			cod_agrup,
			mneu_cli,
			nome_cli,
			root_srv,
			mkp_htl,
			mkp_srv,
			mkp_food,
			login,
			pass,
			ativo,
			ativo_virtuoso,
			descricao_tar,
			lang,
			emp,
			pk_cad_cli,
			extranet,
			root_htl,
			ativo2tar,
			root_htl2,
			mkp_htl2,
			mkp_srv2,
			mkp_food2,
			fk_depto,
			mkp_eco,
			mkp_eco2,
			mkp_ny,
			mkp_ny2,
			mkp_carn,
			mkp_carn2,
			mkp_winn,
			mkp_winn2,
			fk_controle_acesso_ws,
			ativo_cote 
		FROM
			tarifario.cadastro_clientes
		WHERE
			login = '$login'
			AND pass = '$pass'
			AND ativo = true
			and (fk_depto = '2' or fk_depto = '5' or fk_depto = '6' or fk_depto = '7')
			AND desativar_tarifario = false";
	$result_tar_client = pg_exec($conn, $tar_client); */

$result_tar_client = pg_prepare($conn, "tar_client", "SELECT
			cod_agrup,
			mneu_cli,
			nome_cli,
			root_srv,
			mkp_htl,
			mkp_srv,
			mkp_food,
			login,
			pass,
			ativo,
			ativo_virtuoso,
			descricao_tar,
			lang,
			emp,
			pk_cad_cli,
			extranet,
			root_htl,
			ativo2tar,
			root_htl2,
			mkp_htl2,
			mkp_srv2,
			mkp_food2,
			fk_depto,
			mkp_eco,
			mkp_eco2,
			mkp_ny,
			mkp_ny2,
			mkp_carn,
			mkp_carn2,
			mkp_winn,
			mkp_winn2,
			fk_controle_acesso_ws,
			ativo_cote 
		FROM
			tarifario.cadastro_clientes
		WHERE
			login = $1
			AND pass = $2
			AND ativo = true
			and (fk_depto = '2' or fk_depto = '5' or fk_depto = '6' or fk_depto = '7')
			AND desativar_tarifario = false ");
$result_tar_client = pg_execute($conn, "tar_client", array("$login", "$pass"));

for ($row  = 0; $row  < pg_numrows($result_tar_client); $row++) {
	$lang = pg_result($result_tar_client, $row, 'lang');
}

$tar_client_recordcount = pg_numrows($result_tar_client);

//se não achar nada no cadastro de clientes verifica no sub-clientes
if ($tar_client_recordcount == 0) {


	/* 	$tar_client_operadores="SELECT
								fk_cad_cli
							FROM
								tarifario.cliente_operadores
							INNER JOIN tarifario.cadastro_clientes on tarifario.cliente_operadores.fk_cad_cli = tarifario.cadastro_clientes.pk_cad_cli
                            WHERE
								 operador_id = '$login'
							and
							     senha = '$pass'
							and
                                tarifario.cadastro_clientes.ativo = 'true'";	 */

	$result_client_operadores = pg_prepare($conn, "tar_client_operadores", "SELECT
								fk_cad_cli
							FROM
								tarifario.cliente_operadores
							INNER JOIN tarifario.cadastro_clientes on tarifario.cliente_operadores.fk_cad_cli = tarifario.cadastro_clientes.pk_cad_cli
                            WHERE
								 operador_id = $1
							and
							     senha = $2
							and
                                tarifario.cadastro_clientes.ativo = 'true' ");
	$result_client_operadores = pg_execute($conn, "tar_client_operadores", array("$login", "$pass"));
	$client_operadores_recordcount = pg_numrows($result_client_operadores);



	if ($client_operadores_recordcount != 0) {

		for ($row1  = 0; $row1  < pg_numrows($result_client_operadores); $row1++) {
			$pk_cad_cli = pg_result($result_client_operadores, $row1, 'fk_cad_cli');
		}


		$tar_client = "SELECT
						cod_agrup,
						mneu_cli,
						nome_cli,
						root_srv,
						mkp_htl,
						mkp_srv,
						mkp_food,
						login,
						pass,
						ativo,
						ativo_virtuoso,
						descricao_tar,
						lang,
						emp,
						pk_cad_cli,
						extranet,
						root_htl,
						ativo2tar,
						root_htl2,
						mkp_htl2,
						mkp_srv2,
						mkp_food2,
						fk_depto,
						mkp_eco,
						mkp_eco2,
						mkp_ny,
						mkp_ny2,
						mkp_carn,
						mkp_carn2,
						mkp_winn,
						mkp_winn2,
						fk_controle_acesso_ws,
						ativo_cote 
					FROM
						tarifario.cadastro_clientes
					WHERE
				         pk_cad_cli = '$pk_cad_cli'";


		$result_tar_client = pg_exec($conn, $tar_client);
		for ($row  = 0; $row  < pg_numrows($result_tar_client); $row++) {
			$lang = pg_result($result_tar_client, $row, 'lang');
			$_SESSION['sub_cli'] = 0;
			$_SESSION['user'] = pg_result($result_tar_client, $row, 'pk_cad_cli');
			$user = pg_result($result_tar_client, $row, 'pk_cad_cli');
			$pk_cad_cli = pg_result($result_tar_client, $row, 'pk_cad_cli');
			$_SESSION['tarifario'] = pg_result($result_tar_client, $row, 'root_htl');
			$_SESSION['mkp_htl'] = pg_result($result_tar_client, $row, 'mkp_htl');
			$_SESSION['mkp_srv'] = pg_result($result_tar_client, $row, 'mkp_srv');
			$_SESSION['mkp_food'] = pg_result($result_tar_client, $row, 'mkp_htl');
			$_SESSION['tarifario2'] = pg_result($result_tar_client, $row, 'root_htl2');
			$_SESSION['mkp_htl2'] = pg_result($result_tar_client, $row, 'mkp_htl2');
			$_SESSION['mkp_srv2'] = pg_result($result_tar_client, $row, 'mkp_srv2');
			$_SESSION['mkp_food2'] = pg_result($result_tar_client, $row, 'mkp_htl2');
			$_SESSION['mkp_eco'] = pg_result($result_tar_client, $row, 'mkp_eco');
			$_SESSION['mkp_eco2'] = pg_result($result_tar_client, $row, 'mkp_eco2');
			$_SESSION['mkp_ny'] = pg_result($result_tar_client, $row, 'mkp_ny');
			$_SESSION['mkp_ny2'] = pg_result($result_tar_client, $row, 'mkp_ny2');
			$_SESSION['mkp_carn'] = pg_result($result_tar_client, $row, 'mkp_carn');
			$_SESSION['mkp_carn2'] = pg_result($result_tar_client, $row, 'mkp_carn2');
			$_SESSION['mkp_winn'] = pg_result($result_tar_client, $row, 'mkp_winn');
			$_SESSION['mkp_winn2'] = pg_result($result_tar_client, $row, 'mkp_winn2');
			$_SESSION['lang'] = pg_result($result_tar_client, $row, 'lang');
			$_SESSION['emp'] = pg_result($result_tar_client, $row, 'emp');
			$_SESSION['mkp_htl_sub'] = 0;
			$_SESSION['mkp_srv_sub'] = 0;
			$_SESSION['mkp_fd_sub'] = 0;
			$_SESSION['filtro_data_in'] = 0;
			$_SESSION['filtro_data_out'] = 0;
			$_SESSION['add_percent'] = 0;
			$_SESSION['price_from'] = 0;
			$_SESSION['price_to'] = 0;
			$_SESSION['filtro_estrela'] = 0;
			$_SESSION['mneufor'] = 0;
			$_SESSION['cid'] = 0;
			$_SESSION['eco'] = 0;
			//essa info vem da tabela  conteudo_internet.chat_deptos
			$_SESSION['depto'] = pg_result($result_tar_client, $row, 'fk_depto');
			$_SESSION['pass'] = $pass;
			$_SESSION['login'] = $login;
			$_SESSION['mneu_cli']  = pg_result($result_tar_client, $row, 'mneu_cli');
			$mneu_cli  = pg_result($result_tar_client, $row, 'mneu_cli');
			$_SESSION['ativo_virtuoso']  = pg_result($result_tar_client, $row, 'ativo_virtuoso');
			$ativo_virtuoso = pg_result($result_tar_client, $row, 'ativo_virtuoso');
			$_SESSION['ativo_cote']  = pg_result($result_tar_client, $row, 'ativo_cote');



			$busca_uma_moeda = "SELECT * 
                    from cmnet_integration.moeda_cliente 
	                inner join cmnet_integration.moeda on cmnet_integration.moeda_cliente.moeda = cmnet_integration.moeda.id
	                where cliente = '$mneu_cli'";
			$result_uma_moeda = pg_exec($conn, $busca_uma_moeda);

			$achou_moeda =  pg_numrows($result_uma_moeda);

			if ($achou_moeda != 0) {
				for ($moe = 0; $moe < pg_numrows($result_uma_moeda); $moe++) {
					$umid = pg_result($result_uma_moeda, $moe, 'moeda');
					$umnome = pg_result($result_uma_moeda, $moe, 'nome');
					$umsimbolo = pg_result($result_uma_moeda, $moe, 'simbolo');
					$_SESSION['selected_moeda'] = $umsimbolo . ' ' . $umnome . ' ';
				}
			} else {
				$_SESSION['selected_moeda'] = "US$ Dollar";
			}
		}




		//verifico se tem o reservas online
		$reserva_online = "select
						usa_reservas_online
					from
					        booking_ws.controle_acesso_ws
					inner join 
					        tarifario.cadastro_clientes 
					on       
						booking_ws.controle_acesso_ws.pk_controle_acesso_ws = tarifario.cadastro_clientes.fk_controle_acesso_ws
					where 
					pk_cad_cli =  $pk_cad_cli";
		$result_reserva_online = pg_exec($conn, $reserva_online);
		$reserva_online_recordcount =	 pg_numrows($result_reserva_online);
		for ($row3  = 0; $row3  < pg_numrows($result_reserva_online); $row3++) {
			$usa_reservas_online = pg_result($result_reserva_online, $row3, 'usa_reservas_online');
		}

		if ($reserva_online_recordcount == 0) {
			$_SESSION['online'] = 0;
		} else {
			if ($usa_reservas_online == 't') {
				$_SESSION['online'] = 1;
			} else {
				$_SESSION['online'] = 0;
			}
		}



		//echo$reserva_online;     
		//exit();     



		//registro no log do tarifario
		$area = 8;
		$complemento = 'Tarifario';
		require('insert_log_tarifario.php');






		//faço o redirecionamento para o tarifario  
		if ($imgbnk == 1) {
			header('Location: bankimage.php');
		} else {
			header('Location: index.php');
		}

		/*
			 elseif ($ativo_virtuoso == 't')
		 {
			 header('Location: index_virtuoso.php');
		 }
	 */
	} else {

		echo "<script>
				 alert('incorrect login information');
				 location = 'https://www.blumar.com.br';
				 </script>";
	}
} else {

	//se achar as informações de logon no cadastro de clientes continua		 
	for ($row  = 0; $row  < pg_numrows($result_tar_client); $row++) {
		$_SESSION['sub_cli'] = 0;
		$_SESSION['user'] = pg_result($result_tar_client, $row, 'pk_cad_cli');
		$user = pg_result($result_tar_client, $row, 'pk_cad_cli');
		$pk_cad_cli = pg_result($result_tar_client, $row, 'pk_cad_cli');
		$_SESSION['tarifario'] = pg_result($result_tar_client, $row, 'root_htl');
		$_SESSION['mkp_htl'] = pg_result($result_tar_client, $row, 'mkp_htl');
		$_SESSION['mkp_srv'] = pg_result($result_tar_client, $row, 'mkp_srv');
		$_SESSION['mkp_food'] = pg_result($result_tar_client, $row, 'mkp_htl');
		$_SESSION['tarifario2'] = pg_result($result_tar_client, $row, 'root_htl2');
		$_SESSION['mkp_htl2'] = pg_result($result_tar_client, $row, 'mkp_htl2');
		$_SESSION['mkp_srv2'] = pg_result($result_tar_client, $row, 'mkp_srv2');
		$_SESSION['mkp_food2'] = pg_result($result_tar_client, $row, 'mkp_htl2');
		$_SESSION['mkp_eco'] = pg_result($result_tar_client, $row, 'mkp_eco');
		$_SESSION['mkp_eco2'] = pg_result($result_tar_client, $row, 'mkp_eco2');
		$_SESSION['mkp_ny'] = pg_result($result_tar_client, $row, 'mkp_ny');
		$_SESSION['mkp_ny2'] = pg_result($result_tar_client, $row, 'mkp_ny2');
		$_SESSION['mkp_carn'] = pg_result($result_tar_client, $row, 'mkp_carn');
		$_SESSION['mkp_carn2'] = pg_result($result_tar_client, $row, 'mkp_carn2');
		$_SESSION['mkp_winn'] = pg_result($result_tar_client, $row, 'mkp_winn');
		$_SESSION['mkp_winn2'] = pg_result($result_tar_client, $row, 'mkp_winn2');
		$_SESSION['lang'] = pg_result($result_tar_client, $row, 'lang');
		$_SESSION['emp'] = pg_result($result_tar_client, $row, 'emp');
		$_SESSION['mkp_htl_sub'] = 0;
		$_SESSION['mkp_srv_sub'] = 0;
		$_SESSION['mkp_fd_sub'] = 0;
		$_SESSION['filtro_data_in'] = 0;
		$_SESSION['filtro_data_out'] = 0;
		$_SESSION['add_percent'] = 0;
		$_SESSION['price_from'] = 0;
		$_SESSION['price_to'] = 0;
		$_SESSION['filtro_estrela'] = 0;
		$_SESSION['mneufor'] = 0;
		$_SESSION['cid'] = 0;
		$_SESSION['eco'] = 0;
		//essa info vem da tabela  conteudo_internet.chat_deptos
		$_SESSION['depto'] = pg_result($result_tar_client, $row, 'fk_depto');
		$_SESSION['pass'] = pg_result($result_tar_client, $row, 'pass');
		$_SESSION['login'] = pg_result($result_tar_client, $row, 'login');
		$_SESSION['mneu_cli']  = pg_result($result_tar_client, $row, 'mneu_cli');
		$mneu_cli  = pg_result($result_tar_client, $row, 'mneu_cli');
		$_SESSION['ativo_virtuoso']  = pg_result($result_tar_client, $row, 'ativo_virtuoso');
		$ativo_virtuoso = pg_result($result_tar_client, $row, 'ativo_virtuoso');
		$_SESSION['ativo_cote']  = pg_result($result_tar_client, $row, 'ativo_cote');

		$busca_uma_moeda = "SELECT * 
                    from cmnet_integration.moeda_cliente 
	                inner join cmnet_integration.moeda on cmnet_integration.moeda_cliente.moeda = cmnet_integration.moeda.id
	                where cliente = '$mneu_cli'";
		$result_uma_moeda = pg_exec($conn, $busca_uma_moeda);

		$achou_moeda =  pg_numrows($result_uma_moeda);

		if ($achou_moeda != 0) {
			for ($moe = 0; $moe < pg_numrows($result_uma_moeda); $moe++) {
				$umid = pg_result($result_uma_moeda, $moe, 'moeda');
				$umnome = pg_result($result_uma_moeda, $moe, 'nome');
				$umsimbolo = pg_result($result_uma_moeda, $moe, 'simbolo');
				$_SESSION['selected_moeda'] = $umsimbolo . ' ' . $umnome . ' ';
			}
		} else {
			$_SESSION['selected_moeda'] = "US$ Dollar";
		}
	}


	//verifico se tem o reservas online
	$reserva_online = "select
						usa_reservas_online
					from
					        booking_ws.controle_acesso_ws
					inner join 
					        tarifario.cadastro_clientes 
					on       
						booking_ws.controle_acesso_ws.pk_controle_acesso_ws = tarifario.cadastro_clientes.fk_controle_acesso_ws
					where 
					pk_cad_cli =  $pk_cad_cli";
	$result_reserva_online = pg_exec($conn, $reserva_online);
	$reserva_online_recordcount =	 pg_numrows($result_reserva_online);
	for ($row3  = 0; $row3  < pg_numrows($result_reserva_online); $row3++) {
		$usa_reservas_online = pg_result($result_reserva_online, $row3, 'usa_reservas_online');
	}

	if ($reserva_online_recordcount == 0) {
		$_SESSION['online'] = 0;
	} else {
		if ($usa_reservas_online == 't') {
			$_SESSION['online'] = 1;
		} else {
			$_SESSION['online'] = 0;
		}
	}



	//echo$reserva_online;     
	//exit();     



	//registro no log do tarifario
	$area = 8;
	$complemento = 'Tarifario';
	require('insert_log_tarifario.php');






	//faço o redirecionamento para o tarifario  
	if ($imgbnk == 1) {
		header('Location: bankimage.php');
	} else {
		if (isset($_SESSION['urlorig'])) {
			header('Location:' .	$_SESSION['urlorig'] . '');
		} else {
			header('Location: index.php');
		}
	}

	/*
			 elseif ($ativo_virtuoso == 't')
		 {
			 header('Location: index_virtuoso.php');
		 }
	 */
}




//echo'-'.$login.'-'.$pass.'-'.$imgbnk.'-';
