{include file='header.tpl'}
{if !isset($action)}
	WIP, still<br><br>
	<a href="admin.php?a=pgad">Administer Pages</a><br>
	<a href="admin.php?a=filad">Administer Files</a><br>
{else}
	{$content}
{/if}
{include file='footer.tpl'}