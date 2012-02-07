blogfuncs = new blogFunctions();

if (! jQuery().tinymce) Core.loadJavascript("ld.ap");

function blogFunctions() {
	var myself = this;
	var blogEntryLoaded = -1;
	var loadingEntry =  false;
	
	this.initBlog = function(blog_id) {
		$.get("modules/blog/", {
			a: 'g',
			id: blog_id,
			editmode: (typeof anego.editmode == 'boolean' && anego.editmode == true)
		}, function(data) {
			var aw;
			if(aw = GetAnswer(data)) {
				response = $.parseJSON(aw);
				
				$("#blogc_" + blog_id).html(response.blogs);
				$("#blognav_" + blog_id).html(response.navigation);
				/*$("#blognav_" + blog_id + ' a').each(function() {
					$(this).attr('href', $(this).attr('href').replace(/^.*(\d+)$/, '#'));
				});*/
				
				Core.initPageContent();
				$("#blogc_" + blog_id + " .blogElements").jPaginate({ start: 1, items: 4 });
			}
		});
	}
	
	this.onloadPage = function(page) {
		// When loading the blog entry, Core.curPg will not change so the 'edit page' link still works
		// but then we need to take care that when the users presses the back button, the old page gets loaded again
		if (page.type == 'pg' && page.fullpath == anego.curPg && blogEntryLoaded != -1) {
			blogEntryLoaded = -1;
			// Be really careful when calling loadpage, as it will fire the loadPage event again, so make sure not to fall in a endless loop
			Core.loadPage(location, { forceLoad: true });
			return true;
		}
		
		if (page.type == 'blog') {
			// page.tail[0] is entry id, page.id is blog id
			myself.loadEntry(null, page.tail[0], page.id);
			return true;
		}
		
		blogEntryLoaded = -1;
		return false;
	}
	
	Core.addloadPageHook(this.onloadPage);
	
	this.newEntry = function(blog_id) {
		OpenDialog({
			title: lngBlog.newblog,
			buttons: BTN_SAVECANCEL,
			nohotkeys: true,
			content: lngBlog.blogtitle + '<br><input style="width:90%;" type="text" id="newblogTitle"><br><br><textarea style="width:100%" id="newblogText"></textarea>',
			ok_callback: function() {
				var $self = this;
				$self.waitResponse();
				
				$.post('modules/blog/', {
					a: 'cb',
					id: blog_id, 
					title: $('#newblogTitle').attr('value'),
					content: $('#newblogText').tinymce().getContent()
				}, function(data) {
					var aw;
					
					$self.endWait();
					if (aw = GetAnswer(data)) {
						$('#blogadminbar_' + blog_id).after(aw);
						$('#newblogText').tinymce().hide();
						$self.closeDialog();
					}
				});
			},
			close_callback: function() {
				$('#newblogText').tinymce().hide();
			}
		});
		this.tinyfy("newblogText");
	}
	
	this.editEntry = function(el_id, fullview) {
		OpenDialog({
			title: lngBlog.editblog,
			buttons: BTN_SAVECANCEL,
			content: lngBlog.blogtitle+'<br><input style="width:90%;" type="text" id="editblogTitle" value="'+$('#blogElement_'+el_id+' .blogTitle').html()+'"><br><br><textarea style="width:100%" id="editblogText">'+$('#blogElement_'+el_id+' .blogContent').html()+'</textarea>',
			ok_callback: function() {
				var $self = this;

				$self.waitResponse();
				
				$.post('modules/blog/',{a:'ub',id:el_id, title:$('#editblogTitle').attr('value'),content:$('#editblogText').tinymce().getContent()},function(data) {
					var aw;
					
					$self.endWait();
					if (aw = GetAnswer(data)) {
						$('#blogElement_'+el_id+' .blogTitle').html($('#editblogTitle').attr('value'));
						$('#blogElement_'+el_id+' .blogContent').html($('#editblogText').tinymce().getContent());
						
						$('#editblogText').tinymce().hide();
						$self.closeDialog();
					}
				});
			},
			close_callback: function() {
				$('#editblogText').tinymce().hide();
			}
		});
		this.tinyfy("editblogText");
	}
	
	this.deleteEntry = function(el_id, fullview) {
		OpenDialog({
			title: lngBlog.deleteblog,
			content: lngBlog.reallydelete,
			ok_callback: function() {
				var $self = this;
				$.get('modules/blog/',{a:'db',id:el_id},function(data) {
					if(GetAnswer(data)) {
						if(typeof fullview != 'undefined' && fullview==true)
							javascript:history.go(-1);
						else $('#blogElement_'+el_id).remove();
						$self.closeDialog();
					}
				});
			}
		});
	}
	
	this.loadEntry = function(link_elem, el_id, blog_id) {
		if (loadingEntry) return;
		if (link_elem)
			$(link_elem).attr('href','#blog' + blog_id + '/' + el_id);
		
		var aw;
		var loaded=false;
		
		/* Fade out text */
		if(anego.animatePageLoad > 0)
			$('#content').css({opacity: 1.0}).animate({opacity: 0.0}, anego.animatePageLoad, function() {
				if (loaded) putLoadedText(aw);
				loaded = true;
			});

		
		loadingEntry = true;
		$.get('modules/blog/', {a:'le',id:el_id}, function(data) {
			loadingEntry = false;
			var aw;
			if (aw = GetAnswer(data)) {
				putLoadedText(aw);
				loaded=true;
			}
		});
		
		blogEntryLoaded = el_id;
		
		function putLoadedText(str) {
			response = $.parseJSON(str);
			
			//$("#blogc_" + blog_id).html(response.blogs);
			//$("#blognav_" + blog_id).html(response.navigation);

			$("#blogc_" + blog_id).html(str);
			
			// Fade in text
			if(anego.animatePageLoad>0) 
				$('#content').css({opacity: 0.0}).animate({opacity: 1.0}, anego.animatePageLoad);	
				
			Core.curPg = Core.pageInfo('blog/'+el_id);
		}
	};
	
	this.postComment = function(el_id) {
		if($('#commentMail').attr('value').length > 0) {
			alert(lngBlog.leavemailempty);
			return;
		}
		if($('#commentBody').attr('value').length<1) {
			alert(lngBlog.noemptycomment);
			return;
		}
		
		$('#commentButton').attr('disabled','disabled');
		$('#loadingIconSlot').addClass('loadingIcon');
		
		$.post('modules/blog/',{ 
				a: 'wc',
				id: el_id,
				comment: $('#commentBody').attr('value'),
				name: $('#commentName').attr('value')
			}, function(data) {
				var aw,cmts;
				$('#commentButton').attr('disabled','');
				$('#loadingIconSlot').removeClass('loadingIcon');
				if (aw = GetAnswer(data)) {
					// The first line is the comment counter
					cmts = aw.substr(0,aw.indexOf("\n"));
					$('#blogElement_' + el_id + ' .commentCounter').html(cmts);
					$('#blogElement_' + el_id + ' .commentSection').prepend(aw.substr(cmts.length));
					$('#commentBody').attr('value','');
					$('#commentName').attr('value','');
				}
		});
	};
	
	this.deleteComment = function(cmt_id, blog_id) {
		OpenDialog({
			title: lngBlog.deletecmt,
			content: lngBlog.rlydeletecmt,
			ok_callback: function() {
				var $self = this;
				$.get('modules/blog/', {
					a:'dc',
					cmt_id: cmt_id,
					blog_id: blog_id
				},function(data) {
					var aw;
					if (aw = GetAnswer(data)) {
						$('#blogCmt' + cmt_id).remove();
						$('#blogElement_' + blog_id + ' .commentCounter').html(aw);
						$self.closeDialog();
					}
				});
			}
		});
	};
	
	this.tinyfy = function(el_id) {
		var mcelang='en';
		if(anego.language=='ger') /* language var defined by Anego */
			mcelang='de';
		
		$('#' + el_id).tinymce({
			script_url : anego.path + 'lib/tiny_mce/tiny_mce_gzip.php',
			mode : 'none',
			theme : "advanced",	
			plugins : "advimagescale,advlink,contextmenu,paste,inlinepopups,phpimage",
			height : 300,
			theme_advanced_buttons1 : "bold,italic,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect,|,forecolor,backcolor",
			theme_advanced_buttons2 : "pastetext,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,|,charmap,|,hr,removeformat,|,sub,sup,|,phpimage,|,code",
			theme_advanced_buttons3 : "",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,
			advlink_styles: "Spam Protected E-Mail Address=hiddenEmail",
			language : mcelang,
			paste_text_use_dialog: true,
			accessibility_warnings : false,
			button_tile_map : true,
			content_css : anego.path + "styles/" + anego.style + "/text.css", /* style var defined by Anego */
			external_link_list_url : "modules/simpletext/linkList.js.php",
			convert_urls : false
		});
	};
}