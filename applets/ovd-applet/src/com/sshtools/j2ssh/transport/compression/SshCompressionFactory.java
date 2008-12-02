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

package com.sshtools.j2ssh.transport.compression;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.Enumeration;
import java.util.Properties;
import java.net.URL;
import java.io.InputStream;
import java.util.Vector;

import com.sshtools.j2ssh.io.IOUtil;
import com.sshtools.j2ssh.configuration.ConfigurationException;
import com.sshtools.j2ssh.configuration.ConfigurationLoader;
import com.sshtools.j2ssh.transport.AlgorithmNotSupportedException;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.30 $
 */
public class SshCompressionFactory {
  /**  */
  public final static String COMP_NONE = "none";
  private static String defaultAlgorithm;
  private static Map comps;

  static {
    comps = new HashMap();


    comps.put(COMP_NONE, "");

    defaultAlgorithm = COMP_NONE;

    try {

      Enumeration enumer = ConfigurationLoader.getExtensionClassLoader().
          getResources("j2ssh.compression");
      URL url;
      Properties properties = new Properties();
      InputStream in;
      while (enumer != null && enumer.hasMoreElements()) {
        url = (URL) enumer.nextElement();
        in = url.openStream();
        properties.load(in);
        IOUtil.closeStream(in);
        int num = 1;
        String name = "";
        Class cls;
        while (properties.getProperty("compression.name."
                                      + String.valueOf(num))
               != null) {
          try {
            name = properties.getProperty("compression.name."
                                          + String.valueOf(num));
            cls = ConfigurationLoader.getExtensionClassLoader().loadClass(
                properties.getProperty("compression.class." + String.valueOf(num)));
            cls.newInstance();
            comps.put(name, cls);
          }
          catch (Throwable ex) {
            //log.info("Could not install cipher class for " + name, ex);
          }

          num++;
        }
      }
    }
    catch (Throwable t) {
      //log.info("Failed to load compression algorithms", t);
    }

  }

  /**
   * Creates a new SshCompressionFactory object.
   */
  protected SshCompressionFactory() {
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
  public static String getDefaultCompression() {
    return defaultAlgorithm;
  }

  /**
   *
   *
   * @return
   */
  public static List getSupportedCompression() {
    return new ArrayList(comps.keySet());
  }

  /**
   *
   *
   * @param algorithmName
   *
   * @return
   *
   * @throws AlgorithmNotSupportedException
   */
  public static SshCompression newInstance(String algorithmName) throws
      AlgorithmNotSupportedException {
    try {
      if(algorithmName.equals(COMP_NONE))
        return null;
      else
        return (SshCompression)((Class)comps.get(algorithmName))
          .newInstance();
    }
    catch (Exception e) {
      throw new AlgorithmNotSupportedException(algorithmName
                                               + " is not supported!");
    }
  }
}
