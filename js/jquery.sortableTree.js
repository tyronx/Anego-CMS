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
			var draggerIcon = $('<img src="' + options.dragIcon + '" alt="" class="iconDrop"> ');
			var $shadowNode;
			var curTree;
			
			if (! options) options = {};
				
			this.init = function() {
				dragger = $('div.dragger.draggedElement', tree);
				if (dragger.length == 0) {
					$(tree).first().append(dragger = $('<div style="display:none;" class="dragger draggedElement"></div>'));
				}
				
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
					curTree = closestTree(event.pageX, event.pageY);
					
					mouseDownOn = null;
					dragging=true;

				}
				
				if(dragging) {
					var ov = isOverElement(event.pageX,event.pageY);
					var ovEl;
					var dy;
					dragger.offset({left:event.pageX + 5,top:event.pageY + 15});
					curTree = closestTree(event.pageX, event.pageY);
					
					// Element is over, above or below an element
					if(ov != -1) {
						dropAt = getElementDropAtElement(ov, event);
						ovEl = $(listElements[ov]).find('span.listEl');
						
						tree.removeClass('insertAbove insertIn insertBelow');
						tree.find('li').removeClass('insertAbove insertIn insertBelow');
						
						switch(dropAt) {
							case 'above':
								ovEl.first().addClass('insertAbove');
								dragger.find('img.iconDrop').attr('class','iconDrop iconDropBetween');
							break;
								
							case 'under':
								ovEl.first().addClass('insertBelow');
								dragger.find('img.iconDrop').attr('class','iconDrop iconDropBetween');
							break;
								
							case 'in':
								ovEl.first().addClass('insertIn');
								dragger.find('img.iconDrop').attr('class','iconDrop iconDropAdd');
							break;
						}
						
					// Element is outside the tree
					} else {
						dropAt = getElementDropAtOutside(event.pageX, event.pageY);
						
						switch(dropAt) {
							case 'top':
								$(curTree).addClass('insertAbove');
								dragger.find('img.iconDrop').attr('class','iconDrop iconDropOver');
							break;
							
							case 'bottom':
								$(curTree).addClass('insertBelow');
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
			
			function getElementDropAtOutside(x, y) {
				var result = 'none';
				
				// Element is at bottom of the tree
				$(tree).each(function() {
					// Within 50 px below the tree (y-coordinate)
					if(	inside(y, $(this).offset().top + $(this).innerHeight(), $(this).offset().top + $(this).innerHeight() + 50)
						// No more than 25px outside the this (x-coordinate)
						&& inside(x, $(this).offset().left - 25, $(this).offset().left + $(this).width() + 25 )
					) {
						result='bottom';
						return false;
					}
					
					// Element is at bottom of the this
						
					// Within 50 px above the this (y-coordinate)
					if(inside(y, $(this).offset().top - 50, $(this).offset().top)
						// No more than 25px outside the this (x-coordinate)
						&& inside(x, $(this).offset().left - 25, $(this).offset().left + $(this).width() + 25 )
					) {
						result='top';
						return false ;
					}
					
				});
					
				return result;
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
								$(curTree).prepend(newNode);
								pos = 'before';
								target = $(listElements[0]).attr('id');
								break;
							
							case 'above': 
								if (overElement==0) {
									$(curTree).prepend(newNode);
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
								$(curTree).append(newNode);
								break;
								
							case 'in':
								if((tmp = atEl.find('ul').first()).length==0) 
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
						options.moved(newNode.attr('id'), target, pos, curTree);
				}
			}
			
			function checkforLast(el) {
				$(el).children().last().children('.listImg').addClass('last');
				$(el).find('li > ul').each(function() { checkforLast(this) });
			}
			
			// Returns element index at given coordinate
			function isOverElement(x,y) {
				var of,el,cur=-1;
				
				for(var i=0; i < listElements.length; i++) {
					el = $(listElements[i]);
					of = el.offset();
					
					if (posInElem(x, y, el) && (cur==-1 || $(listElements[cur]).offset().left < of.left)) {
						cur = i;
					}
				}
				
				return cur;
			}
			
			function closestTree(x, y) {
				var cur = null;
				
				$(tree).each(function() {
					if (posInElem(x, y, this)) {
						cur = this;
					}
				});
				
				// Maybe outside a quad somehwere?
				if (!cur && getElementDropAtOutside(x, y) != 'none') {
					
					$(tree).each(function() {
						of = $(this).offset();
						if (posInRect(x, y, of.left - 25, of.top - 50, $(this).outerWidth() + 50, $(this).outerHeight() + 100)) {
							
							cur = this;
							return false;
						}
					});
				}
				
				return cur;
			}
			
			function bindElementEvents(el) {
				el.mousedown(function(event) {
					if (options.ignoreEventsOnElem) {
						$el = $(this).find(options.ignoreEventsOnElem);
						if ($el.length > 0) {
							if (posInElem(event.pageX, event.pageY, $el)) {
								return false;
							}
						}
					}
					
					mouseDownOn = $(this).find('span');
					mouseDownCoords = { x: event.pageX, y: event.pageY };
					return false;
				});
			}
			
			function posInElem(x, y, elem) {
				var of = $(elem).offset();
				return posInRect(x, y, of.left, of.top, $(elem).outerWidth(), $(elem).outerHeight());
			}
			
			function posInRect(x, y, qx, qy, qw, qh) {
				//console.log(x + ' >= ' + qx + ' && ' + x + ' <= ' + (qx+qw) + ' && '+ y + ' >= ' + qy + ' && ' + y + ' <= ' + (qy+qh) +  '       => ' + (x >= qx && x <= qx+qw && y>=qy && y<=qy+qh));
				return x >= qx && x <= qx+qw && y>=qy && y<=qy+qh;
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