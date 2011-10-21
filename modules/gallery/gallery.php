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
		'save'	=> 'saveElement',
		'lp'	=> 'loadPictures',
		'up'	=> 'uploadPictures'
	);
	
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_gallery'; }
	
	function __construct($pageId, $elementId = 0) {
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}
	
	function generateContent() {
		if(!is_dir('files/gallery/' . $this->elementId))
			return "Gallery not set up yet.";
		else {
		}
	}
	
	// Returns a JSON-Array of pictures
	function loadPictures() {
		$path = FILESROOT.'gallery/' . $this->elementId;
		$files = array(
			'path'=>$path,
			'original' => array(),
			'preview' => array()
		);
		if(is_dir($path)) {
			$dir = opendir(path);
			while($file = readdir($dir)) 
				if(preg_match("/_r\.\w+/",$file))
					$files['preview'][] = $file;
				else $files['original'][] = $file;
		}
		
		return "200\n".json_encode($files);
	}
	
	function uploadPictures() {
		$result = print_r($_FILES);
		exit("300\n" . $result);
	/*	switch($_FILES['pictures']['error']) {
			case 0: break;
			case 1: 
			case 2: echo("500\n$lng_err_file_tobig"); break;
			case 7: echo("500\n$lng_err_file_cantwrite"); break;
			default: echo("500\n".sprintf($lng_err_file_fail,$_FILES['pictures']['error'])); break;
			break;
		}

		if($_FILES['pictures']['error'] == 0) {
			$newName = prettyName($_FILES['pictures']['name']);
			if (validPictureFormat($_FILES['pictures']['name'])) {
				if(move_uploaded_file($_FILES['pictures']['tmp_name'],$cfg['tmpPath'].$newName)) {
					chmod ($cfg['tmpPath'].$newName,0664);
					echo "200\n".$cfg['domain'].$cfg['tmpPath'].$newName;
				} else echo ("500\n".$lng_err_file_cantwrite2);
			} else {
				echo "300\n$lng_format";
			}
		} else exit();

	// create a resized filename_r.(jpg/png/gif) file	
	$name_sized = substr($file,0,strrpos($file,'.')).'_r'.substr($file,strrpos($file,'.'));
	
	if(CopyResized($path,$_POST['width'],$_POST['height'],true,'file','',$cfg['imagePath'].$name_sized)) {
		// Also keep a copy of the original size picture
		copy($path,$cfg['imagePath'].$file);
		// delete from tmp directory if its there & possible to delete
		if(is_file($cfg['tmpPath'].$file)) @unlink($cfg['tmpPath'].$file);
		
		exit("200\n".$cfg['domain'].$cfg['imagePath'].$name_sized."\n".$cfg['domain'].$cfg['imagePath'].$file);
	} else exit("500\nnot ok :(");
*/
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