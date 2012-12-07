/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Samuel BOVEE <samuel@ulteo.com>
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

function array_search(arr, obj) {
	for (var i=0; i<arr.length; i++) {
		if (arr[i] == obj)
			return i;
	}
	return -1;
}

ovdsm_publication_saved_options = new Array();
ovdsm_publication_saved_options[0] = new Array();
ovdsm_publication_saved_options[1] = new Array();

function ovdsm_publication_hook_select(select_src) {
	var option = select_src.options[select_src.selectedIndex];
	var option_name = option.firstChild.data;
	var rows = new Array("group_u", "group_a");
	var ind_row = array_search(rows, select_src.name);
	var input = $("input_" + select_src.name);
	var select_dst = $("select_" + rows[(ind_row+1)%2]);
	var options_dst = select_dst.getElementsByTagName("option");

	var restore_options = function() {
		for (i=0 ; i<ovdsm_publication_saved_options[ind_row].length ; i++)
			select_dst.appendChild(ovdsm_publication_saved_options[ind_row].pop());
	}

	if (option_name == "*") {
		input.value = "";
		restore_options();
		return;
	}

	input.value = option.value;
	var list_table = $("publications_list_table");
	var trs = list_table.getElementsByTagName('tr');

	var apps = new Array();
	for (i=1; i<trs.length-1; i++) {
		tds = trs.item(i).getElementsByTagName('td');
		if (tds.item(1) == null)
			return;
		if (tds.item(ind_row).firstChild.firstChild.data == option_name)
			apps.push(tds.item((ind_row+1)%2).firstChild.firstChild.data);
	}

	restore_options();
	for (i=1; i<options_dst.length; i++) {
		for (j=0; j<apps.length; j++) {
			if (options_dst.item(i).firstChild.data == apps[j]) {
				ovdsm_publication_saved_options[ind_row].push(select_dst.removeChild(options_dst.item(i)));
				i--;
				break;
			}
		}
	}
}
