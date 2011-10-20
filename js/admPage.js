/* ==== Extend Core functioniality === */

// Core.editPage required in core.js since we load admPages.js on demand :(

/* Turns off the page editing interface */
Core.endEdit = function(ignorePage) {
	var unloadDragDrop = function() {
		anego.editmode=false; 
		/* Default zoomable picture links */
		$('a.zoomable').fancybox(fancyBoxSettings);
		$('#pageEditLink').html(lngMain.edit_page);
		$('#pageEditLink').attr('href','javascript:Core.editPage()');
		
		this.dragdrop=null;
		CloseDialog();
	}
	
	/* When switching into an admin page, we don't have to load the old page again */
	if(typeof ignorePage != 'undefined' || ignorePage)
		unloadDragDrop();
	else 
		Core.loadPage(Core.curPg,{
			beforeContentLoaded: unloadDragDrop,
			forceLoad:true
		});
}

/* Initializes the Page Editor 
 * Handles Drag&Drop insertion, Moving and Deleting of elements and makes all 
 * the necassary calls to the content element objects
 * Parameter is the return value of PageManager->contentElementModules() (page content and array of loaded modules),
 * thus the page is basically being reconstructed on the client side (in DragDropElements.preparePage()).
 */
function DragDropElements(contentElements) {
	/* Element class prefixes */
	var prefix = 'draggable';
	var prefix2 = 'draggingItem';
	/* Distance between cursor and dragged item */
	var offsetX = 12;
	var offsetY = 10;
	/* All element objects */
	var elements = new Object();
	/* Drag & Drop stuff */
	var oldOffset;
	var dx=0, dy=0;
	var mouseDown = false;
	/* (Content-)Element, the mouse is currently hovering over */
	var curEl;
	/* Mini toolbar stuff */
	var imgEdit = $('<img src="styles/default/img/cleardot.gif" class="imgEdit icon">');
	var imgBin = $('<img src="styles/default/img/cleardot.gif" class="imgBin icon">');
	var miniToolbar = $('<div style="display:none;" class="miniToolbar"></div>');
	
	// identifies wether the item currently being dragged is a placed content element or a content element template
	var movingCE = 0;
	var oldEl;
	
	/* Editor window */
	var out='<div class="draggableList">';
	
	var elements;
	
	this.init = function() {
		// Make sure #content is big enough to put something into
		if(parseInt($('#content').css('min-height')) < 40 || parseInt($('#content').css('height') < 40))
			$('#content').css('min-height','40px');
		
		/* Parse content element modules and execute its js */
		for(var i=0; i<contentElements.length; i++) {
			out += '<div style="float:left; margin-left:7px; margin-right:7px; text-align:center;"><div id="draggable'+i+'" class="ceDraggable ceTemplate"><img src="'+contentElements[i]['image']+'" alt=""></div>'+contentElements[i]['name']+'</div>';	
		}
		out +="</div>";
		
		/* Create content elements window */
		OpenDialog({title:'Page Elements',
			content:out,
			buttons:BTN_NONE,
			autocollapse:false,
			blocking:false,
			close_callback:function() { Core.endEdit(); }
		});
		
		/* Create element mini toolbar & bind events */	
		miniToolbar.append(imgEdit);
		imgEdit.click(function() {
			if(elements[$(curEl).attr('id')]!=undefined) {
				elements[$(curEl).attr('id')].editElement();
				if(typeof elements[$(curEl).attr('id')].hideMiniToolbar != 'undefined' && elements[$(curEl).attr('id')].hideMiniToolbar())
					miniToolbar.css('display','none');
			}
			else alert("Module of this Element not found, please install the module '"+splitID($(curEl).attr('id')).module_id+"'");
		});
		miniToolbar.append(imgBin);
		imgBin.click(function() {
			var res=confirm("Really delete?");
			if(res) {
				var element2Delete = curEl;
				var fn = function() { $(element2Delete).remove(); miniToolbar.css('display','none'); };
				
				if(elements[$(curEl).attr('id')]!=undefined)
					elements[$(curEl).attr('id')].deleteElement(fn);
				else alert("Module of this Element not found, please install the module '"+splitID($(curEl).attr('id')).module_id+"'");
			}
		});
		
		/* Add the minitoolbar to the html page */
		$('#inactive').append(miniToolbar);
		
		/* Set up draggable content element templates in the dialog */
		for(var i=0; i<contentElements.length; i++) {
			$('#draggable'+i).mousedown(function(event) {
				curEl = $(this).clone();
				$(curEl).attr("id",'draggingItem'+parseInt(this.id.substr(prefix.length)));
				$('#inactive').append(curEl);
				mouseDown = 1;
				p = $(this).offset();
				oldOffset=p;
				$(curEl).offset({ top: p.top, left: p.left })
				dx = event.pageX - p.left
				dy = event.pageY - p.top;
				return false;
			});
		}
		
		/* Track document wide mouse movements */
		$(document).mousemove(function(event) {
			mouseMoved(event);
			if(!mouseDown)
				if(curEl && !outerInside($(curEl),event.pageX,event.pageY)) { 
					miniToolbar.css('display','none'); 
					$(curEl).removeClass('ceBorder'); 
					curEl=null; 
				}
		});
		
		/* Track #content wide mouse movement */
		$('#content').mousemove(function(event) {
			mouseMoved(event);
			
			if(mouseDown) {
				if(movingCE==1) dragContentElement(event);
				if($('.contentElement').length==0) {
					$('#insertMarker').remove();
					$('#content').prepend('<hr id="insertMarker" style="background-color:transparent; width:100%; height:10px; border:1px dashed red;">');
				} else
				
				if(event.pageY > $('.contentElement').last().offset().top) {
					$('#insertMarker').remove();
					$('#content').append('<hr id="insertMarker" style="background-color:transparent; width:100%; height:10px; border:1px dashed red;">');					
				}
				
				return false;
			}
		});

		/* Track #contents wide mouse movement */
		$('#contents').mousemove(function(event) {
			mouseMoved(event);
			if(mouseDown && !inside($('#content'),event.pageX,event.pageY)) {
				$('#insertMarker').remove();
				$('#content').prepend('<hr id="insertMarker" style="background-color:transparent; width:100%; height:10px; border:1px dashed red;">');
			}
		});
		
		/* Mouse button released = new element dropped or existing element moved */
		$(document).mouseup(function(event) {
			if(!mouseDown) return;
			mouseDown = 0;
			// User moved a previously placed content element
			if(movingCE) {
				if($('#insertMarker').length>0) {
					var mPos = markerPosition();

					$(curEl).css('left','');
					$(curEl).css('top','');
					$(curEl).css('position','');
					$(curEl).css('width','');
					$(curEl).css('z-index','1');
					$('#insertMarker').replaceWith(curEl);
					$(oldEl).remove();
					bindEvents(curEl);
					var ret=splitID($(curEl).attr('id'));
					
					$.get('index.php?a=mce&mid='+ret.module_id+'&elid='+ret.elem_id+'&newpos='+mPos,
						function(data) {
							if(aw=GetAnswer(data)) {
								// ok
							} else {
								// undo all
							}
						});
				} else {				
					if(oldEl!=undefined) {
						$(curEl).remove();
						$(oldEl).css('display','');
					}
				}
				movingCE=0; 
				oldEl=undefined;
				return; 
			}
			
			// Creating a new content element
			if($('#insertMarker').length>0) {
				// content element template "index"
				var num = parseInt(curEl.attr('id').substr(prefix2.length));
				var $container = $('<div class="contentElement ceDraggable"><img src="styles/default/img/progress_active.gif"></div>');
				var markerPos = markerPosition();
				
				$('#insertMarker').replaceWith($container);
				// Call to module
				var obj;
				eval("obj = new "+contentElements[num]['mid']+"(" + Core.curPg.id + ");");
				obj.createElement(
					$container,
					markerPos,
					function(elmid) { elements[elmid]=obj; }
				);
				bindEvents($container);
			}
			
			$(curEl).remove();
		});
		
		
		this.preparePage();
	}; // end of init();
	
	
	this.preparePage = function() {
		/* Instantiate all element objects in the page */
		$('.contentElement').each(function(index) {
			// Module Type and id is stored in html-element id
			var elInfo = splitID($(this).attr('id'));

			for(var i = 0; i < contentElements.length; i++)
				if(contentElements[i]['mid'] == elInfo.module_id) {
					eval("elements['"+$(this).attr('id')+"'] = new "+contentElements[i]['mid']+"(" + Core.curPg.id + ", '" + elInfo.elem_id + "'); ");
					break;
				}
			//if(!found) alert("some content elements could not be loaded [insert proper error handling here (= don't make those elements editable + mark as such)]");
		});
		
		/* Set up events for already loaded content elemens */
		bindEvents($('.contentElement'));
	};
	
	
	function mouseMoved(event) {
		//document.getElementById('editpage').innerHTML=event.pageX + " / " + event.pageY;
		if(mouseDown && curEl.length>0) {
			var x = BoundBy(event.pageX+offsetX,2,$(document).width()-curEl.width()-4);
			var y = BoundBy(event.pageY+offsetY,2,$(document).height()-curEl.height()-4);
			if($(curEl).hasClass('ceTemplate')) curEl.offset({ top: y, left: x});
			else curEl.offset({ top: y }) //, left: x
			
			if((!inside($('#content'),event.pageX,event.pageY) && !inside($('#content'),event.pageX,event.pageY+50)) || inside($('#dlgBox'),event.pageX,event.pageY))
				$('#insertMarker').remove();
			
		} else mouseDown=0;
	}
	
	// Called when the mouse is moved over a content element
	function overContentElement(event, element) {
		if(mouseDown) {
			if(movingCE==1) dragContentElement(event);
			$('#insertMarker').remove();
			$(element).after('<hr id="insertMarker" style="background-color:transparent; width:100%; height:10px; margin-top:10px; border:1px dashed red;">');			
		} else  {
			if(curEl != element) { miniToolbar.css('display','none'); $(curEl).removeClass('ceBorder'); }
			
			if(elements[$(element).attr('id')]==undefined || typeof elements[$(element).attr('id')].hideMiniToolbar=='undefined' || elements[$(element).attr('id')].hideMiniToolbar()!=true) {
				miniToolbar.css('display','');
				// offset() needed here because other parent elements might have position:absolute etc.
				miniToolbar.offset({ top: $(element).offset().top, left: $(element).offset().left + $(element).outerWidth() - miniToolbar.outerWidth()});
			}
			$(element).addClass('ceBorder');
			curEl = element;
			
		}
	}
	
	function overContentElementOut(event, element) {
		if(!mouseDown) {
			if(!outerInside($(element),event.pageX,event.pageY)) {
				miniToolbar.css('display','none');
				$(element).removeClass('ceBorder');
				curEl = null;
			}
		}
	}
	
	function dragContentElement(event) {
		oldEl = curEl;
		curEl = $(curEl).clone();
		$('#inactive').append(curEl);
		mouseDown = 1;
		p = $(oldEl).offset();
		oldOffset=p;
		$(curEl).offset({ top: p.top, left: p.left })		
		dx = event.pageX - p.left
		dy = event.pageY - p.top;
		
		movingCE=2;
		$(curEl).css('width',$(oldEl).width()+'px');
		$(oldEl).css('display','none');
		$(oldEl).removeClass('ceBorder');
		return false;
	}
	
	/* Set up events for already loaded content element(s) */
	function bindEvents(el) {
		/* Moving mouse over a content element */
		el.mousemove(function(event) {
			overContentElement(event,this);
			mouseMoved(event);
			return false;
		});
		
		/* Start dragging a content element */
		el.mousedown(function() {
			if($(this).hasClass('ceDraggable')) {
				curEl = this; 
				// Make sure its visible
				$(curEl).css('z-index','999');
				movingCE=1; 
				mouseDown=true;
				miniToolbar.css('display','none');
				return false;
			}
		});
		
		/* Higlight placed content elements when hovering over with mouse and put toolbar */
		el.hover(function (event) { overContentElement(event,this); },function (event) { overContentElementOut(event,this); });
	}
	
	function markerPosition() {
		var i=0;
		var el = $('#insertMarker');
		while(el.prev().hasClass('contentElement')) { 
			el = el.prev(); 
			if(el.css('display')!='none') i++; 
		}
		return i;
	}
	
	function elementPosition(id) {
		var i=0;
		var el = $(id);
		while(el.prev().hasClass('contentElement')) { 
			el = el.prev(); 
			if(el.css('display')!='none') i++; 
		}
		return i;
	}
	
	function outerInside(el,x,y) {
		var p = el.offset();
		return x >= p.left && x <= p.left+el.outerWidth()-1 && y >= p.top && y <= p.top+el.outerHeight()-1;
	}
	
	function inside(el,x,y) {
		var p = el.offset();
		return x >= p.left && x <= p.left+el.width() && y >= p.top && y <= p.top+el.height();
	}	
	
	// id_cid because element ids have the form of '(module id)_(data id)'
	function splitID(id_cid) {
		var foo = new RegExp(/_(\d+)$/g);
		var match = foo.exec(id_cid);
		if(!match) { alert('invalid element'); return 0; }
		var id=match[1];
		
		// Remove element index
		id_cid = id_cid.substr(0,id_cid.length - 1 - id.length);
		
		return {elem_id:id,module_id:id_cid};
	}
}