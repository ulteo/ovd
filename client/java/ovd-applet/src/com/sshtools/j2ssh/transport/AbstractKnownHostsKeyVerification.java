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

package com.sshtools.j2ssh.transport;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.FilePermission;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.security.AccessControlException;
import java.security.AccessController;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;
import java.util.StringTokenizer;
import java.util.Map.Entry;

import com.sshtools.j2ssh.transport.publickey.SshKeyPairFactory;
import com.sshtools.j2ssh.transport.publickey.SshPublicKey;
import com.sshtools.j2ssh.util.Base64;

/**
 * <p>
 * An abstract <code>HostKeyVerification</code> class providing validation
 * against the known_hosts format.
 * </p>
 *
 * @author Lee David Painter
 * @version $Revision: 1.18 $
 *
 * @since 0.2.0
 */
public abstract class AbstractKnownHostsKeyVerification
    implements HostKeyVerification {
  //private static String defaultHostFile;

  //private List deniedHosts = new ArrayList();
  private Map<String, Map<String, SshPublicKey>> allowedHosts = new HashMap<String, Map<String, SshPublicKey>>();
  private String knownhosts;
  private boolean hostFileWriteable;

  //private boolean expectEndElement = false;
  //private String currentElement = null;

  /**
   * <p>
   * Constructs a host key verification instance reading the specified
   * known_hosts file.
   * </p>
   *
   * @param knownhosts the path of the known_hosts file
   *
   * @throws InvalidHostFileException if the known_hosts file is invalid
   *
   * @since 0.2.0
   */
  public AbstractKnownHostsKeyVerification(String knownhosts) throws
      InvalidHostFileException {
    InputStream in = null;

    try {
      //  If no host file is supplied, or there is not enough permission to load
      //  the file, then just create an empty list.
      if (knownhosts != null) {
        if (System.getSecurityManager() != null) {
        	System.out.println("We have a security manager: " + System.getSecurityManager());
          AccessController.checkPermission(new FilePermission(
              knownhosts, "read"));
        }

        File f = new File(knownhosts);

        if (f.exists()) {
          in = new FileInputStream(f);

          BufferedReader reader = new BufferedReader(new InputStreamReader(
              in));
          String line;

          while ( (line = reader.readLine()) != null) {
            StringTokenizer tokens = new StringTokenizer(line, " ");
            String host = (String) tokens.nextElement();
            /*String algorithm = (String)*/ tokens.nextElement();
            String key = (String) tokens.nextElement();

            SshPublicKey pk = SshKeyPairFactory.decodePublicKey(Base64
                .decode(key));

            putAllowedKey(host, pk);

          }

          reader.close();

          hostFileWriteable = f.canWrite();
        }
        else {
          // Try to create the file and its parents if necersary
          f.getParentFile().mkdirs();

          if (f.createNewFile()) {
            FileOutputStream out = new FileOutputStream(f);
            out.write(toString().getBytes());
            out.close();
            hostFileWriteable = true;
          }
          else {
            hostFileWriteable = false;
          }
        }

        if (!hostFileWriteable) {
//          System.err.println("Hosts file is not writable!");
        }

        this.knownhosts = knownhosts;
      }
    }
    catch (AccessControlException ace) {
    	System.err.println("Access Control Exception! No rights to access the hosts file!");
      hostFileWriteable = false;
    }
    catch (IOException ioe) {
    	System.err.println("IOException! Whatever!");
      hostFileWriteable = false;
    }
    finally {
      if (in != null) {
        try {
          in.close();
        }
        catch (IOException ioe) {
        }
      }
    }
  }

  /**
   * <p>
   * Determines whether the host file is writable.
   * </p>
   *
   * @return true if the host file is writable, otherwise false
   *
   * @since 0.2.0
   */
  public boolean isHostFileWriteable() {
    return hostFileWriteable;
  }

  /**
   * <p>
   * Called by the <code>verifyHost</code> method when the host key supplied
   * by the host does not match the current key recording in the known hosts
   * file.
   * </p>
   *
   * @param host the name of the host
   * @param allowedHostKey the current key recorded in the known_hosts file.
   * @param actualHostKey the actual key supplied by the user
   *
   * @throws TransportProtocolException if an error occurs
   *
   * @since 0.2.0
   */
  public abstract void onHostKeyMismatch(String host,
                                         SshPublicKey allowedHostKey,
                                         SshPublicKey actualHostKey) throws
      TransportProtocolException;

  /**
   * <p>
   * Called by the <code>verifyHost</code> method when the host key supplied
   * is not recorded in the known_hosts file.
   * </p>
   *
   * <p></p>
   *
   * @param host the name of the host
   * @param key the public key supplied by the host
   *
   * @throws TransportProtocolException if an error occurs
   *
   * @since 0.2.0
   */
  public abstract void onUnknownHost(String host, SshPublicKey key) throws
      TransportProtocolException;

  /**
   * <p>
   * Allows a host key, optionally recording the key to the known_hosts file.
   * </p>
   *
   * @param host the name of the host
   * @param pk the public key to allow
   * @param always true if the key should be written to the known_hosts file
   *
   * @throws InvalidHostFileException if the host file cannot be written
   *
   * @since 0.2.0
   */
  public void allowHost(String host, SshPublicKey pk, boolean always) throws
      InvalidHostFileException {
    /*if (log.isDebugEnabled()) {
      log.debug("Allowing " + host + " with fingerprint "
                + pk.getFingerprint());
    }*/

    // Put the host into the allowed hosts list, overiding any previous
    // entry
    putAllowedKey(host, pk);

    //allowedHosts.put(host, pk);
    // If we always want to allow then save the host file with the
    // new details
    if (always) {
      saveHostFile();
    }
  }
  
  /**
   * <p>
   * Verifies a host key against the list of known_hosts.
   * </p>
   *
   * <p>
   * If the host unknown or the key does not match the currently allowed host
   * key the abstract <code>onUnknownHost</code> or
   * <code>onHostKeyMismatch</code> methods are called so that the caller
   * may identify and allow the host.
   * </p>
   *
   * @param host the name of the host
   * @param pk the host key supplied
   *
   * @return true if the host is accepted, otherwise false
   *
   * @throws TransportProtocolException if an error occurs
   *
   * @since 0.2.0
   */
  public boolean verifyHost(String host, SshPublicKey pk) throws
      TransportProtocolException {
    //String fingerprint = pk.getFingerprint();

    /*if (log.isDebugEnabled()) {
      log.debug("Fingerprint: " + fingerprint);
    }*/

    Iterator<String> it = allowedHosts.keySet().iterator();

    while (it.hasNext()) {
      // Could be a comma delimited string of names/ip addresses
      String names = it.next();

      if (names.equals(host)) {
        return validateHost(names, pk);
      }

      StringTokenizer tokens = new StringTokenizer(names, ",");

      while (tokens.hasMoreElements()) {
        // Try the allowed hosts by looking at the allowed hosts map
        String name = (String) tokens.nextElement();

        if (name.equalsIgnoreCase(host)) {
          return validateHost(names, pk);
        }
      }
    }

    // The host is unknown os ask the user
    onUnknownHost(host, pk);

    // Recheck ans return the result
    return checkKey(host, pk);
  }

  private boolean validateHost(String names, SshPublicKey pk) throws
      TransportProtocolException {
    // The host is allowed so check the fingerprint
    SshPublicKey pub = getAllowedKey(names, pk.getAlgorithmName()); //shPublicKey) allowedHosts.get(host + "#" + pk.getAlgorithmName());

    if ( (pub != null) && pk.equals(pub)) {
      return true;
    }
    else {
      // The host key does not match the recorded so call the abstract
      // method so that the user can decide
      if (pub == null) {
        onUnknownHost(names, pk);
      }
      else {
        onHostKeyMismatch(names, pub, pk);
      }

      // Recheck the after the users input
      return checkKey(names, pk);
    }
  }

  private boolean checkKey(String host, SshPublicKey key) {
    SshPublicKey pk = getAllowedKey(host, key.getAlgorithmName()); //shPublicKey) allowedHosts.get(host + "#" + key.getAlgorithmName());

    if (pk != null) {
      if (pk.equals(key)) {
        return true;
      }
    }

    return false;
  }

  private SshPublicKey getAllowedKey(String names, String algorithm) {
    if (allowedHosts.containsKey(names)) {
      Map<String, SshPublicKey> map =  allowedHosts.get(names);

      return map.get(algorithm);
    }

    return null;
  }

  private void putAllowedKey(String host, SshPublicKey key) {
    if (!allowedHosts.containsKey(host)) {
      allowedHosts.put(host, new HashMap<String, SshPublicKey>());
    }

    Map<String, SshPublicKey> map = allowedHosts.get(host);
    map.put(key.getAlgorithmName(), key);
  }

  /**
   * <p>
   * Save's the host key file to be saved.
   * </p>
   *
   * @throws InvalidHostFileException if the host file is invalid
   *
   * @since 0.2.0
   */
  public void saveHostFile() throws InvalidHostFileException {
    if (!hostFileWriteable) {
      throw new InvalidHostFileException("Host file is not writeable.");
    }


    try {
      File f = new File(knownhosts);

      FileOutputStream out = new FileOutputStream(f);

      out.write(toString().getBytes());

      out.close();
    }
    catch (IOException e) {
      throw new InvalidHostFileException("Could not write to "
                                         + knownhosts);
    }
  }

  /**
   * <p>
   * Outputs the allowed hosts in the known_hosts file format.
   * </p>
   *
   * <p>
   * The format consists of any number of lines each representing one key for
   * a single host.
   * </p>
   * <code> titan,192.168.1.12 ssh-dss AAAAB3NzaC1kc3MAAACBAP1/U4Ed.....
   * titan,192.168.1.12 ssh-rsa AAAAB3NzaC1kc3MAAACBAP1/U4Ed.....
   * einstein,192.168.1.40 ssh-dss AAAAB3NzaC1kc3MAAACBAP1/U4Ed..... </code>
   *
   * @return
   *
   * @since 0.2.0
   */
  @Override
public String toString() {
	  	  
    String knownhosts = "";
    
    /*Map.Entry*/ Entry<String, Map<String, SshPublicKey>> entry;
    /*Map.Entry*/ Entry<String, SshPublicKey> entry2;
    Iterator<Entry<String, Map<String, SshPublicKey>>> it = allowedHosts.entrySet().iterator();

    
    while (it.hasNext()) {
      entry = it.next();

      Iterator<Entry<String, SshPublicKey>> it2 = ( entry.getValue()).entrySet().iterator();

      while (it2.hasNext()) {
        entry2 = it2.next();

        SshPublicKey pk = entry2.getValue();
        knownhosts += (entry.getKey().toString() + " "
                       + pk.getAlgorithmName() + " "
                       + Base64.encodeBytes(pk.getEncoded(), true) + "\n");
      }
    }

    return knownhosts;
  }
}
