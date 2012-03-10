<ul class="{$menuname}">
	{foreach from=$pagetree item=page name=page}
		{* $page.suggestedClass contains:
			navParent		if $page.haschildren 					= 1
			navSelected		if $page.selected && !$page.hasparent	= 1
			childSelected	if $page.childselected					= 1
			subnavSelected	if $page.hasparent && $page.selected	= 1
		*}
		<li class="{$page.suggestedClass}">
			<div class="bothclear">
				{* Only show as link if its supposed to be one *}
				{if !$page.nolink}
					<a href="{$page.link}" title="{$page.info}" class="{$page.linkclass}">
				{/if}
				
					{* No available picture for this page *}
					{if !$page.defimg} 
						{$page.name}
					{else}
						{if $page.activeimg && $page.idx == $currentpage}
							<img src="{$page.activeimg}" alt="{$page.name}" title="{$page.info}">
						{else}
							<img src="{$page.defimg}" alt="{$page.name}" title="{$page.info}">
						{/if}
					{/if}
				
				{if !$page.nolink}
					</a>
				{/if}
			</div>
				
			{if $submenustyle == 'auto' || $submenustyle == 'onselect'}
				
			{/if}
			
			{if count($page.children)}
				<div class="subnavbox"> 
					{include file="menu.tpl" menuname="subnavlist" pagetree=$page.children}
				</div>
			{/if}
			
		</li>
	{/foreach}
</ul>