<?
include("core.php");

$anego->assign('lng_pagetitle',$lng_pagetitle." - Admin");

// Load admin related js language file - core.php loads this already...?
//if(!isset($_GET['noheader'])) {
//	$anego->AddJsModule('ad'.$language);
//}


if(!LOGINOK && (!isset($_GET['a']) || $_GET['a']!='li')) {
	if(isset($_GET['noheader']) && $_GET['noheader']) $lib='Core.loadJavascript(\'ld.lo\');';
	else $lib='';
	$logon = <<<EOT
		<div align="center"><div class="loginTitle">$lng_adminarea</div>
		<div class="loginBox">
		<form id="loginForm" action="#" method="post" accept-charset="UTF-8" onsubmit="return false">
			$lng_login_name<br>
			<input type="text" name="username"><br><br>
			$lng_login_pass<br>
			<input type="password" name="password"><br>
			<input type="checkbox" name="staysigned" value="1" checked="checked"> $lng_staysigned<br><br>
			<div align="right"><input type="button" onclick="login()" name="submit" value="$lng_login"></div>
		</form>
		</div></div>
		<form id="submitForm" action="admin.php?a=li" method="post" accept-charset="UTF-8">
		<input type="hidden" name="username">
		<input type="hidden" name="response">
		<input type="hidden" name="staysigned" value="0">
		</form>
		<script type="text/javascript">
			$lib
			function login() {
				var loginForm = document.getElementById("loginForm");
				if (loginForm.username.value == "") {
					alert("Please enter your user name.");
					return false;
				}
				if (loginForm.password.value == "") {
					alert("Please enter your password.");
					return false;
				}
				var submitForm = document.getElementById("submitForm");
				submitForm.username.value = loginForm.username.value;
				if(loginForm.staysigned.checked)
					submitForm.staysigned.value = '1';
				else submitForm.staysigned.value = '0';
				submitForm.response.value = hex_sha256(loginForm.password.value);
				submitForm.submit();
				return false;
			}
			document.getElementById("loginForm").password.onkeypress = function(ev) {
				if(!ev) ev = window.event;
				if(ev.keyCode==13) login();
			}
		</script>		
EOT;

	if(isset($_GET['noheader']) && $_GET['noheader']) {
		echo "200\nAdmin - Login\r\n";
		exit($logon);
	}
		//exit("300\nPermission denied, please log in first");

	// Load Javascript files for login
	$anego->AddJsModule('lo');
	$anego->AddContent($logon);
	$anego->display('index.tpl');
	exit();
}

if(!isset($_GET['a'])) {
	if( UserRole() < Role::ProMod ) Bail($lng_missing_rights);
	
	$p = CurrentPage();
			
	$anego->AddJsPreload("\tanego.editmode=true;");
	$anego->AddJsModule('ap');

	define('ADMIN_MODE',1);
	PrintPage(CurrentPage());
	exit();
}

$anego->assign('action',1);

switch($_GET['a']) {

	/****** Page loads or ajax page loads ******/
	
	/* Login */
	case 'li':
		/*
			client_response = sha256(pass)
			database_pass = sha256(salt+sha256(passwd))
			sha256(salt + client_response) == db_pass
		*/
		$saltedPw = hash('sha256',$cfg['hash_salt'].$_POST['response']);
		if(ValidAuth($_POST['username'],$saltedPw)) {
			setcookie($cfg['cookieName'],strtolower($_POST['username']).",".$saltedPw,($_POST['staysigned']==1)?(time()+$cfg['cookieTime']*3600):0);
			//if(isset($_SERVER['HTTP_REFERER']))
			//	$anego->Reload($_SERVER['HTTP_REFERER']);
			//else 
			$anego->Reload($cfg['domain']);
		} else {
			$anego->AddContent($lng_loginfail);
			$anego->display('index.tpl');
		}
		break;
	
	/* Logout */
	case 'lo':
		setcookie($cfg['cookieName'],'dead.',100);
		$anego->Reload();
		break;
		
	/* Page Administration */
	case 'pgad':
		$footer = "$(function() { $('.menuTree').MyTree(); } );";
	
		/* Ajax call */
		if(isset($_GET['noheader'])) {
			if(UserRole() < Role::ProMod) BailAjax($lng_missing_rights);
			
			$json = Array();
			$json['title'] = 'Anego - Admin';
			$json['js'] = Array('ld.am');
			$json['content']="\t<script type=\"text/javascript\">$(document).ready(function() {\n".$footer."\n});\n\t</script>\n".PrintLinks();
			
			exit("200\n".json_encode($json));
		}
		
		/* Normal call */
		if(UserRole() < Role::ProMod) Bail($lng_missing_rights);
		AdminBar(-1);		
		
		$anego->AddJsModule("am");
		//$anego->AddJavaScriptFile("lib/dragdrop.js");
		$anego->AddFooter("\t<script type=\"text/javascript\">\n".$footer."\n\t</script>");
		$anego->AddContent(PrintLinks());
		$anego->display('index.tpl');
		break;
		
		
	/* Admin files */
	case 'filad':
		if(isset($_GET['fgx'])) $fgx = str_replace(array('"',"'"),array('',''),$_GET['fgx']);
		else $fgx = '';
		$max_filesize=min(in_mb(ini_get('post_max_size')),in_mb(ini_get('upload_max_filesize')));
		$content="<div id=\"editpage\">[<a href=\"javascript:AddFile('$fgx')\">$lng_addfile</a> | <a href=\"javascript:AddFolder('$fgx')\">$lng_addfolder</a>]</div>";
		$content.=Gallery("",DTYPE_ADMIN,$cfg['galAdminRows'],$cfg['galAdminCols']);
		
		/* Ajax call */
		if(isset($_GET['noheader'])) {
			if(UserRole() < Role::ProMod) BailAjax($lng_missing_rights);
			
			$json = Array();
			$json['title']='Anego - Admin';
			$json['js']='ld.af';
			$json['content']='<script type="text/javascript">anego.maxmb='.$max_filesize.';</script>';
			$json['content'].="\n".$content;
			
			exit("200\n".json_encode($json));
		}
		
		/* Normal call */
		if(UserRole() < Role::ProMod) Bail($lng_missing_rights);
		AdminBar(-1);
		
		$anego->AddJsModule("af");
		$anego->AddJsPreload("\t".'anego.maxmb='.$max_filesize.';');
		$anego->AddContent($content);
		$anego->display('index.tpl');
		break;
	
	/* Admin settings */
	case 'setg':
		if(isset($_GET['noheader']))
			if(UserRole() < Role::Admin) BailAjax($lng_missing_rights);
		else 
			if(UserRole() < Role::Admin) Bail($lng_missing_rights);
			
		if(isset($_POST['Save'])) {
			mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'firstpage\',\''.$_POST['homepage'].'\')');
			mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'pagetitle\',\''.$_POST['pagetitle'].'\')');
			mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'keywords\',\''.$_POST['keywords'].'\')');
			mysql_query('REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'description\',\''.$_POST['description'].'\')');
		}
		$s=array();
		$res = mysql_query("SELECT * FROM ".SETTINGS);
		while($row=mysql_fetch_array($res))
			$s[$row['name']] = $row['value'];
			
		ob_start();
?>
		<div id="adminpage" class="adminstyles">
			<h2><?=$lng_settings?></h2>
			<div id="tabs">
				<ul>
					<li><a href="#tabs-1"><?=$lng_settings_general?></a></li>
					<li><a href="#tabs-2"><?=$lng_settings_modules?></a></li>
				</ul>
				<div id="tabs-1">
					<form action="admin.php?a=setg&g=1" method="POST" accept-charset="UTF-8">
					<?=$lng_settings_homepage?><br>
					<select name="homepage">					
			<?
			$res=mysql_query("SELECT idx,name FROM ".PAGES." WHERE nolink=0 AND file='' ORDER BY name");
			while($row=mysql_fetch_array($res)) {
				echo "\t\t\t";
				if($s['firstpage']==$row['idx']) echo '<option value="'.$row['idx'].'" selected>'.$row['name'].'</option>';
				else echo '<option value="'.$row['idx'].'">'.$row['name'].'</option>';
			}
			
			
			?>
					</select><br><br><?=$lng_settings_title?><br>
					<input type="text" name="pagetitle" value="<?=@$s['pagetitle']?>">
					<br><br><?=$lng_settings_keywords?><br>
					<textarea type="text" cols="60" style="width:100%" rows="3" name="keywords"><?=@$s['keywords']?></textarea>
					<br><br><?=$lng_settings_description?><br>
					<textarea name="description" rows="3" cols="60" style="width:100%"><?=@$s['description']?></textarea>
					<br><br><input type="submit" name="Save" value="<?=$lng_settings_save?>"></form>
				</div>
				<div id="tabs-2">
					<table id="modulesTable" class="grid" style="width:100%">
					<!--<tr><td style="width:60px;"></td><td>Module Name</td><td>Type</td><td>Author</td><td>Installed</td></tr>-->
					</table>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			$(document).ready(function() {
				settings = new settingsFunctions();
				settings.loadModules();
			});
		</script>
<?
		$str=ob_get_contents();
		ob_end_clean();
		
		if(isset($_GET['noheader'])) {
			$json=Array();
			$json['title']='Anego - Admin';
			$json['js']='ld.as.jui';
			$json['css']='styles/default/jui/jquery-ui.css';
			$json['content']=$str;
			
			exit("200\n".json_encode($json));
		}
		
		AdminBar(-1);		
		$anego->AddContent($str);
		$anego->AddJsModule('as');
		// jquery ui requires this css file
		$anego->AddCSSFile('styles/default/jui/jquery-ui.css');		
		$anego->AddJsModule('jui');
		$anego->display('index.tpl');
		break;
		



	/****** AJAX Callback handlers ******/
		
	/* Load modules list */
	case 'lm':
		if(UserRole() < Role::Admin) Bail($lng_missing_rights);
		
		include('inc/modules.php');
		$pmg = new PageManager();
		$pmg->findModules();
		exit("200\n".json_encode($pmg->modules()));
		break;
		
	/* Install module */
	case 'im':
		if(UserRole() < Role::Admin) Bail($lng_missing_rights);
		
		if(!isset($_GET['name'])) exit("400\nForgot module name to install?");
		if(preg_match('#(\.|/)#',$_GET['name'])) exit("400\nInvalid module name");
		
		include('inc/modules.php');
		$pmg = new PageManager();
		if($pmg->installModule($_GET['name']))
			exit("200\nok");
		else exit("300\nCouldn't find this module for installing");
		break;
		
	/* Uninstall module */
	case 'uim':
		if(UserRole() < Role::Admin) Bail($lng_missing_rights);
		
		if(!isset($_GET['name'])) exit("400\nForgot module name to uninstall?");
		if(preg_match('#(\.|/)#',$_GET['name'])) exit("400\nInvalid module name");
		
		include('inc/modules.php');
		$pmg = new PageManager();
		if($pmg->uninstallModule($_GET['name']))
			exit("200\nok");
		else exit("300\nCouldn't find this module for uninstalling");
		break;
		
	/* AJAX functions - no smarty can be used here. Direct output needed */
	case 'files':
		if(UserRole() < Role::ProMod) Bail($lng_missing_rights);
		
		if(!isset($_GET['fgx'])) $fgx='';
			else $fgx = $_GET['fgx'];
		$path = SimplifyPath($fgx);	
		if(preg_match("#\.\.#",$path)) exit($lng_hack);
		
		echo "<div id=\"editpage\">[<a href=\"javascript:AddFile('$fgx')\">$lng_addfile</a> | <a href=\"javascript:AddFolder('$fgx')\">$lng_addfolder</a>]</div>";
		if($_GET['r']==0)
			echo Gallery('',DTYPE_ADMIN,$cfg['galAdminRows'],$cfg['galAdminCols']);
		if($_GET['r']==1)
			echo Gallery('',DTYPE_INSERTIMG,$cfg['galInsertRows'],$cfg['galInsertCols']);		
		break;
	
	// Add page
	case 'ap':
		if(UserRole() < Role::ProMod) Bail($lng_missing_rights);
		
		$id=intval($_POST['id']);
		$subm=intval($_POST['subm']);
		$nolink=intval($_POST['nolink']);
		$vis =intval($_POST['vis']);
		$pos = 0;
		$par = 0;
		
		$q="SELECT * FROM ".PAGES." WHERE idx=$id";
		$res = mysql_query($q) or
			BailAjax($lng_pageinfo,$q);
		
		//if(!mysql_num_rows($res)) exit("400\nNo/Wrong page id?");
		
		$row = mysql_fetch_array($res);
		
		if(!isset($_POST['intopage'])) {
			if($id!=0) {
				// Free up one position by moving down all the other
				$q = "UPDATE ".PAGES." SET position=position+1 WHERE parent_idx=".$row['parent_idx']." AND position>".$row['position'];
				mysql_query($q) or
					BailAjax($lng_failedfreeing,$q);
						
				$pos= $row['position']+1;
				$par = $row['parent_idx'];
			}
		} else {
			$q = "SELECT idx FROM ".PAGES." WHERE parent_idx=".$row['idx']." LIMIT 1";
			$res2=mysql_query($q) or
				BailAjax($lng_failedgetting,$q);
				
			if(mysql_affected_rows()) {
				$q = "UPDATE ".PAGES." SET position=position+1 WHERE parent_idx=".$row['idx'];
				mysql_query($q) or
					BailAjax($lng_failedfreeing,$q);
			}
			$pos = 0;
			$par = $row['idx'];
		}
		
		if(isset($_POST['filename']))
			$fname = $_POST['filename'];
		else $fname='';
		
		if(get_magic_quotes_gpc()) {
			$_POST['name']=stripslashes($_POST['name']);
			$_POST['info']=stripslashes($_POST['info']);
			$_POST['menu']=stripslashes($_POST['menu']);
			$fname=stripslashes($fname);
		}
		$_POST['name']=mysql_real_escape_string($_POST['name']);
		$_POST['info']=mysql_real_escape_string($_POST['info']);
		$fname=mysql_real_escape_string($fname);
				
		$q = "INSERT INTO ".PAGES." (name, info, date, parent_idx, file, visibility, position, subpoint,nolink,content,menu) VALUES ('".$_POST['name']."','".$_POST['info']."',".time().",'".$par."','".$fname."','".$vis."','".$pos."','$subm','$nolink','','".mysql_real_escape_string($_POST['menu'])."')";
		mysql_query($q) or
			BailAjax($lng_failedinserting,$q);
		
		echo "200\n".PrintLinks();
		
		break;
	
	case 'movenode':
		if(UserRole() < Role::ProMod) BailAjax($lng_missing_rights);
		
		if(!preg_match("/^node(\d+)$/",$_GET['movingNode'],$match)) exit("400\nProgramming error: wrong dropped node");
		$movingNodeId = $match[1];
		
		if(!preg_match("/^node(\d+)$/",$_GET['targetNode'],$match)) exit("400\nProgramming error: wrong target node");
		$targetNodeId = intval($match[1]);
		
		$q = "SELECT idx,name,position,parent_idx,menu FROM ".PAGES." WHERE idx=$movingNodeId";
		$res=mysql_query($q) or
			BailAjax($lng_failedmoving,$q);
		$movingNode = mysql_fetch_array($res);
			
		$q = "SELECT idx,name,position,parent_idx,menu FROM ".PAGES." WHERE idx=$targetNodeId";
		$res=mysql_query($q) or
			BailAjax($lng_failedmoving,$q);
		$targetNode = mysql_fetch_array($res);
			
			
		/* Start the move */
		mysql_query("START TRANSACTION") or 
			BailAjax('Couldn\'t start transaction',"START TRANSACTION");
		
		// Free the space from old place
		$q="UPDATE ".PAGES." SET position=position-1 WHERE position>".$movingNode['position']." AND parent_idx=".$movingNode['parent_idx']." AND menu='".$movingNode['menu']."'";
		if(!($res=mysql_query($q)))
			{ @mysql_query("ROLLBACK"); BailAjax($lng_failedmoving,$q); }
		
		switch($_GET['position']) {
			case 'before':
				// Make space in new place
				$q="UPDATE ".PAGES." SET position=position+1 WHERE position>=".$targetNode['position']." AND parent_idx=".$targetNode['parent_idx']." AND menu='".$targetNode['menu']."'";
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailAjax($lng_failedmoving,$q); }
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=".$targetNode['parent_idx'].", position=".$targetNode['position'].", menu='".$targetNode['menu']."' WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailAjax($lng_failedmoving,$q); }
				
				break;
				
			case 'after':
				// Make space in new place
				$q="UPDATE ".PAGES." SET position=position+1 WHERE position>".$targetNode['position']." AND parent_idx=".$targetNode['parent_idx']." AND menu='".$targetNode['menu']."'";
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailAjax($lng_failedmoving,$q); }
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=".$targetNode['parent_idx'].", position=".$targetNode['position']."+1, menu='".$targetNode['menu']."' WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailAjax($lng_failedmoving,$q); }
					
				break;
				
			case 'inside':
				// 'inside' always moves to the bottom of the target parent
				$q='SELECT MAX(position) FROM '.PAGES.' WHERE parent_idx='.$targetNode['idx']." AND menu='".$targetNode['menu']."'";;
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailAjax($lng_failedmoving,$q); }
				list($newPos)=mysql_fetch_row($res);
				$newPos++;
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=".$targetNode['idx'].", position=".$newPos.", menu='".$targetNode['menu']."' WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailAjax($lng_failedmoving,$q); }
			
				break;
				
			case 'bottom':
				$q='SELECT MAX(position) FROM '.PAGES.' WHERE parent_idx=0 AND menu=\''.$movingNode['menu'].'\'';
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailAjax($lng_failedmoving,$q); }
				list($newPos)=mysql_fetch_row($res);
				$newPos++;
				
				// Move the node
				$q="UPDATE ".PAGES." SET parent_idx=0, position=".$newPos." WHERE idx=".$movingNode['idx'];
				if(!($res=mysql_query($q)))
					{ @mysql_query("ROLLBACK"); BailAjax($lng_failedmoving,$q); }
				
				break;
				
			default:
				exit("500\nWrong command");
		}
		
		if(!mysql_query("COMMIT"))
			BailAjax("500\nCouldn't commit change","COMMIT");

		echo "200\n";
		exit();
		
		break;
	

	// Update page
	case 'rp':
		if(UserRole() < Role::ProMod) Bail($lng_missing_rights);
		
		$id=intval($_POST['page_id']);
		
		//echo 'name is '.$_POST['name'];
		
		if(get_magic_quotes_gpc()) {
			$_POST['name']=stripslashes($_POST['name']);
			$_POST['info']=stripslashes($_POST['info']);
			$_POST['filename']=stripslashes($_POST['filename']);
		}

		$vis = intval($_POST['vis']);
		$subm = intval($_POST['subm']);
		//$nolink = intval($_POST['nolink']);
			
		$q = "UPDATE ".PAGES." SET name='".mysql_real_escape_string($_POST['name'])."', info='".mysql_real_escape_string($_POST['info'])."',file='".mysql_real_escape_string($_POST['filename'])."', visibility='".$vis."', subpoint='".$subm."' WHERE idx='$id'";
		mysql_query($q) or
			BailAjax($lng_failedrenaming,$q);
			
		//$res=mysql_query("SELECT name FROM ".PAGES." WHERE idx='$id'");
		//list($name)=mysql_fetch_array($res);
		
		//echo "<bR>name in db is $name";
			
		echo "200\n".PrintLinks();
		break;
	
	// Remove Page
	case 'dp':
		if(UserRole() < Role::ProMod) Bail($lng_missing_rights);
		
		$id=intval($_POST['page_id']);
		
		$q = "DELETE FROM ".PAGES." WHERE idx=$id";
		mysql_query($q) or
			BailAjax($lng_faileddeleting,$q);
			
		DeleteChildPages($id);
		
		echo "200\n".PrintLinks();
		break;
	
	// Add file
	case 'af':
		if(UserRole() < Role::ProMod) Bail($lng_missing_rights);
		
		echo "<html><body><span id='result' style='display:none;'>";
		
		switch($_FILES['fiupl']['error']) {
			case 0: break;
			case 1: 
			case 2: echo("500\n$lng_err_file_tobig"); break;
			case 7: echo("500\n$lng_err_file_cantwrite"); break;
			default: echo("500\n".sprintf($lng_err_file_fail,$_FILES['fiupl']['error'])); break;
			break;
		}
		
		if($_FILES['fiupl']['error']==0) {
			if(!InvalidFormat($_FILES['fiupl']['name'])) {
				$path = SimplifyPath('files/'.$_POST['path']);	
				if(!preg_match("#^files.*#",$path)) Bail($lng_hack,true);
				
				if(move_uploaded_file($_FILES['fiupl']['tmp_name'],$path.'/'.$_FILES['fiupl']['name'])) {
					chmod ($path.'/'.$_FILES['fiupl']['name'],0664);
					echo "200\n".$path.'/'.$_FILES['fiupl']['name']."</span><span>$lng_upload";
				} else echo ("500\n".$lng_err_file_cantwrite2); 
				
				
			} else {
				echo "300\n$lng_format";
			}
		}
		echo "</span></body></html>";
		break;
	
	// Create folder
	case 'cfol':
		if(UserRole() < Role::ProMod) Bail($lng_missing_rights);
		
		$folder = $_POST['nfolder'];
		if(!strlen($folder) || preg_match("/[^A-Za-z0-9_\-]/",$folder)) $folder = 'New folder';
		
		$path = SimplifyPath('files/'.$_POST['path']);	
		if(!preg_match("#^files.*#",$path)) Bail($lng_hack,true);
		
		if(!file_exists($path.'/'.$folder))
			mkdir($path.'/'.$folder);
			
		if(isset($_GET['fgx'])) $fgx = $_GET['fgx'];
		else $fgx = '';			
			
		echo "<div id=\"editpage\">[<a href=\"javascript:AddFile('$fgx')\">$lng_addfile</a> | <a href=\"javascript:AddFolder('$fgx')\">$lng_addfolder</a>]</div>";
		if($_GET['r']==0)
			echo Gallery('',DTYPE_ADMIN,$cfg['galAdminRows'],$cfg['galAdminCols']);
		if($_GET['r']==1)
			echo Gallery('',DTYPE_INSERTIMG,$cfg['galInsertRows'],$cfg['galInsertCols']);
		break;
		
	// Rename folder/file
	case 'renf':
		if(UserRole() < Role::ProMod) Bail($lng_missing_rights);
		
		$path = SimplifyPath($_POST['path']);
		if(!preg_match("#^files.*#",$path)) Bail($lng_hack,true);
		
		if(preg_match("/[^A-Za-z0-9_\-\.]/",$_POST['renfile'])) Bail($lng_contain);
		
		$newf = dirname($_POST['path']).'/'.$_POST['renfile'];
		
		if(!file_exists($_POST['path'])) Bail($lng_notexist);
		
		if(InvalidFormat($newf))
			exit("300\n$lng_format");
		
		rename($_POST['path'],$newf);
		
		echo "200\n";
		
		//$str=trim(str_replace('files/','',$_POST['path']));
		//sif(strlen($str)) $str=dirname($str);
		if(isset($_GET['fgx'])) $fgx = $_GET['fgx'];
		else $fgx = '';

		echo "<div id=\"editpage\">[<a href=\"javascript:AddFile('$fgx')\">$lng_addfile</a> | <a href=\"javascript:AddFolder('$fgx')\">$lng_addfolder</a>]</div>";
		
		if($_GET['r']==0)
			echo Gallery('',DTYPE_ADMIN,$cfg['galAdminRows'],$cfg['galAdminCols']);
		if($_GET['r']==1)
			echo Gallery('',DTYPE_INSERTIMG,$cfg['galInsertRows'],$cfg['galInsertCols']);
		
		break;
	
	
	// Delete folder
	case 'delf':
		if(UserRole() < Role::ProMod) Bail($lng_missing_rights);
		
		$file = SimplifyPath($_POST['file']);	
		if(!preg_match("#^files.*#",$file)) Bail($lng_hack,true);
		
		if(!is_dir($file))
			unlink($file);
		else deltree($file);
		
		if(isset($_GET['fgx'])) $fgx = $_GET['fgx'];
		else $fgx = '';


		echo "<div id=\"editpage\">[<a href=\"javascript:AddFile('$fgx')\">$lng_addfile</a> | <a href=\"javascript:AddFolder('$fgx')\">$lng_addfolder</a>]</div>";
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

	echo '<div class="treeDiv"><b>'.$GLOBALS['lng_mainmenu'].'</b><br>';
	PrintLinksRec(0, MENU_MAIN,true);
	echo '</div>';

	Legend();

	if($cfg['minorMenu']) {
		echo '<div class="treeDiv"><b>'.$GLOBALS['lng_minormenu'].'</b><br>';
		PrintLinksRec(0,MENU_MINOR,true);
		echo '</div>';
	}

	$str=ob_get_contents();
	ob_end_clean();
	return $str;
}

function PrintLinksRec($parent, $menu, $first=0) {
	global $defIcons,$cfg;
	
	$q = "SELECT * FROM ".PAGES." WHERE parent_idx=$parent AND menu='".$menu."' ORDER BY position";
	$res=mysql_query($q) or
		BailSQLn($GLOBALS['lng_failedmain'],$q);
	
	$id='1';
	if($first) $id='0';

	if(!mysql_affected_rows() && !$first) return;

	if($first) {
		echo '<div class="innertreeDiv">';
		echo "<img alt=\"\" title=\"".$GLOBALS['lng_addpage']."\" class=\"adp\" src=\"".$defIcons['add']."\"> <a href=\"javascript:AddPage(0,0,'$menu',$id)\">".$GLOBALS['lng_createnew']."</a>"; 
	
		if($menu==MENU_MAIN)
			echo '<ul id="tree_major" class="menuTree">';
		else echo '<ul id="tree_minor" class="menuTree">';
	} else echo '<ul>';
	
	
	$numRows=mysql_affected_rows();
	if(!$numRows && $first) {
		echo "</ul>";
		return;
	}
	
	$j=0;
	while($row=mysql_fetch_array($res)) {
		$j++;
		$link = "index.php?p=".$row['idx'];
		
		$name = htmlentities($row['name'],ENT_COMPAT,'UTF-8');
		if(!strlen(trim($name))) $name = '<i>Nameless Page</i>';
		if($row['visibility']!=3) $name="<i>".$name."</i>";
		
		echo '<li id="node'.$row['idx'].'">';
		if($j==$numRows)
			echo '<img src="styles/default/img/cleardot.gif" class="listImg last"><span class="listEl">';
		else echo '<img src="styles/default/img/cleardot.gif" class="listImg"><span class="listEl">';
		echo "<a id=\"adm".$row['idx']."\" href=\"javascript:RenamePage(".$row['idx'].",'".addslashes(htmlentities($row['name'],ENT_COMPAT,'UTF-8'))."','".addslashes(htmlentities($row['info'],ENT_COMPAT,'UTF-8'))."',".$row['visibility'].",".$row['subpoint'].",'".$row['file']."')\">$name</a> ";
		echo '</span>';
		echo "<a href=\"javascript:DelPage(".$row['idx'].")\"><img class=\"adp smallIcon smallimgBin\" alt=\"".$GLOBALS['lng_deletepage']."\" title=\"".$GLOBALS['lng_deletepage']."\" src=\"styles/default/img/cleardot.gif\"></a>\n";
		PrintLinksRec($row['idx'], $menu);
		echo "</li>\n\n";
		
	}
	
	echo '</li></ul>';
	if($first) echo '</ul></div>';
	
	return;
}

function Legend() {
	global $defIcons, $cfg;
?>
		<div class="legend">
		<b><?=$GLOBALS['lng_legend']?></b><br>
		<div class="box">
		<img src="styles/default/img/cleardot.gif" class="adp icon imbBin" align="left" alt="Add">&nbsp;<?=$GLOBALS['lng_addpage']?><br>
		<? if(isset($cfg['menuPics'])) { ?>
		<img src="<?=$defIcons['linkpic']?>" class="adp" align="left" alt="Set up images">&nbsp;<?=$GLOBALS['lng_picLink']?><br>
		<? } ?>
		<!--		<img src="styles/<?=STYLE?>/img/add_into.png" class="adp" align="left" alt="Add into">&nbsp;<?=$GLOBALS['lng_tree']?><br>-->
		<img src="<?=$defIcons['del']?>" class="adp" align="left" alt="Delete">&nbsp;<?=$GLOBALS['lng_deletepage']?><br>
		</div>
		</div><?
}

function DeleteChildPages($id) {
	$q = "SELECT idx FROM ".PAGES." WHERE parent_idx=$id";
	$res=mysql_query($q) or
		BailSQLn($GLOBALS['lng_failedpages'],$q);
	while(list($idx)=mysql_fetch_row($res))
		DeleteChildPages($idx);
		
	$q="DELETE FROM ".PAGES." WHERE idx=$id";
	mysql_query($q) or
		BailSQLn($GLOBALS['lng_faileddeleting']." $id",$q);
}

function InvalidFormat($file) {
	$ext=substr(strtolower(strrchr($file,".")),1);

	if(in_array($ext,$GLOBALS['cfg']['ForbiddenFiles']))
			return 1;
			
	return 0;
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
?>