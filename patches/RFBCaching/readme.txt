==========================
0. Build instructions
==========================

1) Client
  
> tar xvfz ovd-applet_1.0beta1.1.tar.gz 
> cd ovd-applet
> patch -p0 < ../client.patch

... and following the ovd-applet's readme:

> export CLASSPATH = ...
> ant 


2) Server

> tar xvfz tightvnc-1.3.9-debianpatched.tar.gz 
> cd tightvnc-1.3.9
> patch -p0 < ../server.patch

 ... and following the tightvnc's readme:
 
> xmkmf
> make World
> cd Xvnc
> ./configure
> make


==========================
1. Xvnc's cache parameters
==========================

Next parameters for Xvnc executable file are responsible for caching feature's behavior:
	-caching		activates caching feature;
	-caching_ent N		maximum number of items in cache;
	-caching_malg LRU/FIFO	cache maintenance algorithm: Last-Recent-Used or First-In-First-Out;
	-caching_minsize N	minimal size of rectangle's encoded data that should be cached.
	
Example:
	Xvnc -caching -caching_ent 1500 -caching_malg LRU -caching_minsize 20
	
In ./vncserver (Xvnc start-up script) next variables are responsible for caching:
	$caching_enable
	$caching_maximum_entries
	$caching_maintenance_algorithm
	$caching_minimum_cache_datasize
	
Client-server working session's caching settings and statistics (after client is gone) are put to the
~/.vnc/user:display.log file.
	
	
==============================
2. Xvnc server developer notes
==============================

"Catch-Raw-Data mode" mechanism:
--------------------------------
Server sends single rectangle update message by executing one of the next functions:
	rfbSendRectEncodingRaw()
	rfbSendRectEncodingRRE()
	rfbSendRectEncodingCoRRE()
	rfbSendRectEncodingHextile()
	rfbSendRectEncodingZlib()
	rfbSendRectEncodingTight(),
depending on session's encoding. The purpose of the Catch-Raw-Data mode is to send data
to buffers (cl->rawCacheBuffers) instead of client's socket. For this one calls rfbCachingStuff_start()
before function sends headers, rfbCachingStuff_ headersWereSent() after function sends rectangle's
update message's header (12 bytes) and rfbCachingStuff_stop() in the end after one sends the last byte of
rectangle's content. The rfbCachingStuff_stop() is the heart of the caching mechanism, to cache or not
to cache decisions are taken there.

Caching storage:
---------------
To store cache items (rectangle's encoded content's sha1 hash code: block of 20 bytes) one uses 
"List of caches" and "Cache queue" entities.

- List of caches (cl->cache[][]). All cache items are grouped by the first two bytes, for example:
	cl->cache[a1][a2] contains 
		a1, a2, a3, ... a20			
		a1, a2, b3, ... b20
		...
Each group of cache (cl->cache[a1][a2]) organized in the next way: 
	first 4 bytes stores number of the items in this group (size)
	last bytes (size) stores cache items, one by one.
Each cache item in the group of cache takes 20 + sizeof(char *) bytes:
	first 20 bytes are the sha-1 hash of the cached rectangle's encoded content,
	last sizeof(char *) bytes are the pointer to the item in cache queue entity (described below).
	
- Cache queue (cl->cache_queue) brings the order to cache items (cl->cache), so one could define if one
  cache item is older (younger) than another. It stores cache queue items, one by one. Each cache queue
  item consists of 3*sizeof(char *) bytes:
	first sizeof(char *) bytes stores pointer to the next (older) item;
	next sizeof(char *) bytes stores pointer to the previous (younger) item;
	last sizeof(char *) bytes stores pointer to the item in cache (cl->cache).	
cl->cache_queue_oldest and cl->cache_queue_newest point to the oldest and youngest items in cache queue
correspondenly (define the beginning and the end of the queue)
		
		
			

==============================
3. VNC Applet cache parameters
==============================

1) Enable/Disable caching on client
    name="rfb.cache.enabled" 
    value="true" or "false" string
    default="false"

2) Cache version 
    name="rfb.cache.ver.major" 
    value="1" - integer
    default="1"

    name="rfb.cache.ver.minor" 
    value="0" - integer
    default="0"

3) Maximum number of items to be stored in the cache 
    name="rfb.cache.size" 
    value= integer
    default="64"

4) Cache maintenance algorithm
    name="rfb.cache.alg"
    value= "LRU" or "FIFO" string 
    default="LRU"

5) Frame buffer data size to be cached (in bytes)
    name="rfb.cache.datasize"
    value= integer
    default="128"      
    

==============================
4. VNC Applet developer notes
==============================

1) RFBCaching protocol extension constants 

Please, refer to org\vnc\rfbcaching\IRfbCachingConstants.java if you want to change it:
	ServerCacheInit 
	public static final int RFB_CACHE_SERVER_INIT_MSG = 4;
	public static final int RFB_CACHE_CLIENT_INIT_MSG = 7;
	public static final int RFB_CACHE_ENCODING = 64;

2) Client Ñache implementation

Standard LinkedHashMap container is used for the LRU/FIFO cache implementation.

3) HashMap Key

- Hash provider is configurable via IRfbCache.setHasher(RfbHashProvider hasher) method (May be useful for
  future implementation with different hashing algorithm)
- java.security.MessageDigest class is used for hash value calculation
- Byte array wrapper is used as key of cache table 
- "One-a-time" hash algorithm used for hashing SHA-1 key byte array


