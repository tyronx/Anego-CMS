<?
/* For standalone include */
if(!defined('CONFIG_LOADED')) {
	include_once("default.conf.php");
	include_once("conf.inc.php");
}

define("LOGINOK",LoggedOn());

function LoggedOn() {
	global $_COOKIE, $cfg, $user_accounts;
	
	if(!strlen($cfg['cookieName'])) return 0;
	if(array_key_exists($cfg['cookieName'],$_COOKIE) && strlen(trim($_COOKIE[$cfg['cookieName']]))) {
		$var=explode(",",$_COOKIE[$cfg['cookieName']]);
		if(array_key_exists(strtolower($var[0]),$user_accounts))
			if(ValidAuth($var[0],$var[1]))
				return strtolower($var[0]);
	}
	
	
	return FALSE;
}

function ValidAuth($user,$pass) {
	global $user_accounts, $cfg;
	$user=strtolower($user);
	if(!isset($user_accounts[$user])) return false;
	
	if($pass == $user_accounts[$user])
		return true;
		
	return false;
}

function GetCookie($cookiename) {
	global $_COOKIE;
	if(array_key_exists($cookiename,$_COOKIE) && strlen(trim($_COOKIE[$cookiename]))) {
		return $_COOKIE[$cookiename];
	}
	else return 0;
}

function UserRole() {
	global $user_roles;
	if(LoggedOn()) return $user_roles[LoggedOn()];
	else return Role::Nothing; 
}

?>