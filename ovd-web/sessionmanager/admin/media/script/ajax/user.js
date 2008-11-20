function user_action_add() {
	new Ajax.Request(
		'../webservices/admin/user.php',
		{
			method: 'post',
			parameters: {
				action:'add',
				uid: $('user_add_uid').value,
				login: $('user_add_login').value,
				displayName: $('user_add_displayName').value,
				gid: $('user_add_gid').value,
				homeDir: $('user_add_fileserver_homeDir').value,
				fileserver_uid: $('user_add_fileserver_uid').value,
				password: $('user_add_password').value,
				fileserver: $('user_add_fileserver').value
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content('users.php?ajax=noheader','users_div');
				}
				else {
					$('user_add').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('user_add').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
}

function user_action_modify(login_) {
	new Ajax.Request(
		'../webservices/admin/user.php',
		{
			method: 'post',
			parameters: {
				action:'mod',
				uid: $('user_list_uid_'+login_).value,
				login: $('user_list_login_'+login_).value,
				displayName: $('user_list_displayName_'+login_).value,
				gid: $('user_list_gid_'+login_).value,
				homeDir: $('user_list_homeDir_'+login_).value,
				fileserver_uid: $('user_list_fileserver_uid_'+login_).value,
				password: $('user_list_password_'+login_).value,
				fileserver: $('user_list_fileserver_'+login_).value
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					$('div_user_list_login_'+login_).innerHTML =$('user_list_login_'+login_).value;
					$('div_user_list_displayName_'+login_).innerHTML =$('user_list_displayName_'+login_).value;
					$('div_user_list_password_'+login_).innerHTML =$('user_list_password_'+login_).value;
					$('div_user_list_uid_'+login_).innerHTML =$('user_list_uid_'+login_).value;
					$('div_user_list_gid_'+login_).innerHTML =$('user_list_gid_'+login_).value;
					$('div_user_list_fileserver_'+login_).innerHTML =$('user_list_fileserver_'+login_).value;
					$('div_user_list_fileserver_uid_'+login_).innerHTML =$('user_list_fileserver_uid_'+login_).value;
					$('div_user_list_homeDir_'+login_).innerHTML =$('user_list_homeDir_'+login_).value;
					// TODO BETTER
					var uid2 = $('user_list_uid_'+login_).value;
					var login2 = $('user_list_login_'+login_).value;
					var displayName2 = $('user_list_displayName_'+login_).value;
					var gid2 = $('user_list_gid_'+login_).value;
					var homeDir2 = $('user_list_homeDir_'+login_).value;
					var fileserver_uid2 = $('user_list_fileserver_uid_'+login_).value;
					var password2 = $('user_list_password_'+login_).value;
					var fileserver2 = $('user_list_fileserver_'+login_).value;
					$('div_user_list_modify2_button_'+login_).innerHTML = '<input type="button" id="user_list_modify2_button_'+login_+'" name="user_list_modify2_button_'+login_+'" onclick="user_modify('+login2+','+displayName2+','+password2+','+uid2+','+gid2+','+fileserver2+','+fileserver_uid2+','+homeDir2+'); return false"  value="MODIF" />';
				}
				else {
					$('users_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('users_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
}

function user_modify (login_,displayName_,password_,uid_,gid_,fileserver_,fileserver_uid_,homeDir_){
	$('div_user_list_login_'+login_).innerHTML ='<input type="text" id="user_list_login_'+login_+'" name="login" value="'+login_+'" size="20" maxlength="100" disabled />';
	$('div_user_list_displayName_'+login_).innerHTML ='<input type="text" id="user_list_displayName_'+login_+'" name="displayName" value="'+displayName_+'" size="20" maxlength="100"  />';
	$('div_user_list_password_'+login_).innerHTML ='<input type="text" id="user_list_password_'+login_+'" name="password" value="'+password_+'" size="20" maxlength="100"  />';
	$('div_user_list_uid_'+login_).innerHTML ='<input type="text" id="user_list_uid_'+login_+'" name="uid" value="'+uid_+'" size="20" maxlength="100" disabled />';
	$('div_user_list_gid_'+login_).innerHTML ='<input type="text" id="user_list_gid_'+login_+'" name="gid" value="'+gid_+'" size="20" maxlength="100"  />';
	$('div_user_list_fileserver_'+login_).innerHTML ='<input type="text" id="user_list_fileserver_'+login_+'" name="fileserver" value="'+fileserver_+'" size="20" maxlength="100"  />';
	$('div_user_list_fileserver_uid_'+login_).innerHTML ='<input type="text" id="user_list_fileserver_uid_'+login_+'" name="fileserver_uid" value="'+fileserver_uid_+'" size="20" maxlength="100"  />';
	$('div_user_list_homeDir_'+login_).innerHTML ='<input type="text" id="user_list_homeDir_'+login_+'" name="homeDir" value="'+homeDir_+'" size="20" maxlength="100"  />';
	$('div_user_list_modify2_button_'+login_).innerHTML ='<input type="button" id="user_list_modify2_button_'+login_+'" name="user_list_modify2_button_'+login_+'" onclick="user_action_modify(\''+login_+'\'); return false"  value="SAVE" />';
}

function user_remove(login){
	new Ajax.Request(
		'../webservices/admin/user.php',
		{
			method: 'post',
			parameters: {
				action:'del',
				login: login
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content('users.php?action=list&ajax=noheader','users_div');
				}
				else {
					$('users_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('users_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
}

function user_remove_from_usersgroup(login,uid){
	new Ajax.Request(
	'../webservices/admin/usergroupliaison.php',
	{
		method: 'post',
		parameters: {
			action:'del',
			user: login,
			group: $("user_del_to_usersgroup_uid_"+uid).value
		},
		onSuccess: function(transport) {
			if (transport.responseText == 'OK') {
				page_content("ajax.php?user="+login+"&visible=add","user_usersgroup_add_"+uid);
				page_content("ajax.php?user="+login+"&visible=del","user_usersgroup_del_"+uid);
			}
			else {
				$('users_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
			}
			return false;
		},
		onFailure: function() {
			$('users_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
		}
	}
	);
}

function user_add_to_usersgroup(login,uid){
	new Ajax.Request(
	'../webservices/admin/usergroupliaison.php',
	{
		method: 'post',
		parameters: {
			action:'add',
			user: login,
			group: $("user_add_to_usersgroup_uid_"+uid).value
		},
		onSuccess: function(transport) {
			if (transport.responseText == 'OK') {
				page_content("ajax.php?user="+login+"&visible=add","user_usersgroup_add_"+uid);
				page_content("ajax.php?user="+login+"&visible=del","user_usersgroup_del_"+uid);
			}
			else {
				$('users_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
			}
			return false;
		},
		onFailure: function() {
			$('users_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
		}
	}
	);
}

