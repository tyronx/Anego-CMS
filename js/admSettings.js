	settingsFunctions = function() {
	/* General settings code */
	
	$('input[name="SaveWebsite"]').click(function() {
		$(this).attr('disabled','disabled');
		$(this).parent().find('.ajaxLoad').show();
		
		var $self = $(this);

		$.post('admin.php?a=savesetweb', $('form[name="websitesettings"]').serialize(), function(data) {
			$self.removeAttr('disabled');
			$self.parent().find('.ajaxLoad').hide();
			GetAnswer(data);
		});
		
		return false;
	});
	
	$('input[name="SaveGeneral"]').click(function() {
		$(this).attr('disabled','disabled');
		$(this).parent().find('.ajaxLoad').show();
		
		var $self = $(this);

		$.post('admin.php?a=savesetgen', $('form[name="generalsettings"]').serialize(), function(data) {
			$self.removeAttr('disabled');
			$self.parent().find('.ajaxLoad').hide();
			GetAnswer(data);
		});
		
		return false;
	});

	
	/* Opens a dialog and loads config html from the module into it. 
	 * When saving, it just sends back everything withing a <form> element.
	 * Alternative idea: define a name ([module]_ok_callback) that the module should
	 * use as callback function name (js in the return html) to be called once the user presses save
	 */
	this.config = function(event) {
		var module = event.data.module;
		var mid = event.data.id;
		
		$.post('index.php',{
			a: 'callce',
			fn: 'getconf',
			mid: mid
		}, function(data) {
			var aw;
			if (aw = GetAnswer(data))
				OpenDialog({
					title: module.name + ' ' + lngMain.configuration,
					buttons: BTN_SAVECANCEL,
					content: aw,
					ok_callback:function() {
						var self = this;
						
						self.waitResponse();
						$.post('index.php',{
							a: 'callce',
							fn: 'saveconf',
							mid: mid,
							formdata: $('form', self).first().serializeArray(),
						}, function(data) {
							if(GetAnswer(data))
								self.closeDialog();
							self.endWait();
						});
					}
				});
		});
	
		return false;
	}

	this.moduleControl = function() {
		var $module = $(this).parents('.module')
		var name = $module.data('name');
		var installed = $module.data('installed');
		var action = 'im';
		var newname = __('Disable');
		
		if(installed) {
			action = 'uim';
			newname = __('Enable');
		}
		
		$.get('admin.php',{a: action, name:name}, function(data) {
			if(GetAnswer(data)) {
				$('.control a', $module).html(newname);
				$module.data('installed', !installed);
				$module.toggleClass('disabled', installed);

				Core.contentElementModules = null;
			}
		});

		return false;
	}

	this.loadModules = function() {
		var modules;
		/*var orderList = $('<span>List</span>');
		var orderDetails = $('<span class="modLink">Details</span>');
		
		orderList.click(function() {
			if(!$(this).hasClass('modLink')) return;
			
			orderList.removeClass('modLink');
			orderDetails.addClass('modLink');
			
			populateList(modules);
		});
		
		orderDetails.click(function() {
			if(!$(this).hasClass('modLink')) return;
			
			orderList.addClass('modLink');
			orderDetails.removeClass('modLink');
			
			populateDetails(modules);
		});*/
		
		//$(function() { $("#tabs").tabs({cookie: {expires:7}}); });
		makeTabs();
		
		$.get('admin.php',{ a:'lm' },function (data) {
			var aw;  
			var ins;
			if((aw=GetAnswer(data))) {
				modules = jQuery.parseJSON(aw);
				/*$('#modules').prepend("<br><br>");
				$('#modules').prepend(orderDetails);
				$('#modules').prepend(" | ");
				$('#modules').prepend(orderList);*/

				//populateList(modules);
				populateDetails(modules);
			}
		});
		
		function populateList(modules) {
			/*var ins;
			
			$('#modulesTable').html('');
			$('#modulesTable').append('<tr><th>Name/Version</th><th>Author</th><th>Type</th><th></th></tr>');
			
			for(var prop in modules) 
				if(modules.hasOwnProperty(prop)) {
					if(modules[prop].installed)
						ins='<span class="modLink" onclick="settings.uninstall(\''+prop+'\')">'+lng_deactivate+'</span>';
					else ins=' <span class="modLink" onclick="settings.install(\''+prop+'\')">'+lng_activate+'</span>';
					
					if(modules[prop].type!='ContentElement') ins='';
					
					if(modules[prop].configurable) ins = '<span class="modLink" onclick="settings.config(\''+prop+'\')">'+lngMain.configure+'</span> '+ins;
					
					$('#modulesTable').append(
						'<tr>' + 
							'<td><b>'+modules[prop].name+'</b> v'+modules[prop].version+'</td>' + 
							'<td>'+modules[prop].author+'</td><td>'+modules[prop].type+'</td>' + 
							'<td align="right"><div class="modulesInstall" id="'+prop+'_installed">'+ins+'</div></td>' + 
						'</tr>');
				}*/
		}
		
		function populateDetails(modules) {
			var ins;
			
			var moduleTemplate = 
				'<div class="module">' +
					'<b class="name"></b> v<span class="version"></span><br>' +
					__('Type') + ': <span class="type"></span><br>' +
					__('Author') + ': <span class="author"></span><br>' +
					'<small class="description"></span>' +
				'</div>';
			
			var controlLinks = 
				'<div class="control rightfloat">' +
					'<a href="#"></a>' +
				'</div>' +
				'<div class="configure rightfloat" style="margin-right:15px;">' +
					'<a href="#"></a>' +
				'</div>' +
				'<hr>';
			
			for(var prop in modules) 
				if(modules.hasOwnProperty(prop)) {
					$module = $(moduleTemplate);
					mdata = modules[prop];

					if (!mdata.installed) 
						$module.addClass('disabled');

					$('.name', $module).html(mdata.name);
					$('.version', $module).html(mdata.version);
					$('.type', $module).html(mdata.type);
					$('.author', $module).html(mdata.author);
					$('.description', $module).html(mdata.description);
					
					$module.append(controlLinks);
					
					if(mdata.type == 'ContentElement') {
						$module.data('installed', modules[prop].installed);
						$module.data('name', prop);
						
						if(modules[prop].installed) {
							$('.control a', $module).html(__('Disable'));
						} else {
							$('.control a', $module).html(__('Enable'));
						}
						
						$('.control a', $module).click(settings.moduleControl);
					}
					
					
					if(mdata.configurable) {
						$('.configure a', $module)
							.html(__('Configure'))
							.click({ id: prop, module: mdata}, settings.config);
					} else {
						$('.configure', $module).remove();
					}
					
					$('#modules').append($module);
				}
		}
		
	}
};

function makeTabs() {
	//When page loads...
	$(".tab_content").hide(); //Hide all content
	
	if(typeof tabpage == "number") {
		$("ul.tabs li:nth-child(" + tabpage + ")").addClass("active").show();
		$(".tab_content:nth-child(" + tabpage + ")").show();
	} else {
		$("ul.tabs li:first").addClass("active").show();
		$(".tab_content:first").show();
	}

	$("ul.tabs li").click(function() {
		$("ul.tabs li").removeClass("active");
		$(this).addClass("active");
		$(".tab_content").hide();

		var activeTab = $(this).find("a").attr("href");
		$(activeTab).show();
		return false;
	});
}