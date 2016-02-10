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
			
			$o->updateAllSoc($PDOdb);
		}
	}
	static function del(&$PDOdb, $fk_category, $fk_user) {
		
		$o=new TCommercialCategory;
		if($o->loadByCategoryUser($PDOdb, $fk_category, $fk_user)) {
			$o->delete($PDOdb);		
			$o->updateAllSoc($PDOdb);	
		}
	}

	function updateAllSoc(&$PDOdb) {
		
		global $langs,$conf,$user,$db;
		$TUser = TCommercialCategory::getUser($PDOdb, $this->fk_category); // useless, just for popup
		$nb_user = count($TUser);
		
		$TSociete = TCommercialCategory::getSociete($PDOdb, $this->fk_category);
		$nb_soc = count($TSociete);
		
		foreach($TSociete as &$soc) {
			
			self::pdateSociete($PDOdb,$soc);

		}
		
		setEventMessage($langs->trans('CategUserAffectation', $nb_user, $nb_soc));
	}

	static function updateSociete(&$PDOdb, &$soc, $fk_category = null) {
		global $langs,$conf,$user,$db;
		
		$TUserIdAffected = TCommercialCategory::getAllUserForSociete($PDOdb, $soc->id,$fk_category);
		
		if(!empty($TUserIdAffected)) {
			foreach($TUserIdAffected as $idcomm) {
			//	print "Ajout $idcomm dans ".$soc->id."<br>";
				$res = $soc->add_commercial($user, $idcomm);
				
			}
			
		}

		$listsalesrepresentatives=$soc->getSalesRepresentatives($user);		
		
		foreach($listsalesrepresentatives as &$comm) {
			if(!in_array($comm['id'], $TUserIdAffected)) {
				$soc->del_commercial($user, $comm['id']);
			}
		}
		
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
	
	static function getAllUserForSociete(&$PDOdb, $fk_soc,$fk_category=null) {
		//$PDOdb->debug=true;
		
		$sql ="SELECT DISTINCT fk_user 
				FROM ".MAIN_DB_PREFIX."commercial_category cc
				LEFT JOIN ".MAIN_DB_PREFIX."categorie_societe cs ON (cs.fk_categorie = cc.fk_category )
				WHERE cs.fk_soc=".$fk_soc;
		
		if($fk_category>0) $sql.=" OR cc.fk_category=".$fk_category;
		
		$Tab = $PDOdb->ExecuteAsArray($sql);
				
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
				
				$TSoc[] = $o;
			}
			
		}
		
		
		return $TSoc;
		
	}

}