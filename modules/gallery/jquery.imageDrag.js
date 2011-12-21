(function($) {
	jQuery.fn.imageDrag = function(method) {
		var methods = {
			init : function(options) {
				// Already initialized
				if($(this).data('imageDrag') != undefined || $(this).data('imageDrag') != null)
					return false;
				
				var tb = new imageDragInstance(this, options);
				tb.init();
				$(this).data('imageDrag',tb);
				
				return this;
			},
			enable : function( ) { 
				$(this).data('imageDrag').enable();
			},
			refresh : function( ) { 
				$(this).data('imageDrag').refresh();
			},
			disable : function( ) { 
				$(this).data('imageDrag').disable();
			},
			destroy : function( ) { 
				$(this).data('imageDrag').destroy();
				$(this).data('imageDrag', null);
			}
		};

		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.imageDrag' );
		} 
	};
	
	function imageDragInstance(elem, options) {
		var clicked = false;
		var start_x;
		var start_y;
		var $container = $(elem);
		var $img;
		var ready = false;
		var scrollableArea = {};
		var imgPos = { x:0, y: 0 };
				
		this.init = function() {
			// hide the image & add as bg image
			$img = $('img', $container).first();
			
			$mover = $container.children().first();
			
			var src = $img.attr('src');
			$img.attr('src','');
			
			$img.load(function() {
				setScrollableArea();
				ready = true;
			});
			
			$img.attr('src',src);
			
			imgPos.x = parseInt($mover.css('left'));
			imgPos.y = parseInt($mover.css('top'));
			//this.enable();
		};
		
		this.refresh = function() {
			this.disable();
			setScrollableArea();
			this.enable();
		};
		
		function setScrollableArea() {
			var cntDim = { w: $container.width(), h: $container.height() }
			var movDim = { w: $mover.width(), h: $mover.height() }
			var spanX = cntDim.w - movDim.w;
			var spanY = cntDim.h - movDim.h;
			
			var sideX = ['left', 'right'];
			if(spanX > 0) sideX = sideX.reverse();
			
			scrollableArea[sideX[0]] = spanX;
			scrollableArea[sideX[1]] = 0;
			
			sideY = ['top', 'bottom'];
			if(spanY > 0) sideY = sideY.reverse();
			
			scrollableArea[sideY[0]] = spanY;
			scrollableArea[sideY[1]] = 0;
		}
		
		function sign(x) {
			return x / Math.abs(x);
		}
		
		this.destroy = function() {
			this.disable();
		};
		
		this.enable = function() {
			$mover.addClass('grabCursor');

			$mover.bind('mousedown.imageDrag', imageDragMouseDown);
			$(document).bind('mouseup.imageDrag', imageDragMouseUp);
			$(document).bind('mousemove.imageDrag', imageDragMouseMove);
		};
		
		this.disable = function() {
			$mover.removeClass('grabCursor');
			
			$mover.unbind('.imageDrag');
			$(document).unbind('.imageDrag');
		};

		function imageDragMouseDown(e) {
			if (! ready) return false;
			
			clicked = true;
			
			start_x = Math.round(e.pageX - $container.offset().left) - imgPos.x; 
			start_y = Math.round(e.pageY - $container.offset().top) - imgPos.y;

			return false;
		}
		
		function imageDragMouseUp(e) {
			if (! ready) return false;
			
			clicked = false;
			
			if(options.onDragComplete) {
				options.onDragComplete(imgPos);
			}
			
			return false;
		}
		
		function imageDragMouseMove(e) {
			if (! ready) return false; 
			
			if(clicked) {
				imgPos = {
					x: BoundBy(Math.round(e.pageX - $container.offset().left) - start_x, scrollableArea.left, scrollableArea.right),
					y: BoundBy(Math.round(e.pageY - $container.offset().top) - start_y, scrollableArea.top, scrollableArea.bottom)
				};
				
				$mover.css('top', imgPos.y + 'px')
					  .css('left', imgPos.x + 'px');
			}
			
			return false;
		}
    }
})( jQuery );