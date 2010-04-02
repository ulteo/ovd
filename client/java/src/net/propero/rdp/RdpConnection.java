/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009
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

package net.propero.rdp;

import org.ulteo.ovd.Application;
import java.io.IOException;
import java.io.InputStream;
import java.net.InetAddress;
import java.net.SocketException;
import java.net.UnknownHostException;
import java.util.ArrayList;
import java.util.Locale;
import java.util.Observable;

import net.propero.rdp.keymapping.KeyCode_FileBased;
import net.propero.rdp.keymapping.KeyMapException;
import net.propero.rdp.rdp5.cliprdr.ClipChannel;
import net.propero.rdp.rdp5.seamless.SeamlessChannel;
import net.propero.rdp.rdp5.Rdp5;
import net.propero.rdp.rdp5.VChannel;
import net.propero.rdp.rdp5.VChannels;

public class RdpConnection extends Observable implements Runnable {
	private static final String keyMapPath = "ressources/keymaps/";

	private VChannels channels = null;
	private ClipChannel clipChannel = null;
	private Rdp5 RdpLayer = null;
	public Options opt = null;
	public Common common = null;
	private RdesktopCanvas_Localised canvas = null;
	private ArrayList<Application> appsList = null;
	private String state = "disconnected";
	private String mapFile = null;
	
	public RdpConnection(Options opt_, Common common_) throws RdesktopException {
		this.init(opt_, common_);
		
		this.opt.seamlessEnabled = false;
		
	}

	public RdpConnection(Options opt_, Common common_, SeamlessChannel seamChan) throws RdesktopException {
		this.init(opt_, common_);

		if (seamChan == null) {
			this.opt.seamlessEnabled = false;
			return;
		}
		this.opt.seamlessEnabled = true;
		this.common.seamlessChannelInstance  = seamChan;
		this.common.seamlessChannelInstance.setClip(this.clipChannel);
		this.channels.register(this.common.seamlessChannelInstance);
	}

	private void init(Options opt_, Common common_) throws RdesktopException {
		this.common = common_;
		this.opt = opt_;

		this.channels = new VChannels(this.opt);
		this.appsList = new ArrayList<Application>();

		this.clipChannel = new ClipChannel(common, opt);
		this.channels.register(this.clipChannel);

		this.canvas = new RdesktopCanvas_Localised(this.opt, this.common);
	}
	
	public boolean addChannel(VChannel channel) {
		try {
			this.channels.register(channel);
		} catch (RdesktopException e) {
			e.printStackTrace();
			return false;
		}
		
		return true;
	}

	public ArrayList<Application> getAppsList() {
		return this.appsList;
	}

	public void addApp(Application app_) {
		this.appsList.add(app_);
	}

	public void setKeymap(String keymap) {
		this.mapFile = keymap;
	}
	
	protected void detectKeymap() {
		String language = System.getProperty("user.language");
		String country = System.getProperty("user.country");

		this.mapFile =  new Locale(language, country).toString().toLowerCase();
		this.mapFile = this.mapFile.replace('_', '-');
	}

	protected boolean loadKeymap() {
		InputStream istr = null;
		KeyCode_FileBased keyMap = null;

		if (this.mapFile == null)
			this.detectKeymap();

		istr = RdpConnection.class.getResourceAsStream("/" + RdpConnection.keyMapPath + this.mapFile);

		if (istr == null) {
			this.mapFile = this.mapFile.substring(0, this.mapFile.indexOf('-'));
			istr = RdpConnection.class.getResourceAsStream("/" + RdpConnection.keyMapPath + this.mapFile);
			if (istr == null) {
				this.mapFile = "en-gb";
				istr = RdpConnection.class.getResourceAsStream("/" + RdpConnection.keyMapPath + this.mapFile);
			}
		}

		try {
			keyMap = new KeyCode_FileBased_Localised(istr, opt);
		} catch (KeyMapException kmEx) {
			kmEx.printStackTrace();
			return false;
		}
		System.out.println("Autoselected keyboard map "+this.mapFile);

		if(istr != null) {
			try {
				istr.close();
			} catch (IOException ioEx) {
				System.err.println("Error: Unable to close keymap "+ this.mapFile +" : "+ ioEx);
				ioEx.printStackTrace();
			}
		}
		this.opt.keylayout = keyMap.getMapCode();

		if (keyMap != null)
			this.canvas.registerKeyboard(keyMap);

		return true;
	}
	
	public void run() {
		int logonflags = Rdp.RDP_LOGON_NORMAL;
		boolean[] deactivated = new boolean[1];
		int[] ext_disc_reason = new int[1];
		
		logonflags |= Rdp.RDP_LOGON_AUTO;

		// Configure a keyboard layout
		this.loadKeymap();
		
		/*main while*/
		this.RdpLayer = new Rdp5(channels, this.opt, this.common);
		this.common.rdp = this.RdpLayer;
		this.RdpLayer.registerDrawingSurface(this.canvas);
		this.canvas.registerCommLayer(this.RdpLayer);
		this.opt.loggedon = false;
		this.opt.readytosend = false;
		this.opt.grab_keyboard = false;
		if(this.opt.hostname.equalsIgnoreCase("localhost")) this.opt.hostname = "127.0.0.1";
		
		this.setConnecting();
		
		boolean keep_running = true;
		int exit = 0;
		
		while (keep_running) {
			if (this.RdpLayer != null) {
				// Attempt to connect to server on port Options.port
				try {
					this.RdpLayer.connect(this.opt.username, InetAddress.getByName(this.opt.hostname), logonflags, this.opt.domain, this.opt.password, this.opt.command, this.opt.directory);
					
					if (keep_running) {
						this.setConnected();
						
						this.RdpLayer.mainLoop(deactivated, ext_disc_reason);
						if (! deactivated[0]) {
							String reason = Rdesktop.textDisconnectReason(ext_disc_reason[0]);
							System.out.println("Connection terminated: " + reason);
						}
						
						keep_running = false; // exited main loop
						
						if (!this.opt.readytosend)
							System.out.println("The terminal server disconnected before licence negotiation completed.\nPossible cause: terminal server could not issue a licence.");
					}
				}catch(ConnectionException e){
					System.out.println("ConnectionException - "+e.getMessage());
					keep_running = false;
					exit = 1;
				} catch (UnknownHostException e) {
					System.out.println(e.getClass().getName() + " " + e.getMessage());
					keep_running = false;
					exit = 1;
				}catch(SocketException s){
					if(this.RdpLayer.isConnected()){
						System.out.println(s.getClass().getName() + " " + s.getMessage());
						s.printStackTrace();
						keep_running = false;
						exit = 1;
					}
				}catch (RdesktopException e) {
					System.out.println(e.getClass().getName() + " " + e.getMessage());
					e.printStackTrace(System.err);
					
					if (!this.opt.readytosend) {
						// maybe the licence server was having a comms
						// problem, retry?
						System.out.println("The terminal server reset connection before licence negotiation completed.\nPossible cause: terminal server could not connect to licence server.");
						
						if (this.RdpLayer != null && this.RdpLayer.isConnected())
							this.RdpLayer.disconnect();
						this.setDisconnected();
						System.out.println("Retrying connection...");
						keep_running = true; // retry
						continue;
					} else {
						keep_running = false;
						exit = 1;
					}
				}catch (Exception e) {
					System.out.println(e.getClass().getName() + " " + e.getMessage());
					e.printStackTrace();
				}
			} else
				System.out.println("The communications layer could not be initiated!");
		}
		exit(exit);
	}
	
	public RdesktopCanvas getCanvas() {
		return this.canvas;
	}
	
	public Common getCommon() {
		return this.common;
	}
	
	public void disconnect() {
		Rdesktop.exit(0, this.RdpLayer, null, true);
	}
	
	private void exit(int n) {
		if (this.common.rdp != null && this.common.rdp.isConnected()) {
			this.common.rdp.disconnect();
			this.common.rdp = null;
		}
		
		System.gc();
		
		if (n == 0)
			this.setDisconnected();
		else
			this.setFailed();
	}
	
	private void setState(String state_) {
		this.state = state_;
		setChanged();
		notifyObservers(state_);
	}
	
	private void setConnecting() {
		if (!this.state.equals("connecting"))
			this.setState("connecting");
	}
	
	private void setFailed() {
		if (!this.state.equals("failed"))
			this.setState("failed");
	}
	
	public void setConnected() {
		if (!this.state.equals("connected"))
			this.setState("connected");
	}
	
	private void setDisconnected() {
		if (!this.state.equals("disconnected"))
			this.setState("disconnected");
	}
	
	public String getState() {
		return this.state;
	}
}
