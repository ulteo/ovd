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
import java.util.Vector;

import com.sshtools.j2ssh.transport.publickey.InvalidSshKeyException;
import com.sshtools.j2ssh.transport.publickey.InvalidSshKeySignatureException;
import com.sshtools.j2ssh.transport.publickey.SshPrivateKey;
import com.sshtools.j2ssh.transport.publickey.SshPublicKey;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.15 $
 */
public class KeyStore {
  HashMap<SshPublicKey, String> publickeys = new HashMap<SshPublicKey, String>();
  HashMap<SshPublicKey, SshPrivateKey> privatekeys = new HashMap<SshPublicKey, SshPrivateKey>();
  HashMap<SshPublicKey, KeyConstraints> constraints = new HashMap<SshPublicKey, KeyConstraints>();
  Vector<SshPublicKey> index = new Vector<SshPublicKey>();
  Vector<KeyStoreListener> listeners = new Vector<KeyStoreListener>();
  String lockedPassword = null;

  /**
   * Creates a new KeyStore object.
   */
  public KeyStore() {
  }

  /**
   *
   *
   * @return
   */
  public Map<SshPublicKey, String> getPublicKeys() {
    return (Map<SshPublicKey, String>) publickeys.clone();
  }


  /**
   *
   *
   * @param prvkey
   * @param pubkey
   * @param description
   * @param cs
   *
   * @return
   *
   * @throws IOException
   */
  public boolean addKey(SshPrivateKey prvkey, SshPublicKey pubkey,
                        String description, KeyConstraints cs) throws
      IOException {
    synchronized (publickeys) {
      if (!publickeys.containsKey(pubkey)) {
        publickeys.put(pubkey, description);
        privatekeys.put(pubkey, prvkey);
        constraints.put(pubkey, cs);
        index.add(pubkey);

        Iterator<KeyStoreListener> it = listeners.iterator();
        KeyStoreListener listener;

        while (it.hasNext()) {
          listener = it.next();
          listener.onAddKey(this);
        }

        return true;
      }
      else {
        return false;
      }
    }
  }

  /**
   *
   */
  public void deleteAllKeys() {
    synchronized (publickeys) {
      publickeys.clear();
      privatekeys.clear();
      constraints.clear();
      index.clear();

      Iterator<KeyStoreListener> it = listeners.iterator();
      KeyStoreListener listener;

      while (it.hasNext()) {
        listener = it.next();
        listener.onDeleteAllKeys(this);
      }
    }
  }

  /**
   *
   *
   * @param pubkey
   * @param forwardingNodes
   * @param data
   *
   * @return
   *
   * @throws KeyTimeoutException
   * @throws InvalidSshKeyException
   * @throws InvalidSshKeySignatureException
   */
  public byte[] performHashAndSign(SshPublicKey pubkey, /*List forwardingNodes,*/
                                   byte[] data) throws KeyTimeoutException,
      InvalidSshKeyException,
      InvalidSshKeySignatureException {
    synchronized (publickeys) {
      if (privatekeys.containsKey(pubkey)) {
        SshPrivateKey key = privatekeys.get(pubkey);
        KeyConstraints cs = constraints.get(pubkey);

        if (cs.canUse()) {
          if (!cs.hasTimedOut()) {
            cs.use();

            byte[] sig = key.generateSignature(data);
            Iterator<KeyStoreListener> it = listeners.iterator();
            KeyStoreListener listener;

            while (it.hasNext()) {
              listener = it.next();
              listener.onKeyOperation(this, "hash-and-sign");
            }

            return sig;
          }
          else {
            throw new KeyTimeoutException();
          }
        }
        else {
          throw new KeyTimeoutException();
        }
      }
      else {
        throw new InvalidSshKeyException("The key does not exist");
      }
    }
  }

  /**
   *
   *
   * @param pubkey
   * @param description
   *
   * @return
   *
   * @throws IOException
   */
  public boolean deleteKey(SshPublicKey pubkey, String description) throws
      IOException {
    synchronized (publickeys) {
      if (publickeys.containsKey(pubkey)) {
        String desc = publickeys.get(pubkey);

        if (description.equals(desc)) {
          publickeys.remove(pubkey);
          privatekeys.remove(pubkey);
          constraints.remove(pubkey);
          index.remove(pubkey);

          Iterator<KeyStoreListener> it = listeners.iterator();
          KeyStoreListener listener;

          while (it.hasNext()) {
            listener = it.next();
            listener.onDeleteKey(this);
          }

          return true;
        }
      }

      return false;
    }
  }

  /**
   *
   *
   * @param password
   *
   * @return
   *
   * @throws IOException
   */
  public boolean lock(String password) throws IOException {
    synchronized (publickeys) {
      if (lockedPassword == null) {
        lockedPassword = password;

        Iterator<KeyStoreListener> it = listeners.iterator();
        KeyStoreListener listener;

        while (it.hasNext()) {
          listener = it.next();
          listener.onLock(this);
        }

        return true;
      }
      else {
        return false;
      }
    }
  }

  /**
   *
   *
   * @param password
   *
   * @return
   *
   * @throws IOException
   */
  public boolean unlock(String password) throws IOException {
    synchronized (publickeys) {
      if (lockedPassword != null) {
        if (password.equals(lockedPassword)) {
          lockedPassword = null;

          Iterator<KeyStoreListener> it = listeners.iterator();
          KeyStoreListener listener;

          while (it.hasNext()) {
            listener = it.next();
            listener.onUnlock(this);
          }

          return true;
        }
      }

      return false;
    }
  }
}
