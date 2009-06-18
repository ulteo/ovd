package org.vnc.rfbcaching;

/**
 * RFB Caching constants
 */
public interface IRfbCachingConstants {
	// RFB protocol specific constant 
	public static final int RFB_CACHE_MAINT_ALG_LRU = 1;
	public static final int RFB_CACHE_MAINT_ALG_FIFO = 2;
	
	public static final int RFB_CACHE_SERVER_INIT_MSG = 4;
	public static final int RFB_CACHE_CLIENT_INIT_MSG = 7;
	public static final int RFB_CACHE_ENCODING = 64;
	
	// Default values
	public static final int RFB_CACHE_DEFAULT_SIZE = 64;
	public static final int RFB_CACHE_DEFAULT_DATA_SIZE = 128;
	public static final int RFB_CACHE_DEFAULT_MAINT_ALG = RFB_CACHE_MAINT_ALG_LRU;
	public static final int RFB_CACHE_DEFAULT_VER_MAJOR = 1;
	public static final int RFB_CACHE_DEFAULT_VER_MINOR = 0;
	
	public static final String RFB_CACHE_DEFAULT_FACTORY = "org.vnc.rfbcaching.RfbCacheFactory";
}
