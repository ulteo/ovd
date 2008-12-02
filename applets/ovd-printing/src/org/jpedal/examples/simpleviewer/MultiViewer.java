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
* MultiViewer.java
* ---------------
*/

package org.jpedal.examples.simpleviewer;

//needed for some countries - do not remove

import javax.swing.*;

import org.jpedal.PdfDecoder;


import org.jpedal.gui.GUIFactory;
import org.jpedal.utils.LogWriter;

/**
 * enhance SimpleViewer with ability to include multiple windows for opening more than 1 PDF
 */
public class MultiViewer extends SimpleViewer {

	/**
	 * setup and run client
	 */
	public MultiViewer() {

		//tell user we are in multipanel display
		currentGUI.setDisplayMode(GUIFactory.MULTIPAGE);

		//enable error messages which are OFF by default
		PdfDecoder.showErrorMessages=true;

//		//Search Frame style to Use
//		//0 = external window
//		//1 = search tab
//		//2 = Button Bar
//		searchFrame.setStyle(2);

	}

	/**
	 * setup and run client passing in paramter to show if
	 * running as applet, webstart or JSP (only applet has any effect
	 * at present)
	 */
	public MultiViewer(int modeOfOperation) {

		//tell user we are in multipanel display
		currentGUI.setDisplayMode(GUIFactory.MULTIPAGE);

		//enable error messages which are OFF by default
		PdfDecoder.showErrorMessages=true;

		commonValues.setModeOfOperation(modeOfOperation);

	}

	/** main method to run the software as standalone application */
	public static void main(String[] args) {

		/**
		 * set the look and feel for the GUI components to be the
		 * default for the system it is running on
		 */
		try {
			UIManager.setLookAndFeel( UIManager.getSystemLookAndFeelClassName() );
		}catch (Exception e) {
			LogWriter.writeLog("Exception " + e + " setting look and feel");
		}

		MultiViewer current = new MultiViewer();

		if (args.length > 0)
			current.setupViewer(args[0]);
		else
			current.setupViewer();

	}

}
