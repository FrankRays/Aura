<?php

	/**
	 * Funções do cliente. 
	 */
	
	function loadConfigFile() {
		$aIniArray = parse_ini_file(dirname(__FILE__) . "/config.ini");

		define('BRAIN_URL', 		$aIniArray['brain_url']);
		define('PING_INTERVAL', 	$aIniArray['brain_pulling_interval']);		
	}

	function getUrl($theUrl) {
		$aUserAgent = 'Aura Client/1.0 ('.AURA_OS_NAME.'; '.AURA_OS_VERSION.')';
		
		$aCh = curl_init($theUrl);
		curl_setopt($aCh, CURLOPT_SSL_VERIFYPEER, 	false);
		curl_setopt($aCh, CURLOPT_USERAGENT, 		$aUserAgent);
		curl_setopt($aCh, CURLOPT_RETURNTRANSFER, 	1);
		curl_setopt($aCh, CURLOPT_CONNECTTIMEOUT, 	10);
		curl_setopt($aCh, CURLOPT_FAILONERROR, 		1);
		
		$aResult = curl_exec($aCh);
		$aRet	 = curl_errno($aCh) ? false : $aResult; 
		curl_close($aCh);
		
		return $aRet;
	}
	
	function logMsg($theMsg) {
		echo date('[h:i:s d/m/Y]') . " ".$theMsg . "\n";
	}
	
	/**
	 * Executa os comandos recebidos do cérebro. Os comandos devem vir no seguinte formato:
	 * 
	 * array(
	 * 		'win' 	=> 'versão windows do comando' 
	 * 		'mac' 	=> 'versão mac do comando' 
	 * 		'linux' => 'versão linux do comando' 
	 * 	) 
	 * @param array $theCommand comando a ser executado, indexado pela plataforma, que poder 'win', 'mac' ou 'linux'. 
	 */
	function runCommand($theCommand) {
		$aRet 		= '';
		$aCommand 	= array();
		
		if(is_string($theCommand)) {
			$aCommand = @unserialize($theCommand);
		}

		if(isset($aCommand[AURA_OS])) {
			ob_start();
			$aOut = trim(shell_exec($aCommand[AURA_OS]));
			$aRet = empty($aOut) ? ob_get_contents() : $aOut;
			ob_end_clean(); 
		} else {
			$aRet = 'Nao suportado em '.AURA_OS.':' . print_r($theCommand, true);
		}
		
		return $aRet;
	}
	
	function pingBrain() {
		static $aLastPing;
		
		if(($aLastPing + PING_INTERVAL) <= time()) {
			$aData = array(
				'ping_ip'				=> 0, 	// percentagem de pacotes perdidos.
				'ping_host'				=> 0,	// percentagem de pacotes perdidos.
				'storage_total'			=> -1,	// tamanho em bytes do HD principal
				'storage_available'		=> -1,	// bytes disponíveis no HD principal.
				'users'					=> ''	// usuários logados
			);
			
			switch(AURA_OS) {
				case 'win':
					$aData = getSystemInfosWindows();
					break;
					
				case 'mac':
				case 'linux':
					$aData = getSystemInfosLinux();
					break;
			}
			
			logMsg('Enviando ping.');
			getUrl(BRAIN_URL . '?method=ping&device='.AURA_HOSTNAME.'&time='.time().'&data='.urlencode(serialize($aData)));
			
			$aLastPing = time();
		}
	}
	
	function getSystemInfosWindows() {
		$aData 		= array();
		
		// Pings
		$aOut 				= trim(shell_exec('ping -n 5 -w 1000 8.8.8.8'));
		$aMatches 			= array();
		
		preg_match_all('/ \(([0-9]+)%/', $aOut, $aMatches);
		$aData['ping_ip'] 	= isset($aMatches[1][0]) ? $aMatches[1][0] : NULL;

		// Espaço no HD
		$aMatches 					= array();
		$aOut 						= trim(shell_exec('fsutil volume diskfree c:'));
		$aTemp						= explode("\n", $aOut);
		$aFreeBytes 				= explode(':', $aTemp[2]);
		$aTotalBytes 				= explode(':', $aTemp[1]);

		$aData['storage_total'] 	 = trim($aTotalBytes[1]);
		$aData['storage_available']  = trim($aFreeBytes[1]);

		// Usuários logados
		$aUsers				= array();
		$aMatches 			= array();
		$aOut 				= trim(shell_exec('qwinsta'));
		preg_match_all('/(\w+)\s+(.*)([0-9]+)(.*)/', $aOut, $aMatches);

		if(count($aMatches[2])) {
			foreach($aMatches[2] as $aIndex => $aUserName) {
				$aUsers[] = array('name' => trim($aUserName), 'status' => trim($aMatches[4][$aIndex]));
			}			
		}
		$aData['users'] = serialize($aUsers);
		
		return $aData;
	}
	
	function getSystemInfosLinux() {
		$aData 		= array();
	
		// Pings
		$aOut 				= trim(shell_exec('ping -c 5 -q 8.8.8.8'));
		$aMatches 			= array();
	
		preg_match_all('/ ([0-9]+)%/', $aOut, $aMatches);
		$aData['ping_ip'] 	= isset($aMatches[1][0]) ? $aMatches[1][0] : NULL;
	
		// Espaço no HD
		$aMatches 					= array();
		$aOut 						= trim(shell_exec('df'));
		preg_match_all('/ ([0-9]+) +([0-9]+) +([0-9]+).*\//', $aOut, $aMatches);
		$aData['storage_total'] 	= trim(isset($aMatches[1][0]) ? $aMatches[1][0] : NULL);
		$aData['storage_available'] = trim(isset($aMatches[3][0]) ? $aMatches[3][0] : NULL);
	
		// Usuários logados
		$aUsers				= array();
		$aMatches 			= array();
		$aOut 				= @explode(' ', trim(shell_exec('users')));
	
		if(count($aOut)) {
			foreach($aOut as $aUserName) {
				$aUsers[] = array('name' => trim($aUserName), 'status' => '');
			}
		}
		$aData['users'] = serialize($aUsers);
	
		return $aData;
	}
	
	function getMachineInfoWindows() {
		$aRet = array();
		
		$aRet['hostname'] = trim(shell_exec('hostname'));
		
		$aInfos 			= explode("\n", shell_exec('systeminfo'));
		$aTemp				= explode(':', $aInfos[2], 2);
		$aRet['os_name']	= trim($aTemp[1]);
		
		$aTemp				= explode(':', $aInfos[3], 2);
		$aRet['os_version']	= trim($aTemp[1]);
		
		return $aRet;
	}
	
	function getMachineInfoLinux() {
		$aRet = array();
	
		$aRet['hostname'] = trim(shell_exec('hostname'));
	
		$aInfos 			= explode("\n", shell_exec('lsb_release -a'));
		$aTemp				= explode(':', $aInfos[0], 2);
		$aRet['os_name']	= trim($aTemp[1]);
	
		$aTemp				= explode(':', $aInfos[2], 2);
		$aRet['os_version']	= trim($aTemp[1]);
		
		$aTemp				= explode(':', $aInfos[3], 2);
		$aRet['os_version']	.= ' ' . trim($aTemp[1]);
	
		return $aRet;
	}
?>