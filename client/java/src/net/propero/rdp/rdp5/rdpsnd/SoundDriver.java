/* Subversion properties, do not modify!
 * 
 * Sound Channel Process Functions - javax.sound-driver
 * 
 * $Date: 2008/01/28 13:47:42 $
 * $Revision: 1.2 $
 * $Author: tome.he $
 * 
 * Author: Miha Vitorovic
 * 
 * Based on: (rdpsnd_libao.c)
 *  rdesktop: A Remote Desktop Protocol client.
 *  Sound Channel Process Functions
 *  Copyright (C) Matthew Chapman 2003
 *  Copyright (C) GuoJunBo guojunbo@ict.ac.cn 2003
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

import java.util.GregorianCalendar;

import javax.sound.sampled.AudioFormat;
import javax.sound.sampled.AudioSystem;
import javax.sound.sampled.DataLine;
import javax.sound.sampled.SourceDataLine;

import net.propero.rdp.Input;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.rdp5.VChannels;

import org.apache.log4j.Logger;

public class SoundDriver {

	protected static Logger	logger	= Logger.getLogger( Input.class );

	private class AudioPacket {
		protected RdpPacket	s;
		protected int		tick;
		protected int		index;
	}

	private SoundChannel		soundChannel;

	private static final int		MAX_QUEUE		= 20;
	private static final int		BUFFER_SIZE	= 65536;

	private AudioPacket[]		packetQueue;
	private int				queueHi, queueLo;

	private SourceDataLine		oDevice;
	private int				volume;

	private WaveFormatEx		format;

	private boolean			reopened;
	private boolean			dspBusy;
	private boolean 		soundDown;		

	private byte[]				buffer, outBuffer;
	private GregorianCalendar	prevTime;

	public SoundDriver( SoundChannel sndChannel ) {
		soundDown = false;
		soundChannel = sndChannel;
		packetQueue = new AudioPacket[ MAX_QUEUE ];
		for( int i = 0; i < MAX_QUEUE; i++ )
			packetQueue[ i ] = new AudioPacket();
		queueHi = 0;
		queueLo = 0;
		reopened = true;
		dspBusy = false;
		buffer = new byte[ BUFFER_SIZE ];
		outBuffer = new byte[ BUFFER_SIZE ];
		oDevice = null;
		format = null;
		volume = 65535;
	}

	public boolean waveOutOpen() {
		return true;
	}

	public void waveOutClose() {
		int queueLo2 = queueLo;
		while( queueLo2 != queueHi ) {
			soundChannel.sendCompletion( packetQueue[ queueLo2 ].tick, packetQueue[ queueLo2 ].index );
			queueLo2 = ( queueLo2 + 1 ) % MAX_QUEUE;
		}
		if( oDevice != null ) {
			oDevice.stop();
			oDevice.flush();
			oDevice.close();
			oDevice = null;
		}
	}

	public boolean waveOutSetFormat( WaveFormatEx fmt ) {
		if (soundDown)
			return false;
		format = fmt;

		WaveFormatEx trFormat = SoundDecoder.translateFormatForDevice( fmt );
		AudioFormat audioFormat = new AudioFormat( trFormat.nSamplesPerSec, trFormat.wBitsPerSample, trFormat.nChannels, true, false );

		try {
			if( oDevice != null ) {
				oDevice.drain();
				oDevice.close();
			}

			DataLine.Info dataLineInfo = new DataLine.Info( SourceDataLine.class, audioFormat );
			oDevice = (SourceDataLine)AudioSystem.getLine( dataLineInfo );

			oDevice.open( audioFormat );
			oDevice.start();
		} catch( Exception e ) {
			System.out.println("Unable to play sound");
			this.soundDown = true;
			return false;
		}

		reopened = true;

		return true;
	}

	public void waveOutWrite( RdpPacket s, int tick, int packetIndex ) {
		AudioPacket packet = packetQueue[ queueHi ];
		int nextHi = ( queueHi + 1 ) % MAX_QUEUE;

		if( nextHi == queueLo ) {
			logger.error( "No space to queue audio packet" );
			return;
		}

		queueHi = nextHi;

		packet.s = s;
		packet.tick = tick;
		packet.index = packetIndex;

		packet.s.incrementPosition( 4 );

		if( !dspBusy ) waveOutPlay();
	}

	public void waveOutVolume( int left, int right ) {
		volume = left < right ? right : left;
	}

	public void waveOutPlay() {

		if( reopened ) {
			reopened = false;
			prevTime = new GregorianCalendar();
		}

		if( queueLo == queueHi ) {
			dspBusy = false;
			return;
		}

		AudioPacket packet = packetQueue[ queueLo ];
		RdpPacket out = packet.s;

		int nextTick;
		if( ( ( queueLo + 1 ) % MAX_QUEUE ) != queueHi )
			nextTick = packetQueue[ ( queueLo + 1 ) % MAX_QUEUE ].tick;
		else
			nextTick = ( packet.tick + 65535 ) % 65536;

		int len = ( BUFFER_SIZE > out.size() - out.getPosition() ) ? ( out.size() - out.getPosition() ) : BUFFER_SIZE;
		out.copyToByteArray( buffer, 0, out.getPosition(), len );
		out.incrementPosition( len );

		int outLen = SoundDecoder.getBufferSize( len, format );
		if( outLen > outBuffer.length ) outBuffer = new byte[ outLen ];
		outBuffer = SoundDecoder.decode( buffer, outBuffer, len, format );

		oDevice.write( outBuffer, 0, outLen );

		GregorianCalendar tv = new GregorianCalendar();

		long duration = tv.getTimeInMillis() - prevTime.getTimeInMillis();

		if( packet.tick > nextTick ) nextTick += 65536;

		if( ( out.getPosition() == out.size() ) || ( duration > nextTick - packet.tick + 500 ) ) {
			prevTime = tv;
			soundChannel.sendCompletion( ( ( packet.tick + (int)duration ) % 65536 ), packet.index );
			queueLo = ( queueLo + 1 ) % MAX_QUEUE;
		}
		dspBusy = true;
		return;
	}

	public boolean isDspBusy() {
		return dspBusy;
	}

	public boolean waveOutFormatSupported( WaveFormatEx fmt ) {
		switch( fmt.wFormatTag ) {
			case VChannels.WAVE_FORMAT_ALAW:
				return ( ( fmt.nChannels == 1 ) || ( fmt.nChannels == 2 ) ) && ( fmt.wBitsPerSample == 8 );
			case VChannels.WAVE_FORMAT_PCM:
				return ( ( fmt.nChannels == 1 ) || ( fmt.nChannels == 2 ) )
						&& ( ( fmt.wBitsPerSample == 8 ) || ( fmt.wBitsPerSample == 16 ) );
				// ADPCM crashes the "RDP Clip monitor" on the server
				//case VChannels.WAVE_FORMAT_ADPCM:
				//	logger.info( "ADPCM" );
				//	return ( ( fmt.nChannels == 1 ) || ( fmt.nChannels == 2 ) ) && ( fmt.wBitsPerSample == 4 );
			default:
				return false;
		}
	}

}
