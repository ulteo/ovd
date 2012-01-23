/* Disk.java
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
 * Purpose: Disk class, chanel
 */
package net.propero.rdp.rdp5.rdpdr;

import java.io.*;
import java.util.*;
import net.propero.rdp.*;

public class Disk extends RdpdrDevice{
	///for debug
	public static boolean debug_flag = false;
	public static int func_count = 0;
	
	///parameter
	public static final int DEVICE_TYPE = 0x08;
	static boolean g_notify_stamp = false;
	static Map g_fileinfo = new HashMap();//a map which hold the all FILEINFO structure.
	//private String name = "f1o"; //!!!Notice:the name can only be 8 character, otherwise will cause overflow.
	
	//flags
	public static final int OPEN_EXISTING     = 1;
	public static final int CREATE_NEW        = 2;
	public static final int OPEN_ALWAYS       = 3;
	public static final int TRUNCATE_EXISTING = 4;
	public static final int CREATE_ALWAYS     = 5;
	public static final int GENERIC_ALL		  = 0x10000000;
	public static final int GENERIC_READ	  = 0x80000000;
	public static final int GENERIC_WRITE	  = 0x40000000;
	
	//file flags
	public static final int FILE_NON_DIRECTORY_FILE       = 0x00000040;
	public static final int FILE_DIRECTORY_FILE           = 0x00000001;
	public static final int FILE_ATTRIBUTE_DIRECTORY	  = 0x00000010;
	public static final int FILE_ATTRIBUTE_NORMAL		  = 0x00000080;
	public static final int FILE_ATTRIBUTE_HIDDEN		  =	0x00000002;
	public static final int FILE_ATTRIBUTE_READONLY		  =	0x00000001;
	public static final int FILE_DELETE_ON_CLOSE          = 0x00001000;
	public static final int FILE_COMPLETE_IF_OPLOCKED     = 0x00000100;
	
	/* NT status codes for RDPDR */
	public static final int STATUS_ACCESS_DENIED		  = 0xc0000022;
	public static final int STATUS_NO_SUCH_FILE           = 0xc000000f;
	public static final int STATUS_DISK_FULL              = 0xc000007f;
	public static final int STATUS_INVALID_PARAMETER	  = 0xc000000d;
	public static final int STATUS_NO_MORE_FILES          = 0x80000006;
	public static final int MAX_OPEN_FILES                = 0x100;
	public static final int STATUS_SUCCESS				  = 0x00000000;
	public static final int STATUS_PENDING                = 0x00000103;
	public static final int STATUS_INVALID_DEVICE_REQUEST = 0xc0000010;
	public static final int STATUS_NOTIFY_ENUM_DIR        = 0xc000010c;
	public static final int STATUS_NOT_IMPLEMENTED        = 0x00000001;
	public static final int STATUS_NOT_SUPPORTED          = 0xc00000bb;
	public static final int STATUS_FILE_IS_A_DIRECTORY    = 0xc00000ba;
	public static final int STATUS_OBJECT_NAME_COLLISION  = 0xc0000035;
	
	public static final int FileFullDirectoryInformation  = 0x00000002;
	public static final int FileBothDirectoryInformation  = 0x00000003;
	public static final int FileNameInformation           = 0x0000000c;
	

	public Disk(RdpdrChannel rdpdr_, String path, String name_){
		super(rdpdr_);

		this.local_path = path;
		this.name = name_;
		this.device_type = 8;
		this.slotIsFree = false;
	}
	
	public int get_device_type(){
		return this.DEVICE_TYPE;
	}
	
	public String get_device_name(){
		return name;
	}
	
	private int getNewHandle() {
		for (int i = 0 ; i< MAX_OPEN_FILES ; i++) {
			if (this.g_fileinfo.get(i) == null) {
				return i;
			}
		}
		return -1;
	}

	public int create(int device_id, int access_mask, int share_mode, 
			int create_disposition, int flags_and_attributes, String filename, int[] result) {
		DEBUG("create");
		//parameter dealing
		long accessmask = java.lang.Math.abs(access_mask);//if accessmask<0,then get ABS value
		if(filename.endsWith("/") || filename.endsWith("\\"))
			filename = filename.substring(0, filename.length()-1);
		if(filename.length() > 256) //Filename must<256,you can verify it on xp, I did.
			return STATUS_ACCESS_DENIED;
		//dealing path
		filename = filename.replace("\\", System.getProperty("file.separator"));
		String path = this.rdpdr.g_rdpdr_device[device_id].local_path + filename;
		//path = path.replace("//", "/");

		if (path.length() == 0)
			return STATUS_NO_SUCH_FILE;

		File file1 = new File(path);
		int flags = 0;
		int handle = 0;//unsigned32 type
		String dirp = "";
		
		switch (create_disposition){
			case CREATE_ALWAYS:
				/* Delete existing file/link. */
				if(file1.exists()){
					file1.delete();
				}
				//flags = 1;
				flags |= 64;//O_CREAT;
				break;

			case CREATE_NEW:
				/* If the file already exists, then fail. */
				//flags = 2;
				flags |= 64 | 128;//O_CREAT | O_EXCL;
				break;

			case OPEN_ALWAYS:
				/* Create if not already exists. */
				//flags = 3;
				flags |= 64;//O_CREAT;
				break;

			case OPEN_EXISTING:
				/* Default behaviour */
				break;

			case TRUNCATE_EXISTING:
				/* If the file does not exist, then fail. */
				//flags = 5;
				flags |= 512;//O_TRUNC;
				break;
		}
		
		/* Get information about file and set that flag ourselfs */
		if ( file1.exists() && file1.isDirectory() ) {
			if ((flags_and_attributes & FILE_NON_DIRECTORY_FILE)!=0)
				return STATUS_FILE_IS_A_DIRECTORY;
			else
				flags_and_attributes |= FILE_DIRECTORY_FILE;
		}
		
		if ((flags_and_attributes & FILE_DIRECTORY_FILE)!=0){
			if ((flags & 64)!=0){
				try{
					file1.mkdir();
				}catch(SecurityException e){
					e.printStackTrace();
					return STATUS_ACCESS_DENIED;
				}catch(Exception e){
					e.printStackTrace();
					return STATUS_ACCESS_DENIED;
				}
			}
			dirp = path;
			if(!file1.exists()){
				return STATUS_NO_SUCH_FILE;
			}			

		}else{
			if ((accessmask & GENERIC_ALL)!=0
				    || ((accessmask & GENERIC_READ)!=0 && (accessmask & GENERIC_WRITE)!=0))
				{
					;//flags = 6;
					flags |= 2;//O_RDWR;
				}
				else if ((accessmask & GENERIC_WRITE)!=0 && (accessmask & GENERIC_READ)==0)
				{
					;//flags = 7;
					flags |= 1;//O_WRONLY;
				}
				else
				{
					;//flags = 0;
					flags |= 0;//O_RDONLY;
				}
			
			int ret = open_weak_exclusive(file1,flags,493);
			if(ret!=0){
				result[0] = handle;
				return ret;
			}
		}
		handle = getNewHandle();
		FILEINFO tempFILEINFO;
		tempFILEINFO = new FILEINFO();
		tempFILEINFO.pdir = dirp;
		tempFILEINFO.device_id = device_id;
		tempFILEINFO.flags_and_attributes = flags_and_attributes;
		tempFILEINFO.accessmask = accessmask;
		tempFILEINFO.path = path;
		tempFILEINFO.notify = new NOTIFY();
		if ((flags_and_attributes & FILE_DELETE_ON_CLOSE) > 0) {
			tempFILEINFO.delete_on_close = true;
		}
		g_notify_stamp = true;
		//Update/Add FILEINFO in g_fileinfo
		if (handle < 0) {
			System.out.println("The maximum number of opened file was reached");
			return STATUS_INVALID_PARAMETER;
		}
		this.g_fileinfo.put(handle, tempFILEINFO);
		result[0] = handle;
		return STATUS_SUCCESS;
	}
	
	public static int disk_query_information(int handle, int info_class,
			RdpPacket out){
		DEBUG("disk_query_information");
		String path;
		File tempFile = getFileFromHandle(handle);
		
		/* Set file attributes */
		int file_attributes = 0;
		if (tempFile.isDirectory())
			file_attributes |= FILE_ATTRIBUTE_DIRECTORY;
		
		if (tempFile.isHidden())
			file_attributes |= FILE_ATTRIBUTE_HIDDEN;

		if (file_attributes == 0)
			file_attributes |= FILE_ATTRIBUTE_NORMAL;
		
		if (tempFile.canRead() && !(tempFile.canWrite()))
			file_attributes |= FILE_ATTRIBUTE_READONLY;
		
		/* Return requested data */
		switch (info_class){
			case 4://FileBasicInformation
				int[] ft_low = new int[1];
				int[] ft_high = new int[1];
				long last_modify = tempFile.lastModified();
				seconds_since_1970_to_filetime(last_modify,ft_high,ft_low);
				out.setLittleEndian32(ft_low[0]);
				out.setLittleEndian32(ft_high[0]);
				out.setLittleEndian32(ft_low[0]);
				out.setLittleEndian32(ft_high[0]);
				out.setLittleEndian32(ft_low[0]);
				out.setLittleEndian32(ft_high[0]);
				out.setLittleEndian32(ft_low[0]);
				out.setLittleEndian32(ft_high[0]);
			
				out.setLittleEndian32(file_attributes);
				break;
				
			case 5://FileStandardInformation:
				out.setLittleEndian32((int)(tempFile.length()));
				out.setLittleEndian32(0);
				out.setLittleEndian32((int)(tempFile.length()));
				out.setLittleEndian32(0);
				out.setLittleEndian32(1);
				out.set8(0);
				out.set8(tempFile.isDirectory()?1:0);
				break;
			
			case 35://FileObjectIdInformation:
				out.setLittleEndian32(file_attributes);	/* File Attributes */
				out.setLittleEndian32(0);	/* Reparse Tag */
				break;
				
			default:
				System.out.print("IRP Query (File) Information class: 0x%x\n"+ info_class);
				return STATUS_INVALID_PARAMETER;
		}
		
		return STATUS_SUCCESS;
	}
	
	
	public static int disk_query_volume_information(int handle, int info_class,
			RdpPacket out){
		DEBUG("disk_query_volume_information");
		FILEINFO pfinfo = new FILEINFO();
		FsInfoType fsinfo = new FsInfoType();
		if(g_fileinfo.get(handle)!=null)
			pfinfo = (FILEINFO)g_fileinfo.get(handle);
		String path = pfinfo.path;
		File tempFile = new File(path);
		
		FsVolumeInfo(path, fsinfo);
		switch (info_class) {
			case 1://FileFsVolumeInformation:
				out.setLittleEndian32(0); /* volume creation time low */
				out.setLittleEndian32(0);	/* volume creation time high */
				out.setLittleEndian32(123456);	/* serial */
				out.setLittleEndian32(2*fsinfo.label.length());
				out.set8(0);
				out.outUnicodeString(fsinfo.label, 2*fsinfo.label.length()-2);
				break;

			case 3://case FileFsSizeInformation:
				out.setLittleEndian32(4277312);
				out.setLittleEndian32(0);
				out.setLittleEndian32(846342);
				out.setLittleEndian32(0);
				out.setLittleEndian32(4096/0x200);
				out.setLittleEndian32(0x200);
				break;

			case 5://FileFsAttributeInformation:
				out.setLittleEndian32(0x00000001 | 0x00000002); /* fs attributes */
				out.setLittleEndian32(255);	/* max length of filename */
				out.setLittleEndian32(2*fsinfo.type.length()); /* length of fs_type */
				out.outUnicodeString(fsinfo.type, 2*fsinfo.type.length()-2);
				break;

			case 2://FileFsLabelInformation:
			case 4://FileFsDeviceInformation:
			case 6://FileFsControlInformation:
			case 7://FileFsFullSizeInformation:
				long length = tempFile.length();
				
				int low = (int) length;
				int high = (int) (length >> 32);
				out.setLittleEndian32(low);                 /* Total allocation units low */
				out.setLittleEndian32(high);                /* Total allocation units high */
				out.setLittleEndian32(low);                 /* Caller allocation units low */
				out.setLittleEndian32(high);                /* Caller allocation units high */
				out.setLittleEndian32(low);                 /* Available allocation units low */
				out.setLittleEndian32(high);                /* Available allocation units high*/
				out.setLittleEndian32((int)length / 0x200); /* Sectors per allocation unit */
				out.setLittleEndian32(0x200);               /* Bytes per sector */
				break;
			case 8://FileFsObjectIdInformation:
			case 10://FileFsMaximumInformation:*/

			default:
				System.out.print("IRP Query Volume Information class: 0x%x\n"+ info_class);
				return STATUS_INVALID_PARAMETER;
		}
		return STATUS_SUCCESS;
	}
	
	static void seconds_since_1970_to_filetime(long seconds, int[] high, int[] low) {
		long ticks;
		ticks = (seconds/1000 + 11644473600L) * 10000000;
		low[0] = (int) ticks;
		high[0] = (int) (ticks >> 32);
	}
	
	static long convert_1970_to_filetime(int[] high, int[] low) {
		long ticks;
		
		// Java time is measured in milliseconds since the epoch (00:00:00 GMT, January 1, 1970) 
		ticks = low[0] + (((long)high[0]) << 32);
		ticks /= 10000000;
		ticks -= 11644473600L;
		ticks *= 1000L;

		return ticks ;
	}
	
	static void FsVolumeInfo(String fpath, FsInfoType fsinfo){
		fsinfo.label = "RDESKTOP";
		fsinfo.type = "RDPFS";
	}

	public int close(int handle) {
		DEBUG("close");
		FILEINFO finfo = (FILEINFO)g_fileinfo.get(handle);
		File fileToDelete = null;
		
		if (finfo == null)
		{
			return STATUS_NO_SUCH_FILE;
		}
		String path = finfo.get_path();
		if (finfo.delete_on_close && path != null)
		{
			try {
				fileToDelete = new File(finfo.get_path());
				if (fileToDelete.exists()) {
					fileToDelete.delete();
				}
			}
			catch (Exception e) {
				System.err.print("Unable to delete the file "+path);
				return STATUS_INVALID_PARAMETER;
			}
		}
		finfo = null;
		g_fileinfo.remove(handle);
		
		return STATUS_SUCCESS;
	}
	
	public int disk_set_information(
			int handle, int info_class, RdpPacket in, RdpPacket out){
		DEBUG("disk_set_information");
		int length, file_attributes, delete_on_close;
		int[] ft_high = new int[1];
		int[] ft_low = new int[1];
		String newname, fullpath;
		int mode;
		long write_time, change_time, access_time, mod_time;
		g_notify_stamp = true;
		FILEINFO tempfinfo = (FILEINFO)g_fileinfo.get(handle);
		
		switch (info_class){
			case 4://FileBasicInformation:
				write_time = change_time = access_time = 0;
				in.incrementPosition(4);	/* Handle of root dir? */
				in.incrementPosition(24);	/* unknown */
				
				/* CreationTime */
				ft_low[0] = in.getLittleEndian32();
				ft_high[0] = in.getLittleEndian32();
				
				/* AccessTime */
				ft_low[0] = in.getLittleEndian32();
				ft_high[0] = in.getLittleEndian32();
				if (ft_low[0]>0 || ft_high[0]>0)
					access_time = convert_1970_to_filetime(ft_high, ft_low);
				
				/* WriteTime */
				ft_low[0] = in.getLittleEndian32();
				ft_high[0] = in.getLittleEndian32();
				if (ft_low[0]>0 || ft_high[0]>0)
					write_time = convert_1970_to_filetime(ft_high, ft_low);
				
				/* ChangeTime */
				ft_low[0] = in.getLittleEndian32();
				ft_high[0] = in.getLittleEndian32();
				if (ft_low[0]>0 || ft_high[0]>0)
					change_time = convert_1970_to_filetime(ft_high, ft_low);
				
				//Get mod time
				if (write_time>0 || change_time>0)
					mod_time =write_time < change_time?write_time:change_time;
				else
					mod_time = write_time > 0 ? write_time : change_time;
				
				//Set mod time 
				File tempFile = getFileFromHandle(handle);
				if(tempFile.exists() && mod_time != 0){
					try{
						tempFile.setLastModified(mod_time);
					}catch(SecurityException e){
						e.printStackTrace();
						return STATUS_ACCESS_DENIED;
					}
				}
				break;
				
			case 10://FileRenameInformation:
				in.incrementPosition(4);	/* Handle of root dir? */
				in.incrementPosition(0x1a);	/* unknown */
				length = in.getLittleEndian32();

				if (length>0 && (length / 2) < 256) {
					/*rdp_in_unistr(in, newname, length);
					convert_to_unix_filename(newname);*/
					newname = RdpdrChannel.rdp_in_unistr(in, length);
					newname = newname.replace("\\", "/");
				}
				else
				{
					return STATUS_INVALID_PARAMETER;
				}
				
				newname = this.rdpdr.g_rdpdr_device[tempfinfo.get_device_id()].local_path + newname;
				File tmpF2 = new File(newname);
				try{
					File tmpFile = getFileFromHandle(handle);
					tmpFile.renameTo(tmpF2);
				}catch(SecurityException  e){
					e.printStackTrace();
					return STATUS_ACCESS_DENIED;
				}
				break;
				
			case 13://FileDispositionInformation:
				delete_on_close = in.getLittleEndian32();
				if (delete_on_close > 0 ||(tempfinfo.accessmask & (FILE_DELETE_ON_CLOSE | FILE_COMPLETE_IF_OPLOCKED))>0){
					tempfinfo.delete_on_close = true;
				}
				break; 
				
			case 19://FileAllocationInformation:
				/* Fall through to FileEndOfFileInformation,
				   which uses ftrunc. This is like Samba with
				   "strict allocation = false", and means that
				   we won't detect out-of-quota errors, for
				   example. */

			case 20://FileEndOfFileInformation:
				in.incrementPosition(28); /* unknown */
				length = in.getLittleEndian32();
				try {
					RandomAccessFile raf = new RandomAccessFile(tempfinfo.get_path(), "rw");
					raf.setLength(length);
					raf.close();
				} catch (FileNotFoundException e) {
					System.err.print("Unable to find " +tempfinfo.get_path());
					return STATUS_NO_SUCH_FILE;
				}
				catch(IOException e) {
					System.err.print("Unable to set end of file of " +tempfinfo.get_path()+ ": "+e.getMessage());
					return STATUS_DISK_FULL;
				}
				break;
				
			default:
				System.out.print("IRP Set File Information class: 0x%x\n" + info_class + "\n");
				return STATUS_INVALID_PARAMETER;
		}
		
		return STATUS_SUCCESS;
	}
	
	public static int disk_query_directory(
			int handle, int info_class, String pattern, RdpPacket out){
		//
		DEBUG("disk_query_directory");
		int file_attributes;
		int[] ft_low = new int[1]; int[] ft_high = new int[1];
		String dirname, fullpath = "";
		String pdir, pdirent, d_name="";
		FILEINFO pfinfo;
		pfinfo = (FILEINFO)g_fileinfo.get(handle);
		pdir = pfinfo.pdir;
		dirname = pfinfo.path;
		file_attributes = 0;
		
		switch (info_class) {
			case FileNameInformation:
			case FileBothDirectoryInformation:
			case FileFullDirectoryInformation:
				DEBUG("disk_query_directory---FileBothDirectoryInformation");
				/* If a search pattern is received, remember this pattern, and restart search */
				if (pattern != null && ! pattern.equals("")) {
					pattern = pattern.replace("\\", "/");
					pfinfo.pattern = pattern.substring(pattern.lastIndexOf('/')+1);
					pfinfo.reset_file_searched_map();

					File tmp_dir = new File(pdir);
					String[] children = tmp_dir.list();
					if (children == null)
						return STATUS_ACCESS_DENIED;
					
					if (pfinfo.pattern.equals("*")) {
						// Java do not manage . and ..
						pfinfo.add_searched_file(".");
						pfinfo.add_searched_file("..");
					}
					
					for (int i = 0 ; i < children.length ; i++) {
						if (children[i].equalsIgnoreCase(pfinfo.pattern) || pfinfo.pattern.equalsIgnoreCase("*")) {
							File f = new File(dirname + File.separator + children[i]);
							if (f.exists())
								pfinfo.add_searched_file(children[i]);
						}
					}
				}

				if (pfinfo.is_searched_file_empty())
					return STATUS_NO_MORE_FILES;
				
				d_name = pfinfo.get_next_searched_file();
				fullpath = dirname + File.separator + d_name;

				File this_file = new File(fullpath);
			    if(this_file.isDirectory())
			    	file_attributes |= FILE_ATTRIBUTE_DIRECTORY;
			    if(this_file.isHidden())
			    	file_attributes |= FILE_ATTRIBUTE_HIDDEN;
			    if(this_file.canRead() && !this_file.canWrite())
			    	file_attributes |= FILE_ATTRIBUTE_READONLY;
			    if(file_attributes==0)
			    	file_attributes |= FILE_ATTRIBUTE_NORMAL;
			    
			    /* Return requested information */
			    out.incrementPosition(8); /* unknown zero */
			    
				seconds_since_1970_to_filetime(this_file.lastModified(), ft_high, ft_low);
				out.setLittleEndian32(ft_low[0]);
				out.setLittleEndian32(ft_high[0]);	/* create time */

				seconds_since_1970_to_filetime(this_file.lastModified(), ft_high, ft_low);
				out.setLittleEndian32(ft_low[0]);	/* last_access_time */
				out.setLittleEndian32(ft_high[0]);

				seconds_since_1970_to_filetime(this_file.lastModified(), ft_high, ft_low);
				out.setLittleEndian32(ft_low[0]);	/* last_write_time */
				out.setLittleEndian32(ft_high[0]);

				seconds_since_1970_to_filetime(this_file.lastModified(), ft_high, ft_low);
				out.setLittleEndian32(ft_low[0]);	/* change_write_time */
				out.setLittleEndian32(ft_high[0]);

				out.setLittleEndian32((int)this_file.length());
				out.setLittleEndian32(0);	/* filesize high */
				out.setLittleEndian32((int)this_file.length());	 /* filesize low */
				out.setLittleEndian32(0);	/* filesize high */
				out.setLittleEndian32(file_attributes);
				out.setLittleEndian32(2 * d_name.length() + 2); // Filename length (Unicode)
				out.setLittleEndian32(0);    // EA Size
				
				if (info_class != FileFullDirectoryInformation) {
					out.set8(0);                 // ShortName length
					out.incrementPosition(24);   // Short name
				}
				
				out.outUnicodeString(d_name, 2*(d_name.length()));
				break;
				
			default:
				/* FIXME: Support FileDirectoryInformation,
				   FileFullDirectoryInformation, and
				   FileNamesInformation */
				System.out.print("IRP Query Directory sub: 0x%x\n"+info_class+"\n");
				return STATUS_INVALID_PARAMETER; 
		}
		return STATUS_SUCCESS;
	}
	
	static int disk_device_control(
			int handle, int request, RdpPacket in, RdpPacket out) {
		DEBUG("disk_device_control");
		if (((request >> 16) != 20) || ((request >> 16) != 9))
			return STATUS_INVALID_PARAMETER;

		/* extract operation */
		request >>= 2;
		request &= 0xfff;

		System.out.print("DISK IOCTL " + request + "\n");

		switch (request) {
			case 25:	/* ? */
			case 42:	/* ? */
			default:
				System.out.print("DISK IOCTL " + request + "\n");
				return STATUS_INVALID_PARAMETER;
		}
		//return STATUS_SUCCESS;
	}
	
	static int
		NotifyInfo(int handle, int info_class, NOTIFY p) {
		//
		DEBUG("NotifyInfo");
		long st_mtime,st_ctime;
		File tempFile = getFileFromHandle(handle);
		FILEINFO pfinfo = (FILEINFO)g_fileinfo.get(handle);
		
		try{
			st_mtime = tempFile.lastModified();
			st_ctime = st_mtime;
		}catch(SecurityException e){
			e.printStackTrace();
			return STATUS_ACCESS_DENIED;
		}
		p.modify_time = st_mtime;
		p.status_time = st_ctime;
		p.num_entries = 0;
		p.total_time = 0;
		
		File dir = new File(pfinfo.path);
	    String[] children = dir.list();
	    if (children == null) {
	        // Either dir does not exist or is not a directory
	    } else {
	        for (int i=0; i<children.length; i++) {
	            // Get filename of file or directory
	            String filename = children[i];
	            if(filename.equalsIgnoreCase(".") || filename.equalsIgnoreCase(".."))
	            	continue;
	            p.num_entries ++;
	            String fullName = "";
	            fullName = pfinfo.path + "filename";
	            File tempFile2 = new File(fullName);
	            try{
	            	p.total_time += (tempFile2.lastModified() + tempFile2.lastModified());
	    		}catch(SecurityException e){
	    			e.printStackTrace();
	    			return STATUS_ACCESS_DENIED;
	    		}
	            //System.out.print(filename+"\n");
	        }
	    }
		return STATUS_PENDING;
	}
	
	public static int
		disk_create_notify(int handle, int info_class) {
		DEBUG("disk_create_notify");
		FILEINFO pfinfo;
		int ret = STATUS_PENDING;
		pfinfo = (FILEINFO)g_fileinfo.get(handle);
		pfinfo.info_class = info_class;

		ret = NotifyInfo(handle, info_class, pfinfo.notify);

		if ((info_class & 0x1000)>0) {	
			/* ???? */
			if (ret == STATUS_PENDING)
				return STATUS_SUCCESS;
		}
		return ret;
	}
	
	int disk_check_notify(int handle) {
		DEBUG("disk_check_notify");
		FILEINFO pfinfo;
		int status = STATUS_PENDING;
		NOTIFY notify = new NOTIFY();

		pfinfo = (FILEINFO)g_fileinfo.get(handle);
		if (pfinfo.pdir==null || pfinfo.pdir=="")
			return STATUS_INVALID_DEVICE_REQUEST;

		status = NotifyInfo(handle, pfinfo.info_class, notify);
		if (status != STATUS_PENDING)
			return status;

		if (pfinfo.notify.modify_time!=notify.modify_time ||
				pfinfo.notify.num_entries!=notify.num_entries ||
				pfinfo.notify.status_time!=notify.status_time ||
				pfinfo.notify.total_time!=notify.total_time )
	    {
			/*printf("disk_check_notify found changed event\n"); */
			pfinfo.notify = notify;
			status = STATUS_NOTIFY_ENUM_DIR;
		}

		return status;
	}
	
	public int read(
			int handle, byte[] data, int length, int offset, int[] result) {
		DEBUG("read");
		int n = 0;
		File tempFile = getFileFromHandle(handle);;
		if(!tempFile.exists())
			return STATUS_INVALID_PARAMETER;
		if(tempFile.isDirectory())
			return STATUS_NOT_IMPLEMENTED;
		//if((offset+length)>tempFile.length())
			//return STATUS_INVALID_PARAMETER;
		
		try{
			RandomAccessFile raf = new RandomAccessFile(tempFile, "r");
			raf.seek(offset);
			n = raf.read(data, 0, length);
			if(n==-1){
				n = 0;
				raf.close();
				return STATUS_SUCCESS;
			}
			raf.close();
		}catch(Exception e){
			return STATUS_INVALID_PARAMETER;
		}
		result[0] = n;
		
		return STATUS_SUCCESS;
	}
	

	public int write(
			int handle, byte[] data, int length, int offset, int[] result) {
		DEBUG("write");
		int n = 0;
		File tempFile = getFileFromHandle(handle);
		//if((offset+length)>tempFile.length())//check offset
			//return STATUS_INVALID_PARAMETER;
		
		try{
			RandomAccessFile raf = new RandomAccessFile(tempFile, "rw");
			raf.seek(offset);
			raf.write(data, 0, length);
			raf.close();
		}catch(SecurityException e){
			e.printStackTrace();
			return STATUS_ACCESS_DENIED;
		}catch(FileNotFoundException e){
			System.out.println("File not found: "+tempFile);
			e.printStackTrace();
			return STATUS_ACCESS_DENIED;
		}catch(IOException  e){
			e.printStackTrace();
			return STATUS_ACCESS_DENIED;
		}
		n = length;
		result[0] = n;
		
		return STATUS_SUCCESS;
	}

	public int device_control(int file, int request, RdpPacket in, RdpPacket out){
		DEBUG("device_control");
		if (((request >> 16) != 20) || ((request >> 16) != 9))
			return STATUS_INVALID_PARAMETER;

		/* extract operation */
		request >>= 2;
		request &= 0xfff;

		switch (request){
			case 25:	/* ? */
			case 42:	/* ? */
			default:
				System.out.println("DISK IOCTL "+request);
				return STATUS_INVALID_PARAMETER;
		}
	}
	
	static File getFileFromHandle(int handle) {
		FILEINFO pfinfo;
		pfinfo = (FILEINFO)g_fileinfo.get(handle);

		File tempFile = new File(pfinfo.path);
		return tempFile;
	}
	
	public static void DEBUG(String str){
		if(debug_flag)
			System.out.print("\n\nfunction:"+(func_count++)+"\n"+str);
	}
	
	public int open_weak_exclusive(File file, int flags, int mode){
		if((flags & 64)!=0 && (flags & 128)!=0){
			try{
				if(file.exists())
					return STATUS_OBJECT_NAME_COLLISION;
				else
					file.createNewFile();
			}catch(Exception e){
				return STATUS_ACCESS_DENIED;
			}
		}
		else if((flags & 64)!=0){
			try{
				file.createNewFile();
			}catch(Exception e){
				return STATUS_ACCESS_DENIED;
			}
		}
		if((flags & 512)!=0){
			if(!file.exists()){
				return STATUS_NO_SUCH_FILE;//then fail
			}
		}
		if(file.isDirectory() && ((flags & 1)!=0 || (flags & 2)!=0))//EISDIR
			return STATUS_FILE_IS_A_DIRECTORY;
		if((flags & 64)==0){//ENOENT
			if(!file.exists())
				return STATUS_NO_SUCH_FILE;
		}
		if(!file.exists())
			return STATUS_NO_SUCH_FILE;
		
		/*
		switch (flags){
			case 0:
				if(!file.exists())
					return STATUS_NO_SUCH_FILE;
				break;
			case 1:
				try{
					if(file.exists())
						file.delete();
					file.createNewFile();
				}catch(Exception e){
					return STATUS_ACCESS_DENIED;
				}
				break;
			case 2:
				try{
					if(file.exists())
						return STATUS_OBJECT_NAME_COLLISION;
					file.createNewFile();
				}catch(Exception e){
					return STATUS_ACCESS_DENIED;
				}
				break;
			case 3:
				try{
					if(!file.exists())
						file.createNewFile();
				}catch(Exception e){
					return STATUS_ACCESS_DENIED;
				}
				break;
			case 4:
				if(!file.exists())
					return STATUS_NO_SUCH_FILE;
				break;
			case 5:
				if(!file.exists())
					return STATUS_NO_SUCH_FILE;
				break;
			default:
				if((flags == 6 || flags == 7) && file.isDirectory())
					return STATUS_FILE_IS_A_DIRECTORY;
				if((flags == 6 || flags == 7))
					try{
						file.createNewFile();
						return 0;
					}catch(Exception e){
						return STATUS_ACCESS_DENIED;
					}
				if(!file.exists())
					return STATUS_NO_SUCH_FILE;	
				
		}*/
		return 0;
	}
}
