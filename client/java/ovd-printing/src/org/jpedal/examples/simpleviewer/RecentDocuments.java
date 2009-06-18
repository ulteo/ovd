/**
* ===========================================
* Java Pdf Extraction Decoding Access Library
* ===========================================
*
* Project Info:  http://www.jpedal.org
* (C) Copyright 1997-2008, IDRsolutions and Contributors.
*
* 	This file is part of JPedal
*
    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    General Public License for more details.

    You should have received a copy of the GNU General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


*
* ---------------
* RecentDocuments.java
* ---------------
*/
package org.jpedal.examples.simpleviewer;

import java.util.Stack;
import java.util.StringTokenizer;

import org.jpedal.examples.simpleviewer.utils.PropertiesFile;


public class RecentDocuments {
	
	int noOfRecentDocs;
	PropertiesFile properties;
	
	private Stack previousFiles = new Stack();
	private Stack nextFiles = new Stack();

	public RecentDocuments(int noOfRecentDocs, PropertiesFile properties) {
		
		this.noOfRecentDocs=noOfRecentDocs;
		this.properties=properties;
		
	}
	
	String getShortenedFileName(String fileNameToAdd) {
		final int maxChars = 30;
		
		if (fileNameToAdd.length() <= maxChars)
			return fileNameToAdd;
		
		StringTokenizer st = new StringTokenizer(fileNameToAdd,"\\/");
		
		int noOfTokens = st.countTokens();
		String[] arrayedFile = new String[noOfTokens];
		for (int i = 0; i < noOfTokens; i++)
			arrayedFile[i] = st.nextToken();
		
		String filePathBody = fileNameToAdd.substring(arrayedFile[0].length(),
				fileNameToAdd.length() - arrayedFile[noOfTokens - 1].length());
		
		StringBuffer sb = new StringBuffer(filePathBody);
		
		for (int i = noOfTokens - 2; i > 0; i--) {
			
			//<start-13>
			int start = sb.lastIndexOf(arrayedFile[i]);
			//<end-13>
			
			//<start-13>
			/**
			//<end-13>
			int start = sb.toString().lastIndexOf(arrayedFile[i]);
			/**/
			
			int end = start + arrayedFile[i].length();
			sb.replace(start, end, "...");

			if (sb.toString().length() <= maxChars)
				break;
		}
		
		return arrayedFile[0] + sb + arrayedFile[noOfTokens - 1];
	}

	public String getPreviousDocument() {
		
		String fileToOpen =null;
		
		if(previousFiles.size() > 1){
			nextFiles.push(previousFiles.pop());
			fileToOpen = (String)previousFiles.pop();	
		}
		
		return fileToOpen;
	}
	
	public String getNextDocument() {
		
		String fileToOpen =null;
		
		if(!nextFiles.isEmpty())
			fileToOpen = (String)nextFiles.pop();
		
		return fileToOpen;
	}

	public void addToFileList(String selectedFile) {
		previousFiles.push(selectedFile);
		
		
	}

	
}
