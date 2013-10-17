/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

function configuration4_mod(js) {
	function copy_and_init(oldrow,newrow) {
		var oelements = oldrow.getElementsByTagName('input');
		var nelements = newrow.getElementsByTagName('input');

		if (oelements.length != nelements.length)
			return false;

		for (var i = 0; i < oelements.length; i++)
			nelements[i].value = '';

		if (oelements.length > 0) {
			if (!oelements[0].value){
				return false;
			}
		}
		if (oelements.length > 1) {
			if (!oelements[1].value){
				return false;
			}
		}
		return true;
	}

	function changeNames(row, index) {
		var re = /(_{3})([0-9+])(_{3})(.+)$/;
		var rt = '$1'+index+'$3$4';

		function update(type) {
			var elements = row.getElementsByTagName('input');
			for (var i = 0, n = elements.length; i < n; i++) {
				var newname = elements[i].name.replace(re, rt );
				elements[i].setAttribute('name', newname);
				if (elements[i].getAttribute('type') == 'hidden') {
					elements[i].setAttribute('value', index);
				}
			}
		}
		update('text');
		update('hidden');
	}

	var div,td,tr,table;
	div = js.parentNode;
	td = div.parentNode;
	tr = td.parentNode;
	table = tr.parentNode;

	if ((tr.sectionRowIndex +1 )== table.rows.length) { // last one ?
		// last -> add
		if ((new_row = tr.cloneNode(true)) && new_row.getElementsByTagName) {
			if (copy_and_init(tr, new_row) == true) {
				table.appendChild(new_row);
				$(js).innerHTML = '<img src="media/image/less.png"/>';
				changeNames(new_row, table.rows.length - 1);
			}
		}
	}
	else {
		// not last -> remove
		for (i = tr.sectionRowIndex + 1; i < table.rows.length; i++) {
			changeNames(table.rows[i], i - 1);
		}
		table.deleteRow(tr.sectionRowIndex);
	}
}
function element_exists(id) {
	var buf = document.getElementById(id);
	return (buf!=null);
}
function module_is_enable(module_name){
	var buf;
	buf = 'module___module_enable___'+module_name.substring(module_name.lastIndexOf('_')+1);
// 	if (element_exist)
// 		return $(buf).checked;
	return false;
}
function printConfigurationModule(id_select){
	var i = 0;
	while(i<$(id_select).options.length) {
		if (element_exists(id_select+"_"+$(id_select).options[i].value)) {
			if ($(id_select).options[i].selected == true){
// 				if (module_is_enable(id_select)){
					$(id_select+"_"+$(id_select).options[i].value).show();
// 				}
			}
			else {
				$(id_select+"_"+$(id_select).options[i].value).hide();
			}
		}
		i+=1;
	}
}

function configuration_switch(object_,container,container_sub,id_element) {
	if (object_.type == "select-one" ) {
		var option = object_.options[object_.selectedIndex];
		for (var i = 0 ; i<object_.options.length;i++) {
			var id_div = container+"___"+object_.options[i].value;
			if (element_exists(id_div)) {
				if ( i == object_.selectedIndex ) {
					$(id_div).show();
				}
				else {
					$(id_div).hide();
				}
			}
		}
	}

	if (object_.type == "checkbox" ) {
		if (element_exists(object_.value)) {
			if (object_.checked) {
				$(object_.value).show();
			}
			else {
				$(object_.value).hide();
			}
		}

	}
	return false;
}

function configuration_switch_references(object_, context_, references_) {
	var selected = object_.options[object_.selectedIndex].value;
	
	for (var i = 0; i<object_.options.length; i++) {
		var name = object_.options[i].value;
		
		if (typeof references_.get(name) == 'undefined')
			continue;
		
		var ids = references_.get(name);
		for (var j = 0; j < ids.length; j++) {
			var id = context_ + "___" + ids[j];
			if (! element_exists(id))
				continue;
			
			var tr_node = search_first_tr_node($(id));
			if (tr_node == null)
				continue;
			
			if (name == selected)
				tr_node.setAttribute("style", "display: visible;");
			else
				tr_node.setAttribute("style", "display: none;");
		}
	}
}

function search_first_tr_node(node) {
	if (node == null || node == document)
		return null;
	
	if (node.nodeName.toLowerCase() == "tr")
		return node;
	
	return search_first_tr_node(node.parentNode);
}

function configuration_switch_init() {
	Event.observe(window, 'load', function() {
		configuration_switch_init2();
	});
}

function configuration_switch_init2() {
	$$('input', 'select').each(function(element) {
		if ((element.type == "select-one" ) || (element.type == "checkbox")) {
			fire_event_change(element);
		}
	});
}

function configuration_set_value(key_, value_) {
	var elements = $$('input[name='+key_+']', 'select[name='+key_+']');
	if (elements.length == 0) {
		return;
	}
	
	var element = elements[0];
	if (element.nodeName.toLowerCase() == 'input') {
		element.value = value_;
		
	}
	else if (element.nodeName.toLowerCase() == 'select') {
		for(var i=0; i<element.options.length; i++) {
			var option = element.options[i];
			if (option.value == value_) {
				option.selected = true;
				break;
			}
		}
	}
	
	fire_event_change(element);
}


function configuration_del_available(select_, select_cache_, setting_) {
	for(var i=0; i<select_.options.length; i++) {
		option = select_.options[i];
		if (option.value != setting_) {
			continue;
		}
		
		select_.removeChild(option);
		break;
	}
	
	if (select_.options.length == 0 && select_.up('form').visible()) {
		select_.up('form').hide();
	}
}

function configuration_add_available(select_, select_cache_, setting_) {
	for(var i=0; i<select_cache_.options.length; i++) {
		var option = select_cache_.options[i];
		if (option.value != setting_) {
			continue;
		}
		
		option = option.cloneNode(true);
		
		select_.appendChild(option);
		break;
	}
	
	if (select_.options.length > 0 && ! select_.up('form').visible()) {
		select_.up('form').show();
	}
}

function configuration_add(container_, setting_) {
	var tr = $('tr_'+container_+'_'+setting_);
	tr.up().removeChild(tr);
	$('settings_table_'+container_).down('tbody').appendChild(tr);
	
	configuration_del_available($('settings_select_'+container_), $('cache_select_'+container_), setting_);
	
	if (! $('settings_table_'+container_).visible() && $('settings_table_'+container_).down('tbody').descendants('tr').length > 0) {
		$('settings_table_'+container_).show();
	}
	
	if (! $('foot_'+container_).visible()) {
		$('foot_'+container_).show();
	}
	
	repaint($('settings_table_'+container_)); // for content1 or content2
}

function configuration_del(container_, setting_) {
	var tr = $('tr_'+container_+'_'+setting_);
	tr.up().removeChild(tr);
	$('cache_table_'+container_).down('tbody').appendChild(tr);
	
	configuration_add_available($('settings_select_'+container_), $('cache_select_'+container_), setting_);
	
	if (! $('foot_'+container_).visible()) {
		$('foot_'+container_).show();
	}
	
	if (! $('foot_'+container_).visible() && $('settings_table_'+container_).down('tbody').descendants('tr').length == 0) {
		$('settings_table_'+container_).hide();
	}
	else {
		repaint($('settings_table_'+container_)); // for content1 or content2
	}
}

function configuration_show_extra_values(container_, setting_id_) {
	var cache_element = $('cache_more_info_'+container_+'_'+setting_id_);
	$('button_show_'+container_+'_'+setting_id_).hide();
	$('button_hide_'+container_+'_'+setting_id_).show();
	
	var anchor = $('tr_'+container_+'_'+setting_id_);
	var offset = anchor.cumulativeOffset();
	
	cache_element.style.top = (offset['top'] + anchor.getHeight())+'px';
	cache_element.style.left = (offset['left'] + (anchor.getWidth() / 2) - ((anchor.getWidth()*2/3) / 2))+'px';
	cache_element.style.width = (anchor.getWidth()*2/3)+'px';
	cache_element.style.height = '200px';
	cache_element.show();
	
	cache_element.observe('click', function() {
		configuration_hide_extra_values(container_, setting_id_);
	});
}

function configuration_hide_extra_values(container_, setting_id_) {
	$('cache_more_info_'+container_+'_'+setting_id_).hide();
	$('button_show_'+container_+'_'+setting_id_).show();
	$('button_hide_'+container_+'_'+setting_id_).hide();
}

function configuration_change_monitor(container_) {
	$('settings_table_'+container_).select('input', 'select').each(function(element) {
		element.observe('change', function(e) {
			if (! $('foot_'+container_).visible()) {
				$('foot_'+container_).show();
			}
		});
	});
}
