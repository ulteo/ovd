/* FILEINFO.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.0
 * Author: tomqq (hekong@gmail.com)
 * Date: 2009/05/16
 *
 * Copyright (c) tomqq
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com> 2011
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
 *
 */
package net.propero.rdp.rdp5.rdpdr;
import java.io.*;
import java.util.LinkedList;


public class FILEINFO {
	public static final int PATH_MAX = 256;
	public int device_id, flags_and_attributes;
	long accessmask;	
	String path;//
	String pdir;
	String pdirent;
	String pattern;;
	boolean delete_on_close;
	NOTIFY notify;
	int info_class;
	public File file;
	LinkedList<String> file_searched = new LinkedList<String>();
	
	public int get_device_id(){
		return device_id;
	}
	public void set_device_id(int x){
		device_id = x;
	}
	
	public int get_flags_and_attributes(){
		return flags_and_attributes;
	}
	public void set_flags_and_attributes(int x){
		flags_and_attributes = x;
	}
	
	
	public int get_accessmask(){
		return (int)accessmask;
	}
	public void set_accessmask(int x){
		accessmask = x;
	}
	
	public String get_path(){
		return path;
	}
	public void set_path(String x){
		path = x;
	}
	
	public String get_pdir(){
		return pdir;
	}
	public void set_pdir(String x){
		pdir = x;
	}
	
	public String get_pdirent(){
		return pdirent;
	}
	public void set_pdirent(String x){
		pdirent = x;
	}
	
	public String get_pattern(){
		return pattern;
	}
	public void set_pattern(String x){
		pattern = x;
	}
	
	public boolean get_delete_on_close(){
		return delete_on_close;
	}
	public void set_delete_on_close(boolean x){
		delete_on_close = x;
	}
	
	public int get_info_class(){
		return info_class;
	}
	public void set_info_class(int x){
		info_class = x;
	}
	
	public void add_searched_file(String str){
		file_searched.add(str);
	}
	
	public boolean is_searched_file_empty(){
		return file_searched.isEmpty();
	}

	public String get_next_searched_file() {
		String file = file_searched.getFirst();
		file_searched.removeFirst();
		return file;
	}

	public void reset_file_searched_map(){
		this.file_searched = new LinkedList<String>();
	}
}
