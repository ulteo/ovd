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

function page_content(url,div) {
	new Ajax.Updater(
		$(div),
		url
	);
}

function field_increase(id, number) {
  var p = parseInt($(id).value);
  p+= number;

  if (! (p>0))
    return false;

  $(id).value = p;
}

function field_check_integer(field) {
  var p = parseInt($(field).value);
  if ((! isNaN(p)) && p>0)
    return true;

  $(field).value = $(field).defaultValue;
}

function markAllRows( container_id ) {
    var rows = document.getElementById(container_id).getElementsByTagName('tr');
    var unique_id;
    var checkbox;

    for ( var i = 0; i < rows.length; i++ ) {

        checkbox = rows[i].getElementsByTagName( 'input' )[0];

        if ( checkbox && checkbox.type == 'checkbox' ) {
            unique_id = checkbox.name + checkbox.value;
            if ( checkbox.disabled == false ) {
                checkbox.checked = true;
            }
        }
    }

    return true;
}

function unMarkAllRows( container_id ) {
    var rows = document.getElementById(container_id).getElementsByTagName('tr');
    var unique_id;
    var checkbox;

    for ( var i = 0; i < rows.length; i++ ) {

        checkbox = rows[i].getElementsByTagName( 'input' )[0];

        if ( checkbox && checkbox.type == 'checkbox' ) {
            unique_id = checkbox.name + checkbox.value;
            checkbox.checked = false;
        }
    }

    return true;
}

function updateMassActionsForm(form_, table_id_) {
	var rows = $(table_id_).getElementsByTagName('tr');

	for ( var i = 0; i < rows.length; i++ ) {
		var checkbox = rows[i].getElementsByTagName('input')[0];

		if ( checkbox && checkbox.type == 'checkbox' ) {
			if ( checkbox.checked ==  true ) {
				var node = document.createElement('input');
				node.setAttribute('type', 'hidden')
				node.setAttribute('name', checkbox.name);
				node.setAttribute('value', checkbox.value);
				form_.appendChild(node);
			}
		}
	}

	return true;
}

function popupOpen2(ulteoForm) {
	var my_width = screen.width;
	var my_height = screen.height;
	var new_width = 0;
	var new_height = 0;
	var pos_top = 0;
	var pos_left = 0;

	new_width = my_width;
	new_height = my_height;

	var date = new Date();
	var rand = Math.round(Math.random()*100)+date.getTime();

	var w = window.open('about:blank', 'Ulteo'+rand, 'toolbar=no,status=no,top='+pos_top+',left='+pos_left+',width='+new_width+',height='+new_height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');
	ulteoForm.target = 'Ulteo'+rand;

	return false;
}

var pos_x;
var pos_y;

function position(e) {
	pos_x = Event.pointerX(e);
	pos_y = Event.pointerY(e);
}

Event.observe(window, 'load', function() {
	Event.observe(document, 'mousemove', position, false);
});

function showInfoBulle(string_) {
	if (! $('infoBulle').visible()) {
		$('infoBulle').innerHTML = string_;
		$('infoBulle').show();

		Event.observe(document, 'mousemove', function() {
			if ($('infoBulle').visible()) {
				$('infoBulle').style.top = pos_y+20+'px';
				$('infoBulle').style.left = pos_x+20+'px';
			}
		});
	}
}

function hideInfoBulle() {
	if ($('infoBulle').visible()) {
		$('infoBulle').hide();
		$('infoBulle').innerHTML = '';
	}
}

function offContent(container) {
	$(container+'_ajax').innerHTML = '<img src="media/image/show.png" width="16" height="16" alt="+" title="+" />';
	$(container+'_content').hide();

	return true;
}

function onContent(container) {
	$(container+'_ajax').innerHTML = '<img src="media/image/hide.png" width="16" height="16" alt="-" title="-" />';
	$(container+'_content').show();

	return true;
}

function toggleContent(container) {
	if ($(container+'_content').visible())
		offContent(container);
	else
		onContent(container);

	return true;
}
