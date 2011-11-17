<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Anego CMS Setup</title>
<script type="text/javascript" src="lib/jquery-1.7.min.js"></script>
<style type="text/css">
img {
	border:0;
}

body { 
	color:#232F2F;
	font-family:Arial;
	font-size:102%;
	margin:0;
	padding:0;
}

li {
	padding-bottom:6px;
}

div#title {
	text-align:center;
	width:90%;
	color:white;
	top:0px;
	margin:auto;
	background-color:#36393E;
	height:60px;
	-moz-border-radius-bottomleft: 18px;
	border-bottom-left-radius: 18px;
	-moz-border-radius-bottomright: 18px;
	border-bottom-right-radius: 18px;
}

div#titleText {
	font-size:120%;
	padding-top:15px;
}

div#main { 
	position:relative;
	top:50px;
	left:15%;
	width:800px;
	border:1px solid gray;
	background-color:#DFE1E5;
	padding:0px;
	border-radius:50px;
	-moz-border-radius:50px;
	
	-moz-box-shadow: 6px 6px 5px #eee;
	-webkit-box-shadow: 6px 6px 6px #eee;
	box-shadow: 6px 6px 2x #eee;
}
div#main a {
	color:gray;
}

div#padder {
	padding-left:50px;
	padding-right:30px;
}
.footer {
	font-size:80%;
	text-align:center;
	padding-top:15px;
}

.err {
	font-size:9pt;
	font-style:italic;
}
.box {
	border:1px solid gray;
	font-family:'Courier New';
}
</style>
</head>
<body>
<div style="padding-bottom:70px;">
<div id="main">
	<div id="padder">
		<div id="title"><div id="titleText">Anego CMS Setup</div>(quick'n'dirty)</div>
		<br><h3>Tasks:</h3>
		<ol>
		<li><b>Configure PHP / Server</b><br>
<?php
	include('default.conf.php');
	include('conf.inc.php');

	$good = '<img src="styles/default/img/tick.png" alt="ok"> ';
	$warn = '<img src="styles/default/img/warning.png" alt="warning"> ';
	$bad  = '<img src="styles/default/img/cross.png" alt="bad"> ';
	
	$configGood = true;
	$str='PHP Version 5 or higher';
	
	if(intval(substr(phpversion(),0,1))>=5) {
		echo $good.$str;
	} else  { 
		echo $bad.$str.' <span class="err">- you have '.phpversion()." :(</span>"; 
		$configGood=false;
	}
	
	echo '<br>';

	if(ini_get('short_open_tag')) echo $good;
	else { echo $bad; $configGood=false; }
	echo '(php.ini) short_open_tag on<br>';
	
	if(function_exists('mysql_connect')) echo $good;
		else { echo $bad; $configGood=false; }
	echo '(php.ini) MySQL extension<br>';
	
	if(extension_loaded ('gd')) echo $good;
	else { echo $bad; $configGood=false; }
	echo '(php.ini) GD2 extension<br>';

	if(extension_loaded ('json')) echo $good;
	else { echo $bad; $configGood=false; }
	echo '(php.ini) JSON extension (included in php 5.2 and above)<br>';

	if($cfg['fancyURLs']) {
	?>
	<span id="rwcheck"></span>
	<script type="text/javascript">
	$.get('rwcheck','',function(data) {
		$('#rwcheck').html('<img src="styles/default/img/tick.png" alt="ok"> (apache httpd.conf) mod_rewrite');
	}).error(function() {
		$('#rwcheck').html('<img src="styles/default/img/warning.png" alt="warning"> (apache httpd.conf) mod_rewrite<br>&nbsp;&nbsp;&nbsp;Anego can run without mod_rewrite but you will have to add the following line to the conf.inc.php:<br><div class="box" style="margin-left:10px; width:500px;">$cfg[\'fancyURLs\'] = false;</div>');	});
	</script>
	<?php } else 
		echo $good.'(conf.inc.php) fancyURLs off';
	?>
	</li>
	<li><b>Give write access to following folders/files:</b><br>
<?php

	if($configGood) {
		$writeRequired = Array('var/error.log','var/installed_modules','tmp',FILESROOT,'styles/'.STYLE.'/templates_c');
		$writeable=false;
		$allWriteable=true;
		foreach($writeRequired as $f) {
			if(is_writeable($f)) $writeable = true;
			if(is_dir($f)) $writeableRec = CheckWriteableRec($f);
			if($writeable) {
				if(is_dir($f) && !$writeableRec) {
					echo $warn.$f.' <span class="err">- no write access to some files in this folder!</span><br>';
				} else {
					echo $good.$f.'<br>';
				}
			} else {
				echo $bad.$f;
				echo '<br>';
			}
			$allWriteable = $allWriteable && $writeable;
		}
		
	?>
			
			</li>
			
			<?php
			if($allWriteable) {
			?>
				
				<li><b>Configure conf.inc.php</b><br>
		<?php
				$err = '';
				$icon = $good;
				if(!file_exists('conf.inc.php')) {
					$icon = $bad;
					$err = 'conf.inc.php not found. You can use conf.sample.inc.php as template';
				}
				echo $icon.'Config file'.$err.'<br>';
		
				$sql_link=false;
				if(file_exists('conf.inc.php'))
					$sql_link=@mysql_connect(HOST,SQLUSER,SQLPASS);
				
				if (isset($_GET['a']) && $_GET['a'] == 'crdb') {
					if( @mysql_query('CREATE DATABASE IF NOT EXISTS '.$_POST['dbname']))
						echo '<b>Database \''.$_POST['dbname'].'\' created.</b> You have to add it to the conf.inc.php to be recognized by Setup.<br>';
					else echo '<b>Couldn\'t create database, please create it manually.</b><span class="err">Error was \''.mysql_error().'\'</span><br>';
				}
				
				$icon = $good;
				$err = '';
				if (! $sql_link) { 
					$icon = $bad;
					$err = ' <span class="err">- couldn\'t connect to database</span>'; 
				} else {
					if(!@mysql_select_db(SQLDB)) { 
						$sql_link=0;
						$icon=$bad;
						$err=' <span class="err">- connected but couldn\'t select database (create a database with name: <form style="display:inline;" action="setup.php?a=crdb" method="post"><input type="text" size="10" name="dbname"> <input type="submit" value="Create">)</form></span>'; 
					}
				}
				
				echo $icon.'MySQL Info'.$err.'<br>';
				$err='';
				
				if (file_exists('styles/'.STYLE.'/templates/index.tpl')) {
					echo $good.'Page design';
				} else {
					echo $bad.'Page design <span class="err">- \'styles/'.STYLE.'/templates/index.tpl not found.\'</span>';
				}
				echo '<br>';
				
				
				// Check if we are in the server root directory - if not, anego needs to know about this
				$url = parse_url($cfg['domain']);
				$path = dirname($_SERVER['REQUEST_URI']).'/';
				// dirname adds backslashes in windows O.o
				if(preg_match('/Windows/i', php_uname("s"))) 
					$path = str_replace('\\','/',$path);

				if($path[0] == '/') {
					$path = substr($path,1);
				}
				
				if($path == $url['path']) {
					echo $good;
				} else { 
					echo $bad;
					$sql_link=0;
					$err='<div style="padding-left:20px;">Please add the following line to conf.inc.php:<div class="box">$cfg[\'domain\'].=\''.$path.'\';</div>';
				}
				echo 'Anego path'.$err;
								
		?>
				</li>
				<?php
				if($sql_link) {
					?>
					<li><b>Set up database</b><br>
			<?php
						if(isset($_GET['a']) && $_GET['a']=='indb') {
							if(file_exists('tables.sql')) {
								$sql=file('tables.sql');
								$installOK=true;
								$statement='';
								foreach($sql as $line) {
									if($cfg['tablePrefix']!='anego_' && preg_match('/CREATE/',$line))
										$line=str_replace('anego_',$cfg['tablePrefix'],$line);
									
									$statement.=$line;
									
									if(preg_match("/;$/",$line))
										if(!@mysql_query($statement)) {
											echo '<b>Automatic creation of tables failed, please create them manually (use tables.sql file)</b><br>';
											echo '<span class="err">(Error was \''.mysql_error().'\')</span><br>';
											$installOK=false;
											break;
										} else $statement='';
								}
								if($installOK) echo 'Tables created.<br>';
							} else {
								echo '<b>Can\'t create tables, tables.sql file missing</b><br>';
								$installOK=false;
							}
						}
						
						$tablesOK=false;
						if($sql_link) {
							mysql_query("show tables like '".$cfg['tablePrefix']."%'");
							if(mysql_affected_rows()>=2) $tablesOK=true;
						}
						
						echo ($tablesOK?$good:$bad).' Database tables';
						if($sql_link) echo ' (<a href="setup.php?a=indb">Create tables</a>)';
						if(!$tablesOK && $sql_link) echo '<span class="err"> - minimum required tables not set up</span>';
			?>
						
					</li>
					
					<li><b>Set up a user</b><br><div style="padding-left:6px;">
			<?php
					if (isset($_GET['a']) && $_GET['a']=='gu') {
						if (isset($_POST['users'])) {
							$users=unserialize(urldecode($_POST['users']));
							$userroles=unserialize(urldecode($_POST['userroles']));
						} else { 
							$users=Array();
							$userroles=Array(); 
						}
						
						$pass=hash('sha256',$cfg['hash_salt'].hash('sha256',$_POST['pass']));
						$users[$_POST['name']] = $pass;
						$userroles[$_POST['name']] = $_POST['role'];
						$i=0;

						echo 'Insert/Replace the following lines into the conf.inc.php, then <a href="setup.php">refresh</a>:<br>';
						echo '<table border="0"><tr><td class="box"><pre>$user_accounts = Array(';
						foreach($users as $name=>$pass) {
							if($i++>0) echo ',';
							echo "\n\t'".strtolower($name)."' => '".$pass."'";
						}
						$i=0;
						echo "\n);\n\n";
						echo '$user_roles = Array(';
						foreach($userroles as $name=>$ro) {
							if($i++>0) echo ',';
							echo "\n\t'".strtolower($name)."' => Role::".$ro;
						}
						echo "\n);";
						echo '</pre></td></tr></table><p>...or add another user if you need more (<a href="setup.php">start over</a>):</p>';
					}
			?>
					<form action="setup.php?a=gu" method="post">
					<?php if(isset($users)) echo '<input type="hidden" name="users" value="'.urlencode(serialize($users)).'"><input type="hidden" name="userroles" value="'.urlencode(serialize($userroles)).'">';  ?>
					New user: <input type="text" name="name">, password: <input type="text" name="pass">, role <select name="role"><option>Admin</option><option>ProMod</option><option>SimpleMod</option></select>
					<input type="submit" value="Generate">
					<br>
					</form>
					</div>
					<?php
						if (isset($user_accounts) && count($user_accounts) > 0) {
							echo $good . count($user_accounts);
						} else {
							echo $bad . '0';
						}
					?> Configured user(s)<br>
					</li>
					<li><b>Delete setup.php and tables.sql</b></li>
					<?php
						if($tablesOK && count($user_accounts)) {
							$link='admin.php?a=pgad';
							if($cfg['pageLoad']=='ajax' && $cfg['fancyURLs']) $link='#admpgad';
							echo '<li><b>All done.</b> <a href="'.$cfg['domain'].$link.'">Enjoy setting up your freshly installed Anego CMS!</a><br>Start by creating your first page in the menu</li>';
						}
				
				}
		}
	}
	?>
			</ol>
		<div class="footer">&copy;2011 <a href="http://www.anego.at">Anego</a> Team. All rights reserved.</div>
	</div>
</div>

</div>
</body>
</html>

<?php
function CheckWriteableRec($f) {
	$dir=opendir($f);
	while($file=readdir($dir)) {
		if($file=='.' || $file=='..') continue;
		if(!is_writeable($f.'/'.$file)) return false;
		if(is_dir($f.'/'.$file))
			if(!CheckWriteableRec($f.'/'.$file)) return false;
	}
	return true;
}
?>
