/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

package org.ulteo.crypto;

import java.security.GeneralSecurityException;
import java.security.NoSuchAlgorithmException;
import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.spec.SecretKeySpec;
import org.ulteo.Logger;

public class AES implements SymmetricCryptography {

	public static final byte[] default_key = {
		(byte) 0xd9, (byte) 0x61, (byte) 0x84, (byte) 0xf6,
		(byte) 0x40, (byte) 0xa5, (byte) 0x6f, (byte) 0xba,
		(byte) 0x22, (byte) 0x2e, (byte) 0xfb, (byte) 0x40,
		(byte) 0x14, (byte) 0x76, (byte) 0xcd, (byte) 0x6f
	};
	
	private SecretKeySpec skey;
	
	public AES(byte[] skey) {
		this.skey = new SecretKeySpec(skey, "AES");
	}
	
	public static byte[] generateKey() {
		KeyGenerator kgen;
		try {
			kgen = KeyGenerator.getInstance("AES");
		} catch (NoSuchAlgorithmException ex) {
			Logger.error("AES algorithm is not supported: "+ex.getMessage());
			return null;
		}
		// The big keys may not be available (> 128 bits)
		kgen.init(128);
		return kgen.generateKey().getEncoded();
	}

	@Override
	public byte[] encrypt(byte[] data) throws GeneralSecurityException {
		Cipher cipher = Cipher.getInstance("AES");
		cipher.init(Cipher.ENCRYPT_MODE, this.skey);
		return cipher.doFinal(data);
	}

	@Override
	public byte[] decrypt(byte[] data) throws GeneralSecurityException {
		Cipher  cipher = Cipher.getInstance("AES");
		cipher.init(Cipher.DECRYPT_MODE, this.skey);
		return cipher.doFinal(data);
	}
}