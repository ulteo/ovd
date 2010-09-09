/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

package org.ulteo.ovd.sm;

import java.util.ArrayList;
import java.util.List;

public class ServerAccess {
	private String host = null;
	private int port = 0;
	private String login = null;
	private String password = null;
	private String token = null;
	private boolean mode_gateway = false;

	private List<Application> applications = null;
	
	public ServerAccess(String host, int port, String login, String password) {
		this.host = host;
		this.port = port;
		this.login = login;
		this.password = password;
		
		this.applications = new ArrayList<Application>();
	}
	
	public String getHost() {
		return this.host;
	}
	
	public int getPort() {
		return this.port;
	}
	
	public String getLogin() {
		return this.login;
	}
	
	public String getPassword() {
		return this.password;
	}
	
	public List<Application> getApplications() {
		return this.applications;
	}
	
	public void addApplication(Application application) {
		this.applications.add(application);
	}

	public void setToken(String token_) {
		this.token = token_;
	}

	public String getToken() {
		return this.token;
	}

	public void setModeGateway(boolean mode_gateway_) {
		this.mode_gateway = mode_gateway_;
	}

	public boolean getModeGateway() {
		return this.mode_gateway;
	}
}
