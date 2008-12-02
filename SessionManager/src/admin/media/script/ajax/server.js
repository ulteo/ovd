function server_sourceslist(fqdn_,mirror_,action_){
	new Ajax.Request(
		'../webservices/admin/sourceslist.php',
		{
			method: 'post',
			parameters: {
				fqdn:fqdn_,
				action:action_,
				mirror:mirror_
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content('servers.php?action=manage&fqdn='+fqdn_+'&ajax=noheader','servers_div');
				}
				else {
					$('servers_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('servers_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
}

function server_sourceslist_send(fqdn_){
	new Ajax.Request(
		'../webservices/admin/sourceslist.php',
		{
			method: 'post',
			parameters: {
				action:'send',
				fqdn:fqdn_
			},
			onSuccess: function(transport) {
				if (transport.responseText == 'OK') {
					page_content('servers.php?action=manage&fqdn='+fqdn_+'&ajax=noheader','servers_div');
				}
				else {
					$('servers_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
				}
				return false;
			},
			onFailure: function() {
				$('servers_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
			}
		}
	);
}

function server_sourceslist_duplicate(original_,id_destinations){
	var ok=true;
	for (var i = 0; i < id_destinations.options.length; i++){
		if ((id_destinations.options[i].selected) && (ok == true)){
			new Ajax.Request(
			'../webservices/admin/sourceslist.php',
			{
				method: 'post',
				parameters: {
					action:'duplicate',
					ori:original_,
	 				dest:id_destinations.options[i].value
				},
				onSuccess: function(transport) {
					if (transport.responseText == 'OK') {
						// we do nothing right now, we wait for all duplications
					}
					else {
						$('servers_div').innerHTML = "<p class=\"important\">Error : "+transport.responseText+"</p>";
						ok = false;
					}
					return false;
				},
				onFailure: function() {
					$('servers_div').innerHTML = "<p class=\"important\">Error (failure)</p>";
					ok = false;
				}
			}
			);
		}
	}
	if (ok == false){
		$('servers_div').innerHTML = "<p class=\"important\">Error : fail to duplicate</p>";
	}
	else {
		alert('duplication done');
		page_content('servers.php?action=list&ajax=noheader','servers_div');
	}
}

function statusJob(fqdn_,id_job_,app_,action_){
	var status_job;
	var fqdn2;
	fqdn2 = fqdn_.replace(/\./g, "_");
	new Ajax.Request(
		'../webservices/admin/apt-get.php',
		{
			method: 'post',
			parameters: {
				fqdn:fqdn_,
				job:id_job_,
				action:'show',
				show:'status'
			},
			onSuccess: function(transport) {
				status_job = transport.responseText
				if (status_job == ""){
					setTimeout("statusJob('"+fqdn_+"','"+id_job_+"','"+app_+"','"+action_+"')", 2000);
				}
				else{
					if (isNaN(status_job)==false ) { // caution "" is a number
						if (status_job == 0){
							// install ok so we update application list
							new Ajax.Request(
								'../webservices/admin/server.php',
								{
									method: 'post',
									parameters: {
										action:'avalaibleapplication',
										fqdn:fqdn_
									}
								}
							);
							$('server_manage_applications_log_'+fqdn2).innerHTML += action_+' <i>'+app_+'</i> OK'+'<br />';
						}
						else {
							new Ajax.Request(
								'../webservices/admin/apt-get.php',
								{
									method: 'post',
									parameters: {
										fqdn:fqdn_,
										job:id_job_,
										action:'show',
										show:'stdout'
									},
									onSuccess: function(transport) {
										$('server_manage_applications_log_'+fqdn2).innerHTML = "<p class=\"important\">Error (failure) <br /> LOG : <pre>"+transport.responseText+"</pre></p>";
									},
									onFailure: function() {
										$('server_manage_applications_log_'+fqdn2).innerHTML = "<p class=\"important\">Error (failure)</p>";
									}
								}
							);
						}
					}
					else{
						setTimeout("statusJob('"+fqdn_+"','"+id_job_+"','"+app_+"','"+action_+"')", 2000);
					}
				}
			},
			onFailure: function() {
			alert("statusJob onFailure");
				$('server_manage_applications_log_'+fqdn2).innerHTML = "<p class=\"important\">Error (failure)</p>"; // TODO
				return -1;
			}
		}
	);
}

function server_manage_applications(fqdn_,app_,action_){
	var id_job_;
	var status_job ;
	var fqdn2;
	fqdn2 = fqdn_.replace(/\./g, "_");
	new Ajax.Request(
		'../webservices/admin/apt-get.php',
		{
			method: 'post',
			parameters: {
				fqdn:fqdn_,
				action:action_,
				app:app_
			},
			onSuccess: function(transport) {
				id_job_ = transport.responseText;
				$('server_manage_applications_log_'+fqdn2).innerHTML += action_+' <i>'+app_+'</i> in progress'+'<br />';
				statusJob(fqdn_,id_job_,app_,action_);
			},
			onFailure: function() {
				$('server_manage_applications_log_'+fqdn2).innerHTML = "<p class=\"important\">Error (failure)</p>"; // TODO
				ok = false;
			}
		}
	);
}

function server_manage_duplicate_applications(fqdn_ori_,fqdn_dest_,dupplicate_){
	// dupplicate_ : 0 -> only add application to dest
	// dupplicate_ : 1 -> duplicate (so we also remove app from dest)
	new Ajax.Request(
		'../webservices/admin/apt-get.php',
		{
			method: 'post',
			parameters: {
				fqdn_dest:fqdn_dest_,
				fqdn_ori:fqdn_ori_,
				action:'duplicate',
				param:dupplicate_
			},
			onSuccess: function(transport) {
				alert(transport.responseText);
			},
			onFailure: function() {
			}
		}
	);
}
