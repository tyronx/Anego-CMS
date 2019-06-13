<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta name="generator" content="Anego CMS (anego.at)"> 
	<title>{$pagetitle}</title>
	<link rel="stylesheet" href="{$basepath}styles/default/default.css" type="text/css" media="screen">
	<link rel="stylesheet" href="{$basepath}styles/anego2/text.css" type="text/css" media="screen">
	<link rel="stylesheet" href="{$basepath}styles/anego2/design.css" type="text/css" media="screen">	

	{$header}
</head>



<body>
	<div id="narrower">
		<div id="border">
			<div id="innerNarrower">
				<table id="headerTable" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td id="cornerL"></td>
					<td id="title"><a href="http://www.anego.at"><img src="{$basepath}styles/anego2/img/logo_white.png" alt="Anego CMS"></a></td>
					<td id="menu">
						{include file='menu.tpl' pagetree=$pages.major menuname="mainnav"}
					</td>
					<td id="minor">
						{include file='menu.tpl' pagetree=$pages.minor menuname="minornav"}
					</td>
					<td id="cornerR"></td>
				</tr>
				</table>

				{$menuadmin}
				<div id="contents">
					<div id="content">
						{$content}
					</div>
				<div id="footer">&copy; 2009 Anego Team. All rights reserved</div>
				</div>
			</div>
		</div>
	</div>
	{$footer}
</body>

</html>