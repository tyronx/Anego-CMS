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
		
	function __construct($pageId, $elementId = 0) {
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}

	function editElement() {
		return true;
	}
	
	function generateContent() {
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