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

package com.sshtools.j2ssh.session;

import java.io.IOException;
import java.io.InputStream;

import com.sshtools.j2ssh.connection.ChannelInputStream;
import com.sshtools.j2ssh.connection.IOChannel;
import com.sshtools.j2ssh.connection.SshMsgChannelExtendedData;
import com.sshtools.j2ssh.io.ByteArrayReader;
import com.sshtools.j2ssh.subsystem.SubsystemClient;
import com.sshtools.j2ssh.transport.SshMessageStore;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.52 $
 */
public class SessionChannelClient
    extends IOChannel {
  private Integer exitCode = null;
  private String sessionType = "Uninitialized";
  private SubsystemClient subsystem;
  private boolean localFlowControl = false;
  private SignalListener signalListener;
  private SshMessageStore errorMessages = new SshMessageStore();
  private ChannelInputStream stderr = new ChannelInputStream(
      /*ChannelInputStream.createExtended(*/
      errorMessages,
      new Integer(SshMsgChannelExtendedData.SSH_EXTENDED_DATA_STDERR));
  //  new Integer(SshMsgChannelExtendedData.SSH_EXTENDED_DATA_STDERR));

  /**
   * Creates a new SessionChannelClient object.
   */
  public SessionChannelClient() {
    super();
    setName("session");
  }

  /**
   *
   *
   * @return
   */
  @Override
public byte[] getChannelOpenData() {
    return null;
  }

  /**
   *
   *
   * @return
   */
  @Override
public byte[] getChannelConfirmationData() {
    return null;
  }

  /**
   *
   *
   * @return
   */
  @Override
public String getChannelType() {
    return "session";
  }

  /**
   *
   *
   * @return
   */
  @Override
protected int getMinimumWindowSpace() {
    return 1024;
  }

  /**
   *
   *
   * @return
   */
  @Override
protected int getMaximumWindowSpace() {
    return 32648;
  }

  /**
   *
   *
   * @return
   */
  @Override
protected int getMaximumPacketSize() {
    return 32648;
  }

  /**
   *
   *
   * @param signalListener
   */
  public void setSignalListener(SignalListener signalListener) {
    this.signalListener = signalListener;
  }


  /**
   *
   *
   * @return
   */
  public Integer getExitCode() {
    return exitCode;
  }

  /**
   *
   *
   * @return
   */
  public boolean isLocalFlowControlEnabled() {
    return localFlowControl;
  }

  /**
   *
   *
   * @return
   */
  public String getSessionType() {
    return sessionType;
  }

  /**
   *
   *
   * @param sessionType
   */
  public void setSessionType(String sessionType) {
    this.sessionType = sessionType;
  }

  /**
   *
   *
   * @return
   */
  public SubsystemClient getSubsystem() {
    return subsystem;
  }

  /**
   *
   *
   * @throws IOException
   */
  @Override
protected void onChannelClose() throws IOException {
    super.onChannelClose();

    try {
      stderr.close();
    }
    catch (IOException ex) {
    }

    Integer exitCode = getExitCode();

    if (exitCode != null) {
      //log.debug("Exit code " + exitCode.toString());
    }
  }

  /**
   *
   *
   * @throws IOException
   */
  @Override
protected void onChannelOpen() throws IOException {
  }

  /**
   *
   *
   * @return
   *
   * @throws IOException
   */
  public InputStream getStderrInputStream() throws IOException {
    /*if (stderr == null) {
        throw new IOException("The session must be started first!");
             }*/
    return stderr;
  }

  /**
   *
   *
   * @param msg
   *
   * @throws IOException
   */
  @Override
protected void onChannelExtData(SshMsgChannelExtendedData msg) throws
      IOException {
    errorMessages.addMessage(msg);
  }

  /**
   *
   *
   * @param requestType
   * @param wantReply
   * @param requestData
   *
   * @throws IOException
   */
  @Override
protected void onChannelRequest(String requestType, boolean wantReply,
                                  byte[] requestData) throws IOException {

    if (requestType.equals("exit-status")) {
      exitCode = new Integer( (int) ByteArrayReader.readInt(requestData, 0));
    }
    else if (requestType.equals("exit-signal")) {
      ByteArrayReader bar = new ByteArrayReader(requestData);
      String signal = bar.readString();
      boolean coredump = bar.read() != 0;
      String message = bar.readString();
      /*String language =*/ bar.readString();


      if (signalListener != null) {
        signalListener.onExitSignal(signal, coredump, message);
      }
    }
    else if (requestType.equals("xon-xoff")) {
      if (requestData.length >= 1) {
        localFlowControl = (requestData[0] != 0);
      }
    }
    else if (requestType.equals("signal")) {
      String signal = ByteArrayReader.readString(requestData, 0);

      if (signalListener != null) {
        signalListener.onSignal(signal);
      }
    }
    else {
      if (wantReply) {
        connection.sendChannelRequestFailure(this);
      }
    }
  }
}
