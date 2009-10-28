/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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
