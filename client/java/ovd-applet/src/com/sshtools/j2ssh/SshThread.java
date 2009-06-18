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

import java.util.HashMap;

import com.sshtools.j2ssh.configuration.ConfigurationLoader;

/**
 * <p>
 * Enables the J2SSH application framework to execute threads in the context of
 * a given session.
 * </p>
 *
 * @author Lee David Painter
 * @version $Revision: 1.25 $
 *
 * @since 0.2.0
 */
public class SshThread
    extends Thread {
  private static HashMap<String, Integer> names = new HashMap <String, Integer>();

  /** The raw session id generating during the first key exchange. */
  protected byte[] sessionId;

  /** A string representation of the session id. */
  protected String sessionIdString = null;

  /** The thread owner */
  protected String username;


  /**
   * <p>
   * Constructs an SshThread.
   * </p>
   *
   * @param target The target to execute
   * @param name The name of the thread
   * @param daemon run as a daemon thread?
   *
   * @since 0.2.0
   */
  public SshThread(Runnable target, String name, boolean daemon) {
    super(target);
    setProperties(name, daemon);
  }

  private void setProperties(String name, boolean daemon) {
    Integer i;

    if (names.containsKey(name)) {
      i = new Integer( (names.get(name)).intValue() + 1);
    }
    else {
      i = new Integer(1);
    }

    names.put(name, i);
    setName(name + " " + Integer.toHexString(i.intValue() & 0xFF));
    setDaemon(daemon);

    if (ConfigurationLoader.isContextClassLoader()) {
      setContextClassLoader(ConfigurationLoader.getContextClassLoader());
    }
  }

  /**
   * <p>
   * Sets the session id for this thread.
   * </p>
   *
   * @param sessionId the session id created during the first key exchange.
   *
   * @since 0.2.0
   */
  public void setSessionId(byte[] sessionId) {
    if (sessionId != null) {
      this.sessionId = new byte[sessionId.length];
      System.arraycopy(sessionId, 0, this.sessionId, 0, sessionId.length);
      sessionIdString = String.valueOf(new String(sessionId).hashCode()
                                       & 0xFFFFFFFFL);
    }
  }

  /**
   * <p>
   * Returns the session id string for this thread.
   * </p>
   *
   * @return a string representation of the session id
   *
   * @since 0.2.0
   */
  public String getSessionIdString() {
    return sessionIdString;
  }

  /**
   * <p>
   * Set the username for this thread.
   * </p>
   *
   * @param username the thread owner
   *
   * @since 0.2.0
   */
  public void setUsername(String username) {
    this.username = username;
  }

  /**
   * <p>
   * Gets the username for this thread.
   * </p>
   *
   * @return the thread owner
   *
   * @since 0.2.0
   */
  public String getUsername() {
    return username;
  }

//  /**
//   * <p>
//   * Create's a cloned copy of this thread with the given target and name.
//   * </p>
//   *
//   * @param target the target to execute
//   * @param name the thread name
//   *
//   * @return the cloned thread
//   *
//   * @since 0.2.0
//   */
//  public SshThread cloneThread(Runnable target, String name) {
//    SshThread thread = new SshThread(target, name, isDaemon());
//    thread.setSessionId(sessionId);
//    thread.setUsername(username);
//    thread.settings.putAll(settings);
//
//    return thread;
//  }

//  /**
//   * <p>
//   * Sets a property in the thread.
//   * </p>
//   *
//   * @param name the name of the property
//   * @param value the property value
//   *
//   * @since 0.2.0
//   */
//  public void setProperty(String name, Object value) {
//    settings.put(name, value);
//  }

}
