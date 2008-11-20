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
import java.net.Socket;

import com.sshtools.j2ssh.SshException;
import com.sshtools.j2ssh.agent.AgentSocketChannel;
import com.sshtools.j2ssh.agent.SshAgentClient;
import com.sshtools.j2ssh.connection.Channel;
import com.sshtools.j2ssh.connection.ChannelFactory;
import com.sshtools.j2ssh.connection.ChannelInputStream;
import com.sshtools.j2ssh.connection.IOChannel;
import com.sshtools.j2ssh.connection.InvalidChannelException;
import com.sshtools.j2ssh.connection.SshMsgChannelExtendedData;
import com.sshtools.j2ssh.io.ByteArrayReader;
import com.sshtools.j2ssh.io.ByteArrayWriter;
import com.sshtools.j2ssh.io.UnsignedInteger32;
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
  public byte[] getChannelOpenData() {
    return null;
  }

  /**
   *
   *
   * @return
   */
  public byte[] getChannelConfirmationData() {
    return null;
  }

  /**
   *
   *
   * @return
   */
  public String getChannelType() {
    return "session";
  }

  /**
   *
   *
   * @return
   */
  protected int getMinimumWindowSpace() {
    return 1024;
  }

  /**
   *
   *
   * @return
   */
  protected int getMaximumWindowSpace() {
    return 32648;
  }

  /**
   *
   *
   * @return
   */
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
   * @param name
   * @param value
   *
   * @return
   *
   * @throws IOException
   */
  public boolean setEnvironmentVariable(String name, String value) throws
      IOException {

    ByteArrayWriter baw = new ByteArrayWriter();

    baw.writeString(name);
    baw.writeString(value);

    return connection.sendChannelRequest(this, "env", true,
                                         baw.toByteArray());
  }

  /**
   *
   *
   * @return
   *
   * @throws IOException
   * @throws SshException
   * @throws InvalidChannelException
   */
  public boolean requestAgentForwarding() throws IOException {

    if (System.getProperty("sshtools.agent") == null) {
      throw new SshException(
          "Agent not found! 'sshtools.agent' system property should identify the agent location");
    }

    boolean success = connection.sendChannelRequest(this, "auth-agent-req",
        true, null);

    if (success) {
      // Allow an Agent Channel to be opened
      connection.addChannelFactory(AgentSocketChannel.AGENT_FORWARDING_CHANNEL,
                                   new ChannelFactory() {
        public Channel createChannel(String channelType,
                                     byte[] requestData) throws
            InvalidChannelException {
          try {
            AgentSocketChannel channel = new AgentSocketChannel(false);

            Socket socket = SshAgentClient.connectAgentSocket(System
                .getProperty("sshtools.agent") /*, 5*/);

            channel.bindSocket(socket);

            return channel;
          }
          catch (Exception ex) {
            throw new InvalidChannelException(ex.getMessage());
          }
        }
      });
    }

    return success;
  }

  /**
   *
   *
   * @param display
   * @param cookie
   *
   * @return
   *
   * @throws IOException
   */
  public boolean requestX11Forwarding(int display, String cookie) throws
      IOException {

    ByteArrayWriter baw = new ByteArrayWriter();
    baw.writeBoolean(false);
    baw.writeString("MIT-MAGIC-COOKIE-1");
    baw.writeString(cookie);
    baw.writeUINT32(new UnsignedInteger32(String.valueOf(display)));

    return connection.sendChannelRequest(this, "x11-req", true,
                                         baw.toByteArray());
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
   * @param term
   *
   * @throws IOException
   */
  public void changeTerminalDimensions(PseudoTerminal term) throws IOException {

    ByteArrayWriter baw = new ByteArrayWriter();

    baw.writeInt(term.getColumns());
    baw.writeInt(term.getRows());
    baw.writeInt(term.getWidth());
    baw.writeInt(term.getHeight());

    connection.sendChannelRequest(this, "window-change", false,
                                  baw.toByteArray());
  }

  /**
   *
   *
   * @param command
   *
   * @return
   *
   * @throws IOException
   */
  public boolean executeCommand(String command) throws IOException {

    ByteArrayWriter baw = new ByteArrayWriter();

    baw.writeString(command);

    if (connection.sendChannelRequest(this, "exec", true, baw.toByteArray())) {
      if (sessionType.equals("Uninitialized")) {
        sessionType = command;
      }

      return true;
    }
    else {
      return false;
    }
  }

  /**
   *
   *
   * @param term
   * @param cols
   * @param rows
   * @param width
   * @param height
   * @param terminalModes
   *
   * @return
   *
   * @throws IOException
   */
  public boolean requestPseudoTerminal(String term, int cols, int rows,
                                       int width, int height,
                                       String terminalModes) throws IOException {

    /*if (log.isDebugEnabled()) {
      log.debug("Terminal Type is " + term);
      log.debug("Columns=" + String.valueOf(cols));
      log.debug("Rows=" + String.valueOf(rows));
      log.debug("Width=" + String.valueOf(width));
      log.debug("Height=" + String.valueOf(height));
    }*/

    // This requests a pseudo terminal
    ByteArrayWriter baw = new ByteArrayWriter();
    baw.writeString(term);
    baw.writeInt(cols);
    baw.writeInt(rows);
    baw.writeInt(width);
    baw.writeInt(height);
    baw.writeString(terminalModes);

    return connection.sendChannelRequest(this, "pty-req", true,
                                         baw.toByteArray());
  }

  /**
   *
   *
   * @param term
   *
   * @return
   *
   * @throws IOException
   */
  public boolean requestPseudoTerminal(PseudoTerminal term) throws IOException {
    return requestPseudoTerminal(term.getTerm(), term.getColumns(),
                                 term.getRows(), term.getWidth(),
                                 term.getHeight(),
                                 term.getEncodedTerminalModes());
  }

  /**
   *
   *
   * @return
   *
   * @throws IOException
   */
  public boolean startShell() throws IOException {

    // Send the request for a shell, we want a reply
    if (connection.sendChannelRequest(this, "shell", true, null)) {
      if (sessionType.equals("Uninitialized")) {
        sessionType = "shell";
      }

      return true;
    }
    else {
      return false;
    }
  }

  /**
   *
   *
   * @param subsystem
   *
   * @return
   *
   * @throws IOException
   */
  public boolean startSubsystem(String subsystem) throws IOException {

    ByteArrayWriter baw = new ByteArrayWriter();

    baw.writeString(subsystem);

    if (connection.sendChannelRequest(this, "subsystem", true,
                                      baw.toByteArray())) {
      if (sessionType.equals("Uninitialized")) {
        sessionType = subsystem;
      }

      return true;
    }
    else {
      return false;
    }
  }

  /**
   *
   *
   * @param subsystem
   *
   * @return
   *
   * @throws IOException
   */
  public boolean startSubsystem(SubsystemClient subsystem) throws IOException {
    boolean result = startSubsystem(subsystem.getName());

    if (result) {
      this.subsystem = subsystem;
      subsystem.setSessionChannel(this);
      subsystem.start();
    }

    return result;
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
      String language = bar.readString();


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
