<?
/*
Plugin Name: HTML
Plugin Image: plainhtml.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Plain textarea for html/javascript content
Version: 1
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/
class plainhtml extends ContentElement {
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_richtext'; }
	
	function __construct($pageId, $elementId = 0) {
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}

	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>'plainhtml.js'
			)
		);
	}
}
?>