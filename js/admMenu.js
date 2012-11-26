adminMenu = new AdminMenuFunctions();

$(document).ready(function() {
	$('.treeDiv').livequery(function() {
		$('.menuTree').each(function() {
			$(this).sortableTree({ 
				moved: adminMenu.nodeMoved,
				ignoreEventsOnElem: 'img.smallimgBin',
				dragIcon: anego.path + 'styles/default/img/cleardot.gif'
			});
		});
	});
});

function AdminMenuFunctions() {
	this.nodeMoved = function(movingNode, targetNode, position) {
		$.get('admin.php', { 
			a: 'movenode', 
			movingNode:movingNode, 
			targetNode:targetNode, 
			position:position 
		}, function(data) { 
			var aw;
			if(aw = GetAnswer(data)) {
				printLinks(aw, 'moved'); 
			}
		});
	}

	this.addPage = function(intopage, menu) {
		var submit = function() {
			if ($('input[name=name]', this).val().length == 0) {
				alert(lng_entername);
				return;
			}

			this.waitResponse();

			var $dlg = this;
			var vis = 1;
			if ($('input[name=menu]', this).is(':checked'))
				vis = vis | 2;
			if ($('input[name=admin]', this).is(':checked'))
				vis = vis ^ 1;
			
			var fname='';
			if ($('input[name=isfile]', this).is(':checked'))
				fname = $('input[name=filename]', this).val();
			
			var nolink = '0';
			if($('input[name=nolink]', this).is(':checked'))
				nolink = '1';
			
			$.post("admin.php?a=ap", {
				name: $('input[name=name]', this).val(),
				vis: vis,
				nolink: nolink,
				info: $('input[name=info]', this).val(),
				url: $('input[name=url]', this).val(),
				filename: fname,
				menu: menu,
				intopage: intopage
			}, function(data) {
				var aw;
				if(aw = GetAnswer(data)) {
					$dlg.closeDialog();
					printLinks(aw, 'add'); 
				} else {
					$dlg.endWait();
				}
			});
		};

		var $cnt = $(
			'<div class="addPageDlgContent">' + 
				'<div class="floatleft">' +
					lng_rename + ':<br>' + 
					'<input type="text" name="name" size="20">' +
				'</div><div class="floatleft">' +
					lng_pageurl + ':<br>' + 
					'<input type="text" size="20" name="url">' +
				'</div><div class="bothclear"></div>' +
				'<br>' + lng_pageinfo + ':<br>' +
				'<input type="text" size="35" name="info"><br><br>' +
				'<input type="checkbox" name="isfile" id="isfile" value="1"> <label for="isfile">' + lng_link2file + '</label><br>' +
				'<span class="pglink">' + 
					lng_filename + ': <input type="text" name="filename"><br><br>' + 
				'</span>' +
				'<input type="checkbox" name="menu" id="editPageMenu" value="1" checked> <label for="editPageMenu">' + lng_showinmenu + '</label><br>' +
				'<input type="checkbox" name="admin" id="editPageAdmin" value="1"> <label for="editPageAdmin">' + lng_notvisible + '</label><br>' +
				'<input type="checkbox" name="nolink" id="editPageNolink" value="1"> <label for="editPageNolink">' + lng_notpage + '</label>' +
			'</div>');
		
		$('input[name="isfile"]', $cnt).change(function() {
			var checked = $(this).is(':checked');
			$(this).parent().find('.pglink').toggle(checked);
			if (checked) {
				$('input[name="url"]', $cnt).attr('disabled', 'disabled');
			} else {
				if (! $('input[name="nolink"]', $cnt).is(':checked')) {
					$('input[name="url"]', $cnt).removeAttr('disabled');
				}
			}
		});
		
		$('input[name="nolink"]', $cnt).change(function() {
			if ($(this).is(':checked')) {
				$('input[name="url"]', $cnt).attr('disabled', 'disabled');
					$('input[name="isfile"]', $cnt)
						.removeAttr("checked")
						.trigger('change')
						.attr('disabled','disabled');
			} else {
				if (! $('input[name="isfile"]', $cnt).is(':checked')) {
					$('input[name="url"]', $cnt).removeAttr('disabled');
				}
				$('input[name="isfile"]', $cnt).removeAttr("disabled");
			}
		});
		
		$('input[name="name"]', $cnt).keyup(function() {
			var $url = $('input[name="url"]', $cnt);
			if (! $url.data('changed')) {
				var val = $(this).val().toLowerCase()
					.replace(/ä/g,'ae')
					.replace(/ö/g,'oe')
					.replace(/ü/g,'ue')
					.replace(/ß/g,'sz')
					.replace(/[\s\-]+/g, '-')
					.replace(/[^a-z\d-]+/g, '');

				$url.val(val);
			}
		});

		$('input[name="url"]', $cnt).keyup(function() {
			$(this).data('changed', true);
		}).data('changed', false);

		OpenDialog({
			title: lng_addpage,
			content: $cnt,
			ok_callback: submit
		});
	}

	this.delPage = function(page_id, submit) {
		OpenDialog({
			title: lng_delete,
			content: lng_delpage,
			buttons: BTN_YESNO,
			ok_callback: function() {
				var $dlg = this;
				
				$dlg.waitResponse();
				$.post('admin.php?a=dp',{ page_id: page_id }, function(data) {
					var aw;
					if(aw = GetAnswer(data)) {
						$dlg.closeDialog();
						printLinks(aw, 'del'); 
					} else {
						$dlg.endWait();
					}
				});
			}
		});
		
		return false;
	}

	this.renamePage = function(page_id, page_name, page_url, page_info, vis, file) {
		var ch1="", ch2="", ch3="",ch4="";
		var fileDsp = '';
		
		if (vis&2) ch1=" checked";
		if (!(vis&1)) ch2=" checked";
		
		if (file.length>0) {
			ch4=" checked";
			fileDsp='display:block;';
		}

		
		var pageLink;
		if (anego.pageLoad == 'ajax') {
			pageLink = (page_url.length > 0) ? '#pages/' + page_url : '#pages/' + page_id;
		} else {
			pageLink = (page_url.length > 0) ? anego.path + page_url : anego.path + 'pages/' + page_id;
		}
		
		var $cnt = $(
			'<div>' +
				'<div class="renamePageDlgContent">' + 
					'<div class="floatleft">' +
						lng_rename + ':<br>' + 
						'<input type="text" name="name" size="20" value="' + page_name.replace(/\"/g,"&quot;") + '">' +
					'</div><div class="floatleft">' +
						lng_pageurl + ':<br>' + 
						'<input type="text" size="20" name="url" value="' + page_url.replace(/\"/g,"&quot;") + '">' +
					'</div><div class="bothclear"></div>' +
					'<br>' + lng_pageinfo + ':<br>' +
					'<input type="text" style="width: 312px;" name="info" value="' + page_info.replace(/\"/g,"&quot;") + '">' +
					'<br><br>' +
					'<input type="checkbox" id="pageisfile' + page_id + '" name="isfile" value="1"' + ch4 + '> <label for="pageisfile' + page_id + '">' + lng_link2file + '</label><br>' +
					'<span class="pglink" style="' + fileDsp + '">' +
						lng_filename + ': <input type="text" name="filename" value="' + file + '"><br><br>' +
					'</span>' +
					'<input type="checkbox" name="menu" id="editPageMenu' + page_id + '" value="1" '+ch1+'> <label for="editPageMenu' + page_id + '">' + lng_showinmenu + '</label><br>' +
					'<input type="checkbox" name="admin" id="editPageAdmin' + page_id + '" value="1" '+ch2+'> <label for="editPageAdmin' + page_id + '">' + lng_notvisible + '</label><br>' +

				'<div class="toPage">' +
					'<a href="' + pageLink + '">' + lng_topage + '</a>' +
				'</div>' +
			'</div>');
		
		var $dlg = OpenDialog({
			title: lng_editpage,
			content: $cnt,
			ok_callback: function() {
				if ($('input[name=name]', this).val().length == 0) {
					alert(lng_entername);
					return;
				}

				this.waitResponse();

				var $dlg = this;
				var vis = 1;
				if ($('input[name=menu]', this).is(':checked'))
					vis = vis | 2;
				if ($('input[name=admin]', this).is(':checked'))
					vis = vis ^ 1;
				
				var fname='';
				if ($('input[name=isfile]', this).is(':checked'))
					fname = $('input[name=filename]', this).val();
				
				$.post("admin.php?a=rp", {
					page_id: page_id,
					name: $('input[name=name]', this).val(),
					vis: vis,
					url: $('input[name=url]', this).val(),
					info: $('input[name=info]', this).val(),
					filename: fname
				}, function(data) {
					var aw;
					if(aw = GetAnswer(data)) {
						$dlg.closeDialog();
						printLinks(aw, 'renamed');
					} else {
						$dlg.endWait();
					}
				});
			}
		});
		
		$('input[name="isfile"]', $cnt).change(function() {
			$(this).parent().find('.pglink').toggle();
			if ($(this).is(':checked')) {
				$('input[name="url"]', $cnt).attr('disabled', 'disabled');
			} else {
				$('input[name="url"]', $cnt).removeAttr('disabled');
			}
		});
		
		
		$('.toPage a', $cnt).click(function() {
			$dlg.closeDialog();
		});
		
		return false;
	}


	function printLinks(html, type) {
		// Todo: remove reloading the content, instead just apply changes to node directly 
		if(type != 'moved') {
			$('#content').html(html);
		}
		
		$.post('index.php?a=mainmenu','',function(data) {
			$("#menu").html(data);
			$.post("index.php?a=minormenu",function(data) {
				$("ul.minornav").parent().html(data);
				/* Upgrade "degraded" links to ajax loading again */
				if(anego.pageLoad=='ajax') Core.ajaxifyMenu();
			});
		});
	}
}

function RequestTimeout() {
	alert("The request timed out. Please make sure the connection to the internet is working, then try again.");
	
	document.getElementById('im_pr').style.display='';
	document.getElementById("dlgOK").disabled = false;
	document.getElementById("dlgCancel").disabled = false;
}
