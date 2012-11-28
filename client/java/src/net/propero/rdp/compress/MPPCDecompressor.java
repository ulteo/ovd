/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010, 2012
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
 */

package net.propero.rdp.compress;

import java.util.Arrays;
import net.propero.rdp.RdpPacket_Localised;
import org.apache.log4j.Logger;

public class MPPCDecompressor extends RdpDecompressor {
	private static final int MPPC_BIG = 0x01;
	private static final int MPPC_COMPRESSED = 0x20;
	private static final int MPPC_RESET = 0x40;
	private static final int MPPC_FLUSH = 0x80;
	private static final int MPPC_8K_DICT_SIZE = 8192;
	private static final int MPPC_64K_DICT_SIZE = 65536;
	
	private static final byte[] RDP60_HuffLengthLEC = {
		0x6, 0x6, 0x6, 0x7, 0x7, 0x7, 0x7, 0x7,
		0x7, 0x7, 0x7, 0x8, 0x8, 0x8, 0x8, 0x8,
		0x8, 0x8, 0x9, 0x8, 0x9, 0x9, 0x9, 0x9,
		0x8, 0x8, 0x9, 0x9, 0x9, 0x9, 0x9, 0x9,
		0x8, 0x9, 0x9, 0xa, 0x9, 0x9, 0x9, 0x9,
		0x9, 0x9, 0x9, 0xa, 0x9, 0xa, 0xa, 0xa,
		0x9, 0x9, 0xa, 0x9, 0xa, 0x9, 0xa, 0x9,
		0x9, 0x9, 0xa, 0xa, 0x9, 0xa, 0x9, 0x9,
		0x8, 0x9, 0x9, 0x9, 0x9, 0xa, 0xa, 0xa,
		0x9, 0x9, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0x9, 0x9, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0xa, 0x9, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0x8, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0x9, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0x9, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0x9,
		0x7, 0x9, 0x9, 0xa, 0x9, 0xa, 0xa, 0xa,
		0x9, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0x9, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0xa, 0xa, 0xa, 0xd, 0xa, 0xa, 0xa, 0xa,
		0xa, 0xa, 0xb, 0xa, 0xa, 0xa, 0xa, 0xa,
		0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0x9, 0xa, 0xa, 0xa, 0xa, 0xa, 0x9, 0xa,
		0xa, 0xa, 0xa, 0xa, 0x9, 0xa, 0xa, 0xa,
		0x9, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0x9, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0xa,
		0xa, 0xa, 0xa, 0xa, 0xa, 0xa, 0x9, 0xa,
		0x8, 0x9, 0x9, 0xa, 0x9, 0xa, 0xa, 0xa,
		0x9, 0xa, 0xa, 0xa, 0x9, 0x9, 0x8, 0x7,
		0xd, 0xd, 0x7, 0x7, 0xa, 0x7, 0x7, 0x6,
		0x6, 0x6, 0x6, 0x5, 0x6, 0x6, 0x6, 0x5,
		0x6, 0x5, 0x6, 0x6, 0x6, 0x6, 0x6, 0x6,
		0x6, 0x6, 0x6, 0x6, 0x6, 0x6, 0x6, 0x6,
		0x8, 0x5, 0x6, 0x7, 0x7
	};
	
	private static final int[] RDP60_HuffIndexLEC = {
		0x007b, 0xff1f, 0xff0d, 0xfe27, 0xfe00, 0xff05, 0xff17, 0xfe68,
		0x00c5, 0xfe07, 0xff13, 0xfec0, 0xff08, 0xfe18, 0xff1b, 0xfeb3,
		0xfe03, 0x00a2, 0xfe42, 0xff10, 0xfe0b, 0xfe02, 0xfe91, 0xff19,
		0xfe80, 0x00e9, 0xfe3a, 0xff15, 0xfe12, 0x0057, 0xfed7, 0xff1d,
		0xff0e, 0xfe35, 0xfe69, 0xff22, 0xff18, 0xfe7a, 0xfe01, 0xff23,
		0xff14, 0xfef4, 0xfeb4, 0xfe09, 0xff1c, 0xfec4, 0xff09, 0xfe60,
		0xfe70, 0xff12, 0xfe05, 0xfe92, 0xfea1, 0xff1a, 0xfe0f, 0xff07,
		0xfe56, 0xff16, 0xff02, 0xfed8, 0xfee8, 0xff1e, 0xfe1d, 0x003b,
		0xffff, 0xff06, 0xffff, 0xfe71, 0xfe89, 0xffff, 0xffff, 0xfe2c,
		0xfe2b, 0xfe20, 0xffff, 0xfebb, 0xfecf, 0xfe08, 0xffff, 0xfee0,
		0xfe0d, 0xffff, 0xfe99, 0xffff, 0xfe04, 0xfeaa, 0xfe49, 0xffff,
		0xfe17, 0xfe61, 0xfedf, 0xffff, 0xfeff, 0xfef6, 0xfe4c, 0xffff,
		0xffff, 0xfe87, 0xffff, 0xff24, 0xffff, 0xfe3c, 0xfe72, 0xffff,
		0xffff, 0xfece, 0xffff, 0xfefe, 0xffff, 0xfe23, 0xfebc, 0xfe0a,
		0xfea9, 0xffff, 0xfe11, 0xffff, 0xfe82, 0xffff, 0xfe06, 0xfe9a,
		0xfef5, 0xffff, 0xfe22, 0xfe4d, 0xfe5f, 0xffff, 0xff03, 0xfee1,
		0xffff, 0xfeca, 0xfecc, 0xffff, 0xfe19, 0xffff, 0xfeb7, 0xffff,
		0xffff, 0xfe83, 0xfe29, 0xffff, 0xffff, 0xffff, 0xfe6c, 0xffff,
		0xfeed, 0xffff, 0xffff, 0xfe46, 0xfe5c, 0xfe15, 0xffff, 0xfedb,
		0xfea6, 0xffff, 0xffff, 0xfe44, 0xffff, 0xfe0c, 0xffff, 0xfe95,
		0xfefc, 0xffff, 0xffff, 0xfeb8, 0x16c9, 0xffff, 0xfef0, 0xffff,
		0xfe38, 0xffff, 0xffff, 0xfe6d, 0xfe7e, 0xffff, 0xffff, 0xffff,
		0xffff, 0xfe5b, 0xfedc, 0xffff, 0xffff, 0xfeec, 0xfe47, 0xfe1f,
		0xffff, 0xfe7f, 0xfe96, 0xffff, 0xffff, 0xfea5, 0xffff, 0xfe10,
		0xfe40, 0xfe32, 0xfebf, 0xffff, 0xffff, 0xfed4, 0xfef1, 0xffff,
		0xffff, 0xffff, 0xfe75, 0xffff, 0xffff, 0xfe8d, 0xfe31, 0xffff,
		0xfe65, 0xfe1b, 0xffff, 0xfee4, 0xfefb, 0xffff, 0xffff, 0xfe52,
		0xffff, 0xfe0e, 0xffff, 0xfe9d, 0xfeaf, 0xffff, 0xffff, 0xfe51,
		0xfed3, 0xffff, 0xff20, 0xffff, 0xfe2f, 0xffff, 0xffff, 0xfec1,
		0xfe8c, 0xffff, 0xffff, 0xffff, 0xfe3f, 0xffff, 0xffff, 0xfe76,
		0xffff, 0xfefa, 0xfe53, 0xfe25, 0xffff, 0xfe64, 0xfee5, 0xffff,
		0xffff, 0xfeae, 0xffff, 0xfe13, 0xffff, 0xfe88, 0xfe9e, 0xffff,
		0xfe43, 0xffff, 0xffff, 0xfea4, 0xfe93, 0xffff, 0xffff, 0xffff,
		0xfe3d, 0xffff, 0xffff, 0xfeeb, 0xfed9, 0xffff, 0xfe14, 0xfe5a,
		0xffff, 0xfe28, 0xfe7d, 0xffff, 0xffff, 0xfe6a, 0xffff, 0xffff,
		0xff01, 0xfec6, 0xfec8, 0xffff, 0xffff, 0xfeb5, 0xffff, 0xffff,
		0xffff, 0xfe94, 0xfe78, 0xffff, 0xffff, 0xffff, 0xfea3, 0xffff,
		0xffff, 0xfeda, 0xfe58, 0xffff, 0xfe1e, 0xfe45, 0xfeea, 0xffff,
		0xfe6b, 0xffff, 0xffff, 0xfe37, 0xffff, 0xffff, 0xffff, 0xfe7c,
		0xfeb6, 0xffff, 0xffff, 0xfef8, 0xffff, 0xffff, 0xffff, 0xfec7,
		0xfe9b, 0xffff, 0xffff, 0xffff, 0xfe50, 0xffff, 0xffff, 0xfead,
		0xfee2, 0xffff, 0xfe1a, 0xfe63, 0xfe4e, 0xffff, 0xffff, 0xfef9,
		0xffff, 0xfe73, 0xffff, 0xffff, 0xffff, 0xfe30, 0xfe8b, 0xffff,
		0xffff, 0xfebd, 0xfe2e, 0x0100, 0xffff, 0xfeee, 0xfed2, 0xffff,
		0xffff, 0xffff, 0xfeac, 0xffff, 0xffff, 0xfe9c, 0xfe84, 0xffff,
		0xfe24, 0xfe4f, 0xfef7, 0xffff, 0xffff, 0xfee3, 0xfe62, 0xffff,
		0xffff, 0xffff, 0xffff, 0xfe8a, 0xfe74, 0xffff, 0xffff, 0xfe3e,
		0xffff, 0xffff, 0xffff, 0xfed1, 0xfebe, 0xffff, 0xffff, 0xfe2d,
		0xffff, 0xfe4a, 0xfef3, 0xffff, 0xffff, 0xfedd, 0xfe5e, 0xfe16,
		0xffff, 0xfe48, 0xfea8, 0xffff, 0xfeab, 0xfe97, 0xffff, 0xffff,
		0xfed0, 0xffff, 0xffff, 0xfecd, 0xfeb9, 0xffff, 0xffff, 0xffff,
		0xfe2a, 0xffff, 0xffff, 0xfe86, 0xfe6e, 0xffff, 0xffff, 0xffff,
		0xfede, 0xffff, 0xffff, 0xfe5d, 0xfe4b, 0xfe21, 0xffff, 0xfeef,
		0xfe98, 0xffff, 0xffff, 0xfe81, 0xffff, 0xffff, 0xffff, 0xfea7,
		0xffff, 0xfeba, 0xfefd, 0xffff, 0xffff, 0xffff, 0xfecb, 0xffff,
		0xffff, 0xfe6f, 0xfe39, 0xffff, 0xffff, 0xffff, 0xfe85, 0xffff,
		0x010c, 0xfee6, 0xfe67, 0xfe1c, 0xffff, 0xfe54, 0xfeb2, 0xffff,
		0xffff, 0xfe9f, 0xffff, 0xffff, 0xffff, 0xfe59, 0xfeb1, 0xffff,
		0xfec2, 0xffff, 0xffff, 0xfe36, 0xfef2, 0xffff, 0xffff, 0xfed6,
		0xfe77, 0xffff, 0xffff, 0xffff, 0xfe33, 0xffff, 0xffff, 0xfe8f,
		0xfe55, 0xfe26, 0x010a, 0xff04, 0xfee7, 0xffff, 0x0121, 0xfe66,
		0xffff, 0xffff, 0xffff, 0xfeb0, 0xfea0, 0xffff, 0x010f, 0xfe90,
		0xffff, 0xffff, 0xfed5, 0xffff, 0xffff, 0xfec3, 0xfe34, 0xffff,
		0xffff, 0xffff, 0xfe8e, 0xffff, 0x0111, 0xfe79, 0xfe41, 0x010b
	};
	
	private static final int[] RDP60_HuffIndexLOM = {
		0xfe1, 0xfe0, 0xfe2, 0xfe8, 0xe, 0xfe5, 0xfe4, 0xfea,
		0xff1, 0xfe3, 0x15, 0xfe7, 0xfef, 0x46, 0xff0, 0xfed,
		0xfff, 0xff7, 0xffb, 0x19, 0xffd, 0xff4, 0x12c, 0xfeb,
		0xffe, 0xff6, 0xffa, 0x89, 0xffc, 0xff3, 0xff8, 0xff2
	};
	
	private static final byte[] RDP60_LOMHTab = {
		0, 4, 10, 19
	};
	
	private static final int[] RDP60_LECHTab = {
		511, 0, 508, 448, 494, 347, 486, 482
	};
	
	private static final byte[] RDP60_CopyOffsetBitsLUT = {
		0, 0, 0, 0, 1, 1, 2, 2,
		3, 3, 4, 4, 5, 5, 6, 6,
		7, 7, 8, 8, 9, 9, 10, 10,
		11, 11, 12, 12, 13, 13, 14, 14,
		15
	};
		
	private static final int[] RDP60_CopyOffsetBaseLUT = {
		1, 2, 3, 4, 5, 7, 9, 13,
		17, 25, 33, 49, 65, 97, 129, 193,
		257, 385, 513, 769, 1025, 1537, 2049, 3073,
		4097, 6145, 8193, 12289, 16385, 24577, 32769, 49153,
		65537
	};
	
	private static final byte[] RDP60_LengthOfMatchCode = {
		0x4, 0x2, 0x3, 0x4, 0x3, 0x4, 0x4, 0x5,
		0x4, 0x5, 0x5, 0x6, 0x6, 0x7, 0x7, 0x8,
		0x7, 0x8, 0x8, 0x9, 0x9, 0x8, 0x9, 0x9,
		0x9, 0x9, 0x9, 0x9, 0x9, 0x9, 0x9, 0x9
	};
	
	private static final byte[] LOMBitsLUT = {
		0, 0, 0, 0, 0, 0, 0, 0,
		1, 1, 1, 1, 2, 2, 2, 2,
		3, 3, 3, 3, 4, 4, 4, 4,
		6, 6, 8, 8, 14, 14
	};

	private static final int[] LOMBaseLUT = {
		2, 3, 4, 5, 6, 7, 8, 9,
		10, 12, 14, 16, 18, 22, 26, 30,
		34, 42, 50, 58, 66, 82, 98, 114,
		130, 194, 258, 514, 2, 2
	};	
	
	protected static Logger logger = Logger.getLogger(MPPCDecompressor.class);

	private MPPCType type = null;
	private int roff;
	private byte[] hist;
	private String errorMsg = new String("Error while decompressing packet");

	private int dataOffset;
	private int walker_len;
	private int walker;
	private int match_off;
	
	private int[] copyOffsetCache = null;

	public MPPCDecompressor(MPPCType type) throws RdpCompressionException {
		int dictSize;
		
		switch (type) {
			case mppc4:
				dictSize = MPPC_8K_DICT_SIZE;
				break;
			case mppc5:
				dictSize = MPPC_64K_DICT_SIZE;
				break;
			case mppc6:
				dictSize = MPPC_64K_DICT_SIZE;
				this.copyOffsetCache = new int[4];
				break;
			default:
				throw new RdpCompressionException("Bad MPPC compression type: "+type);
		}

		this.hist = new byte[dictSize];
		this.roff = 0;
	}

	public RdpPacket_Localised decompress(RdpPacket_Localised compressedPacket, int length, int compressionType) throws RdpCompressionException {
		MPPCType ctype = null;
		try {
			ctype = MPPCType.values()[(compressionType & 0xf)];
		} catch (IndexOutOfBoundsException ex) {
			throw new RdpCompressionException("Unknown compression type: "+ex.getMessage());
		}
		
		if (this.type != ctype) {
			if (this.type == null) {
				this.type = ctype;
				logger.info("Using packet compression type: "+this.type);
			}
			else {
				throw new RdpCompressionException("Compression type has changed from "+this.type+" to "+ctype);
			}
		}
		
		byte[] compressedDatas = new byte[length];
		compressedPacket.copyToByteArray(compressedDatas, 0, compressedPacket.getPosition(), length);

		byte[] decompressedDatas;

		switch (ctype) {
			case mppc4:
			case mppc5:
				decompressedDatas = this.expand(compressedDatas, length, compressionType);
				break;
			case mppc6:
				decompressedDatas = this.RDP60_decompress(compressedDatas, length, compressionType);
				break;
			default:
				throw new RdpCompressionException("Failed to decompress packet: Compression type "+String.format("0x%x", ctype.value())+" is not supported.");
		}
		
		RdpPacket_Localised decompressedPacket = new RdpPacket_Localised(decompressedDatas.length);
		decompressedPacket.copyFromByteArray(decompressedDatas, 0, 0, decompressedDatas.length);
		decompressedPacket.setPosition(0);
		decompressedPacket.markEnd(decompressedPacket.size());
		decompressedPacket.setStart(decompressedPacket.getPosition());

		return decompressedPacket;
	}

	private long unsignedInt(int signedInt) {
		long unsignedInt = (long) signedInt;
		unsignedInt = (unsignedInt << 32) >>> 32;
		return unsignedInt;
	}

	private byte getByteFromWalker(boolean unsigned) {
		byte b;
		if (unsigned)
			b = (byte) (unsignedInt(this.walker) >> 24 | 0x80);
		else
			b = (byte) (unsignedInt(this.walker) >> 24);
		this.walker <<= 8;
		this.walker_len -= 8;

		return b;
	}

	private int getByte(byte[] datas, int offset) {
		return (datas[this.dataOffset++] & 0xff) << (24 -offset);
	}

	private void offsetDecoding(byte[] compressedDatas, int clen, int walkerShift, int matchShift, int offset, int length) throws RdpCompressionException {
		for (; walker_len < length; walker_len += 8) {
			if (dataOffset >= clen) {
				throw new RdpCompressionException(this.errorMsg);
			}
			this.walker |= (compressedDatas[dataOffset++] & 0xff) << (24 - walker_len);
		}

		this.walker <<= walkerShift;
		this.match_off = ((int) (unsignedInt(this.walker) >> matchShift)) + offset;
		this.walker <<= (length - walkerShift);
		walker_len -= length;
	}

	private byte[] expand(byte[] compressedDatas, int clen, int ctype) throws RdpCompressionException {
		int k;
		this.dataOffset = 0;
		this.walker_len = 0;
		int match_len, match_bits;
		int next_offset, old_offset;
		boolean big = ((ctype & MPPC_BIG) != 0);

		int data_roff, data_rlen;

		if ((ctype & MPPC_COMPRESSED) == 0) {
			data_roff = 0;
			data_rlen = clen;
			return extractBytes(data_roff, data_rlen);
		}

		if ((ctype & MPPC_RESET) != 0) {
			this.roff = 0;
		}

		if ((ctype & MPPC_FLUSH) != 0) {
			for (int j = 0; j < this.hist.length; j++)
				this.hist[j] = 0;
			this.roff = 0;
		}

		data_roff = 0;
		data_rlen = 0;

		this.walker = this.roff;

		next_offset = this.walker;
		old_offset = next_offset;
		data_roff = old_offset;

		if (clen == 0)
			throw new RdpCompressionException(this.errorMsg+": compression length is 0");
		clen += this.dataOffset;

		do {
			if (this.walker_len == 0) {
				if (this.dataOffset >= clen)
					break;
				this.walker = this.getByte(compressedDatas, 0);
				this.walker_len = 8;
			}
			if (this.walker >= 0) {
				if (this.walker_len < 8) {
					if (this.dataOffset >= clen) {
						if (this.walker != 0) {
							throw new RdpCompressionException(this.errorMsg);
						}
						break;
					}
					this.walker |= this.getByte(compressedDatas, this.walker_len);
					this.walker_len += 8;
				}
				if (next_offset >= this.hist.length) {
					throw new RdpCompressionException(this.errorMsg);
				}
				this.hist[next_offset++] = getByteFromWalker(false);
				continue;
			}
			this.walker <<= 1;

			/* Fetch next 8-bits */
			if (--this.walker_len == 0) {
				if (this.dataOffset >= clen) {
					throw new RdpCompressionException(this.errorMsg);
				}
				this.walker = this.getByte(compressedDatas, 0);
				this.walker_len = 8;
			}

			/* Literal decoding */
			if (this.walker >= 0) {
				if (this.walker_len < 8) {
					if (dataOffset >= clen) {
						throw new RdpCompressionException(this.errorMsg);
					}
					this.walker |= this.getByte(compressedDatas, this.walker_len);
					this.walker_len += 8;
				}
				if (next_offset >= this.hist.length) {
					throw new RdpCompressionException(this.errorMsg);
				}
				this.hist[next_offset++] = getByteFromWalker(true);
				continue;
			}

			/* Decode offset */
			/* Length pair */
			this.walker <<= 1;
			if (--this.walker_len < (big ? 3 : 2)) {
				if (this.dataOffset >= clen) {
					throw new RdpCompressionException(this.errorMsg);
				}
				this.walker |= this.getByte(compressedDatas, this.walker_len);
				this.walker_len += 8;
			}

			if (big) {
				/* offset decoding where offset len is:
				 * -63: 11111 followed by the lower 6 bits of the value
				 * 64-319: 11110 followed by the lower 8 bits of the value ( value - 64 )
				 * 320-2367: 1110 followed by lower 11 bits of the value ( value - 320 )
				 * 2368-65535: 110 followed by lower 16 bits of the value ( value - 2368 )
				 */
				switch ((int) (unsignedInt(this.walker) >> 29)) {
					case 7:	/* - 63 */
						this.offsetDecoding(compressedDatas, clen, 3, 26, 0, 9);
						break;

					case 6:	/* 64 - 319 */
						this.offsetDecoding(compressedDatas, clen, 3, 24, 64, 11);
						break;

					case 5:
					case 4:	/* 320 - 2367 */
						this.offsetDecoding(compressedDatas, clen, 2, 21, 320, 13);
						break;

					default:	/* 2368 - 65535 */
						this.offsetDecoding(compressedDatas, clen, 1, 16, 2368, 17);
						break;
				}
			}
			else {
				/* offset decoding where offset len is:
				 * -63: 1111 followed by the lower 6 bits of the value
				 * 64-319: 1110 followed by the lower 8 bits of the value ( value - 64 )
				 * 320-8191: 110 followed by the lower 13 bits of the value ( value - 320 )
				 */
				switch ((int) (unsignedInt(this.walker) >> 30))
				{
					case 3:	/* - 63 */
						this.offsetDecoding(compressedDatas, clen, 2, 26, 0, 8);
						break;

					case 2:	/* 64 - 319 */
						this.offsetDecoding(compressedDatas, clen, 2, 24, 64, 10);
						break;

					default: /* 320 - 8191 */
						this.offsetDecoding(compressedDatas, clen, 0, 18, 320, 14);
						break;
				}
			}
			if (this.walker_len == 0) {
				if (this.dataOffset >= clen) {
					throw new RdpCompressionException(this.errorMsg);
				}
				this.walker = this.getByte(compressedDatas, 0);
				this.walker_len = 8;
			}

			/* Decode length of match */
			match_len = 0;
			if (this.walker >= 0) {
				/* Special case - length of 3 is in bit 0 */
				match_len = 3;
				this.walker <<= 1;
				this.walker_len--;
			}
			else {
				/* this is how it works len of:
				 * 4-7: 10 followed by 2 bits of the value
				 * 8-15: 110 followed by 3 bits of the value
				 * 16-31: 1110 followed by 4 bits of the value
				 * 32-63: .... and so forth
				 * 64-127:
				 * 128-255:
				 * 256-511:
				 * 512-1023:
				 * 1024-2047:
				 * 2048-4095:
				 * 4096-8191:
				 *
				 * dataOffset.e. 4097 is encoded as: 111111111110 000000000001
				 * meaning 4096 + 1...
				 */
				match_bits = big ? 14 : 11; /* 11 or 14 bits of value at most */
				do {
					this.walker <<= 1;
					if (--this.walker_len == 0) {
						if (this.dataOffset >= clen) {
							throw new RdpCompressionException(this.errorMsg);
						}
						this.walker = this.getByte(compressedDatas, 0);
						this.walker_len = 8;
					}
					if (this.walker >= 0) {
						break;
					}
					if (--match_bits == 0) {
						throw new RdpCompressionException(this.errorMsg);
					}
				} while (true);
				match_len = (big ? 16 : 13) - match_bits;
				this.walker <<= 1;
				if (--this.walker_len < match_len) {
					for (; this.walker_len < match_len; this.walker_len += 8) {
						if (this.dataOffset >= clen) {
							throw new RdpCompressionException(this.errorMsg);
						}
						this.walker |= this.getByte(compressedDatas, this.walker_len);
					}
				}

				match_bits = match_len;
				match_len = ((this.walker >> (32 - match_bits)) & (~(-1 << match_bits))) | (1 << match_bits);
				this.walker <<= match_bits;
				this.walker_len -= match_bits;
			}
			if (next_offset + match_len >= this.hist.length) {
				throw new RdpCompressionException(this.errorMsg);
			}

			/* Memory areas can overlap - meaning we can't use memXXX functions */
			k = (next_offset - this.match_off) & (big ? (MPPC_64K_DICT_SIZE - 1) : (MPPC_8K_DICT_SIZE - 1));
			do {
				this.hist[next_offset++] = this.hist[k++];
			} while (--match_len != 0);
		} while (true);

		/* Store history offset */
		this.roff = next_offset;

		data_roff = old_offset;
		data_rlen = next_offset - old_offset;

		return extractBytes(data_roff, data_rlen);
	}

	private byte[] extractBytes(int data_roff, int data_rlen) {
		byte[] decompressedDatas = new byte[data_rlen];
		for (int j = 0; j < decompressedDatas.length; j++)
			decompressedDatas[j] = this.hist[data_roff + j];

		return decompressedDatas;
	}
	
	private static int RDP60_transposebits(int x) {
		x = ((x & 0x55555555) << 1) | ((x >> 1) & 0x55555555);
		x = ((x & 0x33333333) << 2) | ((x >> 2) & 0x33333333);
		x = ((x & 0x0f0f0f0f) << 4) | ((x >> 4) & 0x0f0f0f0f);
		if((x >> 8) == 0) {
			return x;
		}
		
		x = ((x & 0x00ff00ff) << 8) | ((x >> 8) & 0x00ff00ff);
		if((x >> 16) == 0) {
			return x;
		}
		
		x = ((x & 0x0000ffff) << 16) | ((x >> 16) & 0x0000ffff);
		
		return x;
	}
	
	private static int RDP60_LEChash(int key) {
		return ((key & 0x1ff) ^ (key  >> 9) ^ (key >> 4) ^ (key >> 7));
	}

	private static int RDP60_miniLEChash(int key) {
		int h = ((((key >> 8) ^ (key & 0xff)) >> 2) & 0xf);
		if ((key >> 9) != 0) {
			h = (~h) & 0x0000ffff;
		}
		
		return (h % 12);
	}

	private static int RDP60_getLECindex(int huff) {
		int h = RDP60_HuffIndexLEC[RDP60_LEChash(huff)];
		
		if (((h ^ huff) >> 9) != 0) {
			return h & 0x1ff;
		}
		
		return RDP60_HuffIndexLEC[RDP60_LECHTab[RDP60_miniLEChash(huff)]];
	}
	
	private static int RDP60_LOMhash(int key) {
		return ((key & 0x1f) ^ (key  >> 5) ^ (key >> 9));
	}

	private static byte RDP60_miniLOMhash(int key) {
		byte h = (byte) ((key >> 4) & 0xf);
		
		return (byte) ((h ^ (h >> 2) ^ (h >> 3)) & 0x3);
	}
	
	private static int RDP60_getLOMindex(int huff) {
		int h = RDP60_HuffIndexLOM[RDP60_LOMhash(huff)];
		
		if (((h ^ huff) >> 5) != 0) {
			return h & 0x1f;
		}
		
		return RDP60_HuffIndexLOM[RDP60_LOMHTab[RDP60_miniLOMhash(huff)]];
	}
	
	private static void RDP60_copyOffsetCache_add(int[] copyOffsetCache, int copyOffset) {
		for (int i = 3; i > 0; i--) {
			copyOffsetCache[i] = copyOffsetCache[i - 1];
		}
		copyOffsetCache[0] = copyOffset;
	}
	
	private static void RDP60_copyOffsetCache_swap(int[] copyOffsetCache, int idx) {
		int buf = copyOffsetCache[0];
		copyOffsetCache[0] = copyOffsetCache[idx];
		copyOffsetCache[idx] = buf;
	}
	
	public byte[] RDP60_decompress(byte[] compressedDatas, int clen, int ctype) {
		int i, tmp = 0;
		int cptr = 0;
		int i32 = 0, d32 = 0;
		int bits_left = 0;
		int cur_byte = 0;
		int cur_bits_left = 0;
		int LUTIndex;
		int lom = 0;
		int historyOffset = this.roff;
		
		if ((ctype & MPPC_COMPRESSED) != MPPC_COMPRESSED) {
			logger.error("[decompress_rdp60] data is not compressed");
			return null;
		}

		if ((ctype & MPPC_RESET) != 0) {
			this.hist = Arrays.copyOfRange(this.hist, historyOffset - 32768, historyOffset + 32768);
			this.roff = historyOffset = 32768;
		}

		if ((ctype & MPPC_FLUSH) != 0) {
			historyOffset = 0;
			Arrays.fill(this.hist, (byte) 0);
			Arrays.fill(this.copyOffsetCache, 0);
			this.roff = 0;
		}
		
		while (cptr < compressedDatas.length) {
			i32 = compressedDatas[cptr++] & 0xff;
			d32  |= i32 << tmp;
			
			bits_left += 8;
			tmp += 8;
			if (tmp >= 32) {
				break;
			}
		}

		d32 = RDP60_transposebits(d32);

		if (cptr < compressedDatas.length) {
			cur_byte = RDP60_transposebits(compressedDatas[cptr++] & 0xff);
			cur_bits_left = 8;
		}
		else {
			cur_bits_left = 0;
		}

		// start uncompressing data
		while (bits_left >= 8) {
			// Decode Huffman Code for Literal/EOS/CopyOffset
			int copy_offset = 0;
			
			for (i = 0x5; i <= 0xd; i++) {
				if (i == 0xc) {
					continue;
				}
				
				i32 = RDP60_transposebits((d32 & (0xffffffff << (32 - i))));
				i32 = RDP60_getLECindex(i32);
				if(i == RDP60_HuffLengthLEC[i32]) {
					break;
				}
			}
			d32 <<= i;
			bits_left -= i;
			
			if (i32 < 256) {
				this.hist[historyOffset++] = (byte) i32;
			}
			else if (i32 > 256 && i32 < 289) {
				LUTIndex = i32 - 257;
				tmp = RDP60_CopyOffsetBitsLUT[LUTIndex];
				copy_offset = RDP60_CopyOffsetBaseLUT[LUTIndex] - 0x1;
				if (tmp != 0) {
					copy_offset += RDP60_transposebits(d32 & (0xffffffff << (32 - tmp)));
				}
				
				RDP60_copyOffsetCache_add(this.copyOffsetCache, copy_offset);
				
				d32 <<= tmp;
				bits_left -= tmp;
			}
			else if (i32 > 288 && i32 < 293) {
				LUTIndex = i32 - 289;
				copy_offset = this.copyOffsetCache[LUTIndex];
				if (LUTIndex != 0) {
					RDP60_copyOffsetCache_swap(this.copyOffsetCache, LUTIndex);
				}
			}
			else if (i32 == 256) {
				break;
			}

			tmp = 32 - bits_left;
			while (tmp != 0) {
				if (cur_bits_left < tmp) {
					// We have less bits than we need
					i32 = (cur_byte & 0xff) >> (8 - cur_bits_left);
					d32 |= i32 << ((32 - bits_left) - cur_bits_left);
					bits_left += cur_bits_left;
					tmp -= cur_bits_left;
					if (cptr < compressedDatas.length) {
						// more compressed data available
						cur_byte = RDP60_transposebits(compressedDatas[cptr++] & 0xff);
						cur_bits_left = 8;
					}
					else {
						// no more compressed data available
						tmp = 0;
						cur_bits_left = 0;
					}
				}
				else if (cur_bits_left > tmp) {
					// we have more bits than we need
					d32 |= (cur_byte & 0xff) >> (8 - tmp);
					cur_byte <<= tmp;
					cur_bits_left -= tmp;
					bits_left = 32;
					break;
				}
				else {
					// we have just the right amount of bits
					d32 |= (cur_byte & 0xff) >> (8 - tmp);
					bits_left = 32;
					if (cptr < compressedDatas.length) {
						cur_byte = RDP60_transposebits(compressedDatas[cptr++] & 0xff);
						cur_bits_left = 8;
					}
					else {
						cur_bits_left = 0;
					}
					break;
				}
			}

			if (copy_offset == 0) {
				continue;
			}

			for (i = 0x2; i <= 0x9; i++) {
				i32 = RDP60_transposebits((d32 & (0xffffffff << (32 - i))));
				i32 = RDP60_getLOMindex(i32);
				if (i == RDP60_LengthOfMatchCode[i32]) {
					break;
				}
			}
			d32 <<= i;
			bits_left -= i;
			tmp = LOMBitsLUT[i32];
			lom = LOMBaseLUT[i32];
			if(tmp != 0) {
				lom += RDP60_transposebits(d32 & (0xffffffff << (32 - tmp)));
			}
			d32 <<= tmp;
			bits_left -= tmp;

			// now that we have copy_offset and LoM, process them
			int src_ptr = historyOffset - copy_offset;
			tmp = (lom > copy_offset) ? copy_offset : lom;
			i32 = 0;
			if (src_ptr >= 0) {
				while (tmp > 0) {
					this.hist[historyOffset++] = this.hist[src_ptr++];
					tmp--;
				}
				while (lom > copy_offset) {
					i32 = ((i32 >= copy_offset)) ? 0 : i32;
					this.hist[historyOffset++] = this.hist[(src_ptr + i32++)];
					lom--;
				}
			}
			else {
				src_ptr = this.hist.length - 1 - (copy_offset - (historyOffset - 0));
				src_ptr++;
				while ((tmp != 0) && (src_ptr <= (this.hist.length -1))) {
					this.hist[historyOffset++] = this.hist[src_ptr++];
					tmp--;
				}
				src_ptr = 0;
				while (tmp > 0) {
					this.hist[historyOffset++] = this.hist[src_ptr++];
					tmp--;
				}
				while (lom > copy_offset) {
					i32 = ((i32 > copy_offset)) ? 0 : i32;
					this.hist[historyOffset++] = this.hist[(src_ptr + i32++)];
					lom--;
				}
			}

			// get more bits before we restart the loop
			// how may bits do we need to get?
			tmp = 32 - bits_left;
			while (tmp != 0) {
				if (cur_bits_left < tmp) {
					// we have less bits than we need
					i32 = (cur_byte & 0xff) >> (8 - cur_bits_left);
					d32 |= i32 << ((32 - bits_left) - cur_bits_left);
					bits_left += cur_bits_left;
					tmp -= cur_bits_left;
					if (cptr < compressedDatas.length) {
						// more compressed data available
						cur_byte = RDP60_transposebits(compressedDatas[cptr++] & 0xff);
						cur_bits_left = 8;
					}
					else {
						// no more compressed data available
						tmp = 0;
						cur_bits_left = 0;
					}
				}
				else if (cur_bits_left > tmp) {
					// we have more bits than we need
					d32 |= (cur_byte & 0xff) >> (8 - tmp);
					cur_byte <<= tmp;
					cur_bits_left -= tmp;
					bits_left = 32;
					break;
				}
				else {
					// we have just the right amount of bits
					d32 |= (cur_byte & 0xff) >> (8 - tmp);
					bits_left = 32;
					if (cptr < compressedDatas.length) {
						cur_byte = RDP60_transposebits(compressedDatas[cptr++] & 0xff);
						cur_bits_left = 8;
					}
					else {
						cur_bits_left = 0;
					}
					break;
				}
			}

		}

		int rlen;
		if ((ctype & MPPC_FLUSH) != 0) {
			rlen = historyOffset;
		}
		else {
			rlen = historyOffset - this.roff;
		}
		
		byte[] raw_data = this.extractBytes(this.roff, rlen);

		this.roff = historyOffset;
		
		return raw_data;
	}
}
