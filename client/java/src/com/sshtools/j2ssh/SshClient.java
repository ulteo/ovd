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

package com.sshtools.j2ssh;

import java.io.IOException;
import java.net.UnknownHostException;
import java.util.Vector;

import com.sshtools.j2ssh.authentication.AuthenticationProtocolClient;
import com.sshtools.j2ssh.authentication.AuthenticationProtocolState;
import com.sshtools.j2ssh.authentication.SshAuthenticationClient;
import com.sshtools.j2ssh.configuration.SshConnectionProperties;
import com.sshtools.j2ssh.connection.Channel;
import com.sshtools.j2ssh.connection.ChannelEventAdapter;
import com.sshtools.j2ssh.connection.ChannelEventListener;
import com.sshtools.j2ssh.connection.ConnectionProtocol;
import com.sshtools.j2ssh.forwarding.ForwardingClient;
import com.sshtools.j2ssh.net.TransportProvider;
import com.sshtools.j2ssh.net.TransportProviderFactory;
import com.sshtools.j2ssh.session.SessionChannelClient;
import com.sshtools.j2ssh.sftp.SftpSubsystemClient;
import com.sshtools.j2ssh.transport.HostKeyVerification;
import com.sshtools.j2ssh.transport.TransportProtocolClient;
import com.sshtools.j2ssh.transport.TransportProtocolCommon;
import com.sshtools.j2ssh.transport.TransportProtocolState;
import com.sshtools.j2ssh.transport.publickey.SshPublicKey;
import com.sshtools.j2ssh.util.State;

/**
 * <p>
 * Implements an SSH client with methods to connect to a remote server and
 * perform all necersary SSH functions such as SCP, SFTP, executing commands,
 * starting the users shell and perform port forwarding.
 * </p>
 *
 * <p>
 * There are several steps to perform prior to performing the desired task.
 * This involves the making the initial connection, authenticating the user
 * and creating a session to execute a command, shell or subsystem and/or
 * configuring the port forwarding manager.
 * </p>
 *
 * <p>
 * To create a connection use the following code:<br>
 * <blockquote><pre>
 * // Create a instance and connect SshClient
 * ssh = new SshClient();
 * ssh.connect("hostname");
 * </pre></blockquote>
 * Once this code has executed and returned
 * the connection is ready for authentication:<br>
 * <blockquote><pre>
 * PasswordAuthenticationClient pwd = new PasswordAuthenticationClient();
 * pwd.setUsername("foo");
 * pwd.setPassword("xxxxx");
 * // Authenticate the user
 * int result = ssh.authenticate(pwd);
 * if(result==AuthenticationProtocolState.COMPLETED) {
 *    // Authentication complete
 * }
 * </pre></blockquote>
 * Once authenticated the user's shell can be started:<br>
 * <blockquote><pre>
 * // Open a session channel
 * SessionChannelClient session =
 *                      ssh.openSessionChannel();
 *
 * // Request a pseudo terminal, if you do not you may not see the prompt
 * if(session.requestPseudoTerminal("ansi", 80, 24, 0, 0, "") {
 *      // Start the users shell
 *      if(session.startShell()) {
 *         // Do something with the session output
 *         session.getOutputStream().write("echo message\n");
 *         ....
 *       }
 * }
 * </pre></blockquote>
 * </p>
 *
 * @author Lee David Painter
 * @version $Revision: 1.75 $
 *
 * @since 0.2.0
 */
public class SshClient {

  /**
   * The SSH Authentication protocol implementation for this SSH client. The
   * SSH Authentication protocol runs over the SSH Transport protocol as a
   * transport protocol service.
   */
  protected AuthenticationProtocolClient authentication;

  /**
   * The SSH Connection protocol implementation for this SSH client. The
   * connection protocol runs over the SSH Transport protocol as a transport
   * protocol service and is started by the authentication protocol after a
   * successful authentication.
   */
  protected ConnectionProtocol connection;

  /** Provides a high level management interface for SSH port forwarding. */
  protected ForwardingClient forwarding;

  /** The SSH Transport protocol implementation for this SSH Client. */
  protected TransportProtocolClient transport;

  /** The current state of the authentication for the current connection. */
  protected int authenticationState = AuthenticationProtocolState.READY;

  /**
   * The timeout in milliseconds for the underlying transport provider
   * (typically a Socket).
   */
  protected int socketTimeout = 0;

  /**
   * A Transport protocol event handler instance that receives notifications
   * of transport layer events such as Socket timeouts and disconnection.
   */
  protected SshEventAdapter eventHandler = null;

  /** The currently active channels for this SSH Client connection. */
  protected Vector<Channel> activeChannels = new Vector<Channel>();

  /**
   * An channel event listener implemention to maintain the active channel
   * list.
   */
  protected ActiveChannelEventListener activeChannelListener = new
      ActiveChannelEventListener();

  /**
   * Flag indicating whether the forwarding instance is created when the
   * connection is made.
   */
  protected boolean useDefaultForwarding = true;

  /** The currently active Sftp clients */
  private Vector<SftpClient> activeSftpClients = new Vector<SftpClient>();

  /**
   * <p>
   * Contructs an unitilialized SshClient ready for connecting.
   * </p>
   */
  public SshClient() {
  }


  /**
   * <p>
   * Returns the connection state of the client.
   * </p>
   *
   * @return true if the client is connected, false otherwise
   *
   * @since 0.2.0
   */
  public boolean isConnected() {
    State state = (transport == null) ? null : transport.getState();
    int value = (state == null) ? TransportProtocolState.DISCONNECTED
        : state.getValue();

    return ( (value == TransportProtocolState.CONNECTED)
            || (value == TransportProtocolState.PERFORMING_KEYEXCHANGE));
  }

  /**
   * <p>
   * Evaluate whether the client has successfully authenticated.
   * </p>
   *
   * @return true if the client is authenticated, otherwise false
   */
  public boolean isAuthenticated() {
    return authenticationState == AuthenticationProtocolState.COMPLETE;
  }

  /**
   * <p>
   * Returns the identification string sent by the server during protocol
   * negotiation. For example "SSH-2.0-OpenSSH_p3.4".
   * </p>
   *
   * @return The server's identification string.
   *
   * @since 0.2.0
   */
  public String getServerId() {
    return transport.getRemoteId();
  }

  /**
   * <p>
   * Returns the server's public key supplied during key exchange.
   * </p>
   *
   * @return the server's public key
   *
   * @since 0.2.0
   */
  public SshPublicKey getServerHostKey() {
    return transport.getServerHostKey();
  }

  /**
   * <p>
   * Returns the transport protocol's connection state.
   * </p>
   *
   * @return The transport protocol's state
   *
   * @since 0.2.0
   */
  public TransportProtocolState getConnectionState() {
    return transport.getState();
  }

  /**
   * <p>
   * Returns the default port forwarding manager.
   * </p>
   *
   * @return This connection's forwarding client
   *
   * @since 0.2.0
   */
  public ForwardingClient getForwardingClient() {
    return forwarding;
  }

  /**
   * <p>
   * Return's a rough guess at the server's EOL setting. This is simply
   * derived from the identification string and should not be used as a cast
   * iron proof on the EOL setting.
   * </p>
   *
   * @return The transport protocol's EOL constant
   *
   * @since 0.2.0
   */
  public int getRemoteEOL() {
    return transport.getRemoteEOL();
  }

  /**
   * <p>
   * Set the event handler for the underlying transport protocol.
   * </p>
   * <blockquote>
   * <pre>
   * ssh.setEventHandler(new TransportProtocolEventHandler() {
   *
   *   public void onSocketTimeout(TransportProtocol transport) {<br>
   *     // Do something to handle the socket timeout<br>
   *   }
   *
   *   public void onDisconnect(TransportProtocol transport) {
   *     // Perhaps some clean up?
   *   }
   * });
   * </pre>
   * </blockquote>
   *
   * @param eventHandler The event handler instance to receive transport
   *        protocol events
   *
   * @see com.sshtools.j2ssh.transport.TransportProtocolEventHandler
   * @since 0.2.0
   */
  public void addEventHandler(SshEventAdapter eventHandler) {
    // If were connected then add, otherwise store for later connection
    if (transport != null) {
      transport.addEventHandler(eventHandler);
      authentication.addEventListener(eventHandler);
    } else {
      this .eventHandler = eventHandler;
    }
  }

  /**
   * <p>
   * Set's the socket timeout (in milliseconds) for the underlying transport
   * provider. This MUST be called prior to connect.
   * </p>
   * <blockquote>
   * SshClient ssh = new SshClient();
   * ssh.setSocketTimeout(30000);
   * ssh.connect("hostname");
   * </blockquote>
   *
   * @param milliseconds The number of milliseconds without activity before
   *        the timeout event occurs
   *
   * @since 0.2.0
   */
  public void setSocketTimeout(int milliseconds) {
    this.socketTimeout = milliseconds;
  }

  /**
   * <p>
   * Return's a rough guess at the server's EOL setting. This is simply
   * derived from the identification string and should not be used as a cast
   * iron proof on the EOL setting.
   * </p>
   *
   * @return The EOL string
   *
   * @since 0.2.0
   */
  public String getRemoteEOLString() {
    return ( (transport.getRemoteEOL() == TransportProtocolCommon.EOL_CRLF)
            ? "\r\n" : "\n");
  }

  /**
   * Get the connection properties for this connection.
   *
   * @return
   */
  public SshConnectionProperties getConnectionProperties() {
    return transport.getProperties();
  }

  /**
   * <p>
   * Authenticate the user on the remote host.
   * </p>
   *
   * <p>
   * To authenticate the user, create an <code>SshAuthenticationClient</code>
   * instance and configure it with the authentication details.
   * </p>
   * <code> PasswordAuthenticationClient pwd = new
   * PasswordAuthenticationClient(); pwd.setUsername("root");
   * pwd.setPassword("xxxxxxxxx"); int result = ssh.authenticate(pwd);
   * </code>
   *
   * <p>
   * The method returns a result value will one of the public static values
   * defined in <code>AuthenticationProtocolState</code>. These are<br>
   * <br>
   * COMPLETED - The authentication succeeded.<br>
   * PARTIAL   - The authentication succeeded but a further authentication
   * method is required.<br>
   * FAILED    - The authentication failed.<br>
   * CANCELLED - The user cancelled authentication (can only be returned
   * when the user is prompted for information.<br>
   * </p>
   *
   * @param auth A configured SshAuthenticationClient instance ready for
   *        authentication
   *
   * @return The authentication result
   *
   * @exception IOException If an IO error occurs during authentication
   *
   * @since 0.2.0
   */
  public int authenticate(SshAuthenticationClient auth) throws IOException {
    // Do the authentication
    authenticationState = authentication.authenticate(auth, connection);

    if (authenticationState == AuthenticationProtocolState.COMPLETE
        && useDefaultForwarding) {
      // Use some method to synchronize forwardings on the ForwardingClient
      forwarding.synchronizeConfiguration(transport.getProperties());

    }
    return authenticationState;
  }


  /**
   * <p>
   * Connect the client to the server with the specified properties.
   * </p>
   *
   * <p>
   * This call attempts to connect to using the connection properties
   * specified. When this method returns the connection has been
   * established, the server's identity been verified and the connection is
   * ready for user authentication. To use this method first create a
   * properties instance and set the required fields.
   * </p>
   * <blockquote><pre>
   * SshConnectionProperties properties = new
   *                             SshConnectionProperties();
   * properties.setHostname("hostname");
   * properties.setPort(22);             // Defaults to 22
   * // Set the prefered client->server encryption
   * ssh.setPrefCSEncryption("blowfish-cbc");
   * // Set the prefered server->client encrpytion
   * ssh.setPrefSCEncrpyion("3des-cbc");
   * ssh.connect(properties);
   * </pre></blockquote>
   *
   * <p>
   * Host key verification will be performed using the host key verification
   * instance provided:<br>
   * <blockquote><pre>
   * // Connect and consult $HOME/.ssh/known_hosts
   * ssh.connect("hostname", new ConsoleKnownHostsKeyVerification());
   * // Connect and allow any host
   * ssh.connect("hostname", new
   *                 IgnoreHostKeyVerification());
   * </pre></blockquote>
   * You can provide your own host key verification process by implementing the
   * <code>HostKeyVerification</code> interface.
   * </p>
   *
   * @param properties The connection properties
   * @param hostVerification The host key verification instance to consult
   *        for host  key validation
   *
   * @exception UnknownHostException If the host is unknown
   * @exception IOException If an IO error occurs during the connect
   *            operation
   *
   * @since 0.2.0
   */
  public void connect(SshConnectionProperties properties,
                      HostKeyVerification hostVerification) throws
      UnknownHostException, IOException {
    TransportProvider provider = TransportProviderFactory
        .connectTransportProvider(properties,socketTimeout);
    // Start the transport protocol
    transport = new TransportProtocolClient(hostVerification);
    transport.addEventHandler(eventHandler);
    transport.startTransportProtocol(provider, properties);

    // Start the authentication protocol
    authentication = new AuthenticationProtocolClient();
    authentication.addEventListener(eventHandler);

    transport.requestService(authentication);

    connection = new ConnectionProtocol();
    if (useDefaultForwarding) {
      forwarding = new ForwardingClient(connection);
    }
  }

  /**
   * <p>
   * Sets the timeout value for the key exchange.
   * </p>
   *
   * <p>
   * When this time limit is reached the transport protocol will initiate a
   * key re-exchange. The default value is one hour with the minumin timeout
   * being 60 seconds.
   * </p>
   *
   * @param seconds The number of seconds beofre key re-exchange
   *
   * @exception IOException If the timeout value is invalid
   *
   * @since 0.2.0
   */
  public void setKexTimeout(long seconds) throws IOException {
    transport.setKexTimeout(seconds);
  }

  /**
   * <p>
   * Sets the key exchance transfer limit in kilobytes.
   * </p>
   *
   * <p>
   * Once this amount of data has been transfered the transport protocol will
   * initiate a key re-exchange. The default value is one gigabyte of data
   * with the mimimun value of 10 kilobytes.
   * </p>
   *
   * @param kilobytes The data transfer limit in kilobytes
   *
   * @exception IOException If the data transfer limit is invalid
   */
  public void setKexTransferLimit(long kilobytes) throws IOException {
    transport.setKexTransferLimit(kilobytes);
  }

  /**
   * <p>
   * Set's the send ignore flag to send random data packets.
   * </p>
   *
   * <p>
   * If this flag is set to true, then the transport protocol will send
   * additional SSH_MSG_IGNORE packets with random data.
   * </p>
   *
   * @param sendIgnore true if you want to turn on random packet data,
   *        otherwise false
   *
   * @since 0.2.0
   */
  public void setSendIgnore(boolean sendIgnore) {
    transport.setSendIgnore(sendIgnore);
  }

  /**
   * <p>
   * Turn the default forwarding manager on/off.
   * </p>
   *
   * <p>
   * If this flag is set to false before connection, the client will not
   * create a port forwarding manager. Use this to provide you own
   * forwarding implementation.
   * </p>
   *
   * @param useDefaultForwarding Set to false if you not wish to use the
   *        default forwarding manager.
   *
   * @since 0.2.0
   */
  public void setUseDefaultForwarding(boolean useDefaultForwarding) {
    this.useDefaultForwarding = useDefaultForwarding;
  }

  /**
   * <p>
   * Disconnect the client.
   * </p>
   *
   * @since 0.2.0
   */
  public void disconnect() {
    if (connection != null) {
      connection.stop();
    }

    if (transport != null) {
      transport.disconnect("Terminating connection");
    }
  }

  /**
   * <p>
   * Returns the number of bytes transmitted to the remote server.
   * </p>
   *
   * @return The number of bytes transmitted
   *
   * @since 0.2.0
   */
  public long getOutgoingByteCount() {
    return transport.getOutgoingByteCount();
  }

  /**
   * <p>
   * Returns the number of bytes received from the remote server.
   * </p>
   *
   * @return The number of bytes received
   *
   * @since 0.2.0
   */
  public long getIncomingByteCount() {
    return transport.getIncomingByteCount();
  }

  /**
   * <p>
   * Returns the number of active channels for this client.
   * </p>
   *
   * <p>
   * This is the total count of sessions, port forwarding, sftp, scp and
   * custom channels currently open.
   * </p>
   *
   * @return The number of active channels
   *
   * @since 0.2.0
   */
  public int getActiveChannelCount() {
    synchronized (activeChannels) {
      return activeChannels.size();
    }
  }

  /**
   *
   * <p>
   * Open's a session channel on the remote server.
   * </p>
   *
   * <p>
   * A session channel may be used to start the user's shell, execute a
   * command or start a subsystem such as SFTP.
   * </p>
   *
   * @param eventListener an event listner interface to add to the channel
   *
   * @return
   *
   * @throws IOException
   * @throws SshException
   */
  public SessionChannelClient openSessionChannel(
      ChannelEventListener eventListener) throws IOException {
    if (authenticationState != AuthenticationProtocolState.COMPLETE) {
      throw new SshException("Authentication has not been completed!");
    }

    SessionChannelClient session = new SessionChannelClient();

    session.addEventListener(activeChannelListener);

    if (!connection.openChannel(session, eventListener)) {
      throw new SshException("The server refused to open a session");
    }

    return session;
  }

  /**
   * Get an active sftp client
   *
   * @return
   *
   * @throws IOException
   * @throws SshException
   */
  public SftpClient getActiveSftpClient() throws IOException {
    synchronized (activeSftpClients) {
      if (activeSftpClients.size() > 0) {
        return activeSftpClients.get(0);
      }
      else {
        throw new SshException("There are no active SFTP clients");
      }
    }
  }


  /**
   * Open an SftpSubsystemChannel. For advanced use only
   *
   * @param eventListener
   *
   * @return
   *
   * @throws IOException
   * @throws SshException
   * @deprecated Access to this low level API is now deprecated. Use SftpClient
   * instead
   */
  @Deprecated
public SftpSubsystemClient openSftpChannel(
      ChannelEventListener eventListener) throws IOException {
    /*SessionChannelClient session = */openSessionChannel(eventListener);

    SftpSubsystemClient sftp = new SftpSubsystemClient();

    if (!openChannel(sftp)) {
      throw new SshException("The SFTP subsystem failed to start");
    }

// Initialize SFTP
    if (!sftp.initialize())
      throw new SshException("The SFTP Subsystem could not be initialized");

    return sftp;
  }

  /**
   * <p>
   * Open's a channel.
   * </p>
   *
   * <p>
   * Call this method to open a custom channel. This method is used by all
   * other channel opening methods. For example the openSessionChannel
   * method could be implemented as:<br>
   * <blockquote><pre>
   * SessionChannelClient session =
   *                 new SessionChannelClient();
   * if(ssh.openChannel(session)) {
   *    // Channel is now open
   * }
   * </pre></blockquote>
   * </p>
   *
   * @param channel
   *
   * @return true if the channel was opened, otherwise false
   *
   * @exception IOException if an IO error occurs
   * @throws SshException
   *
   * @since 0.2.0
   */
  public boolean openChannel(Channel channel) throws IOException {
    if (authenticationState != AuthenticationProtocolState.COMPLETE) {
      throw new SshException("Authentication has not been completed!");
    }

    // Open the channel providing our channel listener so we can track
    return connection.openChannel(channel, activeChannelListener);
  }

  /**
   * <p>
   * Implements the <code>ChannelEventListener</code> interface to provide
   * real time tracking of active channels.
   * </p>
   */
  class ActiveChannelEventListener
      extends ChannelEventAdapter {
    /**
     * <p>
     * Adds the channel to the active channel list.
     * </p>
     *
     * @param channel The channel being opened
     */
    @Override
	public void onChannelOpen(Channel channel) {
      synchronized (activeChannels) {
        activeChannels.add(channel);
      }
    }

    /**
     * <p>
     * Removes the closed channel from the clients active channels list.
     * </p>
     *
     * @param channel The channle being closed
     */
    @Override
	public void onChannelClose(Channel channel) {
      synchronized (activeChannels) {
        activeChannels.remove(channel);
      }
    }
  }

}
