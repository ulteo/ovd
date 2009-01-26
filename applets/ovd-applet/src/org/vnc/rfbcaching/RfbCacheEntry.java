package org.vnc.rfbcaching;

public class RfbCacheEntry {
	private int rfbEncoding;
	private byte[] rfbData;

	public RfbCacheEntry(int rfbEncoding,byte[] rfbData) {
		this.rfbData = rfbData;
		this.setRfbEncoding(rfbEncoding);
	}

	public void setRfbData(byte[] rfbData) {
		this.rfbData = rfbData;
	}
	public byte[] getRfbData() {
		return rfbData;
	}

	public void setRfbEncoding(int rfbEncoding) {
		this.rfbEncoding = rfbEncoding;
	}

	public int getRfbEncoding() {
		return rfbEncoding;
	}
}
