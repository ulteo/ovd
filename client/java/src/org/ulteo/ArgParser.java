/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
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
 **/

package org.ulteo;

import java.applet.Applet;


public class ArgParser extends org.vnc.ArgParser{

    public String ssh_host;
    public String ssh_port;
    public String ssh_login;
    public String ssh_password;
    public String vnc_port;
    public String vnc_password;

    public String getParameter(String param) {

	if (param == "ssh.host")
	    return this.ssh_host;
	else if (param == "ssh.port")
	    return this.ssh_port;
	else if (param == "ssh.user")
	    return this.ssh_login;
	else if (param == "ssh.password")
	    return this.ssh_password;
	else if (param == "HOST")
	    return "localhost";
	else if (param == "PORT")
	    return this.vnc_port;
	else if (param == "PASSWORD")
	    return this.vnc_password;

	return null;
    }
}
