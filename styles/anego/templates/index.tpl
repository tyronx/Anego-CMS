<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Style-Type" content="text/css">
	<title>{$pagetitle}</title>
	<link rel="stylesheet" href="{$basepath}styles/default/default.css" type="text/css" media="screen">
	<link rel="stylesheet" href="{$basepath}styles/anego/text.css" type="text/css" media="screen">
	<link rel="stylesheet" href="{$basepath}styles/anego/design.css" type="text/css" media="screen">

{$header}
</head>

<body>
	<div id="keyboard"></div>
		<div id="outer">
			<div id="header" class="shadow"><div id="name" onclick="location.href='http://tyron.at'">Anego CMS<br></div></div>
			<div id="admin">{$menuadmin}</div>
			<div id="menu">
				{include file='menu.tpl' pagetree=$pages.major menuname="mainnav"}
			</div>
			
			<div id="centerBox">
				<div id="maincontent" class="shadow round">
					<div id="contents">
						<div id="content">{$content}</div>
						<div class="clearfloat"></div>
					</div>
				</div>
				
				<div id="footer">
					{include file='menu.tpl' pagetree=$pages.minor menuname="minornav"}
				</div>
			</div>
		</div>
	{$footer}
</body>
</html>
