<?
/*
Plugin Name: Gallery
Plugin Image: gallery.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Description: Simple gallery content element. A convenient way to manage galleries.
Version: 0.01
Author: Tyron Madlener
Author URI: http://www.tyron.at
License: GPL2
*/

// Concept:
// Generate a table of pictures and transform it via jscript to different gallery styles such as:
// - Sliding pics with preview: http://coffeescripter.com/code/ad-gallery/ or http://spaceforaname.com/gallery-dark.html
// - Classical table with with awesome lightbox style: http://visuallightbox.com/lightbox-for-photo-crystal-demo.html or http://visuallightbox.com/lightbox-for-photo-noble-demo.html

class gallery extends ContentElement {
	static $methodMap = Array(
		'save'			=> 'saveElement',
		'loadpictures'	=> 'loadPictures'
	);
	
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_gallery'; }
	
	function __construct($pageId, $elementId = 0) {
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}
	
	function generateContent() {
		if(!is_dir('files/gallery/' . $this->pageId))
			return "Gallery not set up yet.";
		else {
		}
	}
	
	// Returns a JSON-Array of pictures
	function loadPictures() {
		return "lots of pics";
	}

	public static function installModule() {
		return Array(
			'js'=>Array(
				'pageEdit'=>'gallery.js'
			)
		);
	}
}
?>