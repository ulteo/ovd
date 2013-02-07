/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
 * Author David LECHEVALIER <david@ulteo.com> 2013
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

/* Subversion properties, do not modify!
 * 
 * Sound Channel Process Functions - class that handles various sound decodings. 
 * The methods are mostly all ported from different open source libraries. The
 * original author and source is listed with the method. 
 * 
 * $Date: 2008/01/28 13:47:42 $
 * $Revision: 1.2 $
 * $Author: tome.he $
 * 
 */
package net.propero.rdp.rdp5.rdpsnd;

import java.util.Arrays;

import net.propero.rdp.rdp5.VChannels;

public class SoundDecoder {

	/* These are for MS-ADPCM */
	/* AdaptationTable[], AdaptCoeff1[], and AdaptCoeff2[] are from libsndfile */
	private static final int[]	AdaptationTable	= { 230, 230, 230, 230, 307, 409, 512, 614, 768, 614, 512, 409, 307, 230, 230, 230 };

	private static final int[]	AdaptCoeff1		= { 256, 512, 0, 192, 240, 460, 392 };

	private static final int[]	AdaptCoeff2		= { 0, -256, 0, 64, 0, -208, -232 };

	private static class ADPCM
	{
		int[] last_sample = new int[2];
		int[] last_step = new int[2];
	};
	
	private static int[] ima_step_index_table = {
		-1, -1, -1, -1, 2, 4, 6, 8,
		-1, -1, -1, -1, 2, 4, 6, 8
	};
	
	private static int[] ima_step_size_table = {
		7, 8, 9, 10, 11, 12, 13, 14, 16, 17,
		19, 21, 23, 25, 28, 31, 34, 37, 41, 45,
		50, 55, 60, 66, 73, 80, 88, 97, 107, 118,
		130, 143, 157, 173, 190, 209, 230, 253, 279, 307,
		337, 371, 408, 449, 494, 544, 598, 658, 724, 796,
		876, 963, 1060, 1166, 1282, 1411, 1552, 1707, 1878, 2066,
		2272, 2499, 2749, 3024, 3327, 3660, 4026, 4428, 4871, 5358,
		5894, 6484, 7132, 7845, 8630, 9493, 10442, 11487, 12635, 13899,
		15289, 16818, 18500, 20350, 22385, 24623, 27086, 29794, 32767
	};
	
	public static final WaveFormatEx translateFormatForDevice( WaveFormatEx fmt ) {

		if( fmt.wFormatTag != VChannels.WAVE_FORMAT_PCM ) {
			WaveFormatEx ret = new WaveFormatEx();
			ret.wFormatTag = fmt.wFormatTag;
			ret.nChannels = fmt.nChannels;
			ret.wBitsPerSample = 16;
			ret.nSamplesPerSec = fmt.nSamplesPerSec;
			ret.nBlockAlign = ret.nChannels * ret.wBitsPerSample / 8;
			ret.nAvgBytesPerSec = ret.nBlockAlign * ret.nSamplesPerSec;
			ret.cbSize = 0;
			ret.cb = null;

			if (fmt.wFormatTag != VChannels.WAVE_FORMAT_IMA_ADPCM) {
				ret.nBlockAlign = fmt.nBlockAlign;
			}
			
			return ret;
		}
		return fmt;
	}

	public static final int getBufferSize( long inputBufferLength, WaveFormatEx fmt ) {
		if( inputBufferLength > Integer.MAX_VALUE ) throw new NumberFormatException( "Number too large: " + inputBufferLength );
		switch( fmt.wFormatTag ) {
			case VChannels.WAVE_FORMAT_ALAW:
				return 2 * (int)inputBufferLength; // ALAW converts 8 bits samples to 16 bits.
			case VChannels.WAVE_FORMAT_IMA_ADPCM:
			case VChannels.WAVE_FORMAT_ADPCM:
				int blockHeaderOverhead = ( 7 * fmt.nChannels );
				int numOfBlocks = ( (int)inputBufferLength / fmt.nBlockAlign );
				int rawSize = ( fmt.nChannels * 16 / fmt.wBitsPerSample ) * (int)inputBufferLength;
				return rawSize - ( numOfBlocks * blockHeaderOverhead );
			default:
				return (int)inputBufferLength;
		}
	}

	public static final byte[] decode( byte[] inputBuffer, byte[] outputBuffer, int len, WaveFormatEx fmt ) {
		switch( fmt.wFormatTag ) {
			case VChannels.WAVE_FORMAT_ALAW:
				decodeALaw( inputBuffer, outputBuffer, len );
				return outputBuffer;
			case VChannels.WAVE_FORMAT_IMA_ADPCM:
				return dsp_decode_ima_adpcm(inputBuffer, len, fmt.nChannels, fmt.nBlockAlign);
			case VChannels.WAVE_FORMAT_ADPCM:
				decodeADPCM( inputBuffer, outputBuffer, len, fmt );
				return outputBuffer;
			default:
				return inputBuffer;
		}

	}

	/*
	 * A-Law decoder by Marc Sweetgall. 
	 * URL: http://www.codeproject.com/csharp/g711audio.asp
	 * (c) Marc Sweetgall, 2006
	 */
	private static final void decodeALaw( byte[] inputBuffer, byte[] outputBuffer, int len ) {
		int bufferSize = len < inputBuffer.length ? len : inputBuffer.length;

		int o = 0;
		for( int i = 0; i < bufferSize; i++ ) {
			int y = ( (int)inputBuffer[ i ] & 0xFF ) ^ 0xD5;
			int sign = ( y & 0x80 ) != 0 ? -1 : 1;
			int exponent = ( y & 0x70 ) >> 4;
			int data = ( ( y & 0x0f ) << 4 ) + 8;

			if( exponent != 0 ) data += 0x100;
			if( exponent > 1 ) data <<= ( exponent - 1 );
			data *= sign;

			outputBuffer[ o++ ] = (byte)( data & 0xFF );
			outputBuffer[ o++ ] = (byte)( ( data & 0xFF00 ) >> 8 );
		}
	}

	/*
	 * MS ADPCM part starts here. It (mostly) works on the WAV file (some hisses, but normal sound otherwise), 
	 * but I was unable to test over the network, becuse the "RDP Clip monitor" crashes on the server, if ADPCM
	 * is forced. Go figure :(
	 */

	private static final void decodeADPCM( byte inputBuffer[], byte outputBuffer[], int len, WaveFormatEx fmt ) {
		int blocksInSample = len / fmt.nBlockAlign;

		int dstIndex = 0;
		for( int i = 0; i < blocksInSample; i++ )
			dstIndex = adpcmDecodeFrame( fmt, inputBuffer, i * fmt.nBlockAlign, fmt.nBlockAlign, outputBuffer, dstIndex );
	}

	private static final int storeShort( byte[] dst, short data, int offset ) {
		dst[ offset ] = (byte)( data & 0xFF );
		dst[ offset + 1 ] = (byte)( ( data >> 8 ) & 0xFF );

		return offset + 2;
	}

	private static final short adpcmMsExpandNibble( ADPCMChannelStatus c, byte nibble ) {
		int prePredictor;

		prePredictor = ( ( c.sample1 * c.coeff1 ) + ( c.sample2 * c.coeff2 ) ) / 256;
		prePredictor += ( ( nibble & 0x08 ) != 0 ? ( nibble - 0x10 ) : ( nibble ) ) * c.idelta;
		short predictor = (short)( prePredictor & 0xFFFF );

		c.sample2 = c.sample1;
		c.sample1 = predictor;
		c.idelta = ( AdaptationTable[ (int)nibble ] * c.idelta ) >> 8;
		if( c.idelta < 16 ) c.idelta = 16;

		return predictor;
	}

	/* Based on:
	 * ADPCM codecs
	 * Copyright (c) 2001-2003 The ffmpeg Project
	 *
	 * This library is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU Lesser General Public
	 * License as published by the Free Software Foundation; either
	 * version 2 of the License, or (at your option) any later version.
	 *
	 * This library is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	 * Lesser General Public License for more details.
	 *
	 * You should have received a copy of the GNU Lesser General Public
	 * License along with this library; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
	 * 
	 * --- end license ---
	 * 
	 * Adopted for Java by Miha Vitorovic
	 */
	public static final int adpcmDecodeFrame( WaveFormatEx fmt, byte[] srcBuffer, int srcIndex, int inputBufferSize, byte[] dstBuffer,
			int dstIndex ) {
		int blockPredictor[] = new int[ 2 ];
		boolean st; /* stereo */

		ADPCMChannelStatus status0 = new ADPCMChannelStatus();
		ADPCMChannelStatus status1 = new ADPCMChannelStatus();

		if( inputBufferSize == 0 ) return dstIndex;

		st = fmt.nChannels == 2;

		if( fmt.nBlockAlign != 0 && inputBufferSize > fmt.nBlockAlign ) inputBufferSize = fmt.nBlockAlign;
		int n = inputBufferSize - 7 * fmt.nChannels;
		if( n < 0 ) return dstIndex;
		blockPredictor[ 0 ] = clip( srcBuffer[ srcIndex++ ], 0, 7 );
		blockPredictor[ 1 ] = 0;
		if( st ) blockPredictor[ 1 ] = clip( srcBuffer[ srcIndex++ ], 0, 7 );
		status0.idelta = ( ( (int)srcBuffer[ srcIndex ] & 0xFF ) | ( ( (int)srcBuffer[ srcIndex + 1 ] << 8 ) & 0xFF00 ) );
		srcIndex += 2;
		if( st ) {
			status1.idelta = ( ( (int)srcBuffer[ srcIndex ] & 0xFF ) | ( ( (int)srcBuffer[ srcIndex + 1 ] << 8 ) & 0xFF00 ) );
			srcIndex += 2;
		}
		status0.coeff1 = AdaptCoeff1[ blockPredictor[ 0 ] ];
		status0.coeff2 = AdaptCoeff2[ blockPredictor[ 0 ] ];
		status1.coeff1 = AdaptCoeff1[ blockPredictor[ 1 ] ];
		status1.coeff2 = AdaptCoeff2[ blockPredictor[ 1 ] ];

		status0.sample1 = (short)( ( (int)srcBuffer[ srcIndex ] & 0xFF ) | ( ( (int)srcBuffer[ srcIndex + 1 ] << 8 ) & 0xFF00 ) );
		srcIndex += 2;
		if( st )
			status1.sample1 = (short)( ( (int)srcBuffer[ srcIndex ] & 0xFF ) | ( ( (int)srcBuffer[ srcIndex + 1 ] << 8 ) & 0xFF00 ) );
		if( st ) srcIndex += 2;
		status0.sample2 = (short)( ( (int)srcBuffer[ srcIndex ] & 0xFF ) | ( ( (int)srcBuffer[ srcIndex + 1 ] << 8 ) & 0xFF00 ) );
		srcIndex += 2;
		if( st )
			status1.sample2 = (short)( ( srcBuffer[ (int)srcIndex ] & 0xFF ) | ( ( (int)srcBuffer[ srcIndex + 1 ] << 8 ) & 0xFF00 ) );
		if( st ) srcIndex += 2;

		dstIndex = storeShort( dstBuffer, status0.sample1, dstIndex );
		if( st ) dstIndex = storeShort( dstBuffer, status1.sample1, dstIndex );
		dstIndex = storeShort( dstBuffer, status0.sample2, dstIndex );
		if( st ) dstIndex = storeShort( dstBuffer, status1.sample2, dstIndex );
		for( ; n > 0; n-- ) {
			dstIndex = storeShort( dstBuffer, adpcmMsExpandNibble( status0, (byte)( ( (int)srcBuffer[ srcIndex ] >> 4 ) & 0x0F ) ),
					dstIndex );
			dstIndex = storeShort( dstBuffer, adpcmMsExpandNibble( ( st ? status1 : status0 ),
					(byte)( (int)srcBuffer[ srcIndex ] & 0x0F ) ), dstIndex );
			srcIndex++;
		}
		return dstIndex;
	}

	private static final int clip( int a, int amin, int amax ) {
		if( a < amin )
			return amin;
		else if( a > amax )
			return amax;
		else
			return a;
	}

	static short dsp_decode_ima_adpcm_sample(ADPCM adpcm, int channel, int sample)
	{
		int ss;
		int d;
		ss = ima_step_size_table[adpcm.last_step[channel]] & 0x0000FFFF;
		d = (ss >> 3) & 0x1fff;
		
		if ((sample & 1) != 0) {
			d += (ss >> 2) & 0x3fff;
		}
		
		if ((sample & 2) != 0) {
			d += (ss >> 1) & 0x7fff;
		}
		
		if ((sample & 4) != 0) {
			d += ss;
		}
		
		if ((sample & 8) != 0) {
			d = -(short)d;
		}
		
		d += (short)adpcm.last_sample[channel];
		
		if ((short)d < -32768) {
			d = -32768;
		}
		else if ((short)d > 32767) {
			d = 32767;
		}
		
		adpcm.last_sample[channel] = (short)(d);
		adpcm.last_step[channel] += ima_step_index_table[(int)(sample&0x0FF)];
		if (adpcm.last_step[channel] < 0) {
			adpcm.last_step[channel] = 0;
		}
		else if (adpcm.last_step[channel] > 88) {
			adpcm.last_step[channel] = 88;
		}
		return (short)(d & 0xFFFF);
	}

	public static byte[] dsp_decode_ima_adpcm(byte[] src, int size, int channels, int block_size)
	{
		byte[] dst;
		byte sample;
		int decoded;
		int channel;
		int i;
		int sindex = 0;
		int dindex = 0;
		int out_size;
		ADPCM adpcm = new ADPCM();
		
		out_size = size * 4;
		dst = new byte[out_size];
		while (size > 0) {
			if (size % block_size == 0) {
				adpcm.last_sample[0] = (int)(((src[sindex]& 0xFF)) | ((src[sindex+1]) << 8) & 0xFF00);
					
				adpcm.last_step[0] = (int) (src[sindex+2]);
				sindex += 4;
				size -= 4;
				out_size -= 16;
				if (channels > 1) {
					adpcm.last_sample[1] = (int)(((src[sindex]& 0xFF)) | ((src[sindex+1]) << 8) & 0xFF00);
					adpcm.last_step[1] = (int) (src[sindex + 2]);
					sindex += 4;
					size -= 4;
					out_size -= 16;
				}
			}
			
			if (channels > 1) {
				for (i = 0; i < 8; i++) {
					channel = (i < 4 ? 0 : 1);
					sample = (byte)(src[sindex] & 0x0f);
					decoded = dsp_decode_ima_adpcm_sample(adpcm, channel, sample);
					dst[dindex + ((i & 3) << 3) + (channel << 1)] = (byte)(decoded & 0xff);
					dst[dindex + ((i & 3) << 3) + (channel << 1) + 1] = (byte)(decoded >> 8);
					sample = (byte)((src[sindex] >> 4) & 0x0f);
					decoded = dsp_decode_ima_adpcm_sample(adpcm, channel, sample);
					dst[dindex + ((i & 3) << 3) + (channel << 1) + 4] = (byte)(decoded & 0xff);
					dst[dindex + ((i & 3) << 3) + (channel << 1) + 5] = (byte)(decoded >> 8);
					sindex++;
				}
				dindex += 32;
				size -= 8;
			}
			else {
				sample = (byte)(src[sindex] & 0x0f);

				decoded = dsp_decode_ima_adpcm_sample(adpcm, 0, sample);
				dst[dindex++] = (byte)(decoded & 0xff);
				dst[dindex++] = (byte)((decoded >> 8) & 0xff);
				sample = (byte)((src[sindex] >> 4) & 0x0f);
				decoded = dsp_decode_ima_adpcm_sample(adpcm, 0, sample);
				dst[dindex++] = (byte)(decoded & 0xff);
				dst[dindex++] = (byte)((decoded >> 8) & 0xff);
				sindex++;
				size--;
			}
		}
		
		if (out_size != dst.length) {
			byte[] res = Arrays.copyOf(dst, out_size); 
			
			return res;
		}
		
		return dst;
	}
}
