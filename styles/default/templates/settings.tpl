{capture name="content"}
	<div id="adminpage">
		<ul class="tabs settings">
			<li><a href="#pagesettings">{__Website}</a></li>
			<li><a href="#pageoptions">{__General}</a></li>
			<li><a href="#modules">{__Modules}</a></li>
		</ul>
			
		<div class="tab_container settings">
			<div id="pagesettings" class="tab_content">
				<form name="websitesettings" onsubmit="return false">
					{__Home page (The Page which the visitor gets to see first)}<br>
					<select name="homepage">
						{foreach from=$pagelist item=page name=page}
							{if $page.idx == $settings.firstpage}
								<option value="{$page.idx}" selected>{$page.name}</option>
							{else}
								<option value="{$page.idx}">{$page.name}</option>
							{/if}
						{/foreach}
					</select>
					<br><br>{__Website title}<br>
					<input type="text" name="pagetitle" value="{$settings.pagetitle}">
					<br><br>
					{__Website description (e.g. displayed in the google search results, without newlines)}<br>
					<textarea name="description" rows="3" cols="60" style="width:100%">{$settings.description}</textarea>
					<br><br>
					{__Website keywords (seperated by comma, no newlines!)}<br>
					<textarea type="text" cols="60" style="width:100%" rows="3" name="keywords">{$settings.keywords}</textarea>
					<br><br>
					<input type="button" name="SaveWebsite" value="{__Save settings}">
					
					<img src="{$anegopath}styles/default/img/progress_active.gif" class="ajaxLoad">
				</form>
			</div>
			
			<div id="pageoptions" class="tab_content">
				<form name="generalsettings" onsubmit="return false">
					<h2 style="display:inline;">{__Options}</h2>
					<br><br>
					<input type="checkbox" name="autoeditmode" id="autoeditmode" value="1"{if $settings.autoeditmode} checked="checked"{/if}> <label for="autoeditmode">{__When logged in, automatically enter edit mode.}</label>
					<br><br>
					<input type="checkbox" name="developermode" id="developermode" value="1"{if $settings.developermode} checked="checked"{/if}> <label for="developermode">{__Developer Mode (disables Javascript caching and minifying for easier debugging).}</label>
					<br><br>
					<input type="button" name="SaveGeneral" value="{__Save settings}">
				</form>
			</div>
			
			<div id="modules" class="tab_content">
				<h2 style="float:left;">{__Available Modules}</h2>
				<div style="clear:both"></div>
			</div> 

		</div> {* End of tabs *}
	</div> {* End of adminpage *}
	
	<script type="text/javascript">
		{literal}
		$(document).ready(function() {
			settings = new settingsFunctions();
			settings.loadModules();
		});
		{/literal}
	</script>
{/capture}

{if $showheader}
	{include file="index.tpl" content=$smarty.capture.content}
{else}
	{$smarty.capture.content}
{/if}
