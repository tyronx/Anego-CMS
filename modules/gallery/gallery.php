<?
/*
Plugin Name: Gallery
Plugin Image: gallery.png
Plugin URI: http://www.anego.at
Plugin Type: ContentElement
Configurable: yes
Description: Simple gallery content element. A convenient way to manage galleries.
Version: 0.2
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
		'up'	=> 'uploadPicture',
		'us'	=> 'updateSettings',
		'dp'	=> 'deletePicture',
		'mp'	=> 'movePicture',
		'getconf' => 'getConfig',
		'saveconf' => 'saveConfig'
	);
	
	function databaseTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_gallery'; }
	function picTable() { return $GLOBALS['cfg']['tablePrefix'].'pages_gallerypicture'; }
	function imageSizesTable() { return $GLOBALS['cfg']['tablePrefix'].'image_sizes'; }
	
	function __construct($pageId, $elementId = 0) {
		$this->path = FILESROOT . 'gallery/' . $elementId . '/';
		
		// Module id is equivalent to classname
		parent::__construct(get_class($this), $pageId, $elementId);
	}
	
	function getConfig() {
		global $cfg;
		
		$q = 'SELECT * FROM ' . $this->imageSizesTable();
		$res = mysql_query($q) or BailSQL('Couldn\'t retrieve preview image sizes', $q);
		
		?>200
		<form name="galleryconfig" id="galleryconfig" onsubmit="return false">
			<h3>Image sizes</h3>
			<table border="0">
				<thead>
				<tr>
					<th>Name</th>
					<th>Width</th>
					<th>Height</th>
					<th></th>
				</tr>
				</thead>
				<tbody>
			<?
			while ($row = mysql_fetch_array($res)) {
				?>
				<tr>
					<td><?=$row['name']?></td>
					<td><?=$row['width']?></td>
					<td><?=$row['height']?></td>
					<td><a class="delete" style="border:0;" href="#delete-<?=$row['idx']?>">
						<img class="adp smallIcon smallimgBin" alt="<?=__('Delete Imagesize')?>" title="<?=__('Delete Page')?>" src="<?=$cfg['path']?>styles/default/img/cleardot.gif">
					</a></td>
				</tr>
				<?
			}

			?>
				</tbody>
			</table>
			<br>
			<a style="margin-left:5px;" href="#newSize"><?=__('New image size')?></a>
		</form>
		<script type="text/javascript">
			// Needed, for whatever reason
			setTimeout(function() {
				var $form = $('form#galleryconfig');
				
				var imageSizeTemplate = 
					'<tr>' +
						'<td></td> ' +
						'<td></td> ' +
						'<td></td> ' + 
						'<td><a class="delete" style="border:0;" href="#"><img class="adp smallIcon smallimgBin" alt="<?=__('Delete Imagesize')?>" title="<?=__('Delete Page')?>" src="<?=$cfg['path']?>styles/default/img/cleardot.gif"></a></td>' +
					'</tr>';
					
				
				var createImageTemplate = 
					'<form name="newimagesize">' +
						'<label for="imagename">Image name</label><br>' +
						'<input type="text" name="imagename" id="imagename" size="19"><br><br> ' +
						'Image width and height<br>' +
						'<input type="text" name="imagewidth" size="5"> x <input type="text" name="imageheight" size="5">' +
					'</form>';
						
				$('a[href="#newSize"]', $form).click(function() {
					OpenDialog({
						title: '<?=__('New image size')?>',
						buttons: BTN_SAVECANCEL,
						content: createImageTemplate,
						ok_callback: function() {
							$newimgform = $('form', this);
							
							var name = $('input[name="imagename"]', $newimgform).val();
							var width = $('input[name="imagewidth"]', $newimgform).val();
							var height = $('input[name="imageheight"]', $newimgform).val();
							
							$form.append('<input type="hidden" name="createname[]" value="' + name + '">');
							$form.append('<input type="hidden" name="createwidth[]" value="' + width + '">');
							$form.append('<input type="hidden" name="createheight[]" value="' + height + '">');
							
							var $row = $(imageSizeTemplate);
							$('td:nth-child(1)', $row).html(name);
							$('td:nth-child(2)', $row).html(width);
							$('td:nth-child(3)', $row).html(height);
							$('td:nth-child(4) a.delete', $row).attr('href', '#');
							
							$('table tbody', $form).append($row);
							
							this.closeDialog();
						}
					});
					
					return false;
				});
				
				$('a.delete', $form).live('click', function() {
					if (! confirm('<?=__('Delete this image size?\nPlease make sure its not being used by any of your galleries!')?>')) return false;

					if ($(this).attr('href').length > 1) {
						var id = $(this).attr('href').substr('#delete-'.length);
						$form.append('<input type="hidden" name="delete[]" value="' + id + '">');
					}
					
					$(this).parents('tr').first().remove();
				});
			}, 80);
		</script>
		<?
		exit();
		/*$smarty = new Smarty();
		$smarty->assign('sizes', $sizes);
		$config = $smarty->fetch('modules/gallery/config.tpl');
		exit("200\n" . $config);*/
	}
	
	function saveConfig() {
		$newsizes = array();
		$newCnt = -1;
		$deleted = array();
		
		
		foreach($_POST['formdata'] as $pair) {
			switch ($pair['name']) {
				case 'createname[]':
					$newCnt++;
					$newsizes[$newCnt] = array('name' => $pair['value']);
					break;
				case 'createwidth[]':
					$newsizes[$newCnt]['width'] = intval($pair['value']);
					break;
				case 'createheight[]':
					$newsizes[$newCnt]['height'] = intval($pair['value']);
					break;
				case 'delete[]':
					$deleted[] = intval($pair['value']);
					break;
			}
		}
		
		foreach($deleted as $id) {
			$q = 'DELETE FROM ' . $this->imageSizesTable() . ' where idx='.$id;
			mysql_query($q) or
				BailSQL('Couldn\'t delete image size', $q);
		}
		
		foreach($newsizes as $new) {
			if ($new['width'] != 0 && $new['height'] != 0) {
				$q ='INSERT INTO ' . $this->imageSizesTable() . ' (name, width, height) '.
					'VALUES (\'' . mysql_real_escape_string($new['name']) . '\', \'' . $new['width'] . '\', \'' . $new['height'] . '\')';
				
				mysql_query($q) or
					BailSQL('Couldn\'t create image size', $q);
			}
		}
		
		exit("200\nok");
	}
	
	function generateContent() {
		if ( !is_dir($this->path)) {
			return "Gallery not set up yet.";
		} else {
			$str = '<div class="gallery">';
			$pics = $this->pictures();
			
			$q = 'SELECT sizes.width as width, sizes.height as height FROM ' . $this->imageSizesTable() . ' as sizes, ' . $this->databaseTable() . ' as gallery WHERE 
				 sizes.idx=gallery.preview_default_size_id AND gallery.idx = '. $this->elementId;
			$res = mysql_query($q) or BailSQL('Couldn\'t retrieve preview image sizes', $q);
			list($w, $h) = mysql_fetch_array($res);

			while($pic = mysql_fetch_array($pics)) {
				$preview = preg_replace("/(\.\w+)$/i", "_r\\1", $pic['filename']);
				$str .= '<div class="pic">';
				$str .= '<a style="width:' . $w . 'px; height:' . $h . 'px;" rel="gallery'. $this->elementId .'" href="' . $this->path . $pic['filename'] . '" title="' . $pic['description'] . '">
						<img class="thumbnail" src="' . $this->path . $preview . '" alt="' . $pic['title'] . '" title="' . $pic['title'] . '"></a>';
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
	
	function movePicture() {
		$picid = intval(@$_POST['picid']);
		$picidxLeft = intval(@$_POST['picidxLeft']);
		//$picidxRight = intval(@$_POST['picidxRight']);
		
		// Get old positions of left & right item
		$picPosLeft = 0;
		
		if ($picidxLeft) {
			$q = 'SELECT position FROM ' . $this->picTable() . ' WHERE idx=' . $picidxLeft;
			$res=mysql_query($q) or BailSQL(__("Failed getting element pos"), $q);
			list($picPosLeft) = mysql_fetch_array($res);
		}
		
		$newPos = $picPosLeft + 1;
		
		/* Start the move */
		mysql_query("START TRANSACTION") or 
			BailSQL(__('Couldn\'t start transaction'), "START TRANSACTION");
		
		// Get old position
		$q = 'SELECT position FROM ' . $this->picTable() . ' WHERE idx=' . $picid;
		$res=mysql_query($q) or BailSQL(__("Failed getting element pos"), $q);
		list($oldpos) = mysql_fetch_array($res);
		
		// Cut it out
		$q = 'UPDATE ' . $this->picTable() . ' SET position=position-1 WHERE gallery_id=' . $this->elementId . ' AND position>' . $oldpos;
		mysql_query($q) or BailSQL(__("Failed cutting out element"), $q);
		// If being moved forward, we also have to decrease that position
		if ( $newPos > $oldpos ) $newPos--;

		// Move all pictures on the right up a position
		$q = 'UPDATE ' . $this->picTable() . ' SET position=position+1 WHERE position>=' . $newPos . ' AND gallery_id=' . $this->elementId;
		$res = mysql_query($q) or BailSQL(__('Couldn\'t move images in db'), $q);
		
		// Finally update our element
		$q = 'UPDATE ' . $this->picTable() . ' SET position=' . $newPos . ' WHERE idx=' . $picid;
		$res = mysql_query($q) or BailSQL(__('Couldn\'t move image in db'), $q);

		if (! mysql_query("COMMIT")) {
			BailSQL(__("Couldn't commit change"),"COMMIT");
		}
		
		return "200\nok";
	}
	
	function savePicture() {
		$desc = mysql_real_escape_string(@$_POST['description']);
		$title = mysql_real_escape_string(@$_POST['title']);
		$picid = intval(@$_POST['picid']);
		
		$q = 'SELECT filename FROM ' . $this->picTable() . ' WHERE idx=' . $picid;
		$res = mysql_query($q) or BailSQL(__('Couldn\'t read image info'), $q);
		
		list($filename)= mysql_fetch_array($res);
		
		if (!$filename) exit("500\n" . __('Wrong picture id or broken picture row in db'));
		
		$pic = $_POST['resizeSettings'];
	
		$q = 'UPDATE ' . $this->picTable() . ' SET 
			description=\'' . $desc . '\', 
			title=\'' . $title . '\',
			prev_cropx=\'' . $pic['selection']['x'] . '\', 
			prev_cropy=\'' . $pic['selection']['y'] . '\', 
			prev_cropw=\'' . $pic['selection']['w'] . '\', 
			prev_croph=\'' . $pic['selection']['h'] . '\', 
			prev_w=\'' . $pic['size']['w'] . '\', 
			prev_h=\'' . $pic['size']['h'] . '\'
		WHERE idx=' . $picid;
		
		mysql_query($q) or BailSQL(__('Couldn\'t update image info'), $q);
		
		$pic = $_POST['resizeSettings'];
		
		if ($pic['changed'] == 'true') {
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
		
		$q = 'SELECT * FROM ' . $this->imageSizesTable() . ' ORDER BY width, height';
		$res = mysql_query($q) or BailSQL(__('Couldn\'t read image sizes info from db'), $q);
		while($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
			$files['sizes'][] = $row;
		}
		
		
		$q = 'SELECT preview_default_size_id, original_default_size_id FROM ' . $this->databaseTable() . ' WHERE idx=' . $this->elementId;
		$res = mysql_query($q) or BailSQL(__('Couldn\'t read gallery info from db'), $q);
		$row = mysql_fetch_assoc($res);
		
		$files = array_merge($row, $files);
		
		$r = $this->pictures();
		while($row = mysql_fetch_assoc($r)) {
			$row['filename_preview'] = preg_replace('/(\.\w+)$/i', "_r\\1", $row['filename']);
			$files['pictures'][] = $row;
		}
		
		$files['count'] = count($files['pictures']);
		
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
		if (! is_dir(FILESROOT . 'gallery/')) {
			if (! @mkdir(FILESROOT . 'gallery/')) {
				$result['status'] = "502\n" . __('Can\'t create directory ' . FILESROOT . 'gallery. Missing writing permissions?'); 
				return json_encode($result);
			}
		}
		
		if (! is_dir($this->path)) {
			if (! @mkdir($this->path)) {
				$result['status'] = "502\n" . __('Can\'t create directory ' . $this->path . '. Missing writing permissions?'); 
				return json_encode($result);
			}
			if (! @chmod($this->path, 0775)) {
				$result['status'] = "502\n" . __('Can\'t create directory ' . $this->path . '. Missing writing permissions?');
				return json_encode($result);
			}
		}
	
		$newName = prettyName($_FILES['pic']['name']);
		
		if (validPictureFormat($_FILES['pic']['name'])) {
			// Move original file to temp folder
			if (! @move_uploaded_file($_FILES['pic']['tmp_name'], $this->path . $newName)) {
				$result['status'] = "503\n" . sprintf(__('Cannot write file to folder %s. Forgot to set writing permissions?'), $this->path);
				return json_encode($result);
			}
			
			// Create original resized file
			if (! $this->createResizedImage($result, $newName, 'original')) {
				$result['status'] = "504\n" . sprintf(__('Cannot write file to folder %s. Forgot to set writing permissions?'), $this->path);
				return json_encode($result);
			}
			
			// Create preview resized file
			if (! $this->createResizedImage($result, $newName, 'preview')) {
				$result['status'] = "505\n" . sprintf(__('Cannot write file to folder %s. Forgot to set writing permissions?'), $this->path);
				return json_encode($result);
			}
			
			// Upload successfull, insert to db and etc.
			@chmod($this->path . $newName, 0664);
			$result['status'] = "200\nok";
			
			$q = 'SELECT max(position) FROM ' . $this->picTable() . ' WHERE gallery_id=' . $this->elementId;
			$res = mysql_query($q) or BailSQL(__('Couldn\'t move images in db'), $q);
			
			list($maxPos) = mysql_fetch_row($res);
			
			$result['maxpos'] = $maxPos;
			
			$q = 'INSERT INTO ' . $this->picTable() . ' (gallery_id, position, filename) VALUES ';
			$q.= "('" . $this->elementId . "','" . ($maxPos + 1) . "', '" . $newName . "')";
			$res = mysql_query($q) or BailSQL(__('Couldn\'t insert image into db'), $q);
			
			$idx = mysql_insert_id();
			
			$q = 'SELECT * FROM ' . $this->picTable() . ' WHERE idx='.$idx;
			$res = mysql_query($q) or BailSQL(__('Couldn\'t insert image into db'), $q);
			
			$result['pic'] = mysql_fetch_array($res);
		} else {
			$result['status'] = "300\n" . $lng_format; 
		}
		
		return json_encode($result);
	}
	
	public function deletePicture() {
		$picid = intval($_POST['picid']);
		if (! $picid) return "500\nWrong pic id?";
		
		$q = 'SELECT filename FROM '. $this->picTable() . ' WHERE idx='.$picid;
		$res = mysql_query($q) or BailSQL(__('Couldn\'t read image in db'), $q);
		list($filename) = mysql_fetch_row($res);
		
		$q = 'DELETE FROM '. $this->picTable() . ' WHERE idx='.$picid;
		$res = mysql_query($q) or BailSQL(__('Couldn\'t remove image from db'), $q);
		
		@unlink($this->path . $filename);
		@unlink($this->path . preg_replace("/(\.\w+)$/i", "_r\\1", $filename));
		
		return "200\nok";
	}
	
	public function updateSettings() {
		$previewSize = intval($_POST['previewSize']);
		$originalSize = intval($_POST['originalSize']);
		
		if($previewSize && $originalSize) {
			$q = 'UPDATE ' . $this->databaseTable() . ' SET original_default_size_id='.$originalSize.', preview_default_size_id='.$previewSize.' WHERE idx=' . $this->elementId;
			$res = mysql_query($q) or BailSQL(__('Couldn\'t update gallery settings'), $q);
		}
	
		return "200\nok";
	}
	
	private function createResizedImage(&$result, $file, $type='preview') {
		global $cfg;
		
		// create a resized filename_r.(jpg/png/gif) file
		$fileExt = '';
		if($type == 'preview') $fileExt = '_r';
	
		$q = 'SELECT sizes.width as width, sizes.height as height FROM ' . $this->imageSizesTable() . ' as sizes, ' . $this->databaseTable() . ' as gallery WHERE 
			 sizes.idx=gallery.' . $type . '_default_size_id AND gallery.idx = '. $this->elementId;

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
	
	public static function moduleInfos($language) {
		if ($language == "ger") {
			return array(
				"name" =>  "Galerie",
				"description" => "Für die Erstellung von Bildergalerien"
			);
		} else {
			return array(
				"name" =>  "Gallery",
				"description" => "To create picture galleries"
			);
		}
	}
}
?>