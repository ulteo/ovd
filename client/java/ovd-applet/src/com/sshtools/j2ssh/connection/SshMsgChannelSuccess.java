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

import com.sshtools.j2ssh.io.ByteArrayReader;
import com.sshtools.j2ssh.io.ByteArrayWriter;
import com.sshtools.j2ssh.transport.InvalidMessageException;
import com.sshtools.j2ssh.transport.SshMessage;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.20 $
 */
public class SshMsgChannelSuccess
    extends SshMessage {
  /**  */
  protected final static int SSH_MSG_CHANNEL_SUCCESS = 99;
  private long channelId;

  /**
   * Creates a new SshMsgChannelSuccess object.
   */
  public SshMsgChannelSuccess() {
    super(SSH_MSG_CHANNEL_SUCCESS);
  }

  /**
   *
   *
   * @return
   */
  public long getChannelId() {
    return channelId;
  }

  /**
   *
   *
   * @return
   */
  @Override
public String getMessageName() {
    return "SSH_MSG_CHANNEL_SUCCESS";
  }

  /**
   *
   *
   * @param baw
   *
   * @throws InvalidMessageException
   */
  @Override
protected void constructByteArray(ByteArrayWriter baw) throws
      InvalidMessageException {
    try {
      baw.writeInt(channelId);
    }
    catch (IOException ioe) {
      throw new InvalidMessageException("Invalid message data");
    }
  }

  /**
   *
   *
   * @param bar
   *
   * @throws InvalidMessageException
   */
  @Override
protected void constructMessage(ByteArrayReader bar) throws
      InvalidMessageException {
    try {
      channelId = bar.readInt();
    }
    catch (IOException ioe) {
      throw new InvalidMessageException("Invalid message data");
    }
  }
}
