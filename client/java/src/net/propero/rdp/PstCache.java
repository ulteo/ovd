/* PstCache.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:16 $
 *
 * Copyright (c) 2005 Propero Limited
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author: Julien LANGLOIS <julien@ulteo.com> 2012
 * Author: Thomas MOUTON <thomas@ulteo.com> 2012
 *
 * Purpose: Handle persistent caching
 */

package net.propero.rdp;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.RandomAccessFile;
import java.lang.Thread;
import java.util.concurrent.BlockingQueue;
import java.util.concurrent.LinkedBlockingQueue;
import java.util.concurrent.TimeUnit;
import org.apache.log4j.Logger;

public class PstCache {

    protected static Logger logger = Logger.getLogger(Rdp.class);
    
    public static final int MAX_CELL_SIZE = 0x1000;  /* pixels */
    
    protected Options opt = null;
    protected Common common = null;
    
    protected Thread thread = null;
    protected BlockingQueue<PstCacheJob> spool = null;
    
    private Bitmap bmpConverter = null;
    
    public PstCache(Options opt_, Common common_) {
		this.opt = opt_;
		this.common = common_;
		
		this.bmpConverter = new Bitmap(this.opt);
		
		this.spool = new LinkedBlockingQueue<PstCacheJob>() ;
	}

    protected boolean IS_PERSISTENT(int id){
        return (id < 8 && g_pstcache_fd[id] != null);
    }

    public int g_stamp;
    private File[] g_pstcache_fd = new File[8];
    private int g_pstcache_Bpp;
    private boolean g_pstcache_enumerated = false;

    /* Update usage info for a bitmap */
    protected void touchBitmap(int cache_id, int cache_idx, int stamp)
    {
        logger.debug("PstCache.touchBitmap");
    FileOutputStream fd;

    if (!IS_PERSISTENT(cache_id) || cache_idx >= this.opt.persistent_caching_max_cells)
        return;

    try {
        fd = new FileOutputStream(g_pstcache_fd[cache_id]);
        
        fd.write(toBigEndian32(stamp), 12 + cache_idx * (g_pstcache_Bpp * MAX_CELL_SIZE + CELLHEADER.SIZE),4);
        //rd_lseek_file(fd, 12 + cache_idx * (g_pstcache_Bpp * MAX_CELL_SIZE + sizeof(CELLHEADER))); // this seems to do nothing (return 0) in rdesktop
        //rd_write_file(fd, &stamp, sizeof(stamp)); // same with this one???

    } catch (IOException e) {
        return;
    }
 }
    
    private static byte[] toBigEndian32(int value){
        byte[] out = new byte[4];
        out[0] = (byte) (value & 0xFF);
        out[1] = (byte) (value & 0xFF00);
        out[2] = (byte) (value & 0xFF0000);
        out[3] = (byte) (value & 0xFF000000);
        return out;
    }

 /* Load a bitmap from the persistent cache */
 public boolean pstcache_load_bitmap(int cache_id, int cache_idx) throws IOException, RdesktopException
 {
    logger.debug("PstCache.pstcache_load_bitmap");
    byte[] celldata = null;
    RandomAccessFile fd;
    Bitmap bitmap;
    byte[] cellHead = null;
    
    if (!this.opt.persistent_bitmap_caching)
        return false;

    if (!IS_PERSISTENT(cache_id) || cache_idx >= this.opt.persistent_caching_max_cells)
        return false;

        fd = new RandomAccessFile(g_pstcache_fd[cache_id], "r");
        int offset = cache_idx * (g_pstcache_Bpp * MAX_CELL_SIZE + CELLHEADER.SIZE);

        fd.seek(offset);
        cellHead = new byte[CELLHEADER.SIZE];
        fd.read(cellHead, 0, CELLHEADER.SIZE);
        CELLHEADER c = new CELLHEADER(cellHead);

        celldata = new byte[c.length];
        fd.seek(offset + CELLHEADER.SIZE);
        fd.read(celldata, 0, c.length);

        logger.debug("Loading bitmap from disk (" + cache_id + ":" + cache_idx + ")\n");

        bitmap = new Bitmap(this.bmpConverter.convertImage(celldata, this.opt.Bpp), c.width, c.height, 0, 0, this.opt);
        this.common.cache.putBitmap(cache_id,cache_idx,bitmap, (int) c.stamp);

    return true;
 }

 /* Store a bitmap in the persistent cache */
 public boolean pstcache_put_bitmap(int cache_id, int cache_idx, byte[] bitmap_id, int width, int height, int length, byte[] data) {
	logger.debug("PstCache.pstcache_put_bitmap");
	 
	PstCacheJob job = new PstCacheJob(cache_id, cache_idx, bitmap_id, width, height, length, data);

	try {
		this.spool.put(job);
	}
	catch (InterruptedException e) {
		return false;
	}

	if (this.thread == null || ! this.thread.isAlive()) {
		this.thread = new PstCacheThread(this, this.spool);
		this.thread.start();
	}

	return true;
 }

 public boolean pstcache_put_bitmap_process(int cache_id, int cache_idx, byte[] bitmap_id, int width, int height, int length, byte[] data) throws IOException
 {
    logger.debug("PstCache.pstcache_put_bitmap_process");
    RandomAccessFile fd;
    CELLHEADER cellhdr = new CELLHEADER();

    if (!IS_PERSISTENT(cache_id) || cache_idx >= this.opt.persistent_caching_max_cells)
        return false;

    for(int i = 0; i < bitmap_id.length; i++)
        cellhdr.bitmap_id[i] = (byte) (bitmap_id[i] & 0x00ff);
    
    cellhdr.width = (byte) (width & 0x000000ff);
    cellhdr.height = (byte) (height & 0x000000ff);
    cellhdr.length = length;
    cellhdr.stamp = 0;

    fd = new RandomAccessFile(g_pstcache_fd[cache_id], "rws");
    int offset = cache_idx * (g_pstcache_Bpp * MAX_CELL_SIZE + CELLHEADER.SIZE);

    byte[] cellHeaderData = cellhdr.toBytes();
    fd.seek(offset);
    fd.write(cellhdr.toBytes(), 0, CELLHEADER.SIZE);

    fd.seek(offset + CELLHEADER.SIZE);
    fd.write(data, 0, length);

    fd.close();
    return true;
 }

 /* list the bitmaps from the persistent cache file */
 public int pstcache_enumerate(int cache_id, byte[][] idlist) throws IOException, RdesktopException
 {
    logger.debug("PstCache.pstcache_enumerate");
    RandomAccessFile fd;
    int idx, c = 0;
    CELLHEADER cellhdr = null;

    if (!(this.opt.bitmap_caching && this.opt.persistent_bitmap_caching && IS_PERSISTENT(cache_id)))
        return 0;

    /* The server disconnects if the bitmap cache content is sent more than once */
    if (g_pstcache_enumerated)
	    return 0;

    logger.debug("pstcache enumeration... ");
    fd = new RandomAccessFile(g_pstcache_fd[cache_id], "r");

    for (idx = 0; idx < this.opt.persistent_caching_max_cells; idx++)
    {
        fd.seek(idx * (g_pstcache_Bpp * MAX_CELL_SIZE + CELLHEADER.SIZE));
        
        byte[] cellhead_data = new byte[CELLHEADER.SIZE];
        if (fd.read(cellhead_data) <= 0)
            break;

        cellhdr = new CELLHEADER(cellhead_data);

        int result = 0;
        for(int i = 0; i < cellhdr.bitmap_id.length; i++){
            result += cellhdr.bitmap_id[i];
        }

        if (result != 0)
        {
            for(int i = 0; i < 8; i++){
                idlist[idx][i] = (byte) cellhdr.bitmap_id[i];
            }

            if (cellhdr.stamp != 0 && this.opt.precache_bitmaps && (this.opt.server_bpp > 8))
            {
                /* Pre-caching is not possible with 8bpp because a colourmap
                 * is needed to load them */
                if (pstcache_load_bitmap(cache_id, idx))
                    c++;
            }

            g_stamp = Math.max(g_stamp, cellhdr.stamp);
        }
        else
        {
            break;
        }
    }
    fd.close();

    logger.info(idx + " bitmaps in persistent cache, " + c + " bitmaps loaded in memory");
    g_pstcache_enumerated = true;
    return idx;
 }

 /* initialise the persistent bitmap cache */
 public boolean pstcache_init(int cache_id)
 {
    String filename;

    if (g_pstcache_enumerated)
        return true;

    g_pstcache_fd[cache_id] = null;

    if (!(this.opt.bitmap_caching && this.opt.persistent_bitmap_caching))
        return false;

    g_pstcache_Bpp = this.opt.Bpp;
    filename = this.opt.persistent_caching_path + "pstcache_" + cache_id + "_" + g_pstcache_Bpp;
    logger.debug("persistent bitmap cache file: " + filename);
    
    File cacheDir = new File(this.opt.persistent_caching_path);
    if(!cacheDir.exists() && !cacheDir.mkdirs()){
        logger.warn("failed to get/make cache directory");
        return false;
    }
    
    File f = new File(filename);
    
    try {
        if(!f.exists() && !f.createNewFile()){
            logger.warn("Could not create cache file");
            return false;
        }
    } catch (IOException e) {
        return false;
    }

    g_pstcache_fd[cache_id] = f;
    return true;
 }
 
 
}

class PstCacheJob {
	public int cache_id;
	public int cache_idx;
	public byte[] bitmap_id;
	public int width;
	public int height;
	public int length;
	public byte[] data;
	
	public PstCacheJob(int cache_id, int cache_idx, byte[] bitmap_id, int width, int height, int length, byte[] data) {
		this.cache_id = cache_id;
		this.cache_idx = cache_idx;
		this.bitmap_id = bitmap_id;
		this.width = width;
		this.height = height;
		this.length = length;
		this.data = data;
	}
}

class PstCacheThread extends Thread {
	private PstCache parent;
	private BlockingQueue<PstCacheJob> spool;
	
	public PstCacheThread(PstCache parent, BlockingQueue<PstCacheJob> spool) {
		this.parent = parent;
		this.spool = spool;
	}
	
	public void run() {
		while (true) {
			PstCacheJob job = null;
			try {
				job = this.spool.poll(1, TimeUnit.SECONDS);
			}
			catch (InterruptedException e) {
				break;
			}
			
			if (job == null)
				return;
			
			try {
				this.parent.pstcache_put_bitmap_process(job.cache_id, job.cache_idx, job.bitmap_id, job.width, job.height, job.length, job.data);
			}
			catch (IOException e) {}
		}
	}
}


/* Header for an entry in the persistent bitmap cache file */
class CELLHEADER
{
   public final static int SIZE = 16; //8 + 1 + 1 + 2 + 4

   byte[] bitmap_id = new byte[8]; // int8 *
   byte width, height; // int8
   int length; // int16
   int stamp; // int32
   
   public CELLHEADER(){
       
   }
   
   public CELLHEADER(byte[] data){
       for(int i = 0; i < bitmap_id.length; i++)
           bitmap_id[i] = data[i];

       width = data[bitmap_id.length];
       height = data[bitmap_id.length + 1];
       length = ((data[bitmap_id.length + 2] & 0x000000ff) << 8) +
	        (data[bitmap_id.length + 3] & 0x000000ff);
       stamp = ((data[bitmap_id.length + 4] & 0x000000ff) << 24) +
	       ((data[bitmap_id.length + 5] & 0x000000ff) << 16) +
	       ((data[bitmap_id.length + 6] & 0x000000ff) << 8) +
	       (data[bitmap_id.length + 7] & 0x000000ff);
   }
   
   public byte[] toBytes(){
	   byte[] byteArray = new byte[CELLHEADER.SIZE];

	   for (int i = 0; i < this.bitmap_id.length; i++)
		byteArray[i] = bitmap_id[i];

	   byteArray[this.bitmap_id.length] = this.width;
	   byteArray[this.bitmap_id.length + 1] = this.height;
	   byteArray[this.bitmap_id.length + 2] = (byte) ((this.length & 0x0000ff00) >> 8);
	   byteArray[this.bitmap_id.length + 3] = (byte) (this.length & 0x000000ff);
	   byteArray[this.bitmap_id.length + 4] = (byte) ((this.stamp & 0xff000000) >> 24);
	   byteArray[this.bitmap_id.length + 5] = (byte) ((this.stamp & 0x00ff0000) >> 16);
	   byteArray[this.bitmap_id.length + 6] = (byte) ((this.stamp & 0x0000ff00) >> 8);
	   byteArray[this.bitmap_id.length + 7] = (byte) (this.stamp & 0x000000ff);

	   return byteArray;
   }
}
