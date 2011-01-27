/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
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
 */

package org.ulteo.vdi;

import java.io.*;
import java.util.concurrent.*;

import javax.swing.JFrame;
import javax.swing.JOptionPane;

public class fifoclient {

	private static String fifodir = "/var/cache/vdiserver/fifo/";
	
	public static void main(String[] args) throws Exception {

		if (args.length != 2) throw new Exception("cmd <fifo> <cmd>");
		final String pipe = args[0];
		final String cmd = args[1];
		
		FutureTask<?> theTask = null;
	    theTask = new FutureTask<Object>(new Runnable() {
	        public void run() {
	    		FileOutputStream f = null;
	    		try {
	    			f = new FileOutputStream(fifodir + pipe);
	    		} catch (FileNotFoundException e) {
	    			System.out.println("le tube nommé " + pipe + " n'existe pas");
	    			System.exit(1);
	    		}
				PrintWriter pw = new PrintWriter(new BufferedOutputStream(f));
				pw.println(cmd);
				pw.close();
	        }
	    }, null);
	    
		try {
		    new Thread(theTask).start();
		    theTask.get(5L, TimeUnit.SECONDS); 
		}
		catch (TimeoutException e) {
			JOptionPane.showMessageDialog(new JFrame(),
				cmd + " can't be launched.\n Maybe the VDI server crashed !\n Maybe not...",
			    "Launch application failed",
			    JOptionPane.ERROR_MESSAGE);
			System.exit(1);
		}
	}

}
