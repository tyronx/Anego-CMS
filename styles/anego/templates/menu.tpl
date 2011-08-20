		<ul class="mainnav">
{section name=mav loop=$mainmenu}
	<li class="{$mainmenu[mav].suggestedClass}">{$mainmenu[mav].link}</li>
{/section}
		</ul>