/* Rdesktop.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Author: tomqq <hekong@gmail.com> 2009
 * Date: $Date: 2007/03/08 00:26:22 $
 *
 * Copyright (c) 2005 Propero Limited
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author: Thomas MOUTON <thomas@ulteo.com> 2011
 * Author: Julien LANGLOIS <julien@ulteo.com> 2009
 * Author: Samuel BOVEE <samuel@ulteo.com> 2010
 *
 * Purpose: Main class, launches session
 */
package net.propero.rdp;

import java.io.File;
import java.io.IOException;
import java.io.InputStream;
import java.net.*;
import java.awt.*;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Locale;

import net.propero.rdp.keymapping.KeyCode_FileBased;
import net.propero.rdp.keymapping.KeyMapException;
import net.propero.rdp.rdp5.Rdp5;
import net.propero.rdp.rdp5.VChannels;
import net.propero.rdp.rdp5.cliprdr.ClipChannel;
import net.propero.rdp.rdp5.rdpdr.Disk;
import net.propero.rdp.rdp5.rdpdr.Printer;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpdr.RdpdrDevice;
import net.propero.rdp.rdp5.rdpsnd.SoundChannel;
import net.propero.rdp.rdp5.seamless.SeamlessChannel;
import net.propero.rdp.tools.SendEvent;

import org.apache.log4j.*;
import gnu.getopt.*;

public class Rdesktop {
    
    /**
     * Translate a disconnect code into a textual description of the reason for the disconnect
     * @param reason Integer disconnect code received from server
     * @return Text description of the reason for disconnection
     */
    public static String textDisconnectReason(int reason)
    {
        String text;

        switch (reason)
        {
            case exDiscReasonNoInfo:
                text = "No information available";
                break;

            case exDiscReasonAPIInitiatedDisconnect:
                text = "Server initiated disconnect";
                break;

            case exDiscReasonAPIInitiatedLogoff:
                text = "Server initiated logoff";
                break;

            case exDiscReasonServerIdleTimeout:
                text = "Server idle timeout reached";
                break;

            case exDiscReasonServerLogonTimeout:
                text = "Server logon timeout reached";
                break;

            case exDiscReasonReplacedByOtherConnection:
                text = "Another user connected to the session";
                break;

            case exDiscReasonOutOfMemory:
                text = "The server is out of memory";
                break;

            case exDiscReasonServerDeniedConnection:
                text = "The server denied the connection";
                break;

            case exDiscReasonServerDeniedConnectionFips:
                text = "The server denied the connection for security reason";
                break;

            case exDiscReasonLicenseInternal:
                text = "Internal licensing error";
                break;

            case exDiscReasonLicenseNoLicenseServer:
                text = "No license server available";
                break;

            case exDiscReasonLicenseNoLicense:
                text = "No valid license available";
                break;

            case exDiscReasonLicenseErrClientMsg:
                text = "Invalid licensing message";
                break;

            case exDiscReasonLicenseHwidDoesntMatchLicense:
                text = "Hardware id doesn't match software license";
                break;

            case exDiscReasonLicenseErrClientLicense:
                text = "Client license error";
                break;

            case exDiscReasonLicenseCantFinishProtocol:
                text = "Network error during licensing protocol";
                break;

            case exDiscReasonLicenseClientEndedProtocol:
                text = "Licensing protocol was not completed";
                break;

            case exDiscReasonLicenseErrClientEncryption:
                text = "Incorrect client license enryption";
                break;

            case exDiscReasonLicenseCantUpgradeLicense:
                text = "Can't upgrade license";
                break;

            case exDiscReasonLicenseNoRemoteConnections:
                text = "The server is not licensed to accept remote connections";
                break;

            default:
                if (reason > 0x1000 && reason < 0x7fff)
                {
                    text = "Internal protocol error "+String.format("0x%08x", reason);
                }
                else
                {
                    text = "Unknown reason "+String.format("0x%08x", reason);
                }
        }
        return text;
    }

    /* RDP5 disconnect PDU */
    public static final int exDiscReasonNoInfo = 0x0000;
    public static final int exDiscReasonAPIInitiatedDisconnect = 0x0001;
    public static final int exDiscReasonAPIInitiatedLogoff = 0x0002;
    public static final int exDiscReasonServerIdleTimeout = 0x0003;
    public static final int exDiscReasonServerLogonTimeout = 0x0004;
    public static final int exDiscReasonReplacedByOtherConnection = 0x0005;
    public static final int exDiscReasonOutOfMemory = 0x0006;
    public static final int exDiscReasonServerDeniedConnection = 0x0007;
    public static final int exDiscReasonServerDeniedConnectionFips = 0x0008;
    public static final int exDiscReasonLicenseInternal = 0x0100;
    public static final int exDiscReasonLicenseNoLicenseServer = 0x0101;
    public static final int exDiscReasonLicenseNoLicense = 0x0102;
    public static final int exDiscReasonLicenseErrClientMsg = 0x0103;
    public static final int exDiscReasonLicenseHwidDoesntMatchLicense = 0x0104;
    public static final int exDiscReasonLicenseErrClientLicense = 0x0105;
    public static final int exDiscReasonLicenseCantFinishProtocol = 0x0106;
    public static final int exDiscReasonLicenseClientEndedProtocol = 0x0107;
    public static final int exDiscReasonLicenseErrClientEncryption = 0x0108;
    public static final int exDiscReasonLicenseCantUpgradeLicense = 0x0109;
    public static final int exDiscReasonLicenseNoRemoteConnections = 0x010a;
    
    public static Logger logger = Logger.getLogger("net.propero.rdp");

	static boolean keep_running;

	static boolean showTools;

	static final String keyMapPath = "resources/keymaps/";

	static String mapFile = null;

	static String keyMapLocation = "";

	static SendEvent toolFrame = null;
    
	public static Options opt = new Options();
	public static Common common = new Common();
	

	/**
	 * Outputs version and usage information via System.err
	 * 
	 */
	public static void usage() {
		System.err.println("UlteoRDP version " + Version.version);
		System.err.println("Usage: java net.propero.rdp.Rdesktop [options] server[:port]");
		System.err.println("	-b 							bandwidth saving (good for 56k modem, but higher latency");
		System.err.println("	-c DIR						working directory");
		System.err.println("	-d DOMAIN					logon domain");
		System.err.println("	-f[l]						full-screen mode [with Linux KDE optimization]");
		System.err.println("	-g WxH						desktop geometry");
		System.err.println("	-m MAPFILE					keyboard mapping file for terminal server");
		System.err.println("	-l LEVEL					logging level {DEBUG, INFO, WARN, ERROR, FATAL}");
		System.err.println("	-n HOSTNAME					client hostname");
		System.err.println("	-p PASSWORD					password");
		System.err.println("	-s SHELL					shell");
		System.err.println("	-A SEAMLESS					enable SeamlessRDP mode");
		System.err.println("	-V SOUND					enable sound mapping");
		System.err.println("	-D DISKPATH                                        enable disk mapping");
		System.err.println("	-P auto-detect|name[,name]*	enable printer mapping");
		System.err.println("	-t NUM						RDP port (default 3389)");
		System.err.println("	-T TITLE					window title");
		System.err.println("	-u USERNAME					user name");
		System.err.println("	-o BPP						bits-per-pixel for display");
        System.err.println("    -r path                     path to load licence from (requests and saves licence from server if not found)");
        System.err.println("    --save_licence              request and save licence from server");
        System.err.println("    --load_licence              load licence from file");
        System.err.println("    --console                   connect to console");
		System.err.println("	--debug_key 				show scancodes sent for each keypress etc");
		System.err.println("	--debug_hex 				show bytes sent and received");
		System.err.println("	--no_remap_hash 			disable hash remapping");
		System.err.println("	--quiet_alt 				enable quiet alt fix");
		System.err.println("	--no_encryption				disable encryption from client to server");
		System.err.println("	--use_rdp4					use RDP version 4");
        //System.err.println("    --enable_menu               enable menu bar");
        System.err.println("	--log4j_config=FILE			use FILE for log4j configuration");
        System.err.println("Example: java net.propero.rdp.Rdesktop -g 800x600 -l WARN m52.propero.int");
		Rdesktop.exit(0, null, null, true);
	}
  
  
	/**
	 * 
	 * @param args
	 * @throws OrderException
	 * @throws RdesktopException
	 */
	public static void main(String[] args) throws OrderException,
			RdesktopException {
		
        // Ensure that static variables are properly initialised
        keep_running = true;
        showTools = false;
        keyMapLocation = "";
        toolFrame = null;

		BasicConfigurator.configure();
		logger.setLevel(Level.INFO);

		// Attempt to run a native RDP Client

		RDPClientChooser Chooser = new RDPClientChooser(opt);

		if (Chooser.RunNativeRDPClient(args)) {
            if(!common.underApplet) System.exit(0);
		}

		// Failed to run native client, drop back to Java client instead.

		// parse arguments

		int logonflags = Rdp.RDP_LOGON_NORMAL;

		boolean fKdeHack = false;
		int c;
		String arg;
		StringBuffer sb = new StringBuffer();
		LongOpt[] alo = new LongOpt[15];
		alo[0] = new LongOpt("debug_key", LongOpt.NO_ARGUMENT, null, 0);
		alo[1] = new LongOpt("debug_hex", LongOpt.NO_ARGUMENT, null, 0);
		alo[2] = new LongOpt("no_paste_hack", LongOpt.NO_ARGUMENT, null, 0);
		alo[3] = new LongOpt("log4j_config", LongOpt.REQUIRED_ARGUMENT, sb, 0);
		alo[4] = new LongOpt("packet_tools", LongOpt.NO_ARGUMENT, null, 0);
		alo[5] = new LongOpt("quiet_alt", LongOpt.NO_ARGUMENT, sb, 0);
		alo[6] = new LongOpt("no_remap_hash", LongOpt.NO_ARGUMENT, null, 0);
		alo[7] = new LongOpt("no_encryption", LongOpt.NO_ARGUMENT, null, 0);
		alo[8] = new LongOpt("use_rdp4", LongOpt.NO_ARGUMENT, null, 0);
		alo[9] = new LongOpt("use_ssl", LongOpt.NO_ARGUMENT, null, 0);
        alo[10] = new LongOpt("enable_menu", LongOpt.NO_ARGUMENT, null, 0);
        alo[11] = new LongOpt("console", LongOpt.NO_ARGUMENT, null, 0);
        alo[12] = new LongOpt("load_licence", LongOpt.NO_ARGUMENT, null, 0);
        alo[13] = new LongOpt("save_licence", LongOpt.NO_ARGUMENT, null, 0);
        alo[14] = new LongOpt("persistent_caching", LongOpt.NO_ARGUMENT, null, 0);
        
		String progname = "UlteoRDP";

		Getopt g = new Getopt("properJavaRDP", args,
				"bc:d:f::g:k:l:m:n:p:s:A:VP:D:t:T:u:o:r:z", alo);

		ClipChannel clipChannel = new ClipChannel(common, opt);
		SeamlessChannel seamChannel = null;
		SoundChannel soundChannel;
		RdpdrChannel rdpdrChannel;
		RdpdrChannel rdpdrChannelDisk;
		HashMap<String, String> diskList = new HashMap<String, String>();

		while ((c = g.getopt()) != -1) {
			switch (c) {

			case 0:
				switch (g.getLongind()) {
				case 0:
					opt.debug_keyboard = true;
					break;
				case 1:
					opt.debug_hexdump = true;
					break;
				case 2:
					break;
				case 3:
					arg = g.getOptarg();
					PropertyConfigurator.configure(arg);
					logger.info("Log4j using config file " + arg);
					break;
				case 4:
					showTools = true;
					break;
				case 5:
					opt.altkey_quiet = true;
					break;
				case 6:
					opt.remap_hash = false;
					break;
				case 7:
					opt.packet_encryption = false;
					break;
				case 8:
					opt.use_rdp5 = false;
					opt.set_bpp(8);
					break;
				case 9:
					opt.use_ssl = true;
					break;
                case 10:
                	opt.enable_menu = true;
                    break;
                case 11:
                	opt.console_session = true;
                    break;
                case 12:
                    opt.load_licence = true;
                    break;
                case 13:
                    opt.save_licence = true;
                    break;
                case 14:
                    opt.persistent_bitmap_caching = true;
                    break;
				default:
					usage();
				}
				break;

			case 'z':
				opt.packet_compression = true;
				break;
			case 'o':
				opt.set_bpp(Integer.parseInt(g.getOptarg()));
				break;
			case 'b':
				opt.low_latency = false;
				break;
			case 'm':
				mapFile = g.getOptarg();
				break;
			case 'c':
				opt.directory = g.getOptarg();
				break;
			case 'd':
				opt.domain = g.getOptarg();
				break;
			case 'f':
				Dimension screen_size = Toolkit.getDefaultToolkit()
						.getScreenSize();
				// ensure width a multiple of 4
				opt.width = screen_size.width & ~3;
				opt.height = screen_size.height;
				opt.fullscreen = true;
				arg = g.getOptarg();
				if (arg != null) {
					if (arg.charAt(0) == 'l')
						fKdeHack = true;
					else {
						System.err.println(progname
								+ ": Invalid fullscreen option '" + arg + "'");
						usage();
					}
				}
				break;
			case 'g':
				arg = g.getOptarg();
				int cut = arg.indexOf("x", 0);
				if (cut == -1) {
					System.err.println(progname + ": Invalid geometry: " + arg);
					usage();
				}
				opt.width = Integer.parseInt(arg.substring(0, cut)) & ~3;
				opt.height = Integer.parseInt(arg.substring(cut + 1));
				break;
			case 'k':
				arg = g.getOptarg();
				//opt.keylayout = KeyLayout.strToCode(arg);
				if (opt.keylayout == -1) {
					System.err.println(progname + ": Invalid key layout: "
							+ arg);
					usage();
				}
				break;
			case 'l':
				arg = g.getOptarg();
				switch (arg.charAt(0)) {
				case 'd':
				case 'D':
					logger.setLevel(Level.DEBUG);
					break;
				case 'i':
				case 'I':
					logger.setLevel(Level.INFO);
					break;
				case 'w':
				case 'W':
					logger.setLevel(Level.WARN);
					break;
				case 'e':
				case 'E':
					logger.setLevel(Level.ERROR);
					break;
				case 'f':
				case 'F':
					logger.setLevel(Level.FATAL);
					break;
				default:
					System.err.println(progname + ": Invalid debug level: "
							+ arg.charAt(0));
					usage();
				}
				break;
			case 'n':
				opt.hostname = g.getOptarg();
				break;
			case 'p':
				opt.password = g.getOptarg();
				logonflags |= Rdp.RDP_LOGON_AUTO;
				break;
			case 'A':
				opt.seamlessEnabled = true;
				logger.info("Seamless mode enabled");
				break;
			case 'V':
				opt.soundEnabled = true;
				logger.info("Sound mapping enabled");
				break;
			case 'D':
				String diskOpt = g.getOptarg().trim();
				//String diskOpt = new String("C:\\Documents and Settings\\john,C:\\,C:\\test");
				if (diskOpt.length()>0) {
					System.out.println("diskOpt: "+diskOpt);
					/*diskOpt.replace("\\", "\\\\");
					diskOpt.replace("\"", "\\\"");
					diskOpt.replace(" ", "\\ ");*/
					System.out.println("diskOpt: "+diskOpt);
					ArrayList<String> list = new ArrayList<String>(Arrays.asList(diskOpt.split(",")));
 					
					for(String opt : list) {
						System.out.println(opt);
						String diskName = null;
						String diskPath = null;
						if (opt.contains("=")) {
							int pos = opt.indexOf('=');
							diskName = opt.substring(0, pos);
							diskPath = opt.substring(pos+1);
						}
						else
							diskPath = opt;
						
						File f = new File(diskPath); 
						if (! f.exists()) {
							logger.error("Unable to open '"+diskPath+"': the file does not exist.");
							Rdesktop.exit(0, null, null, true);
						}
						if(! f.canRead()) {
							logger.error("Unable to open '"+diskPath+"': you can't read this file.");
							Rdesktop.exit(0, null, null, true);
						}
						if(! f.isDirectory()) {
							logger.error("Unable to open '"+diskPath+"': this file is not a directory.");
							Rdesktop.exit(0, null, null, true);
						}
						
						if (diskName == null) {
							if (f.getName().length() > 0)
								diskName = f.getName();
							else
								diskName = f.getPath();
						}
						
						diskName = diskName.replace("\\", "");
						diskName = diskName.replace("/", "");
						diskName = diskName.replace(":", "");
						diskName = diskName.replace("*", "");
						diskName = diskName.replace("?", "");
						diskName = diskName.replace("\"", "");
						diskName = diskName.replace("<", "");
						diskName = diskName.replace(">", "");
						diskName = diskName.replace("|", "");
						
						if (diskName.length() > 7)
							diskName = diskName.substring(0, 7);
						
						if (diskName.length() == 0)
							diskName = new String("root");
						
						diskList.put(diskName, diskPath);
						System.out.println("diskName: "+diskName+" | diskPath: "+diskPath);
 					}
					
					if (diskList.size() > 0) {
						opt.rdpdrEnabled = true;
						opt.diskEnabled = true;
						logger.info("Disk mapping enabled");
					}
				}
				break;
 			case 'P':
 				String buffer = g.getOptarg();
 				if (buffer.equalsIgnoreCase("auto-detect")) {
 					opt.printers = Printer.getAllAvailable();
 				}
 				if (opt.printers.length > 0){
						opt.printerEnabled = true;
				}
 				else {
 					ArrayList<String> list = new ArrayList<String>(Arrays.asList(buffer.split(",")));
 					Iterator<String> iterator = list.iterator();
 					while (iterator.hasNext()) {
 						String name = iterator.next();
 						if (! Printer.getPrinterByName(name))
 							list.remove(name);
 					}
 				
					if (list.size() > 0) {
						opt.printerEnabled = true;
						opt.printers = list.toArray(new String[list.size()]);
					}
				}


 				break;				
			case 's':
				String command = g.getOptarg();
				opt.command = command.replace("$$$", " ");
				System.err.println("***** " + opt.command + " ****");
				break;
			case 'u':
				opt.username = g.getOptarg();
				break;
			case 't':
				arg = g.getOptarg();
				try {
					opt.port = Integer.parseInt(arg);
				} catch (NumberFormatException nex) {
					System.err.println(progname + ": Invalid port number: "
							+ arg);
					usage();
				}
				break;
			case 'T':
				opt.windowTitle = g.getOptarg().replace('_', ' ');
				break;
            case 'r':
                opt.licence_path = g.getOptarg();
                break;
                
			case '?':
			default:
				usage();
				break;

			}
		}

		if (fKdeHack) {
			opt.height -= 46;
		}

		String server = null;

		if (g.getOptind() < args.length) {
			int colonat = args[args.length - 1].indexOf(":", 0);
			if (colonat == -1) {
				server = args[args.length - 1];
			} else {
				server = args[args.length - 1].substring(0, colonat);
				opt.port = Integer.parseInt(args[args.length - 1]
						.substring(colonat + 1));
			}
		} else {
			System.err.println(progname + ": A server name is required!");
			usage();
		}
		
		if (opt.seamlessEnabled)
		{
//			if (opt.win_button_size == 0)
//			{
//				System.err.println("You cannot use -S and -A at the same time\n");
//				return;
//			}
			opt.rdp5_performanceflags &= ~Rdp5.PERF_DISABLE_FULLWINDOWDRAG;
			/*if (opt.width != 800 && opt.height != 600)
			{
				System.err.println("You cannot use -g and -A at the same time\n");
				return;
			}*/
			if (opt.fullscreen)
			{
				System.err.println("You cannot use -f and -A at the same time\n");
				return;
			}
			if (opt.hide_decorations)
			{
				System.err.println("You cannot use -D and -A at the same time\n");
				return;
			}
			if (!opt.use_rdp5)
			{
				System.err.println("You cannot use --use_rdp4 and -A at the same time\n");
				return;
			}

			GraphicsConfiguration gc = new Frame().getGraphicsConfiguration();
			Insets insets = Toolkit.getDefaultToolkit().getScreenInsets(gc);
			logger.debug("insects:::"+insets);

			Dimension dim = Toolkit.getDefaultToolkit().getScreenSize();
			// Ensure that width is multiple of 4
			// Prevent artifact on screen with a with resolution
			// not divisible by 4
			opt.width = (int)(dim.getWidth() - insets.left -insets.right) & ~3;
			opt.height = (int)(dim.getHeight() - insets.bottom - insets.top);


			opt.grab_keyboard = false;
		}
		
		
		
        VChannels channels = new VChannels(opt);

		// Initialise all RDP5 channels
		if (opt.use_rdp5) {
			// TODO: implement all relevant channels
			if (opt.map_clipboard) {
				channels.register(clipChannel);
			}
			if (opt.seamlessEnabled) {
				seamChannel = new SeamlessChannel(opt, common);
				seamChannel.setClip(clipChannel);
				channels.register(seamChannel);
			}
			if (opt.soundEnabled) {
				soundChannel = new SoundChannel(opt, common);
				channels.register(soundChannel);
				common.soundChannel = soundChannel;
			}
			if (opt.rdpdrEnabled) {
				rdpdrChannel = new RdpdrChannel(opt, common);
				if (opt.diskEnabled) {
					for (String diskName : diskList.keySet()) {
						String diskPath = diskList.get(diskName);
						
						RdpdrDevice disk = new Disk(rdpdrChannel, diskPath, diskName);
						rdpdrChannel.register(disk);
					}
				}
				if (opt.printerEnabled) {
					rdpdrChannel = new RdpdrChannel(opt, common);
					for(int i=0; i<opt.printers.length; i++) {
						Printer p = new Printer(rdpdrChannel, opt.printers[i],opt.printers[i] ,true);
						rdpdrChannel.register(p);
					}
					channels.register(rdpdrChannel);
				}
			}
		}

		// Now do the startup...

		logger.info("UlteoRDP version " + Version.version);

		if (args.length == 0)
			usage();

		String java = System.getProperty("java.specification.version");
		logger.info("Java version is " + java);

		String os = System.getProperty("os.name");
		String osvers = System.getProperty("os.version");

		if (os.equals("Windows 2000") || os.equals("Windows XP"))
			opt.built_in_licence = true;

		logger.info("Operating System is " + os + " version " + osvers);

		if (os.startsWith("Linux"))
			Constants.OS = Constants.LINUX;
		else if (os.startsWith("Windows"))
			Constants.OS = Constants.WINDOWS;
		else if (os.startsWith("Mac"))
			Constants.OS = Constants.MAC;

		if (Constants.OS == Constants.MAC)
			opt.caps_sends_up_and_down = false;

		Rdp5 RdpLayer = null;
		common.rdp = RdpLayer;
		RdesktopFrame window = new RdesktopFrame_Localised(opt, common);
		window.setClip(clipChannel);

		if (seamChannel != null)
			seamChannel.setMainFrame(window);

		window.show();
		
		// Configure a keyboard layout
		InputStream istr = null;
		KeyCode_FileBased keyMap = null;
		if (mapFile == null) {
			String language = System.getProperty("user.language");
			String country = System.getProperty("user.country");
			mapFile =  new Locale(language, country).toString().toLowerCase();
			mapFile = mapFile.replace('_', '-');
			istr = Rdesktop.class.getResourceAsStream("/" + keyMapPath + mapFile);
			if (istr == null) {
				mapFile = mapFile.substring(0, mapFile.indexOf('-'));
				istr = Rdesktop.class.getResourceAsStream("/" + keyMapPath + mapFile);
				if (istr == null) {
					mapFile = "en-gb";
					istr = Rdesktop.class.getResourceAsStream("/" + keyMapPath + mapFile);
				}
			}
			try {
				keyMap = new KeyCode_FileBased_Localised(istr, opt);
			} catch (KeyMapException kmEx) {
				String[] msg = { (kmEx.getClass() + ": " + kmEx.getMessage()) };
	            kmEx.printStackTrace();
				Rdesktop.exit(0, null, null, true);
			}
			System.out.println("Autoselected keyboard map "+mapFile);
		} else {
			try {
				logger.info("looking for: " + "/" + keyMapPath + mapFile);
				istr = Rdesktop.class.getResourceAsStream("/" + keyMapPath + mapFile);
//				 logger.info("istr = " + istr);
				if (istr == null) {
	                logger.debug("Loading keymap from filename");
					keyMap = new KeyCode_FileBased_Localised(mapFile, opt);
				} else {
	                logger.debug("Loading keymap from InputStream");
					keyMap = new KeyCode_FileBased_Localised(istr, opt);
				}
			} catch (Exception kmEx) {
				String[] msg = { (kmEx.getClass() + ": " + kmEx.getMessage()) };
				window.showErrorDialog(msg);
	            kmEx.printStackTrace();
				Rdesktop.exit(0, null, null, true);
			}
		}
		if(istr != null) {
			try {
				istr.close();
			} catch (IOException ioEx) {
				logger.error("Unable to close keymap "+ mapFile +" : "+ ioEx);
				ioEx.printStackTrace();
				Rdesktop.exit(0, null, null, true);
			}
		}
		opt.keylayout = keyMap.getMapCode();

        logger.debug("Registering keyboard...");
		if (keyMap != null)
			window.registerKeyboard(keyMap);

        boolean[] deactivated = new boolean[1];
        int[] ext_disc_reason = new int[1];
        
        logger.debug("keep_running = " + keep_running);        
		while (keep_running) {
            logger.debug("Initialising RDP layer...");
			RdpLayer = new Rdp5(channels, opt, common);
			common.rdp = RdpLayer;
            logger.debug("Registering drawing surface...");
            RdpLayer.registerDrawingSurface(window);
            logger.debug("Registering comms layer...");
            window.registerCommLayer(RdpLayer);
			opt.loggedon = false;
			opt.readytosend = false;
			logger.info("Connecting to " + server + ":" + opt.port + " ...");

            if(server.equalsIgnoreCase("localhost")) server = "127.0.0.1";

			if (RdpLayer != null) {
				// Attempt to connect to server on port opt.port
				try {
					RdpLayer.connect(opt.username, InetAddress.getByName(server), logonflags, opt.domain, opt.password, opt.command, opt.directory);
				
				// Remove to get rid of sendEvent tool
				if (showTools) {
					toolFrame = new SendEvent(RdpLayer);
					toolFrame.show();
				}
				// End

				if (keep_running) {

					/*
					 * By setting encryption to False here, we have an encrypted
					 * login packet but unencrypted transfer of other packets
					 */
					if (!opt.packet_encryption)
						opt.encryption = false;

					logger.info("Connection successful");
					// now show window after licence negotiation
						RdpLayer.mainLoop(deactivated, ext_disc_reason, null);
                                           
                        if (deactivated[0])
                        {
                            /* clean disconnect */
                            Rdesktop.exit(0, RdpLayer, window, true);
                            // return 0;
                        }
                        else
                        {
                            if (ext_disc_reason[0] == exDiscReasonAPIInitiatedDisconnect
                                || ext_disc_reason[0] == exDiscReasonAPIInitiatedLogoff)
                            {
                                /* not so clean disconnect, but nothing to worry about */
                                Rdesktop.exit(0, RdpLayer, window, true);
                                //return 0;
                            }
                            
                            if(ext_disc_reason[0] >= 2){
                                String reason = textDisconnectReason(ext_disc_reason[0]);
                                String msg[] = { "Connection terminated", reason};
                                window.showErrorDialog(msg);
                                logger.warn("Connection terminated: " + reason);
                                Rdesktop.exit(0, RdpLayer, window, true);
                            }
                            
                        }
                        
						keep_running = false; // exited main loop
						if (!opt.readytosend) {
							// maybe the licence server was having a comms
							// problem, retry?
							String msg1 = "The terminal server disconnected before licence negotiation completed.";
							String msg2 = "Possible cause: terminal server could not issue a licence.";
							String[] msg = { msg1, msg2 };
							logger.warn(msg1);
							logger.warn(msg2);
							window.showErrorDialog(msg);
						}
				} // closing bracket to if(running)

				// Remove to get rid of tool window
				if (showTools)
					toolFrame.dispose();
				// End
                
                }catch(ConnectionException e){
                    String msg[] = { "Connection Exception", e.getMessage() };
                    window.showErrorDialog(msg);
                    Rdesktop.exit(0, RdpLayer, window, true);
                } catch (UnknownHostException e) {
                    error(e,RdpLayer,window,true);
                }catch(SocketException s){
                    if(RdpLayer.isConnected()){
                        logger.fatal(s.getClass().getName() + " " + s.getMessage());
                        //s.printStackTrace();
                        error(s, RdpLayer, window, true);
                        Rdesktop.exit(0, RdpLayer, window, true);
                    }
                }catch (RdesktopException e) {
                    String msg1 = e.getClass().getName();
                    String msg2 = e.getMessage();
                    logger.fatal(msg1 + ": " + msg2);

                    e.printStackTrace(System.err);

                    if (!opt.readytosend) {
                        // maybe the licence server was having a comms
                        // problem, retry?
                        String msg[] = {
                                "The terminal server reset connection before licence negotiation completed.",
                                "Possible cause: terminal server could not connect to licence server.",
                                "Retry?" };
                        boolean retry = window.showYesNoErrorDialog(msg);
                        if (!retry) {
                            logger.info("Selected not to retry.");
                            Rdesktop.exit(0, RdpLayer, window, true);
                        } else {
                            if (RdpLayer != null && RdpLayer.isConnected()) {
                                logger.info("Disconnecting ...");
                                RdpLayer.disconnect();
                                logger.info("Disconnected");
                            }
                            logger.info("Retrying connection...");
                            keep_running = true; // retry
                            continue;
                        }
                    } else {
                        String msg[] = { e.getMessage() };
                        window.showErrorDialog(msg);
                        Rdesktop.exit(0, RdpLayer, window, true);
                    }
                }catch (Exception e) {
                    logger.warn(e.getClass().getName() + " " + e.getMessage());
                    e.printStackTrace();
                    error(e, RdpLayer, window, true);
                }
			} else { // closing bracket to if(!rdp==null)
				logger
						.fatal("The communications layer could not be initiated!");
			}
		}
		Rdesktop.exit(0, RdpLayer, window, true);
	}

	/**
	 * Disconnects from the server connected to through rdp and destroys the
	 * RdesktopFrame window.
	 * <p>
	 * Exits the application iff sysexit == true, providing return value n to
	 * the operating system.
	 * 
	 * @param n
	 * @param rdp
	 * @param window
	 * @param sysexit
	 */
	public static void exit(int n, Rdp rdp, RdesktopFrame window, boolean sysexit) {
		keep_running = false;

		// Remove to get rid of tool window
		if ((showTools) && (toolFrame != null))
			toolFrame.dispose();
		// End

		if (rdp != null && rdp.isConnected()) {
			logger.info("Disconnecting ...");
			rdp.disconnect();
			logger.info("Disconnected");
		}
		if (window != null) {
			window.setVisible(false);
			window.dispose();
		}
		
        System.gc();
        
		if (sysexit && Constants.SystemExit){
            if(!common.underApplet) System.exit(n);
        }
	}

	/**
	 * Displays an error dialog via the RdesktopFrame window containing the
	 * customised message emsg, and reports this through the logging system.
	 * <p>
	 * The application then exits iff sysexit == true
	 * 
	 * @param emsg
	 * @param RdpLayer
	 * @param window
	 * @param sysexit
	 */
	public static void customError(String emsg, Rdp RdpLayer,
			RdesktopFrame window, boolean sysexit) {
		logger.fatal(emsg);
		String[] msg = { emsg };
		window.showErrorDialog(msg);
		Rdesktop.exit(0, RdpLayer, window, true);
	}

	/**
	 * Displays details of the Exception e in an error dialog via the
	 * RdesktopFrame window and reports this through the logger, then prints a
	 * stack trace.
	 * <p>
	 * The application then exits iff sysexit == true
	 * 
	 * @param e
	 * @param RdpLayer
	 * @param window
	 * @param sysexit
	 */
	public static void error(Exception e, Rdp RdpLayer, RdesktopFrame window, boolean sysexit) {
		try {

			String msg1 = e.getClass().getName();
			String msg2 = e.getMessage();

			logger.fatal(msg1 + ": " + msg2);

			String[] msg = { msg1, msg2 };
			if (window != null)
				window.showErrorDialog(msg);

			//e.printStackTrace(System.err);
		} catch (Exception ex) {
            logger.warn("Exception in Rdesktop.error: " + ex.getClass().getName() + ": " + ex.getMessage() );
		}

		Rdesktop.exit(0, RdpLayer, window, sysexit);
	}
}
