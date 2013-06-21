/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
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


function loadFileAsText(self, area) {
        if ( window.FileReader ) {
                var fileToLoad = self.files[0];
                var fileReader = new FileReader();
                fileReader.onload = function(fileLoadedEvent) {
                        var content = fileLoadedEvent.target.result;
			editAreaLoader.setValue(area, content);
                };
                fileReader.readAsText(fileToLoad, "UTF-8");
        } else {
                
                var fso  = new ActiveXObject("Scripting.FileSystemObject"); 
                var fh = fso.OpenTextFile(self.value, 1); 
                var content = fh.ReadAll(); 
                fh.Close();
		editAreaLoader.setValue(area, content);
        }
}


function initScriptArea(areaID) {
	editAreaLoader.init({
		id: areaID,
		syntax: "basic",
		start_highlight: true,
		allow_resize: true,
		font_size: 12,
		toolbar: "highlight",
		allow_toggle: false,
		replace_tab_by_spaces: 4
	});
}


function changeScriptAreaSyntax(container, area) {
	var lang = container.options[container.selectedIndex].value;
	lang = lang.toLowerCase()
	var ret = editAreaLoader.execCommand(area,'change_syntax', lang);
}

