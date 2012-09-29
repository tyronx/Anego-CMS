<?php

/* Relative path to website */
$cfg['homePath']='../../../../';

/* Paths relative from your website path with trailing slash! */
$cfg['tmpPath'] ='tmp/';
$cfg['filePath']='files/downloads/';

chdir($cfg['homePath']);

if (!is_dir($cfg['filePath'])) {
	mkdir($cfg['filePath']);
}

// Also includes config
require 'inc/auth.php';
// Image resizing tools
require 'inc/functions.php';
// Some language strings
require 'lang/'.$language.'.php';

$cfg['domain'] .= ($cfg['path']{0} == '/') ? substr($cfg['path'],1) : $cfg['path'];

if (!LoggedOn())
	exit("300\nYou are not logged on. Please log in as admin to add a download");



$iconTypes = array(
	'audio'		=> array('mp3','mp4','m4a', 'wav', 'ogg'),
	'pdf'		=> array('pdf', 'ppt', 'sdd'),
	'picture'	=> array('jpg', 'jpeg', 'png', 'gif', 'aiff', 'psd'),
	'text'		=> array('doc','docx','odt','ott','stw','rtf','txt','xml'),
	'video'		=> array('wmv','avi','mkv', 'mpg', 'mpeg'),
	'xls'		=> array('cvs','ods','ots','sxc','stc','dif','xsl','xlt'),
	'zip'		=> array('zip', 'rar', 'gz', 'tar', 'bzip2', '7zip')
);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Add downloadable file</title>
    <script type="text/javascript" src="../../tiny_mce_popup.js" ></script>
    <script type="text/javascript" src="editor_plugin.js" ></script>
    <base target="_self" />
</head>
<body id="fileuploader" onresize="ResizePrev()">
<?php

	if (@$_GET['q'] == 'upload') {
		
		$uploadedFile = $_FILES["upload_file"];
		
		if (preg_match("/\.(" . implode("|", $cfg['ForbiddenFiles']) . ")$/", $uploadedFile['name'])) {
			exit( __('Format not allowed! Any kind of php,html,js files are refused. Sorry') );
		}
		
		if (move_uploaded_file($uploadedFile['tmp_name'], $cfg['filePath'] . $uploadedFile['name'])) {
			CloseWindow($cfg['filePath'] . $uploadedFile['name']);
		} else {
			exit( __('Upload failed. Is there write access in the files folder?') );
		}
	} else {
		display_upload_form();
	}
?>
</body>
</html>

<?php
// displays the upload form
function display_upload_form() {
	global $cfg;
	?>
		<script type="text/javascript">
		function showProgress() {
			document.getElementById('progress_div').style.visibility='visible';
		}
		</script>
		<form id="wholepageForm" name="form1" action="upload.php?<?php echo $_SERVER["QUERY_STRING"]; ?>&q=upload" method="post" enctype="multipart/form-data" onsubmit="">
			<div class="tabs">
				<ul>
					<li id="general_tab" class="current"><span><a href="javascript:mcTabs.displayTab('general_tab','general_panel');" onmousedown="return false;">{#phpimage_dlg.tab_general}</a></span></li>
				</ul>
			</div>
			
			<div class="panel_wrapper" id="panel_wrapper">
				<div id="general_panel" class="panel current" style="height: 110px;">
					<fieldset>
						<legend>{#phpimage_dlg.browse}</legend>
						<input type="file" size="70" name="upload_file" id="File1"/><br/>
					</fieldset>
					<div>
						<p>Max file size: <?=maxFileSize()?></p>
					</div>
				</div>
				<div id="progress_div" style="visibility: hidden;">
					<img src="<?=$cfg['domain']?>styles/default/img/progress_active.gif" alt="Please wait..." style="padding-top: 5px;">
				</div>
			</div>
			
			<div class="mceActionPanel">
				<div style="float: left">
					<input type="submit" id="insert" name="insert" value="{#insert}" onclick="showProgress()" />
				</div>
				
				<div style="float: right">
					<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
				</div>
			</div>

		</form>
	<?php
}



function maxFileSize() {
	return in_mb(min(ini_get('post_max_size'),ini_get('upload_max_filesize'))) . 'MB';
	
}

function in_mb($val) {
	$ret = intval(trim($val));
	$last = strtolower($val[strlen($val)-1]);

	switch($last) {
		// The 'G' modifier is available since PHP 5.1.0
		case 'g':
			$ret *= 1024;
		case 'k':
			$ret = $val/1024;
	}

	return $ret;
}

function CloseWindow($filepath) {
	global $iconTypes, $cfg;
	
	$filename = basename($filepath);
	$ending = substr($filename, strrpos($filename, '.') + 1);
	
	$imageURL = 'styles/default/img/filetypes/file.png';
	
	foreach($iconTypes as $type => $endings) {
		if (in_array($ending, $endings)) {
			$imageURL = 'styles/default/img/filetypes/' . $type . '.png';
			break;
		}
	}

	?>
		<script language="javascript" type="text/javascript">	
			ClosePluginPopup('<?=$cfg['domain'] . $filepath?>', '<?=$filename?>', '<?=$cfg['domain'] . $imageURL?>', '<?=$filename?>');
		</script>
	<?php
}