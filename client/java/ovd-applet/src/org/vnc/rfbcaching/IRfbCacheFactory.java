package org.vnc.rfbcaching;

/**
 * 
 * Cache Factory interface. 
 * Due to possible RFBCaching protocol 
 * extensions client may use 
 * different cache implementations.
 * Cache implementation is chosen according to
 * cache version specified in the Server 
 * RfbServerCacheInit message.
 *    
 */

public interface IRfbCacheFactory {
	public IRfbCache CreateRfbCache(RfbCacheProperties p);	
}
