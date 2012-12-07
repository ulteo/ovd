function usergroup_settings_remove(uid_, container_, element_id_) {
	new Ajax.Request(
		'actions.php',
		{
			method: 'post',
			parameters: {
				name: "UserGroup_settings",
				unique_id: uid_,
				action: "del",
				container: container_,
				element_id: element_id_
			},
			onSuccess: function() {
				window.location.reload();
			}
		}
	);
}
