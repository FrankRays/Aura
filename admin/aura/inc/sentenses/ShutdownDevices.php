<?php 

function shutdownDevices($theGroupName) {
	$aGroup = Aura\Groups::getByClue($theGroupName);

	if($aGroup === null) {
		echo 'Não conheço o grupo ' . $theGroupName.'.';
		return;
	}

	$aDevices = Aura\Groups::findDevices($aGroup['id']);

	if(count($aDevices) > 0) {
		$aPings  = Aura\Pings::findByDevices($aDevices, time() - 60 * 3);
		$aReport = Aura\Utils::generateLabReport($aPings); 

		if(count($aReport['computers']) > 0) {
			$aCommand = array(
				'win' 	=> 'shutdown -s -t 10 -c "O computador vai desligar em 30 segundos. Salve tudo aberto agora!"',
				'linux' => '',
				'mac' 	=> '',
			);
			$aTask = array(
				'time' 		=> time(),
				'priority' 	=> 1,
				'status' 	=> Aura\Tasks::STATUS_RUNNING,
				'exec' 		=> serialize($aCommand)
			);
			Aura\Tasks::add($aTask, $aReport['computers']);
			echo 'Ok, os computadores serão desligados em 30 segundos.';
		} else {
			echo 'Todos os computadores já estão desligados.';
		}
	} else {
		echo 'O grupo '.$theGroupName.' não tem computadores.';
	}
}

Aura\Interpreter::addSentenseHandler('shutdownDevices', '/(desligue|desligar?|apagar?|apague)(todos |todas )?( os| as)? (computadores|computador|aparelhos|dispositivos|pcs|máquinas|equipamentos|coisos|coisas) d(a|o) ([\w\W]*)/', array(6));

?>