package org.vnc.rfbcaching;

public abstract class RfbHashProvider {	
	abstract byte[] getHash(byte[] data); 
}
