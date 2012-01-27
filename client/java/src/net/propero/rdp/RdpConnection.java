/*
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2012
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Arnaud LEGRAND <arnaud@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

import java.awt.Dimension;
import java.io.File;
import java.io.IOException;
import java.io.InputStream;
import java.net.InetAddress;
import java.net.SocketException;
import java.net.UnknownHostException;
import java.util.concurrent.CopyOnWriteArrayList;
import javax.swing.JFrame;
import javax.swing.SwingUtilities;

import net.propero.rdp.keymapping.KeyCode_FileBased;
import net.propero.rdp.keymapping.KeyMapException;
import net.propero.rdp.rdp5.seamless.SeamListener;
import net.propero.rdp.rdp5.seamless.SeamlessChannel;
import net.propero.rdp.rdp5.Rdp5;
import net.propero.rdp.rdp5.VChannel;
import net.propero.rdp.rdp5.VChannels;
import net.propero.rdp.rdp5.cliprdr.ClipChannel;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpsnd.SoundChannel;
import org.apache.log4j.Logger;

public class RdpConnection implements SeamListener, Runnable{
	public static final int RDP_PORT = 3389;
	public static final int DEFAULT_BPP = 24;
	public static final int DEFAULT_WIDTH = 800;
	public static final int DEFAULT_HEIGHT = 600;
	public static final int DEFAULT_PERSISTENT_CACHE_SIZE = 100;
	public static final String DEFAULT_KEYMAP = "us";

	public final static int STATE_DISCONNECTED = 0;
	public final static int STATE_CONNECTING = 1;
	public final static int STATE_CONNECTED = 2;
	public final static int STATE_FAILED = 3;

	protected String keyMapPath = "/resources/keymaps/";

	protected VChannels channels = null;
	protected RdpdrChannel rdpdrChannel = null;
	protected SoundChannel soundChannel = null;
	protected ClipChannel clipChannel = null;
	protected SeamlessChannel seamChannel = null;
	protected Rdp5 RdpLayer = null;
	protected Options opt = null;
	protected Common common = null;
	private RdesktopCanvas_Localised canvas = null;
	protected String mapFile = null;
	protected String inputMethod = null;
	private CopyOnWriteArrayList<RdpListener> listener = new CopyOnWriteArrayList<RdpListener>();
	private int state = STATE_DISCONNECTED;
	private int tryNumber = 0;
	private String failedMsg = null;
	private Thread connectionThread = null;
	private Logger logger = Logger.getLogger(RdpConnection.class);

	private boolean keep_running = false;

	private JFrame backstoreFrame = null;
	
	public RdpConnection(Options opt_, Common common_) {
		this.common = common_;
		this.opt = opt_;

		this.opt.width = DEFAULT_WIDTH;
		this.opt.height = DEFAULT_HEIGHT;
		this.opt.set_bpp(DEFAULT_BPP);

		this.opt.use_rdp5 = true;
		this.opt.rdp5_performanceflags = Rdp5.PERF_DISABLE_ALL;

		this.channels = new VChannels(this.opt);
	}

	public String toString() {
		return this.opt.hostname+":"+this.opt.port;
	}
	
	public int getState() {
		return this.state;
	}

	public int getTryNumber() {
		return this.tryNumber;
	}

	public String getServer() {
		return this.opt.hostname;
	}
	
	public String getUsername() {
		return this.opt.username;
	}

	public Dimension getGraphics() {
		return new Dimension(this.opt.width, this.opt.height);
	}

	public int getBpp() {
		return this.opt.server_bpp;
	}

	/**
	 * Set the host to connect on default port
	 * @param address
	 *	The RDP server address
	 */
	public void setServer(String address) {
		this.setServer(address, RDP_PORT);
	}

	/**
	 * Set the host and the port to connect
	 * @param host
	 *	The RDP server address
	 * @param port
	 *	The port to use
	 */
	public void setServer(String host, int port) {
		this.opt.hostname = host;
		this.opt.port = port;
	}

	/**
	 * Set credentials
	 * @param username
	 * @param password
	 */
	public void setCredentials(String username, String password) {
		this.opt.username = username;
		this.opt.password = password;
	}

	/**
	 * Set informations about display
	 * The default bpp is 24 bits
	 * @param width
	 * @param height
	 */
	public void setGraphic(int width, int height) {
		this.setGraphic(width, height, DEFAULT_BPP);
	}

	/**
	 * Set informations about display
	 * @param width
	 * @param height
	 * @param bpp
	 */
	public void setGraphic(int width, int height, int bpp) {
		this.opt.width = width;
		this.opt.height = height;
		this.opt.set_bpp(bpp);
	}

	public void setGraphicOffset(int x, int y) {
		this.opt.x_offset = x;
		this.opt.y_offset = y;
	}

	public void setWallpaperEnabled(boolean enabled) {
		if (enabled)
			this.opt.rdp5_performanceflags &= ~Rdp5.PERF_DISABLE_WALLPAPER;
		else
			this.opt.rdp5_performanceflags |= Rdp5.PERF_DISABLE_WALLPAPER;
	}

	public void setFullWindowDragEnabled(boolean enabled) {
		if (enabled)
			this.opt.rdp5_performanceflags &= ~Rdp5.PERF_DISABLE_FULLWINDOWDRAG;
		else
			this.opt.rdp5_performanceflags |= Rdp5.PERF_DISABLE_FULLWINDOWDRAG;
	}

	public void setMenuAnimationsEnabled(boolean enabled) {
		if (enabled)
			this.opt.rdp5_performanceflags &= ~Rdp5.PERF_DISABLE_MENUANIMATIONS;
		else
			this.opt.rdp5_performanceflags |= Rdp5.PERF_DISABLE_MENUANIMATIONS;
	}

	public void setThemingEnabled(boolean enabled) {
		if (enabled)
			this.opt.rdp5_performanceflags &= ~Rdp5.PERF_DISABLE_THEMING;
		else
			this.opt.rdp5_performanceflags |= Rdp5.PERF_DISABLE_THEMING;
	}

	public void setCursorShadowEnabled(boolean enabled) {
		if (enabled)
			this.opt.rdp5_performanceflags &= ~Rdp5.PERF_DISABLE_CURSOR_SHADOW;
		else
			this.opt.rdp5_performanceflags |= Rdp5.PERF_DISABLE_CURSOR_SHADOW;
	}

	public void setCursorSettingsEnabled(boolean enabled) {
		if (enabled)
			this.opt.rdp5_performanceflags &= ~Rdp5.PERF_DISABLE_CURSORSETTINGS;
		else
			this.opt.rdp5_performanceflags |= Rdp5.PERF_DISABLE_CURSORSETTINGS;
	}

	public void setFontSmoothingEnabled(boolean enabled) {
		if (enabled)
			this.opt.rdp5_performanceflags &= ~Rdp5.PERF_ENABLE_FONT_SMOOTHING;
		else
			this.opt.rdp5_performanceflags |= Rdp5.PERF_ENABLE_FONT_SMOOTHING;
	}

	public void setDesktopCompositionEnabled(boolean enabled) {
		if (enabled)
			this.opt.rdp5_performanceflags &= ~Rdp5.PERF_ENABLE_DESKTOP_COMPOSITION;
		else
			this.opt.rdp5_performanceflags |= Rdp5.PERF_ENABLE_DESKTOP_COMPOSITION;
	}

	public void setAllDesktopEffectsEnabled(boolean enabled) {
		if (enabled)
			this.opt.rdp5_performanceflags = Rdp5.PERF_ENABLE_ALL;
		else
			this.opt.rdp5_performanceflags = Rdp5.PERF_DISABLE_ALL;
	}

	private void initCanvas() throws RdesktopException {
		if (this.opt.width <= 0 || this.opt.height <= 0)
			throw new RdesktopException("Unable to init canvas: The desktop size is negative or nil");
		this.canvas = new RdesktopCanvas_Localised(this.opt, this.common);
		this.canvas.addFocusListener(new RdesktopFocusListener(this.canvas, this.opt));
		this.canvas.addFocusListener(clipChannel);

		this.logger.info("Desktop size: "+this.opt.width+"x"+this.opt.height);
	}
	
	protected boolean addChannel(VChannel channel) {
		try {
			this.channels.register(channel);
		} catch (RdesktopException e) {
			e.printStackTrace();
			return false;
		}
		
		return true;
	}

	protected void initSoundChannel() throws RdesktopException {
		if (this.soundChannel != null)
			return;

		this.soundChannel = new SoundChannel(this.opt, this.common);
		if (! this.addChannel(this.soundChannel))
			throw new RdesktopException("Unable to add sound channel");
	}

	protected void initRdpdrChannel() throws RdesktopException {
		if (this.rdpdrChannel != null)
			return;

		this.rdpdrChannel = new RdpdrChannel(this.opt, this.common);
		if (! this.addChannel(this.rdpdrChannel))
			throw new RdesktopException("Unable to add rdpdr channel");
	}

	/**
	 * Add clip channel
	 */
	protected void initClipChannel() throws RdesktopException {
		if (this.clipChannel != null)
			return;

		if (! ClipChannel.isAccessClipboardPermissionSet()) {
			System.out.println("Clipboard access is disabled.");
			this.clipChannel = null;
			return;
		}

		this.clipChannel = new ClipChannel(this.common, this.opt);
		if (! this.clipChannel.isSupported()) {
			this.clipChannel = null;
			return;
		}

		if (! this.addChannel(this.clipChannel))
			throw new RdesktopException("Unable to add clip channel");
		if (this.seamChannel != null)
			this.seamChannel.setClip(clipChannel);
	}

	protected void initSeamlessChannel() throws RdesktopException {
		this.opt.seamlessEnabled = true;
		if (this.seamChannel != null)
			return;

		this.seamChannel = new SeamlessChannel(this.opt, this.common);
		if (this.addChannel(this.seamChannel))
			this.seamChannel.addSeamListener(this);
		else
			throw new RdesktopException("Unable to add seamless channel");
	}

	public void setShell(String shell) {
		this.opt.command = shell;
	}

	public void setSeamForm(boolean enabled) {
		this.opt.seamformEnabled = enabled;
	}

	/**
	 * Enable/disable MPPC-BULK compression with a history buffer of 64k
	 * @param packetCompression
	 */
	public void setPacketCompression(boolean packetCompression) {
		this.opt.packet_compression = packetCompression;
	}

	/**
	 * Enable/disable volatile bitmap caching
	 * @param volatileCaching
	 */
	public void setVolatileCaching(boolean volatileCaching) {
		if ((! volatileCaching) && this.opt.persistent_bitmap_caching)
			this.setPersistentCaching(false);
		this.opt.bitmap_caching = volatileCaching;
	}

	/**
	 * Enable/disable persistent bitmap caching
	 * @param persistentCaching
	 */
	public void setPersistentCaching(boolean persistentCaching) {
		this.opt.persistent_bitmap_caching = persistentCaching;

		if (! persistentCaching)
			return;

		if (! this.opt.bitmap_caching)
			this.setVolatileCaching(true);

		this.setPersistentCachingMaxSize(DEFAULT_PERSISTENT_CACHE_SIZE);
	}

	/**
	 * Not implemented yet
	 * Specify the path where the persistent bitmap cache is
	 * @param persistentCachingPath
	 */
	public void setPersistentCachingPath(String persistentCachingPath) {
		if (persistentCachingPath == null || persistentCachingPath.equals(""))
			return;
		
		String separator = System.getProperty("file.separator");

		if (persistentCachingPath.lastIndexOf(separator) != persistentCachingPath.length()-1)
			persistentCachingPath = persistentCachingPath.concat(separator);

		this.opt.persistent_caching_path = persistentCachingPath;
	}

	/**
	 * Not implemented yet
	 * Specify the maximum size of persistent bitmap cache in MegaByte
	 * @param persistentCachingMaxSize (MB)
	 */
	public void setPersistentCachingMaxSize(int persistentCachingMaxSize) {
		if (persistentCachingMaxSize == 0)
			return;
		
		if(! System.getProperty("os.name").startsWith("Mac OS X")) {
			int maxSize = (int) ((new File(System.getProperty("user.home")).getFreeSpace()) / 1024 /1024); // convert bytes to megabytes
			if (maxSize > (persistentCachingMaxSize * 1.25))
				maxSize = persistentCachingMaxSize;
			else
				persistentCachingMaxSize = (int) (maxSize * 0.8);
		}
		this.opt.persistent_caching_max_cells = (persistentCachingMaxSize * 1024 * 1024) / PstCache.MAX_CELL_SIZE;
	}

	/**
	 * Specify the path where keymaps are
	 * @param keymapPath
	 */
	public void setKeymapPath(String keymapPath_) {
		this.keyMapPath = keymapPath_;
	}

	public void setKeymap(String keymap) {
		this.mapFile = keymap;
	}
	
	public void setInputMethod(String inputMethod) {
		if (inputMethod.equalsIgnoreCase("scancode"))
			this.opt.supportUnicodeInput = false;
		else if (inputMethod.equalsIgnoreCase("unicode"))
			this.opt.supportUnicodeInput = true;
	}

	protected boolean loadKeymap() {
		InputStream istr = null;
		KeyCode_FileBased keyMap = null;

		istr = RdpConnection.class.getResourceAsStream(this.keyMapPath + this.mapFile);

		if (istr == null) {
			int idx = this.mapFile.indexOf('-');
			this.mapFile = (idx == -1) ? DEFAULT_KEYMAP : this.mapFile.substring(idx+1, this.mapFile.length());
			istr = RdpConnection.class.getResourceAsStream(this.keyMapPath + this.mapFile);
			if (istr == null) {
				this.mapFile = DEFAULT_KEYMAP;
				istr = RdpConnection.class.getResourceAsStream(this.keyMapPath + this.mapFile);
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
		try {
			this.initCanvas();
		} catch (RdesktopException ex) {
			this.logger.fatal(ex.getMessage());
			return;
		}

		if (this.opt.seamlessEnabled) {
			this.backstoreFrame = new JFrame();
			this.backstoreFrame.setVisible(false);
			this.backstoreFrame.add(this.canvas);
			this.backstoreFrame.pack();
		}

		this.tryNumber++;

		// Configure a keyboard layout
		this.loadKeymap();
		
		/*main while*/
		this.RdpLayer = new Rdp5(channels, this.opt, this.common);
		this.common.rdp = this.RdpLayer;
		this.RdpLayer.registerDrawingSurface(this.canvas);
		this.opt.loggedon = false;
		this.opt.readytosend = false;
		this.opt.grab_keyboard = false;
		if(this.opt.hostname.equalsIgnoreCase("localhost")) this.opt.hostname = "127.0.0.1";
		
		this.keep_running = true;
		int exit = 0;

		this.fireConnecting();
		
		while (this.keep_running) {
			if (this.RdpLayer != null) {
				// Attempt to connect to server on port Options.port
				try {
					this.RdpLayer.connect(this.opt.username, InetAddress.getByName(this.opt.hostname), logonflags, this.opt.domain, this.opt.password, this.opt.command, this.opt.directory);

					if (this.keep_running) {
						this.RdpLayer.mainLoop(deactivated, ext_disc_reason, this);
						if (! deactivated[0]) {
							this.disconnect();

							String reason = Rdesktop.textDisconnectReason(ext_disc_reason[0]);
							System.out.println("Connection terminated: " + reason);
						}
						
						this.keep_running = false; // exited main loop
						
						if (!this.opt.readytosend)
							System.out.println("The terminal server disconnected before licence negotiation completed.\nPossible cause: terminal server could not issue a licence.");
					}
				}catch(ConnectionException e){
					this.failedMsg = e.getMessage();
					this.keep_running = false;
					exit = 1;
				} catch (UnknownHostException e) {
					this.failedMsg = e.getMessage();
					this.keep_running = false;
					exit = 1;
				}catch(SocketException s){
					if(this.RdpLayer.isConnected()){
						this.failedMsg = s.getMessage();
						exit = 1;
					}
					this.keep_running = false;
				}catch (RdesktopException e) {
					this.failedMsg = e.getMessage();
					
					if (!this.opt.readytosend) {
						// maybe the licence server was having a comms
						// problem, retry?
						System.out.println("The terminal server reset connection before licence negotiation completed.\nPossible cause: terminal server could not connect to licence server.");
						
						if (this.RdpLayer != null && this.RdpLayer.isConnected())
							this.RdpLayer.disconnect();
						this.fireDisconnected();
						System.out.println("Retrying connection...");
						this.keep_running = true; // retry
						continue;
					} else {
						this.keep_running = false;
						exit = 1;
					}
				} catch (Exception e) {
					System.err.println("["+this.getServer()+"] An error occured: "+e.getClass().getName()+" "+e.getMessage());
					e.printStackTrace();

					this.keep_running = false;

					if (this.state == STATE_CONNECTING)
						this.fireFailed();
					else
						this.fireDisconnected();
				}
			} else
				System.out.println("The communications layer could not be initiated!");
		}

		if (this.backstoreFrame != null) {
			SwingUtilities.invokeLater(new Runnable() {

				public void run() {
					backstoreFrame.setVisible(false);
					backstoreFrame.removeAll();
					backstoreFrame.dispose();
					backstoreFrame = null;
				}
			});
		}

		exit(exit);
	}

	/**
	 * Launch a RdpConnection thread
	 */
	public void connect() {
		this.connectionThread = new Thread(this, "RDP - " + this.opt.hostname);
		this.connectionThread.start();
	}

	/**
	 * Interrupt the thread launched by the connect() method
	 */
	public void interruptConnection() throws RdesktopException {
		if (this.connectionThread == null)
			throw new RdesktopException("Unable to interrupt the connection: The connection thread is not started");

		if (this.connectionThread.isAlive())
			this.connectionThread.interrupt();
	}
	
	public RdesktopCanvas getCanvas() {
		return this.canvas;
	}
	
	public Common getCommon() {
		return this.common;
	}
	
	public SeamlessChannel getSeamlessChannel() {
		return this.seamChannel;
	}

	public boolean isConnected() {
		return (this.RdpLayer != null && this.RdpLayer.isConnected());
	}
	
	public void disconnect() {
		if (this.common.rdp != null && this.common.rdp.isConnected()) {
			this.common.rdp.disconnect();
			this.common.rdp = null;
		}
	}

	public synchronized void stop() {
		this.keep_running = false;
		this.disconnect();

		if (this.opt.seamlessEnabled && this.seamChannel != null) {
			this.seamChannel.closeAllWindows();
		}

		if (this.soundChannel != null)
			this.soundChannel.stopPlayThread();
	}
	
	private void exit(int n) {
		this.disconnect();

		System.gc();

		if (n == 0)
			this.fireDisconnected();
		else
			this.fireFailed();
	}

	public void addRdpListener(RdpListener l) {
		this.listener.add(l);
	}
	
	public void removeRdpListener(RdpListener l) {
		this.listener.remove(l);
	}
	
	protected void fireConnected() {
		if (this.state == STATE_CONNECTED)
			return;

		this.state = STATE_CONNECTED;
		
		for(RdpListener list : this.listener) {
			list.connected(this);
		}
	}
	
	protected void fireConnecting() {
		this.state = STATE_CONNECTING;

		for(RdpListener list : this.listener) {
			list.connecting(this);
		}
	}
	
	protected void fireFailed() {
		this.stop();
		this.state = STATE_FAILED;
		
		for(RdpListener list : this.listener) {
			list.failed(this, this.failedMsg);
		}
	}
	
	protected synchronized void fireDisconnected() {
		this.stop();
		this.state = STATE_DISCONNECTED;
		
		for(RdpListener list : this.listener) {
			list.disconnected(this);
		}
	}
	
	protected void fireSeamlessEnabled() {
		for(RdpListener list : this.listener) {
			list.seamlessEnabled(this);
		}
	}

	public void ackHello(SeamlessChannel channel) {
		this.fireSeamlessEnabled();
	}

	public void setCookieElement(String fieldName, String fieldValue) {
		this.opt.rdpCookie.addCookieElement(fieldName, fieldValue);
	}
}
