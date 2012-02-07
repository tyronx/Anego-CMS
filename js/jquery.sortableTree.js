/******************************************************************
 * My version of a Drag&drop tree, because 7.3kb (unminified) < 5 mb (= extjs core + tree component + css/images)
 * Copyright (C) 2011  tyron.at, Tyron Madlener
 * You may do stuff with this files in ways the GPL 2.0 allows you to, but please attribute my work
 ******************************************************************/
(function($) {
	jQuery.fn.sortableTree = function(method) {
		
		var methods = {
			init : function(options) {
				// Already initialized
				if($(this).data('sortableTree') != undefined || $(this).data('sortableTree') != null)
					return false;
				
				var tb = new sortableTreeInstance(this, options);
				tb.init();
				$(this).data('sortableTree',tb);
			},/*
			refresh : function( ) { 
				$(this).data('sortableTree').refresh();
			},*/
			destroy : function( ) { 
				$(this).data('sortableTree').destroy();
				$(this).data('sortableTree',null);
			}
		};
		
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.sortableTree' );
		}    
		
		function sortableTreeInstance(tree, options) {
			var draggedElement;
			var listElements = new Array();
			var overElement = null;
			var dropAt = null;
			var draggedAwayListNode = null;
			var mouseDownOn = null;
			var mouseDownCoords = [];
			var dragger;
			var dragging = false;
			var draggerIcon = $('<img src="' + anego.path + 'styles/default/img/cleardot.gif" alt="" class="iconDrop"> ');
			var $shadowNode;
			
			if (! options) options = {};
				
			this.init = function() {
				$('body').append(dragger = $('<div style="display:none;" class="dragger draggedElement"></div>'));
				
				$(tree).find('li').each(function() {
					listElements[listElements.length]=this;
				});
				
				bindElementEvents($(tree).find('li'));
				
				$(document).mousemove(onMouseMove);
				$(document).mouseup(onMouseUp);
			};
			
			this.destroy = function() {
				dragger.remove();
				$(document).unbind('moousemove',onMouseMove);
				$(document).unbind('moouseup',onMouseUp);
			};
		
			function onMouseMove(event) {
				if (mouseDownOn != null && dist(mouseDownCoords, {x: event.pageX, y: event.pageY}) > 3 ) {
					dragger.html($(mouseDownOn).clone());
					dragger.prepend(draggerIcon);
					dragger.css('display','');
					draggedAwayListNode = $(mouseDownOn).parent();
					
					draggedAwayListNode.find('span')
						.append('<div class="shadowElement"></div>')
						.css('position', 'relative');
					
					//draggedAwayListNode.hide();
					
					mouseDownOn = null;
					dragging=true;

				}
				
				if(dragging) {
					var ov = isOverElement(event.pageX,event.pageY);
					var ovEl;
					var dy;
					dragger.offset({left:event.pageX + 5,top:event.pageY + 15});
					
					// Element is over,above or below an element
					if(ov != -1) {
						dropAt = getElementDropAtElement(ov, event);
						ovEl = $(listElements[ov]).find('span.listEl');
						
						switch(dropAt) {
							case 'above':
								ovEl.addClass('insertAbove');
								ovEl.removeClass('insertBelow');
								ovEl.removeClass('insertIn');
								dragger.find('img.iconDrop').attr('class','iconDrop iconDropBetween');
							break;
								
							case 'under':
								ovEl.addClass('insertBelow');
								ovEl.removeClass('insertIn');
								ovEl.removeClass('insertAbove');
								dragger.find('img.iconDrop').attr('class','iconDrop iconDropBetween');
							break;
								
							case 'in':
								ovEl.addClass('insertIn');
								ovEl.removeClass('insertBelow');
								ovEl.removeClass('insertAbove');
								dragger.find('img.iconDrop').attr('class','iconDrop iconDropAdd');
							break;
						}
						
					// Element is outside the tree
					} else {
						dropAt = getElementDropAtOutside(event);
						
						switch(dropAt) {
							case 'top':
								$(tree).addClass('insertAbove');
								dragger.find('img.iconDrop').attr('class','iconDrop iconDropOver');
							break;
							
							case 'bottom':
								$(tree).addClass('insertBelow');
								dragger.find('img.iconDrop').attr('class','iconDrop iconDropUnder');
							break;
						}
					}
					
					// May happen when too far outside the tree, or when above the same element itsef
					if(dropAt == 'none') {
						$(tree).removeClass('insertBelow');
						$(tree).removeClass('insertAbove');
						
						dragger.find('img.iconDrop').attr('class','iconDrop iconDropNo');
					}
					
					if(ov != overElement && overElement!=-1) {
						$(listElements[overElement]).find('span.listEl').removeClass('insertIn');
						$(listElements[overElement]).find('span.listEl').removeClass('insertBelow');
						$(listElements[overElement]).find('span.listEl').removeClass('insertAbove');
					}
					
					overElement = ov;
				}
				
				return false;
			}
			
			function getElementDropAtElement(ov, event) {
				dy = event.pageY - $(listElements[ov]).offset().top;
				ovEl = $(listElements[ov]).find('span.listEl');
				
				if($(listElements[ov]).attr('id') == draggedAwayListNode.attr('id')) {
					return 'none';
				}
				
				if($(listElements[ov]).parents('li#' + draggedAwayListNode.attr('id')).length > 0) {
					return 'none';
				}
				
				if(dy < 6) {
					return 'above';
				} else {
					if(ovEl.innerHeight() - dy < 6) {
						return 'under';
					} else {
						return 'in';
					}
				}
				
				return 'none';
			}
			
			function getElementDropAtOutside(event) {
				// Element is at bottom of the tree
				
					// Within 50 px below the tree (y-coordinate)
				if(	inside(event.pageY, $(tree).offset().top + $(tree).innerHeight(), $(tree).offset().top + $(tree).innerHeight() + 50)
					// No more than 25px outside the tree (x-coordinate)
					&& inside(event.pageX, $(tree).offset().left - 25, $(tree).offset().left + $(tree).width() + 25 )
				) {
					
					return 'bottom';
				}
				
				// Element is at bottom of the tree
					
					// Within 50 px above the tree (y-coordinate)
				if(inside(event.pageY, $(tree).offset().top - 50, $(tree).offset().top)
					// No more than 25px outside the tree (x-coordinate)
					&& inside(event.pageX, $(tree).offset().left - 25, $(tree).offset().left + $(tree).width() + 25 )
				) {
					return 'top';
				}
					
				return 'none';
			}
			
			function onMouseUp(event) {
				dragging = false;
				mouseDownOn = null;
				var newNode;
				var tmp;
				var atEl = $(listElements[overElement]);
				var atElDiv = $(listElements[overElement]).find('span.listEl');
				var target='none';
				var pos='none';
				
				$(listElements[overElement]).find('span.listEl').removeClass('insertIn');
				$(listElements[overElement]).find('span.listEl').removeClass('insertBelow');
				$(listElements[overElement]).find('span.listEl').removeClass('insertAbove');
				
				if (draggedAwayListNode) {
					/* No place to drop found, put it back */
					if ((overElement==-1 && dropAt!='bottom' && dropAt!='top') || dropAt=='none') {
						var off = draggedAwayListNode.offset();
						dragger.animate({
							left: off.left,
							top: off.top,
							opacity: 0.5
						}, {
							duration: 200,
							complete: function() {
								dragger.css('display','none');
								dragger.css('opacity','1');
								draggedAwayListNode.find('.shadowElement').remove();
								draggedAwayListNode=null;
							}
						});
					} else {
						draggedAwayListNode.find('.shadowElement').remove();
						newNode =  $('<li>'+draggedAwayListNode.html()+'</li>');
						var nodeId=draggedAwayListNode.attr('id');
						
						switch(dropAt) {
							case 'top':
								$(tree).prepend(newNode);
								pos = 'before';
								target = $(listElements[0]).attr('id');
								break;
							
							case 'above': 
								if (overElement==0) {
									$(tree).prepend(newNode);
								} else {
									atEl.before(newNode);
								}
								
								target=atEl.attr('id');
								pos='before';
								break;
							
							case 'under':
								//	Has child elements => put inside				exception: we moved out the last child from this element
								if((tmp=atEl.find('ul')).length>0 && (tmp.find('li').length>1 || tmp.find('li').first().css('display')!='none')) {
									target=tmp.children('li').first().attr('id');
									pos='before';
									tmp.prepend(newNode);
								} else {
									atEl.after(newNode);
									target=atEl.attr('id');
									pos='after';
								}
								break;
								
							case 'bottom':
								// useless value (might be the same as newNode), just to cause no error serverside
								target = $(listElements[listElements.length-1]).attr('id'); 
								pos = 'bottom';
								$(tree).append(newNode);
								break;
								
							case 'in':
								if((tmp=atEl.find('ul')).length==0) 
									atEl.append(tmp=$('<ul></ul>'));
								
								target=atEl.attr('id');
								pos = 'inside';
								tmp.append(newNode);
								break;
						}
						
						if (draggedAwayListNode.parent().children().length==1) {
							draggedAwayListNode.parent().remove();
						} else {
							draggedAwayListNode.remove();
						}
						
						
						
						newNode.attr('id',nodeId);
					}
					
					atElDiv.removeClass('insertBelow');
					atElDiv.removeClass('insertAbove');
					atElDiv.removeClass('insertIn');
					$(tree).removeClass('insertBelow');
					
					if(newNode) {
						bindElementEvents(newNode);
						dragger.css('display','none');
						draggedAwayListNode = null;
					}

					$(tree).find('li').each(function() {
						listElements[listElements.length]=this;
					});
					
					$(tree).find('.listImg').removeClass('last');
					
					checkforLast(tree);
					
					if(options.moved && newNode)
						options.moved(newNode.attr('id'), target, pos);
				}
			}
			
			function checkforLast(el) {
				$(el).children().last().children('.listImg').addClass('last');
				$(el).find('li > ul').each(function() { checkforLast(this) });
			}
			
			// Returns element index at given coordinate
			function isOverElement(x,y) {
				var of,el,cur=-1;
				
				for(var i=0; i<listElements.length; i++) {
					el=$(listElements[i]);
					of=el.offset();
					if(of.top <= y && of.left <= x &&
						of.left + el.outerWidth() >= x &&
						of.top  + el.outerHeight() >= y &&
						(cur==-1 || $(listElements[cur]).offset().left < of.left))
							cur = i;
				}
				
				return cur;
			}
			
			function bindElementEvents(el) {
				el.mousedown(function(event) {
					if (options.ignoreEventsOnElem) {
						$el = $(this).find(options.ignoreEventsOnElem);
						if ($el.length > 0) {
							$eloff = $el.offset();
							if (inside(event.pageX, $eloff.left, $eloff.left + $el.width())
							 && inside(event.pageY, $eloff.top, $eloff.top + $el.height())) {
								 return false;
							 }
						}
					}
					
					mouseDownOn = $(this).find('span');
					mouseDownCoords = { x: event.pageX, y: event.pageY };
					return false;
				});
			}
			
			function inside(val, x1, x2) {
				return val >= x1 && val <= x2;
			}
			
			function dist(p1, p2) {
				 return Math.sqrt( Math.pow(p2.x - p1.x, 2) + Math.pow(p2.y - p1.y, 2) )
			}
		}
	}
})( jQuery );