<?
/*
Plugin Name: Richtext
Plugin Image: richtext.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Simple richtext content element. A text element with almost all available TinyMCE features enabled.
Version: 1
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/

class richtext extends ContentElement {
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_richtext'; }
	
	function __construct($pageId, $elementId = 0) {
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}

	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>'richtext.js'
			)
		);
	}
}
?>