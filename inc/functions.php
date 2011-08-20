<?
function FormatText(&$str) {
	global $anego; 
	
	$str = preg_replace("/\[gallery ([^\]]*)\]/ie","MakeGal('\\1')",$str);
	$str = preg_replace("/<title name=\"([^\"]*)\">/ie","\$anego->Box('\\1')",$str);
	return $str;
}

function MakeGal($str) {
	$str = stripslashes($str);
	
	$fo = array('path'=>'','rows'=>$GLOBALS['cfg']['galPublicRows'],'cols'=>$GLOBALS['cfg']['galPublicCols'],'width'=>$GLOBALS['cfg']['galThumbnailWidth'],'height'=>$GLOBALS['cfg']['galThumbnailHeight'],'showdirs'=>0);
	//echo "<pre>";
	//print_r($fo);
	
	$opts = explode(" ",$str);
	foreach($opts as $opt) {
		$nameval = explode('=',$opt,2);
		if(!array_key_exists($nameval[0],$fo))
			return sprintf($lng_unkown_opt,$nameval[0]);
			
		if(preg_match("/('|\")([^\\1]*)(\\1)/",$nameval[1],$matches))
			$fo[$nameval[0]] = $matches[2];
		else return sprintf($lng_invalidsynt,$opt);
		
	}
	
	if($fo['showdirs']) $t=DTYPE_IMGANDFOLDERS;
	else $t=DTYPE_NORMAL;
	
	return Gallery($fo['path'],$t,$fo['rows'],$fo['cols'],$fo['width'],$fo['height']);
}

function ParsePars($str) {
	$regexp = '/([a-zA-Z0-9]+)[\s]{0,1}=[\s]{0,1}(\'|"|)((?:(?:\\2)|(?(3)[^\']|[^"]))*)(?:\2)/U';
	preg_match_all($regexp,$str,$matches,PREG_SET_ORDER);
	
	$pars=array();
	foreach($matches as $value)
		$pars[$value[1]]=$value[3];
	 
	return $pars;
}

function AdjustImages($text, $pid) {
	preg_match_all("/<\s*img\s*([^>]*)\s*\/?>/i",$text,$matches);
	
	foreach($matches[1] as $idx=>$p) {
		$pars=ParsePars($p);
		$text_wdt=$pars['width'];
		$text_hgt=$pars['height'];
		if($pars['style']) {
			if(preg_match("/width:\s*(\d+)\s*px/",$pars['style'],$matches))
				$text_wdt=$matches[1];
			if(preg_match("/height:\s*(\d+)\s*px/",$pars['style'],$matches))
				$text_hgt=$matches[1];				
		}
		$proportions = false;
		if($text_wdt==0 || $text_hgt==0) $proportions=true;
		
		list($width, $height) = getimagesize($pars['src']);
		
		//exit("400\n$text_wdt/$text_hgt vs. $width/$height\n\nprop: $proportions");

		if($width != $text_wdt || $height != $text_hgt) {
			$filename = basename($pars['src']);
			$ret=false;
			
			if(!file_exists('var/'.$pid))
				mkdir('var/'.$pid);
				
			if(file_exists("var/$pid/$filename")) {
				list($wid, $heig) = @getimagesize("var/$pid/$filename");
				//exit("400\n$wid/$heig vs. $text_wdt/$text_hgt");
				if($wid!=$text_wdt || $heig != $text_hgt) $ret=CopyResized($pars['src'], $text_wdt, $text_hgt,$proportions,'file','',"var/$pid/$filename");
			} else $ret=CopyResized($pars['src'], $text_wdt, $text_hgt,$proportions,'file','',"var/$pid/$filename");
			
			// Something failed with copy resized
			if(!$ret) continue;
			
			$pars['src']="var/$pid/$filename";
			
			// nice but not needed actually
			/*if($pars['style']) {
				$cnt1=$cnt2=0;
				preg_replace("#width:\d+px;?#",'width:'.$text_wdt.'px;',$pars['style'],-1,$cnt1);
				if(!$cnt1) {
					if($pars['style'][strlen($pars['style'])-1]!=';') $pars['style'].=';';
					$pars['style'].=' width:'.$text_wdt.'px;';
				}
				preg_replace("#height:\d+px;?#",'height:'.$text_hgt.'px;',$pars['style'],-1,$cnt2);
				if(!$cnt2) {
					if($pars['style'][strlen($pars['style'])-1]!=';') $pars['style'].=';';
					$pars['style'].=' height:'.$text_hgt.'px;';
				}
			} else {
				$pars['style'].='width: '.$text_wdt.'px; height: '.$text_hgt.'px;';
			}*/
			
			$newpars='';
			foreach($pars as $name=>$value) {
				$newpars .= ' '.$name.'="'.$value.'"';
			}
			//exit("400\nnewpars is $newpars\n\nbefore: $text\n\nafter:".str_replace($p,$newpars,$text));
		
			$text=str_replace($p,$newpars,$text);
		}
	}
	
	return $text;
}

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

function CopyResized($file, $width = 0, $height = 0, $proportional = true, $output = 'file',$ext='_thumb',$newfile='') {
	$use_linux_commands = false;
	
	if ( $height <= 0 && $width <= 0 ) {
		return false;
	}

 
	$info = getimagesize($file);
	//exit("400\n$file");
	if($info[2]!=IMAGETYPE_GIF && $info[2]!=IMAGETYPE_JPEG && $info[2]!=IMAGETYPE_PNG) return false;
	
	$image = '';
 
	$final_width = 0;
	$final_height = 0;
	list($width_old, $height_old) = $info;
 
	if ($proportional) {
	  if ($width == 0) $factor = $height/$height_old;
	  elseif ($height == 0) $factor = $width/$width_old;
	  else $factor = min ( $width / $width_old, $height / $height_old);   
 
	  $final_width = round ($width_old * $factor);
	  $final_height = round ($height_old * $factor);
 
	}
	else {
	  $final_width = ( $width <= 0 ) ? $width_old : $width;
	  $final_height = ( $height <= 0 ) ? $height_old : $height;
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

	$image_resized = imagecreatetruecolor( $final_width, $final_height );
 
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
	fastimagecopyresampled($image_resized, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
 
	switch ( strtolower($output) ) {
		case 'browser':
			$mime = image_type_to_mime_type($info[2]);
			header("Content-type: $mime");
			$output = NULL;
			break;
		case 'file':
			if(strlen($newfile)) $output=$newfile;
			else $output = preg_replace("/(?U)(.*)(\.\w+)$/","\\1$ext\\2",$file);
			break;
		case 'return':
			return $image_resized;
			break;
		default:
			break;
	}

	switch ( $info[2] ) {
		case IMAGETYPE_GIF:
			imagegif($image_resized, $output);
			break;
			
		case IMAGETYPE_JPEG:
			imagejpeg($image_resized, $output);
			break;
			
		case IMAGETYPE_PNG:
			imagepng($image_resized, $output);
			break;
			
		default:
			return false;
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

?>