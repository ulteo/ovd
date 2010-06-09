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