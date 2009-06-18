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

package com.sshtools.j2ssh.transport.cipher;

import java.security.InvalidAlgorithmParameterException;
import java.security.InvalidKeyException;
import java.security.NoSuchAlgorithmException;

import javax.crypto.Cipher;
import javax.crypto.NoSuchPaddingException;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.SecretKeySpec;

import com.sshtools.j2ssh.transport.AlgorithmOperationException;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.21 $
 */
public class BlowfishCbc
    extends SshCipher {

  /**  */
//  protected static String algorithmName = "blowfish-cbc";
  Cipher cipher;

  /**
   * Creates a new BlowfishCbc object.
   */
  public BlowfishCbc() {
  }

  /**
   *
   *
   * @return
   */
  @Override
public int getBlockSize() {
    return cipher.getBlockSize();
  }

  /**
   *
   *
   * @param mode
   * @param iv
   * @param keydata
   *
   * @throws AlgorithmOperationException
   */
  @Override
public void init(int mode, byte[] iv, byte[] keydata) throws
      AlgorithmOperationException {
    try {
      cipher = Cipher.getInstance("Blowfish/CBC/NoPadding");

      // Create a 16 byte key
      byte[] actualKey = new byte[16];
      System.arraycopy(keydata, 0, actualKey, 0, actualKey.length);

      SecretKeySpec keyspec = new SecretKeySpec(actualKey, "Blowfish");

      // Create the cipher according to its algorithm
      cipher.init( ( (mode == ENCRYPT_MODE) ? Cipher.ENCRYPT_MODE
                    : Cipher.DECRYPT_MODE),
                  keyspec, new IvParameterSpec(iv, 0, cipher.getBlockSize()));
    }
    catch (NoSuchPaddingException nspe) {
      throw new AlgorithmOperationException("No Padding not supported");
    }
    catch (NoSuchAlgorithmException nsae) {
      throw new AlgorithmOperationException("Algorithm not supported");
    }
    catch (InvalidKeyException ike) {
      throw new AlgorithmOperationException("Invalid encryption key");

      /*} catch (InvalidKeySpecException ispe) {
           throw new AlgorithmOperationException("Invalid encryption key specification");*/
    }
    catch (InvalidAlgorithmParameterException ape) {
 	      throw new AlgorithmOperationException("Invalid algorithm parameter");
 	    }
 	  }

 	  /**
 	   *
 	   *
 	   * @param data
 	   * @param offset
	   * @param len
 	   *
 	   * @return
 	   *
 	   * @throws AlgorithmOperationException
 	   */

  @Override
public byte[] transform(byte[] data, int offset, int len) throws
       AlgorithmOperationException {
 	    return cipher.update(data, offset, len);
   }
 }
