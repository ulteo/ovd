package org.vnc.rfbcaching;
 

public interface IRfbCache {
			
	//
	// Put (key, value) pair into cache
	//
	public Object  put(byte[] key, RfbCacheEntry value);
	
	//
	// Get cache entry value corresponding to specified key
	// Returns null if value not found 
	//
	public RfbCacheEntry get(byte[] key);
	
	//
	// Get Hash value of binary data
	//
	public byte[] hash(byte[] data);
	
	
	//
	// Returns true if Cache contains key
	//
	public boolean containsKey(byte[] key);
	
	//
	// Set hash calculator class 
	//
	public void setHashProvider(RfbHashProvider hashProvider);
	
	//
	// Return size of cache
	//
	public int size();
}
