/*
 * Copyright (C) 2012-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Yann Hodique <y.hodique@ulteo.com> 2012
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
 * Author Abraham Macías Paredes <amacias@solutia-it.es> 2013
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
package org.ulteo.ovd.smartCard;

import java.io.UnsupportedEncodingException;
import java.nio.charset.Charset;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpdr.RdpdrDevice;
import org.ulteo.Logger;
import org.ulteo.pcsc.Connection;
import org.ulteo.pcsc.PCSC;
import org.ulteo.pcsc.PCSCException;
import org.ulteo.pcsc.ReaderState;
import org.ulteo.pcsc.Status;

public class SmartCard extends RdpdrDevice {
	private final boolean SmartCardDebug = false;

	private class IoRequest {
		public int dwProtocol;
		public int cbPciLength;
	}

	private class AtrMask {
		public int cbAtr;
		public byte[] rgbAtr;
		public byte[] rgbMask;
	}

	public static final int DEVICE_TYPE = 0x20;

	public static final int WIN_FILE_DEVICE_SMARTCARD = 0x31;

	private static int smartCardCount = 0;
	private ExecutorService pool;

	public final int SCARD_IOCTL_ESTABLISH_CONTEXT = 0x00090014; // EstablishContext
	public final int SCARD_IOCTL_RELEASE_CONTEXT = 0x00090018; // ReleaseContext
	public final int SCARD_IOCTL_IS_VALID_CONTEXT = 0x0009001C; // IsValidContext
	public final int SCARD_IOCTL_LIST_READER_GROUPS = 0x00090020; // ListReaderGroupsA
	public final int SCARD_IOCTL_LIST_READER_GROUPS_UNICODE = 0x00090024; // ListReaderGroupsW
	public final int SCARD_IOCTL_LIST_READERS = 0x00090028; // ListReadersA
	public final int SCARD_IOCTL_LIST_READERS_UNICODE = 0x0009002c; // ListReadersW
	public final int SCARD_IOCTL_INTRODUCE_READER_GROUP = 0x00090050; // IntroduceReaderGroup
	public final int SCARD_IOCTL_FORGET_READER_GROUP = 0x00090058; // ForgetReader
	public final int SCARD_IOCTL_INTRODUCE_READER = 0x00090060; // IntroduceReader
	public final int SCARD_IOCTL_FORGET_READER = 0x00090068; // IntroduceReader
	public final int SCARD_IOCTL_ADD_READER_TO_GROUP = 0x00090070; // AddReaderToGroup
	public final int SCARD_IOCTL_REMOVE_READER_FROM_GROUP = 0x00090078; // RemoveReaderFromGroup
	public final int SCARD_IOCTL_GET_STATUS_CHANGE = 0x000900A0; // GetStatusChangeA
	public final int SCARD_IOCTL_GET_STATUS_CHANGE_UNICODE = 0x000900A4; // GetStatusChangeW
	public final int SCARD_IOCTL_CANCEL = 0x000900A8; // Cancel
	public final int SCARD_IOCTL_CONNECT = 0x000900AC; // ConnectA
	public final int SCARD_IOCTL_CONNECT_UNICODE = 0x000900B0; // ConnectW
	public final int SCARD_IOCTL_RECONNECT = 0x000900B4; // Reconnect
	public final int SCARD_IOCTL_DISCONNECT = 0x000900B8; // Disconnect
	public final int SCARD_IOCTL_BEGIN_TRANSACTION = 0x000900BC; // BeginTransaction
	public final int SCARD_IOCTL_END_TRANSACTION = 0x000900C0; // EndTransaction
	public final int SCARD_IOCTL_STATE = 0x000900C4; // State
	public final int SCARD_IOCTL_STATUS = 0x000900C8; // StatusA
	public final int SCARD_IOCTL_STATUS_UNICODE = 0x000900CC; // StatusW
	public final int SCARD_IOCTL_TRANSMIT = 0x000900D0; // Transmit
	public final int SCARD_IOCTL_CONTROL = 0x000900D4; // Control
	public final int SCARD_IOCTL_GETATTRIB = 0x000900D8; // GetAttrib
	public final int SCARD_IOCTL_SETATTRIB = 0x000900DC; // SetAttrib
	public final int SCARD_IOCTL_ACCESS_STARTED_EVENT = 0x000900E0; // SCardAccessStartedEvent
	public final int SCARD_IOCTL_LOCATE_CARDS_BY_ATR = 0x000900E8; // LocateCardsByATRA
	public final int SCARD_IOCTL_LOCATE_CARDS_BY_ATR_UNICODE = 0x000900EC; // LocateCardsByATRW

	public final int SCARD_INPUT_LINKED = 0xFFFFFFFF;

	
	private List<Long> rgSCardContextList = new ArrayList<Long>();
	
	public SmartCard(RdpdrChannel rdpdr) {
		super(rdpdr);
		device_type = DEVICE_TYPE;
		name = "SCARD";
		Logger.info("SmartCard creation");
		pool = Executors.newFixedThreadPool(20);
		smartCardCount++;
	}

	private void outRepos(RdpPacket packet, int written) {
		int add = (4 - written % 4) % 4;
		if (add > 0) {
			packet.incrementPosition(add);
		}
	}

	public void inRepos(RdpPacket packet, int read) {
		int add = 4 - read % 4;
		if (add < 4 && add > 0) {
			packet.incrementPosition(add);
		}
	}

	public void outForceAlignment(RdpPacket out, int seed) {
		// int add = (seed - (out.getPosition()) % seed) % seed;
		// if (add > 0)
		// out.incrementPosition(add);
	}

	@Override
	public int close(int file) {
		return 0;
	}

	@Override
	public int create(int device, int desiredAccess, int shareMode, int disposition, int flagsAndAttributes,
			String filename, int[] result) {
		Logger.debug("SmartCard Unimplemented method: SmartCard::create");
		return 0;
	}

	private class ScardTask implements Runnable {
		public ScardTask(int file, int request, RdpPacket in, RdpPacket out) {
			this.file = file;
			this.request = request;
			this.in = in;
			this.out = out;
			this.out_position = out.getPosition();

			int in_pos = in.getPosition();
			// we need some more information that's already been consummed in
			// the input. On this code path, we skipped 0x14 bytes, after
			// reading 8 int.
			in.setPosition(in_pos - 0x14 - 8 * 4);
			this.device = in.getLittleEndian32();
			in.incrementPosition(4);
			this.id = in.getLittleEndian32();
			in.setPosition(in_pos);
		}

		public void run() {
			int status;
			status = SmartCard.this.device_dispatch(file, request, in, out);
			int result = out.getPosition() - out_position;
			SmartCard.this.rdpdr.rdpdr_send_completion(device, id, status, result, (RdpPacket_Localised) out, result);
		}

		private int file, request, device, id, out_position;
		private RdpPacket in, out;
	}

	@Override
	public int device_control(int file, int request, RdpPacket in, RdpPacket out) {
		if (!async_operation(request))
			return device_dispatch(file, request, in, out);
		else {
			pool.execute(new ScardTask(file, request, in, out));
			return RdpdrChannel.STATUS_PENDING;
		}
	}

	private boolean async_operation(int request) {
		switch (request) {
		/* sync events */
		case SCARD_IOCTL_ACCESS_STARTED_EVENT:
		case SCARD_IOCTL_ESTABLISH_CONTEXT:
		case SCARD_IOCTL_RELEASE_CONTEXT:
		case SCARD_IOCTL_IS_VALID_CONTEXT:

			return false;

			/* async events */
		case SCARD_IOCTL_GET_STATUS_CHANGE:
		case SCARD_IOCTL_GET_STATUS_CHANGE_UNICODE:
		case SCARD_IOCTL_TRANSMIT:
		case SCARD_IOCTL_STATUS:
		case SCARD_IOCTL_STATUS_UNICODE:
			return true;

		default:
			return true; // default to async
		}
	}

	private int device_dispatch(int file, int request, RdpPacket in, RdpPacket out) {
		if (SmartCardDebug)
			Logger.debug("SmartCard device control " + file + " " + Integer.toHexString(request));
		
		int status = PCSC.SCARD_F_INTERNAL_ERROR;
		boolean useUnicode;

		/* [MS-RPCE] 2.2.6.1 */
		out.setLittleEndian32(0x00081001); // len 8, LE, v1
		out.setLittleEndian32(0xcccccccc); // filler

		int lengthPos = out.getPosition();
		out.setLittleEndian32(0); // size

		out.setLittleEndian32(0); // filler

		int statusPos = out.getPosition();
		out.setLittleEndian32(0); // result

		switch (request) {
		case SCARD_IOCTL_ESTABLISH_CONTEXT:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_ESTABLISH_CONTEXT");

			status = this.establishContext(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_ESTABLISH_CONTEXT");

			break;

		case SCARD_IOCTL_RELEASE_CONTEXT:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_RELEASE_CONTEXT");

			status = this.releaseContext(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_RELEASE_CONTEXT");

			break;

		case SCARD_IOCTL_IS_VALID_CONTEXT:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_IS_VALID_CONTEXT");

			status = this.isValidContext(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_IS_VALID_CONTEXT");

			break;

		case SCARD_IOCTL_LIST_READER_GROUPS:
		case SCARD_IOCTL_LIST_READER_GROUPS_UNICODE:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_LIST_READER_GROUPS not implemented");

			status = PCSC.SCARD_F_INTERNAL_ERROR;
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_LIST_READER_GROUPS not implemented");

			break;

		case SCARD_IOCTL_LIST_READERS:
		case SCARD_IOCTL_LIST_READERS_UNICODE:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_LIST_READERS");

			useUnicode = request == SCARD_IOCTL_LIST_READERS_UNICODE;
			status = this.listReaders(in, out, useUnicode);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_LIST_READERS");

			break;

		case SCARD_IOCTL_INTRODUCE_READER_GROUP:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_INTRODUCE_READER_GROUP not implemented");

			status = PCSC.SCARD_F_INTERNAL_ERROR;
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_INTRODUCE_READER_GROUP not implemented");

			break;

		case SCARD_IOCTL_FORGET_READER_GROUP:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_FORGET_READER_GROUP not implemented");

			status = PCSC.SCARD_F_INTERNAL_ERROR;
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_FORGET_READER_GROUP not implemented");

			break;

		case SCARD_IOCTL_INTRODUCE_READER:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_INTRODUCE_READER not implemented");

			status = PCSC.SCARD_F_INTERNAL_ERROR;
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_INTRODUCE_READER not implemented");

			break;

		case SCARD_IOCTL_FORGET_READER:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_FORGET_READER not implemented");

			status = PCSC.SCARD_F_INTERNAL_ERROR;
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_FORGET_READER not implemented");

			break;

		case SCARD_IOCTL_ADD_READER_TO_GROUP:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_ADD_READER_TO_GROUP not implemented");

			status = PCSC.SCARD_F_INTERNAL_ERROR;
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_ADD_READER_TO_GROUP not implemented");

			break;

		case SCARD_IOCTL_REMOVE_READER_FROM_GROUP:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_REMOVE_READER_FROM_GROUP not implemented");

			status = PCSC.SCARD_F_INTERNAL_ERROR;
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_REMOVE_READER_FROM_GROUP not implemented");

			break;

		case SCARD_IOCTL_GET_STATUS_CHANGE:
		case SCARD_IOCTL_GET_STATUS_CHANGE_UNICODE:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_GET_STATUS_CHANGE");

			useUnicode = request == SCARD_IOCTL_GET_STATUS_CHANGE_UNICODE;
			status = this.getStatusChange(in, out, useUnicode);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_GET_STATUS_CHANGE");

			break;

		case SCARD_IOCTL_CANCEL:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_CANCEL");

			status = this.cancel(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_CANCEL");

			break;

		case SCARD_IOCTL_CONNECT:
		case SCARD_IOCTL_CONNECT_UNICODE:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_CONNECT");

			useUnicode = request == SCARD_IOCTL_CONNECT_UNICODE;
			status = this.connect(in, out, useUnicode);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_CONNECT");

			break;

		case SCARD_IOCTL_RECONNECT:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_RECONNECT");

			status = this.reconnect(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_RECONNECT");

			break;

		case SCARD_IOCTL_DISCONNECT:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_DISCONNECT");

			status = this.disconnect(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_DISCONNECT");

			break;

		case SCARD_IOCTL_BEGIN_TRANSACTION:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_BEGIN_TRANSACTION");

			status = this.beginTransaction(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_BEGIN_TRANSACTION");

			break;

		case SCARD_IOCTL_END_TRANSACTION:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_END_TRANSACTION");

			status = this.endTransaction(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_END_TRANSACTION");

			break;

		case SCARD_IOCTL_STATE:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_STATE");

			status = this.state(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_STATE");

			break;

		case SCARD_IOCTL_STATUS:
		case SCARD_IOCTL_STATUS_UNICODE:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_STATUS");

			useUnicode = request == (SCARD_IOCTL_STATUS_UNICODE);
			status = this.status(in, out, useUnicode);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_STATUS");

			break;

		case SCARD_IOCTL_TRANSMIT:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_TRANSMIT");

			status = this.transmit(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_TRANSMIT");

			break;

		case SCARD_IOCTL_CONTROL:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_CONTROL");

			status = this.control(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_CONTROL");

			break;

		case SCARD_IOCTL_GETATTRIB:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_GETATTRIB");

			status = this.getAttrib(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_GETATTRIB");

			break;

		case SCARD_IOCTL_SETATTRIB:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_SETATTRIB not implemented");

			status = PCSC.SCARD_F_INTERNAL_ERROR;
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_SETATTRIB not implemented");

			break;

		case SCARD_IOCTL_ACCESS_STARTED_EVENT:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_ACCESS_STARTED_EVENT");

			status = handleAccessStartedEvent(in, out);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_ACCESS_STARTED_EVENT");

			break;

		case SCARD_IOCTL_LOCATE_CARDS_BY_ATR:
		case SCARD_IOCTL_LOCATE_CARDS_BY_ATR_UNICODE:
			if (SmartCardDebug)
				Logger.debug("SmartCard +SCARD_IOCTL_LOCATE_CARDS_BY_ATR");

			useUnicode = request == (SCARD_IOCTL_LOCATE_CARDS_BY_ATR_UNICODE);
			status = this.locateCards(in, out, useUnicode);
			if (SmartCardDebug)
				Logger.debug("SmartCard -SCARD_IOCTL_LOCATE_CARDS_BY_ATR");

			break;

		default:
			status = RdpdrChannel.STATUS_UNSUCCESSFUL;
			if (SmartCardDebug)
				Logger.debug("SmartCard scard unknown ioctl " + Integer.toHexString(request));

			break;
		}

		/* look for NTSTATUS errors */
		if ((status & 0xc0000000) > 0) {
			if (SmartCardDebug)
				Logger.debug("SmartCard smart card wrong status " + status);

			return status;
		}

		/*
		 * per Ludovic Rousseau, map different usage of this particular error
		 * code between pcsc-lite & windows
		 */
		if (status == 0x8010001F)
			status = 0x80100022;

		/* handle response packet */
		int endPos = out.getPosition();

		out.setPosition(statusPos);
		out.setLittleEndian32(status);

		out.setPosition(endPos);

		/* pad stream to 16 byte align */
		int padLen = (endPos - statusPos) % 8;
		out.incrementPosition((8 - padLen) % 8);
		endPos = out.getPosition();

		int streamLen = endPos - lengthPos - 8;

		out.setPosition(lengthPos);
		out.setLittleEndian32(streamLen);
		out.setPosition(endPos);
		out.markEnd();

		if (status != PCSC.SCARD_S_SUCCESS) {
			Logger.error("SmartCard device_dispatch exit code = " + Integer.toHexString(status));
		}

		return RdpdrChannel.STATUS_SUCCESS;
	}

	private int getStatusChange(RdpPacket in, RdpPacket out, boolean useUnicode) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int hContext;
		int dwTimeout;
		int dwCount;
		ReaderState[] servers;

		in.incrementPosition(0x18);
		dwTimeout = in.getLittleEndian32();
		dwCount = in.getLittleEndian32();
		in.incrementPosition(0x8);

		hContext = in.getLittleEndian32();

		in.incrementPosition(0x04);

		if (SmartCardDebug)
			Logger.debug("SmartCard SCardGetStatusChange(context: " + Integer.toHexString(hContext) + ", timeout: "
				+ Integer.toHexString(dwTimeout) + ", count:" + dwCount + ", unicode:" + useUnicode);

		if (dwCount > 0) {
			servers = getReaderStates(in, dwCount, useUnicode);
		} else {
			servers = null;
		}

		try {
			PCSC.SCardGetStatusChange(hContext, dwTimeout, servers);
		} catch (PCSCException e) {
			rv = e.getErrCode();
			if (rv != PCSC.SCARD_E_TIMEOUT && rv != PCSC.SCARD_E_CANCELLED) {
				Logger.error("SmartCard Failed to get Smart card state : " + e.toString());
			}
		}

		putReaderStates(out, servers);

		outForceAlignment(out, 8);

		return rv;
	}

	private int cancel(RdpPacket in, RdpPacket out) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int hContext;

		in.incrementPosition(0x1c);
		hContext = in.getLittleEndian32();

		try {
			PCSC.SCardCancel(hContext);
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}

		outForceAlignment(out, 8);
		return rv;
	}

	private int listReaders(RdpPacket in, RdpPacket out, boolean useUnicode) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int hContext;
		int dataLength;
		int len;

		in.incrementPosition(8);
		len = in.getLittleEndian32();
		in.incrementPosition(0x1c);
		len = in.getLittleEndian32();

		if (len != 4)
			return PCSC.SCARD_F_INTERNAL_ERROR;

		hContext = in.getLittleEndian32();

		if (SmartCardDebug)
			Logger.debug("SmartCard SCardListReaders(context: " + Integer.toHexString(hContext) + ", unicode: "+useUnicode+")");

		int lenPos1 = out.getPosition();
		out.incrementPosition(4);
		out.setLittleEndian32(0x20000);

		int lenPos2 = out.getPosition();
		out.incrementPosition(4);

		dataLength = 0;

		String[] readers = null;
		try {
			readers = PCSC.SCardListReaders(hContext);
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}

		if (rv != 0) {
			Logger.error("SmartCard Fail to list smart card");
		}

		dataLength = putReaderNames(out, readers, useUnicode);

		int endPos = out.getPosition();
		
		if (readers!=null) {
			out.setPosition(lenPos1);
			byte[] tempBuf = new byte[endPos-lenPos1];
			out.copyToByteArray(tempBuf,0, lenPos1, endPos-lenPos1);
		} else {
			Logger.debug("No readers available!");
		}
		
		out.setPosition(lenPos1);
		out.setLittleEndian32(dataLength);

		out.setPosition(lenPos2);
		out.setLittleEndian32(dataLength);
		out.setPosition(endPos);

		outForceAlignment(out, 8);
		return rv;
	}

	private int establishContext(RdpPacket in, RdpPacket out) {
		int len, rv = 0;
		int scope;
		long hContext = -1;

		in.incrementPosition(8);
		len = in.getLittleEndian32();

		if (len != 8)
			return PCSC.SCARD_F_INTERNAL_ERROR;

		in.incrementPosition(4);
		scope = in.getLittleEndian32();

		if (SmartCardDebug)
			Logger.debug("scope = "+scope);
		
		try {
			hContext = PCSC.SCardEstablishContext(scope);
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}

		if (SmartCardDebug)
			Logger.debug("hContext = "+String.format("%08X ", hContext));
		
		out.setLittleEndian32(4); // ?
		out.setLittleEndian32(0x20000); // ?

		out.setLittleEndian32(4);
		out.setLittleEndian32((int) hContext);

		/* TODO: store hContext in allowed context list */
		this.rgSCardContextList.add(hContext);

		return rv;
	}

	private int releaseContext(RdpPacket in, RdpPacket out) {
		int len, rv = PCSC.SCARD_S_SUCCESS;
		long hContext = -1;

		in.incrementPosition(8);
		len = in.getLittleEndian32();

		in.incrementPosition(0x10);
		hContext = in.getLittleEndian32() & 0xFFFFFFFFL;

		if (SmartCardDebug)
			Logger.debug("Release hContext = "+String.format("%08X ", hContext));
		if (!this.rgSCardContextList.contains(hContext)) {
			Logger.error("Unknown context: "+String.format("%08X ", hContext));
		}
		
		try {
			PCSC.SCardReleaseContext(hContext);
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}
		
		this.rgSCardContextList.remove(hContext);
		
		return rv;
	}

	private int isValidContext(RdpPacket in, RdpPacket out) {
		int rv = PCSC.SCARD_S_SUCCESS;
		long hContext = -1;

		in.incrementPosition(0x1c);
		hContext = in.getLittleEndian32();

		if (SmartCardDebug)
			Logger.debug("is valid hContext? = "+String.format("%08X ", hContext));
		if (!this.rgSCardContextList.contains(hContext)) {
			Logger.error("Unknown context: "+String.format("%08X ", hContext));
		}
		
		try {
			PCSC.SCardIsValidContext(hContext);
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}
		out.setLittleEndian32(rv);
		return rv;
	}

	private int handleAccessStartedEvent(RdpPacket in, RdpPacket out) {
		// out.incrementPosition(8);
		return PCSC.SCARD_S_SUCCESS;
	}

	private int connect(RdpPacket in, RdpPacket out, boolean useUnicode) {
		int rv = PCSC.SCARD_S_SUCCESS;
		long hContext = -1;
		int shareMode;
		int preferredProtocol;
		String reader;
		Connection conn = null;
		int proto = 0;
		int hCard = 0;

		if (SmartCardDebug)
			Logger.debug("Use unicode: "+useUnicode);
		
		in.incrementPosition(0x1c);
		
		shareMode = in.getLittleEndian32();
		preferredProtocol = in.getLittleEndian32();
		reader = getReaderName(in, useUnicode);

		if (reader==null || reader.trim().equals("")) {
			if (SmartCardDebug)
				Logger.debug("Empty name found!");
			return PCSC.SCARD_E_UNKNOWN_READER;
		} else {
			if (SmartCardDebug)
				Logger.debug("Connect to: "+reader);
		}
		
		in.incrementPosition(4);
		hContext = in.getLittleEndian32();

		if (SmartCardDebug)
			Logger.debug("SmartCard SCardConnect(context: " + Long.toHexString(hContext) + ", share: "
				+ Integer.toHexString(shareMode) + ", proto: " + Integer.toHexString(preferredProtocol) + ", reader: "
				+ reader + ")");

		try {
			conn = PCSC.SCardConnect(hContext, reader, shareMode, preferredProtocol);
			if (conn != null) {
				proto = (int) conn.getActiveProtocol();
				hCard = (int) conn.getCardHandler();
			}
		} catch (PCSCException e) {
			if (SmartCardDebug)
				Logger.debug("SmartCard Connect failed: " + e.toString());

			rv = e.getErrCode();
		}

		out.setLittleEndian32(0x00000000);
		out.setLittleEndian32(0x00000000);
		out.setLittleEndian32(0x00000004);
		out.setLittleEndian32(0x016Cff34);
		out.setLittleEndian32(proto);
		out.setLittleEndian32(0x00000004);
		out.setLittleEndian32(hCard);
		out.incrementPosition(28);

		outForceAlignment(out, 8);

		return rv;
	}

	private int reconnect(RdpPacket in, RdpPacket out) {
		int rv = PCSC.SCARD_S_SUCCESS;
		long hContext = -1, hCard = -1;
		int shareMode = 0, preferredProtocol = 0, initialization = 0, activeProtocol = 0;
		Connection conn = null;

		in.incrementPosition(0x20);
		shareMode = in.getLittleEndian32();
		preferredProtocol = in.getLittleEndian32();
		initialization = in.getLittleEndian32();

		in.incrementPosition(0x4);
		hContext = in.getLittleEndian32();
		in.incrementPosition(0x4);
		hCard = in.getLittleEndian32();

		if (SmartCardDebug)
			Logger.debug("SmartCard SCardReConnect(context: " + Long.toHexString(hContext) + ", card: "
				+ Long.toHexString(hCard) + ", share: " + Integer.toHexString(shareMode) + ", proto: "
				+ Integer.toHexString(preferredProtocol) + ", initialization: " + initialization + ", active: "
				+ activeProtocol + ")");

		try {
			conn = PCSC.SCardReconnect(hCard, shareMode, preferredProtocol, initialization);
			activeProtocol = (int) conn.getActiveProtocol();
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}

		outForceAlignment(out, 8);
		out.setLittleEndian32(activeProtocol); /* reversed? */

		return rv;
	}

	private int disconnect(RdpPacket in, RdpPacket out) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int hContext;
		int hCard;
		int dwDisposition = 0;

		in.incrementPosition(0x20);
		dwDisposition = in.getLittleEndian32();
		in.incrementPosition(4);
		hContext = in.getLittleEndian32();
		in.incrementPosition(4);
		hCard = in.getLittleEndian32();

		try {
			PCSC.SCardDisconnect(hCard, dwDisposition);
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}
		outForceAlignment(out, 8);

		return rv;
	}

	private int beginTransaction(RdpPacket in, RdpPacket out) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int hCard;

		in.incrementPosition(0x30);
		hCard = in.getLittleEndian32();

		try {
			PCSC.SCardBeginTransaction(hCard);
		} catch (PCSCException e) {
			if (SmartCardDebug)
				Logger.error("SmartCard Begin Transaction failed: " + e.toString());
			rv = e.getErrCode();
		}

		outForceAlignment(out, 8);
		return rv;
	}

	private int endTransaction(RdpPacket in, RdpPacket out) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int hCard;
		int dwDisposition;

		in.incrementPosition(0x20);
		dwDisposition = in.getLittleEndian32();
		in.incrementPosition(0x0c);
		hCard = in.getLittleEndian32();

		try {
			PCSC.SCardEndTransaction(hCard, dwDisposition);
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}

		outForceAlignment(out, 8);
		return rv;
	}

	private int status(RdpPacket in, RdpPacket out, boolean useUnicode) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int readerLen;
		int atrLen;
		int hCard;
		Status status = null;
		int dataLength;

		in.incrementPosition(0x24);
		readerLen = in.getLittleEndian32();
		atrLen = in.getLittleEndian32();
		in.incrementPosition(0x0c);
		hCard = in.getLittleEndian32();
		in.incrementPosition(0x4);

		if (SmartCardDebug)
			Logger.debug("SmartCard status: readerLen=" + readerLen + " atrLen=" + atrLen + " hCard=" + hCard + " useUnicode=" + useUnicode);

		try {
			status = PCSC.SCardStatus(hCard);
		} catch (PCSCException e) {
			Logger.error("SmartCard Error getting status: " + e.toString());
			rv = e.getErrCode();
		}

		int poslen1 = out.getPosition();
		byte[] atr;
		if (status != null)
			atr = status.getAtr();
		else
			atr = new byte[0];

		atrLen = atr.length;

		out.setLittleEndian32(readerLen);
		out.setLittleEndian32(0x00020000);
		out.setLittleEndian32((status != null) ? status.getState() : 0);
		out.setLittleEndian32((status != null) ? status.getProtocol() : 0);
		out.copyFromByteArray(atr, 0, out.getPosition(), atrLen);
		// pad ATR
		out.incrementPosition(32);
		out.setLittleEndian32(atrLen);

		int poslen2 = out.getPosition();
		out.setLittleEndian32(readerLen);

		String[] names;
		if (status != null)
			names = status.getReaderNames();
		else
			names = new String[0];
		dataLength = putReaderNames(out, names, useUnicode);

		int pos = out.getPosition();
		out.setPosition(poslen1);
		out.setLittleEndian32(dataLength);
		out.setPosition(poslen2);
		out.setLittleEndian32(dataLength);
		out.setPosition(pos);

		outForceAlignment(out, 8);
		return rv;
	}

	public int state(RdpPacket in, RdpPacket out) {
		int rv = PCSC.SCARD_S_SUCCESS;
		long hCard;
		int state = 0, protocol = 0;
		int atrLength = 0;
		byte[] atr = null;
		Status status = null;

		in.incrementPosition(0x24);
		in.incrementPosition(4); /* atrLen */

		in.incrementPosition(0x0c);
		hCard = in.getLittleEndian32();
		in.incrementPosition(4);

		try {
			status = PCSC.SCardStatus(hCard);
			if (status != null) {
				state = status.getState();
				protocol = status.getProtocol();
				atr = status.getAtr();
				atrLength = atr.length;
			}
		} catch (PCSCException e) {
			rv = e.getErrCode();
			Logger.debug("SmartCard Error getting status: " + e.toString());
		}

		out.setLittleEndian32(state);
		out.setLittleEndian32(protocol);
		out.setLittleEndian32(atrLength);
		out.setLittleEndian32(0x00000001);
		out.setLittleEndian32(atrLength);

		out.copyFromByteArray(atr, 0, out.getPosition(), atrLength);
		// pad ATR
		out.incrementPosition(32);

		outRepos(out, atrLength);
		outForceAlignment(out, 8);

		return rv;
	}

	public int transmit(RdpPacket in, RdpPacket out) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int hCard, linkedLen;
		int[] map = new int[7];
		IoRequest pioSendPci = new IoRequest();
		IoRequest pioRecvPci = new IoRequest();
		IoRequest pPioRecvPci = null;
		int cbSendLength = 0, cbRecvLength = 0;
		byte[] sendBuf = null;
		byte[] recvBuf = null;

		in.incrementPosition(0x14);
		map[0] = in.getLittleEndian32();
		in.incrementPosition(0x4);
		map[1] = in.getLittleEndian32();

		pioSendPci.dwProtocol = in.getLittleEndian32();
		pioSendPci.cbPciLength = in.getLittleEndian32();

		map[2] = in.getLittleEndian32();
		cbSendLength = in.getLittleEndian32();
		map[3] = in.getLittleEndian32();
		map[4] = in.getLittleEndian32();
		map[5] = in.getLittleEndian32();
		cbRecvLength = in.getLittleEndian32();

		if ((map[0] & SCARD_INPUT_LINKED) != 0)
			skipLinked(in);

		in.incrementPosition(4);
		hCard = in.getLittleEndian32();

		if ((map[2] & SCARD_INPUT_LINKED) != 0) {
			/* sendPci */
			linkedLen = in.getLittleEndian32();
			pioSendPci.dwProtocol = in.getLittleEndian32();
			in.incrementPosition(linkedLen - 4);
			inRepos(in, linkedLen);
		}
		pioSendPci.cbPciLength = 8; // sizeof(SCARD_IO_REQUEST);

		if ((map[3] & SCARD_INPUT_LINKED) != 0) {
			/* send buffer */
			linkedLen = in.getLittleEndian32();

			sendBuf = new byte[linkedLen];
			in.copyToByteArray(sendBuf, 0, in.getPosition(), linkedLen);
			in.incrementPosition(linkedLen);
			inRepos(in, linkedLen);
		}

		if (cbRecvLength != 0)
			recvBuf = new byte[cbRecvLength];

		if ((map[4] & SCARD_INPUT_LINKED) != 0) {
			/* recvPci */
			linkedLen = in.getLittleEndian32();
			pioRecvPci.dwProtocol = in.getLittleEndian32();
			in.incrementPosition(linkedLen - 4);
			inRepos(in, linkedLen);

			map[6] = in.getLittleEndian32();
			if ((map[6] & SCARD_INPUT_LINKED) != 0) {
				/* not sure what this is */
				linkedLen = in.getLittleEndian32();
				in.incrementPosition(linkedLen);
				inRepos(in, linkedLen);
			}
			pioRecvPci.cbPciLength = 8; // sizeof(SCARD_IO_REQUEST);
			pPioRecvPci = pioRecvPci;
		} else {
			pPioRecvPci = null;
		}
		pPioRecvPci = null;

		if (SmartCardDebug)
			Logger.debug("SmartCard SCardTransmit(hcard: 0x" + Integer.toHexString(hCard) + ", send: " + cbSendLength
				+ " bytes, recv: " + recvBuf.length + " bytes)");

		try {
			recvBuf = PCSC.SCardTransmit(hCard, pioSendPci.dwProtocol, sendBuf, 0, cbSendLength);
		} catch (PCSCException e) {
			rv = e.getErrCode();
			Logger.error("SmartCard Transmission error: " + e.toString());
		}

		if (rv == PCSC.SCARD_S_SUCCESS) {
			if (SmartCardDebug)
				Logger.debug("SmartCard Success (" + recvBuf.length + " bytes)");

			out.setLittleEndian32(0);
			outBufferStart(out, recvBuf.length);

			outBuffer(out, recvBuf);
		}

		outForceAlignment(out, 8);

		return rv;
	}

	private int control(RdpPacket in, RdpPacket out) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int[] map = new int[3];
		int controlCode, controlFunction, recvLength, outBufferSize;
		int hContext, hCard;
		byte[] recvBuffer = null, sendBuffer = null;
		int nBytesReturned = 0;

		in.incrementPosition(0x14);
		map[0] = in.getLittleEndian32();
		in.incrementPosition(0x4);
		map[1] = in.getLittleEndian32();
		controlCode = in.getLittleEndian32();
		recvLength = in.getLittleEndian32();
		map[2] = in.getLittleEndian32();
		in.incrementPosition(0x4);
		outBufferSize = in.getLittleEndian32();
		in.incrementPosition(0x4);
		hContext = in.getLittleEndian32();
		in.incrementPosition(0x4);
		hCard = in.getLittleEndian32();

		if (WinCtlDeviceType(controlCode) == WIN_FILE_DEVICE_SMARTCARD) {
			controlFunction = WinCtlFunction(controlCode);
			controlCode = SCardCtlCode(controlFunction);
		}

		if ((map[2] & SCARD_INPUT_LINKED) != 0) {
			recvLength = in.getLittleEndian32();
			recvBuffer = getBytes(in, recvLength);
		}

		try {
			sendBuffer = PCSC.SCardControl(hCard, controlCode, recvBuffer);
			nBytesReturned = sendBuffer.length;
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}

		out.setLittleEndian32(nBytesReturned);
		out.setLittleEndian32(0x4);
		out.setLittleEndian32(nBytesReturned);

		if (nBytesReturned > 0) {
			putBytes(out, sendBuffer);
		}
		outForceAlignment(out, 8);

		return rv;
	}

	private int getAttrib(RdpPacket in, RdpPacket out) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int hCard;
		long attrId;
		byte[] attrib = null;

		in.incrementPosition(0x20);
		attrId = in.getLittleEndian32();
		in.incrementPosition(0x4 + 0x4 + 0xc);
		hCard = in.getLittleEndian32();

		try {
			attrib = PCSC.SCardGetAttrib(hCard, attrId);
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}

		if (rv == PCSC.SCARD_S_SUCCESS && attrib != null) {
			out.setLittleEndian32(attrib.length);
			out.setLittleEndian32(0x200);
			out.setLittleEndian32(attrib.length);

			putBytes(out, attrib);
			outRepos(out, attrib.length);
			out.setLittleEndian32(0);
		}
		outForceAlignment(out, 8);

		return rv;
	}

	private int locateCards(RdpPacket in, RdpPacket out, boolean useUnicode) {
		int rv = PCSC.SCARD_S_SUCCESS;
		int hContext;
		int atrMaskCount;
		AtrMask[] atrMasks;
		int readerCount;
		ReaderState[] readerStates;

		in.incrementPosition(0x2c);
		hContext = in.getLittleEndian32();
		atrMaskCount = in.getLittleEndian32();
		atrMasks = new AtrMask[atrMaskCount];

		for (int i = 0; i < atrMaskCount; i++) {
			atrMasks[i] = new AtrMask();
			atrMasks[i].cbAtr = in.getLittleEndian32();
			atrMasks[i].rgbAtr = getBytes(in, 36);
			atrMasks[i].rgbMask = getBytes(in, 36);
		}

		readerCount = in.getLittleEndian32();
		readerStates = getReaderStates(in, readerCount, useUnicode);

		try {
			PCSC.SCardGetStatusChange(hContext, 0x1, readerStates);
		} catch (PCSCException e) {
			rv = e.getErrCode();
		}

		for (int i = 0; i < atrMaskCount; i++) {
			AtrMask curMask = atrMasks[i];

			for (int j = 0; j < readerCount; j++) {
				ReaderState state = readerStates[j];

				boolean ok = true;
				byte[] atr = state.getAtr();

				for (int k = 0; k < curMask.cbAtr; k++) {
					if (curMask.rgbMask[k] != 0
							&& (atr.length < k || ((curMask.rgbAtr[k] & curMask.rgbMask[k]) != (atr[k] & curMask.rgbMask[k])))) {
						ok = false;
						break;
					}
				}
				if (ok) {
					int event = state.getEvent();
					event |= PCSC.SCARD_STATE_ATRMATCH;
					state.setEvent(event);
				}
			}
		}

		putReaderStates(out, readerStates);

		outForceAlignment(out, 8);

		return rv;
	}

	private byte[] getBytes(RdpPacket in, int length) {
		byte[] data = new byte[length];
		in.copyToByteArray(data, 0, in.getPosition(), length);
		in.incrementPosition(length);
		return data;
	}

	private void putBytes(RdpPacket out, byte[] bytes) {
		if (bytes != null) {
			out.copyFromByteArray(bytes, 0, out.getPosition(), bytes.length);
			out.incrementPosition(bytes.length);
		}
	}

	private String getReaderName(RdpPacket in, boolean useUnicode) {
		String name;

		in.incrementPosition(8);

		int dataLength = in.getLittleEndian32();

		// we don't need the trailing \0
		int dataSize = useUnicode ? (dataLength - 1) * 2 : (dataLength - 1);
		if (dataSize>0) {
			byte[] data = getBytes(in, dataSize);
			int increment = (useUnicode ? 2 : 1);
			in.incrementPosition(increment);
			if (useUnicode)
				name = new String(data, Charset.forName("UTF-16LE"));
			else
				name = new String(data);
			
			inRepos(in, dataSize + increment);
		} else {
			name = null;
		}
		
		if (dataSize>0 && name==null) {
			throw new RuntimeException("Extrange error!");
		}
		
		return name;
	}

	private int putReaderName(RdpPacket out, String name, boolean useUnicode) {
		byte[] res = null;
		try {
			if (useUnicode)
				res = name.getBytes("UTF-16LE");
			else
				res = name.getBytes("US-ASCII");
		} catch (UnsupportedEncodingException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		out.copyFromByteArray(res, 0, out.getPosition(), res.length);

		int dataLength = res.length + (useUnicode ? 2 : 1);
		out.incrementPosition(dataLength);

		return dataLength;
	}

	private int putReaderNames(RdpPacket out, String[] names, boolean useUnicode) {
		int dataLength = 0;
		if (names != null) {
			for (String readerName : names) {
				int length = putReaderName(out, readerName, useUnicode);
				dataLength += length;
			}
		}
		int padding = useUnicode ? 2 : 1;
		dataLength += padding;
		out.incrementPosition(padding);
		outRepos(out, dataLength);

		return dataLength;
	}

	private void skipLinked(RdpPacket in) {
		int len;

		len = in.getLittleEndian32();
		if (len > 0) {
			in.incrementPosition(len);
			inRepos(in, len);
		}
	}

	private void outBufferLimit(RdpPacket out, byte[] buffer, int highLimit) {
		int length = buffer.length;
		int header = (length < 0) ? 0 : ((length > highLimit) ? highLimit : length);
		out.setLittleEndian32(header);

		if (length == 0) {
			out.setLittleEndian32(0);
		} else {
			if (header < length)
				length = header;

			out.copyFromByteArray(buffer, 0, out.getPosition(), length);
			out.incrementPosition(length);
			outRepos(out, length);
		}
	}

	private void outBuffer(RdpPacket out, byte[] buffer) {
		outBufferLimit(out, buffer, 0x7fffffff);
	}

	private void outBufferStartLimit(RdpPacket out, int length, int highLimit) {
		int header = (length < 0) ? 0 : ((length > highLimit) ? highLimit : length);
		out.setLittleEndian32(header);
		out.setLittleEndian32(0x00000001); /* Magit int - any nonzero */
	}

	private void outBufferStart(RdpPacket out, int length) {
		outBufferStartLimit(out, length, 0x7fffffff);
	}

	private ReaderState[] getReaderStates(RdpPacket in, int number, boolean useUnicode) {
		ReaderState[] servers = new ReaderState[number];

		for (int i = 0; i < number; i++) {
			in.incrementPosition(4);

			/*
			 * TODO: on-wire is little endian; need to either convert to host
			 * endian or fix the headers to request the order we want
			 */
			int state = in.getLittleEndian32();
			int event = in.getLittleEndian32();
			int cbAtr = in.getLittleEndian32();
			byte[] atr = new byte[cbAtr];

			if (cbAtr > 0)
				in.copyToByteArray(atr, 0, in.getPosition(), cbAtr);
			// max atr size provisioned anyway
			in.incrementPosition(32);

			in.incrementPosition(4);

			servers[i] = new ReaderState(null, state, event, atr);
		}

		for (int i = 0; i < number; i++) {
			ReaderState cur = servers[i];
			cur.setName(getReaderName(in, useUnicode));

			if (SmartCardDebug) {
				Logger.debug("["+i+"]   " + cur.getName());
				Logger.debug("       user: " + cur.getUserData() + ", state: 0x" + Integer.toHexString(cur.getState())
					+ ", event: 0x" + Integer.toHexString(cur.getEvent()));
			}

			if (cur.getName().equals("\\\\?PnP?\\Notification")) {
				int state = cur.getState();
				cur.setState(state |= PCSC.SCARD_STATE_IGNORE);
			}
		}
		return servers;
	}

	private void putReaderStates(RdpPacket out, ReaderState[] states) {
		int dwCount = states.length;

		out.setLittleEndian32(dwCount);
		out.setLittleEndian32(0x20000);
		out.setLittleEndian32(dwCount);

		for (int i = 0; i < dwCount; i++) {
			ReaderState cur = states[i];

			if (SmartCardDebug) {
				Logger.debug("   " + cur.getName());
				Logger.debug("       user: " + cur.getUserData() + ", state: 0x" + Integer.toHexString(cur.getState())
					+ ", event: 0x" + Integer.toHexString(cur.getEvent()));
			}

			/* TODO: do byte conversions if necessary */
			out.setLittleEndian32(cur.getState());
			out.setLittleEndian32(cur.getEvent());

			byte[] atr = cur.getAtr();
			out.setLittleEndian32(atr.length);
			out.copyFromByteArray(atr, 0, out.getPosition(), atr.length);
			out.incrementPosition(32);
			if (i + 1 < dwCount) {
				out.incrementPosition(4);
			}
		}
	}

	private int WinCtlFunction(int ctl_code) {
		return ((ctl_code & 0x3FFC) >> 2);
	}

	private int WinCtlDeviceType(int ctl_code) {
		return (ctl_code >> 16);
	}

	private int SCardCtlCode(int code) {
		return (0x42000000 + (code));
	}

	@Override
	public int read(int handle, byte[] data, int length, int offset, int[] result) {
		Logger.debug("SmartCard Unimplemented method: SmartCard::read");
		return 0;
	}

	@Override
	public int write(int handle, byte[] data, int length, int offset, int[] result) {
		Logger.debug("SmartCard Unimplemented method: SmartCard::write");
		return 0;
	}

}
