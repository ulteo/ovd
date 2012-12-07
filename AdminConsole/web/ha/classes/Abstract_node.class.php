<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Arnaud LEGRAND <arnaud@ulteo.com>
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

abstract class Abstract_node {
	abstract public function getNodename();
	abstract public function get_attribute($n_attr);
	abstract public function setNodename($name);
	abstract public function set_attribute($name,$value);
	abstract public function set_child(& $child);
	abstract public function clear_attributes();
	abstract public function clear_childs();
	abstract public function has_attribute($attr_name);
	abstract public function has_childs();
	abstract public function view_format_table($cib);
}
