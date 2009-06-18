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

import java.io.File;
import java.util.Iterator;
import java.util.List;
import java.util.Vector;

import com.sshtools.j2ssh.sftp.SftpFile;

/**
 * <p>This class provides a list of operations that have been/or will be
 * completed by the SftpClient's copyRemoteDirectory/copyLocalDirectory
 * methods.</p>
 * @author Lee David Painter
     * @version $Id: DirectoryOperation.java,v 1.8 2003/09/22 15:58:07 martianx Exp $
 */
public class DirectoryOperation {

  Vector<Object> unchangedFiles = new Vector<Object>();
  Vector<Object> newFiles = new Vector<Object>();
  Vector<Object> updatedFiles = new Vector<Object>();
  Vector<Object> deletedFiles = new Vector<Object>();
  Vector<Object> recursedDirectories = new Vector<Object>();

  /**
   * Construct a new directory operation object
   */
  public DirectoryOperation() {
  }

  protected void addNewFile(File f) {
    newFiles.add(f);
  }

  protected void addUpdatedFile(File f) {
    updatedFiles.add(f);
  }

  protected void addDeletedFile(File f) {
    deletedFiles.add(f);
  }

  protected void addUnchangedFile(File f) {
    unchangedFiles.add(f);
  }

  protected void addNewFile(SftpFile f) {
    newFiles.add(f);
  }

  protected void addUpdatedFile(SftpFile f) {
    updatedFiles.add(f);
  }

  protected void addDeletedFile(SftpFile f) {
    deletedFiles.add(f);
  }

  protected void addUnchangedFile(SftpFile f) {
    unchangedFiles.add(f);
  }

  /**
       * Returns a list of new files that will be transfered in the directory operation
   * @return
   */
  public List<Object> getNewFiles() {
    return newFiles;
  }

  /**
   * Returns a list of files that will be updated in the directory operation
   * @return
   */
  public List<Object> getUpdatedFiles() {
    return updatedFiles;
  }

  /**
   * Returns the list of files that will not be changed during the directory
   * operation
   * @return
   */
  public List<Object> getUnchangedFiles() {
    return unchangedFiles;
  }

  /**
   * When synchronizing directories, this method will return a list of files
   * that will be deleted becasue they no longer exist at the source location.
   * @return
   */
  public List<Object> getDeletedFiles() {
    return deletedFiles;
  }

  /**
   * Determine whether the operation contains a file.
   * @param f
   * @return
   */
  public boolean containsFile(File f) {
    return unchangedFiles.contains(f) ||
        newFiles.contains(f) ||
        updatedFiles.contains(f) ||
        deletedFiles.contains(f) ||
        recursedDirectories.contains(f);
  }

  /**
   * Determine whether the directory operation contains an SftpFile
   * @param f
   * @return
   */
  public boolean containsFile(SftpFile f) {
    return unchangedFiles.contains(f) ||
        newFiles.contains(f) ||
        updatedFiles.contains(f) ||
        deletedFiles.contains(f) ||
        recursedDirectories.contains(f.getAbsolutePath());
  }

  /**
   * Add the contents of another directory operation. This is used to
   * record changes when recuring through directories.
   * @param op
   * @param f
   */
  public void addDirectoryOperation(DirectoryOperation op, File f) {
    updatedFiles.addAll(op.getUpdatedFiles());
    newFiles.addAll(op.getNewFiles());
    unchangedFiles.addAll(op.getUnchangedFiles());
    deletedFiles.addAll(op.getDeletedFiles());
    recursedDirectories.add(f);
  }

  /**
   * Get the total number of new and changed files to transfer
   * @return
   */
  public int getFileCount() {
    return newFiles.size() + updatedFiles.size();
  }

  /**
   * Get the total number of bytes that this operation will transfer
   * @return
   */
  public long getTransferSize() {

    Object obj;
    long size = 0;
    SftpFile sftpfile;
    File file;
    for (Iterator<Object> i = newFiles.iterator();
         i.hasNext(); ) {
      obj = i.next();
      if (obj instanceof File) {
        file = (File) obj;
        if (file.isFile()) {
          size += file.length();
        }
      }
      else if (obj instanceof SftpFile) {
        sftpfile = (SftpFile) obj;
        if (sftpfile.isFile()) {
          size += sftpfile.getAttributes().getSize().longValue();
        }
      }
    }
    for (Iterator<Object> i = updatedFiles.iterator();
         i.hasNext(); ) {
      obj = i.next();
      if (obj instanceof File) {
        file = (File) obj;
        if (file.isFile()) {
          size += file.length();
        }
      }
      else if (obj instanceof SftpFile) {
        sftpfile = (SftpFile) obj;
        if (sftpfile.isFile()) {
          size += sftpfile.getAttributes().getSize().longValue();
        }
      }
    }

    // Add a value for deleted files??

    return size;
  }

}