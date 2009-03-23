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

package com.sshtools.j2ssh.transport.kex;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import com.sshtools.j2ssh.transport.AlgorithmNotSupportedException;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.29 $
 */
public class SshKeyExchangeFactory {
  private static Map<String, Class<?>> kexs;
  private static String defaultAlgorithm;

  static {
    kexs = new HashMap<String, Class<?>>();

    kexs.put("diffie-hellman-group1-sha1", DhGroup1Sha1.class);

    defaultAlgorithm = "diffie-hellman-group1-sha1";
  }

  /**
   * Creates a new SshKeyExchangeFactory object.
   */
  protected SshKeyExchangeFactory() {
  }

  /**
   *
   */
  public static void initialize() {
  }

  /**
   *
   *
   * @return
   */
  public static String getDefaultKeyExchange() {
    return defaultAlgorithm;
  }

  /**
   *
   *
   * @return
   */
  public static List<String> getSupportedKeyExchanges() {
    return new ArrayList<String>(kexs.keySet());
  }

  /**
   *
   *
   * @param methodName
   *
   * @return
   *
   * @throws AlgorithmNotSupportedException
   */
  public static SshKeyExchange newInstance(String methodName) throws
      AlgorithmNotSupportedException {
    try {
      return (SshKeyExchange) (kexs.get(methodName)).newInstance();
    }
    catch (Exception e) {
      throw new AlgorithmNotSupportedException(methodName
                                               + " is not supported!");
    }
  }
}
