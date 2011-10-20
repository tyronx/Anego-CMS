<?
/*
Plugin Name: Simpletext
Plugin Image: simpletext.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Simple text content element. Basic formatting for text elements.
Version: 0.1
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/
class simpletext extends ContentElement {
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_richtext'; }
	
	function __construct($pageId, $elementId = 0) {
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}

	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>'simpletext.js'
			)
		);
	}	
}
?>