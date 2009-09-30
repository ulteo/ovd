/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
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


package org.ulteo;

import org.sshvnc.Viewer;
import org.vnc.RfbProto;
import org.vnc.rfbcaching.IRfbCachingConstants;

import com.sshtools.j2ssh.SshErrorResolver;
import com.sshtools.j2ssh.SshDialog;
import java.awt.FlowLayout;
import javax.swing.JOptionPane;

public class OvdApplet extends org.sshvnc.Applet implements SshErrorResolver {
    public static final String version = "0.2.4";


	public void init() {
		Runtime rt = Runtime.getRuntime();
		if(rt.totalMemory() == rt.maxMemory() && rt.freeMemory() < 11000000){
			System.err.println("Not enough memory to start the applet");
			JOptionPane.showMessageDialog(null, "Your Java Machine is low on virtual memory.\nPlease restart the browser before launching Ulteo Online Desktop", "Warning", JOptionPane.ERROR_MESSAGE);
			stop();
			return;
		}

		SshDialog.registerResolver(this);
		FlowLayout layout = new FlowLayout();
		layout.setHgap(0);
		layout.setVgap(0);
		this.setLayout(layout);

		super.init();
    }

	public String vncGetPassword() {
		return Utils.DecryptEncVNCString(this.vncPassword);
	}


	public void readParameters() {
		String buf;

		this.ssh.host = getParameter("ssh.host");

		String[] buffer = getParameter("ssh.port").split(",");
		if (buffer.length == 0) {
			System.err.println("no port given");
			stop();
		}
		try {
			this.ssh.port = Integer.parseInt(buffer[0]);
		} catch(NumberFormatException e) {}

		this.ssh.user = getParameter("ssh.user");
		this.ssh.password = Utils.DecryptString(getParameter("ssh.password"));

		// Read proxy parameters, if any -- by ArnauVP
		proxyType = getParameter("proxyType");
		proxyHost = getParameter("proxyHost");
		try {
			proxyPort = Integer.parseInt(getParameter("proxyPort"));
		} catch(NumberFormatException e) {}

		proxyUsername = getParameter("proxyUsername");
		proxyPassword = getParameter("proxyPassword");

		try {
			this.ssh.vncPort = Integer.parseInt(getParameter("PORT"));
		} catch(NumberFormatException e) {}

		org.vnc.Options.host = getParameter("HOST");
		this.vncPassword = getParameter("ENCPASSWORD");

		buf = getParameter("Encoding");
		if (buf != null) {
			if (buf.equalsIgnoreCase("RRE"))
				org.vnc.Options.preferredEncoding = RfbProto.EncodingRRE;
			else if (buf.equalsIgnoreCase("CoRRE"))
				org.vnc.Options.preferredEncoding = RfbProto.EncodingCoRRE;
			else if (buf.equalsIgnoreCase("Hextile"))
				org.vnc.Options.preferredEncoding = RfbProto.EncodingHextile;
			else if (buf.equalsIgnoreCase("ZRLE"))
				org.vnc.Options.preferredEncoding = RfbProto.EncodingZRLE;
			else if (buf.equalsIgnoreCase("Zlib"))
				org.vnc.Options.preferredEncoding = RfbProto.EncodingZlib;
			else if (buf.equalsIgnoreCase("Tight"))
				org.vnc.Options.preferredEncoding = RfbProto.EncodingTight;
		}

		buf = getParameter("JPEG image quality");
		if (buf != null) {
			try {
				org.vnc.Options.jpegQuality = Integer.parseInt(buf);
			} catch(NumberFormatException e) {}
		}

		buf = getParameter("Compression level");
		if (buf != null) {
			try {
				org.vnc.Options.compressLevel = Integer.parseInt(buf);
			} catch(NumberFormatException e) {}
		}

		buf = getParameter("Restricted colors");
		if (buf != null && buf.equalsIgnoreCase("yes"))
			org.vnc.Options.eightBitColors = true;

		buf = getParameter("View only");
		if (buf != null && buf.equalsIgnoreCase("yes"))
			org.vnc.Options.viewOnly = true;

		buf = getParameter("Share desktop");
		if (buf != null && buf.equalsIgnoreCase("true"))
			org.vnc.Options.shareDesktop = true;


		if (getParameter("rfb.cache.enabled") != null) {
			org.vnc.Options.cacheEnable = true;
			buf = getParameter("rfb.cache.ver.major");
			if (buf != null) {
				try {
					org.vnc.Options.cacheVerMajor = Integer.parseInt(buf);
				} catch(NumberFormatException e) {}
			}

			buf = getParameter("rfb.cache.ver.minor");
			if (buf != null) {
				try {
					org.vnc.Options.cacheVerMinor = Integer.parseInt(buf);
				} catch(NumberFormatException e) {}
			}

			buf = getParameter("rfb.cache.size");
			if (buf != null) {
				try {
					org.vnc.Options.cacheSize = Integer.parseInt(buf);
				} catch(NumberFormatException e) {}
			}


			buf = getParameter("rfb.cache.datasize");
			if (buf != null) {
				try {
					org.vnc.Options.cacheDataSize = Integer.parseInt(buf);
				} catch(NumberFormatException e) {}
			}

			buf = getParameter("rfb.cache.alg");
			if (buf.equalsIgnoreCase("FIFO"))
				org.vnc.Options.cacheMaintAlgI = IRfbCachingConstants.RFB_CACHE_MAINT_ALG_FIFO;
			else if (buf.equalsIgnoreCase("LRU"))
				org.vnc.Options.cacheMaintAlgI = IRfbCachingConstants.RFB_CACHE_MAINT_ALG_LRU;
		}
    }

    void showMessage(String msg) {
		//vncContainer.removeAll();
		JOptionPane.showMessageDialog(this, "The Online Desktop has closed.\n" +
				      "Thanks for using our service!\n", "Online Desktop session finished",JOptionPane.INFORMATION_MESSAGE);
		System.err.println("ERROR: "+msg+"\n");
	}

	public String getAppletInfo() {
		return "UlteoVNC";
	}

	public void resolvError(String error) {
		System.out.println(error);
	}

	public void logError(String errorMessage) {
		System.err.println(errorMessage);
	}
}
