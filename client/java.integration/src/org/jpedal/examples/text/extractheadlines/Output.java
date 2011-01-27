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
* Output.java
* ---------------
*/
package org.jpedal.examples.text.extractheadlines;

import java.io.FileWriter;
import java.io.PrintWriter;

public class Output {


	/**output file*/
	private String targetFile="";
	
	/**called to setup output*/
	public void open(String targetFile) {
		this.targetFile=targetFile;
	}

	/**called to write to file*/
	public void outputSection(String section, String refPage,String file) {
		
		//write message
		PrintWriter log_file = null;
		try
		{
			log_file = new PrintWriter( new FileWriter( targetFile, true ) );
			log_file.println(refPage+ ' ' +section+ ' ' +file);
			log_file.flush();
			log_file.close();
		}
		catch( Exception e )
		{
			System.err.println( "Exception " + e + " attempting to write to file " + targetFile );
		}
	}

	/**called at end of output to flush/close/release*/
	public void close() {
		
	}

}
