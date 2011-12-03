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
	var $path;
	
	static $methodMap = Array(
		'save'	=> 'savePicture',
		'lp'	=> 'loadPictures',
		'up'	=> 'uploadPictures'
	);
	
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_gallery'; }
	function picTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_gallerypicture'; }
	
	function __construct($pageId, $elementId = 0) {
		$this->path = FILESROOT.'gallery/' . $elementId . '/';
		
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}
	
	function generateContent() {
		if ( !is_dir($this->path)) {
			return "Gallery not set up yet.";
		} else {
			$str = '<div class="gallery">';
			$pics = $this->pictures();
			while($pic = mysql_fetch_array($pics)) {
				$preview = preg_replace("/(\.\w+)$/i", "_r\\1", $pic['filename']);
				$str .= '<div class="pic">';
				$str .= '<a rel="gallery'. $this->elementId .'" href="' . $this->path . $pic['filename'] . '" title="' . $pic['longdescription'] . '"><img src="' . $this->path . $preview . '" alt="' . $pic['shortdescription'] . '"></a>';
				$str .= '</div>';
			}
			$elemId = $this->elementId;
			
			return <<<EOF
			$str
			</div>
			<script type="text/javascript">
			$(document).ready(function() {
				Core.lightbox('div.gallery a[rel=gallery$elemId]');
			});
			</script>
			<div class="bothclear"></div>
EOF;
		}
	}
	
	function savePicture() {
		$longdesc = mysql_real_escape_string(@$_POST['longdescription']);
		$shortdesc = mysql_real_escape_string(@$_POST['shortdescription']);
		$picid = intval(@$_POST['picid']);
	
		$q = 'UPDATE ' . $this->picTable() . ' SET longdescription=\'' . $longdesc . '\', shortdescription=\'' . $shortdesc . '\' WHERE idx=' . $picid;
		mysql_query($q) or BailSQL('Couldn\'t update image info', $q);
		
		return "200\nok";
	}
	
	// Returns a JSON-Array of pictures
	function loadPictures() {
		$files = array(
			'path' => $this->path,
			'pictures' => array()
		);
		
		$r = $this->pictures();
		while($row = mysql_fetch_array($r)) {
			$files['pictures'][] = array(
				'idx' 				=> $row['idx'],
				'original'			=> $row['filename'],
				'preview'			=> preg_replace('/(\.\w+)$/i', "_r\\1", $row['filename']),
				'shortdescription'	=> $row['shortdescription'],
				'longdescription'	=> $row['longdescription']
			);
		}
		
		return "200\n".json_encode($files);
	}
	
	function pictures() {
		$q = 'SELECT * FROM ' . $this->picTable() . ' WHERE gallery_id=' . $this->elementId . ' ORDER BY position';
		$res = mysql_query($q) or BailSQL('Couldn\'t read images from db', $q);
		return $res;
	}
	
	function picturesinFolder() {
		$files = array(
			'path' => $this->path,
			'original' => array(),
			'preview' => array()
		);
		
		if (is_dir($this->path)) {
			$dir = opendir($this->path);
			while ($file = readdir($dir)) {
				if (validPictureFormat($file)) {
					if(preg_match("/_r\.\w+$/", $file)) {
						$files['preview'][] = $file;
					} else {
						$files['original'][] = $file;
					}
				}
			}
		}
	}
	
	function uploadPictures() {
		global $cfg;
		global $lng_err_file_tobig, $lng_err_file_cantwrite, $lng_err_file_cantwrite2, $lng_err_file_fail, $lng_format, $lng_contain; 
		
		$result = array();
		
		switch($_FILES['pic']['error']) {
			case 0: break;
			case 1: 
			case 2: $result['status'] = $lng_err_file_tobig; break;
			case 7: $result['status'] = $lng_err_file_cantwrite; break;
			default: $result['status'] = sprintf($lng_err_file_fail,$_FILES['pic']['error']); break;
			break;
		}

		if ($_FILES['pic']['error'] == 0) {
			if(! is_dir($this->path)) {
				if(! @mkdir($this->path)) {
					$result['status'] = "501\n" . $lng_err_file_cantwrite; 
					return json_encode($result);
				}
				chmod($this->path, 0664);
			}
		
			$newName = prettyName($_FILES['pic']['name']);
			
			if (validPictureFormat($_FILES['pic']['name'])) {
				if (move_uploaded_file($_FILES['pic']['tmp_name'], $this->path . $newName)) {
					chmod($this->path . $newName, 0664);
					$result['status'] = "200\nok";
					$result['original'] = $cfg['domain'] . $this->path . $newName;
					$this->createPreviewImage($result, $newName);
					
					$q= 'UPDATE ' . $this->picTable() . ' SET position = position+1 WHERE gallery_id=' . $this->elementId;
					$res = mysql_query($q) or BailErr('Couldn\'t move images in db', $q);
					
					$q = 'INSERT INTO ' . $this->picTable() . ' (gallery_id, position, filename) VALUES ';
					$q.= "('" . $this->elementId . "','0', '" . $newName . "')";
					$res = mysql_query($q) or BailErr('Couldn\'t insert image into db', $q);
					
				} else $result['status'] = "503\n".$lng_err_file_cantwrite2;
			} else {
				$result['status'] = "300\n" . $lng_format; 
			}
		}
		
		return json_encode($result);
	}
	
	private function createPreviewImage(&$result, $file) {
		global $cfg;
		
		// create a resized filename_r.(jpg/png/gif) file
		$name_sized = substr($file, 0, strrpos($file, '.')) . '_r' . substr($file, strrpos($file, '.'));
	
		$q = 'SELECT preview_width, preview_height FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysql_query($q) or BailSQL('Couldn\'t retrieve preview image sizes', $q);
		list($pWidth, $pHeight) = mysql_fetch_row($res);
		
		if (CopyResized($this->path . $file, $pWidth, $pHeight, true, 'file', '', $this->path . $name_sized)) {
			$result['preview'] = $cfg['domain'] . $this->path . $name_sized;
		} else {
			$result['status'] = "502\n" . 'Can\'t write thumbnail to disk'; 
		}
	}

	public static function installModule() {
		// Here we just assume that we can write to filesroot. 
		// When uploading pics the user will be notified anyway if this operation failed
		if(! is_dir(FILESROOT . 'gallery'))
			@mkdir(FILESROOT . 'gallery');
		
		return Array(
			'js'=>Array(
				'pageEdit'=>'gallery.js'
			)
		);
	}
}
?>