<?php
/*
Ulteo License
*/
require_once(INSTALL_PATH."/server/classes/class.AbstractConfDriver.php");
class ulteoConfDriver extends AbstractConfDriver {
		
	var $repoSerialFile;
	var $usersSerialDir;
	
	function init($options){
		parent::init($options);
		$this->repoSerialFile = $options["REPOSITORIES_FILEPATH"];
		$this->usersSerialDir = $options["USERS_DIRPATH"];
	}
	
	// SAVE / EDIT / CREATE / DELETE REPOSITORY
	function listRepositories(){
		return Utils::loadSerialFile($this->repoSerialFile);
		
	}
	/**
	 * Unique ID of the repositor
	 *
	 * @param String $repositoryId
	 * @return Repository
	 */
	function getRepositoryById($repositoryId){
		$repositories = Utils::loadSerialFile($this->repoSerialFile);
		if(isSet($repositories[$repositoryId])){
			return $repositories[$repositoryId];		
		}
		return null;
	}
	/**
	 * Store a newly created repository 
	 *
	 * @param Repository $repositoryObject
	 * @param Boolean $update 
	 * @return -1 if failed
	 */
	function saveRepository($repositoryObject, $update = false){
		$repositories = Utils::loadSerialFile($this->repoSerialFile);
		if(!$update){
			$repositoryObject->writeable = true;
			$repositories[$repositoryObject->getUniqueId()] = $repositoryObject;
		}else{
			foreach ($repositories as $index => $repo){
				if($repo->getUniqueId() == $repositoryObject->getUniqueId()){
					$repositories[$index] = $repositoryObject;
					break;
				}
			}
		}
		$res = Utils::saveSerialFile($this->repoSerialFile, $repositories);
		if($res == -1){
			return $res;
		}		
	}
	/**
	 * Delete a repository, given its unique ID.
	 *
	 * @param String $repositoryId
	 */	
	function deleteRepository($repositoryId){
		$repositories = Utils::loadSerialFile($this->repoSerialFile);
		$newList = array();
		foreach ($repositories as $repo){
			if($repo->getUniqueId() != $repositoryId){
				$newList[$repo->getUniqueId()] = $repo;
			}
		}
		Utils::saveSerialFile($this->repoSerialFile, $newList);
	}
	
	// SAVE / EDIT / CREATE / DELETE USER OBJECT (except password)
	/**
	 * Instantiate the right class
	 *
	 * @param AbstractAjxpUser $userId
	 */
	function instantiateAbstractUserImpl($userId){
		return new AJXP_User($userId, $this);
	}
	
	
	function getUserClassFileName(){
		return INSTALL_PATH."/plugins/conf.ulteo/class.AJXP_User.php";
	}	
}
