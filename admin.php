<?
include("core.php");

$anego->assign('pagetitle', getSetting('pagetitle') . " - Admin");
$anego->assign('showheader', !@$_GET['noheader']);

if (!LOGINOK) {
	$message = '';
	/* Login */
	
	if (@$_GET['a'] == 'login') {
	
		/* Slow down login attempts after the user entered the wrong password for 5 times */
		$loginattempts = getSetting("loginattempts", true);
		$attempts = 1;
		
		if ($loginattempts && time() - strtotime($loginattempts["lastmodified"]) < 60) {
			$attempts += $loginattempts["value"];
		}
		setSetting("loginattempts", $attempts);
		
		usleep(min(10000, max(0, ($attempts - 5) * 500))*1000);
		
		/*
			client_response = sha256(pass)
			database_pass = sha256(salt+sha256(passwd))
			sha256(salt + client_response) == db_pass
		*/
		$saltedPw = hash('sha256',$cfg['hash_salt'].@$_POST['response']);
		if (!empty($_POST['username']) && ValidAuth($_POST['username'], $saltedPw)) {
			setcookie(
				$cfg['cookieName'], 
				strtolower($_POST['username']) . "," . $saltedPw, 
				($_POST['staysigned']==1) ? (time()+$cfg['cookieTime']*3600) : 0,
				$cfg['path']
			);
			
			header('Location: ' . $cfg['path']);
			exit();
			
		} else {
			$message = __('Wrong username or password');
			$anego->assign("username", @$_POST["username"]);
		}
	}
	
	$anego->assign('message', $message);
	
	if(isset($_GET['noheader']) && $_GET['noheader']) {
		echo "200\nAdmin - Login\r\n";
		echo $anego->fetchContent('login.tpl');
	} else {
	
		$anego->AddJsModule('lo');
		//$anego->AddContent($logon);
		$anego->display('login.tpl');
	}
	
	exit();
}

if (!isset($_GET['a']) || $_GET['a']=='login') {
	$anego->Reload($cfg['domain']);
}

$anego->assign('action',1);

switch ($_GET['a']) {

	/****** Page loads or ajax page loads ******/
	
	/* Logout */
	case 'logout':
		setcookie($cfg['cookieName'], 'dead.', 0, $cfg['path']);
		
		if (!$_GET['noheader']) {
			$anego->Reload($cfg['domain']);
		} else {
			$json = array("content" => '<script type="text/javascript">location.href="'.$cfg['path'].'"</script>');
			exit("200\n".json_encode($json));
		}
		break;
	
	
	/* Page Administration */
	case 'pgad':
		/* Ajax call */
		if(isset($_GET['noheader'])) {
			if(UserRole() < Role::ProMod) BailErr(__('No permission to access this page, sorry.'));
			
			$json = array();
			$json['title'] = 'Anego - Admin';
			$json['js'] = array('ld.am');
			$json['content'] = PrintLinks();
			
			exit("200\n".json_encode($json));
		}
		
		/* Normal call */
		if(UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		AdminBar(-1);		
		
		$anego->AddJsModule("am");
		$anego->AddContent(PrintLinks());
		$anego->display('index.tpl');
		break;
		
	
	
	/* Admin settings */
	case 'setg':
		if(UserRole() < Role::Admin) {
			BailErr(__('No permission to access this page, sorry.'));
		}
		
		$settings = array();
		$res = mysql_query("SELECT * FROM ".SETTINGS);
		while ($row = mysql_fetch_array($res)) {
			$settings[$row['name']] = $row['value'];
		}
		
		$res = mysql_query("SELECT idx, name FROM ".PAGES." WHERE nolink=0 AND file='' ORDER BY name");
		$pages = array();
		while ($row = mysql_fetch_assoc($res)) {
			$pages[] = $row;
		}
		
		$anego->assign('settings', $settings);
		$anego->assign('pagelist', $pages);
		
		if (isset($_GET['noheader'])) {
			$json = array();
			$json['title'] = 'Anego - Admin';
			$json['js'] = 'ld.as'; //.jui
			//$json['css'] = 'styles/default/jui/jquery-ui.css';
			$json['content'] = $anego->fetchContent('settings.tpl');
			
			exit("200\n".json_encode($json));
		}
		
		AdminBar(-1);

		$anego->AddJsModule('as');
		$anego->display('settings.tpl');
		break;



	/****** AJAX Callback handlers ******/
	
	/* Settings - Website */
	case 'savesetweb':
		if(UserRole() < Role::Admin) BailErr(__('No permission to access this page, sorry.'));
		
		// Unfortunately can only be done one by one
		mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'firstpage\',\'' . mysql_real_escape_string($_POST['homepage'])  . '\')');
		mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'pagetitle\',\'' . mysql_real_escape_string($_POST['pagetitle']) . '\')');
		mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'keywords\',\'' . mysql_real_escape_string($_POST['keywords']) . '\')');
		mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'description\',\'' . mysql_real_escape_string($_POST['description']) . '\')');
		
		exit("200\n");
		break;


	/* Settings - General */
	case 'savesetgen':
		if(UserRole() < Role::Admin) BailErr(__('No permission to access this page, sorry.'));
		
		mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'autoeditmode\',\'' . intval(@$_POST['autoeditmode'])  . '\')');
		mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'developermode\',\'' . intval(@$_POST['developermode'])  . '\')');
		
		exit("200\n");
		break;


	/* Load modules list */
	case 'lm':
		if(UserRole() < Role::Admin) Bail(__('No permission to access this page, sorry.'));
		
		include('inc/modules.php');
		$pmg = new PageManager();
		$pmg->findModules();
		exit("200\n".json_encode($pmg->getModules()));
		break;
	
	
	/* Install module */
	case 'im':
		if (UserRole() < Role::Admin) Bail(__('No permission to access this page, sorry.'));
		
		if (!isset($_GET['name'])) exit("400\nForgot module name to install?");
		if (preg_match('#(\.|/)#',$_GET['name'])) exit("400\nInvalid module name");
		
		include('inc/modules.php');
		$pmg = new PageManager();
		if ($pmg->installModule($_GET['name'])) {
			exit("200\nok");
		} else {
			exit("300\nCouldn't find this module for installing");
		}
		break;
	
	
	/* Uninstall module */
	case 'uim':
		if (UserRole() < Role::Admin) Bail(__('No permission to access this page, sorry.'));
		
		if (!isset($_GET['name'])) exit("400\nForgot module name to uninstall?");
		if (preg_match('#(\.|/)#',$_GET['name'])) exit("400\nInvalid module name");
		
		include('inc/modules.php');
		$pmg = new PageManager();
		if ($pmg->uninstallModule($_GET['name'])) {
			exit("200\nok");
		} else {
			exit("300\nCouldn't find this module for uninstalling");
		}
		
		break;
		
	
	// Add page
	case 'ap':
		if (UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		
		$nolink = intval($_POST['nolink']);
		$vis = intval($_POST['vis']);
		$intopage = isset($_POST['intopage']) ? intval($_POST['intopage']) : 0;
		// Todo: For multi-menus check here the database for valid menu names!
		$menu = in_array($_POST['menu'],array('MINOR','MAIN')) ? $_POST['menu'] : 'MAIN';
		$pos = 0;
		$par = 0;
			
		
		if (! $intopage) {
			// Add page to the bottom of the root tree
			$q = "SELECT MAX(position) as pos FROM ".PAGES." WHERE parent_idx=0 AND menu='".$menu."'";
			$res = mysql_query($q) or
				BailSQL(__('Failed freeing a position for new page'),$q);
			$row = mysql_fetch_array($res);

			$pos = $row['pos'] + 1;
			$par = 0;
		} else {
			// Note: This code is currently not in use and might be buggy
			// It allows to create new pages inside existing pages
			
			$q="SELECT * FROM ".PAGES." WHERE idx=".$intopage;
			$res = mysql_query($q) or
				BailSQL(__('Failed getting page info'),$q);
			$row = mysql_fetch_array($res);

			$q = "SELECT idx FROM ".PAGES." WHERE parent_idx=".$row['idx']." LIMIT 1";
			$res2=mysql_query($q) or
				BailSQL(__('Failed getting idx from '),$q);
				
			if (mysql_affected_rows()) {
				$q = "UPDATE ".PAGES." SET position=position+1 WHERE parent_idx=".$row['idx']." AND menu=".$row['menu'];
				mysql_query($q) or
					BailSQL(__('Failed freeing a position for new page'),$q);
			}
			$pos = 0;
			$par = $row['idx'];
		}
		
		if  (isset($_POST['filename'])) {
			$fname = $_POST['filename'];
		} else {
			$fname='';
		}
		
		if (get_magic_quotes_gpc()) {
			$_POST['name'] = stripslashes($_POST['name']);
			$_POST['info'] = stripslashes($_POST['info']);
			$_POST['url'] = stripslashes($_POST['url']);
			$_POST['menu'] = stripslashes($_POST['menu']);
			$fname = stripslashes($fname);
		}
		$_POST['name'] = mysql_real_escape_string($_POST['name']);
		$_POST['info'] = mysql_real_escape_string($_POST['info']);
		$_POST['url'] = mysql_real_escape_string($_POST['url']);
		
		$fname = mysql_real_escape_string($fname);
				
		$q = "INSERT INTO ".PAGES . 
			 " (name, url, info, date, parent_idx, file, visibility, position, nolink,content,menu) VALUES " .
			 " ('".$_POST['name']."','".$_POST['url']."','".$_POST['info']."',".time().",'".$par."','".$fname."','".$vis."','".$pos."','$nolink','','".$menu."')";
		
		mysql_query($q) or
			BailSQL(__('Failed inserting new page'),$q);
		
		echo "200\n".PrintLinks();
		
		break;
	
	case 'movenode':
		if (UserRole() < Role::ProMod) BailErr(__('No permission to access this page, sorry.'));
		
		if  (!preg_match("/^node(\d+)$/",$_GET['movingNode'],$match)) exit("400\nProgramming error: wrong dropped node");
		$movingNodeId = $match[1];
		
		if  (!preg_match("/^node(\d+)$/",$_GET['targetNode'],$match)) exit("400\nProgramming error: wrong target node");
		$targetNodeId = intval($match[1]);
		
		$trees = array('tree_major', 'tree_minor');
		
		if (!in_array($_GET['tree'], $trees)) exit("400\nProgramming error: wrong tree");
		
		$targetmenu = str_replace($trees, array('MAIN', 'MINOR'), $_GET['tree']);
		
		if($movingNodeId == $targetNodeId && $_GET['position'] == 'inside') exit("400\nProgramming error: dropped node == target node");
		
		$q = "SELECT idx,name,position,parent_idx,menu FROM ".PAGES." WHERE idx=$movingNodeId";
		$res = mysql_query($q) or
			BailSQL(__('Failed moving page'),$q);
		
		$movingNode = mysql_fetch_array($res);
			
		$q = "SELECT idx,name,position,parent_idx,menu FROM ".PAGES." WHERE idx=$targetNodeId";
		$res = mysql_query($q) or
			BailSQL(__('Failed moving page'),$q);
			
		$targetNode = mysql_fetch_array($res);
		
		// Prevent moving an element into its direct child
		if ($targetNode['parent_idx'] == $movingNodeId) {
			exit("400\nProgramming error: trying to move page into its subpage");
		}
		
		/* Start the move */
		mysql_query("START TRANSACTION") or 
			BailSQL('Couldn\'t start transaction',"START TRANSACTION");
		
		// Free the space from old place
		$q="UPDATE ".PAGES." SET position=position-1 WHERE position>".$movingNode['position']." AND parent_idx=".$movingNode['parent_idx']." AND menu='".$movingNode['menu']."'";
		if(!($res=mysql_query($q)))
			{ @mysql_query("ROLLBACK"); BailSQL(__('Failed moving page'),$q); }
		
		switch($_GET['position']) {
			case 'before':
				// Make space in new place
				$q="UPDATE ".PAGES." SET position=position+1 WHERE position>=".$targetNode['position']." AND parent_idx=".$targetNode['parent_idx']." AND menu='".$targetNode['menu']."'";
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailSQL(__('Failed moving page'),$q); }
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=".$targetNode['parent_idx'].", position=".$targetNode['position'].", menu='".$targetNode['menu']."' WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailSQL(__('Failed moving page'),$q); }
				
				break;
				
			case 'after':
				// Make space in new place
				$q="UPDATE ".PAGES." SET position=position+1 WHERE position>".$targetNode['position']." AND parent_idx=".$targetNode['parent_idx']." AND menu='".$targetNode['menu']."'";
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailSQL(__('Failed moving page'),$q); }
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=".$targetNode['parent_idx'].", position=".$targetNode['position']."+1, menu='".$targetNode['menu']."' WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailSQL(__('Failed moving page'),$q); }
					
				break;
				
			case 'inside':
				// 'inside' always moves to the bottom of the target parent
				$q='SELECT MAX(position) FROM '.PAGES.' WHERE parent_idx='.$targetNode['idx']." AND menu='".$targetNode['menu']."'";;
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailSQL(__('Failed moving page'),$q); }
				list($newPos)=mysql_fetch_row($res);
				$newPos++;
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=".$targetNode['idx'].", position=".$newPos.", menu='".$targetNode['menu']."' WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailSQL(__('Failed moving page'),$q); }
			
				break;
				
			case 'bottom':
				$q='SELECT MAX(position) FROM '.PAGES.' WHERE parent_idx=0 AND menu=\''.$targetmenu.'\'';
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailSQL(__('Failed moving page'),$q); }
				list($newPos)=mysql_fetch_row($res);
				$newPos++;
				
				$q = "UPDATE ".PAGES." SET parent_idx=0, menu='".$targetmenu."', position=".$newPos." WHERE idx=".$movingNode['idx'];
				// Move the node
				if(!($res = mysql_query($q))) { @mysql_query("ROLLBACK"); BailSQL(__('Failed moving page'),$q); }
				
				break;
				
			default:
				exit("500\nWrong command");
		}
		
		if (!mysql_query("COMMIT"))
			BailSQL("500\nCouldn't commit change","COMMIT");

		echo "200\n".PrintLinks();
		exit();
		
		break;
	

	// Update page
	case 'rp':
		if (UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		
		$id = intval($_POST['page_id']);
		
		if (get_magic_quotes_gpc()) {
			$_POST['name']=stripslashes($_POST['name']);
			$_POST['info']=stripslashes($_POST['info']);
			$_POST['url']=stripslashes($_POST['url']);
			$_POST['filename']=stripslashes($_POST['filename']);
		}
		
		if (strlen(trim($_POST['url']))) {
			$q = "SELECT idx, name FROM ".PAGES." WHERE url='" . mysql_real_escape_string($_POST['url']) . "'";
			$res = mysql_query($q);
			list ($idxWithSameUrl, $pgname) = mysql_fetch_row($res);
			if  (mysql_affected_rows() && $idxWithSameUrl != $id) {
				echo "304\n" . sprintf(__("The page '%s' already uses this URL-Alias, please choose another!"), $pgname);
				exit();
			}
		}
 
		$vis = intval($_POST['vis']);
			
		$q = "UPDATE ".PAGES." SET " . 
			"name='".mysql_real_escape_string($_POST['name'])."', " .
			"info='".mysql_real_escape_string($_POST['info'])."', " .
			"url='".mysql_real_escape_string(trim($_POST['url']))."', " .
			"file='".mysql_real_escape_string($_POST['filename'])."', " .
			"visibility='".$vis."' WHERE idx='$id'";

		mysql_query($q) or
			BailSQL(__('Failed renaming page'), $q);
			
		echo "200\n".PrintLinks();
		break;
	
	
	// Remove Page
	case 'dp':
		if (UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		
		$id = intval($_POST['page_id']);
		
		$q = "DELETE FROM ".PAGES." WHERE idx=$id";
		mysql_query($q) or
			BailErr(__('Failed deleting page'),$q);
			
		DeleteChildPages($id);
		
		echo "200\n".PrintLinks();
		break;
	
	
	default:
		$anego->display('index.tpl');
		break;
}
	

/****** FUNCTIONS ******/

// Prints menu links for menu administration
function PrintLinks() {
	global $cfg;
	
	ob_start();

	echo '<div class="treeDiv"><b>' . __('Main menu') . '</b><br>';
	PrintLinksRec(0, MENU_MAIN, true);
	echo '</div>';

	if ($cfg['minorMenu']) {
		echo '<div class="treeDiv"><b>' . __('Secondary menu') . '</b><br>';
		PrintLinksRec(0, MENU_MINOR, true);
		echo '</div>';
	}
	

	$str=ob_get_contents();
	ob_end_clean();
	return $str.'<div class="clearfloat"></div>';
}

function PrintLinksRec($parent, $menu, $first=0) {
	global $defIcons, $cfg;
	
	$q = "SELECT * FROM ".PAGES." WHERE parent_idx=$parent AND menu='".$menu."' ORDER BY position";
	$res=mysql_query($q) or
		BailSQLn(__('Couldn\'t read pages for menu'), $q);
	
	$id='1';
	if ($first) $id='0';

	if (!mysql_affected_rows() && !$first) return;

	if ($first) {
		echo '<div class="innertreeDiv">';
		echo "<img alt=\"\" title=\"" . __('New page') ."\" class=\"adp\" src=\"".$defIcons['add']."\">";
		echo " <a href=\"javascript:adminMenu.addPage(0,'$menu',$id)\">" . __('New page') ."</a>"; 
	
		if($menu == MENU_MAIN)
			echo '<ul id="tree_major" class="menuTree">';
		else echo '<ul id="tree_minor" class="menuTree">';
	} else echo '<ul>';
	
	
	$numRows=mysql_affected_rows();
	if (!$numRows && $first) {
		echo "</ul></div>";
		return;
	}
	
	$j=0;
	while ($row = mysql_fetch_array($res)) {
		$j++;
		$link = "index.php?p=".$row['idx'];
		
		$name = htmlentities($row['name'],ENT_COMPAT,'UTF-8');
		if (!strlen(trim($name))) $name = '<i>Nameless Page</i>';
		if ($row['visibility'] != 3) $name="<i>".$name."</i>";
		
		echo '<li id="node'.$row['idx'].'">';
		if ($j==$numRows) {
			echo '<img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="listImg last"><span class="listEl">';
		} else {
			echo '<img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="listImg"><span class="listEl">';
		}
		
		echo "<a id=\"adm".$row['idx']."\" href=\"#\" onclick=\"return adminMenu.renamePage(" . 
			$row['idx'] . ",'" . 
			addslashes(htmlentities($row['name'], ENT_COMPAT,'UTF-8')) . "','" . 
			addslashes(htmlentities(@$row['url'], ENT_COMPAT,'UTF-8')) . "','" . 
			addslashes(htmlentities(@$row['info'], ENT_COMPAT,'UTF-8')) . "'," . 
			$row['visibility'] . ",'" . 
			$row['file'] . "')\">$name</a> ";
		
		echo '</span>';
		echo "<a href=\"#\" onclick=\"return adminMenu.delPage(".$row['idx'].")\">";
		echo "<img class=\"adp smallIcon smallimgBin\" alt=\"". __('Delete Page') ."\" title=\"". __('Delete Page') ."\" src=\"" . $cfg['path'] . "styles/default/img/cleardot.gif\"></a>\n";
		PrintLinksRec($row['idx'], $menu);
		echo "</li>\n\n";
		
	}
	
	echo '</li></ul>';
	if ($first) echo '</ul></div>';
	
	return;
}

function DeleteChildPages($id) {
	$q = "SELECT idx FROM ".PAGES." WHERE parent_idx=$id";
	$res=mysql_query($q) or
		BailSQLn(__('Failed getting child pages for deletion'), $q);
	
	while (list($idx)=mysql_fetch_row($res)) {
		DeleteChildPages($idx);
	}
		
	$q = "DELETE FROM ".PAGES." WHERE idx=$id";
	mysql_query($q) or
		BailSQLn(sprintf(__('Failed deleting page %s'), $id), $q);
}