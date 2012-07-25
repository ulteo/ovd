/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Yann Hodique <y.hodique@ulteo.com> 2012
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
 *
 * Copyright (c) 2005, 2006, Oracle and/or its affiliates. All rights reserved.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This code is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License version 2 only, as
 * published by the Free Software Foundation.  Oracle designates this
 * particular file as subject to the "Classpath" exception as provided
 * by Oracle in the LICENSE file that accompanied this code.
 *
 * This code is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
 * version 2 for more details (a copy is included in the LICENSE file that
 * accompanied this code).
 *
 * You should have received a copy of the GNU General Public License version
 * 2 along with this work; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * Please contact Oracle, 500 Oracle Parkway, Redwood Shores, CA 94065 USA
 * or visit www.oracle.com if you need additional information or have any
 * questions.
 */

package org.ulteo.pcsc;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;
import org.ulteo.Logger;
import org.ulteo.ovd.integrated.OSTools;

/**
 * Access to native PC/SC functions and definition of PC/SC constants.
 * Initialization and platform specific PC/SC constants are handled in the
 * platform specific superclass.
 */
public class PCSC {

	private final static String LIB_PCSCLITE = "libpcsclite.so";
	private final static String PROP_NAME_PCSCLITE = "org.ulteo.pcsc.library";

	private final static String LIB1 = "/usr/$LIBISA/";
	private final static String LIB2 = "/usr/local/$LIBISA/";
	private final static String LIB3 = "/usr/lib/";
	private final static String LIB4 = "/usr/local/lib/";
	private final static String LIB5 = "/usr/lib/$LIBLIN-linux-gnu/";
	private final static String LIB6 = "/lib/$LIBLIN-linux-gnu/";

	// expand $LIBISA to the system specific directory name for libraries
	private static String expand(String lib, String search, String replace32, String replace64) {
		int k = lib.indexOf(search);
		if (k == -1) {
			return lib;
		}
		String s1 = lib.substring(0, k);
		String s2 = lib.substring(k + 7);
		String libDir;
		if (OSTools.is64()) {
			// assume Linux convention
			libDir = replace64;
		} else {
			// must be 32-bit
			libDir = replace32;
		}
		String s = s1 + libDir + s2;
		return s;
	}

	private static String searchSystemLibrary(String libraryName, String propName) {
		// if system property is set, use that library
		String lib = System.getProperty(propName, "").trim();
		lib = expand(lib, "$LIBISA", "lib", "lib64");
		lib = expand(lib, "$LIBLIN", "i386", "x86_64");
		if (lib.length() != 0) {
			return lib;
		}

		String[] std_libs = { LIB1, LIB2, LIB3, LIB4, LIB5, LIB6 };
		for (String std_lib : std_libs) {
			lib = expand(std_lib, "$LIBISA", "lib", "lib64");
			lib = expand(lib, "$LIBLIN", "i386", "x86_64");
			if (new File(lib + libraryName).isFile()) {
				// if lib exists, use that
				return lib + libraryName;
			}
			if (new File(lib + libraryName + ".1").isFile()) {
				// if lib exists, use that
				return lib + libraryName + ".1";
			}
		}
		return libraryName;
	}

	public static void LoadPCSCLite() throws FileNotFoundException {
		String syslib = PCSC.searchSystemLibrary(PCSC.LIB_PCSCLITE, PCSC.PROP_NAME_PCSCLITE);
		if (syslib == null) {
			throw new FileNotFoundException("Library not found " + PCSC.LIB_PCSCLITE);
		} else {
			try {
				Logger.info("Smartcard loading " + syslib);
				PCSC.initialize(syslib);
			} catch (IOException e) {
				throw new FileNotFoundException(e.getMessage());
			}
		}
	}

	// PCSC constants defined differently under Windows and MUSCLE
	private final class PCSCWin {
		// Windows version
		final static int SCARD_PROTOCOL_RAW = 0x10000;

		final static int SCARD_UNKNOWN = 0x0000;
		final static int SCARD_ABSENT = 0x0001;
		final static int SCARD_PRESENT = 0x0002;
		final static int SCARD_SWALLOWED = 0x0003;
		final static int SCARD_POWERED = 0x0004;
		final static int SCARD_NEGOTIABLE = 0x0005;
		final static int SCARD_SPECIFIC = 0x0006;
	}

	private final class PCSCUnix {
		// MUSCLE version
		final static int SCARD_PROTOCOL_RAW = 0x0004;

		final static int SCARD_UNKNOWN = 0x0001;
		final static int SCARD_ABSENT = 0x0002;
		final static int SCARD_PRESENT = 0x0004;
		final static int SCARD_SWALLOWED = 0x0008;
		final static int SCARD_POWERED = 0x0010;
		final static int SCARD_NEGOTIABLE = 0x0020;
		final static int SCARD_SPECIFIC = 0x0040;
	}

	static {
		if (OSTools.isWindows()) {
			Logger.debug("SmartCard windows version");
			SCARD_PROTOCOL_RAW = PCSCWin.SCARD_PROTOCOL_RAW;
			SCARD_UNKNOWN = PCSCWin.SCARD_UNKNOWN;
			SCARD_ABSENT = PCSCWin.SCARD_ABSENT;
			SCARD_PRESENT = PCSCWin.SCARD_PRESENT;
			SCARD_SWALLOWED = PCSCWin.SCARD_SWALLOWED;
			SCARD_POWERED = PCSCWin.SCARD_POWERED;
			SCARD_NEGOTIABLE = PCSCWin.SCARD_NEGOTIABLE;
			SCARD_SPECIFIC = PCSCWin.SCARD_SPECIFIC;
		} else {
			Logger.debug("SmartCard unix version");
			SCARD_PROTOCOL_RAW = PCSCUnix.SCARD_PROTOCOL_RAW;
			SCARD_UNKNOWN = PCSCUnix.SCARD_UNKNOWN;
			SCARD_ABSENT = PCSCUnix.SCARD_ABSENT;
			SCARD_PRESENT = PCSCUnix.SCARD_PRESENT;
			SCARD_SWALLOWED = PCSCUnix.SCARD_SWALLOWED;
			SCARD_POWERED = PCSCUnix.SCARD_POWERED;
			SCARD_NEGOTIABLE = PCSCUnix.SCARD_NEGOTIABLE;
			SCARD_SPECIFIC = PCSCUnix.SCARD_SPECIFIC;
		}
	}

	private static boolean loadLibrary = false;

	private PCSC() {
		// no instantiation
	}

	public static void disableLibraryLoading() {
		Logger.warn("Smartcard disabled");
		PCSC.loadLibrary = false;
	}

	public static void libraryLoaded() {
		Logger.info("Smartcard library loaded");
		PCSC.loadLibrary = true;
	}

	public static boolean checkAvailable() {
		return PCSC.loadLibrary;
	}

	// only for Unix
	private static native void initialize(String libraryName) throws IOException;

	// returns SCARDCONTEXT (contextId)
	public static native long SCardEstablishContext(int scope) throws PCSCException;

	public static native long SCardReleaseContext(long context) throws PCSCException;

	public static native boolean SCardIsValidContext(long context) throws PCSCException;

	public static native String[] SCardListReaders(long contextId) throws PCSCException;

	public static native void SCardCancel(long context) throws PCSCException;

	private static native Object PrivateSCardConnect(long contextId, String readerName, int shareMode,
			int preferredProtocols) throws PCSCException;

	public static Connection SCardConnect(long contextId, String readerName, int shareMode, int preferredProtocols)
			throws PCSCException {
		return (Connection) PrivateSCardConnect(contextId, readerName, shareMode, preferredProtocols);
	}

	private static native Object PrivateSCardReconnect(long card, int shareMode, int preferredProtocols,
			int initialization) throws PCSCException;

	public static Connection SCardReconnect(long card, int shareMode, int preferredProtocols, int initialization)
			throws PCSCException {
		return (Connection) PrivateSCardReconnect(card, shareMode, preferredProtocols, initialization);
	}

	public static native byte[] SCardTransmit(long cardId, int protocol, byte[] buf, int ofs, int len)
			throws PCSCException;

	private static native Object PrivateSCardStatus(long cardId) throws PCSCException;

	public static Status SCardStatus(long cardId) throws PCSCException {
		return (Status) PrivateSCardStatus(cardId);
	}

	public static native void SCardDisconnect(long cardId, int disposition) throws PCSCException;

	private static native void PrivateSCardGetStatusChange(long contextId, long timeout, Object[] readers)
			throws PCSCException;

	public static void SCardGetStatusChange(long contextId, long timeout, ReaderState[] readers) throws PCSCException {
		PrivateSCardGetStatusChange(contextId, timeout, readers);
	}

	public static native void SCardBeginTransaction(long cardId) throws PCSCException;

	public static native void SCardEndTransaction(long cardId, int disposition) throws PCSCException;

	public static native byte[] SCardControl(long cardId, int controlCode, byte[] sendBuffer) throws PCSCException;

	public static native byte[] SCardGetAttrib(long cardId, long attrId) throws PCSCException;

	final static int SCARD_PROTOCOL_T0 = 0x0001;
	final static int SCARD_PROTOCOL_T1 = 0x0002;

	// Some constants are platform dependant
	final static int SCARD_PROTOCOL_RAW;

	final static int SCARD_UNKNOWN;
	final static int SCARD_ABSENT;
	final static int SCARD_PRESENT;
	final static int SCARD_SWALLOWED;
	final static int SCARD_POWERED;
	final static int SCARD_NEGOTIABLE;
	final static int SCARD_SPECIFIC;

	// PCSC success/error/failure/warning codes
	public final static int SCARD_S_SUCCESS = 0x00000000;
	public final static int SCARD_E_CANCELLED = 0x80100002;
	public final static int SCARD_E_CANT_DISPOSE = 0x8010000E;
	public final static int SCARD_E_INSUFFICIENT_BUFFER = 0x80100008;
	public final static int SCARD_E_INVALID_ATR = 0x80100015;
	public final static int SCARD_E_INVALID_HANDLE = 0x80100003;
	public final static int SCARD_E_INVALID_PARAMETER = 0x80100004;
	public final static int SCARD_E_INVALID_TARGET = 0x80100005;
	public final static int SCARD_E_INVALID_VALUE = 0x80100011;
	public final static int SCARD_E_NO_MEMORY = 0x80100006;
	public final static int SCARD_F_COMM_ERROR = 0x80100013;
	public final static int SCARD_F_INTERNAL_ERROR = 0x80100001;
	public final static int SCARD_F_UNKNOWN_ERROR = 0x80100014;
	public final static int SCARD_F_WAITED_TOO_LONG = 0x80100007;
	public final static int SCARD_E_UNKNOWN_READER = 0x80100009;
	public final static int SCARD_E_TIMEOUT = 0x8010000A;
	public final static int SCARD_E_SHARING_VIOLATION = 0x8010000B;
	public final static int SCARD_E_NO_SMARTCARD = 0x8010000C;
	public final static int SCARD_E_UNKNOWN_CARD = 0x8010000D;
	public final static int SCARD_E_PROTO_MISMATCH = 0x8010000F;
	public final static int SCARD_E_NOT_READY = 0x80100010;
	public final static int SCARD_E_SYSTEM_CANCELLED = 0x80100012;
	public final static int SCARD_E_NOT_TRANSACTED = 0x80100016;
	public final static int SCARD_E_READER_UNAVAILABLE = 0x80100017;

	public final static int SCARD_W_UNSUPPORTED_CARD = 0x80100065;
	public final static int SCARD_W_UNRESPONSIVE_CARD = 0x80100066;
	public final static int SCARD_W_UNPOWERED_CARD = 0x80100067;
	public final static int SCARD_W_RESET_CARD = 0x80100068;
	public final static int SCARD_W_REMOVED_CARD = 0x80100069;
	public final static int SCARD_W_INSERTED_CARD = 0x8010006A;

	public final static int SCARD_E_UNSUPPORTED_FEATURE = 0x8010001F;
	public final static int SCARD_E_PCI_TOO_SMALL = 0x80100019;
	public final static int SCARD_E_READER_UNSUPPORTED = 0x8010001A;
	public final static int SCARD_E_DUPLICATE_READER = 0x8010001B;
	public final static int SCARD_E_CARD_UNSUPPORTED = 0x8010001C;
	public final static int SCARD_E_NO_SERVICE = 0x8010001D;
	public final static int SCARD_E_SERVICE_STOPPED = 0x8010001E;

	// MS undocumented
	public final static int SCARD_E_NO_READERS_AVAILABLE = 0x8010002E;
	// std. Windows invalid handle return code, used instead of SCARD code
	public final static int WINDOWS_ERROR_INVALID_HANDLE = 6;
	public final static int WINDOWS_ERROR_INVALID_PARAMETER = 87;

	//
	public final static int SCARD_SCOPE_USER = 0x0000;
	public final static int SCARD_SCOPE_TERMINAL = 0x0001;
	public final static int SCARD_SCOPE_SYSTEM = 0x0002;
	public final static int SCARD_SCOPE_GLOBAL = 0x0003;

	public final static int SCARD_SHARE_EXCLUSIVE = 0x0001;
	public final static int SCARD_SHARE_SHARED = 0x0002;
	public final static int SCARD_SHARE_DIRECT = 0x0003;

	public final static int SCARD_LEAVE_CARD = 0x0000;
	public final static int SCARD_RESET_CARD = 0x0001;
	public final static int SCARD_UNPOWER_CARD = 0x0002;
	public final static int SCARD_EJECT_CARD = 0x0003;

	public final static int SCARD_STATE_UNAWARE = 0x0000;
	public final static int SCARD_STATE_IGNORE = 0x0001;
	public final static int SCARD_STATE_CHANGED = 0x0002;
	public final static int SCARD_STATE_UNKNOWN = 0x0004;
	public final static int SCARD_STATE_UNAVAILABLE = 0x0008;
	public final static int SCARD_STATE_EMPTY = 0x0010;
	public final static int SCARD_STATE_PRESENT = 0x0020;
	public final static int SCARD_STATE_ATRMATCH = 0x0040;
	public final static int SCARD_STATE_EXCLUSIVE = 0x0080;
	public final static int SCARD_STATE_INUSE = 0x0100;
	public final static int SCARD_STATE_MUTE = 0x0200;
	public final static int SCARD_STATE_UNPOWERED = 0x0400;

	public final static int TIMEOUT_INFINITE = 0xffffffff;

	private final static char[] hexDigits = "0123456789abcdef".toCharArray();

	public static String toString(byte[] b) {
		StringBuffer sb = new StringBuffer(b.length * 3);
		for (int i = 0; i < b.length; i++) {
			int k = b[i] & 0xff;
			if (i != 0) {
				sb.append(':');
			}
			sb.append(hexDigits[k >>> 4]);
			sb.append(hexDigits[k & 0xf]);
		}
		return sb.toString();
	}

}
