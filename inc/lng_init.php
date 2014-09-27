<?php
// Language setup
// eng: 1
// ger: 2
$langnum = array("eng"=>1, "ger"=>2);

if ($cfg['interfacelanguage'] != 'auto' && !array_key_exists($cfg['interfacelanguage'], $langnum)) {
	$cfg['interfacelanguage'] = 'eng';
}

if ($cfg['websitelanguage'] == 'auto') {
	$cfg['websitelanguage'] = GetCookie('lang');
	if (!array_key_exists($cfg['websitelanguage'], $langnum))
		$cfg['websitelanguage']='eng';
}

/**** Table constants ****/

if ($cfg['websitelanguage']) {
	$tableappendix = "_" . $cfg['websitelanguage'];
} else {
	$tableappendix  = "";
}

define("PAGES",$cfg['tablePrefix']."pages".$tableappendix);
define("SETTINGS",$cfg['tablePrefix']."settings".$tableappendix);
define("PAGE_ELEMENT",$cfg['tablePrefix']."pages_element".$tableappendix);
