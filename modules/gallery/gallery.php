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
		'up'	=> 'uploadPicture'
	);
	
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_gallery'; }
	function picTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_gallerypicture'; }
	function imageSizesTable() { return $GLOBALS['cfg']['tablePrefix'].'image_sizes'; }
	
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
				$str .= '<a rel="gallery'. $this->elementId .'" href="' . $this->path . $pic['filename'] . '" title="' . $pic['longdescription'] . '">
						<img class="thumbnail" src="' . $this->path . $preview . '" alt="' . $pic['shortdescription'] . '"></a>';
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
		
		$q = 'SELECT filename FROM ' . $this->picTable() . ' WHERE idx=' . $picid;
		$res = mysql_query($q) or BailSQL(__('Couldn\'t read image info'), $q);
		
		list($filename)= mysql_fetch_array($res);
		
		if (!$filename) exit("500\n" . __('Wrong picture id or broken picture row in db'));
		
		$pic = $_POST['resizeSettings'];
	
		$q = 'UPDATE ' . $this->picTable() . ' SET 
			longdescription=\'' . $longdesc . '\', 
			shortdescription=\'' . $shortdesc . '\',
			prev_cropx=\'' . $pic['selection']['x'] . '\', 
			prev_cropy=\'' . $pic['selection']['y'] . '\', 
			prev_cropw=\'' . $pic['selection']['w'] . '\', 
			prev_croph=\'' . $pic['selection']['h'] . '\', 
			prev_w=\'' . $pic['size']['w'] . '\', 
			prev_h=\'' . $pic['size']['h'] . '\'
		WHERE idx=' . $picid;
		
		mysql_query($q) or BailSQL(__('Couldn\'t update image info'), $q);
		
		$pic = $_POST['resizeSettings'];
		
		if ($pic['changed']) {
			if ($pic['selection']['w'] > 0) {
				CopyResized($this->path . $filename, $pic['size']['w'], $pic['size']['h'], false, 'file', '_r', '', $pic['selection']);
			} else {
				CopyResized($this->path . $filename, $pic['size']['w'], $pic['size']['h'], true, 'file', '_r');
			}
		}
		
		$q = 'SELECT * FROM ' . $this->picTable() . ' WHERE idx=' . $picid;
		$res = mysql_query($q) or BailSQL(__('Couldn\'t read image info'), $q);
		
		$row = mysql_fetch_array($res, MYSQL_ASSOC);

		$row['filename_preview'] = preg_replace('/(\.\w+)$/i', "_r\\1", $row['filename']);
		
		return "200\n".json_encode($row);
	}
	
	// Returns a JSON-Array of pictures
	function loadPictures() {
		$files = array(
			'path' => $this->path,
			'sizes' => array(),
			'pictures' => array()
		);
		
		$q = 'SELECT * FROM ' . $this->imageSizesTable();
		$res = mysql_query($q) or BailSQL(__('Couldn\'t read image sizes info from db'), $q);
		while($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
			$files['sizes'][] = $row;
		}
		
		
		$q = 'SELECT preview_default_size_id, original_default_size_id FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysql_query($q) or BailSQL(__('Couldn\'t read gallery info from db'), $q);
		$row = mysql_fetch_array($res, MYSQL_ASSOC);
		
		$files = array_merge($row, $files);
		
		$r = $this->pictures();
		while($row = mysql_fetch_array($r, MYSQL_ASSOC)) {
			$row['filename_preview'] = preg_replace('/(\.\w+)$/i', "_r\\1", $row['filename']);
			$files['pictures'][] = $row;
		}
		
		return "200\n".json_encode($files);
	}
	
	function pictures() {
		$q = 'SELECT * FROM ' . $this->picTable() . ' WHERE gallery_id=' . $this->elementId . ' ORDER BY position';
		$res = mysql_query($q) or BailSQL(__('Couldn\'t read images from db'), $q);
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
	
	function uploadPicture() {
		global $cfg;
		
		$result = array();
		
		switch($_FILES['pic']['error']) {
			case 0: break;
			case 1: 
			case 2: $result['status'] = "501\n" . __('Can\'t upload File. Size exceeds server limits!'); break;
			case 7: $result['status'] = "501\n" . __('Cannot write file to temporary files folder. No free space left?'); break;
			default: $result['status'] = "501\n" . sprintf(__('A unexpected error occurend while uploading. Error number %s'), $_FILES['pic']['error']); break;
			break;
		}

		if ($_FILES['pic']['error'] != 0) {
			return json_encode($result);
		}
		
		// Create gallery directory if not created yet
		if(! is_dir($this->path)) {
			if(! @mkdir($this->path)) {
				$result['status'] = "501\n" . $lng_err_file_cantwrite; 
				return json_encode($result);
			}
			if(! @chmod($this->path, 0664)) {
				$result['status'] = "501\n" . $lng_err_file_cantwrite; 
				return json_encode($result);
			}
		}
	
		$newName = prettyName($_FILES['pic']['name']);
		
		if (validPictureFormat($_FILES['pic']['name'])) {
			// Move original file to temp folder
			if (! move_uploaded_file($_FILES['pic']['tmp_name'], $this->path . $newName)) {
				$result['status'] = "503\n" . sprintf('Cannot write file to folder %s. Forgot to set writing permissions?', $this->path);
				return json_encode($result);
			}
			
			// Create original resized file
			if (! $this->createResizedImage($result, $newName, 'original')) {
				$result['status'] = "504\n" . sprintf('Cannot write file to folder %s. Forgot to set writing permissions?', $this->path);
				return json_encode($result);
			}
			
			// Create preview resized file
			if (! $this->createResizedImage($result, $newName, 'preview')) {
				$result['status'] = "505\n" . sprintf('Cannot write file to folder %s. Forgot to set writing permissions?', $this->path);
				return json_encode($result);
			}
			
			// Upload successfull, insert to db and etc.
			@chmod($this->path . $newName, 0664);
			$result['status'] = "200\nok";
			
			$q = 'SELECT max(position) FROM ' . $this->picTable() . ' WHERE gallery_id=' . $this->elementId;
			$res = mysql_query($q) or BailSQL('Couldn\'t move images in db', $q);
			list($maxPos) = mysql_fetch_row($res);
			
			$q = 'INSERT INTO ' . $this->picTable() . ' (gallery_id, position, filename) VALUES ';
			$q.= "('" . $this->elementId . "','" . ($maxPos + 1) . "', '" . $newName . "')";
			$res = mysql_query($q) or BailSQL('Couldn\'t insert image into db', $q);
			
			$idx = mysql_insert_id();
			
			$q = 'SELECT * FROM ' . $this->picTable() . ' WHERE idx='.$idx;
			$res = mysql_query($q) or BailSQL('Couldn\'t insert image into db', $q);
			
			$result['pic'] = mysql_fetch_array($res);
		} else {
			$result['status'] = "300\n" . $lng_format; 
		}
		
		return json_encode($result);
	}
	
	private function createResizedImage(&$result, $file, $type='preview') {
		global $cfg;
		
		// create a resized filename_r.(jpg/png/gif) file
		$fileExt = '';
		if($type == 'preview') $fileExt = '_r';
	
		$q = 'SELECT sizes.width as width, sizes.height as height FROM ' . $this->imageSizesTable() . ' as sizes, ' . $this->databaseTable() . ' as gallery WHERE 
			 sizes.idx=gallery.' . $type . '_default_size_id AND gallery.idx = '. $this->elementId;
		$result['sql'] = $q;
		$res = mysql_query($q) or BailSQL('Couldn\'t retrieve preview image sizes', $q);
		
		// Id unset or wrong => dont resize, unless its a preview image. in that case just assume 160x120 as standard
		if (! mysql_affected_rows()) {
			if($type == 'preview') {
				$pWidth = 160;
				$pHeight = 120;
			} else {
				$result[$type] = $cfg['domain'] . $this->path . $file;
				return true;
			}
		} else {
			list($pWidth, $pHeight) = mysql_fetch_row($res);
		}
		
		if ($name_sized = CopyResized($this->path . $file, $pWidth, $pHeight, true, 'file', $fileExt)) {
			$result[$type] = $cfg['domain'] . $name_sized;
		} else {
			$result['status'] = "502\n" . 'Can\'t write thumbnail to disk'; 
			return false;
		}
		
		return true;
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