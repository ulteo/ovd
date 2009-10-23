var available_applications;

function fetchInstallableApplicationsList(fqdn_) {
	$('installableApplicationsList_content').innerHTML = '<div style="width: 5%; text-align: center; margin-top: 10px;"><img src="media/image/loader.gif" width="16" height="16" alt="" title="" /></div>';
	
	new Ajax.Request(
		'ajax/installable_applications.php',
		{
			method: 'get',
			parameters: {
				fqdn: fqdn_
			},
			onSuccess: function(transport) {
				var xml = transport.responseXML;
				var list = new Array();
				
				buffer = xml.getElementsByTagName('categories');
				if (buffer.length != 1) {
					return;
				}
				
				buffer = buffer[0];
				
				categories = buffer.getElementsByTagName('category');
				if (categories.length < 1) {
					return;
				}
				
				for (i=0; i<categories.length; i++) {
					category = categories[i];
					category_name = category.getAttribute('name');
					
					applications = category.getElementsByTagName('application');
					
					list[category_name] = new Array();
					for (j=0; j<applications.length; j++) {
						application = applications[j];
						
						app_name = application.getAttribute('name');
						app_package = application.getAttribute('package');
						
						list[category_name][app_name] = app_package;
					}
				}
				
				available_applications = list;
				
				$('installableApplicationsList_content').innerHTML = $('installableApplicationsListDefault').innerHTML;
				
				InstallableApplicationsInitList();
			}
		}
	);
}

function InstallableApplicationsInitList() {
	var buffer = '';

	buffer+='<select id="installable_applications_category_select" onchange="InstallableApplicationsChangeList();">';
	for (i in available_applications)
		buffer+='<option value="'+i+'">'+i+'</option>';
	buffer+='</select>';

	$('installable_applications_category').innerHTML = buffer;

	InstallableApplicationsChangeList();
}

function InstallableApplicationsChangeList() {
	var subElements = available_applications[$('installable_applications_category_select').value];
	var buffer = '';

	buffer+='<select name="line">';
	for (i in subElements)
		buffer+='<option value="'+subElements[i]+'">'+i+'</option>';
	buffer+='</select>';

	$('installable_applications_application').innerHTML = buffer;
}

var installable_applications_list_already_shown = false;
function toggleInstallableApplicationsList(fqdn_) {
	toggleContent('installableApplicationsList');

	if ($('installableApplicationsList_content').visible() && installable_applications_list_already_shown == false) {
		installable_applications_list_already_shown = true;
 		fetchInstallableApplicationsList(fqdn_);
	}
}
