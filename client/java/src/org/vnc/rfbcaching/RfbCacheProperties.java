package org.vnc.rfbcaching;

public class RfbCacheProperties {

	private int cacheMajor;
	private int cacheMinor;	
	private int cacheMaxEntries;	
	private int cacheMinDataSize; 
	private int cacheMaintAlg;
	
	public RfbCacheProperties(int cacheMaintAlg, int cacheMaxEntries,
			int cacheMinDataSize, int cacheMajor, int cacheMinor) {		
		this.cacheMaintAlg = cacheMaintAlg;
		this.cacheMaxEntries = cacheMaxEntries;
		this.cacheMinDataSize = cacheMinDataSize;		
		this.cacheMajor = cacheMajor;
		this.cacheMinor = cacheMinor;
	}

	public int getCacheMaxEntries() {
		return cacheMaxEntries;
	}

	public int getCacheMinDataSize() {
		return cacheMinDataSize;
	}

	public int getCacheMaintAlg() {
		return cacheMaintAlg;
	}

	public int getCacheMajor() {
		return cacheMajor;
	}

	public int getCacheMinor() {
		return cacheMinor;
	}
}
