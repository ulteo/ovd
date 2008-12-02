function usersgroup_action_add(){
	var pub_ = -1;
	if ($('usersgroup_add_published_yes').checked == true)
		pub_ = 1;
	else
		pub_ = 0;
	new Ajax.Request(
		'../webservices/admin/usersgroup.php',
		{
			method: 'post',
			parameters: {
				action:'add',
				name: $('usersgroup_add_name').value,
				description: $('usersgroup_add_description').value,
				published: pub_
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content('usersgroup.php?action=list&ajax=noheader','usersgroup_div');
				}
				else {
					$('usersgroup_div').innerHTML = "<p class=\"important\">(ERR#041) Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('usersgroup_div').innerHTML = "<p class=\"important\">(ERR#040) Error (failure)</p>";
			}
		}
	);
};

function usersgroup_modify1(id_){
	new Ajax.Request(
		'../webservices/admin/usersgroup.php',
		{
			method: 'post',
			parameters: {
				action:'mod',
				id: id_,
				name: $('usersgroup_modify_name_'+id_).value,
				description: $('usersgroup_modify_description_'+id_).value,
				published: $('usersgroup_modify_published_'+id_).value
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content('usersgroup.php?action=list&ajax=noheader','usersgroup_div');
				}
				else {
					$('usersgroup_div').innerHTML = "<p class=\"important\">(ERR#039) Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('usersgroup_div').innerHTML = "<p class=\"important\">(ERR#032) Error (failure)</p>";
			}
		}
	);
};

function usersgroup_modify(id_,name_,description_,published_){
	$('div_usersgroup_list_name_'+id_).innerHTML ='<input type="text" id="usersgroup_modify_name_'+id_+'" name="name" value="'+name_+'" size="15" maxlength="100" />';
	$('div_usersgroup_list_description_'+id_).innerHTML ='<input type="text" id="usersgroup_modify_description_'+id_+'" name="name" value="'+description_+'" size="15" maxlength="100" />';
	var pub = '<select id="usersgroup_modify_published_'+id_+'"  name="published">';
	
	if ( published_ == "1" ) {
		pub += '<option value="1" selected="selected">Yes</option>';
		pub += '<option value="0" >No</option>';
	}
	else {
		pub += '<option value="0" selected="selected">No</option>';
		pub += '<option value="1" >Yes</option>';
	}
	pub += '</select>';
	$('div_usersgroup_list_published_'+id_).innerHTML = pub;
	$('div_usersgroup_list_modify2_button_'+id_).innerHTML ='<input type="button" id="usersgroup_modify1_button_'+id_+'" name="usersgroup_modify1_button" onclick="usersgroup_modify1('+id_+'); return false" value="SAVE" />';
}

function usersgroup_remove_from_appsgroup(usergroup_id){
	new Ajax.Request(
		'../webservices/admin/usersgroupappsgroupliaison.php',
		{
			method: 'post',
			parameters: {
				action:'del',
				usersgroup: usergroup_id,
				appsgroup: $("usersgroup_appsgroup_del_id_appsgroup_"+usergroup_id).value
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content("ajax.php?usersgroup="+usergroup_id+"&visible=del","usersgroup_appsgroup_del_"+usergroup_id);
					page_content("ajax.php?usersgroup="+usergroup_id+"&visible=add","usersgroup_appsgroup_add_"+usergroup_id);
				}
				else {
					$('usersgroup_div').innerHTML = "<p class=\"important\">(ERR#033) Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('usersgroup_div').innerHTML = "<p class=\"important\">(ERR#034) Error (failure)</p>";
			}
		}
	);
};

function usersgroup_add_to_appsgroup(usergroup_id){
	new Ajax.Request(
		'../webservices/admin/usersgroupappsgroupliaison.php',
		{
			method: 'post',
			parameters: {
				action:'add',
				usersgroup: usergroup_id,
				appsgroup: $("usersgroup_appsgroup_add_id_appsgroup_"+usergroup_id).value
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content("ajax.php?usersgroup="+usergroup_id+"&visible=add","usersgroup_appsgroup_add_"+usergroup_id);
					page_content("ajax.php?usersgroup="+usergroup_id+"&visible=del","usersgroup_appsgroup_del_"+usergroup_id);
				}
				else {
					$('usersgroup_div').innerHTML = "<p class=\"important\">(ERR#035) Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('usersgroup_div').innerHTML = "<p class=\"important\">(ERR#036) Error (failure)</p>";
			}
		}
	);
};

function usersgroup_del(id_){
	new Ajax.Request(
		'../webservices/admin/usersgroup.php',
		{
			method: 'post',
			parameters: {
				action:'del',
				id: id_
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content('usersgroup.php?action=list&ajax=noheader','usersgroup_div');
				}
				else {
					$('usersgroup_list').innerHTML = "<p class=\"important\">(ERR#037) Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('usersgroup_list').innerHTML = "<p class=\"important\">(ERR#038) Error (failure)</p>";
			}
		}
	);
}
