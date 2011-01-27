/* LicenceStore_Localised.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:54 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: Java 1.4 specific extension of LicenceStore class
 */
// Created on 05-Aug-2003

package net.propero.rdp;

import java.util.prefs.Preferences;

public class LicenceStore_Localised extends LicenceStore {

	public LicenceStore_Localised(Options opt_, Common common_) {
		this.opt = opt_;
		this.common = common_;
	}
	
    public byte[] load_licence(){
        Preferences prefs = Preferences.userNodeForPackage(this.getClass());
        return prefs.getByteArray("licence."+this.opt.hostname,null);
        
    }
    
    public void save_licence(byte[] databytes){
        Preferences prefs = Preferences.userNodeForPackage(this.getClass());
        prefs.putByteArray("licence."+this.opt.hostname, databytes);
    }
    
}
