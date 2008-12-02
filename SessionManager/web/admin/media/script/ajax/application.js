function application_action_add() {
	var pub_ = -1;
	if ($('app_add_published_yes').checked == true)
		pub_ = 1;
	else
		pub_ = 0;
	
	new Ajax.Request(
		'../webservices/admin/application.php',
		{
			method: 'post',
			parameters: {
				action:'add',
				name: $('app_add_name').value,
				type: $('app_add_type').value,
				executable_path: $('app_add_executable_path').value,
				description: $('app_add_description').value,
				icon_path: $('app_add_icon_path').value,
				package: $('app_add_package').value,
				published: pub_
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					$('app_add_div').innerHTML = "Application added"; //transport.responseText;
				}
				else {
					$('app_add_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('app_add_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
};

function application_action_modify(id_) {
	new Ajax.Request(
		'../webservices/admin/application.php',
		{
			method: 'post',
			parameters: {
				action:'mod',
				id: id_,
				name: $('app_mod_'+id_+'_name').value,
				description: $('app_mod_'+id_+'_description').value,
				type: $('app_mod_'+id_+'_mod_type').value,
				executable_path: $('app_mod_'+id_+'_executable_path').value,
				package: $('app_mod_'+id_+'_package').value,
				icon_path: $('app_mod_'+id_+'_icon_path').value,
				published: $('app_mod_'+id_+'_published').value
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					application_print(id_,$('app_mod_'+id_+'_name').value,$('app_mod_'+id_+'_description').value,$('app_mod_'+id_+'_mod_type').value,$('app_mod_'+id_+'_executable_path').value,$('app_mod_'+id_+'_package').value,$('app_mod_'+id_+'_icon_path').value,$('app_mod_'+id_+'_published').value);
				}
				else {
					$('apps_list').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('apps_list').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
}

function application_modify(id_,name_,description_,type_,executable_path_,package_,icon_path_,published_){
	var pub="";
	$("app_list_"+id_+"_name").innerHTML ='<input type="text" id="app_mod_'+id_+'_name" name="name" value="'+name_+'" size="20" maxlength="100" />';
	$("app_list_"+id_+"_description").innerHTML ='<input type="text" id="app_mod_'+id_+'_description" name="description" value="'+description_+'" size="30" maxlength="100" />';
	$("app_list_"+id_+"_type").innerHTML ='<input type="text" id="app_mod_'+id_+'_mod_type" name="type" value="'+type_+'" size="10" maxlength="100" />';
	$("app_list_"+id_+"_executable_path").innerHTML ='<input type="text" id="app_mod_'+id_+'_executable_path" name="executable_path" value="'+executable_path_+'" size="10" maxlength="100" />';
	$("app_list_"+id_+"_package").innerHTML ='<input type="text" id="app_mod_'+id_+'_package" name="package" value="'+package_+'" size="15" maxlength="100" />';
	$("app_list_"+id_+"_icon_path").innerHTML ='<input type="text" id="app_mod_'+id_+'_icon_path" name="icon_path" value="'+icon_path_+'" size="10" maxlength="100" />';
	
	pub += '<select id="app_mod_'+id_+'_published"  name="published">';
	if (published_ == '0') {
		pub += '<option value="0" selected="selected">No</option>';
		pub += '<option value="1" >Yes</option>';
	}
	else {
		pub += '<option value="1" selected="selected">Yes</option>';
		pub += '<option value="0" >No</option>';
	}
	pub += '</select>';
	$("app_list_"+id_+"_published").innerHTML = pub;
	
	$("app_list_"+id_+"_button_mod").innerHTML ='<input type="button" id="app_'+id_+'_mod_button" name="app_'+id_+'_mod_button" onclick="application_action_modify(\''+id_+'\'); return false"  value="SAVE" />';
}

function application_remove(id_){
// 	$("app_list_table").removeChild("app_list_tr_"+id_); // better but not working... TODO644
	new Ajax.Request(
		'../webservices/admin/application.php',
		{
			method: 'post',
			parameters: {
				action:'del',
				id: id_
			},
			onSuccess: function(transport) {
				page_content("applications.php?action=list&ajax=noheader","applications_div");
				return false;
			},
			onFailure: function() {
				page_content("applications.php?action=list&ajax=noheader","applications_div");
			}
		}
	);
}

function application_print(id_,name_,description_,type_,executable_path_,package_,icon_path_,published_){
	var pub="";
	$("app_list_"+id_+"_name").innerHTML = name_;
	$("app_list_"+id_+"_description").innerHTML = description_;
	$("app_list_"+id_+"_type").innerHTML = type_;
	$("app_list_"+id_+"_executable_path").innerHTML = executable_path_;
	$("app_list_"+id_+"_package").innerHTML = package_;
	$("app_list_"+id_+"_icon_path").innerHTML = icon_path_;
	if (published_ == '0')
		$("app_list_"+id_+"_published").innerHTML = 'no';
	else
		$("app_list_"+id_+"_published").innerHTML = 'yes';
	
	$("app_list_"+id_+"_button_mod").innerHTML = '<input type="button" id="application_modify_button_mod" name="application_modify_button_mod" onclick="application_modify(\''+id_+'\',\''+name_+'\',\''+description_+'\',\''+type_+'\',\''+executable_path_+'\',\''+package_+'\',\''+icon_path_+'\',\''+published_+'\'); return false"  value="MODIF" />';
}

function application_add_to_appsgroup(id_app,id_group){
	new Ajax.Request(
		'../webservices/admin/appgroupliaison.php',
		{
			method: 'post',
			parameters: {
				action:'add',
				application: id_app,
				group: id_group
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content("ajax.php?application="+id_app+"&visible=add","application_appsgroup_add_div_"+id_app)
					page_content("ajax.php?application="+id_app+"&visible=del","application_appsgroup_del_div_"+id_app)
				}
				else {
					$('applications_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('applications_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
}

function application_remove_from_appsgroup(id_app,id_group){
	new Ajax.Request(
		'../webservices/admin/appgroupliaison.php',
		{
			method: 'post',
			parameters: {
				action:'del',
				application: id_app,
				group: id_group
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content("ajax.php?application="+id_app+"&visible=add","application_appsgroup_add_div_"+id_app)
					page_content("ajax.php?application="+id_app+"&visible=del","application_appsgroup_del_div_"+id_app)
				}
				else {
					$('applications_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('applications_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
}