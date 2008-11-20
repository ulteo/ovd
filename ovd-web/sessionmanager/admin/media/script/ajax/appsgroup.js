function appsgroup_modif(id_,name_,description_,published_){
	$('div_appsgroup_list_name_'+id_).innerHTML = '<input type="text" id="appsgroup_list_name_'+id_+'" name="name" value="'+name_+'" size="10" maxlength="100" />';
	$('div_appsgroup_list_description_'+id_).innerHTML = '<input type="text" id="appsgroup_list_description_'+id_+'" name="description" value="'+description_+'" size="10" maxlength="100" />';
	var pub = '<select id="appsgroup_list_published_'+id_+'"  name="published">';
	if ( published_ == "1" ) {
		pub += '<option value="1" selected="selected">Yes</option>';
		pub += '<option value="0" >No</option>';
	}
	else {
		pub += '<option value="0" selected="selected">No</option>';
		pub += '<option value="1" >Yes</option>';
	}
	pub += '</select>';
	$('div_appsgroup_list_published_'+id_).innerHTML = pub;
	$('div_appsgroup_list_modify_button_'+id_).innerHTML = '<input type="button" id="appgroup_modify1_button'+id_+'" name="appgroup_modify1_button'+id_+'" onclick="appsgroup_modify1('+id_+'); return false" value="SAVE" />';
}

function appsgroup_modify1(id_){
	new Ajax.Request(
		'../webservices/admin/appsgroup.php',
		{
			method: 'post',
			parameters: {
				action:'mod',
				id: id_,
				name: $('appsgroup_list_name_'+id_).value,
				description: $('appsgroup_list_description_'+id_).value,
				published: $('appsgroup_list_published_'+id_).value
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content('appsgroup.php?action=list&ajax=noheader','appsgroup_div');
				}
				else {
					$('appsgroup_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('appsgroup_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
}
function appsgroup_add(){
	var pub_ = -1;
	if ($('addappsgroup_published_yes').checked == true)
		pub_ = 1;
	else
		pub_ = 0;
	new Ajax.Request(
		'../webservices/admin/appsgroup.php',
		{
			method: 'post',
			parameters: {
				action:'add',
				name: $('addappsgroup_name').value,
				description: $('addappsgroup_description').value,
				published: pub_
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content('appsgroup.php?action=list&ajax=noheader','appsgroup_div');
				}
				else {
					$('apps_add').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('apps_add').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
}

function appsgroup_del(id_){
	new Ajax.Request(
		'../webservices/admin/appsgroup.php',
		{
			method: 'post',
			parameters: {
				action:'del',
				id: id_
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content('appsgroup.php?action=list&ajax=noheader','appsgroup_div');
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
