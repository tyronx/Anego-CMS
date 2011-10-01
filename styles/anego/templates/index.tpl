<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Style-Type" content="text/css">
	<title>{$lng_pagetitle}</title>
	<link rel="stylesheet" href="styles/default/default.css" type="text/css" media="screen">
	<link rel="stylesheet" href="styles/anego/text.css" type="text/css" media="screen">
	<link rel="stylesheet" href="styles/anego/design.css" type="text/css" media="screen">	
{$header}
	{literal}
	<script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-19445563-7']);
	  _gaq.push(['_trackPageview']);

	  (function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>
	{/literal}
</head>


<body>
	<div id="keyboard"></div>
		<div id="outer">
			<div id="header" class="shadow"><div id="name" onclick="location.href='http://tyron.at'">Anego Systems<br><div style="font-size:80%">Tyron Madlener</div></div></div>
			<div id="admin">{$menuadmin}</div>
			<div id="menu">
				{include file='menu.tpl'}
			</div>
			
			<div id="centerBox">
				<div id="maincontent" class="shadow round">
					<table border="0" width="100%" cellspacing="0" cellpadding="0">
						<tr>
							<td id="menu"></td>
							<td id="contents"><div id="content">{$content}</div></td>
						</tr>
					</table>
				</div>
				
				<div id="footer">&copy; 2010 Tyron Madlener. Powered by Anego CMS.</div>
			</div>
			<!--<div id="minormenu"><ul class="mnav" id="minornav">{$minormenu}</ul></div>-->
		</div>
	{$footer}
</body>
</html>
