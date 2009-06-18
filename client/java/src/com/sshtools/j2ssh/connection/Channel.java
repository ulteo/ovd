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
import java.util.Iterator;
import java.util.Vector;

import org.vnc.PopupError;


/**
 *
 *
 * @author $author$
 * @version $Revision: 1.74 $
 */
public abstract class Channel {

  /**  */
  protected ChannelDataWindow localWindow = new ChannelDataWindow();

  /**  */
  protected ChannelDataWindow remoteWindow = new ChannelDataWindow();

  /**  */
  protected ConnectionProtocol connection;

  /**  */
  protected long localChannelId;

  /**  */
  protected long localPacketSize;

  /**  */
  protected long remoteChannelId;

  /**  */
  protected long remotePacketSize;

  /**  */
  protected ChannelState state = new ChannelState();
  //private boolean isClosed = false;
  private boolean isLocalEOF = false;
  private boolean isRemoteEOF = false;
  private boolean localHasClosed = false;
  private boolean remoteHasClosed = false;
  private String name = "Unnamed Channel";
  private Vector<ChannelEventListener> eventListeners = new Vector<ChannelEventListener>();

  /**
   * Creates a new Channel object.
   */
  public Channel() {
    this.localPacketSize = getMaximumPacketSize();
    this.localWindow.increaseWindowSpace(getMaximumWindowSpace());
  }

  /**
   *
   *
   * @return
   */
  public abstract byte[] getChannelOpenData();

  /**
   *
   *
   * @return
   */
  public abstract byte[] getChannelConfirmationData();

  /**
   *
   *
   * @return
   */
  public abstract String getChannelType();

  /**
   *
   *
   * @return
   */
  protected abstract int getMinimumWindowSpace();

  /**
   *
   *
   * @return
   */
  protected abstract int getMaximumWindowSpace();

  /**
   *
   *
   * @return
   */
  protected abstract int getMaximumPacketSize();

  /**
   *
   *
   * @param msg
   *
   * @throws IOException
   */
  protected abstract void onChannelData(SshMsgChannelData msg) throws
      IOException;

  /**
   *
   *
   * @param msg
   *
   * @throws IOException
   */
  protected void processChannelData(SshMsgChannelData msg) throws IOException {
    synchronized (state) {
      if (!isClosed()) {
        if (msg.getChannelDataLength() > localWindow.getWindowSpace()) {
          throw new IOException(
              "More data recieved than is allowed by the channel data window ["
              + name + "]");
        }

        long windowSpace = localWindow.consumeWindowSpace(msg
            .getChannelData().length);

        if (windowSpace < getMinimumWindowSpace()) {
          /*if (log.isDebugEnabled()) {
            log.debug("Channel " + String.valueOf(localChannelId)
                      + " requires more window space [" + name + "]");
          }*/

          windowSpace = getMaximumWindowSpace() - windowSpace;

          connection.sendChannelWindowAdjust(this, windowSpace);
          localWindow.increaseWindowSpace(windowSpace);
        }

        onChannelData(msg);

        Iterator<ChannelEventListener> it = eventListeners.iterator();
        ChannelEventListener eventListener;

        while (it.hasNext()) {
	  try{
           eventListener = it.next();
           if (eventListener != null) {
             eventListener.onDataReceived(this, msg.getChannelData());
           }
  	  }catch(Throwable cme){
	    System.err.println(cme.getMessage());
	    cme.printStackTrace();
	  }

        }
      }
      else {
        throw new IOException(
            "Channel data received but channel is closed [" + name
            + "]");
      }
    }
  }

  /**
   *
   *
   * @return
   */
  public boolean isClosed() {
    synchronized (state) {
      return state.getValue() == ChannelState.CHANNEL_CLOSED;
    }
  }

  /**
   *
   *
   * @return
   */
  public boolean isOpen() {
    synchronized (state) {
      return state.getValue() == ChannelState.CHANNEL_OPEN;
    }
  }

  /**
   *
   *
   * @param data
   *
   * @throws IOException
   */
  protected final void sendChannelData(byte[] data) throws IOException {
   synchronized(state){
     if (!connection.isConnected()) {
    	 	IOException e = new IOException();
			String message = "The connection has been closed [" + name + "]";
			String caption = "Connection error";
			PopupError.showError(null, message, caption, e);
      throw e;
     }

     if (!isClosed()) {
      connection.sendChannelData(this, data);

//		K1ZFP removed for ulteo.      
//      Iterator<ChannelEventListener> it = eventListeners.iterator();
//      ChannelEventListener eventListener;
//
//      while (it.hasNext()) {
//        eventListener = it.next();
//
//        if (eventListener != null) {
//          eventListener.onDataSent(this, data);
//        }
//      }
     }
     else {
 	 	IOException e = new IOException();
		String message = "The connection has been closed [" + name + "]";
		String caption = "Connection error";
		PopupError.showError(null, message, caption, e);
		throw e;
     }
   }
  }

  /**
   *
   *
   * @param msg
   *
   * @throws IOException
   */
  protected abstract void onChannelExtData(SshMsgChannelExtendedData msg) throws
      IOException;

  /**
   *
   *
   * @param msg
   *
   * @throws IOException
   */
  protected void processChannelData(SshMsgChannelExtendedData msg) throws
      IOException {
    synchronized (state) {
      if (msg.getChannelData().length > localWindow.getWindowSpace()) {
        throw new IOException(
            "More data recieved than is allowed by the channel data window ["
            + name + "]");
      }

      long windowSpace = localWindow.consumeWindowSpace(msg
          .getChannelData().length);

      if (windowSpace < getMinimumWindowSpace()) {
        /*if (log.isDebugEnabled()) {
          log.debug("Channel " + String.valueOf(localChannelId)
                    + " requires more window space [" + name + "]");
        }*/

        windowSpace = getMaximumWindowSpace() - windowSpace;
        connection.sendChannelWindowAdjust(this, windowSpace);
        localWindow.increaseWindowSpace(windowSpace);
      }

      onChannelExtData(msg);

      Iterator<ChannelEventListener> it = eventListeners.iterator();
      ChannelEventListener eventListener;

      while (it.hasNext()) {
	try{
        eventListener = it.next();

         if (eventListener != null) {
           eventListener.onDataReceived(this, msg.getChannelData());
         }
	}catch(Throwable cme){
	    System.err.println(cme.getMessage());
	    cme.printStackTrace();
	}
      }
    }
  }

  /**
   *
   *
   * @return
   */
  public long getLocalChannelId() {
    return localChannelId;
  }

  /**
   *
   *
   * @return
   */
  public long getLocalPacketSize() {
    return localPacketSize;
  }

  /**
   *
   *
   * @return
   */
  public ChannelDataWindow getLocalWindow() {
    return localWindow;
  }

  /**
   *
   *
   * @return
   */
  public long getRemoteChannelId() {
    return remoteChannelId;
  }

  /**
   *
   *
   * @return
   */
  public long getRemotePacketSize() {
    return remotePacketSize;
  }

  /**
   *
   *
   * @return
   */
  public ChannelDataWindow getRemoteWindow() {
    return remoteWindow;
  }

  /**
   *
   *
   * @return
   */
  public ChannelState getState() {
    return state;
  }

  /**
   *
   *
   * @throws IOException
   */
  public void close() throws IOException {
    synchronized (state) {
      if (isOpen()) {
        if ( (connection != null) && !localHasClosed
            && connection.isConnected()) {
          connection.closeChannel(this);
        }

        localHasClosed = true;

        /*if (log.isDebugEnabled()) {
          log.debug("Connection is "
                    + ( (connection == null) ? "null"
                       : (connection.isConnected()
                          ? "connected" : "not connected")));
        }*/

        if (remoteHasClosed
            || ( (connection == null) || !connection.isConnected())) {
          finalizeClose();
        }
      }
    }
  }

  /**
   *
   *
   * @throws IOException
   */
  protected void remoteClose() throws IOException {

    synchronized (state) {
      remoteHasClosed = true;
      close();
    }
  }

  /**
   *
   *
   * @throws IOException
   */
  protected void finalizeClose() throws IOException {
    synchronized (state) {
      state.setValue(ChannelState.CHANNEL_CLOSED);

      onChannelClose();

      Iterator<ChannelEventListener> it = eventListeners.iterator();
      ChannelEventListener eventListener;

      while (it.hasNext()) {
        eventListener = it.next();

        if (eventListener != null) {
          eventListener.onChannelClose(this);
        }
      }

      if (connection != null) {
        connection.freeChannel(this);
      }
    }
  }

  /**
   *
   *
   * @throws IOException
   */
  public void setLocalEOF() throws IOException {
    synchronized (state) {
      isLocalEOF = true;
      connection.sendChannelEOF(this);
    }
  }

  /**
   *
   *
   * @return
   */
  public boolean isLocalEOF() {
    return isLocalEOF;
  }

  /**
   *
   *
   * @return
   */
  public boolean isRemoteEOF() {
    return isRemoteEOF;
  }

  /**
   *
   *
   * @throws IOException
   */
  protected void setRemoteEOF() throws IOException {
    synchronized (state) {
      isRemoteEOF = true;

      onChannelEOF();

      Iterator<ChannelEventListener> it = eventListeners.iterator();
      ChannelEventListener eventListener;

      while (it.hasNext()) {
        eventListener = it.next();

        if (eventListener != null) {
          eventListener.onChannelEOF(this);
        }
      }
    }
  }

  /**
   *
   *
   * @param eventListener
   */
  public void addEventListener(ChannelEventListener eventListener) {
    eventListeners.add(eventListener);
  }

  /**
   *
   *
   * @param connection
   * @param localChannelId
   * @param senderChannelId
   * @param initialWindowSize
   * @param maximumPacketSize
   *
   * @throws IOException
   */
  protected void init(ConnectionProtocol connection, long localChannelId,
                      long senderChannelId, long initialWindowSize,
                      long maximumPacketSize) throws IOException {
    this.localChannelId = localChannelId;
    this.remoteChannelId = senderChannelId;
    this.remotePacketSize = maximumPacketSize;
    this.remoteWindow.increaseWindowSpace(initialWindowSize);
    this.connection = connection;

    synchronized (state) {
      state.setValue(ChannelState.CHANNEL_OPEN);
    }
  }

  /**
   *
   *
   * @throws IOException
   */
  protected void open() throws IOException {
    synchronized (state) {
      state.setValue(ChannelState.CHANNEL_OPEN);

      onChannelOpen();

      Iterator<ChannelEventListener> it = eventListeners.iterator();
      ChannelEventListener eventListener;

      while (it.hasNext()) {
        eventListener =  it.next();

        if (eventListener != null) {
          eventListener.onChannelOpen(this);
        }
      }
    }
  }

  /**
   *
   *
   * @param connection
   * @param localChannelId
   * @param senderChannelId
   * @param initialWindowSize
   * @param maximumPacketSize
   * @param eventListener
   *
   * @throws IOException
   */
  protected void init(ConnectionProtocol connection, long localChannelId,
                      long senderChannelId, long initialWindowSize,
                      long maximumPacketSize,
                      ChannelEventListener eventListener) throws IOException {
    if (eventListener != null) {
      addEventListener(eventListener);
    }

    init(connection, localChannelId, senderChannelId, initialWindowSize,
         maximumPacketSize);
  }

  /**
   *
   *
   * @throws IOException
   */
  protected abstract void onChannelClose() throws IOException;

  /**
   *
   *
   * @throws IOException
   */
  protected abstract void onChannelEOF() throws IOException;

  /**
   *
   *
   * @throws IOException
   */
  protected abstract void onChannelOpen() throws IOException;

  /**
   *
   *
   * @param requestType
   * @param wantReply
   * @param requestData
   *
   * @throws IOException
   */
  protected abstract void onChannelRequest(String requestType,
                                           boolean wantReply,
                                           byte[] requestData) throws
      IOException;

  /**
   *
   *
   * @param name
   */
  public void setName(String name) {
    this.name = name;
  }

  /**
   *
   *
   * @return
   */
  public String getName() {
    return name;
  }
}
