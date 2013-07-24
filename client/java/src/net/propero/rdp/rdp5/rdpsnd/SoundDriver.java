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

import java.util.GregorianCalendar;
import java.util.concurrent.BlockingQueue;
import java.util.concurrent.LinkedBlockingQueue;

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
		protected long		time;
	}

	private BlockingQueue<AudioPacket> spool;
	private SoundChannel		soundChannel;
	private static final int	BUFFER_SIZE	= 65536;
	private SourceDataLine		oDevice;
	private WaveFormatEx		format;
	private boolean			dspBusy;
	private boolean 		soundDown;		
	private byte[]			buffer, outBuffer;
	private Thread			playThread = null;
	
	@SuppressWarnings("unused")
	private int			volume;

	public SoundDriver( SoundChannel sndChannel ) {
		this.soundDown = false;
		this.soundChannel = sndChannel;
		this.dspBusy = false;
		this.buffer = new byte[ BUFFER_SIZE ];
		this.outBuffer = new byte[ BUFFER_SIZE ];
		this.oDevice = null;
		this.format = null;
		this.volume = 65535;
		this.spool = new LinkedBlockingQueue<AudioPacket>();
	}

	
	public void stopDriver()
	{
		if (this.playThread == null)
			return;

		this.waveOutClose();
		this.playThread.interrupt();
		try {
			this.playThread.join();
		} catch (InterruptedException e) {}
		this.playThread = null;
	}

	public boolean waveOutOpen() {
		return true;
	}
	
	public void waveOutClose() {
		if( this.oDevice != null ) {
			this.oDevice.drain();
			this.oDevice.stop();
			this.oDevice.flush();
			this.oDevice.close();
			this.oDevice = null;
		}
	}

	public boolean waveOutSetFormat( WaveFormatEx fmt ) {
		if (this.soundDown)
			return false;
		this.format = fmt;

		WaveFormatEx trFormat = SoundDecoder.translateFormatForDevice( fmt );
		AudioFormat audioFormat = new AudioFormat( trFormat.nSamplesPerSec, trFormat.wBitsPerSample, trFormat.nChannels, true, false );

		try {
			if( this.oDevice != null ) {
				this.oDevice.drain();
				this.oDevice.close();
			}

			DataLine.Info dataLineInfo = new DataLine.Info( SourceDataLine.class, audioFormat );
			this.oDevice = (SourceDataLine)AudioSystem.getLine( dataLineInfo );

			this.oDevice.open( audioFormat );
			this.oDevice.start();
		} catch( Exception e ) {
			System.out.println("Unable to play sound");
			this.soundDown = true;
			return false;
			
		} catch( LinkageError e ) {
			System.out.println("Unable to play sound due to an internal error");
			this.soundDown = true;
			return false;
		}

		return true;
	}

	public void waveOutWrite( RdpPacket s, int tick, int packetIndex ) {
		AudioPacket current = new AudioPacket();
		
		current.s = s;
		current.tick = tick;
		current.index = packetIndex;
		current.time = new GregorianCalendar().getTimeInMillis();
		try {
			this.spool.add(current);
		}
		catch (IllegalStateException e)
		{
			logger.warn("Sound driver spool is full");
			this.dspBusy = true;
		}
	}

	public void waveOutVolume( int left, int right ) {
		this.volume = left < right ? right : left;
	}

	public void waveOutPlay(AudioPacket packet) {

		RdpPacket out = packet.s;

		int len = ( BUFFER_SIZE > out.size() - out.getPosition() ) ? ( out.size() - out.getPosition() ) : BUFFER_SIZE;
		out.copyToByteArray( this.buffer, 0, out.getPosition(), len );
		out.incrementPosition( len );

		int outLen = SoundDecoder.getBufferSize( len, this.format );
		if( outLen > outBuffer.length ) outBuffer = new byte[ outLen ];
		this.outBuffer = SoundDecoder.decode( this.buffer, this.outBuffer, len, this.format );

		this.oDevice.write( this.outBuffer, 0, outLen );

		GregorianCalendar tv = new GregorianCalendar();
		long duration = tv.getTimeInMillis() - packet.time;
		soundChannel.sendCompletion( ( ( packet.tick + (int)duration ) % 65536 ), packet.index );

		return;
	}

	public boolean isDspBusy() {
		return this.dspBusy;
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


	/*
	 * Use a single Thread to play sound, suggestions of Julien
	 * */
	public class playThread extends Thread{
		public void run(){
			try {
				while(true){
						AudioPacket current = spool.take();
						waveOutPlay(current);
				}
			} catch(InterruptedException e) {
				logger.info("Sound thread stopped");
			} catch (IllegalMonitorStateException e) {
				// This exception is threw at the session end
			}
		}
	}


	public void start() {
		this.playThread = new playThread();
		this.playThread.start();
	}
}
