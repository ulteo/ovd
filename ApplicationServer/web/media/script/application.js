var Application = Class.create({
	id: 0,
	name: '',

	initialize: function(id_, name_) {
		this.id = id_;
		this.name = name_;
	},

	getIconURL: function() {
		return '../icon.php?id='+this.id;
	}
});

var Running_Application = Class.create(Application, {
	pid: '',
	status: -1,

	initialize: function(id_, name_, pid_, status_) {
		Application.prototype.initialize.apply(this, [id_, name_]);

		this.pid = pid_;
		this.status = status_;
	}
});
