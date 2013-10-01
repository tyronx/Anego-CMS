gallery = ContentElement.extend({
	imageData: {},
	$imageDlg: null,
		
	onInit: function() {
		// Theses files will be loaded only once
		Core.loadCSS('modules/gallery/jquery.Jcrop.css');
		Core.loadCSS('styles/default/jui/jquery-ui.css');
		Core.loadJavascript('ld.jui');
		Core.loadJavascript('modules/gallery/jquery.filedrop.js');
		Core.loadJavascript('modules/gallery/jquery.Jcrop.modified.js');
		Core.loadJavascript('modules/gallery/jquery.imageDrag.js');
		Core.loadJavascript('modules/gallery/jquery.imageEdit.js');
		Core.loadJavascript('lib/jquery.mousewheel-3.0.4.pack.js');
	},

	galleryTemplate:
			'<div class="galleryEditor">' +
				'<div class="links">' + 
					'<a class="GalAddFilesLink" href="#">Add files</a> | <a class="GalSettingsLink" href="#">Settings</a>' +
				'</div>' + 
				'<div class="gallery pictureGrid"></div>' +
				'<div class="bothclear"></div>' +
				'<button type="button" name="mew" class="btn_cancelrte" style="min-width:150px">Close & Update page</button> <span class="imagecount"></span>' +
			'</div>', 
	
	imgTemplate: 
			'<div class="pic">'+
				'<div class="imageHolder">'+
					'<a href="#">'+
						'<img />' + 
						'<div class="progressHolder">'+
							'<div class="progress"></div>'+
						'</div>'+
					'</a>'+
					'<span class="uploading"></span>'+
				'</div>'+
			'</div>',
	
	imgEditTemplate: 
		'<div>' + 
			'<table border="0"><tr>' + 
				'<td>Title (for <a href="http://en.wikipedia.org/wiki/Search_engine_optimization">SEO</a>)<br><input type="text" name="title"></td>' +
				'<td style="padding-left: 20px;">Description<br><input type="text" name="description"></td>' +
			'</tr></table>' + 
			'<br>' +
			//'<div class="tabBox">Original</div>' + 
			'<div class="tabBox">Preview</div>' +
			'<div class="editArea">' +
				'<div class="toolbar">' + 
					'<div class="button crop active"><img class="toolbarIcon" src="modules/gallery/crop.png"></div>' +
					'<div class="button hand"><img class="toolbarIcon" src="modules/gallery/hand.png"></div>' +
				'</div>' + 
				'<div class="bothclear"></div>' +
				'<div class="imageContainer"><div class="originalImg"><img class="original" /></div></div>' +
				'<img style="display:none;" class="preview" />' +
				'<br>Preview Image size ' +
				'<table border="0" width="100%"><tr><td width="95">' +
				'<span class="dimensions"><input type="text" class="smallTextInput" name="prevx"> x <input type="text" class="smallTextInput" name="prevy"></span>' +
				'</td><td><div class="slider"> </div></td></tr></table>'+
				'Default sizes: <span class="defaultSizes"></span><br>' +
				'Crop sizes: <span class="cropSizes"></span><br><br>' +
				'<span style="display:none;" class="jcrop_selection">Crop selection: <span class="jcrop_selection_value"></span> ' +
			'</div>' +
		'</div>',
	
	settingsTemplate: 
		'<div class="settings"> ' +
			'<fieldset><legend>Global Settings</legend>' +
				'<p>Default preview size:<br> ' +
				'<select name="previewSize"> </select></p>' +
				'<p>Default original image size:<br> ' +
				'<select name="originalSize"> </select></p>' +
			'</fieldset>' +
		'</div>',
	
	onStartEdit: function(isNew) {
		var self = this;
		var $container = $('#' + self.containerId);
		
		self.html = $('#' + self.containerId).html();
			
		// Done by custom-ce call 
		$.post('index.php',{
			a: 'callce',
			fn: 'lp',
			mid: self.module_id,
			pid: self.page_id,
			elid: self.element_id
		}, function(data) {
			if ((aw = GetAnswer(data)) != null) {
				self.imageData = $.parseJSON(aw);
				// This is just a default value
				self.previewSize = {
					w: 160,
					h: 120
				};
				
				for(var i=0; i < self.imageData.sizes.length; i++) {
					if(self.imageData.sizes[i].idx == self.imageData.preview_default_size_id)
						self.previewSize = {
							w: self.imageData.sizes[i].width,
							h: self.imageData.sizes[i].height
						};
						
					if(self.imageData.sizes[i].idx == self.imageData.original_default_size_id)
						self.originalSize = {
							w: self.imageData.sizes[i].width,
							h: self.imageData.sizes[i].height
						};
				}
				
				
				var $galleryEditor = $(self.galleryTemplate);
				var $imageGrid = $('.pictureGrid', $galleryEditor);
				var $button = $('button', $galleryEditor);
				
				$('span.imagecount', $galleryEditor).html(self.imageData.count + __(' Images in this gallery'));
				
				$('.GalAddFilesLink', $galleryEditor).click(function() { return self.addFiles(); });
				$('.GalSettingsLink', $galleryEditor).click(function() { return self.settings(); });
				
				$container.html($galleryEditor);
				
				$button.click(function() {
					self.endEdit();
					
					if(self.$imageDlg) {
						self.$imageDlg.closeDialog();
					}
					// Update the page server side
					$.get('index.php', { a: 'rp', page: self.page_id} );
				});
				
				var $image, rnd, pic;
				for(var i = 0; i < self.imageData.pictures.length; i++) {
					pic = self.imageData.pictures[i];
					
					// Add random to force reload
					rnd = Math.round(Math.random() * 100000);
					
					$image = $(self.imgTemplate);
					
					
					$image.data('idx', pic.idx);

					$('img', $image)
						.attr('src', self.imageData.path + '/' + pic.filename_preview + '?' + rnd)
						.attr('alt', pic.title);
					$('img', $image)
						.attr('title', pic.title);
					
					$('.progressHolder', $image).remove();
					$('.uploading', $image).remove();
					
					
					$('a', $image)
						.click(function() {
							// Opens the image editor dialog
							self.openImageDialog($(this));
							return false;
						})
						.data('picData', pic)
						.css('width', self.previewSize.w)
						.css('height', self.previewSize.h)
						.attr('title', pic.description);

					$imageGrid.append($image);
				}
				
				$imageGrid.sortable({
					update: function(event, ui) {
						var imgData = $('a', ui.item).data('picData');
						
						$.post('index.php', {
							a: 'callce',
							fn: 'mp',
							mid: self.module_id,
							pid: self.page_id,
							elid: self.element_id,
							picid: imgData.idx,
							picidxLeft: ui.item.prev().data('idx'),
							picidxRight: ui.item.next().data('idx')
						}, function(data) {
							if(aw = GetAnswer(data)) {
								
							}
						});
					}
				});
				
				if(isNew) {
					self.settings();
				}
			}
			
			Core.callHooks('afterContentElementEditLoad', { contentElement: self });
		});
		
		$(document).filedrop({
			// The name of the $_FILES entry:
			paramname: 'pic',
			data: { 
				a: 'callce',           // send POST variables
				fn: 'up',
				mid: self.module_id,
				pid: self.page_id,
				elid: self.element_id
			},
			maxfiles: 20,
			maxfilesize: 4,
			url: 'index.php',
			
			uploadFinished: function(i, file, response) {
				$img = $.data(file, 'preview');
				
				$img.addClass('done');
				$('.progressHolder', $img).fadeOut();
				$('.uploading', $img).fadeOut();
				
				// response is the JSON object that post_file.php returns
				if(GetAnswer(response.status)) {
					self.imageData.count++;
					$('span.imagecount', $img.parents('.galleryEditor').first()).html(self.imageData.count + __(' Images in this gallery'));
					
					$img.find('img').attr('src', response.preview);
					$('a', $img)
						.data('picData', response.pic)
						.click(function() {
							// Opens the image editor dialog
							self.openImageDialog($(this));
							return false;
						})
						.attr('title', response.pic.description);
				} else {
					$img.remove();
				}
			},
			
			error: function(err, file) {
				switch(err) {
					case 'BrowserNotSupported':
						alert('Your browser does not support HTML5 file uploads!');
						break;
					case 'TooManyFiles':
						alert('Too many files! Please select 20 at most! (configurable)');
						break;
					case 'FileTooLarge':
						alert(file.name+' is too large! Please upload files up to 4mb (configurable).');
						break;
					default:
						break;
				}
			},
			
			// Called before each upload is started
			beforeEach: function(file) {
				if(!file.type.match(/^image\//)){
					alert('Only images are allowed!');
					
					// Returning false will cause the
					// file to be rejected
					return false;
				}
			},
			
			uploadStarted:function(i, file, len){
				createImage(file);
			},
			
			progressUpdated: function(i, file, progress) {
				$.data(file, 'preview').find('.progress').css('width', progress + '%');
			}
			 
		});
		
		function createImage(file){
			var $preview = $(self.imgTemplate), $image = $('img', $preview);
			var reader = new FileReader();
			
			$image.css('max-width', self.previewSize.w + 'px');
			$image.css('max-height', self.previewSize.h + 'px');
			
			$('a', $preview).css('width', self.previewSize.w + 'px');
			$('a', $preview).css('height', self.previewSize.h + 'px');
			
			reader.onload = function(e) {
				// e.target.result holds the DataURL which
				// can be used as a source of the image:
				$image.attr('src', e.target.result);
			};
			
			// Reading the file as a DataURL. When finished,
			// this will trigger the onload function above:
			reader.readAsDataURL(file);
			
			$('#' + self.containerId + ' .pictureGrid').append($preview);
			
			// Associating a preview container
			// with the file, using jQuery's $.data():
			$.data(file, 'preview', $preview);
		}

		
		
		return true;
	},
	
	openImageDialog: function($link) {
		var self = this;
		var containerSize = { w: 400, h: 250 };
		var picData = $link.data('picData');
		var $previewImage = $link.parent().parent();
		var $dlgContent = $(self.imgEditTemplate);
		var $editorArea = $('.editArea', $dlgContent);
		
		for(var i=0; i < self.imageData.sizes.length; i++) {
			var size = self.imageData.sizes[i];
			var $imgsize = $('<a class="imgSize" href="#">' + size['width'] + 'x' + size['height'] + '</a>');
			var $cropsize = $('<a class="cropSize" href="#">' + size['width'] + 'x' + size['height'] + '</a>');
			
			$imgsize.data('size', { w: parseInt(size['width']), h: parseInt(size['height']) });
			$('.defaultSizes', $dlgContent).append($imgsize);
			$cropsize.data('size', { w: parseInt(size['width']), h: parseInt(size['height']) });
			$('.cropSizes', $dlgContent).append($cropsize);
			
			if(i < self.imageData.sizes.length - 1) {
				$('.defaultSizes', $dlgContent).append(', ');
				$('.cropSizes', $dlgContent).append(', ');
			}
			
			$imgsize.click(function() {
				$editorArea.imageEdit('setsize', $(this).data('size'));
				return false;
			});
			
			$cropsize.click(function() {
				$editorArea.imageEdit('setcropsize', $(this).data('size'));
				return false;
			});
		}
		
		if(self.$imageDlg) {
			self.$imageDlg.closeDialog();
			self.$imageDlg = null;
		}
		
		var dlgSettings = {
			title: 'Image settings',
			content: $dlgContent,
			buttons: { }
		};

		dlgSettings.buttons['Save'] = function() {
			var $selfDlg = this;
			$selfDlg.waitResponse();
			var aw;
			var resizeSettings = $editorArea.imageEdit('value');
			
			$.post('index.php', {
				a: 'callce',
				fn: 'save',
				mid: self.module_id,
				pid: self.page_id,
				elid: self.element_id,
				picid: picData.idx,
				description: $('input[name="description"]', self.$imageDlg).val(),
				title: $('input[name="title"]', self.$imageDlg).val(),
				resizeSettings: resizeSettings
			}, function(data) {
				if(aw = GetAnswer(data)) {
					$('a', $previewImage).attr('title', $('input[name="description"]', self.$imageDlg).val());
					
					var title = $('input[name="title"]', self.$imageDlg).val();
					$('img', $previewImage)
						.attr('alt', title)
						.attr('title', title);
					
					if(resizeSettings.changed) {
						var rnd = Math.round(Math.random() * 100000);
						$('img', $previewImage).attr('src', $('img', $previewImage).attr('src').replace(/^(.*\.\w+)(\?.*)?$/, '$1?' + rnd) );
					}
					
					for(var i=0; i < self.imageData.pictures.length; i++) {
						if(self.imageData.pictures[i].filename == picData.filename) {
							self.imageData.pictures[i] = $.parseJSON(aw);
							$link.data('picData', self.imageData.pictures[i]);
						}
					}
					
					$selfDlg.closeDialog();
				} else {
					$selfDlg.endWait();
				}
			});
		};
		
		dlgSettings.buttons['Delete Picture'] = function() {
			var dlg = this;
			
			if (confirm('Really Delete?')) {
				dlg.waitResponse();
				$.post('index.php', {
					a: 'callce',
					fn: 'dp',
					mid: self.module_id,
					pid: self.page_id,
					elid: self.element_id,
					picid: picData.idx
				}, function(data) {
					dlg.endWait();
					if(aw = GetAnswer(data)) {
						dlg.closeDialog();
						$previewImage.remove();
					}
				});
			}
		};
		
		dlgSettings.buttons['Cancel'] = function() {
			this.closeDialog();
		};
		
		self.$imageDlg = OpenDialog(dlgSettings);

		$('img.original', $editorArea).attr('src', self.imageData.path + '/' + picData.filename);
		$('input[name="description"]', $dlgContent)
			.val(picData.description)
			.keyup(function() {
				$(this).data('edited', true);
			});
		$('input[name="title"]', $dlgContent)
			.val(picData.title)
			.data('edited', false)
			.keyup(function() {
				var $desc = $('input[name="description"]', $dlgContent);
				if (! $desc.data('edited'))
					$desc.val($(this).val());
			});
		
		$('.imageContainer', $editorArea).css('width', containerSize.w + 'px');
		$('.imageContainer', $editorArea).css('height', containerSize.h + 'px');
		
		var imageEditChange = false;
		$('img.original', $editorArea).load(function() {
			setTimeout(function() {
				$editorArea.imageEdit({ 
					currentSize: {
						w: parseInt( parseInt(picData.prev_w) || $('img', $previewImage).width() ),
						h: parseInt( parseInt(picData.prev_h) || $('img', $previewImage).height() )
					},
					originalSize: {
						w: $('img.original', $editorArea).width(),
						h: $('img.original', $editorArea).height()
					},
					crop: [
						parseInt(picData.prev_cropx),
						parseInt(picData.prev_cropy),
						parseInt(picData.prev_cropx) + parseInt(picData.prev_cropw),
						parseInt(picData.prev_cropy) + parseInt(picData.prev_croph),
					],
					change: function() {
						if (! imageEditChange) {
							$('input.dlgButton', self.$imageDlg).first().attr('value', 'Crop, Resize & Save');
							imageEditChange = true;
						}
					}
				});
			}, 0);
		});
		
	},
	
	onEndEdit: function() {
		var self = this;
		
		$.post('index.php',{
			a: 'gcec',
			mid: self.module_id,
			pid: self.page_id,
			elid: self.element_id
		}, function(data) {
			var aw;
			if(aw = GetAnswer(data)) 
				$('#' + self.containerId).html(aw);
			
				$('.gallery img.thumbnail', $('#' + self.containerId)).each(function() {
					var rnd = Math.round(Math.random() * 100000);
					$(this).attr('src', $(this).attr('src') + '?' + rnd);
				});
		});
		
		$(document).filedrop('destroy');
		
		return true;
	},
	
	settings: function() {
		var self = this;
		
		var $settings = $(self.settingsTemplate);
		
		var prevSelected = '', origSelected = '';
		for(var i=0; i < self.imageData.sizes.length; i++) {
			size = self.imageData.sizes[i];
			
			if (size.idx == self.imageData.original_default_size_id) {
				origSelected = 'selected';
			}
			if (size.idx == self.imageData.preview_default_size_id) {
				prevSelected = 'selected';
			}
			
			$('select[name="previewSize"]', $settings).append('<option value="' + size.idx + '"' + prevSelected + '> ' + size.name + '(' + size.width + ' x ' + size.height + ')</option>');
			$('select[name="originalSize"]', $settings).append('<option value="' + size.idx + '"' + origSelected + '> ' + size.name + '(' + size.width + ' x ' + size.height + ')</option>');
			prevSelected = '', origSelected = '';
		}
		
		OpenDialog({
			title: "Settings",
			content: $settings,
			buttons: BTN_SAVECANCEL,
			ok_callback: function() {
				var dlg = this;
				
				$.post('index.php',{
					a: 'callce',
					fn: 'us',
					mid: self.module_id,
					pid: self.page_id,
					elid: self.element_id,
					previewSize: $('select[name="previewSize"]', $settings).val(),
					originalSize: $('select[name="originalSize"]', $settings).val()
				}, function(data) {
					dlg.endWait();
					if(GetAnswer(data)) {
						self.imageData.preview_default_size_id = $('select[name="previewSize"]', $settings).val();
						self.imageData.original_default_size_id = $('select[name="originalSize"]', $settings).val();
						dlg.closeDialog();
					}
				});
				
				dlg.waitResponse();
			}
		});
		
		return false;
	},
	
	addFiles: function() {
		OpenDialog({
			title: "Add files",
			content: '<b>Hint:</b> You can just Drag&Drop Images into your browser window!<br>(Requires a modern browser)<br><br><input type="file" name="picupload" multiple /> <= not implemented yet'
		});
		//OpenDialog("<div id)
		/*function makeFileList() {
			var input = document.getElementById("filesToUpload");
			var ul = document.getElementById("fileList");
			while (ul.hasChildNodes()) {
				ul.removeChild(ul.firstChild);
			}
			for (var i = 0; i < input.files.length; i++) {
				var li = document.createElement("li");
				li.innerHTML = input.files[i].name;
				ul.appendChild(li);
			}
			if(!ul.hasChildNodes()) {
				var li = document.createElement("li");
				li.innerHTML = 'No Files Selected';
				ul.appendChild(li);
			}*/
		return false;
	}
	
});