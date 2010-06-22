/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

import net.propero.rdp.RdpPacket_Localised;

public class MPPCDecompressor extends RdpDecompressor {
	private static final int MPPC_BIG = 0x01;
	private static final int MPPC_COMPRESSED = 0x20;
	private static final int MPPC_RESET = 0x40;
	private static final int MPPC_FLUSH = 0x80;
	private static final int MPPC_8K_DICT_SIZE = 8192;
	private static final int MPPC_64K_DICT_SIZE = 65536;

	private long roff;
	private byte[] hist;
	private String errorMsg = new String("Error while decompressing packet");

	private int dataOffset;
	private int walker_len;
	private int walker;
	private int match_off;

	public MPPCDecompressor(int type) throws RdpCompressionException {
		int dictSize;

		switch (type) {
			case RdpCompressionConstants.TYPE_8K:
				dictSize = MPPC_8K_DICT_SIZE;
				break;
			case RdpCompressionConstants.TYPE_64K:
				dictSize = MPPC_64K_DICT_SIZE;
				break;
			default:
				throw new RdpCompressionException("Bad MPPC compression type");
		}

		this.hist = new byte[dictSize];
		this.roff = 0;
	}

	public RdpPacket_Localised decompress(RdpPacket_Localised compressedPacket, int length, int compressionType) throws RdpCompressionException {
		byte[] compressedDatas = new byte[length];
		compressedPacket.copyToByteArray(compressedDatas, 0, compressedPacket.getPosition(), length);

		byte[] decompressedDatas;
		decompressedDatas = this.expand(compressedDatas, length, compressionType);

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

		this.walker = (int) this.roff;

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
}
