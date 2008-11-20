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
* JSParser.java
* ---------------
*/
package org.jpedal.objects.javascript;

public class JSParser {

    //actual PDF object
    private org.jpedal.objects.javascript.JSApp app;

    private org.mozilla.javascript.Context cx = null;

    private org.mozilla.javascript.Scriptable scope = null;

    static {

        // see if javascript present
        java.io.InputStream in = JSParser.class.getClassLoader().getResourceAsStream("org/mozilla/javascript/Context.class");
        if (in == null)
            throw new RuntimeException("JPedal Must have Rhino on classpath for Javascript");

    }

    /**
     * store and execute code
     */
    public void execute(String nextKey, String value) {


    }

    public void flush() {
        
        if (cx != null)
            org.mozilla.javascript.Context.exit();

    }
}
