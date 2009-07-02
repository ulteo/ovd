//
//  Copyright (C) 2001-2004 HorizonLive.com, Inc.  All Rights Reserved.
//  Copyright (C) 2002 Constantin Kaplinsky.  All Rights Reserved.
//  Copyright (C) 1999 AT&T Laboratories Cambridge.  All Rights Reserved.
//
//  This is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
//
//  This software is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this software; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,
//  USA.
//


package org.ulteo;

import org.sshvnc.Viewer;

import javax.swing.JOptionPane;

public class OvdApplet extends org.sshvnc.Applet implements java.lang.Runnable {

    public static final String version = "0.2.4";
    public boolean startupPreload = false;
    public String startuponLoad = "";
    public String startuponFailure = "";
    public String startuponBadPing = "";

/*
	    String[] sTemp = sshPort.split(",");
	    int[] arrayPorts = new int[sTemp.length];
	    for(int i=0; i<sTemp.length; i++){
		try{
		    arrayPorts[i] = Integer.parseInt(sTemp[i]);
		    properties.setPort(arrayPorts[i],i);
		}catch(NumberFormatException nfe){
		    System.err.println("One of the entered ports is not valid "+sTemp[i]);
		    throw nfe;
		}
	    }
		}

*/



    /**
     * Sets the value of the given Javascript variable
     *
     * @param varName
     * @param value
     */
    /*  public void setJavaScriptVariable(String varName, String value){
	JSObject window = JSObject.getWindow(this);
	window.setMember("testResult", value);
	}*/

    /**
     * Sets the value of the given Javascript variable
     *
     * @param varName
     * @param value
     */
    /* public void setJavaScriptVariable(String varName, int value){
       this.setJavaScriptVariable(varName, ""+value);
       }*/

    /**
     * Calls the given Javascript method with its parameters
     *
     * @param methodName
     * @param value
     */
    /*public void callJavaScriptMethod(String methodName, Object[] args) {
      JSObject window = JSObject.getWindow(this);
      try {
      window.call(methodName, args);
      System.out.println("JS method called");
      } catch(JSException ex) {
      //		  This method doesn't like it when the args are null (although it works in Konqueror)
      System.err.println("Ouch: " + ex.getMessage());
      setJavaScriptVariable("testResult", testResult);

      }
      }*/


    /**
     * testFinished()
     * According to the value of testResult, call one of the three
     * URLs from the parameters: urlOnPass, urlOnFail, urlLowPing
     */
    /* public void testFinished(int testResult, long avgPing){

    //1. if fails --> red
    //2. if high ping --> yellow
    //3. else --> green
    System.out.println("Test result: "+testResult);
    String methodName = null;
    Object[] methodArgs = null;

    try{
    if (testResult < 0){
    testResult = -testResult;
    methodName = startuponFailure;
    methodArgs = new Object[1];
    methodArgs[0] = ""+testResult;
    openUrl("javascript:"+startuponFailure+"("+testResult+")");
    } else if (avgPing > readIntParameter("maxPingAccepted", 250)){
    methodName = startuponBadPing;
    openUrl("javascript:"+startuponBadPing);
    } else {
    methodName = startuponLoad;
    openUrl("javascript:"+startuponLoad);
    }
    }catch(Exception e){
    System.err.println("Couldn't execute javascript "+e.getMessage());

    callJavaScriptMethod(methodName, methodArgs);
    }

    }*/


    //
    // init()
    //

    @Override
	public void init() {

	 

	/*if(rt.totalMemory() == rt.maxMemory() && rt.freeMemory() < 11000000){
	  System.err.println("Not enough memory to start the applet");
	  JOptionPane.showMessageDialog(null, "Your Java Machine is low on virtual memory.\nPlease restart the browser before launching Ulteo Online Desktop", "Warning", JOptionPane.ERROR_MESSAGE);
	  }else if(startupPreload){
	  //connectImmediately = false;
	  return;
	  }else{
	  getParameters();

	  this.add("Center", this);
	*/


	//this.obj = new Viewer(this);
	//this.obj.arg_parser = new ArgParser(this);
	//this.obj.getParameters();

	Thread rfbThread = new Thread(this);
	rfbThread.start();
	
    }

 
    //
    // run() - executed by the rfbThread to deal with the RFB socket.
    //

    public void run() {
		//this.obj.start();
// ArnauVP: we read the whole list and we'll parse it later
    }

    //
    // Show message text and optionally "Relogin" and "Close" buttons.
    //

    void showMessage(String msg) {
	//vncContainer.removeAll();
	JOptionPane.showMessageDialog(this, "The Online Desktop has closed.\n" +
				      "Thanks for using our service!\n", "Online Desktop session finished",JOptionPane.INFORMATION_MESSAGE);
    	//System.err.println("ERROR: "+msg+"\n");
    }

    //
    // Stop the applet.
    // Main applet thread will terminate on first exception
    // after seeing that rfbThread has been set to null
    //

  
    public String getAppletInfo() {
	return "UlteoVNC";
    }

    public String getHostToPing(){
	return getParameter("hostToPing");
    }

}
