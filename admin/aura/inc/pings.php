<?php 

/**
 * Gerenciamento e manipulação de pings. Os pings são mensagens enviadas
 * pelos clientes Aura para informar o seu status atual, como acesso à internet,
 * espaço em disco, usuários logados, etc.
 */

namespace Aura;

class Pings {
	/**
	 * Insere um ping novo no banco de dados.
	 * 
	 * @param int $theDeviceId id do dispositivo que gerou o ping.
	 * @param array $theInfos array assossiativo com as informações a serem inseridas.
	 * @throws \Exception
	 */
	public static function add($theDeviceId, $theInfos) {
		$theDeviceId = (int)$theDeviceId;
		
		if(empty($theInfos) || empty($theInfos['data'])) {
			throw new \Exception('Ping sem qualquer informação.');
		}
		
		if(Devices::getByClue($theDeviceId) == null) {
			throw new \Exception('O dispositivo informado não existe.');
		}
		
		$aInfo 				= Utils::prepareForSql($theInfos);
		$aInfo['fk_device'] = $theDeviceId; 
		
		Db::execute("INSERT INTO ".Db::TABLE_PINGS." (`".implode("`,`", array_keys($aInfo))."`) VALUES (".implode(',', $aInfo).")");
		return true;
	}

	/** 
	 * Obtem informações de pings de um grupo de dispositivos.
	 * 
	 * @param array $theIds array com os ids dos dispositivos a serem buscados.
	 * @param int $theSinceUnixtime timestamp a partir do qual os logs serão buscados.
	 * @return array array assossiativo com informações dos pings, no formato [id_device] => array(pings).
	 */
	public static function findByDevices($theIds, $theSinceUnixtime) {
		$aRet	 			= array();
		$aIds 				= Utils::prepareForSql($theIds);
		$theSinceUnixtime 	= (int)$theSinceUnixtime;
		
		$aResult 			= Db::execute("SELECT * FROM ".Db::TABLE_PINGS." WHERE fk_device IN (".implode(',', $aIds).") AND time >= " . $theSinceUnixtime);
		
		if(Db::numRows($aResult) > 0) {
			while($aRow = Db::fetchAssoc($aResult)) {
				$aRet[$aRow['fk_device']][] = $aRow;
			}
		}
		
		return $aRet;
	}
	
	public static function remove($theOlderThanTimestamp) {
		$theOlderThanTimestamp = (int)$theOlderThanTimestamp;
		return Db::execute("DELETE FROM ".Db::TABLE_PINGS." WHERE time <= " . $theOlderThanTimestamp);
	}
}
?>