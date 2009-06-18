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

package com.sshtools.j2ssh.subsystem;

import java.io.IOException;
import java.io.InputStream;

import com.sshtools.j2ssh.connection.ChannelState;
import com.sshtools.j2ssh.io.ByteArrayReader;
import com.sshtools.j2ssh.session.SessionChannelClient;
import com.sshtools.j2ssh.util.StartStopState;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.34 $
 */
public abstract class SubsystemClient
    implements Runnable {
  private InputStream in;
  private String name;
  private StartStopState state = new StartStopState(StartStopState.STOPPED);

  /**  */
  protected SubsystemMessageStore messageStore;

  /**  */
  protected SessionChannelClient session;

  /**
   *
   *
   * @return
   */
  public boolean isClosed() {
    return state.getValue() == StartStopState.STOPPED;
  }

  /**
   *
   *
   * @param session
   */
  public void setSessionChannel(SessionChannelClient session) {
    this.session = session;
    this.in = session.getInputStream();
    session.setName(name);
  }

  /**
   *
   *
   * @return
   */
  public SessionChannelClient getSessionChannel() {
    return this.session;
  }
  
  /**
   *
   *
   * @return
   *
   * @throws IOException
   */
  protected abstract boolean onStart() throws IOException;

  /**
   *
   *
   * @return
   */
  public String getName() {
    return name;
  }

  /**
   *
   */
  public void run() {
    int read;
    int len;
    int pos;
    byte[] buffer = new byte[4];
    byte[] msg;

    state.setValue(StartStopState.STARTED);

    try {
      // read the first four bytes of data to determine the susbsytem
      // message length
      while ( (state.getValue() == StartStopState.STARTED)
             && (session.getState().getValue() == ChannelState.CHANNEL_OPEN)) {
        read = in.read(buffer);

        if (read > 0) {
          len = (int) ByteArrayReader.readInt(buffer, 0);
          msg = new byte[len];
          pos = 0;

          while (pos < len) {
            read = in.read(msg, pos, msg.length - pos);

            if (read > 0) {
              pos += read;
            }
            else if (read == -1) {
              break;
            }
          }

          messageStore.addMessage(msg);
          msg = null;
        }
        else if (read == -1) {
          break;
        }
      }

    }
    catch (IOException ioe) {
    }
    finally {
      state.setValue(StartStopState.STOPPED);
    }

  }

}
