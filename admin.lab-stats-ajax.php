<?php
	header("Content-Type: text/html; charset=UTF-8");
	
	require_once dirname(__FILE__).'/inc/globals.php';
	require_once dirname(__FILE__).'/admin/aura/globals.php';

	authRestritoAdmin();
	
	$aLabId = isset($_REQUEST['lab']) ? (int)$_REQUEST['lab'] : 0;

	$aLab			= Aura\Groups::getByClue($aLabId);
	$aDevices 		= Aura\Groups::findDevices($aLabId);
	$aUsers	  		= Aura\Pings::findActiveUsers($aDevices);
	$aActiveDevices = Aura\Pings::findActiveDevices($aDevices);
	$aInternet		= Aura\Utils::hasInternetAccess($aActiveDevices);

	// Computadores
	echo '<div class="span4 aura-bloco">';
		$aAtivos = count($aActiveDevices);

		if($aAtivos > 0) {
			echo '<ul class="aura-bloco-opts">';
			echo '<div class="btn-group">';
			echo '<a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#"><i class="icon-cog icon-black"></i><span class="caret"></span></a>';
			echo '<ul class="dropdown-menu">';
			echo '<li><a href="javascript:void(0)" onclick="AURA.sendCommand(\'Desligue os computadores do '.$aLab['name'].'\');"><i class="icon-off"></i> Desligar todos</a></li>';
			echo '</ul>';
			echo '</div>';
			echo '</ul>';
		}
	
		echo '<img src="./img/icos/computador.png" title="Computadores" />';
		echo '<h2>Computadores</h2>';
		$aTotalDispositivos = count($aDevices);
		if($aTotalDispositivos == 0) {
			echo '<p>Nenhum cadastrado</p>';
		} else {
			echo '<p>Total <strong>'.$aTotalDispositivos.'</strong>, ligados <strong>'.count($aActiveDevices).'</strong></p>';
		}
	echo '</div>';

	// Usuários
	echo '<div class="span4 aura-bloco">';
		$aLogados = count($aUsers);
			
		if($aLogados > 0) {
			echo '<ul class="aura-bloco-opts">';
				echo '<div class="btn-group">';
					echo '<a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#"><i class="icon-cog icon-black"></i><span class="caret"></span></a>';
					echo '<ul class="dropdown-menu">';
						echo '<li><a href="javascript:void(0)" onclick="AURA.sendCommand(\'Deslogue os usuários do '.$aLab['name'].'\');"><i class="icon-remove"></i> Deslogar todos</a></li>';
					echo '</ul>';
				echo '</div>';
			echo '</ul>';
		}
	
		echo '<img src="./img/icos/pessoa.png" title="Usuários" />';
		echo '<h2>Usuários</h2>';
	
		if($aLogados == 0) {
			echo '<p>Ninguém logado</p>';
		} else if($aLogados == 1) {
			echo '<p><strong>Um</strong> usuário logado</p>';
		} else {
			echo '<p><strong>'.$aLogados.'</strong> usuários logados</p>';
		}
	echo '</div>';

	// Internet
	echo '<div class="span4 aura-bloco">';
		if($aInternet['status'] != 'desconhecida') {
			echo '<ul class="aura-bloco-opts">';
				echo '<div class="btn-group">';
					echo '<a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#"><i class="icon-cog icon-black"></i><span class="caret"></span></a>';
					echo '<ul class="dropdown-menu">';
						if($aInternet['status'] == 'online') {
							echo '<li><a href="javascript:void(0)" onclick="AURA.sendCommand(\'Desligue a internet do '.$aLab['name'].'\');"><i class="icon-ban-circle"></i> Desativar internet</a></li>';
						} else {
							echo '<li><a href="javascript:void(0)" onclick="AURA.sendCommand(\'Ligue a internet do '.$aLab['name'].'\');"><i class="icon-ok-sign"></i> Ativar internet</a></li>';
						}
					echo '</ul>';
				echo '</div>';
			echo '</ul>';
		}

		echo '<img src="./img/icos/internet.png" title="Internet" />';
		echo '<h2>Internet</h2>';
		if($aInternet['status'] == 'desconhecida') {
			echo '<span class="label label-warning">Desconhecida</span>';
		} else {
			echo $aInternet['status'] == 'online' ? '<span class="label label-success">Online</span>' : '<span class="label label-important">Offline</span>';
		}
	echo '</div>';
	
	exit();
?>