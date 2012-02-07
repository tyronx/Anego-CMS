settingsFunctions = function() {
	/* General settings code */
	
	$('input[name="Save"]').click(function() {
		$(this).attr('disabled','disabled');
		$(this).parent().find('.ajaxLoad').show();
		
		var $self = $(this);

		$.post('admin.php?a=savesetg', $('form[name="generalsettings"]').serialize(), function(data) {
			$self.removeAttr('disabled');
			$self.parent().find('.ajaxLoad').hide();
			GetAnswer(data);
		});
		
		return false;
	});
	
	// Install module
	this.install = function(name) {
		$.get('admin.php',{a:'im',name:name},function(data) {
			if(GetAnswer(data)) {
				$('#'+name+'_installed').html('<span class="modLink" onclick="settings.uninstall(\''+name+'\')">'+lng_deactivate+'</span>');	
				Core.contentElementModules = null;
			}
		});
	}

	// Uninstall module
	this.uninstall = function(name) {
		$.get('admin.php',{a:'uim',name:name},function(data) {
			if(GetAnswer(data)) {
				$('#'+name+'_installed').html('<span class="modLink" onclick="settings.install(\''+name+'\')">'+lng_activate+'</span>');
				Core.contentElementModules = null;
			}
		});
	}

	/* Opens a dialog and loads config html from the module into it. 
	 * When saving, it just sends back everything withing a <form> element.
	 * Alternative idea: define a name ([module]_ok_callback) that the module should
	 * use as callback function name (js in the return html) to be called once the user presses save
	 */
	this.config = function(name) {
		$.get('modules/'+name+'/'+name+'.php',{a:'getconf'},function(data) {
			var aw;
			if(aw=GetAnswer(data))
				OpenDialog({
					title:name+' '+lngMain.configuration,
					buttons:BTN_SAVECANCEL,
					content:aw,
					ok_callback:function() {
						$.post('modules/'+name+'/'+name+'.php','a=saveconf&'+$('#dlgContent form').serialize(),function(data) {
							if(GetAnswer(data))
								CloseDialog();
						});
					}
				});
		});
	}

	this.loadModules = function() {
		var modules;
		var orderList = $('<span>List</span>');
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
		});
		
		$(function() { $("#tabs").tabs({cookie: {expires:7}}); });
		$.get('admin.php',{ a:'lm' },function (data) {
			var aw;  
			var ins;
			if((aw=GetAnswer(data))) {
				modules = jQuery.parseJSON(aw);
				$('#tabs-2').prepend("<br><br>");
				$('#tabs-2').prepend(orderDetails);
				$('#tabs-2').prepend(" | ");
				$('#tabs-2').prepend(orderList);

				populateList(modules);
			}
		});
		
		function populateList(modules) {
			var ins;
			
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
				}
		}
		
		function populateDetails(modules) {
			var ins;
			
			$('#modulesTable').html('');
			
			for(var prop in modules) 
				if(modules.hasOwnProperty(prop)) {
					if(modules[prop].installed)
						ins='<span class="modLink" onclick="settings.uninstall(\''+prop+'\')">'+lng_deactivate+'</span>';
					else ins=' <span class="modLink" onclick="settings.install(\''+prop+'\')">'+lng_activate+'</span>';
					
					if(modules[prop].type!='ContentElement') ins='';
					if(modules[prop].configurable) ins = '<span class="modLink" onclick="settings.config(\''+prop+'\')">'+lngMain.configure+'</span> '+ins;
					
					$('#modulesTable').append('<tr><td><div class="modulesImg"><img src="' + anego.path + 'modules/'+prop+'/'+modules[prop].image+'" alt="'+prop+'"></div><div class="modulesText"><p><b>'+modules[prop].name+'</b><br><small>by '+modules[prop].author+'</small></p><br><p>'+modules[prop].description+'</p><p>Type: '+modules[prop].type+'<br>Version: '+modules[prop].version+'</p></div><div class="modulesInstall" id="'+prop+'_installed">'+ins+'</div></td></tr>');
				}
		}
	}
};