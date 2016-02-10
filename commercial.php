<?php

	require 'config.php';
	
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';
	
	dol_include_once('/commercialbycategory/class/commercialcategory.class.php');
	
	$langs->load("categories");
	
	$action = GETPOST('action');
	$id = (int)GETPOST('id');
	$commid = (int)GETPOST('commid');
		
	$PDOdb = new TPDOdb;
	
	switch ($action) {
		case 'add':
			
			TCommercialCategory::add($PDOdb, $id,$commid);
			
			_fiche($PDOdb,$id);
			
			break;
		case 'delete':
			
			TCommercialCategory::del($PDOdb, $id,$commid);
			
			_fiche($PDOdb,$id);
			
			break;
		default:
		
			_fiche($PDOdb,$id);
			
			break;
	}
	
	
function _fiche(&$PDOdb,$id) {
	global $conf,$db,$langs,$user,$form;
		
	$object = new Categorie($db);
	$result=$object->fetch($id);
	$object->fetch_optionals($id,$extralabels);
	if ($result <= 0)
	{
		dol_print_error($db,$object->error);
		exit;
	}
			
	
	llxHeader("","",$langs->trans("Categories"));
	
	$title=$langs->trans("CustomersCategoryShort");
	$head = categories_prepare_head($object,Categorie::TYPE_CUSTOMER);
	
	dol_fiche_head($head, 'commercial', $title, 0, 'category');
	
	print '<table class="border" width="100%">';
	print '<tr><td width="20%" class="notopnoleft">';
	$ways = $object->print_all_ways();
	print $langs->trans("Ref").'</td><td>';
	print '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
	foreach ($ways as $way)
	{
		print $way."<br>\n";
	}
	print '</td></tr>';

	// Description
	print '<tr><td width="20%" class="notopnoleft">';
	print $langs->trans("Description").'</td><td>';
	print dol_htmlentitiesbr($object->description);
	print '</td></tr>';
	print '<tr><td>'.$langs->trans("SalesRepresentatives").'</td>';
	print '<td>';
	
	$TUser = TCommercialCategory::getUser($PDOdb, $object->id);
	if(!empty($TUser)) {
		
		foreach($TUser as &$u) {
			
			echo $u->getNomUrl(1); 
			
			if ($user->rights->societe->creer)
			{
			    print '<a href="?id='.$object->id.'&commid='.$u->id.'&action=delete">';
			    print img_delete();
			    print '</a>';
			}
			print '<br />';
		}
	}
	
	print '</td></tr>';
	print '</table>';
	
	
	dol_fiche_end();
	
	if ($user->rights->societe->creer && $user->rights->societe->client->voir)
	{
		/*
		 * Copier... Coller... Jobi... Joba...
		 */
		$langs->load("users");
		$title=$langs->trans("ListOfUsers");

		$sql = "SELECT u.rowid, u.lastname, u.firstname, u.login";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u LEFT JOIN ".MAIN_DB_PREFIX."commercial_category cc ON (cc.fk_user = u .rowid)";
		$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
		if (! empty($conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND u.statut<>0 ";
		$sql.= " AND cc.rowid IS NULL ";

		$sql.= " ORDER BY u.lastname ASC ";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			print load_fiche_titre($title);

			// Lignes des titres
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Name").'</td>';
			print '<td>'.$langs->trans("Login").'</td>';
			print '<td>&nbsp;</td>';
			print "</tr>\n";

			$var=True;

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var]."><td>";
				print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->rowid.'">';
				print img_object($langs->trans("ShowUser"),"user").' ';
				print dolGetFirstLastname($obj->firstname, $obj->lastname)."\n";
				print '</a>';
				print '</td><td>'.$obj->login.'</td>';
				print '<td><a href="?id='.$object->id.'&commid='.$obj->rowid.'&action=add">'.$langs->trans("Add").'</a></td>';

				print '</tr>'."\n";
				$i++;
			}

			print "</table>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}	
	
	llxFooter();
	
}
