var fancyBoxSettings = {
	'cyclic'		: false,
	'overlayShow'	: false,
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
	if(anego.editmode) Core.initPageContentEdit();
	else Core.initPageContent();
	if(anego.pageLoad=='ajax')
		Core.ajaxifyMenu();
});


function CoreFunctions() {
	/* Ignore the first jquery.history callback */
	var firstLoad = false;
	/* Prevent double loading from the rsh lib */
	var loadingPage=null;
	var that = this;
	var dragdrop = null;
	// Keeps a list of loaded js files (and ignores any loads that are done twice)
	var loadedJsFiles=Array();
	
	var loadHooks = Array();
	// anego.curPg is fullpath string, Core.curPg is splitted object containing usfull infos about the current page
	var curPg;
	
	this.pageInfo=function(page) {
		var pginfo = {};
		var result = /^([a-zA-Z_]+)(\d+|\/[a-zA-Z_]+)(\/(.*))?/.exec(page)
		// No anchor means we need to load the home page
		if(! page)
			return {
				valid: true,
				type: 'pg',
				id: anego.homepage,
				head: 'pg' + anego.homepage,
				tail: '',
				root: true,
				fullpath: 'pg' + anego.homepage
			}
			
		if(! result) return { valid: false, fullpath: page };
		
		pginfo = {
			valid: true,
			type: result[1],
			id: result[2],
			head: result[1] + '/' + result[2],
			tail: result[4],
			fullpath: page
		}

		if(pginfo.type!='pg' && pginfo.type!='adm' && (typeof anego.directLoad == 'undefined' || !anego.directLoad.contains(pginfo.type))) 
			pginfo.valid=false;
		
		return pginfo;
	}
	
	this.curPg = this.pageInfo(anego.curPg);
	
	this.initPageContent=function() {
		/* Email defuscator tool */
		$('.hiddenEmail').defuscate();
		/* Links to pages on the same site that are made with tinymce/etc. need to be converted */
		if(anego.pageLoad=='ajax')
			$('#content a').attr('href',function(idx,attr) { return attr.replace(/^pg(\d+)/g,'#pg$1'); } );
		/* Default zoomable picture links */
		$('a.zoomable').fancybox(fancyBoxSettings);
	}
	
	this.initPageContentEdit=function(data) {
		Core.editPage(Core.curPg.id, data);
	}
	
	this.ajaxifyMenu=function() {
		/* Fix degraded links for ajax loading */
		if(anego.editmode) {
			$('.mainnav li a').attr('href',function(idx,attr) { return attr.replace(/admin-(pg\d+)/g,'admin#$1'); });
			$("#minornav li a").attr('href',function(idx,attr) { return attr.replace(/admin-(pg\d+)/g,'admin#$1'); });
		} else {
			$('.mainnav li a').attr('href',function(idx,attr) { return attr.replace(/pg(\d+)/g,'#pg$1'); } );
			$("#minornav li a").attr('href',function(idx,attr) { return attr.replace(/pg(\d+)/g,'#pg$1'); } );
		}
	}
	
	// Changes the language to lang and reloads the page
	this.setLang = function(lang) {
		jQuery.cookie('lang',lang);
		window.location.href = window.location.href.replace(/\?.*/g,'');
	}

	/* Parameter page is a string, whereas the first letter identify the type and the appended number/name the entry id/page */
	// e.g. (pg)34, (adm)setg
	// callback may be a function to be called once the page has loaded
	this.loadPage=function(newpage,settings) {
		if(typeof settings != 'object')
			settings = new Object();
		
		if(typeof newpage != 'object')
			newpage = Core.pageInfo(newpage);
		
		if(! newpage.valid) return false;
		
		/*  RSH calls loadPage() too => don't make requests twice */
		if(loadingPage!=null) return false;
		loadingPage = newpage.fullpath;
		
		//$('#name').html('b');
			
		for(var i=0; i<loadHooks.length; i++)
			if(loadHooks[i](newpage)) {
				loadingPage=null;
				return false;
			}
			
		//$('#name').append('c');
		
		/* Don't load same page (also seems to be buggy in ie if loaded twice).*/
		if(newpage.fullpath == Core.curPg.fullpath && (typeof settings.forceLoad=='undefined' || settings.forceLoad==false)) {
			loadingPage=null;
			return false;
		}
		
		//$('#name').append('d');
		
		/* If we have loaded a non-ajax loaded page (domain.com/pg23) and try to load a page with ajax (domain.com/#pg23)
		 * The browser will load a new site because he sees it as a different file, hence our GET request fails with an
		 * empty error message. So: Don't load pages with ajax in such cases.
		*/
		if(location.pathname[location.pathname.length-1]!='/') {
			loadingPage=null;
			return false;
		}

		var file='index.php';
		var get;
		var pgId;

		switch(newpage.type) {
			case 'adm':
				file = 'admin.php';
				get = { a: newpage.id.substr(1), noheader: 1 };
				$('#pageEditLink').parent().css('display','none');
				if(anego.editmode) this.endEdit(true);
				this.selectPage(null); // Unselect selected page
				break;
				
			case 'pg':
				$('#pageEditLink').parent().css('display','');
				this.selectPage(newpage);
				
				get = {a:'p',p:newpage.id};
				if(anego.editmode)
					get={a:'gce',fgx:newpage.id};
				break;
					
			default:
				loadingPage=null;
				return false;	
		}
		
		//$('#name').append('e');
		
		var animated=false,loaded=false;
		var aw;	
		var xdf=0;
		if(anego.animatePageLoad==0)
			animated=true;
		else
			$('#content').css({opacity: 1.0}).animate({opacity: 0.0}, anego.animatePageLoad, function() {
				if(loaded && !animated) putLoadedText(aw);
				animated=true;
			});
		
		$.get(file,get,function(data) {
			if(aw=GetAnswer(data)) {
				if(animated) putLoadedText(aw);
				loaded=true;
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
			} else {			 // from index.php (ajax.php)
				$('#content').html(data.content);
				that.initPageContent();
			}
			
			if(anego.animatePageLoad>0) 
				$('#content').css({opacity: 0.0}).animate({opacity: 1.0}, anego.animatePageLoad);
				
			Core.curPg = newpage;
			loadingPage=null;
			
			// Callback function from loadPage() parameter
			if(typeof settings.afterContentLoaded != 'undefined')
				settings.afterContentLoaded(data);
		}
		
		return true;
	}
	
	this.loadJSONResult = function(data) {
		/* Load javascript and css files */
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
	this.selectPage = function(page) {
		if(anego.submenuStyle == 'visible') return;
		
		var el = null;
		if(page != null) el = $('#menu .mainnav li a[onclick="Core.loadPage(\'' + page.fullpath + '\')"]').parent();
		
		// If this is a child of a child we just need to make sure its visible
		if(el != null && el.hasClass('subsubitem')) {
			el.show();
			// Make sure subnav list is visible
			el.parent().parent().css('display','');
			return;
		}
		
		// Deselect old page, unless its always visible
		if(anego.submenuStyle != 'submenu onselect' || $('#menu .mainnav li.navSelected').parents('ul').length > 2 ) {
			$('#menu .mainnav li.navSelected .subnavbox').hide();
		}
		
		// Remove current selection
		$('#menu .mainnav li.navSelected div.subsubitems').hide();
		$('#menu .mainnav li.navSelected').removeClass('navSelected');
		
		// No page select => we just unselect the current page
		if(page == null) return;
		
		// If a sub page is clicked, leave submenu open and parent menu selected
		if(el.parents().hasClass('subnavlist')) {
			// Remove other subpages selection
			el.parent().find('li.navSelected').removeClass('navSelected');
			// Menu selected, add class to the. <li>
			el.addClass('navSelected');
			// If this page again has children, show them
			el.children('div.subsubitems').show();
			// Make sure subnav list is visible
			el.parent().parent().css('display','');
			// Make sure parent element is selected
			el.parent().parent().parent().addClass('navSelected');
		} else {
			// Select new page
			// jQuery rocks. Seriously.
			$('#menu .mainnav li a[onclick="Core.loadPage(\'' + page.fullpath + '\')"]').parents('li').first().addClass('navSelected');
			$('#menu .mainnav li.navSelected .subnavbox').show();
		}
	}
	
	/* Opens the page editing interface */
	// Paremeter: (optional) page: (int)
	//			  (optional) data: already received edit page data (will skip get request)
	this.editPage = function(page, data) {
		var aw;
		
		if(!page) page = Core.curPg.id;
		
		/* Dragdrop initialized -> only reinitalize for the contents */
		if(this.dragdrop!=null) {
			this.dragdrop.preparePage();
			return;
		}
		
		anego.editmode=true;
		if(typeof DragDropElements == "undefined") this.loadJavascript('ld.ap.ad'+anego.language);
		$(document).ready(function() {
			$('#pageEditLink').html(lngMain.doneedit_page);
			$('#pageEditLink').attr('href','javascript:Core.endEdit()');
			
			// Todo: Code this a bit cleaner
			if(typeof data == 'undefined') {
				$.get("index.php?a=gce&fgx="+page, function(data) {
					if(aw=GetAnswer(data)) {
						data = jQuery.parseJSON(aw);
						Core.loadJSONResult(data);
						$('#content').html(data.content);
						this.dragdrop=new DragDropElements(data.modules);
						this.dragdrop.init();
					}
				});
			} else {
				this.dragdrop=new DragDropElements(data.modules);
				this.dragdrop.init();
			}
		});
	}

	this.historyChange=function(newLocation) {
		if(! newLocation && !firstLoad) return firstLoad=true;
		firstLoad = true;
		
		var newpage = Core.pageInfo(newLocation);

		if(newpage.invalid) return;
		
		if(newpage.type == 'adm') {
			Core.loadPage(newpage);
			// We'll call the load page hooks in loadpage
			return;
		}
		else if(newpage.type == 'pg') {
			Core.loadPage(newpage);
			// We'll call the load page hooks in loadpage
			return;
		}
		
		for(var i=0; i<loadHooks.length; i++)
			loadHooks[i](newpage);
	}
	
	// Be really careful when calling loadpage inside a hook function, 
	// as it will fire the loadPage event again, so make sure not to fall in a endless loop
	this.addloadPageHook=function(fn) {
		loadHooks.push(fn);
	}

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
				$('head').append('<script type="text/javascript" src="ld.'+toLoad.join('.')+'"></script>');
			
			loadedJsFiles=loadedJsFiles.concat(toLoad);
		} else {
			$('head').append('<script type="text/javascript" src="'+file+'"></script>');
			loadedJsFiles[loadedJsFiles.length]=file;
		}
	}
	
	this.loadCSS = function(file) {
		$('head').append('<link rel="stylesheet" href="'+file+'" type="text/css" media="screen">');	
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

	
	/* ajax post request, similar to $.post, but allows multiple simultaneous requests */
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
	content:		The actual html content of the dialog
	left:			x coordinate of the window
	top:			y coordinate of the window, if x&y not defined it will be centered and the position will be remembered in a cookie
	width:			dialog width in pixel (default: autosize)
	height:			dialog height in pixel (default: autosize)
	buttons:		BTN_YESNO, BTN_OKCANCEL, BTN_SAVECANCEL, BTN_CLOSE or BTN_NONE (default: BTN_OKCANCEL)
	blocking:		false if you want the dialog to be non blocking (= user can still interact with the page) (default: true)
	ok_callback:	function to be called when the user pressed Ok/Yes
	close_callback:	function to be called when the user pressed Cancel,No or Close
	autocollapse:	If true, minimizes the dialog when out of focus (default: false)
*/
/* Todo: Refactor this into a jquery plugin. But more importanly, allow multiple dialogs! */
function OpenDialog(settings) {
	var dlgBox=null;
	var w='',h='';
/*function OpenDialog(title, content, width, height, callback, btntype, close_callback) {*/
	var btn1=lng_ok, btn2=lng_cancel;
	switch(settings.buttons) {
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
	
	if(settings.collapse==undefined) settings.collapse = false;
	
	if(settings.width!=undefined) 
		w = 'width: '+settings.width+'px; ';
	if(settings.height!=undefined) 
		h = 'height: '+settings.height+'px; ';
	
	if($("#inactive").length == 0)
		$('body').append("<div id=\"inactive\" style=\"display:none\"></div>");
	
	$("#inactive").css('display','');
	
	var str = "<div id=\"horizon\"><div id=\"dlgBox\" class=\"adminstyles\" style=\""+w+h+" top:"+(window.innerHeight/3)+"px;\"><div id=\"dlgTitle\">"+settings.title+
						"<div id=\"dlgXBtn\" class=\"dlgBtn\">X</div><div id=\"dlgMBtn\" class=\"dlgBtn\">_</div></div><hr class=\"dlgSep\">\n<div id=\"dlgContent\">"+settings.content+
						'<div id="dlgBtnContainer"><img src="styles/default/img/cleardot.gif" class="loadingIcon" id="im_pr" style="display:none; vertical-align:middle; margin-right:5px;  margin-bottom:2px;"> ';
	if(settings.buttons!=BTN_NONE) {
		if(settings.buttons!=BTN_CLOSE)	
			str += "<input type=\"button\" id=\"dlgOK\" value=\""+btn1+"\"> ";
		str += "<input type=\"button\" id=\"dlgCancel\" value=\""+btn2+"\">";
	}
	str += "</div></div></div></div>";
						
	$("#inactive").html(str);
	dlgBox = $('#dlgBox');
	/* settings.inactivate defines wether the user is still allowed to interact with the site or not (blocking or non blocking dialog) */
	if(settings.blocking==false)
		$('#inactive').css('position','static');
	else $('#inactive').css('position','absolute');
	
	/* Get previously saved position if it is set and no custom position supplied, but limit to viewable area  */
	if(settings.top==undefined && settings.left==undefined) {
		if(localStorage.getItem("anego_dlg_"+settings.title+"_left")!=null) {
			settings.left=BoundBy(localStorage.getItem("anego_dlg_"+settings.title+"_left"),0,f_clientWidth()-$('#dlgBox').width());
			settings.top =BoundBy(localStorage.getItem("anego_dlg_"+settings.title+"_top"),0,f_clientHeight()-$('#dlgBox').height());
		}
	}
	/* Position element if any coordinate is set */
	if(settings.top!=undefined || settings.left!=undefined) {
		if(settings.top==undefined) settings.top=0;
		if(settings.left==undefined) settings.left=0;				
		
		dlgBox.css('top',settings.top);
		dlgBox.css('left',settings.left);
	} 
	
	/* Button callbacks */
	$('#dlgOK').click(settings.ok_callback);
	$('#dlgCancel').click(function() {
		CloseDialog();
		if(settings.close_callback!=undefined)
			settings.close_callback();
	});
	$('#dlgXBtn').click(function() {
		CloseDialog();
		if(settings.close_callback!=undefined)
			settings.close_callback();
	});
	$('#dlgMBtn').click(function() {
		settings.collapse = !settings.collapse;
		if(settings.collapse) {
			$('#dlgMBtn').html('□');
			collapse();
		} else {
			$('#dlgMBtn').html('_');
			expand();
		}
	});

	if(settings.buttons!=BTN_NONE)
		document.getElementsByTagName("input")[0].focus();

	SetupEvents();
	
	var boxColor=dlgBox.css('backgroundColor'), headerColor=$('#dlgTitle').css('backgroundColor');
	var expand = function() {
		if(settings.height == undefined)
			dlgBox.css('height','auto');
		else dlgBox.css('height',settings.height+'px');
		
		$('#dlgTitle').css('backgroundColor',headerColor);
		dlgBox.css('backgroundColor',boxColor);
		$('#dlgContent').show();
		$('#dlgBox .dlgSep').show();
	}
	var collapse = function() {
		dlgBox.css('height','21px');
		dlgBox.css('backgroundColor',headerColor);
		$('#dlgTitle').css('backgroundColor','transparent');
		$('#dlgContent').hide();
		$('#dlgBox .dlgSep').hide();
	}
	
	/* Autocollapse feature */
	if(settings.autocollapse) {
		dlgBox.mouseover(collapse);
		dlgBox.mouseout(expand);
	}
	
	document.onkeydown = function(event) {
		// escape: 27
		// enter: 13
		if(!event) event = window.event;
		
		if(event.keyCode==27 || (settings.buttons==BTN_CLOSE && event.keyCode==13)) {
			CloseDialog();
			if(settings.close_callback!=undefined)
				settings.close_callback();
			
		} else
			if(event.keyCode==13) {				
				settings.ok_callback();
			}
	};
	
	/* Drag and Drop functionality */
	function SetupEvents() {
		var dx=0, dy=0;
		var mouseDown = 0;
		
		$('#dlgTitle').mousedown(function(event) {
			mouseDown = 1;
			dlgBox.css('margin','0');
			/*dx = event.pageX - dlgBox.offset().left;
			dy = event.pageY - dlgBox.offset().top;*/
			dx = event.pageX - dlgBox.css('left').substr(0,dlgBox.css('left').length-2);
			dy = event.pageY - dlgBox.css('top').substr(0,dlgBox.css('top').length-2);
			return false;
		}); 
	
		$(document).mouseup(function(event) {
			if(mouseDown) {
				localStorage.setItem("anego_dlg_"+settings.title+"_left",dlgBox.css('left').substr(0,dlgBox.css('left').length-2));
				localStorage.setItem("anego_dlg_"+settings.title+"_top",dlgBox.css('top').substr(0,dlgBox.css('top').length-2));
			}
			mouseDown = 0;
		});
		$(document).mousemove(function(event) {
			if(mouseDown) {
				dlgBox.css('top',BoundBy(event.pageY-dy,3,$(document).height()-dlgBox.height()-3)+'px');
				dlgBox.css('left',BoundBy(event.pageX-dx,3,$(document).width()-dlgBox.width()-3)+'px'); 
			}
		});	
		
	}
}

function CloseDialog() {
	document.getElementById("inactive").style.display='none';
	document.onkeydown = '';
}

function BoundBy(x, minx, maxx) {
	return Math.min(maxx,Math.max(x,minx));
}

function GetAnswer(text) {
	if(text.substr(0,3)!='200') {
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

// private method for UTF-8 encoding
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
