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

package com.sshtools.j2ssh.forwarding;

import java.io.IOException;
import java.net.Socket;
import java.net.SocketPermission;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;

import com.sshtools.j2ssh.configuration.SshConnectionProperties;
import com.sshtools.j2ssh.connection.Channel;
import com.sshtools.j2ssh.connection.ChannelFactory;
import com.sshtools.j2ssh.connection.ConnectionProtocol;
import com.sshtools.j2ssh.connection.InvalidChannelException;
import com.sshtools.j2ssh.io.ByteArrayReader;
import com.sshtools.j2ssh.io.ByteArrayWriter;
import com.sshtools.j2ssh.util.StartStopState;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.37 $
 */
public class ForwardingClient
    implements ChannelFactory {

  /**  */
  public final static String REMOTE_FORWARD_REQUEST = "tcpip-forward";

  private ConnectionProtocol connection;
  //private List channelTypes = new Vector();
  private Map<String, ForwardingConfiguration> localForwardings = new HashMap<String, ForwardingConfiguration>();
  private Map<String, ForwardingConfiguration> remoteForwardings = new HashMap<String, ForwardingConfiguration>();
  private XDisplay xDisplay;
  private ForwardingConfiguration x11ForwardingConfiguration;

  /**
   * Creates a new ForwardingClient object.
   *
   * @param connection
   *
   * @throws IOException
   */
  public ForwardingClient(ConnectionProtocol connection) throws IOException {
    this.connection = connection;

    //channelTypes.add(ForwardingSocketChannel.REMOTE_FORWARDING_CHANNEL);
    connection.addChannelFactory(ForwardingChannel.
                                 REMOTE_FORWARDING_CHANNEL,
                                 this);
    connection.addChannelFactory(ForwardingChannel.X11_FORWARDING_CHANNEL,
                                 this);
  }

//  /**
//   *
//   *
//   * @return
//   */
//  public List getChannelType() {
//    return channelTypes;
//  }

  /**
   *
   *
   * @return
   */
  public ForwardingConfiguration getX11ForwardingConfiguration() {
    return x11ForwardingConfiguration;
  }


  public void synchronizeConfiguration(SshConnectionProperties properties) {
    ForwardingConfiguration fwd = null;

    if (properties.getLocalForwardings().size() > 0) {
      for (Iterator<ForwardingConfiguration> it = properties.getLocalForwardings().values().iterator();
           it.hasNext(); ) {
        try {
          fwd = it.next();
          fwd = addLocalForwarding(fwd);
          if (properties.getForwardingAutoStartMode()) {
            startLocalForwarding(fwd.getName());
          }
        }
        catch (Throwable ex) {
          //log.warn("Failed to start local forwarding " + fwd != null ?
            //       fwd.getName() : "", ex);
        }

      }
    }

    if (properties.getRemoteForwardings().size() > 0) {
      for (Iterator<ForwardingConfiguration> it = properties.getRemoteForwardings().
           values().iterator();
           it.hasNext(); ) {
        try {
          fwd = it.next();
          addRemoteForwarding(fwd);
          if (properties.getForwardingAutoStartMode()) {
            startRemoteForwarding(fwd.getName());
          }
        }
        catch (Throwable ex) {
          //log.warn("Failed to start remote forwarding " + fwd != null ?
            //       fwd.getName() : "", ex);
        }

      }
    }

  }

  /**
   *
   *
   * @return
   */
  public Map<String, ForwardingConfiguration> getLocalForwardings() {
    return localForwardings;
  }

  /**
   *
   *
   * @return
   */
  public Map<String, ForwardingConfiguration> getRemoteForwardings() {
    return remoteForwardings;
  }

  /**
   *
   *
   * @param addressToBind
   * @param portToBind
   *
   * @return
   *
   * @throws ForwardingConfigurationException
   */
  public ForwardingConfiguration getRemoteForwardingByAddress(
      String addressToBind, int portToBind) throws
      ForwardingConfigurationException {
    Iterator<ForwardingConfiguration> it = remoteForwardings.values().iterator();
    ForwardingConfiguration config;

    while (it.hasNext()) {
      config = it.next();

      if (config.getAddressToBind().equals(addressToBind)
          && (config.getPortToBind() == portToBind)) {
        return config;
      }
    }

    throw new ForwardingConfigurationException(
        "The configuration does not exist");
  }

  /**
   *
   *
   * @param uniqueName
   * @param addressToBind
   * @param portToBind
   * @param hostToConnect
   * @param portToConnect
   *
   * @return
   *
   * @throws ForwardingConfigurationException
   */
  public ForwardingConfiguration addLocalForwarding(String uniqueName,
      String addressToBind, int portToBind, String hostToConnect,
      int portToConnect) throws ForwardingConfigurationException {
    // Check that the name does not exist
    if (localForwardings.containsKey(uniqueName)) {
      throw new ForwardingConfigurationException(
          "The configuration name already exists!");
    }

    // Check that the address to bind and port are not already being used
    Iterator<ForwardingConfiguration> it = localForwardings.values().iterator();
    ForwardingConfiguration config;

    while (it.hasNext()) {
      config = it.next();

      if (config.getAddressToBind().equals(addressToBind)
          && (config.getPortToBind() == portToBind)) {
        throw new ForwardingConfigurationException(
            "The address and port are already in use");
      }
    }

    // Check the security mananger
    SecurityManager manager = System.getSecurityManager();

    if (manager != null) {
      try {
        manager.checkPermission(new SocketPermission(addressToBind
            + ":" + String.valueOf(portToBind), "accept,listen"));
      }
      catch (SecurityException e) {
        throw new ForwardingConfigurationException(
            "The security manager has denied listen permision on "
            + addressToBind + ":" + String.valueOf(portToBind));
      }
    }

    // Create the configuration object
    ForwardingConfiguration cf = new ClientForwardingListener(uniqueName,
        connection, addressToBind, portToBind, hostToConnect,
        portToConnect);

    localForwardings.put(uniqueName, cf);

    return cf;
  }

  /**
   *
   *
   * @param fwd
   *
   * @return
   *
   * @throws ForwardingConfigurationException
   */
  public ForwardingConfiguration addLocalForwarding(
      ForwardingConfiguration fwd) throws ForwardingConfigurationException {
    return addLocalForwarding(fwd.getName(), fwd.getAddressToBind(),
                              fwd.getPortToBind(), fwd.getHostToConnect(),
                              fwd.getPortToConnect());

    /*     // Check that the name does not exist
          if (localForwardings.containsKey(fwd.getName())) {
     throw new ForwardingConfigurationException(
         "The configuration name already exists!");
          }
          // Check that the address to bind and port are not already being used
          Iterator it = localForwardings.values().iterator();
          ForwardingConfiguration config;
          while (it.hasNext()) {
     config = (ForwardingConfiguration) it.next();
     if (config.getAddressToBind().equals(fwd.getAddressToBind())
             && (config.getPortToBind() == fwd.getPortToBind())) {
         throw new ForwardingConfigurationException(
             "The address and port are already in use");
     }
          }
          // Check the security mananger
          SecurityManager manager = System.getSecurityManager();
          if (manager != null) {
     try {
         manager.checkPermission(new SocketPermission(fwd
                 .getAddressToBind() + ":"
                 + String.valueOf(fwd.getPortToBind()), "accept,listen"));
     } catch (SecurityException e) {
         throw new ForwardingConfigurationException(
             "The security manager has denied listen permision on "
             + fwd.getAddressToBind() + ":"
             + String.valueOf(fwd.getPortToBind()));
     }
          }
          // Create the configuration object
          localForwardings.put(fwd.getName(),
     new ClientForwardingListener(fwd.getName(), connection,
         fwd.getAddressToBind(), fwd.getPortToBind(),
         fwd.getHostToConnect(), fwd.getPortToConnect()));*/
  }

  /**
   *
   *
   * @param fwd
   *
   * @throws ForwardingConfigurationException
   */
  public void addRemoteForwarding(ForwardingConfiguration fwd) throws
      ForwardingConfigurationException {
    // Check that the name does not exist
    if (remoteForwardings.containsKey(fwd.getName())) {
      throw new ForwardingConfigurationException(
          "The remote forwaring configuration name already exists!");
    }

    // Check that the address to bind and port are not already being used
    Iterator<ForwardingConfiguration> it = remoteForwardings.values().iterator();
    ForwardingConfiguration config;

    while (it.hasNext()) {
      config =  it.next();

      if (config.getAddressToBind().equals(fwd.getAddressToBind())
          && (config.getPortToBind() == fwd.getPortToBind())) {
        throw new ForwardingConfigurationException(
            "The remote forwarding address and port are already in use");
      }
    }

    // Check the security mananger
    SecurityManager manager = System.getSecurityManager();

    if (manager != null) {
      try {
        manager.checkPermission(new SocketPermission(fwd
            .getHostToConnect() + ":"
            + String.valueOf(fwd.getPortToConnect()), "connect"));
      }
      catch (SecurityException e) {
        throw new ForwardingConfigurationException(
            "The security manager has denied connect permision on "
            + fwd.getHostToConnect() + ":"
            + String.valueOf(fwd.getPortToConnect()));
      }
    }

    // Create the configuration object
    remoteForwardings.put(fwd.getName(), fwd);
  }

  /**
   *
   *
   * @param channelType
   * @param requestData
   *
   * @return
   *
   * @throws InvalidChannelException
   */
  public Channel createChannel(String channelType, byte[] requestData) throws
      InvalidChannelException {
    if (channelType.equals(ForwardingChannel.X11_FORWARDING_CHANNEL)) {
      if (xDisplay == null) {
        throw new InvalidChannelException(
            "Local display has not been set for X11 forwarding.");
      }

      try {
        ByteArrayReader bar = new ByteArrayReader(requestData);
        String originatingHost = bar.readString();
        int originatingPort = (int) bar.readInt();
        /*log.debug("Creating socket to "
                  + x11ForwardingConfiguration.getHostToConnect() + "/"
                  + x11ForwardingConfiguration.getPortToConnect());*/

        Socket socket = new Socket(x11ForwardingConfiguration
                                   .getHostToConnect(),
                                   x11ForwardingConfiguration.getPortToConnect());

        // Create the channel adding it to the active channels
        ForwardingSocketChannel channel = x11ForwardingConfiguration
            .createForwardingSocketChannel(channelType,
                                           x11ForwardingConfiguration.
                                           getHostToConnect(),
                                           x11ForwardingConfiguration.
                                           getPortToConnect(),
                                           originatingHost, originatingPort);

        channel.bindSocket(socket);
        channel.addEventListener(x11ForwardingConfiguration.monitor);

        return channel;
      }
      catch (IOException ioe) {
        throw new InvalidChannelException(ioe.getMessage());
      }
    }

    if (channelType.equals(
        ForwardingChannel.REMOTE_FORWARDING_CHANNEL)) {
      try {
        ByteArrayReader bar = new ByteArrayReader(requestData);
        String addressBound = bar.readString();
        int portBound = (int) bar.readInt();
        String originatingHost = bar.readString();
        int originatingPort = (int) bar.readInt();

        ForwardingConfiguration config = getRemoteForwardingByAddress(
            addressBound,
            portBound);

        Socket socket = new Socket(config.getHostToConnect(),
                                   config.getPortToConnect());

        /*Socket socket = new Socket();
         socket.connect(new InetSocketAddress(
             config.getHostToConnect(), config.getPortToConnect()));*/

        // Create the channel adding it to the active channels
        ForwardingSocketChannel channel = config
            .createForwardingSocketChannel(channelType,
                                           config.getHostToConnect(),
                                           config.getPortToConnect(),
                                           originatingHost, originatingPort);

        channel.bindSocket(socket);
        channel.addEventListener(config.monitor);

        return channel;
      }
      catch (ForwardingConfigurationException fce) {
        throw new InvalidChannelException(
            "No valid forwarding configuration was available for the request address");
      }
      catch (IOException ioe) {
        throw new InvalidChannelException(ioe.getMessage());
      }
    }

    throw new InvalidChannelException(
        "The server can only request a remote forwarding channel or an"
        + "X11 forwarding channel");
  }

  /**
   *
   *
   * @param uniqueName
   *
   * @throws ForwardingConfigurationException
   */
  public void startLocalForwarding(String uniqueName) throws
      ForwardingConfigurationException {
    if (!localForwardings.containsKey(uniqueName)) {
      throw new ForwardingConfigurationException(
          "The name is not a valid forwarding configuration");
    }

    try {
      ForwardingListener listener = (ForwardingListener) localForwardings
          .get(uniqueName);

      listener.start();
    }
    catch (IOException ex) {
      throw new ForwardingConfigurationException(ex.getMessage());
    }
  }

  /**
   *
   *
   * @param name
   *
   * @throws IOException
   * @throws ForwardingConfigurationException
   */
  public void startRemoteForwarding(String name) throws IOException,
      ForwardingConfigurationException {
    if (!remoteForwardings.containsKey(name)) {
      throw new ForwardingConfigurationException(
          "The name is not a valid forwarding configuration");
    }

    ForwardingConfiguration config = remoteForwardings.get(name);

    ByteArrayWriter baw = new ByteArrayWriter();
    baw.writeString(config.getAddressToBind());
    baw.writeInt(config.getPortToBind());

    connection.sendGlobalRequest(REMOTE_FORWARD_REQUEST, true,
                                 baw.toByteArray());
    remoteForwardings.put(name, config);

    config.getState().setValue(StartStopState.STARTED);


    /*if (log.isDebugEnabled()) {
      log.debug("Address to bind: " + config.getAddressToBind());
      log.debug("Port to bind: " + String.valueOf(config.getPortToBind()));
      log.debug("Host to connect: " + config.hostToConnect);
      log.debug("Port to connect: " + config.portToConnect);
    }*/
  }


  public class ClientForwardingListener
      extends ForwardingListener {
    public ClientForwardingListener(String name,
                                    ConnectionProtocol connection,
                                    String addressToBind,
                                    int portToBind, String hostToConnect,
                                    int portToConnect) {
      super(name, connection, addressToBind, portToBind, hostToConnect,
            portToConnect);
    }

    @Override
	public ForwardingSocketChannel createChannel(String hostToConnect,
                                                 int portToConnect,
                                                 Socket socket) throws
        ForwardingConfigurationException {
      return createForwardingSocketChannel(ForwardingChannel.
                                           LOCAL_FORWARDING_CHANNEL,
                                           hostToConnect, portToConnect,
                                           /*( (InetSocketAddress) socket.
           getRemoteSocketAddress()).getAddress()
                                           .getHostAddress()*/
                                           socket.getInetAddress().
                                           getHostAddress(),
                                           /*( (InetSocketAddress) socket.
           getRemoteSocketAddress()).getPort()*/
                                           socket.getPort());
    }
  }
}
