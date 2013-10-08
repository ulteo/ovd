/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

var TimeGrid = Class.create({
	table: null,
	callback: null,
	days_name: null,
	
	isDrag: false,
	drag_cell_start: null,
	drag_cell_current: null,
	
	last_value: null,
	
	initialize: function(table_) {
		this.table = table_;
		this.callback = [];
		this.days_name = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
		
		var thead = document.createElement("thead");
		var tr = document.createElement("tr");
		var th = document.createElement("th");
		tr.appendChild(th);
		
		for (j=0; j<=24; j++) {
			var hour = j;
			if (hour > 23)
				hour = 0;
			
			var th = document.createElement("th");
			var div = document.createElement("div");
			if (hour%2 == 0) {
				div.innerHTML = hour;
			}
			else {
				div.innerHTML = ".";
			}
			
			th.appendChild(div);
			tr.appendChild(th);
		}
		
		thead.appendChild(tr);
		this.table.appendChild(thead);
		var tbody = document.createElement("tbody");
		for (i=0; i<7; i++) {
			var tr = document.createElement("tr");
			var th = document.createElement("th");
			tr.appendChild(th);
			
			var self = this;
			for (j=0; j<24; j++) {
				var td = document.createElement("td");
				if (Prototype.Browser.IE) {
					td.onselectstart = function () { return false; };
				}
				
				td.id = i+"_"+j;
				td.innerHTML = "&nbsp;"
				if (! this.table.hasClassName("disabled")) {
					Event.observe(td, "mousedown", this.on_mousedown.bind(this));
					Event.observe(td, "mouseup",   this.on_mouseup.bind(this));
					Event.observe(td, "mousemove", this.on_mousemove.bind(this));
					Event.observe(td, "mouseout", this.on_mouseout.bind(this));
					
					Event.observe(td, "touchstart", this.on_mousedown.bind(this));
					Event.observe(td, "touchend",   this.on_touchend.bind(this));
					Event.observe(td, "touchmove", this.on_touchmove.bind(this));
					Event.observe(td, "touchcancel", this.on_touchcancel.bind(this));
					
				}
				
				tr.appendChild(td);
			}
			
			tbody.appendChild(tr);
		}
		
		this.table.appendChild(tbody);
		
		this.rebuild_days_name();
	},
	
	rebuild_days_name: function() {
		var pos = 0;
		var self = this;
		this.table.down("tbody").select("th").each(function(th) {
			th.innerHTML = self.days_name[pos++];
		});
	},
	
	set_days_name: function(days_name_) {
		this.days_name = days_name_;
		this.rebuild_days_name();
	},
	
	set_value: function(value_) {
		var day_value = [];
		var pos = 0;
		var day=0;
		
		var self = this;
		this.table.select('td').each(function(item2) {
			if (pos == 0) {
				day_value = self.get_day_values(value_, day);
			}
			
			var p = day_value[pos];
			if (p=="1") {
				self.toggle_cell(item2);
			}
			
			if (pos == 23) {
				pos = 0;
				day++;
			}
			else {
				pos++;
			}
		});
		
		this.last_value = value_;
	},
	
	get_value: function() {
		var value = "";
		var pos = 0;
		var day_value = [];
		
		var self = this;
		this.table.select('td').each(function(item) {
			if (item.hasClassName("selected")) {
				day_value.push("1");
			}
			else {
				day_value.push("0");
			}
			
			if (pos == 23) {
				var e = parseInt(day_value.join(""), 2);
				e = self.add_0_prefix(e.toString(16), 6);
				value+= e;
				day_value = [];
				pos = 0;
			}
			else {
				pos++;
			}
		});
		
		return value.toUpperCase();
	},
	
	add_change_callback: function(callback_) {
		this.callback.push(callback_);
	},
	
	toggle_cell: function(cell) {
		if (cell.hasClassName("selected")) {
			cell.removeClassName("selected");
		}
		else {
			cell.addClassName("selected");
		}
	},
	
	drag_start: function(cell_) {
		this.isDrag = true;
		this.drag_cell_start = cell_;
		this.drag_cell_current = cell_;
		this.drag_cell_start.addClassName("hover");
	},
	
	drag_end: function() {
		var self = this;
		this.table.select('td').each(function(cell) {
			if (cell.hasClassName("hover")) {
				self.toggle_cell(cell);
				cell.removeClassName("hover");
			}
		});
		
		this.isDrag = false;
		this.drag_cell_start = null;
		this.drag_cell_current = null;
		
		var value = this.get_value();
		if (value != this.last_value) {
			this.callback.each(function(callback) {
				callback(value);
			});
		}
	},
	
	on_mousedown: function(event) {
		// Avoid the default browser select behavior
		if (typeof event.preventDefault != 'undefined') {
			event.preventDefault();
		}
		
		this.drag_start(event.target)
	},
	
	on_mouseup: function(event) {
		if (event.target != this.drag_cell_current) {
			this.drag_cell_current = event.target;
			this.set_cells_hover();
		}
		
		this.drag_end();
	},
	
	on_mousemove: function(event) {
		if (this.isDrag == true) {
			if (event.target == this.drag_cell_current) {
				return false;
			}
			
			this.drag_cell_current = event.target;
			this.set_cells_hover();
		}
		else {
			// hover the current cell
			event.target.addClassName("hover");
		}
	},
	
	on_mouseout: function(event) {
		if (event.target.hasClassName("hover")) {
			event.target.removeClassName("hover");
		}
	},
	
	on_touchend: function(event) {
		this.drag_end();
	},
	
	on_touchmove: function(event) {
		var element = document.elementFromPoint(event.pageX, event.pageY);
		if (element == this.drag_cell_current) {
			return false;
		}
		
		this.drag_cell_current = element;
		this.set_cells_hover();
	},
	
	on_touchcancel: function(event) {
		this.reset_cells_hover();
		
		this.drag_end();
	},
	
	set_cells_hover: function() {
		var pos0 = this.get_cell_pos(this.drag_cell_start);
		var pos1 = this.get_cell_pos(this.drag_cell_current);
		
		var self = this;
		this.table.select('td').each(function(cell) {
			var pos = self.get_cell_pos(cell);
			if (self.is_cell_included(pos, pos0, pos1)) {
				cell.addClassName("hover");
			}
			else if (cell.hasClassName("hover")) {
				cell.removeClassName("hover");
			}
		});
	},
	
	reset_cells_hover: function() {
		var self = this;
		this.table.select('td').each(function(cell) {
			if (cell.hasClassName("hover")) {
				cell.removeClassName("hover");
			}
		});
	},
	
	// Static functions
	
	add_0_prefix: function(str_, len_) {
		var diff = len_ - str_.length;
		if (diff > 0) {
			var prefix = "";
			for (i=0; i<diff; i++) {
				prefix+= "0";
			}
			
			str_ = prefix + str_;
		}
		
		return str_;
	},
	
	get_day_values: function(info_, day_) {
		var value_hex = info_.substr(day_*3*2, 3*2);
		var value = parseInt("0x"+value_hex);
		value = this.add_0_prefix(value.toString(2), 3*8);
		value = value.split("");
		
		return value;
	},
	
	get_cell_pos: function(cell_) {
		return {
			"x": cell_.previousSiblings().length,
			"y": cell_.up().previousSiblings().length
		};
	},
	
	is_cell_included: function(cell_, cell0_, cell1_) {
		return (cell_["x"]>=Math.min(cell0_["x"], cell1_["x"]) &&
				cell_["x"]<=Math.max(cell0_["x"], cell1_["x"]) &&
				cell_["y"]>=Math.min(cell0_["y"], cell1_["y"]) &&
				cell_["y"]<=Math.max(cell0_["y"], cell1_["y"])
		);
	}
});

TimeGrid.instances = {};
TimeGrid.getInstance = function(id_) {
	return TimeGrid.instances[id_];
};

Event.observe(window, 'load', function() {
	$$("table.time_grid").each(function(item) {
		var i = new TimeGrid(item);
		if (item.id != undefined) {
			TimeGrid.instances[item.id] = i;
		}
	});
});
