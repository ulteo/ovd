/* KeyCode_FileBased_Localised.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:54 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: Java 1.4 specific extension of KeyCode_FileBased class
 */
package net.propero.rdp;

import java.awt.event.KeyEvent;
import java.io.InputStream;
import java.util.HashMap;

import net.propero.rdp.keymapping.KeyCode_FileBased;
import net.propero.rdp.keymapping.KeyMapException;


public class KeyCode_FileBased_Localised extends KeyCode_FileBased {

	private HashMap keysCurrentlyDown = new HashMap();
	private Options opt = null;
	
	/**
	 * @param fstream
	 * @throws KeyMapException
	 */
	public KeyCode_FileBased_Localised(InputStream fstream, Options opt_) throws KeyMapException {
		super(fstream, opt_);
		this.opt = opt_;
	}

	public KeyCode_FileBased_Localised(String s, Options opt_) throws KeyMapException{
		super(s, opt_);
		this.opt = opt_;
	}
	
	private void updateCapsLock(KeyEvent e){
		if(this.opt.useLockingKeyState){
			try {
				this.opt.useLockingKeyState = true;
				capsLockDown = e.getComponent().getToolkit().getLockingKeyState(KeyEvent.VK_CAPS_LOCK);
			} catch (Exception uoe){ this.opt.useLockingKeyState = false; }
		}
	}
	
}
