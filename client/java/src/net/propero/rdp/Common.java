/* Common.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Author: tomqq <hekong@gmail.com> 2009
 * Date: $Date: 2007/03/08 00:26:14 $
 *
 * Copyright (c) 2005 Propero Limited
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2012
 *
 * Purpose: Provide a static interface to individual network layers
 *          and UI components
 */
package net.propero.rdp;

import javax.swing.JFrame;

import net.propero.rdp.rdp5.Rdp5;

public class Common {

    public boolean underApplet = false;
	public Rdp5 rdp;
	public Secure secure;
	public MCS mcs;
	public ISO iso;
	public RdesktopFrame frame;
	public RdesktopCanvas canvas;
	public JFrame desktopFrame;
	public Cache cache = null;
	public PstCache persistent_cache = null;
}
