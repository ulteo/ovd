/**
* ===========================================
* Java Pdf Extraction Decoding Access Library
* ===========================================
*
* Project Info:  http://www.jpedal.org
* (C) Copyright 1997-2008, IDRsolutions and Contributors.
*
* 	This file is part of JPedal
*
    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    General Public License for more details.

    You should have received a copy of the GNU General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


*
* ---------------
* ExpressionEngine.java
* ---------------
*/
package org.jpedal.objects.javascript;

import org.jpedal.objects.acroforms.rendering.AcroRenderer;

/**
 * allow user to handle expressions with own implementation
 */
public interface ExpressionEngine {

    /**
     *
     * @param ref  ie 1 0 R
     * @param renderer ref to acrorender so you can access objects
     * @param type - defined in ActionHandler (ie K)
     * @param js - Javascript string
     * @param eventType - type of event (Keystroke, focus)
     * @param keyPressed - if key event, key value , otherwsie space
     * @return return code (ActionHandler.STOPPROCESSING to ignore JPedal handling)
     */
    int execute(String ref, AcroRenderer renderer, int type, Object js,int eventType,char keyPressed);

    /**
     * called on close to do any cleanup
     */
    void closeFile();

    //return true if JPedal should do nothing, false if JPedal should execute command as well 
    boolean reportError(int code, Object[] args);
}
