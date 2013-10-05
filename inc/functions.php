<?
/*** General File upload checks - requires config included ***/

function InvalidFormat($file) {
	$ext=substr(strtolower(strrchr($file,".")),1);

	if(in_array($ext,$GLOBALS['cfg']['ForbiddenFiles']))
			return 1;
			
	return 0;
}

function validPictureFormat($file) {
	if(in_array(
		substr(strtolower(strrchr($file, ".")), 1),
		$GLOBALS['cfg']['allowedPictureFiles']
	)) return 1;

	return 0;
}

// used by file managemnet still
function ThumbOf($file) {
	$filename = basename($file);
	if(preg_match('#(?U)(.*)(?-U)(\.\w+)$#',$filename,$splitname))
		return dirname($file).'/'.$splitname[1].'_thumb'.$splitname[2];

	return $file;
}

function hex2rgb($hex) {
  $color = str_replace('#','',$hex);
  $rgb = array('r' => hexdec(substr($color,0,2)),
			   'g' => hexdec(substr($color,2,2)),
			   'b' => hexdec(substr($color,4,2)));
  return $rgb;
}

function BoundBy($val,$min,$max) {
  if($val<$min) return $min;
  if($val>$max) return $max;
  return $val;
}

// Take a filename and replaces a number of chars that might cause trouble in urls
function prettyName($name, $path = '') {
	$lr = array(
		"A" => array("À","Á","Â","Ã","Ä","Å","Ā","Ă"),
		"a" => array("à","á","â","ã","ä","ā","ă","ą"),
		"E" => array("È","É","Ê","Ë","Ē","Ĕ","Ė","Ę","Ě"),
		"e" => array("è","é","ê","ë","ē","ĕ","ė","ę","ě"),
		"I" => array("Ī","Ĭ","İ","Î","Ï","Ì","Í"),
		"i" => array("ì","í","î","ï","ĩ","ī","ĭ"),
		"S" => array("Ş","Ŝ","Ś","Š"),
		"s" => array("ß","ś","ŝ","ş","š"),
		"O" => array("Ò","Ó","Ô","Õ","Ö","Ō","Ŏ","Ő"),
		"o" => array("ò","ó","ô","õ","ö","ō","ŏ","ő"),
		"U" => array("Ù","Ú","Û","Ü","Ũ","Ū","Ŭ","Ů","Ű"),
		"u" => array("ų","ű","ů","ŭ","ū","ũ","ù","ú","û","ü"),
		"C" => array("Ć","Ĉ","Ċ","Č"),
		"c" => array("ć","ĉ","ċ","č"),
		"_" => array(" ","(",")","[","]","<",">")
	);
	
	foreach ($lr as $replace=>$letters) {
		$name = str_replace($letters,$replace,$name);
	}
	
	$len = strlen($name);
	for ($i=0; $i<$len; $i++) {
		$name[$i] = preg_replace("#[^a-zA-Z0-9_\-\.]+#","",$name[$i]);
	}
	
	// If a path is given, it also assures that the file is not being overwritten
	if (strlen($path) && file_exists($path . '/' . $name)) {
		$num = 2;
		if (preg_match("/_(\d+)\.\w+$/", $name, $match)) {
			$num = $match[1] + 1;
			$name = preg_replace("/_\d+(\.\w+)$/","_$num\\1", $name);
		} else {
			$name = substr($name, 0, strpos($name, '.')) . '_' . $num . substr($name, strpos($name, '.'));
		}
		
		while (file_exists($path . '/' . $name)) {
			$num++;
			$name = preg_replace("/_\d+(\.\w+)$/","_$num\\1", $name);
		}
	}
	
	return $name;
}

function CopyResized($file, $width = 0, $height = 0, $proportional = true, $output = 'file', $ext='_thumb', $newfile='', $crop=null) {
	$use_linux_commands = false;
	
	if ( $height <= 0 && $width <= 0 ) {
		return false;
	}

 
	$info = getimagesize($file);
	$image = '';
 
	$final_width = 0;
	$final_height = 0;
	list($width_old, $height_old) = $info;
 

	if( $info[2] != IMAGETYPE_GIF && 
		$info[2] != IMAGETYPE_JPEG && 
		$info[2] != IMAGETYPE_PNG) return false;
	
	$filename = NULL;
	switch ( strtolower($output) ) {
		case 'browser':
			$mime = image_type_to_mime_type($info[2]);
			header("Content-type: $mime");
			break;
		case 'file':
			if(strlen($newfile)) {
				$filename = $newfile;
			} else {
				$filename = preg_replace("/(?U)(.*)(\.\w+)$/","\\1$ext\\2",$file);
			}
			break;
		case 'return':
			return $image_resized;
			break;
		default:
			break;
	}
	
	// Don't resize, just copy
	if ($width_old == $width && $height_old == $height && !$crop) {
		copy($file, $filename);
		return true;
	}
	
	if ($proportional) {
	  if ($width == 0) $factor = $height/$height_old;
	  elseif ($height == 0) $factor = $width/$width_old;
	  else $factor = min ( $width / $width_old, $height / $height_old);   
 
	  $final_width = round ($width_old * $factor);
	  $final_height = round ($height_old * $factor);
	  
	} else {
	  $final_width = ( $width <= 0 ) ? $width_old : $width;
	  $final_height = ( $height <= 0 ) ? $height_old : $height;
	}
	
	// Only resize if the picture is actually bigger than the supplied size
	if($width_old < $width && $height_old < $height) {
		$final_width = $width_old;
		$final_height = $height_old;
	}
	
	switch ( $info[2] ) {
	  case IMAGETYPE_GIF:
		$image = imagecreatefromgif($file);
	  break;
	  case IMAGETYPE_JPEG:
		$image = imagecreatefromjpeg($file);
	  break;
	  case IMAGETYPE_PNG:
		$image = imagecreatefrompng($file);
	  break;
	  default:
		return false;
	}

	if($crop) {
		$image_resized = imagecreatetruecolor( $crop['w'], $crop['h'] );
	} else {
		$image_resized = imagecreatetruecolor( $final_width, $final_height );
	}
 
	if ( ($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG) ) {
	  $trnprt_indx = imagecolortransparent($image);
 
	  // If we have a specific transparent color
	  if ($trnprt_indx >= 0) {
 
		// Get the original image's transparent color's RGB values
		$trnprt_color    = imagecolorsforindex($image, $trnprt_indx);
 
		// Allocate the same color in the new image resource
		$trnprt_indx    = imagecolorallocate($image_resized, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
 
		// Completely fill the background of the new image with allocated color.
		imagefill($image_resized, 0, 0, $trnprt_indx);
 
		// Set the background color for new image to transparent
		imagecolortransparent($image_resized, $trnprt_indx);
 
 
	  } 
	  // Always make a transparent background color for PNGs that don't have one allocated already
	  elseif ($info[2] == IMAGETYPE_PNG) {
 
		// Turn off transparency blending (temporarily)
		imagealphablending($image_resized, false);
 
		// Create a new transparent color for image
		$color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
 
		// Completely fill the background of the new image with allocated color.
		imagefill($image_resized, 0, 0, $color);
 
		// Restore transparency blending
		imagesavealpha($image_resized, true);
	  }
	}
 
	//imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
	//bool imagecopyresampled(resource $dst_image, resource $src_image, int $dst_x, int $dst_y, int $src_x, int $src_y, int $dst_w, int $dst_h, int $src_w, int $src_h )
	if($crop) {
		$propX = $width_old / $final_width;
		$propY = $height_old / $final_height;
	
		fastimagecopyresampled($image_resized, $image, 0, 0, $crop['x'] * $propX, $crop['y'] * $propY, $crop['w'], $crop['h'], $crop['w'] * $propX, $crop['h'] * $propY);
	} else {
		if ($info[2] == IMAGETYPE_PNG) {
			imagecopyresampled ($image_resized, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
		} else {
			fastimagecopyresampled($image_resized, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
		}
	}
 

	switch ( $info[2] ) {
		case IMAGETYPE_GIF:
			imagegif($image_resized, $filename);
			break;
			
		case IMAGETYPE_JPEG:
			imagejpeg($image_resized, $filename, 95);
			break;
			
		case IMAGETYPE_PNG:
			imagepng($image_resized, $filename);
			break;
			
		default:
			return false;
	}
 
	if(strtolower($output) == 'file') {
		@chmod($filename, 0664);
		return $filename;
	}
	
	return true;
}

function fastimagecopyresampled (&$dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3) {
  // Plug-and-Play fastimagecopyresampled function replaces much slower imagecopyresampled.
  // Just include this function and change all "imagecopyresampled" references to "fastimagecopyresampled".
  // Typically from 30 to 60 times faster when reducing high resolution images down to thumbnail size using the default quality setting.
  // Author: Tim Eckel - Date: 12/17/04 - Project: FreeRingers.net - Freely distributable.
  //
  // Optional "quality" parameter (defaults is 3).  Fractional values are allowed, for example 1.5.
  // 1 = Up to 600 times faster.  Poor results, just uses imagecopyresized but removes black edges.
  // 2 = Up to 95 times faster.  Images may appear too sharp, some people may prefer it.
  // 3 = Up to 60 times faster.  Will give high quality smooth results very close to imagecopyresampled.
  // 4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
  // 5 = No speedup.  Just uses imagecopyresampled, highest quality but no advantage over imagecopyresampled.

  if (empty($src_image) || empty($dst_image)) { return false; }
  if ($quality <= 1) {
    $temp = imagecreatetruecolor ($dst_w + 1, $dst_h + 1);
    imagecopyresized ($temp, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w + 1, $dst_h + 1, $src_w, $src_h);
    imagecopyresized ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $dst_w, $dst_h);
    imagedestroy ($temp);
  } elseif ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
    $tmp_w = $dst_w * $quality;
    $tmp_h = $dst_h * $quality;
    $temp = imagecreatetruecolor ($tmp_w + 1, $tmp_h + 1);
    imagecopyresized ($temp, $src_image, $dst_x * $quality, $dst_y * $quality, $src_x, $src_y, $tmp_w + 1, $tmp_h + 1, $src_w, $src_h);
    imagecopyresampled ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $tmp_w, $tmp_h);
    imagedestroy ($temp);
  } else {
    imagecopyresampled ($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
  }
  return true;
}



function SimplifyPath($path) {
  $dirs = explode('/',$path);

  for($i=0; $i<count($dirs);$i++) {
	if($dirs[$i]=="." || $dirs[$i]=="") {
	  array_splice($dirs,$i,1); 
	  $i--;
	}

	if($i>0 && $dirs[$i]=="..") {
	  $cnt = count($dirs);
	  $dirs=Simplify($dirs, $i); 
	  $i-= $cnt-count($dirs);
	}
  }
  return implode('/',$dirs);
}

function Simplify($dirs, $idx) {
  if($idx==0) return $dirs;

  if($dirs[$idx-1]=="..") Simplify($dirs, $idx-1);
  else  array_splice($dirs,$idx-1,2);

  return $dirs;
}



/**** Internationalization ****/

function addL10N($file) {
	if (file_exists($file)) {
		include($file);
		$GLOBALS['lang'] = array_merge($GLOBALS['lang'], $lang);
	}
}

function __($str) {
	global $lang;
	
    if (isset($lang[$str]) && $lang[$str]) {
        return $lang[$str];
		
    } else {
        return $str;
    }
}

function i10n_smarty($source, $template) {
     return preg_replace('!{__([^}]+)}!e', '__("$1")', $source);
}




/**** Error handling ****/

// Todo: You can use this but info.log needs to be set up then in setup.php
/*function LogInfo($file, $text) {
	$fp=fopen('var/info.log','a');
	fwrite($fp,$file.' '.date('d.m.Y H:i:s')."\t".$text."\n");
	fclose($fp);
}*/

function Bail($msg,$no_header=0) {
	ExitError($msg, "", 0, 0, $no_header);
}

// Bail after unsuccessfull SQL Query without header (only used when connecting to DB failed)
function BailSQLn($msg,$q,$log_once=0) {
	ExitError($msg, mysql_error() . "\r\nQuery: '$q'", 2, $log_once, true);
}
// Bail after unsuccessfull SQL Query with header
function BailSQL($msg,$q,$log_once=0) {
	if(IS_AJAX) {
		logError($msg,$q);
		exit("500\n$msg");
	}
	ExitError($msg, mysql_error()."\r\nQuery: '$q'", 2, $log_once);
}
// Normal Bail for non-Ajax Request
function BailErr($msg , $log="", $log_once=0) {
	if(IS_AJAX) {
		logError($msg);
		exit("500\n$msg");
	}
	ExitError($msg,$log,2,$log_once);
}

// Only writes extensive error messages to log file
function logError($msg, $query = '') {
	$mymsg = str_replace('<br>',"\n", 
		sprintf(__('<br>There was an error, I\'m sorry I couldnt execute your request. The respsonsible php script '.
				   'told me: %s<br><br>A detailed error message has been logged.'), $msg));
	
	$log = '';
	if (strlen($query)) {
		$log = mysql_error()."\nQuery: '$query'";
	}
	
	$fp = fopen('var/error.log','a');
	fwrite($fp, "ID: n/a\n");
	fwrite($fp, "Time: ".time()." (".@date("H:i d.m.Y").")\n");
	fwrite($fp, "Error: logError(".str_replace("\n\n","\n",$msg).")\n");
	if (strlen($log)) {
		fwrite($fp, "Log: ".str_replace("\n\n","\n",$log) . "\n");
	}
	fwrite($fp, '$_GET: '.serialize($_GET)."\n");
	fwrite($fp, '$_POST: '.serialize($_POST)."\n");
	fwrite($fp, "\n");
	
	fclose($fp);
}

// Writes error to log file and displays a generic error through smarty 
// $severity:
// 0 ... Print error, dont' log
// 1 ... Print error, simple log msg
// 2 ... Print error, extensive log msg (with serialized GET/POS vars)
// $log_once:
// 0 ... no effect
// id ... check if error has been logged within last hour. if yes, dont log
function ExitError($msg,$ToLog="", $severity=0, $log_once=0, $no_header=0) {
	global $_GET, $_POST, $anego;
	
	if ($severity > 0) {
		$mymsg = '<br>'.sprintf(__('<br>There was an error, I\'m sorry I couldnt execute your request. '.
								   'The respsonsible php script told me: %s<br><br>A detailed error message has been logged.'), $msg);
	} else {
		$mymsg = "<br>$msg";
	}
	
	if ($severity>0) {
		if ($log_once>0 && file_exists('var/error.log')) {
			$log = file_get_contents('var/error.log');
			$entries = explode("\n\n",$log);
			
			foreach ($entries as $entry) {
				if (intval(substr($entry,4,8)) == $log_once) {
					if (time()-intval(substr($entry,strpos($entry,"\n")+6,strpos($entry,"("))) < 3600) {
						//if($GLOBALS['sql_link'] && $no_header==0) {
						if (!$no_header) {
							$anego->AddContent($mymsg);
							$anego->bail('index.tpl');
						//} else echo $mymsg;
						}
						exit();
					}
				}
			}
		}
		
		$fp = fopen('var/error.log','a');
		// if you change 'ID :' or 'Time: ' prefix, also change the substr the lines above!
		fwrite($fp,"ID: " . $log_once . "\r\n");
		fwrite($fp,"Time: " . time() . " (" . @date("H:i d.m.Y") . ")\r\n");
		fwrite($fp,"Error: ". str_replace("\r\n\r\n", "\r\n", $msg) . "\r\n");
		
		if (strlen($ToLog)) {
			fwrite($fp, "Log: " . str_replace("\r\n\r\n","\r\n",$ToLog) . "\r\n");
		}
		
		if ($severity == 2) {
			fwrite($fp, '$_GET: ' . serialize($_GET) . "\r\n");
			fwrite($fp, '$_POST: ' . serialize($_POST) . "\r\n");
		}
		
		fwrite($fp, "\r\n");
		fclose($fp);
	}
	
	$anego->AddJsPreload("\tanego.error=true;");
	
	if (isset($GLOBALS['sql_link']) && $GLOBALS['sql_link'] && $no_header==0 && !defined('DISPLAY_ATTEMPTED')) {
		$anego->AddContent($mymsg);
		$anego->Display('index.tpl');
		//$anego->bail('index.tpl');
	} else {
		$anego->AddContent($mymsg);
		$anego->bail('index.tpl');
	}

	exit();
}
