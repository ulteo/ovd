/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
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

package org.ulteo.ovd.integrated;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;
import java.util.Random;

public class SpoolLauncher {

	final String DIR_INSTANCE;
	final String DIR_TOLAUNCH;

	public SpoolLauncher(String a_path) throws FileNotFoundException {
		this.DIR_INSTANCE = a_path + File.separatorChar + "instances" + File.separatorChar;
		this.DIR_TOLAUNCH = a_path + File.separatorChar + "to_launch" + File.separatorChar;

		File _dir;
		_dir = new File(this.DIR_INSTANCE);
		if (_dir.exists() == false)
			throw new FileNotFoundException(_dir.getPath());
		_dir = new File(this.DIR_TOLAUNCH);
		if (_dir.exists() == false)
			throw new FileNotFoundException(_dir.getPath());
	}

	private int build_unique_id() {
		int rand;
		{
			rand = new Random().nextInt(((int)Math.pow(2,32)) - 1);
		}
		while (new File(this.DIR_INSTANCE + rand).exists() ||
				new File(this.DIR_TOLAUNCH + rand).exists());
		return rand;
	}

	int createInstance(String appId, String argsApplication) throws IOException {
		if (appId == null)
			throw new NullPointerException();

		int instance = this.build_unique_id();
		PrintWriter out = new PrintWriter(new FileWriter(this.DIR_TOLAUNCH + instance));
		out.println("id = " + appId);
		if (argsApplication != null && !argsApplication.equals(""))
			out.print("arg = " + argsApplication);
		out.close();

		return instance;
	}

	boolean instanceIsAlive(int instance) {
		return new File(this.DIR_INSTANCE + instance).exists();
	}
}
