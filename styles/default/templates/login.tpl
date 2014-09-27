{capture name="content"}
	<div align="center">
		<div class="loginTitle">{__Anego CMS Administration Area}</div>
		<div class="loginBox">
		<form id="loginForm" action="#" method="post" accept-charset="UTF-8" onsubmit="return false">
			{__Name}<br>
			<input type="text" name="username" value="{$username|escape}"><br><br>
			{__Password}<br>
			<input type="password" name="password"><br>
			<input type="checkbox" name="staysigned" value="1" checked="checked"> {__Stay signed in}<br>
			<input type="button" onclick="login()" name="submit" value="{__Login}">
			<div class="warning">
				{$message}
			</div>
			<div id="javascriptwarning" class="warning">
				{__It seems like you have Javascript disabled. You will not be able to administrate Anego or even log on without it.}
			</div>
			<div class="bothclear"></div>
		</form>
		</div>
	</div>

	<form id="submitForm" action="admin.php?a=li" method="post" accept-charset="UTF-8">
		<input type="hidden" name="username">
		<input type="hidden" name="response">
		<input type="hidden" name="staysigned" value="0">
	</form>
	<script type="text/javascript">
		document.getElementById('javascriptwarning').style.display = 'none';
		
		{if !$showheader}
			Core.loadJavascript('ld.lo');
		{/if}
		{literal}
		function login() {
			var loginForm = document.getElementById("loginForm");
			if (loginForm.username.value == "") {
				alert(__('Please enter your user name.'));
				return false;
			}
			if (loginForm.password.value == "") {
				alert(__('Please enter your password.'));
				return false;
			}
			var submitForm = document.getElementById("submitForm");
			submitForm.username.value = loginForm.username.value;
			if(loginForm.staysigned.checked)
				submitForm.staysigned.value = '1';
			else submitForm.staysigned.value = '0';
			submitForm.response.value = hex_sha256(loginForm.password.value);
			submitForm.submit();
			return false;
		}
		
		document.getElementById("loginForm").password.onkeypress = function(ev) {
			if(!ev) ev = window.event;
			if(ev.keyCode==13) login();
		}
		{/literal}
	</script>
{/capture}

{if $showheader}
	{include file="index.tpl" content=$smarty.capture.content}
{else}
	{$smarty.capture.content}
{/if}
