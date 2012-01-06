package org.ulteo.vdi;

import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import org.ulteo.ovd.disk.LinuxDiskManager;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;

public class DiskManager extends LinuxDiskManager {

	public DiskManager(RdpdrChannel diskChannel) {
		super((OVDRdpdrChannel)diskChannel, LinuxDiskManager.ALL_MOUNTING_ALLOWED);

		this.profile.addStaticShare(System.getProperty("user.home"));
	}
}
