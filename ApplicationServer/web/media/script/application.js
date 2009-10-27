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
