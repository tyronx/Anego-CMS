<?
/*
Plugin Name: Richtext
Plugin Image: richtext.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Simple richtext content element. A text element with almost all available TinyMCE features enabled.
Version: 0.1
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/

class richtext extends ContentElement {
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_richtext'; }
	
	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>'richtext.js'
			)
		);
	}
}
?>