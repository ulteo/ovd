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

package com.sshtools.j2ssh.transport.publickey;

import java.io.InputStream;
import java.net.URL;
import java.util.ArrayList;
import java.util.Enumeration;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Properties;
import java.util.Vector;

import com.sshtools.j2ssh.configuration.ConfigurationLoader;
import com.sshtools.j2ssh.io.IOUtil;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.26 $
 */
public class SshPrivateKeyFormatFactory {
  private static String defaultFormat;
  private static HashMap<String, Class<?>> formatTypes;
  private static Vector<String> types;

  static {


    List<String> formats = new ArrayList<String>();
    types = new Vector<String>();
    formatTypes = new HashMap<String, Class<?>>();
    formats.add(SshtoolsPrivateKeyFormat.class.getName());
    defaultFormat = "SSHTools-PrivateKey-Base64Encoded";

    try {

      Enumeration<URL> enumer =
          ConfigurationLoader.getExtensionClassLoader().getResources("j2ssh.privatekey");
      URL url;
      Properties properties = new Properties();
      InputStream in;
      while(enumer!=null && enumer.hasMoreElements()) {
        url = enumer.nextElement();
        in = url.openStream();
        properties.load(in);
        IOUtil.closeStream(in);
        int num = 1;
        //String name = "";
        //Class cls;
        while(properties.getProperty("privatekey.name."
                                     + String.valueOf(num))
              != null) {
            /*name = */properties.getProperty("privatekey.name."
                                          + String.valueOf(num));
            formats.add(properties.getProperty("privatekey.class."
                                               + String.valueOf(num)));



          num++;
        }
      }
    }
    catch (Throwable t) {
    }

    SshPrivateKeyFormat f;

    Iterator<String> it = formats.iterator();
    String classname;

    while (it.hasNext()) {
      classname = it.next();

      try {
        Class<?> cls = ConfigurationLoader.getExtensionClass(classname);
        f = (SshPrivateKeyFormat) cls.newInstance();
        formatTypes.put(f.getFormatType(), cls);
        types.add(f.getFormatType());
      }
      catch (Throwable t) {
      }
    }


  }

  /**
   *
   *
   * @return
   */
  public static List<String> getSupportedFormats() {
    return types;
  }


  public static void initialize() {

  }

  /**
   *
   *
   * @param type
   *
   * @return
   *
   * @throws InvalidSshKeyException
   */
  public static SshPrivateKeyFormat newInstance(String type) throws
      InvalidSshKeyException {
    try {
      if (formatTypes.containsKey(type)) {
        return (SshPrivateKeyFormat) (formatTypes.get(type))
            .newInstance();
      }
      else {
        throw new InvalidSshKeyException("The format type " + type
                                         + " is not supported");
      }
    }
    catch (IllegalAccessException iae) {
      throw new InvalidSshKeyException(
          "Illegal access to class implementation of " + type);
    }
    catch (InstantiationException ie) {
      throw new InvalidSshKeyException(
          "Failed to create instance of format type " + type);
    }
  }

  /**
   *
   *
   * @return
   */
  public static String getDefaultFormatType() {
    return defaultFormat;
  }
}
