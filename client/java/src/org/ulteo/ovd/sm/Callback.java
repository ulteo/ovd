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

import org.ulteo.ovd.client.authInterface.LoadingStatus;

public interface Callback {
	public void reportError(int code, String msg);
	public void reportErrorStartSession(String code);
	public void reportBadXml(String data);
	public void reportUnauthorizedHTTPResponse(String moreInfos);
	public void reportNotFoundHTTPResponse(String moreInfos);
	public void sessionConnected();
	public void sessionDisconnecting();
	public void updateProgress(LoadingStatus clientInstallApplication, int subStatus);
}