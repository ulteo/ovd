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

package org.ulteo.utils;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStream;
import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import org.ulteo.Logger;

public class MD5 {
	public static final String getMD5Sum(String str) {
		if (str == null)
			return null;

		MessageDigest digest = null;
		try {
			digest = MessageDigest.getInstance("MD5");
		} catch (NoSuchAlgorithmException ex) {
			Logger.error("Failed to do the md5 sum of '"+str+"': "+ex.getMessage());
			return null;
		}

		byte[] buffer = str.getBytes();
		digest.update(buffer, 0, buffer.length);

		byte[] md5sum = digest.digest();
		BigInteger bigInt = new BigInteger(1, md5sum);

		return bigInt.toString(16);
	}

	public static final String getMD5Sum(File f) {
		if (f == null)
			return null;

		if (! f.exists() || f.isDirectory())
			return null;

		InputStream is;
		try {
			is = new FileInputStream(f);
		} catch (FileNotFoundException ex) {
			Logger.error("Weird, should never appear. "+ex.getMessage());
			return null;
		}
		byte[] buffer = new byte[8192];
		int read = 0;

		MessageDigest digest = null;
		try {
			digest = MessageDigest.getInstance("MD5");
		} catch (NoSuchAlgorithmException ex) {
			Logger.error("Failed to do the md5 sum of '"+f.getPath()+"': "+ex.getMessage());
			return null;
		}
		try {
			while ((read = is.read(buffer)) > 0) {
				digest.update(buffer, 0, read);
			}
		} catch (IOException ex) {
			Logger.error("Failed to do the md5 sum of '"+f.getPath()+"': "+ex.getMessage());
			return null;
		}

		byte[] md5sum = digest.digest();
		BigInteger bigInt = new BigInteger(1, md5sum);

		return bigInt.toString(16);
	}
}
