/* Subversion properties, do not modify!
 * 
 * $Date: 2008/01/28 13:47:42 $
 * $Revision: 1.2 $
 * $Author: tome.he $
 * 
 * Author: Miha Vitorovic
 * 
 * Based on: (rdpsnd.c)
 *  rdesktop: A Remote Desktop Protocol client.
 *  Sound Channel Process Functions
 *  Copyright (C) Matthew Chapman 2003
 *  Copyright (C) GuoJunBo guojunbo@ict.ac.cn 2003
 *  Copyright (C) 2010-2013 Ulteo SAS
 *  http://www.ulteo.com
 *  Author David LECHEVALIER <david@ulteo.com> 2010,2011,2013
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 */

package net.propero.rdp.rdp5.rdpsnd;
import java.io.IOException;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.VChannel;
import net.propero.rdp.rdp5.VChannels;

public class SoundChannel extends VChannel {

	public static final int	RDPSND_CLOSE		= 1;
	public static final int	RDPSND_WRITE		= 2;
	public static final int	RDPSND_SET_VOLUME	= 3;
	public static final int	RDPSND_UNKNOWN4	= 4;
	public static final int	RDPSND_COMPLETION	= 5;
	public static final int	RDPSND_SERVERTICK	= 6;
	public static final int	RDPSND_NEGOTIATE	= 7;

	public static final int	MAX_FORMATS		= 10;

	private boolean		awaitingDataPacket;
	private boolean		deviceOpen;
	private int			format;
	private int			currentFormat;
	private int			tick;
	private int			packetIndex;
	private int			formatCount;

	private SoundDriver		soundDriver;

	private WaveFormatEx[]	formats;
	private byte[] pendingData;
	

	public SoundChannel(Options opt_, Common common_) {
		super(opt_, common_);
		this.awaitingDataPacket = false;
		this.deviceOpen = false;
		this.format = 0;
		this.currentFormat = 0;
		this.tick = 0;
		this.packetIndex = 0;
		this.formatCount = 0;
		this.formats = new WaveFormatEx[ this.MAX_FORMATS ];
		for( int i = 0; i <this. MAX_FORMATS; i++ )
			this.formats[ i ] = new WaveFormatEx();
		this.soundDriver = new SoundDriver( this );
		this.pendingData = new byte[4];
		
		//init & run playThread
		this.soundDriver.start();
	}

	public int flags() {
		return VChannels.CHANNEL_OPTION_INITIALIZED | VChannels.CHANNEL_OPTION_ENCRYPT_RDP;
	}

	public String name() {
		return "rdpsnd";
	}

	public void stopPlayThread() {
		this.soundDriver.stopDriver();
	}

	public void process( RdpPacket data ) throws RdesktopException, IOException, CryptoException {
		int type, length;

		if( this.awaitingDataPacket ) {
			if( this.format >= this.MAX_FORMATS ) {
				logger.error( "RDPSND: Invalid format index\n" );
				return;
			}

			if( !this.deviceOpen || ( this.format != this.currentFormat ) ) {
				if( !this.deviceOpen && !this.soundDriver.waveOutOpen() ) {
					sendCompletion( this.tick, this.packetIndex );
					return;
				}
				if( !this.soundDriver.waveOutSetFormat( this.formats[ format ] ) ) {
					sendCompletion( this.tick, this.packetIndex );
					this.soundDriver.waveOutClose();
					this.deviceOpen = false;
					return;
				}
				this.deviceOpen = true;
				this.currentFormat = format;
			}
			data.copyFromByteArray(pendingData, 0, 0, 4);
			this.soundDriver.waveOutWrite( data, this.tick, this.packetIndex );
			this.awaitingDataPacket = false;
			return;
		}

		type = data.get8();
		data.get8(); // ? unknown ?
		length = data.getLittleEndian16();

		switch( type ) {
			case RDPSND_WRITE:
				this.tick = data.getLittleEndian16() & 0xFFFF;
				this.format = data.getLittleEndian16() & 0xFFFF;
				this.packetIndex = data.get8() & 0xFF;
				data.incrementPosition(3); // pad
				data.copyToByteArray(pendingData, 0, data.getPosition(), 4);
				this.awaitingDataPacket = true;
				break;
			case RDPSND_CLOSE:
				// Under Windows server 2008R2, there is a lose of packet if we do that.
				//this.soundDriver.waveOutClose();
				//this.deviceOpen = false;
				break;
			case RDPSND_NEGOTIATE:
				negotiate( data );
				break;
			case RDPSND_SERVERTICK:
				processServerTick( data );
				break;
			case RDPSND_SET_VOLUME:
				int volume = data.getLittleEndian32();
				if( this.deviceOpen ) {
					this.soundDriver.waveOutVolume( ( volume & 0xffff ), ( volume >> 16 ) & 0xffff );
				}
				break;
			default:
				logger.error( "RDPSND packet type " + type );
				break;
		}
	}

	public void sendCompletion( int tick, int packetIndex ) {
		RdpPacket_Localised out = initPacket( RDPSND_COMPLETION, 4 );
		out.setLittleEndian16( tick );
		out.set8( packetIndex );
		out.set8( 0 );
		out.markEnd();
		try {
			send_packet( out );
		} catch( RdesktopException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( IOException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( CryptoException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		}
	}

	private void negotiate( RdpPacket data ) {
		boolean deviceAvailable = false;
		int formatLength = 0;

		data.incrementPosition( 14 ); // advance 14 bytes - flags, volume, pitch, UDP port

		int inFormatCount = data.getLittleEndian16();

		data.incrementPosition( 4 ); // pad, status, pad

		// test the device
		//if( LibAO.waveOutOpen() ) {
		//	LibAO.waveOutClose();
		deviceAvailable = true;
		//}

		formatCount = 0;

		if( checkRemaining( data, 18 * inFormatCount ) ) {
			for( int i = 0; i < inFormatCount; i++ ) {
				WaveFormatEx format = formats[ formatCount ];
				format.wFormatTag = data.getLittleEndian16();
				format.nChannels = data.getLittleEndian16();
				format.nSamplesPerSec = data.getLittleEndian32();
				format.nAvgBytesPerSec = data.getLittleEndian32();
				format.nBlockAlign = data.getLittleEndian16();
				format.wBitsPerSample = data.getLittleEndian16();
				format.cbSize = data.getLittleEndian16();

				int readCnt = format.cbSize;
				int discardCnt = 0;
				if( format.cbSize > WaveFormatEx.MAX_CBSIZE ) {
					logger.error( "cbSize too large for buffer: " + format.cbSize );
					readCnt = WaveFormatEx.MAX_CBSIZE;
					discardCnt = format.cbSize - WaveFormatEx.MAX_CBSIZE;
				}
				data.copyToByteArray( format.cb, 0, data.getPosition(), readCnt );
				// advance packet position
				data.incrementPosition( readCnt + discardCnt );

				if( deviceAvailable && soundDriver.waveOutFormatSupported( format ) ) {
					this.formatCount++;
					formatLength += 18;
					formatLength += format.cbSize;
					
					if( this.formatCount == MAX_FORMATS ) break;
				}
			}
		}
		
		RdpPacket_Localised out = initPacket( RDPSND_NEGOTIATE | 0x200, 20 + formatLength );
		out.setLittleEndian32( 3 ); // flags
		out.setLittleEndian32( 0xffffffff ); // volume
		out.setLittleEndian32( 0 ); // pitch
		out.setLittleEndian16( 0 ); // UDP port

		out.setLittleEndian16( formatCount );
		out.set8( 0x95 ); // pad ?
		out.setLittleEndian16( 2 ); // status
		out.set8( 0x77 ); // pad ?

		for( int i = 0; i < formatCount; i++ ) {
			WaveFormatEx format = formats[ i ];
			out.setLittleEndian16( format.wFormatTag );
			out.setLittleEndian16( format.nChannels );
			out.setLittleEndian32( format.nSamplesPerSec );
			out.setLittleEndian32( format.nAvgBytesPerSec );
			out.setLittleEndian16( format.nBlockAlign );
			out.setLittleEndian16( format.wBitsPerSample );
			out.setLittleEndian16( format.cbSize ); // cbSize
			if (format.cbSize > 0) {
				out.copyFromByteArray(format.cb, 0, out.getPosition(), format.cbSize);
				out.incrementPosition(format.cbSize);
			}
		}

		out.markEnd();
		try {
			send_packet( out );
		} catch( RdesktopException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( IOException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( CryptoException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		}
	}

	private boolean checkRemaining( RdpPacket p, int required ) {
		int a = p.getPosition();
		int e = p.size();//p.getEnd();
		return p.getPosition() + required <= p.size();//p.getEnd();
	}

	private void processServerTick( RdpPacket data ) {
		int tick1, tick2;

		tick1 = data.getLittleEndian16();
		tick2 = data.getLittleEndian16();

		RdpPacket_Localised out = initPacket( RDPSND_SERVERTICK | 0x2300, 4 );
		out.setLittleEndian16( tick1 );
		out.setLittleEndian16( tick2 );
		out.markEnd();

		try {
			send_packet( out );
		} catch( RdesktopException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( IOException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( CryptoException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		}
	}

	private RdpPacket_Localised initPacket( int type, int size ) {
		RdpPacket_Localised s = new RdpPacket_Localised( size + 4 );
		s.setLittleEndian16( type );
		s.setLittleEndian16( size );
		return s;
	}
}
