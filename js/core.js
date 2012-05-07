//setTimeout(function() { Core.editPage() }, 800);

function __(str) {
	return str;
}

// Todo: Make this configurable
var fancyBoxSettings = {
	'cyclic'		: false,
	'overlayShow'	: true,
	'transitionIn'	: 'elastic',
	'transitionOut'	: 'elastic'
}

/***** Some array extensions *****/

Array.prototype.contains=function(element) {
	for(var i=0; i<this.length; i++) {
		if(this[i]==element) return true;
	}
	return false;
}

Array.prototype.indexof=function(element) {
	for (var i=0; i<this.length; i++) {
		if(this[i]==element) return i;
	}
	return -1;
}

Array.prototype.remove=function(element) {
	var idx = this.indexof(element);
	if(idx!=-1) {
		this.splice(idx,1);
	}
}

Array.prototype.tostring=function() {
	var str = "Array( ";
	for(var i=0; i<this.length; i++) {
		if(i!=0) str+=", ";
		str+=this[i];
	}
	return str+" )";
}

/******* Core functions *******/

Core = new CoreFunctions();

// The page requires js to be loaded, but didn't load it yet (since we don't need it when another page is being loaded via anchor)
if(typeof anego.pageJS != 'undefined') { // && !Core.pageInfo(window.location.hash.substr(1)).valid
	for(var i=0; i<anego.pageJS.length; i++)
		Core.loadJavascript(anego.pageJS[i]);
} else anego.noInit = true;

/* anego.noInit:
 * Following situation: E.g. we have an aloha text editor on the home page (first page), but the user is loading [your domain]#pg20
 * This means aloha should not be loaded. In such cases anego.noIinit is set to true
 */


$(document).ready(function() {
	if (anego.editmode) {
		Core.initPageContentEdit();
	} else {
		Core.initPageContent();
	}
	if (anego.pageLoad == 'ajax')
		Core.ajaxifyMenu();
	
	
	$.postOriginal = $.post;
	$.getOriginal = $.get;
	
	Core.ajaxQueue = [];
	Core.processAjaxQueue = function() {
		if (Core.ajaxQueue.length > 0) {
			var request = Core.ajaxQueue.shift();
			
			$.ajax(request.url, request.settings).always(function() {
				Core.processAjaxQueue();
			});
		}
	}
	
	$.get = function(url, data, success, dataType) {
		if(! url.match(/^http:/))
			url = anego.path + url;
		
		if (typeof data == 'function') {
			dataType = success;
			success = data;
			data = null;
		}

		var request = {
			url: url,
			settings: {
				type: 'GET',
				data: data
			}
		};
		
		if(typeof success != 'undefined')
			request.settings.success = success;
		if(typeof dataType != 'undefined')
			request.settings.dataType = dataType;
		
		Core.ajaxQueue.push(request);
		
		// Only one in the queue? => Start the request
		if (Core.ajaxQueue.length <= 1) {
			Core.processAjaxQueue();
		}
	};
	
	$.post = function(url, data, success, datatype) {
		if (! url.match(/^http:/)) {
			url = anego.path + url;
		}
		
		if (typeof data == 'function') {
			dataType = success;
			success = data;
			data = null;
		}
		
		var request = {
			url: url,
			settings: {
				type: 'POST',
				data: data
			}
		};
		
		if(typeof success != 'undefined')
			request.settings.success = success;
		if(typeof dataType != 'undefined')
			request.settings.dataType = dataType;

		
		Core.ajaxQueue.push(request);
		
		// Only one in the queue? => Start the request
		if (Core.ajaxQueue.length <= 1) {
			Core.processAjaxQueue();
		}
	};
});


function CoreFunctions() {
	/* Ignore the first jquery.history callback */
	var firstLoad = false;
	/* Prevent double loading from the rsh lib */
	var loadingPage=null;
	var that = this;
	var dragdrop = null;
	// Keeps a list of loaded js files (and ignores any loads that are done twice)
	var loadedJsFiles = Array();
	var loadedCSSFiles = Array();
	
	var loadHooks = Array();
	// Core.curPg is splitted object containing usfull infos about the current page
	var curPg;
	
	this.openDialogs = Array();
	this.dialogId = 1;
	this.contentElementModules = null;
	
	this.splitURL = function(url) {
		if(typeof url == "object") return url;
		if(typeof url != "string") return null;
		
		var result = url.split('/');
		
		// #pages/123 or #pages/contact
		result.isPage =  result.length > 1 && (result[0] == 'pages' || result[0] == 'admin');
		
		if(result.isPage) {
			result.pageId = result[1];
		}
		
		result.fullpath = anego.path + '#' + url;
		
		// Remove hashtag
		if(result[0][0] == '#') {
			result[0] = result[0].substr(1);
		}
		
		return result;
	}
	
	this.curPg = this.splitURL(anego.curPg);
	
	this.initPageContent=function() {
		/* Email defuscator tool */
		$('.hiddenEmail').defuscate();
		/* Links to pages on the same site that are made with tinymce/etc. need to be converted */
		if(anego.pageLoad=='ajax') {
			$('#content a').attr('href',function(idx,attr) { 
				if (!attr) return;
				
				var urlaliasRegex = new RegExp('^' + anego.path + '[^/]+$');
				
				if (attr.match(urlaliasRegex) && !$(this).hasClass("nopage")) {
					var pt = new RegExp(anego.path + '(.*)');
					return attr.replace(pt, anego.path + '#pages/$1');
				}

				return attr.replace(/^(pages\/.+)/g, '#$1'); 
			} );
		}
		
		/* Default zoomable picture links */
		this.lightbox($('a.zoomable'));
	}
	
	this.initPageContentEdit=function(data) {
		Core.editPage(Core.curPg.id, data);
	}
	
	/* Fix degraded links for ajax loading */
	this.ajaxifyMenu=function() {
		if(anego.editmode && anego.pageLoad != 'ajax') {
			var replace = function(idx, attr) {
				return attr.replace(/admin-(pg\d+)/g,'admin#$1');
			}
			
			$('.mainnav a').attr('href', replace);
			$(".minornav a").attr('href', replace);
		} else {
			var replace = function(idx, attr) {
				if($(this).hasClass('urlalias')) {
					var pt = new RegExp(anego.path + '(.*)');
					return attr.replace(pt, anego.path + '#pages/$1');
				}

				var pt = new RegExp(anego.path + 'pages/(\\d+)');
				return attr.replace(pt, anego.path + '#pages/$1');
			}
			
			$('.mainnav a').attr('href', replace);
			$(".minornav a").attr('href', replace);
		}
		
		// Ajaxify admin menu
		var adminRegex = new RegExp('^' + anego.path + '(admin/.+)$');
		$('ul.adminnav li a').attr('href', function(idx, attr) {
			return attr.replace(adminRegex,'#$1');
		});
	}
	
	// Changes the language to lang and reloads the page
	this.setLang = function(lang) {
		jQuery.cookie('lang',lang);
		window.location.href = window.location.href.replace(/\?.*/g,'');
	}

	/* Loads a page. Parameter:
	 * url: must be a valid url object like the ones created by splitURL()
	 * settings: {
	 *  beforeContentLoaded: callback when the content has been loaded but not inserted into the page yet
	 *  afterContentLoaded: callback when the page is loaded and content is inserted.
	 *  forceLoad: Ignores some checks to force reload the page on Core.EndEdit()
	*/
	this.loadPage = function(url, settings) {
		if(typeof settings != 'object')
			settings = new Object();
		
		if(typeof url != 'object')
			url = Core.splitURL(url);

		if(! url.isPage) return false;
		/*  RSH calls loadPage() too => don't make requests twice */
		if (loadingPage != null) return false;
		loadingPage = url.fullpath;
		
		//$('#name').html('b');
			
		for(var i=0; i<loadHooks.length; i++)
			if(loadHooks[i](url.fullpath)) {
				loadingPage=null;
				return false;
			}
			
		//$('#name').append('c');
		
		/* Don't load same page (also seems to be buggy in ie if loaded twice).*/
		if(Core.curPg && url.fullpath == Core.curPg.fullpath && (typeof settings.forceLoad == 'undefined' || settings.forceLoad == false)) {
			loadingPage = null;
			return false;
		}
		
		//$('#name').append('d');
		
		/* If we have loaded a non-ajax loaded page (domain.com/pg23) and try to load a page with ajax (domain.com/#pg23)
		 * The browser will load a new site because he sees it as a different file, hence our GET request fails with an
		 * empty error message. So: Don't load pages with ajax in such cases.
		*/
		if(location.pathname[location.pathname.length-1] != '/' && (typeof settings.forceLoad == 'undefined' || settings.forceLoad == false)) {
			loadingPage=null;
			return false;
		}
		
		//$('#name').append('e');

		var file='index.php';
		var get;
		var pgId;
		
		switch(url[0]) {
			case 'admin':
				file = 'admin.php';
				get = { a: url[1], noheader: 1 };
				$('#pageEditLink').parent().css('display','none');
				
				if(anego.editmode) {
					Core.endEdit({ ignorePage: true });
				}
				
				this.selectPageInMenu(null); // Unselect selected page
				break;
				
			case 'pages':
				if($('#pageEditLink').length == 0) {
					$('ul.adminnav').prepend('<li><a href="javascript:Core.editPage()" id="pageEditLink">' + lngMain.edit_page + '</a></li>');
				} else {
					$('#pageEditLink').parent().css('display','');
				}
				
				this.selectPageInMenu(url);
				
				get = { a: 'p', p: url.pageId };
				
				//if (anego.editmode)
				//	get = { a: 'gce', fgx: url.pageId };
					
				if (settings.updatePage)
					get.updatePage = 1;
				else
					get.updatePage = 0;
				
				break;
					
			default:
				loadingPage = null;
				return false;	
		}
		
		//$('#name').append('f');
		
		var animated = false,loaded = false;
		var aw;	
		var xdf = 0;
		
		if (anego.animatePageLoad == 0) {
			animated = true;
		} else {
			$('#content')
				.css({opacity: 1.0})
				.animate({opacity: 0.0}, anego.animatePageLoad, function() {
					if (loaded && !animated) 
						putLoadedText(aw);
					animated=true;
				});
		}
		
		// Retrieve the actual page
		$.get(file, get, function(data) {
			if (aw = GetAnswer(data)) {
				if (animated) putLoadedText(aw);
				loaded=true;

				if(settings.ok_callback) 
					settings.ok_callback(aw);
			} else {
				if(settings.fail_callback) 
					settings.fail_callback(aw);
			}
		});

		function putLoadedText(aw) {
			var data = jQuery.parseJSON(aw);

			if(typeof settings.beforeContentLoaded != 'undefined')
				settings.beforeContentLoaded(data);
			
			/* Set title */
			document.title = data.title;
			
			Core.loadJSONResult(data);
			
			/* Place the content */
			if(anego.editmode) { // from admin.php
				$('#content').html(data.content);
				that.initPageContentEdit(data);
			} else {             // from index.php (ajax.php)
				$('#content').html(data.content);
				that.initPageContent();
			}
			
			if(anego.animatePageLoad>0) 
				$('#content').css({opacity: 0.0}).animate({opacity: 1.0}, anego.animatePageLoad);
				
			Core.curPg = url;
			Core.curPg.pageId = data.pageId;
			
			loadingPage=null;
			
			// Callback function from loadPage() parameter
			if(typeof settings.afterContentLoaded != 'undefined')
				settings.afterContentLoaded(data);
		}
		
		return true;
	}
	
	/* Load javascript and css files, if the data object contains data.js or data.css */
	this.loadJSONResult = function(data) {
		if(typeof data.js != 'undefined') {
			if(typeof data.js == 'object')
				for(var i=0; i<data.js.length; i++) 
					Core.loadJavascript(data.js[i].replace('%lng',anego.language));
			if(typeof data.js == 'string')
				Core.loadJavascript(data.js.replace('%lng',anego.language));
		}
		
		if(typeof data.css != 'undefined') {
			if(typeof data.css == 'object')
				for(var i=0; i<data.css.length; i++) 
					Core.CSS(data.css[i]);
			if(typeof data.css == 'string')
				Core.loadCSS(data.css);
		}
	}
	
	/* Overwrite this method if needed */
	// Adds the correct "selected" css classes and shows menuitems where needed
	this.selectPageInMenu = function(page) {
		if(anego.submenuStyle == 'visible') return;
		
		var el = null;
		if(page != null) {
			el = $('.anegoNav li a[href="' + page.fullpath + '"]').parent();
		}
		
		// If this is a child of a child we just need to make sure its visible
		if(el != null && el.hasClass('subsubitem')) {
			el.show();
			// Make sure subnav list is visible
			el.parent().parent().removeClass('hidden');
			return;
		}
		
		// Deselect old page, unless its always visible
		if(anego.submenuStyle != 'submenu onselect' || $('.anegoNav li.navSelected').parents('ul').length > 2 ) {
			$('.anegoNav li.navSelected .subnavbox').addClass('hidden');
		}
		
		// Remove current selection(s)
		$('.anegoNav li.navSelected div.subnavbox, .anegoNav li.childSelected div.subnavbox,')
			.addClass('hidden');
		$('.anegoNav li.navSelected, .anegoNav li.childSelected')
			.removeClass('navSelected')
			.removeClass('childSelected');
		
		
		// No page select => we just unselect the current page
		if(page == null) return;
		
		// If a sub page is clicked, leave submenu open and parent menu selected
		if(el.parents().hasClass('subnavlist')) {
			// Remove other subpages selection
			el.parents().find('li.navSelected').removeClass('navSelected');
			// Menu selected, add class to the. <li>
			el.parent().addClass('navSelected');
			// If this page again has children, show them
			el.children('div.subnavbox').removeClass('hidden');
			// Make sure subnav list is visible
			el.parents('.subnavbox').removeClass('hidden');
			// Make sure parent element is selected
			el.parents('.navParent').addClass('childSelected');
		} else {
			// Select new page
			// jQuery rocks. Seriously.
			$('.anegoNav li a[href="' + page.fullpath + '"]').parents('li').first().addClass('navSelected');
			$('.anegoNav li.navSelected .subnavbox').removeClass('hidden');
		}
	}
	
	/* Opens the page editing interface */
	// Parameter: (optional) page: (int)
	//			  (optional) data: already received edit page data (will skip get request)
	this.editPage = function(page, data) {
		var aw;
		
		if (!page) {
			page = Core.curPg.id;
		}
		if (!data) {
			data = Core.contentElementModules;
		}
		
		anego.editmode = true;

		/* Dragdrop initialized -> only reinitalize for the contents */
		if (Core.dragdrop) {
			Core.dragdrop.preparePage();
			return;
		}
		
		if (typeof DragDropElements == "undefined") 
			Core.loadJavascript('ld.ap.ad' + anego.language);
		
		$(document).ready(function() {
			$('#pageEditLink').html(lngMain.doneedit_page);
			$('#pageEditLink').attr('href','javascript:Core.endEdit()');
			
			// Todo: Code this a bit cleaner
			var initEdit = function(data) {
				Core.dragdrop = new DragDropElements({
					container: "#content",
					modules: data.modules,
					onMove: function(ret, mPos) {
						$.get('index.php', {
							a: 'mce',
							mid: ret.module_id,
							elid: ret.elem_id,
							newpos: mPos
						}, function(data) {
							if(aw=GetAnswer(data)) {
								// ok
							} else {
								// undo all
							}
						});
					}
				});
				Core.dragdrop.init();
				Core.contentElementModules = data;
			};
			
			if (! data) {
				$.get("index.php?a=gce&fgx=" + page, function(data) {
					if (aw = GetAnswer(data)) {
						data = jQuery.parseJSON(aw);
						// Loads js & css files associated with this response
						Core.loadJSONResult(data);
						initEdit(data);
					}
				});
			} else {
				initEdit(data);
			}
		});
	}

	// This function is being called by the RSH library which tracks events where the user presses back and forward on his browser
	this.historyChange=function(newLocation) {
		if(! newLocation && !firstLoad) return firstLoad=true;
		firstLoad = true;
		
		var url = Core.splitURL(newLocation);

		if(url[0] == 'admin') {
			Core.loadPage(url);
			// We'll call the load page hooks in loadpage
			return;
		} else {
			if(url.isPage) {
				Core.loadPage(url);
				// We'll call the load page hooks in loadpage
				return;
			}
		}
		
		for(var i=0; i < loadHooks.length; i++)
			loadHooks[i](url);
	}
	
	// Through this function, custom page loading events can be implemented
	// If added function returns true, Core.loadPage() will only call the hook and then exit
	// Be really careful when calling loadpage inside a hook function, 
	// as it will fire the loadPage event again, so make sure not to fall in a endless loop
	this.addloadPageHook=function(fn) {
		loadHooks.push(fn);
	}

	// Loads a javascript file dynamically by adding it to the documents <head>
	// May also be a js module (ld.am); avoids also more or less duplicate loading of jsfiles / modules
	this.loadJavascript=function(file) {
		if(loadedJsFiles.contains(file)) return;
		// Split javacript loader files, check them individually if already loaded
		if(file.substr(0,3)=='ld.') {
			file=file.substr(3).split('.');
			var toLoad=Array();
			for(var i=0; i<file.length; i++)
				if(!loadedJsFiles.contains(file[i]))
					toLoad.push(file[i]);
			
			if(toLoad.length>0)
				$('head').append('<script type="text/javascript" src="' + anego.path + 'ld.' + toLoad.join('.') + '"></script>');
			
			loadedJsFiles=loadedJsFiles.concat(toLoad);
		} else {
			$('head').append('<script type="text/javascript" src="' + anego.path + file+'"></script>');
			loadedJsFiles[loadedJsFiles.length]=file;
		}
	}
	
	// Loads given css file
	this.loadCSS = function(file) {
		if(loadedCSSFiles.contains(file)) return;
		
		$('head').append('<link rel="stylesheet" href="' + anego.path + file + '" type="text/css" media="screen">');	
		loadedCSSFiles.push(file);
	}
	
	// Reads a get parameter from the current url - previously gup
	this.GETvar = function( name ) {
		name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
		var regexS = "[\\?&]"+name+"=([^&#]*)";
		var regex = new RegExp( regexS );
		var results = regex.exec( utf8_decode(decodeURIComponent(window.location.href)));
		if( results == null )
			return "";
		else
			return results[1];
	}
	
	this.lightbox = function(selector) {
		$(selector).fancybox(fancyBoxSettings);
	}

	/* ajax post request, similar to $.post, but allows multiple simultaneous requests. Should only be used when its feature is really needed */
	this.postData = function(data, url, callback,timeoutcallback) {
		var req = createXHR();
		var xmlHttpTimeout;
		
		/* Inner function to bind handler and create closure */
		function xhrCallback()  {
			if(req.readyState == 4) {
				clearTimeout(xmlHttpTimeout); 
				 if(req.status == 200) {
						//alert(req.responseText);
						callback(req);
				 } else
					if(req.status!=0)
						alert("Sorry. Did not work. Error code " + req.status);
			}
		}

		if(req) {
			req.onreadystatechange = xhrCallback;	
			req.open("POST", url, true);
			req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			req.setRequestHeader("X-Requested-With", "XMLHttpRequest");
			req.send(data);
		
			// Timeout to abort in 5 seconds
			xmlHttpTimeout=setTimeout(
				function() {
					req.abort();
					if(timeoutcallback)
						timeoutcallback();
					else alert("Request timed out, please try again later");
			}, 5000);
			
		} else alert('Cannot create a AJAX Request Object - to old browser?');
	}
}

/* Returns the class name of the argument or undefined if
   it's not a valid JavaScript object.
*/
function getObjectClass(obj) {
    if (obj && obj.constructor && obj.constructor.toString) {
        var arr = obj.constructor.toString().match(
            /function\s*(\w+)/);

        if (arr && arr.length == 2) {
            return arr[1];
        }
    }

    return undefined;
}


/***************** Dialog code **********************/
var BTN_OKCANCEL = 1;
var BTN_YESNO = 2;
var BTN_CLOSE = 3;
var BTN_SAVECANCEL = 4;
var BTN_NONE = 5;

/* Opens a dialog. Possible settings:
	title:			The dialog title
	content:		The actual html content of the dialog, this may be a jquery object 
	left:			x coordinate of the window
	top:			y coordinate of the window, if x&y not defined it will be centered and the position will be remembered in a cookie
	width:			dialog width in pixel (default: autosize)
	height:			dialog height in pixel (default: autosize)
	buttons:		Number: BTN_YESNO, BTN_OKCANCEL, BTN_SAVECANCEL, BTN_CLOSE or BTN_NONE (default: BTN_OKCANCEL)
					Object: Custom defined buttons with their callbacks
	blocking:		false if you want the dialog to be non blocking (= user can still interact with the page) (default: true)
	ok_callback:	function to be called when the user pressed Ok/Yes
	close_callback:	function to be called when the user pressed Cancel,No or Close
	autocollapse:	If true, minimizes the dialog when out of focus (default: false)
*/
/* Todo: Refactor this into a jquery plugin. */
function OpenDialog(settings) {
	var w='', h='';
	
	/*** Dialog behavior ***/
	if (settings.collapse == undefined) settings.collapse = false;
	if (settings.blocking == undefined) settings.blocking = true;
	
	if (settings.width != undefined) 
		w = 'width: ' + settings.width + 'px; ';
	if (settings.height != undefined) 
		h = 'height: ' + settings.height + 'px; ';
	
	if ($("#inactive").length == 0)
		$('body').append('<div id="inactive" style="display:none"></div>');
	
	$("#inactive").css('display','');

	/*** Dialog HTML ***/
	var str = 
		'<div class="dlgBox" class="adminstyles" style="' + w + h + '">' +
			'<div class="dlgTitle">' + 
				settings.title + 
				'<div class="dlgXBtn dlgBtn">X</div>' + 
				'<div class="dlgMBtn dlgBtn">_</div>' + 
			'</div>' + 
			'<hr class="dlgSep">' +
			'<div class="dlgContent">' + 
				/* Content goes here */
				'<div class="dlgBtnContainer adminstyles">' +
					'<img src="' + anego.path + 'styles/default/img/cleardot.gif" class="loadingIcon"> ' +
					//buttons +
				'</div>' +
			'</div>' +
		'</div>';

	var $dlgBox = $(str);

	/*** Button set up ***/
	var btn1 = lng_ok, btn2 = lng_cancel;
	
	switch (settings.buttons) {
		case BTN_YESNO: 
			btn1=lng_yes;
			btn2=lng_no;
			break;
		
		case BTN_SAVECANCEL: 
			btn1=lng_save;
			break;

		case BTN_CLOSE:
			btn2=lng_close;
			break;	
	}
	
	/* Construct the buttons */
	var $buttons = $('<span></span>');
	
	if (typeof settings.buttons == "object") {
		$.each(settings.buttons, function(name, callback) {
			var $btn = $('<input type="button" class="dlgButton" value="' + name + '"> ');
			// Weird encapsulation thingy to have the correct 'this' in the callback
			if(callback) {
				$dlgBox['button_cb'+name] = callback; 
				$btn.click({ name: name }, function(e) { $dlgBox['button_cb' + e.data.name]() });
			} 
			i++;
			$buttons.append($btn);
		});
	} else {
		if (settings.buttons != BTN_NONE) {
			if (settings.buttons != BTN_CLOSE)
				$buttons.append('<input type="button" class="dlgOK" value="' + btn1 + '"> ');
			$buttons.append('<input type="button" class="dlgCancel" value="' + btn2 + '">');
		}
	}

	$('.dlgContent', $dlgBox).prepend(settings.content);
	$('.dlgBtnContainer', $dlgBox).append($buttons);

	// Make dialog visible
	$("#inactive").append($dlgBox);
	
	// Focus the first field
	$('input').first().focus();
	
	/* settings.blocking defines wether the user is still 
	 * allowed to interact with the site or not (blocking or non blocking dialog) 
	 */
	$('#inactive').toggleClass('blocking', settings.blocking);
	
	
	/* Get previously saved position if it is set and no custom position supplied, but limit to viewable area  */
	if (settings.top == undefined && settings.left == undefined) {
		if (localStorage.getItem("anego_dlg_" + settings.title + "_left") != null) {
			settings.left = BoundBy(localStorage.getItem("anego_dlg_" + settings.title + "_left"), 0, f_clientWidth() - $('#dlgBox').width());
			settings.top  = BoundBy(localStorage.getItem("anego_dlg_" + settings.title + "_top"), 0, f_clientHeight() - $('#dlgBox').height());
		}
	}
	
	/* Position element if any coordinate is set */
	if (settings.top != undefined || settings.left != undefined) {
		if (settings.top == undefined) settings.top = 0;
		if (settings.left == undefined) settings.left = 0;
		
		$dlgBox.css('top', settings.top);
		$dlgBox.css('left', settings.left);
	} else {
		$dlgBox.css('top', (window.innerHeight/3) + 'px');
		$dlgBox.css('left', (window.innerWidth/2 - $dlgBox.width()) + 'px');
	}
	
	
	/* Helper methods */ 
	
	$dlgBox.closeDialog = function() {
		var unblock = true;
		
		if(Core.openDialogs.length == 0)
			document.onkeydown = null;
		
		for(var i=0; i < Core.openDialogs.length; i++) {
			if (Core.openDialogs[i].dialogSettings.blocking && Core.openDialogs[i].dialogId != this.dialogId) {
				unblock = false;
			}
			
			if (Core.openDialogs[i].dialogId == this.dialogId) {
				Core.openDialogs.splice(i,1);
			}
		}
		
		if(unblock) {
			$('#inactive').removeClass('blocking').hide();
		}
		
		this.remove();
	};
	
	// Disables dialog buttons and shows a ajax loading icon
	$dlgBox.waitResponse = function() {
		$('input[type=button]', $dlgBox).attr('disabled','disabled');
		$('.dlgBtnContainer .loadingIcon', $dlgBox).show();
	};
	
	// Resets changes from waitResponse()
	$dlgBox.endWait = function() {
		$('input[type=button]', $dlgBox).removeAttr('disabled');
		$('.dlgBtnContainer .loadingIcon', $dlgBox).hide();
	};
	
	// Store some metadata about the dialog in the jquery object
	$dlgBox.dialogSettings = settings;
	$dlgBox.dialogId = Core.dialogId++;
	$dlgBox.ok_callback = settings.ok_callback;
	$dlgBox.close_callback = settings.close_callback;
	
	Core.openDialogs.push($dlgBox);

	SetupEvents();
	
	// We're done here
	return $dlgBox;
	
	
	/* All events related to the dialog */
	function SetupEvents() {
		var dx=0, dy=0;
		var mouseDown = 0;
		
		/* Dialog collapse expand */
		var expand = function() {
			if(settings.height == undefined) {
				$dlgBox.css('height', 'auto');
			} else {
				$dlgBox.css('height', settings.height + 'px');
			}
			
			$dlgBox.css('backgroundColor', boxColor);
			$('.dlgTitle', $dlgBox).css('backgroundColor', headerColor);
			$('.dlgContent', $dlgBox).show();
			$('.dlgSep', $dlgBox).show();
			
			jQuery.cookie('dialogCollapseState-' + settings.title, false);
		};
		
		var collapse = function() {
			$dlgBox.css('height', '21px');
			$dlgBox.css('backgroundColor', headerColor);
			$('.dlgTitle', $dlgBox).css('backgroundColor','transparent');
			$('.dlgContent', $dlgBox).hide();
			$('.dlgSep', $dlgBox).hide();
			
			jQuery.cookie('dialogCollapseState-' + settings.title, true);
		};

		/* Button callbacks */
		if($dlgBox.ok_callback) {
			$('.dlgOK', $dlgBox).click(function() {
				$dlgBox.ok_callback();
			});
		}

		$('.dlgCancel', $dlgBox).click(function() {
			$dlgBox.closeDialog();
			if($dlgBox.close_callback != undefined)
				$dlgBox.close_callback();
		});

		$('.dlgXBtn', $dlgBox).click(function() {
			$dlgBox.closeDialog();
			if($dlgBox.close_callback != undefined)
				$dlgBox.close_callback();
		});
		
		$('.dlgMBtn', $dlgBox).click(function() {
			settings.collapse = !settings.collapse;
			if(settings.collapse) {
				$('.dlgMBtn', $dlgBox).html('□');
				collapse();
			} else {
				$('.dlgMBtn', $dlgBox).html('_');
				expand();
			}
		});
		
		/* Drag and Drop functionality */
		
		$('.dlgTitle', $dlgBox).mousedown(function(event) {
			mouseDown = 1;
			$dlgBox.css('margin', '0');
			dx = event.pageX - $dlgBox.css('left').substr(0, $dlgBox.css('left').length - 2);
			dy = event.pageY - $dlgBox.css('top').substr(0, $dlgBox.css('top').length - 2);
			return false;
		}); 
	
		$(document).mouseup(function(event) {
			if(mouseDown) {
				localStorage.setItem("anego_dlg_" + settings.title + "_left", $dlgBox.css('left').substr(0, $dlgBox.css('left').length - 2));
				localStorage.setItem("anego_dlg_" + settings.title + "_top", $dlgBox.css('top').substr(0, $dlgBox.css('top').length - 2));
			}
			mouseDown = 0;
		});
		
		$(document).mousemove(function(event) {
			if(mouseDown) {
				$dlgBox.css('top', BoundBy(event.pageY - dy,3, $(document).height() - $dlgBox.height() - 3) + 'px');
				$dlgBox.css('left', BoundBy(event.pageX - dx,3, $(document).width() - $dlgBox.width() - 3) + 'px'); 
			}
		});	
		
		var boxColor = $dlgBox.css('backgroundColor');
		var headerColor = $('.dlgTitle', $dlgBox).css('backgroundColor');
		
		if (jQuery.cookie('dialogCollapseState-' + settings.title)) {
			$('.dlgMBtn', $dlgBox).trigger('click');
		}

		/* Autocollapse feature */
		if(settings.autocollapse) {
			$dlgBox.mouseover(collapse);
			$dlgBox.mouseout(expand);
		}
		
		if(settings.nohotkeys) return;
		
		// Keyboard interaction support (Esc and Enter)
		if( !document.onkeydown) {
			document.onkeydown = function(event) {
				// escape: 27
				// enter: 13
				if (!event) {
					event = window.event;
				}
				
				// Dispatch these to the currently focused dialog or to the last opened one
				var $dlg = null;
				for (var i=0; i < Core.openDialogs.length; i++) {
					$dlg = Core.openDialogs[i];
					if ($dlg.is(':focus')) break;
				}
				if (!$dlg) return;

				if (event.keyCode == 27 || ($dlg.dialogSettings.buttons == BTN_CLOSE && event.keyCode == 13)) {
					if($dlg.close_callback != undefined) {
						$dlg.close_callback();
					}
					$dlg.closeDialog();
				} else {
					if (event.keyCode==13 && $dlg.ok_callback != undefined) {
						$dlg.ok_callback();
					}
				}
			};
		}
	}
}

function BoundBy(x, minx, maxx) {
	return Math.min(maxx,Math.max(x,minx));
}

/* Most AJAX requests reply a 3 digit number at the beginning. 
 * If it is 200 it means the request was successfull, anything
 * else than that means something went wrong. Though the exact type of the
 * returned number has no impact at all, it is currently loosely oriented
 * at http error codes where 3xx denote permission errors, 5xx internal errors, and 4xx not found errors.
 * Todo: Every occurrence of GetAnswer() should ideally be replaced with JSON responses + $.loadJSON() 
 * and non-2xx error numbers should be given some actual meaning
 */
function GetAnswer(text) {
	// If the response data is a json object, then the error code is stored in the status property
	if (typeof text == "object") {
		if (text.status.substr(0,3) != '200') {
			alert(text.substr(4));
			return null;
		}
		
		return text;
	}
	
	if (text.substr(0,3) != '200') {
		alert(text.substr(4));
		return null;
	}
	
	return text.substr(4);
}

function urlencode(str) {
	str = encodeURIComponent(str);
	str =str.replace(/'/g,"%27")
	return str;
}

// method for UTF-8 encoding
function utf8_encode(string) {
	string = string.replace(/\r\n/g,"\n");
	var utftext = "";

	for (var n = 0; n < string.length; n++) {

		var c = string.charCodeAt(n);

		if (c < 128) {
			utftext += String.fromCharCode(c);
		}
		else if((c > 127) && (c < 2048)) {
			utftext += String.fromCharCode((c >> 6) | 192);
			utftext += String.fromCharCode((c & 63) | 128);
		}
		else {
			utftext += String.fromCharCode((c >> 12) | 224);
			utftext += String.fromCharCode(((c >> 6) & 63) | 128);
			utftext += String.fromCharCode((c & 63) | 128);
		}

	}

	return utftext;
}
 
// method for UTF-8 decoding
function utf8_decode(utftext) {
	var string = "";
	var i = 0;
	var c = c1 = c2 = 0;

	while ( i < utftext.length ) {

		c = utftext.charCodeAt(i);

		if (c < 128) {
			string += String.fromCharCode(c);
			i++;
		}
		else if((c > 191) && (c < 224)) {
			c2 = utftext.charCodeAt(i+1);
			string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
			i += 2;
		}
		else {
			c2 = utftext.charCodeAt(i+1);
			c3 = utftext.charCodeAt(i+2);
			string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
			i += 3;
		}

	}

	return string;
}

function createXHR() {
    var xhrObj;
    if (window.XMLHttpRequest) {
        // branch for native XMLHttpRequest object - Mozilla, IE7
        try {
            xhrObj = new XMLHttpRequest();
        } catch (e) {
            xhrObj = null;
        }
    } else if (window.createRequest) {
        try {
            xhrObj = window.createRequest();
        }
        catch (e) {
            xhrObj = null;
        }
    } else if (window.ActiveXObject) {
        // branch for IE/Windows ActiveX version
        try {
            xhrObj = new ActiveXObject("Msxml2.XMLHTTP");
        } catch(e) {
            try{
                xhrObj = new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch(e) {
                xhrObj = null;
            }
        }
    }
    return xhrObj;
}

// Todo: Can these 4 functions be factored away through use of their respective jQuery alternatives?
function f_scrollTop() {
	return f_filterResults (
		window.pageYOffset ? window.pageYOffset : 0,
		document.documentElement ? document.documentElement.scrollTop : 0,
		document.body ? document.body.scrollTop : 0
	);
}

function f_scrollLeft() {
	return f_filterResults (
		window.pageXOffset ? window.pageXOffset : 0,
		document.documentElement ? document.documentElement.scrollLeft : 0,
		document.body ? document.body.scrollLeft : 0
	);
}

function f_clientWidth() {
	return f_filterResults (
		window.innerWidth ? window.innerWidth : 0,
		document.documentElement ? document.documentElement.clientWidth : 0,
		document.body ? document.body.clientWidth : 0
	);
}
function f_clientHeight() {
	return f_filterResults (
		window.innerHeight ? window.innerHeight : 0,
		document.documentElement ? document.documentElement.clientHeight : 0,
		document.body ? document.body.clientHeight : 0
	);
}

function f_filterResults(n_win, n_docel, n_body) {
	var n_result = n_win ? n_win : 0;
	if (n_docel && (!n_result || (n_result > n_docel)))
		n_result = n_docel;
	return n_body && (!n_result || (n_result > n_body)) ? n_body : n_result;
}

/*
 * Email Defuscator - jQuery plugin 1.0-beta2
 *
 * Copyright (c) 2007 Joakim Stai
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Revision: $Id$
 *
 */

/**
 * Converts obfuscated email addresses into normal, working email addresses.
 *
 * @name defuscate
 * @param Boolean link If true, all defuscated email addresses will be turned into links, defaults to true (optional)
 * @param String find The regular expression used to search for obfuscated email addresses (optional)
 * @param String replace Replacement text for defuscating email addresses (optional)
 * @descr Converts obfuscated email addresses into normal, working email addresses
 */

jQuery.fn.defuscate = function( settings ) {
    settings = jQuery.extend({
        link: true,
        find: /\b([A-Z0-9._%-]+)\([^)]+\)((?:[A-Z0-9-]+\.)+[A-Z]{2,6})\b/gi,
        replace: '$1@$2'
    }, settings);
    return this.each(function() {
        if ( $(this).is('a[@href]') ) {
            $(this).attr('href', $(this).attr('href').replace(settings.find, settings.replace));
			$(this).html($(this).attr('href').substr(7));
            var is_link = true;
        }
        $(this).html($(this).html().replace(settings.find, (settings.link && !is_link ? '<a href="mailto:' + settings.replace + '">' + settings.replace + '</a>' : settings.replace)));
    });
};

/**
 * jQuery Cookie plugin
 *
 * Copyright (c) 2010 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Author: Klaus Hartl 
 * See also: http://plugins.jquery.com/project/Cookie
 */

jQuery.cookie = function (key, value, options) {
    
    // key and at least value given, set cookie...
    if (arguments.length > 1 && String(value) !== "[object Object]") {
        options = jQuery.extend({}, options);

        if (value === null || value === undefined) {
            options.expires = -1;
        }

        if (typeof options.expires === 'number') {
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }
        
        value = String(value);
        
        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? value : encodeURIComponent(value),
            options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }

    // key and possibly options given, get cookie...
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};

/* Simple JavaScript Inheritance
 * By John Resig http://ejohn.org/
 * MIT Licensed.
 */
// Inspired by base2 and Prototype
(function(){
  var initializing = false, fnTest = /xyz/.test(function(){xyz;}) ? /\b_super\b/ : /.*/;
  // The base Class implementation (does nothing)
  this.Class = function(){};
  
  // Create a new Class that inherits from this class
  Class.extend = function(prop) {
    var _super = this.prototype;
    
    // Instantiate a base class (but only create the instance,
    // don't run the init constructor)
    initializing = true;
    var prototype = new this();
    initializing = false;
    
    // Copy the properties over onto the new prototype
    for (var name in prop) {
      // Check if we're overwriting an existing function
      prototype[name] = typeof prop[name] == "function" && 
        typeof _super[name] == "function" && fnTest.test(prop[name]) ?
        (function(name, fn){
          return function() {
            var tmp = this._super;
            
            // Add a new ._super() method that is the same method
            // but on the super-class
            this._super = _super[name];
            
            // The method only need to be bound temporarily, so we
            // remove it when we're done executing
            var ret = fn.apply(this, arguments);        
            this._super = tmp;
            
            return ret;
          };
        })(name, prop[name]) :
        prop[name];
    }
    
    // The dummy class constructor
    function Class() {
      // All construction is actually done in the init method
      if ( !initializing && this.init )
        this.init.apply(this, arguments);
    }
    
    // Populate our constructed prototype object
    Class.prototype = prototype;
    
    // Enforce the constructor to be what we expect
    Class.prototype.constructor = Class;

    // And make this class extendable
    Class.extend = arguments.callee;
    
    return Class;
  };
})();


/* ContentElement base class
 * This class takes over element creation, deleting and basic editing handling.
 * You should however implement onStartEdit at least for your module.
 * Todo: When ajax call to create/delete element fails: undo everthing 
 */
var ContentElement = Class.extend({
	init: function(module_id, page_id, element_id) {
		this.module_id = module_id;
		this.page_id = page_id;
		this.element_id = element_id;
		this.hideMiniToolbar = false;
		this.editing = false;
		this.isNew = element_id == undefined;
		// Don't store the jQuery object - drag&drop might invalidate it
		if(! this.isNew)
			this.containerId = module_id + "_" + element_id;
		// init call for children classes, dont use original init.
		if(this.onInit) this.onInit();
	},
	
	createElement: function(container, position, successCallback, endeditCallback) {
		var self = this;
		
		$.get('index.php', {
			a: 'cce',
			mid: this.module_id,
			page_id: this.page_id,
			pos: position
		}, function(data) {
			if(aw = GetAnswer(data)) {
				var data = jQuery.parseJSON(aw);
				// Place element and call the creation callback function
				container.html(data.html);
				container.attr('id',self.module_id + "_" + data.id);
				self.containerId = container.attr('id');
				self.element_id = data.id;
				self.startEdit({newlyCreated: true, onEndEdit: endeditCallback});
				
				successCallback(self.module_id + "_" + data.id);
			}
		});
	},
	
	startEdit: function(options) {
		if(this.onStartEdit(options.newlyCreated)) {
			this.endEditCallback = options.onEndEdit;
			this.editing = true;
			$('#'+this.containerId).removeClass('ceDraggable');
			$('#'+this.containerId).addClass('ceEditing');
			this.hideMiniToolbar = true;
			return true;
		}
		return false;
	},
	
	endEdit: function() {
		if(this.onEndEdit()) {
			$('#'+this.containerId).addClass('ceDraggable');
			$('#'+this.containerId).removeClass('ceEditing');
			this.editing = false;
			this.hideMiniToolbar = false;
			
			if(this.endEditCallback) this.endEditCallback($('#'+this.containerId));
			
			return true;
		}
		return false;
	},
	deleteElement: function(callback) {
		$.get('index.php', {
			a: 'delce',
			pid: this.page_id,
			mid: this.module_id,
			elid: this.element_id
		}, function(data) {
			if(aw = GetAnswer(data)) {
				callback();
			}
		});
	},
	getHideMiniToolbar: function() {
		return this.hideMiniToolbar;
	}
});

// jPaginate Plugin for jQuery - Version 0.3
// by Angel Grablev for Enavu Web Development network (enavu.com)
// Dual license under MIT and GPL :) enjoy
eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('(w($){$.1o.1O=w(s){3 t={8:4,Z:"1q",X:"1Q",y:"y",1k:"G",1i:1h,O:6,1e:V,U:"N",11:1h,18:1p};3 s=$.1P(t,s);S I.16(w(){x=$(I);3 k=s.8;3 l=x.R().1E();3 m=C.17(l/k);3 n=[];3 o=0;3 p=k;3 q=0;3 r=0;1l(i=1;i<=m;i++){n[i]=x.R().1a(o,p);5(s.11){x.R().1a(o,p).16(w(){q+=$(I).1M()});5(q>r)r=q;q=0}o+=k;p+=k}5(s.11){r+=s.18;x.1N({"1C":r})}5(s.1e==V){5(L("z")){D(L("z"));E(L("z"))}9{Q("z","1");D(L("z"));E(L("z"))}}9{D(1);E(1)}w D(a){x.R().1r();n[a].1x()}w E(a){3 b,8="",B,A="";b="<1d v=\'"+s.1k+"\'>";3 c="<7><a v=\'1g\' F=\'#\'>"+s.X+"</a></7>";3 d="<7><a v=\'1j\' F=\'#\'>"+s.Z+"</a></7>";3 e="<7><a v=\'1n\'>"+s.X+"</a></7>";3 f="<7><a v=\'1n\'>"+s.Z+"</a></7>";B="</1d><1F 1K=\'1L\' />";3 g=m-s.N+1;3 h=19(a);1l(i=1;i<=m;i++){5(s.1i==V){3 j=C.17(m/2);5(i>=h.1b&&i<=h.B){5(i==a){8+=\'<7><a v="\'+s.y+\'" u="\'+i+\'">\'+i+\'</a></7>\'}9{8+=\'<7><a F="#" v="P" u="\'+i+\'">\'+i+\'</a></7>\'}}9 5(a<=j){5(i>=(m-2)){5(i==a){8+=\'<7><a v="\'+s.y+\'" u="\'+i+\'">\'+i+\'</a></7>\'}9{8+=\'<7><a F="#" v="P" u="\'+i+\'">\'+i+\'</a></7>\'}}}9 5(a>=j){5(i<=2){5(i==a){8+=\'<7><a v="\'+s.y+\'" u="\'+i+\'">\'+i+\'</a></7>\'}9{8+=\'<7><a F="#" v="P" u="\'+i+\'">\'+i+\'</a></7>\'}}}}9{5(i==a){8+=\'<7><a v="\'+s.y+\'" u="\'+i+\'">\'+i+\'</a></7>\'}9{8+=\'<7><a F="#" v="P" u="\'+i+\'">\'+i+\'</a></7>\'}}}5(a!=1&&a!=m){A=b+c+8+d+B}9 5(m==1){A=b+e+8+f+B}9 5(a==m){A=b+c+8+f+B}9 5(a==1){A=b+e+8+d+B}5(s.U=="Y"){x.Y(A)}9 5(s.U=="N"){x.N(A)}9{x.N(A);x.Y(A)}}w Q(a,b){3 c=1s;3 d=1t 1u();d.1v(d.1w()+c);H.J=a+"="+1y(b)+((c==1z)?"":";1A="+d.1B())}w L(a){5(H.J.10>0){K=H.J.1f(a+"=");5(K!=-1){K=K+a.10+1;T=H.J.1f(";",K);5(T==-1)T=H.J.10;S 1G(H.J.1H(K,T))}}S""}w 19(a){3 b=C.1I(s.O/2);3 c=m-s.O;3 d=a>b?C.1J(C.12(a-b,c),0):0;3 e=a>b?C.12(a+b+(s.O%2),m):C.12(s.O,m);S{1b:d,B:e}}$(".P").13("14",w(e){e.15();D($(I).M("u"));Q("z",$(I).M("u"));$(".G").W();E($(I).M("u"))});$(".1j").13("14",w(e){e.15();3 a="."+s.y;3 b=1m($(".G").1c(".y").M("u"))+1;Q("z",b);D(b);$(".G").W();E(b)});$(".1g").13("14",w(e){e.15();3 a="."+s.y;3 b=1m($(".G").1c(".y").M("u"))-1;Q("z",b);D(b);$(".G").W();E(b)})})}})(1D);',62,115,'|||var||if||li|items|else|||||||||||||||||||||title|class|function|obj|active|current|nav|end|Math|showPage|createPagination|href|pagination|document|this|cookie|c_start|get_cookie|attr|after|nav_items|goto|set_cookie|children|return|c_end|position|true|remove|previous|before|next|length|equal|min|live|click|preventDefault|each|ceil|offset|paginationCalculator|slice|start|find|ul|cookies|indexOf|goto_previous|false|minimize|goto_next|pagination_class|for|parseInt|inactive|fn|50|Next|hide|999|new|Date|setDate|getDate|show|escape|null|expires|toUTCString|height|jQuery|size|br|unescape|substring|floor|max|clear|all|outerHeight|css|jPaginate|extend|Previous'.split('|'),0,{}));