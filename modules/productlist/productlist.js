productlist = ContentElement.extend({
	
	editProductsTemplate:
		'<div class="productsEditor">' +
			'<div class="links">' + 
				'<a class="createproduct" href="#">New Product</a> | <a class="productssettings" href="#">Settings</a>' +
			'</div>' + 
			'<div class="products"></div>' +
			'<div class="bothclear">&nbsp;</div>' +
			'<button type="button" name="mew" class="btn_close" style="min-width:150px">Close and update page</button> <span class="productscount"></span>' +
		'</div>', 

	productTemplate:
		'<div class="product">' +
			'<a href="#editproduct">' +
				'<div class="productpicture"></div>'+
				'<div class="producttitle"></div>' +
			'</a>' +
		'</div>',
	
	settingsTemplate: 
		'<div class="settings">' +
			'<label for="productswidth">Width of the Productlist:</label><br> <input size="3" type="text" name="productswidth" id="productswidth"><br><br> ' +
			'<label for="productwidth">Width and Height of each Product:</label><br> <input size="3" type="text" name="productwidth" id="productwidth"> ' +
			'x <input size="3" type="text" name="productheight" id="productheight"> ' +
			'<br><br><label for="producthorispacing">Horizontal and Vertical spacing between products:</label><br> <input size="3" type="text" name="producthorispacing" id="producthorispacing"> ' +
			'x <input size="3" type="text" name="productvertispacing" id="productvertispacing"> ' +
		'</div>',
	
	serverresponse: [],
	
	onStartEdit: function() {
		var self = this;
		var $container = $('#'+self.containerId);
		
		self.html = $container.html();
		
		var $productsEditor = $(self.editProductsTemplate);
		
		$('.createproduct', $productsEditor).click(function() { return self.editProduct(); });
		$('.productssettings', $productsEditor).click(function() { return self.openSettings(); });
				

		
		$container.html($productsEditor);
		$container.find('.btn_close').click(function() {
			// Update the page server side
			$.post('index.php', {
				a: 'gcec',
				mid: self.module_id,
				pid: self.page_id,
				elid: self.element_id
			}, function (response) {
				if (aw = GetAnswer(response)) {
					self.html = aw;
					$container.html(aw);
				} else {
					$container.html(self.html);
				}

				self.endEdit();
				$.get('index.php', { a: 'rp', page: self.page_id} );
			});
		});
		
		self.loadProducts(function() { Core.callHooks('afterContentElementEditLoad', { contentElement: self }); });
		
		return true;
	},
	
	onEndEdit: function() {
		//$('#' + this.editorId).tinymce().hide();
		$('#' + this.containerId).html(this.html);
		
		return true;
	},
	
	openSettings: function() {
		var self = this;
		
		
		$.ajax({
			type : 'POST',
			url : 'index.php',
			data: { 
				a: 'callce',
				mid: self.module_id,
				elid: self.element_id,
				pid: self.page_id,
				fn: 'gs'
			}, success: function(data) {
				var aw;
				// alerts any errors that might have happened
				if (aw = GetAnswer(data)) {
					
					settings = $.parseJSON(aw);
					
					$settingsContent = $(self.settingsTemplate);
					
					$('input#productswidth', $settingsContent).val(settings.productswidth),
					$('input#productwidth', $settingsContent).val(settings.productwidth),
					$('input#productheight', $settingsContent).val(settings.productheight),
					$('input#producthorispacing', $settingsContent).val(settings.producthorispacing),
					$('input#productvertispacing', $settingsContent).val(settings.productvertispacing)
					
					OpenDialog({
						title: "Settings",
						buttons: BTN_SAVECANCEL,
						content: $settingsContent,
						
						ok_callback: function() {
							var $dlg = this;
							this.waitResponse();
							
							$.ajax({
								type : 'POST',
								url : 'index.php',
								data: { 
									a: 'callce',
									mid: self.module_id,
									elid: self.element_id,
									pid: self.page_id,
									fn: 'ss',
									recache: true,
									productswidth: $('input#productswidth').val(),
									productwidth: $('input#productwidth').val(),
									productheight: $('input#productheight').val(),
									producthorispacing: $('input#producthorispacing').val(),
									productvertispacing: $('input#productvertispacing').val()
								},
								success: function(data) {
									$dlg.endWait();
									// alerts any errors that might have happened
									if (GetAnswer(data)) {
										$dlg.closeDialog();
										self.loadProducts();
									}
								}
							});
						}
					});
					
					
				}
			}
		});
	},
	
	
	loadProducts: function(callback) {
		var self = this;
		var $container = $('#'+self.containerId);
		
		$('.products', $container).html('');
		
		$.post('index.php', {
			a: 'callce',
			fn: 'lp',
			mid: self.module_id,
			pid: self.page_id,
			elid: self.element_id
		}, function(response) {
			if ((aw = GetAnswer(response)) != null) {
				var response = $.parseJSON(aw);
				self.serverresponse = response;
				
				if (response.productswidth > 0) {
					$('.products', $container).width(response.productswidth);
				}

				
				
				for (var i=0; i < response.products.length; i++) {
					var product = response.products[i];
					
					var $product = $(self.productTemplate);
					
					$product.attr('style', response.css);
					
					if (product.filename) {
						$('.productpicture', $product).html('<img src="' + product.filename + '">');
					}
					
					$('.producttitle', $product).html(product.title.replace(/\n/g,"<br>"));
					$('a[href^="#editproduct"]', $product).attr('href','#editproduct-' + product.idx);
					
					$('.products', $container).append($product);
				}
				
				
				$('.products a[href^="#editproduct"]').click(function() {
					productid = $(this).attr('href').substr('#editproduct-'.length);
					
					self.editProduct(productid);
				});
				
			}
			
			if (callback) callback();
		});
		
	},
	
		
	editProduct: function(productid) {
		var self = this;
		var productimage = { name: '' };
		var productimagedata;
		
		
		var dlgSettings = {
			title: productid ? "Edit product" : "Create new product",
			buttons: BTN_SAVECANCEL,
			content: 'Name:<br><textarea style="width:250px; height:50px;" id="productName" value=""></textarea><br>' +
					'Image:<br><input id="productimage" type="file"><br><br>' +
					'<input type="checkbox" name="createpage" id="createpage" value="1" checked="checked"> <label for="createpage">Create a new page for this product</label><br><br>' +
					'<div class="productdesc">Description: <textarea style="width:100%" id="productDescription"></textarea></div>',
			buttons: {}
		};
		
		dlgSettings.buttons['Save'] = function() {
			var $dlg = this;
			this.waitResponse();
							
			$.ajax({
				type : 'POST',
				url : 'index.php',
				data: { 
					a: 'callce',
					mid: self.module_id,
					elid: self.element_id,
					pid: self.page_id,
					fn: 'sp',
					recache: true,
					description: $('#productDescription').tinymce().getContent(),
					title: $('#productName').val(),
					createpage: $('#createpage').is(':checked') ? $('#createpage').val() : 0 ,
					productid: productid,
					createnew: productid ? 0 : 1,
					filename: productimage.name,
					filedata: productimagedata 
				},
				success: function(data) {
					$dlg.endWait();
					// alerts any errors that might have happened
					if (GetAnswer(data)) {
						$('#productDescription').tinymce().hide();
						$dlg.closeDialog();
						self.loadProducts();
					}
				}
			});	
		};
			
		
		dlgSettings.buttons['Cancel'] = function() {
			$('#productDescription').tinymce().hide();
			this.closeDialog();
		};
		

		dlgSettings.buttons['Delete Product'] = function() {
			var dlg = this;
			if (confirm('Really delete Product?')) {
				dlg.waitResponse();
				$.post('index.php', {
					a: 'callce',
					fn: 'dp',
					mid: self.module_id,
					pid: self.page_id,
					elid: self.element_id,
					productid: productid
				}, function(data) {
					dlg.endWait();
					if(aw = GetAnswer(data)) {
						dlg.closeDialog();
						self.loadProducts();
					}
				});
			}
		};
		
		OpenDialog(dlgSettings);
		
		this.tinyfy("productDescription");
		
		document.getElementById('productimage').addEventListener('change', function(evt) {
			productimage = evt.target.files[0]; 

			if (productimage) {
				var r = new FileReader();
				r.onload = function(e) {
					productimagedata = e.target.result;
					if (productimage.type.indexOf('image') == -1) {
						alert("Please select only images");
						$('#productimage').val('');
					}
				}
				r.readAsDataURL(productimage);
			} else { 
				alert("Failed to load file");
			}
	
		}, false);
	
		if (productid) {
			var product;
			for (var i = 0; i < self.serverresponse.products.length; i++) {
				if (self.serverresponse.products[i].idx == productid)
					product = self.serverresponse.products[i];
			}
			
			
			$('#productName').val(product.title);
			$('#productDescription').html(product.syncdescription);
			
			if (product.page_idx > 0) {
				$('label[for="createpage"]').text('Keep and Update product page');
				$('#createpage').attr('value', '2');
			} else {
				$('#createpage').removeAttr('checked');
			}
		}
		
		$('input#createpage').change(function() {
			$('div.productdesc').toggle($(this).is(':checked'));
		});
		$('input#createpage').trigger('change');

		
	},
	
	
	
	tinyfy: function(elementid) {
		var templates = [
			{
				title : "Two columns",
				src : "lib/tiny_mce/templates/2column.htm",
				description : "A template that defines two colums, each one with a title, and some text."
			}, {
				title : "Left side bar",
				src : "lib/tiny_mce/templates/leftsidebar.htm",
				description : "A 2 column template with a bar on the left side."
			}, {
				title : "Right side bar",
				src : "lib/tiny_mce/templates/rightsidebar.htm",
				description : "A 2 column template with a bar on the right side."
			}, {
				title : "Text and Table",
				src : "lib/tiny_mce/templates/texttable.htm",
				description : "A title with some text and a table."
			}
		];
		
		// Per style defined templates
		if(typeof tinymceTemplates != 'undefined') {
			templates = templates.concat(tinymceTemplates);
		}
		
		var mcelang='en';
		if(anego.language=='ger') /* language var defined by Anego */
			mcelang='de';
		
		var settings = {
			script_url : anego.path + 'lib/tiny_mce/tiny_mce_gzip.php',
			mode : 'none',
			theme : "advanced",	
			plugins : "advimagescale,table,tablegrid,advlink,preview,media,searchreplace,contextmenu,paste,fullscreen,xhtmlxtras,inlinepopups,phpimage,anegofileupload,template",
			height : 350,
			theme_advanced_buttons1 : "bold,italic,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect,|,forecolor,backcolor",
			theme_advanced_buttons2 : "pastetext,|,search,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,|,charmap,template,media,phpimage,anegofileupload,|,hr,removeformat,|,sub,sup",
			theme_advanced_buttons3 : "tablegrid,|,row_props,cell_props,|,row_before,row_after,delete_row,|,col_before,col_after,delete_col,|,split_cells,merge_cells,|,preview,code,fullscreen",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,
			theme_advanced_font_sizes : "7pt,8pt,9pt,10pt,11pt,12pt,13pt,14pt,15pt,17pt,19pt,21pt,23pt,25pt",
			theme_advanced_styles : "Gray Frame=grayframe;Small padding right=smallpadright;Small padding left=smallpadleft",
			language : mcelang, 
			advlink_styles: "Spam Protected E-Mail Address=hiddenEmail",
			paste_text_use_dialog: true,
			accessibility_warnings : false,
			advimagescale_noresize_all: true,
			extended_valid_elements: "form[name|id|action|method|enctype|accept-charset|onsubmit|onreset|target],input[id|name|type|value|size|maxlength|checked|accept|src|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|onkeyup|onkeydown|required|style],textarea[id|name|rows|cols|maxlength|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|onkeyup|onkeydown|required|style],option[name|id|value|selected|style],select[id|name|type|value|size|maxlength|checked|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|multiple|style]",	
			button_tile_map : true,
			content_css : anego.path + "styles/" + anego.style + "/text.css", /* style var defined by Anego */
			external_link_list_url : anego.path + "modules/richtext/linkList.js.php",
			external_image_list_url : anego.path + "modules/richtext/imageList.js.php",
			convert_urls : false,
			template_templates : templates
		};
		
		if (typeof tinymceRichtextSettings == "object")
			settings = $.extend(settings, tinymceRichtextSettings);
		
		$('#' + elementid).tinymce(settings);
	}
});