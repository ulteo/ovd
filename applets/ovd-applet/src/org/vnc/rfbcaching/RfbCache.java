package org.vnc.rfbcaching;

import java.security.NoSuchAlgorithmException;
import java.util.Map;
import java.util.LinkedHashMap;

public class RfbCache implements IRfbCache {

	private RfbHashProvider hasher;
		
	private final static int DEFAULT_CACHE_SIZE = 64;
	
	private int cacheSize = DEFAULT_CACHE_SIZE;
	private float cacheLoadFactor = 0.75f;
	
	LinkedHashMap/*<Object, RfbCacheEntry>*/ cacheTable; 	
	
	public RfbCache(RfbCacheProperties p) throws RfbCacheCreateException{		 			
		this.cacheSize = p.getCacheMaxEntries();
		try{
			this.hasher = new RfbShaHashProvider();
		}catch(NoSuchAlgorithmException e){
			throw new RfbCacheCreateException();
		}
		int maxCacheNum = (int)Math.ceil( cacheSize / cacheLoadFactor ) + 1;
		boolean isLRU = (p.getCacheMaintAlg() != IRfbCachingConstants.RFB_CACHE_MAINT_ALG_FIFO);
		this.cacheTable = new LinkedHashMap/*<Object, RfbCacheEntry>*/(maxCacheNum,cacheLoadFactor, isLRU /* access order storage */){
			static final long serialVersionUID = 1;
			
			protected boolean removeEldestEntry (Map.Entry/*<Object, RfbCacheEntry>*/ eldest) {
				 boolean result = size() > RfbCache.this.cacheSize;
		         return result;
		    }
		};		
	}		
			
	public RfbCacheEntry get(byte[] key){
		return (RfbCacheEntry)cacheTable.get(new RfbCacheKey(key));
	}
	
	public Object put(byte[] key, RfbCacheEntry value){
		return cacheTable.put(new RfbCacheKey(key), value);
	}

	public byte[] hash(byte[] data) {		
		return hasher.getHash(data);
	}

	public void setHashProvider(RfbHashProvider hashProvider) {
		hasher = hashProvider;
	}

	public boolean containsKey(byte[] key) {
		return cacheTable.containsKey(new RfbCacheKey(key));
	}
	
	public int size(){return cacheTable.size();}
	
	public static String asHex(byte buf[])
    	{
	    StringBuffer strbuf = new StringBuffer(buf.length * 2);     
	    for(int i=0; i< buf.length; i++)
	    	strbuf.append(Integer.toString( ( buf[i] & 0xff ) + 0x100, 16).substring( 1 ));	    
	    return strbuf.toString();
    	}
}
