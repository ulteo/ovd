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

package com.sshtools.j2ssh.net;

import java.io.IOException;
import java.net.UnknownHostException;
import java.net.SocketTimeoutException; 

import com.sshtools.j2ssh.configuration.SshConnectionProperties;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.15 $
 */
public class TransportProviderFactory {
	
	public static int MAX_ITERATIONS = 5;
	
	
  /**
   *
   *
   * @param properties
   * @param socketTimeout
   *
   * @return
   *
   * @throws UnknownHostException
   * @throws IOException
   */
  public static TransportProvider connectTransportProvider(
      SshConnectionProperties properties /*, int connectTimeout*/,
      int socketTimeout) throws UnknownHostException, IOException {
    if (properties.getTransportProvider() ==
        SshConnectionProperties.USE_HTTP_PROXY) {
      return HttpProxySocketProvider.connectViaProxy(properties.getHost(),
          properties.getPort(), properties.getProxyHost(),
          properties.getProxyPort(), properties.getProxyUsername(),
          properties.getProxyPassword(), "J2SSH");
    }
    else if (properties.getTransportProvider() ==
             SshConnectionProperties.USE_SOCKS4_PROXY) {
      return SocksProxySocket.connectViaSocks4Proxy(properties.getHost(),
          properties.getPort(), properties.getProxyHost(),
          properties.getProxyPort(), properties.getProxyUsername());
    }
    else if (properties.getTransportProvider() ==
             SshConnectionProperties.USE_SOCKS5_PROXY) {
      return SocksProxySocket.connectViaSocks5Proxy(properties.getHost(),
          properties.getPort(), properties.getProxyHost(),
          properties.getProxyPort(), properties.getProxyUsername(),
          properties.getProxyPassword());
    }
    else {
      // No proxy just attempt a standard socket connection

      /*SocketTransportProvider socket = new SocketTransportProvider();
       socket.setSoTimeout(socketTimeout);
       socket.connect(new InetSocketAddress(properties.getHost(),
                               properties.getPort()),
         connectTimeout);*/

      boolean failed;
      int nextPort = 0;
      int iterations = 0;
      int port = properties.getPort(nextPort);
    	  
      SocketTransportProvider socket = null;
      do {
        try{
			System.err.println("Trying to open connection through port "+port);
        	socket = new SocketTransportProvider(properties.getHost(), port);
        	failed = false;
        }catch(SocketTimeoutException socketEx){
        	System.err.println("Connection through port "+port+" failed. ");
    		failed = true;
    		port = properties.getPort(++nextPort);
    		if (port < 0) {
    			if (iterations < MAX_ITERATIONS) {
    				iterations++;
    				nextPort = 0;
    				port = properties.getPort(nextPort);
    				try {
        				Thread.sleep(2500);
        			} catch (InterruptedException e) {
//      				e.printStackTrace();
        			}
    			} else {
    				throw new SocketTimeoutException("No port available. Socket timed out.");
    			}
    		}
        }catch(IOException ioe){
        	System.err.println("Connection through port "+port+" failed. ");
    		failed = true;
    		port = properties.getPort(++nextPort);
    		if (port < 0) {
    			if (iterations < MAX_ITERATIONS) {
    				iterations++;
    				nextPort = 0;
    				port = properties.getPort(nextPort);
    				try {
        				Thread.sleep(2500);
        			} catch (InterruptedException e) {
//      				e.printStackTrace();
        			}
    			} else {
    				throw new SocketTimeoutException("No port available. Socket timed out.");
    			}
    		}
        }
        try {
			Thread.sleep(100);
		} catch (InterruptedException e) {
//			e.printStackTrace();
		}
      } while(failed);
      socket.setTcpNoDelay(true);
      socket.setSoTimeout(socketTimeout);
      return socket;

    }
  }
}
