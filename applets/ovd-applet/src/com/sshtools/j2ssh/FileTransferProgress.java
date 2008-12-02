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

/**
 * <p>Interface for monitoring the state of a file transfer</p>
 *
 * @author Lee David Painter
 * @version $Id: FileTransferProgress.java,v 1.12 2003/09/23 20:17:46 rpernavas Exp $
 */
 public interface FileTransferProgress {
  /**
   * The transfer has started
   *
   * @param bytesTotal
   * @param remoteFile
   */
  public void started(long bytesTotal, String remoteFile);

  /**
   * The transfer is cancelled. Implementations should return true if the
   * user wants to cancel the transfer. The transfer will then be stopped
   * at the next evaluation stage.
   *
   * @return
   */
  public boolean isCancelled();

  /**
   * The transfer has progressed
   *
   * @param bytesSoFar
   */
  public void progressed(long bytesSoFar);

  /**
   * The transfer has completed.
   */
  public void completed();
}
