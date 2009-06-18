/*
 *  SSHTools - Java SSH2 API
 *
 *  Copyright (C) 2002 Lee David Painter.
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Library General Public License
 *  as published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 *
 *  You may also distribute it and/or modify it under the terms of the
 *  Apache style J2SSH Software License. A copy of which should have
 *  been provided with the distribution.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  License document supplied with your distribution for more details.
 *
 */

package com.sshtools.j2ssh.connection;

import java.io.IOException;
import java.io.OutputStream;

import org.vnc.PopupError;


/**
 *
 *
 * @author $author$
 * @version $Revision: 1.27 $
 */
public class ChannelOutputStream extends OutputStream {
  private Channel channel;
  private boolean isClosed = false;

  /**
   * Creates a new ChannelOutputStream object.
   *
   * @param channel
   * @param type
   */
  public ChannelOutputStream(Channel channel, Integer type) {
    this.channel = channel;
  }

  /**
   * Creates a new ChannelOutputStream object.
   *
   * @param channel
   */
  public ChannelOutputStream(Channel channel) {
    this(channel, null);
  }

  /**
   *
   *
   * @return
   */
  public boolean isClosed() {
    return isClosed;
  }

  /**
   *
   *
   * @throws IOException
   */
  @Override
public void close() throws IOException {
    isClosed = true;

    // Send an EOF if the channel is not closed
    if (!channel.isClosed()) {
      channel.connection.sendChannelEOF(channel);
    }
  }

  /**
   *
   *
   * @param b
   * @param off
   * @param len
   *
   * @throws IOException
   */
  @Override
public void write(byte[] b, int off, int len) throws IOException {
    if (isClosed) {
 	 	IOException e = new IOException();
		String message = "The ChannelOutputStream is closed!";
		String caption = "Connection error";
		PopupError.showError(null, message, caption, e);
		throw e;
    }

    byte[] data = null;

    if ( (off > 0) || (len < b.length)) {
      data = new byte[len];
      System.arraycopy(b, off, data, 0, len);
    }
    else {
      data = b;
    }

    sendChannelData(data);
  }

  /**
   *
   *
   * @param b
   *
   * @throws IOException
   */
  @Override
public void write(int b) throws IOException {
    if (isClosed) {
 	 	IOException e = new IOException();
		String message = "The ChannelOutputStream is closed!";
		String caption = "Connection error";
		PopupError.showError(null, message, caption, e);
		throw e;
    }

    byte[] data = new byte[1];
    data[0] = (byte) b;
    sendChannelData(data);
  }

  private void sendChannelData(byte[] data) throws IOException {
    channel.sendChannelData(data);
  }
}
