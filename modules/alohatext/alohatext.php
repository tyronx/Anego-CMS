<?
/*
Plugin Name: Alohatext
Plugin Image: aloha.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Text content element with the aloha editor
Version: 0.1
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/

class alohatext extends ContentElement {
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_aloha'; }
	
	function generateContent($elementId) {
		global $cfg;
		$q = "SELECT value FROM ".$this->databaseTable()." WHERE idx=$elementId";
		$res=mysql_query($q) or
			BailAjax("Failed deleting element",$q);
		list($str)=mysql_fetch_array($res);
		
		if(strlen($str)==0) $str="Type your text here";
		
		$ck=$cfg['cookieName'];
	
		return <<<EOT
		<script type="text/javascript">
		if(jQuery.cookie('$ck')!=null && (typeof anego.editmode=='undefined' || anego.editmode==false) && anego.noInit!=true) {
			alohaObj$elementId = new alohafuncs($elementId);
			alohaObj$elementId.init();
		}
		</script>
		<div class="alohaContent">$str</div>
EOT;
	}
	
//			if(typeof alohafuncs=="undefined" || !alohafuncs) Core.loadJavascript("modules/alohatext/aloha.js")

	public static function installModule() {
		return Array(
			'js' => Array(
				// js to be loaded when page is being edited
				'pageEdit' => Array('alohatext.js'),
				// js to be loaded when page is viewed & a mod is logged on
				'pageMod' => Array('aloha.js'),
			)
		);	
	}
}
?>