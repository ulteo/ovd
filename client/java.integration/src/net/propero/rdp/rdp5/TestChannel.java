/* TestChannel.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:39 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: Dummy RDP5 channel for testing purposes
 */
package net.propero.rdp.rdp5;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdpPacket;

/**
 * @author Tom Elliott
 *
 * TODO To change the template for this generated type comment go to
 * Window - Preferences - Java - Code Style - Code Templates
 */
public class TestChannel extends VChannel {

	public TestChannel(String name, int flags, Options opt_, Common common_){
		super(opt_, common_);
		this.name = name;
		this.flags = flags;
	}
		
	private String name;
	private int flags;
	
	public String name() {
		return name;
	}

	public int flags() {
		return flags;
	}

	public void process(RdpPacket data) {
	}

}
