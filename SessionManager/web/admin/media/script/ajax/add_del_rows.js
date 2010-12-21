/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

function switchButton(node_) {
  var buf = node_.getElementsByTagName('tr');

  for (var i = 0; i < buf.length; i++) {
    var buf2 = buf[i].getElementsByTagName('input');
    for (var j = 0; j < buf2.length; j++) {
      if (buf2[j].type != 'button')
        continue;

        if (buf2[j].value == '+')
          buf2[j].style.display = 'none';
        else if (buf2[j].value == '-')
          buf2[j].style.display = '';
    }
  }
}

function add_field(clone_tr_) {
	function changeNames(row_, index_) {
		var re = /(.+\[)([0-9+])(\].+)$/;
		var rt = false;

		var elements = row_.getElementsByTagName('input');
		for (var i = 0, n = elements.length; i < n; i++) {
			if (rt == false) {
				var buf = elements[i].name.replace(re, '$2');
				rt = '$1'+(parseInt(buf)+1)+'$3';
			}

			elements[i].name = elements[i].name.replace(re, rt);
		}

		var elements2 = row_.getElementsByTagName('select');
		for (var i = 0, n = elements2.length; i < n; i++) {
			elements2[i].name = elements2[i].name.replace(re, rt);
		}
	}

  var table = clone_tr_.parentNode;

  var buf = clone_tr_.cloneNode(true);
  switchButton(table);
  var buf2 = buf.getElementsByTagName('input');
  for(var j = 0; j < buf2.length; j++) {
    if (buf2[j].type == 'text')
      buf2[j].value = '';
	if (buf2[j].type == 'button')
	  buf2[j].style.display = '';
  }
  table.appendChild(buf);

  changeNames(buf, table.rows.length-1);
}

function del_field(clone_tr_) {
  var table = clone_tr_.parentNode;

  table.removeChild(clone_tr_);

  var buf = table.lastChild.getElementsByTagName('input');
  for (var i = 0; i < buf.length; i++) {
    if (buf[i].type == 'button') {
	  if (buf[i].value == '-' && table.rows.length == 1)
	    buf[i].style.display = 'none';
      else
	    buf[i].style.display = '';
	}
  }
}
