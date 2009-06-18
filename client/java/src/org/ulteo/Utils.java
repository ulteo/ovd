//  This is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
//
//  This software is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this software; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,
//  USA.
//



package org.ulteo;

import org.vnc.DesCipher;

public class Utils {

    public static byte[] DecryptString_(String input) {
	byte[] c = {
	    0, 0, 0, 0, 0, 0, 0, 0};
	int len = input.length() / 2;
	for (int i = 0; i < len; i++) {
	    String hex = input.substring(i * 2, i * 2 + 2);
	    Integer x = new Integer(Integer.parseInt(hex, 16));
	    c[i] = x.byteValue();
	}

	return c;
    }


	public static String DecryptString(String input) {
		return new String(DecryptString_(input));
	}

	public static String DecryptEncVNCString(String input) {
		byte[] out = DecryptString_(input);
		byte[] key = {23, 82, 107, 6, 35, 78, 88, 7};

		DesCipher des = new DesCipher(key);
		des.decrypt(out, 0, out, 0);

		return new String(out);

	}
}
