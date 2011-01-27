package org.ulteo.rdp;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdpConnection;

public class Connection {
	public Options options = null;
	public Common common = null;
	public Thread thread = null;
	public RdpConnection connection = null;
	public OvdAppChannel channel = null;
	public boolean inited = false;
}
