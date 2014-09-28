<?
/*
Plugin Name: Separator
Plugin Image: separator.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Simple separator content element
Version: 1
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/
class separator extends ContentElement {
	// basic content element _ standard horizontal line
	
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_seperator'; }
		
	function __construct($pageId, $elementId = 0) {
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}

	function generateContent() {
		return "<hr>";
	}

	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>'separator.js'
			)
		);
	}
	
	public static function moduleInfos($language) {
		if ($language == "ger") {
			return array(
				"name" =>  "Trennlinie",
				"description" => " "
			);
		} else {
			return array(
				"name" =>  "Seperating line",
				"description" => " "
			);
		}
	}
}
?>