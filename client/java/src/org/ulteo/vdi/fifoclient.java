package org.ulteo.vdi;

import java.io.*;

/* faire ce bout de code en C/C++ */
public class fifoclient {

	private static String fifodir = "/var/cache/vdiserver/fifo/";

	public static void main(String[] args) throws Exception {

		if (args.length != 2) throw new Exception("cmd <fifo> <msg>");

		try {
			FileOutputStream f = new FileOutputStream(fifodir + args[0]);
			PrintWriter pw = new PrintWriter(new BufferedOutputStream(f));
			pw.println(args[1]);
			pw.close();
		} catch (FileNotFoundException e) {
			throw new FileNotFoundException("le tube nommé n'existe pas");
		}
	}
}