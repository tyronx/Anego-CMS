/* ==== Extend Core functioniality === */

// Core.editPage required in core.js since we load admPages.js on demand :(

/* Turns off the page editing interface */
// TODO: Important! Call endedit of all content elements!! (otherwise may not correctly unbind events e.g. on $(document))
Core.endEdit = function(options) {
	if (!options) options = {};
		
	var cancel = Core.callHooks("beforeEndEdit");
	if (cancel) return false;
	
	$('#contents').removeClass("editing");

	var unloadDragDrop = function() {
		anego.editmode = false; 
		
		/* Default zoomable picture links */
		Core.lightbox('a.zoomable');
		
		$('#pageEditLink').html(lngMain.edit_page);
		$('#pageEditLink').attr('href','javascript:Core.editPage()');
		
		Core.dragdrop.destroy();
		Core.dragdrop = null;
		Core.pageEditDialog.closeDialog();
		
		Core.callHooks("afterEndEdit");
	}
	
	/* When switching into an admin page, we don't have to load the old page again */
	if (options.ignorePage) {
		unloadDragDrop();
	} else {
		// Let Core.loadPage know that we are not in editmode anymore
		anego.editmode = false; 

		Core.loadPage(Core.curPg,{
			beforeContentLoaded: unloadDragDrop,
			forceLoad: true,
			updatePage: true,
			ok_callback: options.ok_callback
		});
		
		// But leave it on until a page has been successfully loaded
		anego.editmode = true; 
	}
}

Core.pageEditDialog = null;

/* Initializes the Page Editor 
 * Handles Drag&Drop insertion, Moving and Deleting of elements and makes all 
 * the necassary calls to the content element objects
 * Parameter is the return value of PageManager->contentElementModules() (page content and array of loaded modules),
 * thus the page is basically being reconstructed on the client side (in DragDropElements.preparePage()).
 */
function DragDropElements(options) {
	/* Element class prefixes */
	var prefix = 'draggable';
	var prefix2 = 'draggingItem';
	/* Distance between cursor and dragged item */
	var offsetX = 12;
	var offsetY = 10;
	/* All element objects */
	var pageElements = {};
	/* Drag & Drop stuff */
	var oldOffset;
	var dx=0, dy=0;
	var mouseDown = false;
	/* (Content-)Element, the mouse is currently hovering over */
	var curEl;
	/* Mini toolbar stuff */
	var $miniToolbar = $('.miniToolbar');
	
	// identifies wether the item currently being dragged is a placed content element or a content element template
	var movingCE = 0;
	var oldEl;
	
	var $container = $(options.container);
	var insertMarkerTemplate = '<hr id="insertMarker" />';
	var contentElementTemplate = '<div class="contentElement ceDraggable"></div>';
	/* Content elements window */
	var ceWindowTemplate = '<div class="draggableList"></div>';
	var draggableElementTemplate = 
		'<div class="moduleIcon">' + 
			'<div id="" class="ceDraggable ceTemplate">' +
				'<img src=""/>' + 
			'</div>' + 
			'<br/>' +
			'<span class="text"></span>' +
			'' + 
		'</div>';
	
	var miniToolbarTemplate = 
		'<div style="display:none;" class="miniToolbar">' + 
			'<img src="' + anego.path + 'styles/default/img/cleardot.gif" class="imgEdit icon">' +
			'<img src="' + anego.path + 'styles/default/img/cleardot.gif" class="imgBin icon">' +
		'</div>';

	function rebindElementEvents(container) {
		container.bind('mousemove.admPage', elementMouseMove);
		container.bind('mousedown.admPage', elementMouseDown);
	}

	this.init = function() {
		// Make sure $container is big enough to put something into
		if (parseInt($container.css('min-height')) < 80 || parseInt($container.css('height') < 80)) {
			$container.css('min-height','80px');
		}
		
		var elem;
		$ceWindow = $(ceWindowTemplate);
		/* Parse content element modules and execute its js */
		for (var i = 0; i < options.modules.length; i++) {
			$elem = $(draggableElementTemplate);
			$('.ceTemplate', $elem).attr('id', 'draggable' + i)
			$('.text', $elem).text(options.modules[i]['name']);
			
			$('img', $elem).attr('src', anego.path + options.modules[i]['image']);

			$ceWindow.append($elem);
		}
		
		/* Create content elements window */
		Core.pageEditDialog = OpenDialog({
			title: __('Page Elements'),
			content: $ceWindow,
			buttons: BTN_NONE,
			autocollapse: false,
			blocking: false,
			close_callback: function() { Core.endEdit(); }
		});
		
		/* Create element mini toolbar & bind events */	
		if($miniToolbar.length == 0) {
			$miniToolbar = $(miniToolbarTemplate);

			$('.imgEdit', $miniToolbar).click(function() {
				var targetElem = pageElements[$(curEl).attr('id')];
				
				if(targetElem != undefined) {
					if(! targetElem.editing) {
						if(targetElem.startEdit({ onEndEdit: rebindElementEvents })) {
							$(curEl).unbind('mousemove.admPage', elementMouseMove);
							$(curEl).unbind('mousedown.admPage', elementMouseDown);
						}
					} else {
						targetElem.endEdit();
					}
					
					if (targetElem.getHideMiniToolbar()) {
						$miniToolbar.hide();
					}
				} else {
					alert(__("Module of this Element not found, please install the module:") + " '" + splitID($(curEl).attr('id')).module_id + "'");
				}
			});
			
			$('.imgBin', $miniToolbar).click(function() {
				ConfirmDialog(lngMain.reallydeleteelement, function() {
					this.closeDialog();
					var element2Delete = curEl;
					var deleteCompleteFn = function() { 
						$(element2Delete).remove();
						$miniToolbar.hide();
					};
					
					// Todo: If module doesn't exist, instantiate a ContentElement class and delete it that way
					if (pageElements[$(curEl).attr('id')] != undefined) {
						pageElements[$(curEl).attr('id')].deleteElement(deleteCompleteFn);
					} else {
						alert(__("Module of this Element not found, please install the module:") + " '" + splitID($(curEl).attr('id')).module_id + "'");
					}
				});
			});
			
			/* Add the minitoolbar to the html page */
			$('#inactive').append($miniToolbar);
		}
		
		/* Set up draggable content element templates in the dialog */
		for (var i = 0; i < options.modules.length; i++) {
			$('#draggable'+i).mousedown(function(event) {
				curEl = $(this).clone();
				$(curEl)
					.attr("id",'draggingItem'+parseInt(this.id.substr(prefix.length)))
					.addClass("ceDragged")
					.hide();
				
				$('#inactive').append(curEl);
				mouseDown = 1;
				p = $(this).offset();
				oldOffset = p;
				
				/* Track document wide mouse movements */
				$(document)
					.bind('mousemove.admPage', documentMouseMove)
					.bind('mouseup.admPage', documentMouseUp);
								
				return false;
			});
		}
		
		/* Track $container wide mouse movement */
		$container.mousemove(function(event) {
			mouseMoved(event);
			
			if (mouseDown) {
				if (movingCE == 1) {
					dragContentElement(event);
				}
				if ($('.contentElement', $container).length == 0) {
					$('#insertMarker').remove();
					$container.prepend(insertMarkerTemplate);
				} else {
					if (event.pageY > $('.contentElement', $container).last().offset().top) {
						$('#insertMarker').remove();
						$container.append(insertMarkerTemplate);
					}
				}
				
				return false;
			}
		});

		/* Track $container parent wide mouse movement */
		$container.parent().mousemove(function(event) {
			mouseMoved(event);
			if (mouseDown && !inside($container, event.pageX, event.pageY)) {
				$('#insertMarker').remove();
				$container.prepend(insertMarkerTemplate);
			}
		});
		
		this.preparePage();
	}; // end of init();
	
	/* Mouse button released = new element dropped or existing element moved */
	function documentMouseUp(event) {
		if(!mouseDown) return;
		mouseDown = 0;

		/* Track document wide mouse movements */
		$(document)
			.unbind('mousemove.admPage', documentMouseMove)
			.unbind('mouseup.admPage', documentMouseUp);

		// User moved a previously placed content element
		if(movingCE) {
			if($('#insertMarker').length>0) {
				var mPos = markerPosition();

				$(curEl).css({
					'left': '',
					'top': '',
					'position': '',
					'width': '',
					'z-index': '1'
				}).removeClass("ceDragged");
				
				$('#insertMarker').replaceWith(curEl);
				$(oldEl).remove();
				bindEvents(curEl);
				var ret = splitID($(curEl).attr('id'));
				
				options.onMove(ret, mPos);
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
			var $container = $(contentElementTemplate);
			var markerPos = markerPosition();
			
			$('#insertMarker').replaceWith($container);
			// Call to module
			var obj;
			eval("obj = new " + options.modules[num]['mid'] + "('" + options.modules[num]['mid'] + "', " + Core.curPg.pageId + ");");
			obj.createElement(
				$container,
				markerPos,
				function(elmid) { pageElements[elmid]=obj; },
				rebindElementEvents
			);
			bindEvents($container);
		}
		
		$(curEl).remove();
	}
	
	this.destroy = function() {
		$('.contentElement', $container).unbind('.admPage');
		$('.contentElement', $container).removeClass('ceBorder');
		
		$miniToolbar.remove();
		$(document)
			.unbind('mousemove.admPage', documentMouseMove)
			.unbind('mouseup.admPage', documentMouseUp);
	}
	
	this.preparePage = function() {
		pageElements = {};
			
		/* Instantiate all element objects in the page */
		$('.contentElement', $container).each(function(index) {
			// Module Type and id is stored in html-element id
			var elInfo = splitID($(this).attr('id'));
			var module_id;
			var found = false;
			for (var i = 0; i < options.modules.length; i++) {
				module_id = options.modules[i]['mid'];
				if (module_id == elInfo.module_id) {
					pageElements[$(this).attr('id')] = eval("new " + module_id + "('" + module_id + "', " + Core.curPg.pageId + ", '" + elInfo.elem_id + "');");
					found = true;
					break;
				}
			}
			
			if (! found) console.log("some content elements could not be loaded");
		});
		
		/* Set up events for already loaded content elemens */
		bindEvents($('.contentElement', $container));
	};

	function documentMouseMove(event) {
		mouseMoved(event);
		if (!mouseDown) {
			if (curEl && !outerInside($(curEl), event.pageX,event.pageY)) { 
				$miniToolbar.hide();
				$(curEl).removeClass('ceBorder'); 
				curEl=null; 
			}
		}
	}
	
	function mouseMoved(event) {
		if (mouseDown && curEl.length > 0) {
			var x = BoundBy(event.pageX+offsetX, 2, $(window).width() - curEl.width() - 4);
			var y = BoundBy(event.pageY+offsetY, 2, $(window).height() - curEl.height() - 4);
			
			if($(curEl).hasClass('ceTemplate')) {
				curEl.show().offset({ top: y, left: x});
			} else {
				curEl.offset({ top: y });
			}
			
			if ((	! inside($container,event.pageX,event.pageY) && 
					! inside($container, event.pageX, event.pageY + 50)) 
					|| inside(Core.pageEditDialog,event.pageX,event.pageY)) {
				$('#insertMarker').remove();
			}
			
		} else {
			mouseDown=0;
		}
	}
	
	// Called when the mouse is moved over a content element
	function overContentElement(event, element) {
		if (mouseDown) {
			if (movingCE == 1) dragContentElement(event);
			$('#insertMarker').remove();
			$(element).after(insertMarkerTemplate);
		} else  {
			if (curEl != element) { 
				$miniToolbar.hide();
				$(curEl).removeClass('ceBorder'); 
			}
			
			if (pageElements[$(element).attr('id')] == undefined || pageElements[$(element).attr('id')].getHideMiniToolbar() != true) {
				$miniToolbar.show();
				// offset() needed here because other parent elements might have position:absolute etc.
				$miniToolbar.offset({ 
					top: $(element).offset().top, 
					left: $(element).offset().left + $(element).outerWidth() - $miniToolbar.outerWidth()
				});
			}
			$(element).addClass('ceBorder');
			curEl = element;
		}
	}
	
	function overContentElementOut(event, element) {
		if (!mouseDown) {
			if (!outerInside($(element),event.pageX,event.pageY)) {
				$miniToolbar.hide();
				$(element).removeClass('ceBorder');
				curEl = null;
			}
		}
	}
	
	function dragContentElement(event) {
		oldEl = curEl;
		curEl = $(curEl).clone();
		$('#inactive').append(curEl);
		curEl.addClass("ceDragged");
		
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
		el.bind('mousemove.admPage', elementMouseMove);
		/* Start dragging a content element */
		el.bind('mousedown.admPage', elementMouseDown);
		
		/* Higlight placed content elements when hovering over with mouse and put toolbar */
		el.bind('mouseenter.admPage', function (event) { 
			overContentElement(event,this); 
		});
		el.bind('mouseleave.admPage', function (event) { 
			overContentElementOut(event,this); 
		});
	}
	
	function elementMouseMove(event) {
		overContentElement(event,this);
		mouseMoved(event);
		return false;
	}
	
	function elementMouseDown(event) {
		if($(this).hasClass('ceDraggable')) {
			
			curEl = this; 
			// Make sure its visible
			$(curEl).css('z-index','999');
			movingCE=1; 
			mouseDown=true;
			$miniToolbar.hide();

			/* Track document wide mouse movements */
			$(document)
				.bind('mousemove.admPage', documentMouseMove)
				.bind('mouseup.admPage', documentMouseUp);

			return false;
		}
	}
	
	function markerPosition() {
		var i=0;
		var el = $('#insertMarker');
		while (el.prev().length > 0) {
			el = el.prev(); 
			if(el.hasClass('contentElement') && el.css('display') != 'none') { 
				i++; 
			}
			
		}
		return i;
	}
	
	function elementPosition(id) {
		var i=0;
		var el = $(id);
		while (el.prev().length > 0) {
			el = el.prev(); 
			if(el.hasClass('contentElement') && el.css('display') != 'none') { 
				i++; 
			}
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
		if (! match) { 
			alert('invalid element');
			return 0;
		}
		var id=match[1];
		
		// Remove element index
		id_cid = id_cid.substr(0,id_cid.length - 1 - id.length);
		
		return {elem_id:id,module_id:id_cid};
	}
}