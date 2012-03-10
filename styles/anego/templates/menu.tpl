{* Odd formatting because the generated html code looks very bad :/ *}
{* HINT: Do not alter class names, they are hardcoded in core.js *}
<ul class="anegoNav {$menuname}">
	{foreach from=$pagetree item=page name=page}
		<li class="{$page.itemclasses}">
			<div class="bothclear">
{if !$page.nolink} {* Only show as link if its supposed to be one *}
					<a href="{$page.link}" title="{$page.info}" class="{$page.linkclass}">
{/if}
{if !$page.defimg}
	{$page.name}
{else}
	{if $page.activeimg && $page.idx == $currentpage}
		<img src="{$page.activeimg}" alt="{$page.name}" title="{$page.info}">
	{else}
		<img src="{$page.defimg}" alt="{$page.name}" title="{$page.info}">
	{/if}
{/if}			{if !$page.nolink}</a>{/if}

			</div>

			{if count($page.children)}
				<div class="subnavbox {$page.childcontainerclasses}"> 
					{include file="menu.tpl" menuname="subnavlist" pagetree=$page.children}
				</div>
			{/if}
		</li>
	{/foreach}
</ul>