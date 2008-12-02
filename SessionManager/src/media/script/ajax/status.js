Event.observe(window, 'load', function() {
	new Ajax.PeriodicalUpdater(
		$('statusContent'),
		'ajax/status.php',
		{
			method: 'get',
			parameters: {
				update_status: 1
			},
			frequency: 3,
			decay: 2
		}
	);
});
