/* RasterOp.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:20 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Copyright (C) 2011-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
 * 
 * This program is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Purpose: Set of operations used in displaying raster graphics
 */
// Created on 01-Jul-2003

package net.propero.rdp;

import java.awt.Graphics;
import java.awt.image.BufferedImage;

import org.apache.log4j.Logger;

public class RasterOp {
	static Logger logger = Logger.getLogger(RdesktopCanvas.class);
	
	private Options opt = null;
	
	public RasterOp(Options opt_) {
		this.opt = opt_;
	}

	private void ropInvert(WrappedImage biDst, BufferedImage dest, int width, int x, int y, int cx, int cy, int Bpp) {
		int mask = this.opt.bpp_mask;

		for (int i = 0; i < cy; i++) {
			for (int j = 0; j < cx; j++) {
                if(biDst != null){
                    int c = biDst.getRGB(x+j,y+i);
                    biDst.setRGB(x+j,y+i,~c & mask);
                }else {
                	int c = dest.getRGB(x+j,y+i);
                	dest.setRGB(x+j, y+i, (~c) & mask);
                }
			}
		}
	}

	private void ropSet(WrappedImage biDst, int width, int x, int y, int cx, int cy,
			int Bpp) {
        
        int mask = this.opt.bpp_mask;
        
            for(int i = x; i < x+cx; i++){
                for(int j = y; j < y+cy; j++)
                    biDst.setRGB(i,j,mask);
            }

    }

    /**
     * Perform an operation on a rectangular area of a WrappedImage, using an integer array of colour values as
     * source if necessary
     * @param opcode Code defining operation to perform
     * @param biDst Destination image for operation
     * @param dstwidth Width of destination image
     * @param x X-offset of destination area within destination image
     * @param y Y-offset of destination area within destination image
     * @param cx Width of destination area
     * @param cy Height of destination area
     * @param src Source data, represented as an array of integer pixel values
     * @param srcwidth Width of source data
     * @param srcx X-offset of source area within source data
     * @param srcy Y-offset of source area within source data
     */
	public void do_array(int opcode, WrappedImage biDst, int dstwidth, int x, int y,
			int cx, int cy, BufferedImage src, int srcwidth, int srcx, int srcy) {
		int Bpp = this.opt.Bpp;
        //int[] dst = null;
		// System.out.println("do_array: opcode = 0x" +
		// Integer.toHexString(opcode) );
		Graphics g = biDst.getGraphics();
		switch (opcode) {
		case 0x0:
			g.clearRect(x, y, cx, cy);
			break;
		case 0x1:
			ropNor(biDst, dstwidth, x, y, cx, cy, src, srcwidth, srcx, srcy, Bpp);
			break;
		case 0x2:
			ropAndInverted(biDst, dstwidth, x, y, cx, cy, src, srcwidth, srcx,
					srcy, Bpp);
			break;
		case 0x3: // CopyInverted
			ropInvert(biDst, src, srcwidth, srcx, srcy, cx, cy, Bpp);
			if (src == null) {// special case - copy to self
				g.copyArea(srcx,srcy,cx,cy,x-srcx,y-srcy);
			}
			else {
				g.drawImage(src, x, y, x+cx, y+cy, srcx, srcy, srcx+cx, srcy+cy, null);
			}
			break;
		case 0x4: // AndReverse
			ropInvert(biDst, null, dstwidth, x, y, cx, cy, Bpp);
			ropAnd(biDst, dstwidth, x, y, cx, cy, src, srcwidth, srcx, srcy, Bpp);
			break;
		case 0x5:
			ropInvert(biDst, null, dstwidth, x, y, cx, cy, Bpp);
			break;
		case 0x6:
			ropXor(biDst, dstwidth, x, y, cx, cy, src, srcwidth, srcx, srcy, Bpp);
			break;
		case 0x7:
			ropNand(biDst, dstwidth, x, y, cx, cy, src, srcwidth, srcx, srcy, Bpp);
			break;
		case 0x8:
			ropAnd(biDst, dstwidth, x, y, cx, cy, src, srcwidth, srcx, srcy, Bpp);
			break;
		case 0x9:
			ropEquiv(biDst, dstwidth, x, y, cx, cy, src, srcwidth, srcx, srcy,
					Bpp);
			break;
		case 0xa: // Noop
			break;
		case 0xb:
			ropOrInverted(biDst, dstwidth, x, y, cx, cy, src, srcwidth, srcx,
					srcy, Bpp);
			break;
		case 0xc:
			if (src == null) {// special case - copy to self
				g.copyArea(srcx,srcy,cx,cy,x-srcx,y-srcy);
			}
			else {
				g.drawImage(src, x, y, x+cx, y+cy, srcx, srcy, srcx+cx, srcy+cy, null);
			}
			break;
		case 0xd: // OrReverse
			ropInvert(biDst, null, dstwidth, x, y, cx, cy, Bpp);
			ropOr(biDst, dstwidth, x, y, cx, cy, src, srcwidth, srcx, srcy, Bpp);
			break;
		case 0xe:
			ropOr(biDst, dstwidth, x, y, cx, cy, src, srcwidth, srcx, srcy, Bpp);
			break;
		case 0xf:
			ropSet(biDst, dstwidth, x, y, cx, cy, Bpp);
			break;
		default:
			logger.warn("do_array unsupported opcode: " + opcode);
		// rop_array(opcode,dst,dstwidth,x,y,cx,cy,src,srcwidth,srcx,srcy);
		}
	}
    
    /**
     * Perform an operation on a single pixel in a WrappedImage
     * @param opcode Opcode defining operation to perform
     * @param dst Image on which to perform the operation
     * @param x X-coordinate of pixel to modify
     * @param y Y-coordinate of pixel to modify
     * @param color Colour to use in operation (unused for some operations)
     */
    public void do_pixel(int opcode, WrappedImage dst, int x, int y, int color) {
        int mask = this.opt.bpp_mask;
        
        if(dst == null) return;
        
        int c = dst.getRGB(x,y);
        
        switch (opcode) {
        case 0x0: dst.setRGB(x,y,0); break;
        case 0x1: dst.setRGB(x,y,(~(c | color)) & mask); break;
        case 0x2: dst.setRGB(x,y, c & ((~color) & mask)); break;
        case 0x3: dst.setRGB(x,y,(~color) & mask); break;
        case 0x4: dst.setRGB(x,y,(~c & color) * mask); break;
        case 0x5: dst.setRGB(x,y,(~c) & mask);  break;
        case 0x6: dst.setRGB(x,y, c ^ ((color) & mask)); break;
        case 0x7: dst.setRGB(x,y,(~c & color) & mask); break;
        case 0x8: dst.setRGB(x,y, c & ( color & mask )); break;
        case 0x9: dst.setRGB(x,y,c ^ ( ~color & mask) ); break;
        case 0xa: /* Noop */ break;
        case 0xb: dst.setRGB(x,y,c | ( ~color & mask )); break;
        case 0xc: dst.setRGB(x,y,color); break;
        case 0xd: dst.setRGB(x,y,(~c | color) & mask); break;
        case 0xe: dst.setRGB(x,y, c | ( color & mask)); break;
        case 0xf: dst.setRGB(x,y,mask); break;
        default:
            logger.warn("do_byte unsupported opcode: " + opcode);
        }
    }

	private void ropNor(WrappedImage biDst, int dstwidth, int x, int y, int cx, int cy,
			BufferedImage src, int srcwidth, int srcx, int srcy, int Bpp) {
		// opcode 0x1
		int mask = this.opt.bpp_mask;

		for (int row = 0; row < cy; row++) {
			for (int col = 0; col < cx; col++) {
				int c = biDst.getRGB(x+col,y+row);
				biDst.setRGB(x+col,y+row,(~(c | src.getRGB(srcx+col, srcy+row))) & mask);
			}
		}
	}

	private void ropAndInverted(WrappedImage biDst, int dstwidth, int x, int y, int cx,
			int cy, BufferedImage src, int srcwidth, int srcx, int srcy, int Bpp) {
		// opcode 0x2
		int mask = this.opt.bpp_mask;
		for (int row = 0; row < cy; row++) {
			for (int col = 0; col < cx; col++) {
				int c = biDst.getRGB(x+col,y+row);
				biDst.setRGB(x+col,y+row,c & ((~src.getRGB(srcx+col, srcy+row)) & mask));
			}
		}
	}

	private void ropXor(WrappedImage biDst, int dstwidth, int x, int y, int cx, int cy,
			BufferedImage src, int srcwidth, int srcx, int srcy, int Bpp) {
		// opcode 0x6
		int mask = this.opt.bpp_mask;

		cx = Math.min(cx, biDst.getWidth());
		cy = Math.min(cy, biDst.getHeight());
		for (int row = 0; row < cy; row++) {
			for (int col = 0; col < cx; col++) {
				int c = biDst.getRGB(x+col,y+row);
				biDst.setRGB(x+col,y+row, c ^ ((src.getRGB(srcx+col, srcy+row)) & mask));
			}
		}
	}

	private void ropNand(WrappedImage biDst, int dstwidth, int x, int y, int cx, int cy,
			BufferedImage src, int srcwidth, int srcx, int srcy, int Bpp) {
		// opcode 0x7
		int mask = this.opt.bpp_mask;
		for (int row = 0; row < cy; row++) {
			for (int col = 0; col < cx; col++) {
				int c = biDst.getRGB(x+col,y+row);
				biDst.setRGB(x+col,y+row, (~(c & src.getRGB(srcx+col, srcy+row))) & mask);
			}
		}
	}

	private void ropAnd(WrappedImage biDst, int dstwidth, int x, int y, int cx, int cy,
			BufferedImage src, int srcwidth, int srcx, int srcy, int Bpp) {
		// opcode 0x8
		int mask = this.opt.bpp_mask;
		for (int row = 0; row < cy; row++) {
			for (int col = 0; col < cx; col++) {
				int c = biDst.getRGB(x+col,y+row);
				biDst.setRGB(x+col,y+row, c & ((src.getRGB(srcx+col, srcy+row)) & mask));
			}
		}
	}

	private void ropEquiv(WrappedImage biDst, int dstwidth, int x, int y, int cx,
			int cy, BufferedImage src, int srcwidth, int srcx, int srcy, int Bpp) {
		// opcode 0x9
		int mask = this.opt.bpp_mask;
		for (int row = 0; row < cy; row++) {
			for (int col = 0; col < cx; col++) {
				int c = biDst.getRGB(x+col,y+row);
				biDst.setRGB(x+col,y+row, c ^ ((~src.getRGB(srcx+col, srcy+row)) & mask));
			}
		}
	}

	private void ropOrInverted(WrappedImage biDst, int dstwidth, int x, int y, int cx,
			int cy, BufferedImage src, int srcwidth, int srcx, int srcy, int Bpp) {
		// opcode 0xb
		int mask = this.opt.bpp_mask;
		for (int row = 0; row < cy; row++) {
			for (int col = 0; col < cx; col++) {
				int c = biDst.getRGB(x+col,y+row);
				biDst.setRGB(x+col,y+row, c | ((~src.getRGB(srcx+col, srcy+row)) & mask));
			}
		}
	}

	private void ropOr(WrappedImage biDst, int dstwidth, int x, int y, int cx, int cy,
			BufferedImage src, int srcwidth, int srcx, int srcy, int Bpp) {
		// opcode 0xe
		int mask = this.opt.bpp_mask;
		for (int row = 0; row < cy; row++) {
			for (int col = 0; col < cx; col++) {
				int c = biDst.getRGB(x+col,y+row);
				biDst.setRGB(x+col,y+row, c | (src.getRGB(srcx+col, srcy+row) & mask));
			}
		}
	}
}
