 (function($) {
	jQuery.fn.imageEdit = function(method) {
		var methods = {
			init : function(options) {
				// Already initialized
				if($(this).data('imageEdit') != undefined || $(this).data('imageEdit') != null)
					return false;
				
				var tb = new imageEditInstance(this, options);
				tb.init();
				$(this).data('imageEdit',tb);
				
				return this;
			},
			enable : function( ) { 
				$(this).data('imageEdit').enable();
			},
			refresh : function( ) { 
				$(this).data('imageEdit').refresh();
			},
			disable : function( ) { 
				$(this).data('imageEdit').disable();
			},
			destroy : function( ) { 
				$(this).data('imageEdit').destroy();
				$(this).data('imageEdit', null);
			},
			setsize: function( size ) {
				$(this).data('imageEdit').setsize(size);
			},
			setcropsize: function( size ) {
				$(this).data('imageEdit').setcropsize(size);
			},
			value: function( ) {
				return $(this).data('imageEdit').value();
			}
		};

		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.imageEdit' );
		} 
	};
	
	function imageEditInstance(elem, options) {
		var $editorArea = $(elem);
		var imageSize = { w: 0, h: 0 };
		var containerSize = { w: 0, h: 0 };
		var imgPos = { x: 0, y: 0 };
		var relImgPos = { x: 0, y: 0 };
		var fixEditor = false;
		var cropMode = true;
		var jcrop_api = null;
		var init_jcrop_delay = null;
		var sliderWidth;
		var valuesChanged = false;
		
		this.init = function() {
			containerSize = {
				w: $('.imageContainer', $editorArea).width(),
				h: $('.imageContainer', $editorArea).height()
			};
			
			imageSize = {
				w: options.currentSize.w,
				h: options.currentSize.h
			};
			
			options.originalSize.prop = options.originalSize.w / options.originalSize.h;
			
			$('.button.crop, .button.hand').click(toolbarButtonClick);
			$('.dimensions input[name="prevx"]', $editorArea).bind('keyup.imageEdit', manualSizeX );
			$('.dimensions input[name="prevy"]', $editorArea).bind('keyup.imageEdit', manualSizeY );
			
			$('.imageContainer', $editorArea).imageDrag({
				onDragComplete: function(pos) { 
					imgPos = pos;  
					relImgPos.x = Math.abs( pos.x / ( imageSize.w - containerSize.w ) );
					relImgPos.y = Math.abs( pos.y / ( imageSize.h - containerSize.h ) );
				}
			});
			
			setupJcrop();

			sliderWidth = $(".slider", $editorArea).width();
			var prop = options.currentSize.w / options.originalSize.w;
			$(".slider", $editorArea).slider({
				value: prop * sliderWidth,
				min: 0,
				max: sliderWidth,
				slide: onSlide,
				change: onSlide
			});
			
			updateImage();
			
			if($(".imageContainer", $editorArea).mousewheel) {
				$(".imageContainer", $editorArea).mousewheel(function(event, delta, deltaX, deltaY) {
					$slider = $(".slider", $editorArea);
					var max = $slider.slider( "option", "max" );
					var value = $slider.slider('value');
					$slider.slider('value', BoundBy(value + max / 100 * 2 * deltaY, 0, max));
				});
			}
		};
		
		this.refresh = function() {
		};
		
		this.destroy = function() {
			this.disable();
		};
		
		this.enable = function() {
		};
		
		this.disable = function() {

		};
		
		this.setsize = function(size) {
			if(size.w == 0 && size.h == 0) return;
			
			if(size.w == 0) {
				size.w = size.h * options.originalSize.prop;
			}
			if(size.h == 0) {
				size.h = size.w * options.originalSize.prop;
			}
			
			if(size.w != imageSize.w || size.h != imageSize.h)
				valuesChanged = true;
			
			imageSize = size;
			
			updateImage();
		};
		
		this.setcropsize = function(size) {
			if(jcrop_api) {
				var select = jcrop_api.tellSelect();
				select.w = size.w;
				select.h = size.h;
				
				
				jcrop_api.setSelect([select.x, select.y, select.x + select.w, select.y + select.h]);
			}
		}
		
		this.value = function() {
			return {
				changed: valuesChanged || jcrop_api.tellSelect().w > 0,
				size: imageSize,
				selection: jcrop_api.tellSelect()
			};
		};
		
		function manualSizeX() {
			var val = parseInt($(this).val())
			
			if (! val || val == imageSize.w) return;
			
			imageSize = {
				w: val,
				h: val / options.originalSize.prop
			}
			
			valuesChanged = true;
			
			$('.dimensions input[name="prevy"]', $editorArea).val(Math.round(imageSize.h));
			updateImage();
		}
		
		function manualSizeY() {
			var val = parseInt($(this).val())
			
			if (! val || val == imageSize.h) return;
			
			imageSize = {
				w: val * options.originalSize.prop,
				h: val
			}
			
			valuesChanged = true;
			
			$('.dimensions input[name="prevx"]', $editorArea).val(Math.round(imageSize.w));
			updateImage();
		}
		
		function onSlide(event, ui) {
			var prop = ui.value / sliderWidth;
			imageSize.w =  prop * options.originalSize.w;
			imageSize.h = prop * options.originalSize.h;
			valuesChanged = true;
			
			updateImage();
		}
		
		function updateImage() {
			imgPos.x = relImgPos.x * ( containerSize.w - imageSize.w );
			imgPos.y = relImgPos.y * ( containerSize.h - imageSize.h );
			
			if(containerSize.w > imageSize.w) imgPos.x = (containerSize.w - imageSize.w) / 2;
			if(containerSize.h > imageSize.h) imgPos.y = (containerSize.h - imageSize.h) / 2;
			
			$('.originalImg', $editorArea)
				.css('left', imgPos.x  + 'px')
				.css('top', imgPos.y  + 'px');
			
			$('img.original', $editorArea)
				.attr('width', imageSize.w)
				.attr('height', imageSize.h);
			
			$('.dimensions input[name="prevx"]', $editorArea).val(Math.round(imageSize.w));
			$('.dimensions input[name="prevy"]', $editorArea).val(Math.round(imageSize.h));

			// Re-Init jcrop if image size changes
			if (jcrop_api) {
				oldImageSize = jcrop_api.imageSize;
				oldSelection = jcrop_api.tellSelect();

				jcrop_api.setImageSize([imageSize.w, imageSize.h]);
				
				var prop = {
					x: imageSize.w / oldImageSize.w,
					y: imageSize.h / oldImageSize.h
				}
				if(oldSelection.w > 0) {
					jcrop_api.setSelect([
						oldSelection.x * prop.x,
						oldSelection.y * prop.y,
						(oldSelection.x + oldSelection.w) * prop.x,
						(oldSelection.y + oldSelection.h) * prop.y
					]);
				}
				jcrop_api.imageSize = {
					w: imageSize.w,
					h: imageSize.h
				};

			}
		}

		function toolbarButtonClick() {
			var state = $(this).hasClass('active');
			
			if(state) return;
			$(this).toggleClass('active', ! state);
			
			if($(this).hasClass('crop')) {
				$('.button.hand').removeClass('active');
				
				$('.imageContainer', $editorArea).imageDrag('disable');
				$('.originalImg > img.original', $editorArea).hide();
				
				$('.jcrop-holder', $editorArea).show();
				
				if(fixEditor) {
					setupJcrop();
				} else {
					jcrop_api.enable();
				}
				cropMode = true;
				
			} else {
				$('.button.crop').removeClass('active');
				jcrop_api.disable();
				cropMode = false;
				$('.jcrop-holder', $editorArea).hide();
			
				$('.originalImg > img.original', $editorArea).show();
				$('.imageContainer', $editorArea).imageDrag('refresh');
			}
		}
		
		function setupJcrop() {
			$('img.original', $editorArea).Jcrop({
				onChange: onCropChange
			}, function() {
				jcrop_api = this;
				
				this.imageSize = {
					w: imageSize.w,
					h: imageSize.h
				};
				
				if(options.crop) {
					jcrop_api.setSelect(options.crop);
				}
				
			});
		}
		
		function onCropChange(sel) {
			if(sel.w > 0) {
				$('.jcrop_selection', $editorArea).show();
				$('.jcrop_selection_value', $editorArea).text('(' + sel.w + ', ' + sel.h + ') @ ('+ sel.x + ', ' + sel.y + ')');
			} else {
				$('.jcrop_selection', $editorArea).hide();
			}
		}
		
    }
})( jQuery );