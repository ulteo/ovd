package org.vnc.rfbcaching;

import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

public class RfbShaHashProvider extends RfbHashProvider {
	private MessageDigest md;
	
	public RfbShaHashProvider() throws NoSuchAlgorithmException{	  
	      md = MessageDigest.getInstance("SHA-1");
	}
		
	@Override
	byte[] getHash(byte[] data){			
		return md.digest(data);
	}
}
