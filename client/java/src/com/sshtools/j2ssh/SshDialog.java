/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com>
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

package com.sshtools.j2ssh;



public class SshDialog implements SshErrorResolver {
	private static SshDialog instance; 
	private SshErrorResolver resolver;


	private SshDialog(){
		System.out.println("init SshErrorResolver");
		this.resolver = this;
	}

	public static SshDialog getInstance() {
		if (instance == null)
			instance = new SshDialog();
		
		return instance;
	}

	public static void registerResolver(SshErrorResolver resolver) {
		getInstance().resolver = resolver;
	}

	public static void notifyError(String error) {
		getInstance().resolver.resolvError(error);
	}

	public static void logErrorMessage(String errorMessage) {
		getInstance().resolver.logError(errorMessage);
	}

	public void logError(String errorMessage) {
		System.err.println(errorMessage);
	}

	public void resolvError(String error) {		
	}

}
