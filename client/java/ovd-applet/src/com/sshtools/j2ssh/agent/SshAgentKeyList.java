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

package com.sshtools.j2ssh.agent;

import java.io.IOException;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;
import java.util.Map.Entry;

import com.sshtools.j2ssh.io.ByteArrayReader;
import com.sshtools.j2ssh.io.ByteArrayWriter;
import com.sshtools.j2ssh.subsystem.SubsystemMessage;
import com.sshtools.j2ssh.transport.InvalidMessageException;
import com.sshtools.j2ssh.transport.publickey.SshKeyPairFactory;
import com.sshtools.j2ssh.transport.publickey.SshPublicKey;

class SshAgentKeyList
    extends SubsystemMessage {
  /**  */
  public static final int SSH_AGENT_KEY_LIST = 104;
  private Map<SshPublicKey, String> keys;

  /**
   * Creates a new SshAgentKeyList object.
   *
   * @param keys
   */
  public SshAgentKeyList(Map<SshPublicKey, String> keys) {
    super(SSH_AGENT_KEY_LIST);
    this.keys = keys;
  }

  /**
   * Creates a new SshAgentKeyList object.
   */
  public SshAgentKeyList() {
    super(SSH_AGENT_KEY_LIST);
    this.keys = new HashMap<SshPublicKey, String>();
  }

  /**
   *
   *
   * @return
   */
  public Map<SshPublicKey, String> getKeys() {
    return keys;
  }

  /**
   *
   *
   * @return
   */
  @Override
public String getMessageName() {
    return "SSH_AGENT_KEY_LIST";
  }

  /**
   *
   *
   * @param baw
   *
   * @throws java.io.IOException
   * @throws com.sshtools.j2ssh.transport.InvalidMessageException DOCUMENT
   *         ME!
   * @throws InvalidMessageException
   */
  @Override
public void constructByteArray(ByteArrayWriter baw) throws java.io.IOException,
      com.sshtools.j2ssh.transport.InvalidMessageException {
    try {
      baw.writeInt(keys.size());

      Map.Entry<SshPublicKey, String> entry;
      Iterator<Entry<SshPublicKey, String>> it = keys.entrySet().iterator();
      SshPublicKey key;
      String description;

      while (it.hasNext()) {
        entry = it.next();
        key = entry.getKey();
        description = entry.getValue();
        baw.writeBinaryString(key.getEncoded());
        baw.writeString(description);
      }
    }
    catch (IOException ex) {
      throw new InvalidMessageException("Failed to write message data");
    }
  }

  /**
   *
   *
   * @param bar
   *
   * @throws java.io.IOException
   * @throws com.sshtools.j2ssh.transport.InvalidMessageException DOCUMENT
   *         ME!
   * @throws InvalidMessageException
   */
  @Override
public void constructMessage(ByteArrayReader bar) throws java.io.IOException,
      com.sshtools.j2ssh.transport.InvalidMessageException {
    try {
      int num = (int) bar.readInt();
      SshPublicKey key;
      String description;
      byte[] buf;

      for (int i = 0; i < num; i++) {
        buf = bar.readBinaryString();
        key = SshKeyPairFactory.decodePublicKey(buf);
        description = bar.readString();
        keys.put(key, description);
      }
    }
    catch (IOException ex) {
      throw new InvalidMessageException("Failed to read message data");
    }
  }
}
