adminMenu = new AdminMenuFunctions();

$(document).ready(function() {
	$('.menuTree').livequery(function() {
		$(this).sortableTree({ moved: adminMenu.nodeMoved }); 
	});
});

function AdminMenuFunctions() {
	this.nodeMoved = function(movingNode, targetNode, position) {
		$.get(
			'admin.php', 
			{ a:'movenode', movingNode:movingNode, targetNode:targetNode, position:position },
			function(data) { printLinks(data); }
		);
	}

	this.addPage = function(intopage, menu, mainlink, submit) {
		if(submit) {
			if(document.pagedata.name.value.length==0) {
				alert(lng_entername);
				return;
			}
			
			document.getElementById('im_pr').style.display='';
			document.getElementById("dlgOK").disabled = true;
			document.getElementById("dlgCancel").disabled = true;
			
			var vis = 1;
			if(document.pagedata.menu.checked)
				vis = vis | 2;
			if(document.pagedata.admin.checked)
				vis = vis ^ 1;
			var subm = '0';
			//if(document.pagedata.subm)
			//	if(document.pagedata.subm.checked)
			//		subm = '1';
			var nolink = '0';
			if(document.pagedata.nolink.checked)
				nolink = '1';
				
				
			var data = "name="+urlencode(document.pagedata.name.value)+"&info="+urlencode(document.pagedata.info.value)+"&vis="+vis+"&subm="+subm+"&nolink="+nolink;
			if(document.pagedata.isfile.checked)
				data += "&filename="+urlencode(document.pagedata.filename.value);
			//data += "&id="+page_id;
			if(intopage)
				data+="&intopage="+intopage;
			data += "&menu="+menu;

			Core.postData(data,"admin.php?a=ap",closeAndPrint,RequestTimeout);

		} else {
			var str = '<form name="pagedata" accept-charset="UTF-8" onSubmit="return false">'+lng_pagename+':<br><input type="text" size="40" name="name"><br><br>'+lng_pageinfo+':<br><input type="text" size="40" name="info"><br><br><input type="checkbox" onclick="adminMenu.togglePgFile(this)" name="isfile" value="1"> '+lng_link2file+'<br>';
			str +='<span id="pglink" style="display:none; padding-left:10px">'+lng_filename+': <input type="text" name="filename"><br><br></span>';
			str +='<input type="checkbox" name="menu" value="1" checked> '+lng_showinmenu+'<br>';
			str += '<input type="checkbox" name="admin" value="1"> '+lng_notvisible+'<br>';
			//if(!mainlink)
			//	str +='<input type="checkbox" name="subm" value="1"> '+lng_ident+'<br>';
			str +='<input type="checkbox" name="nolink" value="1"> '+lng_notpage+'</form>';
			OpenDialog({
				title:lng_addpage,
				content:str,
				ok_callback:function () { adminMenu.addPage(intopage,menu,0,1); }
			});
		}
	}

	this.togglePgFile = function(elem) {
		if(elem.checked) {
			document.getElementById('pglink').style.display='';
		//	document.getElementById('dlgBox').style.height='310px';
		} else {
			document.getElementById('pglink').style.display='none';
			//document.getElementById('dlgBox').style.height='270px';
		}
	}

	/*function CheckImgUpload() {
		var el = document.getElementById('iframe1');
		var doc = el.contentDocument;
		if (doc == undefined || doc == null)
			doc = el.contentWindow.document;
		
		var upl,aw;
		if(upl=doc.getElementById('result')) {
			
			if(aw=GetAnswer(upl.innerHTML)) {
				//document.getElementById('resultBox').innerHTML = 'OK!';
				$('im_pr').style.display='none';
				Core.postData("","index.php?a=mainmenu",PrintMainMenu);
				CloseDialog();
			} else
				CloseDialog(); //document.getElementById('resultBox').innerHTML = lng_failed;
			
		} else window.setTimeout(CheckImgUpload,500);
	}

	function SetPicture(page_id) {
		OpenDialog({
			title:lng_imagelinks,
			content:'<iframe id="iframe1" scrolling="no" marginWidth="0" marginheight="0" frameborder="0"></iframe><span id="resultBox"></span>',
			ok_callback:function() {
				var el = document.getElementById('iframe1');
				var doc = el.contentDocument;
				if (doc == undefined || doc == null)
					doc = el.contentWindow.document;
				
				doc.fileupload.submit();
				
				$('im_pr').style.display='';
				$('dlgOK').disabled=true;
				
				window.setTimeout(CheckImgUpload,500);
			}
		});
		
		var str = '<html><body><form method="POST" enctype="multipart/form-data" name="fileupload" action="admin.php?a=app" accept-charset="UTF-8"  onSubmit="return false">';
		str +='<input type="hidden" name="idx" value="'+page_id+'">';
		str +=lng_defImg+'<br><input style="margin-bottom:6px; margin-top:3px;" type="file" name="defImg"><br>';
		str +=lng_hoverImg+'<br><input style="margin-bottom:6px; margin-top:3px;" type="file" name="hoverImg"><br>';
		str +=lng_activeImg+'<br><input style="margin-bottom:6px; margin-top:3px;" type="file" name="activeImg"><br>';
		str += '</form></body></html>';

		var el = document.getElementById('iframe1');
		var doc = el.contentDocument;
		if (doc == undefined || doc == null)
			doc = el.contentWindow.document;
		doc.open();
		doc.write(str);
		doc.close();
	}*/

	this.delPage = function(page_id, submit) {
		OpenDialog({
			title:lng_delete,
			content:'<form name="pagedata" accept-charset="UTF-8" onSubmit="return false"><div align="center">'+lng_delpage+'</div></form>',
			ok_callback:function() {
				document.getElementById('im_pr').style.display='';
				document.getElementById("dlgOK").disabled = true;
				document.getElementById("dlgCancel").disabled = true;
				
				Core.postData("page_id="+page_id,"admin.php?a=dp",closeAndPrint,RequestTimeout);
			},
			buttons:BTN_YESNO});
	}

	this.renamePage = function(page_id, page_name, page_info, vis, subpoint, file) {
		var ch1="", ch2="", ch3="",ch4="";
		var fileDsp = 'display:none; ';
		
		if(vis&2) ch1=" checked";
		if(!(vis&1)) ch2=" checked";
		if(subpoint) ch3=" checked";
		
		if(file.length>0) {
			ch4=" checked";
			fileDsp='';
		}

		var pageLink = 'index.php?p='+page_id;
		if(anego.pageLoad == 'ajax') pageLink = '#pg'+page_id;
		
		var str = '<form name="pagedata" accept-charset="UTF-8" onSubmit="return false">'+lng_rename+':<br><input type="text" name="name" size="35" value="'+page_name.replace(/\"/g,"&quot;")+'">';
		str += '<br><br>'+lng_pageinfo+':<br><input type="text" size="35" name="info" value="'+page_info.replace(/\"/g,"&quot;")+'">';
		
		str += '<br><br><input type="checkbox" onclick="adminMenu.togglePgFile(this)" name="isfile" value="1"'+ch4+'> '+lng_link2file+'<br>';
		str +='<span id="pglink" style="'+fileDsp+'padding-left:10px">'+lng_filename+': <input type="text" name="filename" value="'+file+'"><br><br></span>';
		
		str +='<input type="checkbox" name="menu" value="1"'+ch1+'> '+lng_showinmenu+'<br>';
		//str +='<input type="checkbox" name="subm" value="1"'+ch3+'> '+lng_ident+'<br>';
		str += '<input type="checkbox" name="admin" value="1"'+ch2+'> '+lng_notvisible+'</form>';
		str+='<div style="position:absolute; left:5px; bottom:5px; z-index:2;"><a href="' + pageLink + '" onclick="CloseDialog()">'+lng_topage+'</a></div>';

		OpenDialog({
			title:lng_editpage,
			content:str,
			ok_callback:function() { 
				if(document.pagedata.name.value.length==0) {
					alert(lng_entername);
					return;
				}

				document.getElementById('im_pr').style.display='';
				document.getElementById("dlgOK").disabled = true;
				document.getElementById("dlgCancel").disabled = true;

				var vis = 1;
				if(document.pagedata.menu.checked)
					vis = vis | 2;
				if(document.pagedata.admin.checked)
					vis = vis ^ 1;
				var subm = '0';
				
				var data = "page_id="+page_id+"&name="+urlencode(document.pagedata.name.value)+"&vis="+vis+"&subm="+subm+"&info="+urlencode(document.pagedata.info.value);
				
				if(document.pagedata.isfile.checked)
					data += "&filename="+urlencode(document.pagedata.filename.value);
				else data += '&filename=';
				
				Core.postData(data,"admin.php?a=rp",closeAndPrint,RequestTimeout);
			}
		});
	}

	function closeAndPrint(req) {
		CloseDialog();
		printLinks(req.responseText);
	}

	function printLinks(data) {
		var aw;
		if(aw = GetAnswer(data)) {
			// Todo: remove reloading the content, instead just apply changes to node directly 
			$('#content').html(aw);
			
			$.post('index.php?a=mainmenu','',function(data) {
				$("#menu").html(data);
				$.post("index.php?a=minormenu",function(data) {
					$("#minornav").html(data);
					/* Upgrade "degraded" links to ajax loading again */
					if(anego.pageLoad=='ajax') Core.ajaxifyMenu();
				});
			});
		}
	}
}

function RequestTimeout() {
	alert("The request timed out. Please make sure the connection to the internet is working, then try again.");
	
	document.getElementById('im_pr').style.display='';
	document.getElementById("dlgOK").disabled = false;
	document.getElementById("dlgCancel").disabled = false;
}
