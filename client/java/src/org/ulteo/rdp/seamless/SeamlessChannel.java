/* SeamlessChannel.java
 * Component: UlteoRDP
 * 
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author Thomas MOUTON <thomas@ulteo.com> 2009
 * 
 * Revision: $Revision: 0.2 $
 * Author: $Author: arnauvp $
 * Date: $Date: 2008/06/17 18:26:30 $
 *
 * Purpose: Allow seamless RDP session
 * 
 * Inspired by: 
 * Cendio RDP seamless.c
   Copyright (C) Peter Astrand <astrand@cendio.se> 2005-2006
   
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, version 2 of the License.
   
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.
   
   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

package org.ulteo.rdp.seamless;

import net.propero.rdp.Common;
import net.propero.rdp.Options;


public class SeamlessChannel extends net.propero.rdp.rdp5.seamless.SeamlessChannel {
	public SeamlessChannel(Options opt_, Common common_) {
		super(opt_, common_);
	}

	@Override
	protected boolean processCreate(long id, long group, long parent, long flags) {
		String name = "w_"+id;
		if( this.windows.containsKey(name)) {
		    logger.error("ID '"+id+"' already exist");
		    return false;
		}

		this.addFrame(new SeamlessFrame((int)id, (int)group, this.common), name);

		return true;
	}
}
