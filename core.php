<?
header('Content-type: text/html; charset=utf-8');
define("CORE_LOADED",true);
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

require "default.conf.php";
require "conf.inc.php";
require "inc/auth.php";
require "inc/functions.php";
$lang = Array();
require "lang/$language.php";
require "inc/html.php";


/**** Basic Checks ****/

if(substr($cfg['domain'], -1) == '/' && $cfg['path'][0] == '/') {
	$cfg['domain'] = substr($cfg['domain'], 0, strlen($cfg['domain'])-1);
}

$cfg['domain'] .= $cfg['path'];

/* Only available from PHP 5.2 and onward */
if (!function_exists('json_encode')) {
	require 'inc/json_encode.php';
}
	
if (! file_exists('styles/' . STYLE . '/templates/index.tpl'))
	exit("File 'styles/" . STYLE . "/templates/index.tpl' not found. Missing or mistyped style name?");

/* Do not allow tampering with Session/Cookie variables */
if (isset($_REQUEST['_SESSION']) || isset($_REQUEST['_COOKIE'])) die("Get lost Muppet!");

/**** Setup code for the design ****/

// Language setup
// eng: 1
// ger: 2
$langnum = array("eng"=>1,"ger"=>2);

if($language!='auto' && !array_key_exists($language,$langnum))
	$language='eng';

if($language=='auto') {
	$language=GetCookie('lang');
	if(!array_key_exists($language,$langnum))
		$language='eng';
}

function __($str) {
	global $lang;
	
    if (isset($lang[$str]) && $lang[$str]) {
        return $lang[$str];
		
    } else {
        return $str;
    }
}

function i10n_smarty($source, $template) {
     return preg_replace('!{__([^}]+)}!e', '__' . '("$1")', $source);
}

/**** Table constants ****/

if ($language == 'ger') {
	define("PAGES",$cfg['tablePrefix']."pages_ger");
	define("SETTINGS",$cfg['tablePrefix']."settings_ger");
	define("PAGE_ELEMENT",$cfg['tablePrefix']."pages_element_ger");
} else {
	define("PAGES",$cfg['tablePrefix']."pages_eng");
	define("SETTINGS",$cfg['tablePrefix']."settings_eng");
	define("PAGE_ELEMENT",$cfg['tablePrefix']."pages_element_eng");
}

// Main HTML output handler
$anego = new Anego(STYLE);
$anego->assign('language',$language);
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
	$anego->assign('browser', 'ie');
} else {
	$anego->assign('browser', 'non-ie');
}

// Todo: Some of these icons are not needed anymore
$defIcons = array(
	'add' => $cfg['path'] . 'styles/default/img/add.png',
	'del' => $cfg['path'] . 'styles/default/img/delBig.png',
	'drag' => $cfg['path'] . 'styles/default/img/drag.png',
	'linkpic' => $cfg['path'] . 'styles/default/img/linkPic.png',
	'folder' => $cfg['path'] . 'styles/default/img/folder.png',
	'file' => $cfg['path'] . 'styles/default/img/file.png',
	'loading' => $cfg['path'] . 'styles/default/img/progress_active.gif',
	'edit' => $cfg['path'] . 'styles/default/img/pencil.png',
	'editB' => $cfg['path'] . 'styles/default/img/pencilB.png',
	'delB' => $cfg['path'] . 'styles/default/img/del.png'
);

// Tells the loader to load all default javascript files
$anego->AddJsModule('de');
// Loads language related js files
$anego->AddJsModule('ad'.$language);
// Settings required by js files
$anego->AddJsPreload("\tanego=new Object();");
$anego->AddJsPreload("\tanego.language='$language';");
$anego->AddJsPreload("\tanego.style='".STYLE."';");
$anego->AddJsPreload("\tanego.fancyURLs=".($cfg['fancyURLs']?'1':'0').";");
$anego->AddJsPreload("\tanego.submenuStyle='".$cfg['submenuStyle']."';");
$anego->AddJsPreload("\tanego.animatePageLoad=".$cfg['ajaxloadFadeTimer'].";");
$anego->AddJsPreload("\tanego.pageLoad='".$cfg['pageLoad']."';");
$anego->AddJsPreload("\tanego.path='" . $cfg['path'] . "';");
$anego->assign('anegopath', $cfg['path']);

/***** Init database, etc. *****/ 

$customheader="";

define("VIS_USER",1);
define("VIS_INMENU",2);

define('MENU_MAIN','MAIN');
define('MENU_MINOR','MINOR');


/**** Init MySQL ****/
if(!function_exists('mysql_connect')) Bail(__('No PHP MySQL Support on this Server. Please install it.'));

/**** Init MySQL ****/
$sql_link=@mysql_connect(HOST,SQLUSER,SQLPASS)
	or BailErr(__('Our Database is not reachable. Please try again later!'),mysql_error(),true);

if(!@mysql_select_db(SQLDB)) {
	$sql_link=0;
	BailErr(__('Our Database is not reachable. Please try again later!'),mysql_error(),true);
}

/**** More setup code for the design ****/

$s=array();
// Todo: Reduce to the needed settings (dont retrieve all)
$q = "SELECT * FROM ".SETTINGS;
$res = mysql_query($q) or
	BailSQLn(__('A database query failed.'),$q); 
while($row = mysql_fetch_array($res))
	$settings[$row['name']] = $row['value'];

if (!isset($settings['pagetitle']))
	$settings['pagetitle'] = 'Anego CMS';

function getSetting($name) {
	return $settings[$name];
}

function setSetting($name, $value) {
	$q = 'REPLACE INTO '.SETTINGS.' (name,value) VALUES (\'' . $name . '\', \'' . $value . '\')';
	mysql_query($q) or
		BailErr('Failed applying setings', $q);
}

if (! isset($settings['menu_scroll'])) {
	$settings['menu_scroll'] = '0';
}

$anego->AddJsPreload("\tanego.homepage=" . HomePage() . ';');
$anego->AddJsPreload("\tanego.menu_scroll=" . $settings['menu_scroll'] . ";");

if (isset($settings['keywords']) && strlen($settings['keywords'])) {
	$anego->AddHeadHeader("\t" . '<meta name="keywords" content="' . htmlentities(utf8_decode($settings['keywords'])).'">');
}

if (isset($settings['description']) && strlen($settings['description'])) {
	$anego->AddHeadHeader("\t" . '<meta name="description" content="' . htmlentities(utf8_decode(str_replace("\n",' ',$settings['description']))).'">');
}

$anego->assign('pagetitle', str_replace(array('<','>'),array('&lt;','&gt;'), $settings['pagetitle']));
$anego->assign('loginok', LOGINOK);
$anego->assign('editablePage', LOGINOK && basename($_SERVER['SCRIPT_NAME']) == 'index.php');
$anego->assign('basepath', $cfg['path']);

/**** Error handling ****/

// Todo: You can use this but info.log needs to be set up then in setup.php
/*function LogInfo($file, $text) {
	$fp=fopen('var/info.log','a');
	fwrite($fp,$file.' '.date('d.m.Y H:i:s')."\t".$text."\n");
	fclose($fp);
}*/

function Bail($msg,$no_header=0) {
	ExitError($msg, "", 0, 0, $no_header);
}

// Bail after unsuccessfull SQL Query without header (only used when connecting to DB failed)
function BailSQLn($msg,$q,$log_once=0) {
	ExitError($msg, mysql_error() . "\r\nQuery: '$q'", 2, $log_once, true);
}
// Bail after unsuccessfull SQL Query with header
function BailSQL($msg,$q,$log_once=0) {
	if(IS_AJAX) {
		logError($msg,$q);
		exit("500\n$msg");
	}
	ExitError($msg,mysql_error()."\r\nQuery: '$q'",2,$log_once);
}
// Normal Bail for non-Ajax Request
function BailErr($msg , $log="", $log_once=0) {
	if(IS_AJAX) {
		logError($msg);
		exit("500\n$msg");
	}
	ExitError($msg,$log,2,$log_once);
}

// Only writes extensive error messages to log file
function logError($msg, $query = '') {
	$mymsg = str_replace('<br>',"\n", 
		sprintf(__('<br>There was an error, I\'m sorry I couldnt execute your request. The respsonsible php script '.
				   'told me: %s<br><br>A detailed error message has been logged.'),$msg));
	
	$log = '';
	if (strlen($query))
		$log = mysql_error()."\nQuery: '$query'";

	$fp = fopen('var/error.log','a');
	fwrite($fp, "ID: n/a\n");
	fwrite($fp, "Time: ".time()." (".@date("H:i d.m.Y").")\n");
	fwrite($fp, "Error: logError(".str_replace("\n\n","\n",$msg).")\n");
	if (strlen($log)) {
		fwrite($fp, "Log: ".str_replace("\n\n","\n",$log) . "\n");
	}
	fwrite($fp, '$_GET: '.serialize($_GET)."\n");
	fwrite($fp, '$_POST: '.serialize($_POST)."\n");
	fwrite($fp, "\n");
	
	fclose($fp);
}

// Writes error to log file and displays a generic error through smarty 
// $severity:
// 0 ... Print error, dont' log
// 1 ... Print error, simple log msg
// 2 ... Print error, extensive log msg (with serialized GET/POS vars)
// $log_once:
// 0 ... no effect
// id ... check if error has been logged within last hour. if yes, dont log
function ExitError($msg,$ToLog="", $severity=0, $log_once=0, $no_header=0) {
	global $_GET, $_POST, $anego;
	
	if($severity>0)
		$mymsg = '<br>'.sprintf(__('<br>There was an error, I\'m sorry I couldnt execute your request. '.
								   'The respsonsible php script told me: %s<br><br>A detailed error message has been logged.'), $msg);
	else 
		$mymsg = "<br>$msg";
	
	
	if($severity>0) {
		if($log_once>0 && file_exists('var/error.log')) {
			$log = file_get_contents('var/error.log');
			$entries = explode("\n\n",$log);
			
			foreach($entries as $entry) {
				if(intval(substr($entry,4,8)) == $log_once) {
					if(time()-intval(substr($entry,strpos($entry,"\n")+6,strpos($entry,"("))) < 3600) {
						//if($GLOBALS['sql_link'] && $no_header==0) {
						if(!$no_header) {
							$anego->AddContent($mymsg);
							$anego->bail('index.tpl');
						//} else echo $mymsg;
						}
						exit();
					}
				}
			}
		}
		
		$fp = fopen('var/error.log','a');
		// if you change 'ID :' or 'Time: ' prefix, also change the substr the lines above!
		fwrite($fp,"ID: " . $log_once . "\r\n");
		fwrite($fp,"Time: " . time() . " (" . @date("H:i d.m.Y") . ")\r\n");
		fwrite($fp,"Error: ". str_replace("\r\n\r\n", "\r\n", $msg) . "\r\n");
		
		if (strlen($ToLog)) {
			fwrite($fp, "Log: " . str_replace("\r\n\r\n","\r\n",$ToLog) . "\r\n");
		}
		
		if ($severity == 2) {
			fwrite($fp, '$_GET: ' . serialize($_GET) . "\r\n");
			fwrite($fp, '$_POST: ' . serialize($_POST) . "\r\n");
		}
		
		fwrite($fp, "\r\n");
		fclose($fp);
	}
	
	$anego->AddJsPreload("\tanego.error=true;");
	
	if (isset($GLOBALS['sql_link']) && $GLOBALS['sql_link'] && $no_header==0 && !defined('DISPLAY_ATTEMPTED')) {
		$anego->AddContent($mymsg);
		$anego->Display('index.tpl');
		//$anego->bail('index.tpl');
	} else {
		$anego->AddContent($mymsg);
		$anego->bail('index.tpl');
	}

	exit();
}

/******* Page display *******/
/* Determine which page to show - returns the current page to be shown */
function CurrentPage() {
	$p = 0;
	// Integer page?
	if (intval(@$_GET['p'])) {
		$p = intval($_GET['p']);
	}
	// Named page?
	if ( preg_match("/^[\w\d\-]{2,}$/", @$_GET['p'])) {
		$p = $_GET['p'];
	}
	
	// No particular page? Show startpage
	if (! $p) $p = HomePage();
	
	return $p;
}

/* Returns and defines constant HOMEPAGE, which is the first page to be shown when a visitor comes to the site */
function HomePage() {
	if(defined('HOMEPAGE'))
		return HOMEPAGE;
		
	$q = "SELECT value FROM ".SETTINGS." WHERE name='firstpage'";
	$res = mysql_query($q) or
		BailSQLn(__('Failed getting settings data'),$q);
	list($p) = mysql_fetch_array($res);

	/* No home page set up => lets just take the first page we can find */
	if(mysql_affected_rows()==0) {
		$q = "SELECT idx FROM ".PAGES." LIMIT 1";
		$res = mysql_query($q) or
			BailSQLn(__('Failed getting settings data'),$q);
		list($p) = mysql_fetch_array($res);
		// Not even a page available? Damn.
		if (mysql_affected_rows() == 0) $p = -1;
	}
	
	define('HOMEPAGE',$p);
	
	return intval($p);
}

/* Admin links */
function AdminBar($p) {
	global $cfg,$anego;
	
	$userRole = UserRole();
	if(LOGINOK && $userRole>=Role::ProMod) {
		if($p!=-1)
			$anego->AddLink("<a href=\"javascript:Core.editPage()\" id=\"pageEditLink\">" . __('Edit page') . "</a>");
			
		if($cfg['pageLoad'] == 'ajax') {
			$anego->AddLink('<a href="' . $cfg['path'] . 'admin/pgad">' . __('Edit Menu') . '</a>');
			$anego->AddLink('<a href="' . $cfg['path'] . 'admin/filad">' . __('Manage files') . '</a>');
			
			if($userRole>=Role::Admin) 
				$anego->AddLink('<a href="' . $cfg['path'] . 'admin/setg">' . __('Settings') . '</a>');
		} else {
			$anego->AddLink('<a href="' . $cfg['path'] . 'admin/pgad">' . __('Edit Menu') . '</a>');
			$anego->AddLink('<a href="' . $cfg['path'] . 'admin/filad">' . __('Manage files') . '</a>');
			
			if($userRole>=Role::Admin) 
				$anego->AddLink('<a href="' . $cfg['path'] . 'admin/setg">' . __('Settings') . '</a>');
		}
	}
}

/* Print the page */
function PrintPage($p) {
	global $anego, $cfg;
	
	if($p==-1) {
		$anego->AddContent(__('<i>No start page set up yet. Please check your settings.</i>'));
		AdminBar(-1);
		$anego->display('index.tpl');
		exit();
	}
	
	/********* Get page content ********/
	$selection = '';
	if (is_numeric($p)) {
		$selection = "idx='$p'";
	} else {
		$selection = "url='" . mysql_real_escape_string($p) . "'";
	}

	$q = "SELECT idx, name, file, content, content_prepared FROM ".PAGES." WHERE " . $selection . ' ' . (!LOGINOK?"AND (visibility&1)=1":"");
	
	$res = mysql_query($q) or
		BailSQL("Failed getting page data for page $p<br>",$q);
	$row = mysql_fetch_array($res);
	
	AdminBar($row['idx']);
	
	if(!mysql_affected_rows()) {
		$anego->AddContent(__('Page nonexistant or no permission to see it'));
		$anego->display('index.tpl');
		exit();
	}
	
	$anego->curPg = $row['idx'];
	$anego->AddJsPreload("\tanego.curPg = 'pages/" . $row['idx'] . "';");
	
	

	
	$anego->assign('pagetitle', $row['name'] . " - " . $anego->get_template_vars('pagetitle'));
	$anego->assign('pagename', $row['name']);
	
	$js = pageLoadJs($row['idx']);
	if (count($js))
		$anego->AddJsPreload("\tanego.pageJS=new Array('" . implode("','",$js) . "');");
	
	if (strlen($row['file'])) {
		/***** Page is file: include file *****/
		include($row['file']);
	} else {
		/***** Otherwise set up and display page *****/		
		if(!strlen($row['content'])) {
			$row['content']="<i id=\"hasnoContent\">". __('This page has not been filled with content yet. Please use the \'Edit this page\' Link to enter your text') . "</i>";
		}
		if(!strlen($row['content_prepared']) && strlen($row['content']) && $p!=-1) {
			include('inc/modules.php');	
			$pmg = new PageManager();
			$pmg->loadModules();
			$pmg->generatePage($p);
			// Also updates the DB
			$row['content_prepared'] = $pmg->generatePage($p);
		}
		
		$anego->AddContent($row['content_prepared']);
		$anego->assign('pageID',$p);
		if(!$anego->get_template_vars('pageTitle'))
			$anego->assign('pageTitle',$row['name']);
		$anego->display('index.tpl');
	}
}

// Returns required module-js files per page
function pageLoadJs($p) {
	global $language;
	
	if(file_exists('var/installed_modules'))
		$modules = unserialize(file_get_contents('var/installed_modules'));
	else $modules = Array();
		
	// Optimize: Save this information in PAGES so we can eliminate this query
	$q = 'SELECT module_id FROM '.PAGE_ELEMENT.' WHERE page_id='.$p.' GROUP BY module_id';
	$res = mysql_query($q) or
		BailErr("Failed getting page data for page $p<br>",$q);
	
	$js = Array();
	$modjs=Array();
	
	while(list($mid)=mysql_fetch_row($res)) {
		// typecast to array to allow non-array values in the module config
		if(LOGINOK)
			$modjs=array_merge(@(array)$modules[$mid]['config']['js']['load'],@(array)$modules[$mid]['config']['js']['pageMod']);
		else 
			$modjs=array_merge(@(array)$modules[$mid]['config']['js']['load'],@(array)$modules[$mid]['config']['js']['pageView']);
		
		foreach($modjs as $idx=>$file)
			$js[]='modules/'.$mid.'/'.str_replace('%lng',$language,$file);
	}
	
	return $js;
}

// Returns required module-js files per page when editing
function pageEditJs() {
	if(file_exists('var/installed_modules'))
		$modules = unserialize(file_get_contents('var/installed_modules'));
	else $modules = Array();
	
	$js = Array();
	foreach($modules as $mid=>$mod) {
		if(!isset($mod['config']['js'])) echo "$mod has no js";
		if(is_array($mod['config']['js']['pageEdit'])) {
			foreach($mod['config']['js']['pageEdit'] as $file)
				$js[]='modules/'.$mid.'/'.$file;
		} else 
			$js[]='modules/'.$mid.'/'.$mod['config']['js']['pageEdit'];
	}
		
	return $js;
}