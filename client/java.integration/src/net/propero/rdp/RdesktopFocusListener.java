/* RdesktopFocusListener.java
 * Component: ProperJavaRDP
 *
 * Revision: $Revision: 1.3 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/15 23:18:35 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: Focus Listener for RdesktopCanvas
 */

package net.propero.rdp;

import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;

public class RdesktopFocusListener implements FocusListener {
	private RdesktopCanvas canvas = null;
	private Options opt = null;

    	public RdesktopFocusListener(RdesktopCanvas canvas_, Options opt_) {
		this.canvas = canvas_;
    		this.opt = opt_;
    	}

        public void focusGained(FocusEvent arg0) {
            if (Constants.OS == Constants.WINDOWS) {
                // canvas.repaint();
                this.canvas.repaint(0, 0, this.opt.width, this.opt.height);
            }
            // gained focus..need to check state of locking keys
            this.canvas.gainedFocus();
        }

        public void focusLost(FocusEvent arg0) {
            //  lost focus - need clear keys that are down
            this.canvas.lostFocus();
        }
}
