<?php

class TCommercialCategory extends TObjetStd {
	
	function __construct() {
        global $langs;
         
        parent::set_table(MAIN_DB_PREFIX.'commercial_category');
        parent::add_champs('fk_user,fk_category',array('type'=>'int','index'=>true));
		
        parent::_init_vars('label');
        parent::start();    
		
		
    }
	
	static function add(&$PDOdb, $fk_category, $fk_user) {
		
		$o=new TCommercialCategory;
		if(!$o->loadByCategoryUser($PDOdb, $fk_category, $fk_user)) {
			$o->fk_category = $fk_category;
			$o->fk_user = $fk_user;
			$o->save($PDOdb);
			
			$o->updateSoc($PDOdb);
		}
	}
	static function del(&$PDOdb, $fk_category, $fk_user) {
		
		$o=new TCommercialCategory;
		if($o->loadByCategoryUser($PDOdb, $fk_category, $fk_user)) {
			$o->delete($PDOdb);		
			$o->updateSoc($PDOdb);	
		}
	}

	function updateSoc(&$PDOdb) {
		
		global $langs,$conf,$user,$db;
		$TUser = TCommercialCategory::getUser($PDOdb, $this->fk_category);
		$TSociete = TCommercialCategory::getSociete($PDOdb, $this->fk_category);
		
		$nb_user = count($TUser);
		$nb_soc = count($TSociete);
		
		foreach($TSociete as &$soc) {
			if(!empty($soc->TUserIdAffected)) {
				foreach($soc->TUserIdAffected as $idcomm) {
					$soc->add_commercial($user, $idcomm);
				}
				
			}

			$listsalesrepresentatives=$soc->getSalesRepresentatives($user);			
			foreach($listsalesrepresentatives as &$comm) {
				if(!in_array($comm['id'], $soc->TUserIdAffected)) {
					$soc->del_commercial($user, $comm['id']);
				}
			}

		}
		
		setEventMessage($langs->trans('CategUserAffectation', $nb_user, $nb_soc));
	}

	function loadByCategoryUser(&$PDOdb, $fk_category, $fk_user) {
		
		$PDOdb->Execute("SELECT rowid FROM ".$this->get_table()." WHERE fk_user = ".$fk_user." AND fk_category = ".$fk_category);
		if($obj = $PDOdb->Get_line()) {
			return $this->load($PDOdb, $obj->rowid);
		}
		
		return false;
	}

	static function getUser(&$PDOdb, $fk_category) {
		
		global $conf,$db;
		
		$Tab = $PDOdb->ExecuteAsArray("SELECT fk_user FROM ".MAIN_DB_PREFIX."commercial_category WHERE fk_category=".$fk_category);
	
		$TUser = array();
		foreach($Tab as &$row) {
			
			$u=new User($db);
			if($u->fetch($row->fk_user)>0) {
				$TUser[] = $u;
			}
			
		}
		
		
		return $TUser;
		
	}
	
	static function getAllUserForSociete(&$PDOdb, $fk_soc) {
		//$PDOdb->debug=true;
		$Tab = $PDOdb->ExecuteAsArray("SELECT DISTINCT fk_user 
				FROM ".MAIN_DB_PREFIX."commercial_category cc
				LEFT JOIN ".MAIN_DB_PREFIX."categorie_societe cs ON (cs.fk_categorie = cc.fk_category )
				WHERE cs.fk_soc=".$fk_soc);
				
				
		$TUserId=array();
		foreach($Tab as &$row) {
			$TUserId[] = $row->fk_user;	
		}
		
		return $TUserId;
		
	}
	
	static function getSociete(&$PDOdb, $fk_category) {
		
		global $conf,$db;
		
		dol_include_once('/societe/class/societe.class.php');
		
		$Tab = $PDOdb->ExecuteAsArray("SELECT DISTINCT fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe WHERE fk_categorie=".$fk_category);
	
		$TSoc = array();
		foreach($Tab as &$row) {
			
			$o=new Societe($db);
			if($o->fetch($row->fk_soc)>0) {
				
				$o->TUserIdAffected = TCommercialCategory::getAllUserForSociete($PDOdb, $o->id);
				
				$TSoc[] = $o;
			}
			
		}
		
		
		return $TSoc;
		
	}

}