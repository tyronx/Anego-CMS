<?php
// Language setup
// eng: 1
// ger: 2
$langnum = array("eng"=>1, "ger"=>2);

if($language != 'auto' && !array_key_exists($language, $langnum)) {
	$language = 'eng';
}

if ($language == 'auto') {
	$language = GetCookie('lang');
	if (!array_key_exists($language, $langnum))
		$language='eng';
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
