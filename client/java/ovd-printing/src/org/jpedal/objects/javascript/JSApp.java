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
* JSApp.java
* ---------------
*/
package org.jpedal.objects.javascript;

import org.mozilla.javascript.Scriptable;

public class JSApp extends org.mozilla.javascript.ScriptableObject {


    public Object platfXorm="wwww";

    public void jsFunction_alert(){
        System.out.println("xx");
    }

    public void js_alert(Object a, Object b, Object c){
        System.out.println("xx");
    }

    public int   jsFunction_alert(String a, int b, int c){
        System.out.println("xx");
        return 1;
    }
    // The zero-argument constructor used by Rhino runtime to create instances
    public JSApp() { }

    // Method jsConstructor defines the JavaScript constructor
    public void jsConstructor(int a) { count = a; }

    // The class name is defined by the getClassName method
    public String getClassName() { return "App"; }

    // The method jsGet_count defines the count property.
    public int jsGet_count() { return count++; }

    // Methods can be defined using the jsFunction_ prefix. Here we define
    //  resetCount for JavaScript.
    public void jsFunction_resetCount() { count = 0; }

    private int count;
}
