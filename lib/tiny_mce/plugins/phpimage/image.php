<?php
/* Relative path to website */
$cfg['homePath']='../../../../';

/* Paths relative from your website path with trailing slash! */
$cfg['tmpPath'] ='tmp/';
$cfg['imagePath']='files/content/';

chdir($cfg['homePath']);

// Also includes config
require 'inc/auth.php';
// Image resizing tools
require 'inc/functions.php';

if(!LoggedOn())
	exit("300\nYou are not logged on. Please log in as admin to upload a picture");

$lng_err_file_tobig = 'Can\'t upload File. Size exceeds server limits!';
$lng_err_file_cantwrite = 'Cannot write file to temporary files folder. No free space left?';
$lng_err_file_cantwrite2 = 'Cannot write file to disk. Forgot to set writting permissions?';
$lng_err_file_fail = 'A unexpected error occurend while uploading. Error number %s';
$lng_upload = 'Upload successful!';
$lng_format = 'Image not recognized as such. Please upload only png, jpg, gif images.';
$lng_contain = 'File/Folder Names may only contain letters, numbers, dot (.) dash (-) and underscore (_)';

/* Editing a picture requires the original size picture */
if(isset($_POST['getoriginal'])) {
	if(preg_match('#_r\.[a-zA-Z]+$#',$_POST['file'])) {
		$_POST['file']=trim($_POST['file']);
		$name_unsized = basename(preg_replace("#(.*)_r(\.[a-zA-Z]+)$#","\\1\\2",$_POST['file']));		
		if(is_file($cfg['imagePath'].$name_unsized )) {
			list($w,$h)=@getimagesize($cfg['imagePath'].$name_unsized);
			if(!$w) exit("200\n".$lng_format);
			exit("200\n".$cfg['domain'].$cfg['imagePath'].$name_unsized."\n".$w."\n".$h);
		}
		else exit("200\n");
	} else exit("200\n");
}

/* User presses 'Insert' - Image gets inserted into text 
	- resizes the picture
	- copies resized and original to image folder
*/

if(isset($_POST['insert'])) {
	if(!isset($_POST['file']) || !isset($_POST['width']) || !isset($_POST['height'])) exit("300\nScript giving me not enough values :(");
	
	//$url = parse_url($_POST['file']);
	//$file = basename($url['path']);
	$file = basename(substr(trim($_POST['file']),strlen($cfg['domain'])));
	
	// This file may either come from tmp-folder (new image), or image-folder (editing image)
	if(!is_file($cfg['tmpPath'].$file)) {
		// When editing files, the image is not in the tmp path of course
		if(!is_file($cfg['imagePath'].$file)) exit("300\nCouldn't find image on disk. Has it been deleted already?");
		else $path = $cfg['imagePath'].$file;
	} else $path = $cfg['tmpPath'].$file;
	
	// create a resized filename_r.(jpg/png/gif) file	
	$name_sized = substr($file,0,strrpos($file,'.')).'_r'.substr($file,strrpos($file,'.'));
	
	if(CopyResized($path,$_POST['width'],$_POST['height'],true,'file','',$cfg['imagePath'].$name_sized)) {
		// Also keep a copy of the original size picture
		copy($path,$cfg['imagePath'].$file);
		// delete from tmp directory if its there & possible to delete
		if(is_file($cfg['tmpPath'].$file)) @unlink($cfg['tmpPath'].$file);
		
		exit("200\n".$cfg['domain'].$cfg['imagePath'].$name_sized."\n".$cfg['domain'].$cfg['imagePath'].$file);
	} else exit("500\nnot ok :(");
}

/* Upload image temporarily (happens automatically once a image is selected) 
   - also removes/replaces chars that don't belong to a file name
   - displays a meaningful error message if an error appeared (hopefully)
*/
if(isset($_POST['uploadImg'])) {
	echo "<html><body><span id='result' style='display:none;'>";
	switch($_FILES['fiupl']['error']) {
		case 0: break;
		case 1: 
		case 2: echo("500\n$lng_err_file_tobig"); break;
		case 7: echo("500\n$lng_err_file_cantwrite"); break;
		default: echo("500\n".sprintf($lng_err_file_fail,$_FILES['fiupl']['error'])); break;
		break;
	}

	if($_FILES['fiupl']['error']==0) {
		$newName = prettyName($_FILES['fiupl']['name']);
		if (validPictureFormat($_FILES['fiupl']['name'])) {
			if (move_uploaded_file($_FILES['fiupl']['tmp_name'], $cfg['tmpPath'].$newName)) {
				chmod ($cfg['tmpPath'].$newName,0664);
				echo "200\n".$cfg['domain'].$cfg['tmpPath'].$newName;
			} else {
				echo ("500\n".$lng_err_file_cantwrite2);
			}
		} else {
			echo "300\n$lng_format";
		}
	}
	echo <<<STREND
	</span>
	<form method="POST" enctype="multipart/form-data" name="fileupload" action="" accept-charset="UTF-8"  onSubmit="return false">
	<input type="file" onchange="parent.FileEntered()" style="background-color:#FFF; border:1px solid #808080; font-size:11px;" size="40" id="fiupl" name="fiupl">
	<input type="hidden" name="uploadImg" value="1">
	</form>
	</body></html>
STREND;
	exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{#phpimage_dlg.dialog_title}</title>
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
	<script type="text/javascript" src="../../utils/mctabs.js"></script>
	<script type="text/javascript" src="../../utils/form_utils.js"></script>
	<script type="text/javascript" src="../../utils/validate.js"></script>
	<script type="text/javascript" src="../../utils/editable_selects.js"></script>
	<script type="text/javascript" src="js/image.js"></script>
	<link href="css/phpimage.css" rel="stylesheet" type="text/css" />
</head>
<body id="advimage" style="display: none" onresize="ResizePrev()">
	<form id="wholepageForm" action="" enctype="multipart/form-data" method="post"> 
		<div class="tabs">
			<ul>
				<li id="general_tab" class="current"><span><a href="javascript:mcTabs.displayTab('general_tab','general_panel');" onmousedown="return false;">{#phpimage_dlg.tab_general}</a></span></li>
				<li id="appearance_tab"><span><a href="javascript:mcTabs.displayTab('appearance_tab','appearance_panel');" onmousedown="return false;">{#phpimage_dlg.tab_appearance}</a></span></li>
				<li id="advanced_tab" style="visibility:hidden"><span><a href="javascript:mcTabs.displayTab('advanced_tab','advanced_panel');" onmousedown="return false;">{#phpimage_dlg.tab_advanced}</a></span></li>
			</ul>
		</div>

		<div class="panel_wrapper" id="panel_wrapper">
			<div id="general_panel" class="panel current">
				<fieldset>
						<legend>{#phpimage_dlg.browse}</legend>

						<table class="properties">
							<tr>
								<td>
									<iframe id="iframe0" scrolling="no" marginWidth="0" marginheight="0" frameborder="0" style="width: 100%; height:23px;"></iframe>
									<span id="uploadResult"></span>
									<!--<input type="file" id="image_field" name="image_field" class="mceFocus" value="" />
									<input type="hidden" name="action" value="image" />
									<input type="submit" id="insert" name="insert" value="{#phpimage_dlg.upload}" />-->
								</td>
							</tr>
						</table>
				</fieldset>
				<fieldset>
						<legend>{#phpimage_dlg.general}</legend>

						<table class="properties">
							<tr style="display:none;">
								<td class="column1"><label id="srclabel" for="src">{#phpimage_dlg.src}</label></td>
								<td colspan="2"><table border="0" cellspacing="0" cellpadding="0">
									<tr> 
									  <td><input name="src" type="text" id="src" value="" onchange="ImageDialog.showPreviewImage(this.value);" /></td> 
									  <td id="srcbrowsercontainer">&nbsp;</td>
									</tr>
								  </table></td>
							</tr>
							<tr>
								<td><label for="src_list">{#phpimage_dlg.image_list}</label></td>
								<td><select id="src_list" name="src_list" onchange="document.getElementById('src').value=this.options[this.selectedIndex].value;document.getElementById('alt').value=this.options[this.selectedIndex].text;document.getElementById('title').value=this.options[this.selectedIndex].text;ImageDialog.showPreviewImage(this.options[this.selectedIndex].value);"><option value=""></option></select></td>
							</tr>
							<tr> 
								<td class="column1" width="105" ><label id="altlabel" for="alt">{#phpimage_dlg.alt}</label></td> 
								<td colspan="2"><input id="alt" name="alt" type="text" value="" /></td> 
							</tr> 
							<tr> 
								<td class="column1"><label id="titlelabel" for="title">{#phpimage_dlg.title}</label></td> 
								<td colspan="2"><input id="title" name="title" type="text" value="" /></td> 
							</tr>
							<tr>
								<td class="column1"><label id="widthlabel" for="width">{#phpimage_dlg.dimensions}</label></td> 
								<td colspan="2">
									<input name="width" type="text" id="width" value="" size="5" maxlength="5" class="size" onkeydown="ImageDialog.widthDown(event);" onkeyup="ImageDialog.widthPress(event);" /> x 
									<input name="height" type="text" id="height" value="" size="5" maxlength="5" class="size" onkeydown="ImageDialog.heightDown(event);" onkeyup="ImageDialog.heightPress(event);" /> px &nbsp;&nbsp;<a href="#" onclick="ImageDialog.setoriginalSize()">{#phpimage_dlg.origsize}</a>
								</td>
							</tr>
							<tr id="zoomableRow" style="display:none;">
								<td class="column1"></td> 
								<td colspan="2"><input id="zoomable" name="zoomable" type="checkbox" value="1" checked="checked"/> <label id="zoomablelabel" for="zoomable">{#phpimage_dlg.iszoomable}</label></td> 
							</tr>
						</table>
				</fieldset>

				<fieldset>
					<legend>{#phpimage_dlg.preview}</legend>
					<div id="prev"></div>
				</fieldset>
			</div>

			<div id="appearance_panel" class="panel">
				<fieldset>
					<legend>{#phpimage_dlg.tab_appearance}</legend>

					<table border="0" cellpadding="4" cellspacing="0">
						<tr> 
							<td class="column1"><label id="alignlabel" for="align">{#phpimage_dlg.align}</label></td> 
							<td><select id="align" name="align" onchange="ImageDialog.updateStyle('align');ImageDialog.changeAppearance();"> 
									<option value="">{#not_set}</option> 
									<option value="baseline">{#phpimage_dlg.align_baseline}</option>
									<option value="top">{#phpimage_dlg.align_top}</option>
									<option value="middle">{#phpimage_dlg.align_middle}</option>
									<option value="bottom">{#phpimage_dlg.align_bottom}</option>
									<option value="text-top">{#phpimage_dlg.align_texttop}</option>
									<option value="text-bottom">{#phpimage_dlg.align_textbottom}</option>
									<option value="left">{#phpimage_dlg.align_left}</option>
									<option value="right">{#phpimage_dlg.align_right}</option>
								</select> 
							</td>
							<td rowspan="5" valign="top">
								<div class="alignPreview">
									<img id="alignSampleImg" src="img/sample.gif" alt="{#phpimage_dlg.example_img}" />
									Lorem ipsum, Dolor sit amet, consectetuer adipiscing loreum ipsum edipiscing elit, sed diam
									nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.Loreum ipsum
									edipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam
									erat volutpat.
								</div>
							</td>
						</tr>
						<tr>
							<td class="column1"><label id="vspacelabel" for="vspace">{#phpimage_dlg.vspace}</label></td> 
							<td><input name="vspace" type="text" id="vspace" value="" size="3" maxlength="3" class="number" onchange="ImageDialog.updateStyle('vspace');ImageDialog.changeAppearance();" onblur="ImageDialog.updateStyle('vspace');ImageDialog.changeAppearance();" />
							</td>
						</tr>

						<tr> 
							<td class="column1"><label id="hspacelabel" for="hspace">{#phpimage_dlg.hspace}</label></td> 
							<td><input name="hspace" type="text" id="hspace" value="" size="3" maxlength="3" class="number" onchange="ImageDialog.updateStyle('hspace');ImageDialog.changeAppearance();" onblur="ImageDialog.updateStyle('hspace');ImageDialog.changeAppearance();" /></td> 
						</tr>

						<tr>
							<td class="column1"><label id="borderlabel" for="border">{#phpimage_dlg.border}</label></td> 
							<td><input id="border" name="border" type="text" value="" size="3" maxlength="3" class="number" onchange="ImageDialog.updateStyle('border');ImageDialog.changeAppearance();" onblur="ImageDialog.updateStyle('border');ImageDialog.changeAppearance();" /></td> 
						</tr>

						<tr>
							<td><label for="class_list">{#class_name}</label></td>
							<td colspan="0"><select id="class_list" name="class_list" class="mceEditableSelect"><option value=""></option></select></td>
						</tr>

						<tr>
							<td class="column1"><label id="stylelabel" for="style">{#phpimage_dlg.style}</label></td> 
							<td colspan="2"><input id="style" name="style" type="text" value="" onchange="ImageDialog.changeAppearance();" /></td> 
						</tr>

						<!-- <tr>
							<td class="column1"><label id="classeslabel" for="classes">{#phpimage_dlg.classes}</label></td> 
							<td colspan="2"><input id="classes" name="classes" type="text" value="" onchange="selectByValue(this.form,'classlist',this.value,true);" /></td> 
						</tr> -->
					</table>
				</fieldset>
			</div>

			<div id="advanced_panel" class="panel">
				<fieldset>
					<legend>{#phpimage_dlg.swap_image}</legend>

					<input type="checkbox" id="onmousemovecheck" name="onmousemovecheck" class="checkbox" onclick="ImageDialog.setSwapImage(this.checked);" />
					<label id="onmousemovechecklabel" for="onmousemovecheck">{#phpimage_dlg.alt_image}</label>

					<table border="0" cellpadding="4" cellspacing="0" width="100%">
							<tr>
								<td class="column1"><label id="onmouseoversrclabel" for="onmouseoversrc">{#phpimage_dlg.mouseover}</label></td> 
								<td><table border="0" cellspacing="0" cellpadding="0"> 
									<tr> 
									  <td><input id="onmouseoversrc" name="onmouseoversrc" type="text" value="" /></td> 
									  <td id="onmouseoversrccontainer">&nbsp;</td>
									</tr>
								  </table></td>
							</tr>
							<tr>
								<td><label for="over_list">{#phpimage_dlg.image_list}</label></td>
								<td><select id="over_list" name="over_list" onchange="document.getElementById('onmouseoversrc').value=this.options[this.selectedIndex].value;"><option value=""></option></select></td>
							</tr>
							<tr> 
								<td class="column1"><label id="onmouseoutsrclabel" for="onmouseoutsrc">{#phpimage_dlg.mouseout}</label></td> 
								<td class="column2"><table border="0" cellspacing="0" cellpadding="0"> 
									<tr> 
									  <td><input id="onmouseoutsrc" name="onmouseoutsrc" type="text" value="" /></td> 
									  <td id="onmouseoutsrccontainer">&nbsp;</td>
									</tr> 
								  </table></td> 
							</tr>
							<tr>
								<td><label for="out_list">{#phpimage_dlg.image_list}</label></td>
								<td><select id="out_list" name="out_list" onchange="document.getElementById('onmouseoutsrc').value=this.options[this.selectedIndex].value;"><option value=""></option></select></td>
							</tr>
					</table>
				</fieldset>

				<fieldset>
					<legend>{#phpimage_dlg.misc}</legend>

					<table border="0" cellpadding="4" cellspacing="0">
						<tr>
							<td class="column1"><label id="idlabel" for="id">{#phpimage_dlg.id}</label></td> 
							<td><input id="id" name="id" type="text" value="" /></td> 
						</tr>

						<tr>
							<td class="column1"><label id="dirlabel" for="dir">{#phpimage_dlg.langdir}</label></td> 
							<td>
								<select id="dir" name="dir" onchange="ImageDialog.changeAppearance();"> 
										<option value="">{#not_set}</option> 
										<option value="ltr">{#phpimage_dlg.ltr}</option> 
										<option value="rtl">{#phpimage_dlg.rtl}</option> 
								</select>
							</td> 
						</tr>

						<tr>
							<td class="column1"><label id="langlabel" for="lang">{#phpimage_dlg.langcode}</label></td> 
							<td>
								<input id="lang" name="lang" type="text" value="" />
							</td> 
						</tr>

						<tr>
							<td class="column1"><label id="usemaplabel" for="usemap">{#phpimage_dlg.map}</label></td> 
							<td>
								<input id="usemap" name="usemap" type="text" value="" />
							</td> 
						</tr>

						<tr>
							<td class="column1"><label id="longdesclabel" for="longdesc">{#phpimage_dlg.long_desc}</label></td>
							<td><table border="0" cellspacing="0" cellpadding="0">
									<tr>
									  <td><input id="longdesc" name="longdesc" type="text" value="" /></td>
									  <td id="longdesccontainer">&nbsp;</td>
									</tr>
								</table></td> 
						</tr>
					</table>
				</fieldset>
			</div>

		</div>

		<div class="mceActionPanel">
			<div style="float: left">
				<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
			</div>

			<div style="float: right">
				<input type="submit" id="insert" name="insert" value="{#insert}" onclick="ImageDialog.insert();return false;" />
			</div>
		</div>
	</form>
<script type="text/javascript">
window.setTimeout(AddForm,50);
window.setTimeout(AutoUpload,250);	
</script>
</body> 
</html> 