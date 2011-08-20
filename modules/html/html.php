<?
/*
Plugin Name: HTML
Plugin Image: html.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Plain textarea for html/javascript content
Version: 1
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/
class html extends ContentElement {
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_richtext'; }
	
	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>'html.js'
			)
		);
	}
}
?>