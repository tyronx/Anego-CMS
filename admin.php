<?
//error_reporting(E_ALL);
include("core.php");

$anego->assign('pagetitle', $settings['pagetitle'] . " - Admin");
$anego->assign('showheader', !@$_GET['noheader']);

// Load admin related js language file - core.php loads this already...?
//if(!isset($_GET['noheader'])) {
//	$anego->AddJsModule('ad'.$language);
//}

if(!LOGINOK) {
	$message = '';
	/* Login */
	if (@$_GET['a'] == 'li') {
		/*
			client_response = sha256(pass)
			database_pass = sha256(salt+sha256(passwd))
			sha256(salt + client_response) == db_pass
		*/
		$saltedPw = hash('sha256',$cfg['hash_salt'].$_POST['response']);
		if (ValidAuth($_POST['username'], $saltedPw)) {
			setcookie(
				$cfg['cookieName'], 
				strtolower($_POST['username']) . "," . $saltedPw, ($_POST['staysigned']==1) ? (time()+$cfg['cookieTime']*3600) : 0
			);
			//if(isset($_SERVER['HTTP_REFERER']))
			//	$anego->Reload($_SERVER['HTTP_REFERER']);
			//else 
			$anego->Reload($cfg['domain']);
			
		} else {
			$message = __('Wrong username or password');
		}
	}
	
	$anego->assign('message', $message);
	
	if(isset($_GET['noheader']) && $_GET['noheader']) {
		echo "200\nAdmin - Login\r\n";
		echo $anego->fetchContent('login.tpl');
	} else {
	
		$anego->AddJsModule('lo');
		$anego->AddContent($logon);
		$anego->display('login.tpl');
	}
	
	exit();
}

if(!isset($_GET['a']) || $_GET['a']=='li') {
	$anego->Reload($cfg['domain']);
}

$anego->assign('action',1);

switch($_GET['a']) {

	/****** Page loads or ajax page loads ******/
	
	/* Logout */
	case 'lo':
		setcookie($cfg['cookieName'],'dead.',100);
		$anego->Reload();
		break;
		
	/* Page Administration */
	case 'pgad':
		/* Ajax call */
		if(isset($_GET['noheader'])) {
			if(UserRole() < Role::ProMod) BailErr(__('No permission to access this page, sorry.'));
			
			$json = Array();
			$json['title'] = 'Anego - Admin';
			$json['js'] = Array('ld.am');
			$json['content'] = PrintLinks();
			
			exit("200\n".json_encode($json));
		}
		
		/* Normal call */
		if(UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		AdminBar(-1);		
		
		$anego->AddJsModule("am");
		//$anego->AddJavaScriptFile("lib/dragdrop.js");
		//$anego->AddFooter("\t<script type=\"text/javascript\">\n".$footer."\n\t</script>");
		$anego->AddContent(PrintLinks());
		$anego->display('index.tpl');
		break;
		
		
	/* Admin files */
	case 'filad':
		if(isset($_GET['fgx'])) $fgx = str_replace(array('"',"'"),array('',''),$_GET['fgx']);
		else $fgx = '';
		$max_filesize = min(in_mb(ini_get('post_max_size')),in_mb(ini_get('upload_max_filesize')));
		
		$content = "<div id=\"editpage\">[<a href=\"javascript:AddFile('$fgx')\">"  . __('add file') . "</a> ".
				 "| <a href=\"javascript:AddFolder('$fgx')\">" . __('add folder') . "</a>]</div>";
				 
		$content .= Gallery("",DTYPE_ADMIN,$cfg['galAdminRows'],$cfg['galAdminCols']);
		
		/* Ajax call */
		if(isset($_GET['noheader'])) {
			if(UserRole() < Role::ProMod) BailErr(__('No permission to access this page, sorry.'));
			
			$json = Array();
			$json['title']='Anego - Admin';
			$json['js']='ld.af';
			$json['content']='<script type="text/javascript">anego.maxmb='.$max_filesize.';</script>';
			$json['content'].="\n".$content;
			$json['content'].='<script type="text/javascript">$(document).ready(function() { Core.lightbox(\'a[rel=lightbox]\'); }); </script>';
			
			exit("200\n".json_encode($json));
		}
		
		/* Normal call */
		if(UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		AdminBar(-1);
		
		$anego->AddJsModule("af");
		$anego->AddJsPreload("\t".'anego.maxmb='.$max_filesize.';');
		$anego->AddContent($content);
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
		while($row = mysql_fetch_array($res)) {
			$pages[] = $row;
		}
		
		$anego->assign('settings', $settings);
		$anego->assign('pages', $pages);
		
		if(isset($_GET['noheader'])) {
			$json=Array();
			$json['title']='Anego - Admin';
			$json['js']='ld.as.jui';
			$json['css']='styles/default/jui/jquery-ui.css';
			$json['content'] = $anego->fetchContent('settings.tpl');
			
			exit("200\n".json_encode($json));
		}
		
		AdminBar(-1);
		$anego->AddContent($str);
		$anego->AddJsModule('as');
		// jquery ui requires this css file
		$anego->AddCSSFile('styles/default/jui/jquery-ui.css');
		$anego->AddJsModule('jui');
		$anego->display('settings.tpl');
		break;


	/****** AJAX Callback handlers ******/
	
	case 'savesetg':
		if(UserRole() < Role::Admin) BailErr(__('No permission to access this page, sorry.'));
		
		// Unfortunately can only be done one by one
		mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'firstpage\',\'' . mysql_real_escape_string($_POST['homepage'])  . '\')');
		mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'pagetitle\',\'' . mysql_real_escape_string($_POST['pagetitle']) . '\')');
		mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'keywords\',\'' . mysql_real_escape_string($_POST['keywords']) . '\')');
		mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'description\',\'' . mysql_real_escape_string($_POST['description']) . '\')');
		
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
		
	/* AJAX functions - no smarty can be used here. Direct output needed */
	case 'files':
		if(UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		
		if(!isset($_GET['fgx'])) $fgx='';
			else $fgx = $_GET['fgx'];
		$path = SimplifyPath($fgx);	
		if(preg_match("#\.\.#",$path)) exit(__('path var is tampererd, this looks like a hack attempt. Stopping.'));
		
		echo "<div id=\"editpage\">[<a href=\"javascript:AddFile('$fgx')\">" . __('add file') . "</a> ";
		echo "| <a href=\"javascript:AddFolder('$fgx')\">" . __('add folder') . "</a>]</div>";
		
		if($_GET['r']==0)
			echo Gallery('',DTYPE_ADMIN,$cfg['galAdminRows'],$cfg['galAdminCols']);
		if($_GET['r']==1)
			echo Gallery('',DTYPE_INSERTIMG,$cfg['galInsertRows'],$cfg['galInsertCols']);		
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
				BailErr(__('Failed freeing a position for new page'),$q);
			$row = mysql_fetch_array($res);

			$pos = $row['pos'] + 1;
			$par = 0;
		} else {
			// Note: This code is currently not in use and might be buggy
			// It allows to create new pages inside existing pages
			
			$q="SELECT * FROM ".PAGES." WHERE idx=".$intopage;
			$res = mysql_query($q) or
				BailErr(__('Failed getting page info'),$q);
			$row = mysql_fetch_array($res);

			$q = "SELECT idx FROM ".PAGES." WHERE parent_idx=".$row['idx']." LIMIT 1";
			$res2=mysql_query($q) or
				BailErr(__('Failed getting idx from '),$q);
				
			if(mysql_affected_rows()) {
				$q = "UPDATE ".PAGES." SET position=position+1 WHERE parent_idx=".$row['idx']." AND menu=".$row['menu'];
				mysql_query($q) or
					BailErr(__('Failed freeing a position for new page'),$q);
			}
			$pos = 0;
			$par = $row['idx'];
		}
		
		if (isset($_POST['filename'])) {
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
			BailErr(__('Failed inserting new page'),$q);
		
		echo "200\n".PrintLinks();
		
		break;
	
	case 'movenode':
		if(UserRole() < Role::ProMod) BailErr(__('No permission to access this page, sorry.'));
		
		if(!preg_match("/^node(\d+)$/",$_GET['movingNode'],$match)) exit("400\nProgramming error: wrong dropped node");
		$movingNodeId = $match[1];
		
		if(!preg_match("/^node(\d+)$/",$_GET['targetNode'],$match)) exit("400\nProgramming error: wrong target node");
		$targetNodeId = intval($match[1]);
		
		if($movingNodeId == $targetNodeId && $_GET['position'] == 'inside') exit("400\nProgramming error: dropped node == target node");
		
		$q = "SELECT idx,name,position,parent_idx,menu FROM ".PAGES." WHERE idx=$movingNodeId";
		$res=mysql_query($q) or
			BailErr(__('Failed moving page'),$q);
		$movingNode = mysql_fetch_array($res);
			
		$q = "SELECT idx,name,position,parent_idx,menu FROM ".PAGES." WHERE idx=$targetNodeId";
		$res=mysql_query($q) or
			BailErr(__('Failed moving page'),$q);
		$targetNode = mysql_fetch_array($res);
		
		// Prevent moving an element into its direct child
		if($targetNode['parent_idx'] == $movingNodeId) {
			exit("400\nProgramming error: trying to move page into its subpage");
		}
		
		/* Start the move */
		mysql_query("START TRANSACTION") or 
			BailErr('Couldn\'t start transaction',"START TRANSACTION");
		
		// Free the space from old place
		$q="UPDATE ".PAGES." SET position=position-1 WHERE position>".$movingNode['position']." AND parent_idx=".$movingNode['parent_idx']." AND menu='".$movingNode['menu']."'";
		if(!($res=mysql_query($q)))
			{ @mysql_query("ROLLBACK"); BailErr(__('Failed moving page'),$q); }
		
		switch($_GET['position']) {
			case 'before':
				// Make space in new place
				$q="UPDATE ".PAGES." SET position=position+1 WHERE position>=".$targetNode['position']." AND parent_idx=".$targetNode['parent_idx']." AND menu='".$targetNode['menu']."'";
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailErr(__('Failed moving page'),$q); }
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=".$targetNode['parent_idx'].", position=".$targetNode['position'].", menu='".$targetNode['menu']."' WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailErr(__('Failed moving page'),$q); }
				
				break;
				
			case 'after':
				// Make space in new place
				$q="UPDATE ".PAGES." SET position=position+1 WHERE position>".$targetNode['position']." AND parent_idx=".$targetNode['parent_idx']." AND menu='".$targetNode['menu']."'";
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailErr(__('Failed moving page'),$q); }
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=".$targetNode['parent_idx'].", position=".$targetNode['position']."+1, menu='".$targetNode['menu']."' WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailErr(__('Failed moving page'),$q); }
					
				break;
				
			case 'inside':
				// 'inside' always moves to the bottom of the target parent
				$q='SELECT MAX(position) FROM '.PAGES.' WHERE parent_idx='.$targetNode['idx']." AND menu='".$targetNode['menu']."'";;
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailErr(__('Failed moving page'),$q); }
				list($newPos)=mysql_fetch_row($res);
				$newPos++;
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=".$targetNode['idx'].", position=".$newPos.", menu='".$targetNode['menu']."' WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailErr(__('Failed moving page'),$q); }
			
				break;
				
			case 'bottom':
				$q='SELECT MAX(position) FROM '.PAGES.' WHERE parent_idx=0 AND menu=\''.$movingNode['menu'].'\'';
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailErr(__('Failed moving page'),$q); }
				list($newPos)=mysql_fetch_row($res);
				$newPos++;
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=0, position=".$newPos." WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailErr(__('Failed moving page'),$q); }
				
				break;
				
			default:
				exit("500\nWrong command");
		}
		
		if(!mysql_query("COMMIT"))
			BailErr("500\nCouldn't commit change","COMMIT");

		echo "200\n".PrintLinks();
		exit();
		
		break;
	

	// Update page
	case 'rp':
		if (UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		
		$id = intval($_POST['page_id']);
		
		//echo 'name is '.$_POST['name'];
		
		if(get_magic_quotes_gpc()) {
			$_POST['name']=stripslashes($_POST['name']);
			$_POST['info']=stripslashes($_POST['info']);
			$_POST['url']=stripslashes($_POST['url']);
			$_POST['filename']=stripslashes($_POST['filename']);
		}
		
		$q = "SELECT idx, name FROM ".PAGES." WHERE url='" . mysql_real_escape_string($_POST['url']) . "'";
		$res = mysql_query($q);
		list($idxWithSameUrl, $pgname) = mysql_fetch_row($res);
		if (mysql_affected_rows() && $idxWithSameUrl != $id) {
			echo "304\n" . __("The page '$pgname' already uses this URL-Alias, please choose another!");
			exit();
		}
 
		$vis = intval($_POST['vis']);
		//$nolink = intval($_POST['nolink']);
			
		$q = "UPDATE ".PAGES." SET " . 
			"name='".mysql_real_escape_string($_POST['name'])."', " .
			"info='".mysql_real_escape_string($_POST['info'])."', " .
			"url='".mysql_real_escape_string($_POST['url'])."', " .
			"file='".mysql_real_escape_string($_POST['filename'])."', " .
			"visibility='".$vis."' WHERE idx='$id'";

		mysql_query($q) or
			BailSQL(__('Failed renaming page'), $q);
			
		//$res=mysql_query("SELECT name FROM ".PAGES." WHERE idx='$id'");
		//list($name)=mysql_fetch_array($res);
		
		//echo "<bR>name in db is $name";
			
		echo "200\n".PrintLinks();
		break;
	
	// Remove Page
	case 'dp':
		if(UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		
		$id=intval($_POST['page_id']);
		
		$q = "DELETE FROM ".PAGES." WHERE idx=$id";
		mysql_query($q) or
			BailErr(__('Failed deleting page'),$q);
			
		DeleteChildPages($id);
		
		echo "200\n".PrintLinks();
		break;
	
	// Add file
	case 'af':
		if(UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		
		echo "<html><body><span id='result' style='display:none;'>";
		
		switch($_FILES['fiupl']['error']) {
			case 0: break;
			case 1: 
			case 2: echo("500\n" . __('Can\'t upload File. Size exceeds server limits!')); break;
			case 7: echo("500\n" . __('Cannot write file to temporary files folder. No free space left?')); break;
			default: echo("500\n" . sprintf(__('A unexpected error occurend while uploading. Error number %s'), $_FILES['fiupl']['error'])); break;
			break;
		}
		
		if ($_FILES['fiupl']['error']==0) {
			if (!InvalidFormat($_FILES['fiupl']['name'])) {
				$path = SimplifyPath('files/'.$_POST['path']);	
				if (!preg_match("#^files.*#",$path)) Bail(__('path var is tampererd, this looks like a hack attempt. Stopping.'),true);
				
				if (move_uploaded_file($_FILES['fiupl']['tmp_name'],$path.'/'.$_FILES['fiupl']['name'])) {
					chmod ($path.'/'.$_FILES['fiupl']['name'],0664);
					echo "200\n" . $path . '/' . $_FILES['fiupl']['name'] . "</span><span>" . __('Upload successful!');
				} else {
					echo ("500\n". __('Cannot write file to folder %s. Forgot to set writting permissions?')); 
				}
			} else {
				echo "300\n" . __('Format not allowed! Any kind of php,html,js files are refused. Sorry');
			}
		}
		echo "</span></body></html>";
		break;
	
	// Create folder
	case 'cfol':
		if(UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		
		$folder = $_POST['nfolder'];
		if(!strlen($folder) || preg_match("/[^A-Za-z0-9_\-]/",$folder)) $folder = 'New folder';
		
		$path = SimplifyPath('files/'.$_POST['path']);	
		if(!preg_match("#^files.*#",$path)) Bail(__('path var is tampererd, this looks like a hack attempt. Stopping.'),true);
		
		if(!file_exists($path.'/'.$folder))
			mkdir($path.'/'.$folder);
			
		if(isset($_GET['fgx'])) $fgx = $_GET['fgx'];
		else $fgx = '';			
			
		echo "<div id=\"editpage\">[<a href=\"javascript:AddFile('$fgx')\">" . __('add file') . "</a> |";
		echo "<a href=\"javascript:AddFolder('$fgx')\">" . __('add folder') . "</a>]</div>";
		if($_GET['r']==0)
			echo Gallery('',DTYPE_ADMIN,$cfg['galAdminRows'],$cfg['galAdminCols']);
		if($_GET['r']==1)
			echo Gallery('',DTYPE_INSERTIMG,$cfg['galInsertRows'],$cfg['galInsertCols']);
		break;
		
	// Rename folder/file
	case 'renf':
		if(UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		
		$path = SimplifyPath($_POST['path']);
		if(!preg_match("#^files.*#",$path)) Bail(__('path var is tampererd, this looks like a hack attempt. Stopping.'),true);
		
		if(preg_match("/[^A-Za-z0-9_\-\.]/",$_POST['renfile'])) Bail(__('File/Folder Names may only contain letters, numbers, dot (.) dash (-) and underscore (_)'));
		
		$newf = dirname($_POST['path']).'/'.$_POST['renfile'];
		
		if(!file_exists($_POST['path'])) Bail(__('The file you want to rename does not exist (anymore)'));
		
		if(InvalidFormat($newf))
			exit("300\n" . __('Format not allowed! Any kind of php,html,js files are refused. Sorry'));
		
		rename($_POST['path'],$newf);
		
		echo "200\n";
		
		//$str=trim(str_replace('files/','',$_POST['path']));
		//sif(strlen($str)) $str=dirname($str);
		if(isset($_GET['fgx'])) $fgx = $_GET['fgx'];
		else $fgx = '';

		echo "<div id=\"editpage\">[<a href=\"javascript:AddFile('$fgx')\">" . __('add file') . "</a> |";
		echo "<a href=\"javascript:AddFolder('$fgx')\">" . __('add folder') . "</a>]</div>";
		
		if($_GET['r']==0)
			echo Gallery('',DTYPE_ADMIN,$cfg['galAdminRows'],$cfg['galAdminCols']);
		if($_GET['r']==1)
			echo Gallery('',DTYPE_INSERTIMG,$cfg['galInsertRows'],$cfg['galInsertCols']);
		
		break;
	
	
	// Delete folder
	case 'delf':
		if(UserRole() < Role::ProMod) Bail(__('No permission to access this page, sorry.'));
		
		$file = SimplifyPath($_POST['file']);	
		if(!preg_match("#^files.*#",$file)) Bail(__('path var is tampererd, this looks like a hack attempt. Stopping.'),true);
		
		if(!is_dir($file))
			unlink($file);
		else deltree($file);
		
		if(isset($_GET['fgx'])) $fgx = $_GET['fgx'];
		else $fgx = '';


		echo "<div id=\"editpage\">[<a href=\"javascript:AddFile('$fgx')\">" . __('add file') . "</a> |";
		echo "<a href=\"javascript:AddFolder('$fgx')\">" . __('add folder') . "</a>]</div>";

		if($_GET['r']==0)
			echo Gallery('',DTYPE_ADMIN,$cfg['galAdminRows'],$cfg['galAdminCols']);
		if($_GET['r']==1)
			echo Gallery('',DTYPE_INSERTIMG,$cfg['galInsertRows'],$cfg['galInsertCols']);
		
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

	if($cfg['minorMenu']) {
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
		BailSQLn(__('Couldn\'t read pages for menu'),$q);
	
	$id='1';
	if($first) $id='0';

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
	if(!$numRows && $first) {
		echo "</ul></div>";
		return;
	}
	
	$j=0;
	while($row = mysql_fetch_array($res)) {
		$j++;
		$link = "index.php?p=".$row['idx'];
		
		$name = htmlentities($row['name'],ENT_COMPAT,'UTF-8');
		if(!strlen(trim($name))) $name = '<i>Nameless Page</i>';
		if($row['visibility']!=3) $name="<i>".$name."</i>";
		
		echo '<li id="node'.$row['idx'].'">';
		if($j==$numRows) {
			echo '<img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="listImg last"><span class="listEl">';
		} else {
			echo '<img src="' . $cfg['path'] . 'styles/default/img/cleardot.gif" class="listImg"><span class="listEl">';
		}
		
		echo "<a id=\"adm".$row['idx']."\" href=\"javascript:adminMenu.renamePage(" . 
			$row['idx'] . ",'" . 
			addslashes(htmlentities($row['name'], ENT_COMPAT,'UTF-8')) . "','" . 
			addslashes(htmlentities(@$row['url'], ENT_COMPAT,'UTF-8')) . "','" . 
			addslashes(htmlentities(@$row['info'], ENT_COMPAT,'UTF-8')) . "'," . 
			$row['visibility'] . ",'" . 
			$row['file'] . "')\">$name</a> ";
		
		echo '</span>';
		echo "<a href=\"javascript:adminMenu.delPage(".$row['idx'].")\">";
		echo "<img class=\"adp smallIcon smallimgBin\" alt=\"". __('Delete Page') ."\" title=\"". __('Delete Page') ."\" src=\"" . $cfg['path'] . "styles/default/img/cleardot.gif\"></a>\n";
		PrintLinksRec($row['idx'], $menu);
		echo "</li>\n\n";
		
	}
	
	echo '</li></ul>';
	if($first) echo '</ul></div>';
	
	return;
}

function DeleteChildPages($id) {
	$q = "SELECT idx FROM ".PAGES." WHERE parent_idx=$id";
	$res=mysql_query($q) or
		BailSQLn(__('Failed getting child pages for deletion'), $q);
	while(list($idx)=mysql_fetch_row($res))
		DeleteChildPages($idx);
		
	$q="DELETE FROM ".PAGES." WHERE idx=$id";
	mysql_query($q) or
		BailSQLn(sprintf(__('Failed deleting page %s'), $id), $q);
}

function deltree($path) {
	if (is_dir($path)) {
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != '.' && $file != '..') {
					if(is_dir($path."/".$file)) deltree($path."/".$file);
					else unlink($path."/".$file);
				}
			}
			closedir($handle);
			rmdir($path);
		}
	}
	return;
}


function in_mb($val) {
	$ret = intval(trim($val));
	$last = strtolower($val[strlen($val)-1]);

	switch($last) {
		// The 'G' modifier is available since PHP 5.1.0
		case 'g':
			$ret *= 1024;
		case 'k':
			$ret = $val/1024;
	}

	return $ret;
}