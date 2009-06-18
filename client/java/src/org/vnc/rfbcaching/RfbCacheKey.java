package org.vnc.rfbcaching;

/**
 * This class is wrapper for key byte array. 
 * It is necessary for using byte array
 * as key with standard Java hash-aware containers 
 */

public class RfbCacheKey {
	public byte[] k = null;

	/**
	 * Constructor
	 * @param k
	 */
	public RfbCacheKey(byte[] k){
		this.k = k;
	} 
	
	/**
	 * Override equals method 
	 */
	@Override
	public boolean equals(Object o){
		if (!(o instanceof RfbCacheKey))
			return false;
		if (((RfbCacheKey)o).k.length != this.k.length )
			return false;
		for (int i=0;i<k.length; i++){
			if (k[i]!= ((RfbCacheKey)o).k[i]) return false;
		}	
		return true;
	}
	
	/**
	 * Override hashCode method
	 * "One-A-Time" hash function. 
	 */
	@Override
	public int hashCode(){
		int hash = 0;
		for (int i = 0; i < k.length; i++) {
	        hash += (k[i] & 0xff);
	        hash += (hash << 10);
	        hash ^= (hash >> 6);
	    }
	    hash += (hash << 3);
	    hash ^= (hash >> 11);
	    hash += (hash << 15);		
		return hash;
	}
}
