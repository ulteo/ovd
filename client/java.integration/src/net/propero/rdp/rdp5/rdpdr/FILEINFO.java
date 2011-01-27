/* FILEINFO.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.0
 * Author: tomqq (hekong@gmail.com)
 * Date: 2009/05/16
 *
 * Copyright (c) tomqq
 *
 */
package net.propero.rdp.rdp5.rdpdr;
import java.io.*;
import java.util.HashMap;
import java.util.Map;

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
	Map<String,String> file_searched = new HashMap<String, String>();
	
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
		file_searched.put(str, str);
	}
	public boolean has_searched_file(String str){
		if(file_searched.get(str)!=null){
			if(((String)file_searched.get(str)).equalsIgnoreCase(str))
				return true;
			else
				return false;
		}
		return false;
	}
	
	public void reset_file_searched_map(){
		this.file_searched = new HashMap<String,String>();
	}
}
