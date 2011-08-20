<?
/*
Plugin Name: Seperator
Plugin Image: seperator.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Simple seperator content element
Version: 0.1
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/
class seperator extends ContentElement {
	// basic content element _ standard horizontal line
	
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_seperator'; }
		
	function editElement($id) {
		return true;
	}
	
	function generateContent($id) {
		return "<hr>";
	}

	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>'seperator.js'
			)
		);
	}
}
?>