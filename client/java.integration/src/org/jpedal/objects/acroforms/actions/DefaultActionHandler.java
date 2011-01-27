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
* DefaultActionHandler.java
* ---------------
*/
package org.jpedal.objects.acroforms.actions;

//<start-os>
import com.idrsolutions.pdf.acroforms.xfa.XFAFormObject;
//<end-os>
import org.jpedal.PdfDecoder;
import org.jpedal.io.PdfObjectReader;
import org.jpedal.objects.Javascript;
import org.jpedal.objects.acroforms.decoding.FormStream;
import org.jpedal.objects.acroforms.formData.FormObject;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.objects.acroforms.utils.ConvertToString;
import org.jpedal.objects.raw.PdfObject;
import org.jpedal.utils.BrowserLauncher;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Messages;
import org.jpedal.utils.Strip;

import java.io.File;
import java.io.IOException;
import java.util.HashMap;
import java.util.Map;
import java.util.StringTokenizer;


public class DefaultActionHandler implements ActionHandler {
	
	final static private boolean showMethods = false;

	private PdfObjectReader currentPdfFile;
	private Javascript javascript;

	private AcroRenderer acrorend;

    private ActionFactory actionFactory;

    //handle so we can access
	private PdfDecoder decode_pdf;

	private int pageHeight,insetH;

	public void init(PdfDecoder decode_pdf, Javascript javascript, AcroRenderer acrorend) {
		if(showMethods)
			System.out.println("DefaultActionHandler.init()");

		currentPdfFile = decode_pdf.getIO();
		this.javascript = javascript;
		this.acrorend = acrorend;
		this.decode_pdf = decode_pdf;

    }
	
	public void init(PdfObjectReader pdfFile, Javascript javascript, AcroRenderer acrorend) {
		if(showMethods)
			System.out.println("DefaultActionHandler.init()");

		currentPdfFile = decode_pdf.getIO();
		this.javascript = javascript;
		this.acrorend = acrorend;
		
    }

	public void setPageAccess(int pageHeight, int insetH) {
		if(showMethods)
			System.out.println("DefaultActionHandler.setPageAccess()");
		
		this.pageHeight=pageHeight;
		this.insetH=insetH;
	}

    public void setActionFactory(ActionFactory actionFactory) {
    	if(showMethods)
    		System.out.println("DefaultActionHandler.setActionFactory()");

        actionFactory.setPDF(decode_pdf,acrorend);
        this.actionFactory=actionFactory;

    }

    /**
	 * creates a returns an action listener that will change the down icon for each click
	 */
	public Object setupChangingDownIcon(Object downOff, Object downOn) {
		if(showMethods)
			System.out.println("DefaultActionHandler.setupChangingDownIcon()");
		
		return new SwingDownIconListener(downOff, downOn);
	}

	/**
	 * sets up the captions to change as needed
	 */
	public Object setupChangingCaption(String normalCaption, String rolloverCaption, String downCaption) {
		if(showMethods)
			System.out.println("DefaultActionHandler.setupChangingCaption()");
		
		return new SwingFormButtonListener(normalCaption, rolloverCaption, downCaption);
	}

	/**
	 * set the combobox to show its options on entry to field
	 */
	public Object setComboClickOnEntry(String ref) {
		if(showMethods)
			System.out.println("DefaultActionHandler.setComboClickOnEntry()");
		
		return new SwingFormButtonListener(ref, "comboEntry", (AcroRenderer) null);
	}
	
	public Object setHoverCursor(){
		if(showMethods)
			System.out.println("DefaultActionHandler.setHoverCursor()");
		
		return actionFactory.getHoverCursor();
		
	}

	//<start-os>
	/**
	 * sets up the specified action
	 */
	public Object setupXFAAction(int activity, String scriptType, String script) {
		if(showMethods)
			System.out.println("DefaultActionHandler.setupXFAAction()");
		
		switch (activity) {
		case XFAFormObject.ACTION_MOUSECLICK:
		case XFAFormObject.ACTION_MOUSEENTER:
		case XFAFormObject.ACTION_MOUSEEXIT:
		case XFAFormObject.ACTION_MOUSEPRESS:
		case XFAFormObject.ACTION_MOUSERELEASE:
			return new SwingFormButtonListener(activity, scriptType, script, acrorend);
		default:
		}
		return null;
	}
	//<end-os>

	/**
	 * A action when pressed in active area ?some others should now be ignored?
	 */
	public void A(Object raw, FormObject formObj, int eventType) {
		if(showMethods)
			System.out.println("DefaultActionHandler.A()");
		
		int popupFlag=formObj.getActionFlag();

		/* may need to add static variables for type of actions, e.g. MOUSEPRESSED, MOUSEENTERED etc */
		Object data = formObj.getAobj();

		int typeFlag = formObj.getType();
		if (typeFlag == FormObject.FORMSIG) {

			additionalAction_Signature(formObj, eventType);

		} else if (popupFlag>0 || data instanceof Map) {
			Map aDataMap=new HashMap();

			if(data!=null)
				aDataMap = (Map) data;

			//S is Name of action
			if (aDataMap.containsKey("S")) {
//				String command = (String) currentPdfFile.resolveToMapOrString("S", aDataMap.get("S"));
				String command = (String) aDataMap.get("S");
				command = Strip.checkRemoveLeadingSlach(command);

				////@turn key into ID. may give idea

				if (command.equals("Named")) {

					if (aDataMap.containsKey("N")) {
//						String nameCmd = (String) currentPdfFile.resolveToMapOrString("N", aDataMap.get("N"));
						String nameCmd = (String) aDataMap.get("N");
						nameCmd = Strip.checkRemoveLeadingSlach(nameCmd);

						if (nameCmd.equals("Print")) {
							additionalAction_Print(eventType);
						} else if (nameCmd.equals("SaveAs")) {

							//TODO save the pdf, with changes to forms

							LogWriter.writeFormLog("Named Action=SaveAs pagenumber=" + /*currentPdfFile.resolveToMapOrString("Pagenumber", */aDataMap.get("PageNumber")/*)*/, FormStream.debugUnimplemented);
							//       activateData.put("SaveAs", "currentPage");

						} else if (nameCmd.startsWith("AcroForm:")) {

							//Named action, spec says optional to viewer application to resolve
							if (FormStream.debug)
								System.out.println("Named action AcroForm:" + nameCmd);

						} else if (nameCmd.startsWith("NextPage")) {

							//move to next page
							LogWriter.writeFormLog("Named action NextPage NOT implemented" + nameCmd, FormStream.debugUnimplemented);

						} else if (nameCmd.startsWith("ZoomTo")) {
							//bring up zoom options

						} else if (nameCmd.startsWith("FullScreen")) {
							//bring up page in full screen
							LogWriter.writeFormLog("Named action FullScreen NOT implemented" + nameCmd, FormStream.debugUnimplemented);
						} else {
							LogWriter.writeFormLog("{stream} Named Action NOT IMPLEMENTED command=" + nameCmd +
									" field=" + ConvertToString.convertMapToString(aDataMap, null/*currentPdfFile*/), FormStream.debugUnimplemented);
						}
					}
				} else if (command.equals("GoTo") && aDataMap.containsKey("D")) {

					addtionalAction_Goto(formObj, eventType);

				} else if (command.equals("ResetForm")) {

					additionalAction_ResetForm(eventType);

				} else if (command.equals("SubmitForm")) {

					//TODO check over as could be improved.
					additionalAction_SubmitForm(eventType, aDataMap);

				} else if (command.equals("JavaScript")) {
					LogWriter.writeFormLog("{stream} JavaScript field=" + ConvertToString.convertMapToString(aDataMap, null/*currentPdfFile*/), FormStream.debugUnimplemented);

				} else if (command.equals("Hide")) {

					additionalAction_Hide(eventType, aDataMap);

				} else if (command.equals("URI")) {

					additionalAction_URI(eventType, aDataMap);

				} else if (command.equals("Launch")) {

					LogWriter.writeFormLog("{stream} launch activate action NOT IMPLEMENTED", FormStream.debugUnimplemented);
					
					//TODO poss delete
				} else if (command.equals("GoTo")) {
					//A /GoTo action with value "D" specifiying a destination to jump to
					LogWriter.writeFormLog("{FormStream.resolveAdditionalAction} /GoTo action NOT IMPLEMENTED", FormStream.debugUnimplemented);
					//TODO poss delete
				} else if (command.equals("GoToR")) {

					addAdditionalAction_GotoR(eventType, aDataMap);

				} else {
					LogWriter.writeFormLog("{stream} UNKNOWN command for Activate Action command=" +
							command + " field=" + ConvertToString.convertMapToString(aDataMap, null/*currentPdfFile*/), FormStream.debugUnimplemented);
				}

			} else if (aDataMap.containsKey("Dest")) {
				if (eventType == MOUSECLICKED) {

					//retrieve values sotored and goto page
					Map destMap = (Map) ((Map) formObj.getAobj()).get("Dest");
					int pageNumber = decode_pdf.getPageFromObjectRef((String) destMap.get("Page"));
					
					changeTo(null, pageNumber, destMap.get("Position"));
                }else
                    actionFactory.setCursor(eventType);


			} else if (formObj.getActionFlag()==FormObject.POPUP) {

                actionFactory.popup(raw,formObj,currentPdfFile, pageHeight,insetH);
                
			} else {
				LogWriter.writeFormLog("{stream} Activate Action UNKNOWN command NOT IMPLEMENTED field=" +
						ConvertToString.convertMapToString(aDataMap, null/*currentPdfFile*/), FormStream.debugUnimplemented);
			}

			//formObject.setActivateAction(activateData);
		} else if (data != null) {
			LogWriter.writeFormLog("{stream} A additionalAction NON Map UNIMPLEMENTED", FormStream.debugUnimplemented);
		}
	}


	private void addAdditionalAction_GotoR(int eventType, Map aDataMap) {
		if(showMethods)
			System.out.println("DefaultActionHandler.addAdditionalAction_GotoR()");
		
		if (eventType == MOUSECLICKED) {
			//A /GoToR action is a goto remote file action,
			//F specifies the file
			//D specifies the location or page

			String stpage = (String) aDataMap.get("D");
//			Map dataMap = (Map) currentPdfFile.resolveToMapOrString("F", aDataMap.get("F"));
			Map dataMap = (Map) aDataMap.get("F");
			String type = (String) dataMap.get("Type");

			String file = (String) dataMap.get("F");
			if (file.startsWith("(")) {
				file = file.substring(1, file.length() - 1);
			}

			if (stpage.startsWith("(")) {
				stpage = stpage.substring(1, stpage.length() - 1);
			}

			int page;
			int index = stpage.indexOf("P.");
			if (index != -1) {
				stpage = stpage.substring(index + 2, stpage.length());
				page = Integer.parseInt(stpage);
			} else if (stpage.equals("F")) {
				//use file only
				page = 1;
			} else {
				page = 1;
			}

			if (type.equals("/Filespec")) {
				if (file.startsWith("./")) {
					file = new File(file.substring(2, file.length())).getAbsolutePath();
				}
				if (file.startsWith("../")) {
					String tmp = new File("").getAbsolutePath();
					file = tmp.substring(0, tmp.lastIndexOf('\\') + 1) + file.substring(3, file.length());
				}

				if (new File(file).exists()) {

					//Open this file, on page 'page'
					changeTo(file, page, null);

					LogWriter.writeFormLog("{DefaultActionHamdler.A} Form has GoToR command, needs methods for opening new file on page specified", FormStream.debugUnimplemented);
				} else {
					actionFactory.showMessageDialog("The file specified " + file + " Does Not Exist!");
				}
			} else {
				LogWriter.writeFormLog("{CustomMouseListener.mouseClicked} GoToRemote NON Filespec NOT IMPLEMENTED", FormStream.debugUnimplemented);
			}

			//				((JComponent)currentComp).setToolTipText(text);
		}else
            actionFactory.setCursor(eventType);
	}

	private void additionalAction_URI(int eventType, Map aDataMap) {
		if(showMethods)
			System.out.println("DefaultActionHandler.additionalAction_URI()");
		//URL command
		String url = removeBrackets((String) aDataMap.get("URI"));

		if (eventType == MOUSECLICKED) {
			try {
				BrowserLauncher.openURL(url);
			} catch (IOException e1) {
				actionFactory.showMessageDialog(Messages.getMessage("PdfViewer.ErrorWebsite"));
			}
		}else
            actionFactory.setCursor(eventType);
	}

    private void additionalAction_Hide(int eventType, Map aDataMap) {
    	if(showMethods)
    		System.out.println("DefaultActionHandler.additionalAction_Hide()");

        if (eventType == MOUSEPRESSED) {

            Map hideMap = new HashMap();
            getHideMap(aDataMap, hideMap);

            actionFactory.setFieldVisibility(hideMap);
        }
    }

	private void additionalAction_SubmitForm(int eventType, Map aDataMap) {
		if(showMethods)
			System.out.println("DefaultActionHandler.additionalAction_SubmitForm()");

        if (eventType == MOUSECLICKED) {

	        if (aDataMap.containsKey("Fields")) {
				StringTokenizer fieldsTok = new StringTokenizer((String) aDataMap.get("Fields"), "[]()");
				String tok, preName = null;
				StringBuffer names = new StringBuffer();
				while (fieldsTok.hasMoreTokens()) {
					tok = fieldsTok.nextToken();
					if (tok.indexOf(".x") != -1) {
						preName = tok.substring(tok.indexOf('.') + 1, tok.indexOf(".x") + 1);
					}
					if (tok.indexOf(" R") != -1) {
                        String ref=tok.trim();
                        tok = (String) currentPdfFile.readObject(new PdfObject(ref), ref, false, null).get("T");
						if (preName != null) {
							names.append(preName);
						}
						names.append(tok.substring(1, tok.length() - 1));
						names.append(',');
					}
				}
				aDataMap.put("Fields", names.toString());
			}

			String submitURL;

			Object fObj = aDataMap.get("F");
			if (fObj instanceof Map) {
				submitURL = (String) ((Map) fObj).get("F");
				if (submitURL.startsWith("(")) {
					submitURL = submitURL.substring(1, submitURL.length() - 1);
				}
			} else {
				submitURL = (String) fObj;
				if (submitURL.startsWith("(")) {
					submitURL = submitURL.substring(1, submitURL.length() - 1);
				}
			}

            actionFactory.submitURL(aDataMap, submitURL);

        }
	}

    private void additionalAction_ResetForm(int eventType) {
    	if(showMethods)
    		System.out.println("DefaultActionHandler.additionalAction_ResetForm()");
    	
		if (eventType == MOUSEPRESSED)
            actionFactory.reset();

	}

    private void addtionalAction_Goto(FormObject formObj, int eventType) {
    	if(showMethods)
    		System.out.println("DefaultActionHandler.addtionalAction_Goto()");
    	
		if (eventType == MOUSECLICKED) {
			//retrieve values stored and goto page
			String ref = (String) ((Map) formObj.getAobj()).get("D");
			String pageRef = ref.substring(1, ref.indexOf('/') - 1);
			int pageNumber = decode_pdf.getPageFromObjectRef(pageRef);

			// todo potentially can recover the location of the goto using FitH and FitR values

			changeTo(null, pageNumber,null);
        }else
            actionFactory.setCursor(eventType);

	}

	private void additionalAction_Print(int eventType) {
		if(showMethods)
			System.out.println("DefaultActionHandler.additionalAction_Print()");

        if (eventType == MOUSERELEASED)
            actionFactory.print();

	}

    /**
     * display signature details in popup frame
     * @param formObj
     * @param eventType
     */
    private void additionalAction_Signature(FormObject formObj, int eventType) {
    	if(showMethods)
    		System.out.println("DefaultActionHandler.additionalAction_Signature()");
    	
		if (eventType == MOUSECLICKED) {

            Map sigObject = decode_pdf.getFormRenderer().getSignatureObject(formObj.getPDFRef());

			if (sigObject == null)
				return;

            actionFactory.showSig(sigObject);

		} else
            actionFactory.setCursor(eventType);
    }

	/**
	 * this calls the PdfDecoder to open a new page and change to the correct page and location on page,
	 * is any value is null, it means leave as is.
	 */
	public void changeTo(String file, int page, Object location) {
		if(showMethods)
			System.out.println("DefaultActionHandler.changeTo()");

		//open file 'file'
		if (file != null) {
			try {
				decode_pdf.openPdfFile(file);
			} catch (Exception e) {
			}
		}

		//change to 'page'
		if (page != -1) {
			if (page > 0 && page < decode_pdf.getPageCount()) {
				try {
					decode_pdf.decodePage(page);

					decode_pdf.updatePageNumberDisplayed(page);
				} catch (Exception e) {
					e.printStackTrace();
				}

				/**reset as rotation may change!*/
				decode_pdf.setPageParameters(-1, page);

			}
		}

        actionFactory.setPageandPossition(location);


	}

	/**
	 * E action when cursor enters active area
	 */
	public void E(Object e, FormObject formObj) {
		if(showMethods)
			System.out.println("DefaultActionHandler.E()");
		
		//<start-os>
		javascript.execute(formObj.getFieldName(), ActionHandler.E, ActionHandler.FOCUS_EVENT, ' ');
		//<end-os>
	}

	/**
	 * X action when cursor exits active area
	 */
	public void X(Object e, FormObject formObj) {
		if(showMethods)
			System.out.println("DefaultActionHandler.X()");
		
		//<start-os>
		javascript.execute(formObj.getFieldName(), ActionHandler.X, ActionHandler.FOCUS_EVENT, ' ');
		//<end-os>
	}

	/**
	 * D action when cursor button pressed inside active area
	 */
	public void D(Object e, FormObject formObj) {
		if(showMethods)
			System.out.println("DefaultActionHandler.D()");

		//<start-os>
		javascript.execute(formObj.getFieldName(), ActionHandler.D, ActionHandler.FOCUS_EVENT, ' ');
		//<end-os>
	}

	/**
	 * U action when cursor button released inside active area
	 */
	public void U(Object e, FormObject formObj) {
		if(showMethods)
			System.out.println("DefaultActionHandler.U()");

		//<start-os>
		javascript.execute(formObj.getFieldName(), ActionHandler.U, ActionHandler.FOCUS_EVENT, ' ');
		//<end-os>
	}

	/**
	 * Fo action on input focus
	 */
	public void Fo(Object e, FormObject formObj) {     //todo @MARK called with focus gained
		if(showMethods)
			System.out.println("DefaultActionHandler.Fo()");
		
		//javascript.executeAction(pageNumber,ActionHandler.Fo);
	}

	/**
	 * Bl action when input focus lost
	 */
	public void Bl(Object e, FormObject formObj) {           //todo @Mark called by focus lost
		if(showMethods)
			System.out.println("DefaultActionHandler.Bl()");
		//note: this is Bl (capital B, lower case L)
		//javascript.executeAction(pageNumber,ActionHandler.BI);
	}

	/**
	 * O
	 */
	public void O(int pageNumber) {
		if(showMethods)
			System.out.println("DefaultActionHandler.O()");

		Map Oaction=null;//hack as not yet implemented
		
		//<start-os>
		if(Oaction!=null)
		javascript.executeAction(pageNumber, ActionHandler.O);
		//<end-os>
	}

	/**
	 * PO action when page containing is opened,
	 * actions O of pages AA dic, and OpenAction in document catalog should be done first
	 */
	public void PO(int pageNumber) {
		if(showMethods)
			System.out.println("DefaultActionHandler.PO()");

		Map POaction=null;//hack as not yet implemented
		
		//<start-os>
		if(POaction!=null)
		javascript.executeAction(pageNumber, ActionHandler.PO);
		//<end-os>
	}

	/**
	 * PC action when page is closed, action C from pages AA dic follows this
	 */
	public void PC(int pageNumber) {
		if(showMethods)
			System.out.println("DefaultActionHandler.PC()");

		//<start-os>
		javascript.executeAction(pageNumber, ActionHandler.PC);
		//<end-os>
	}

	/**
	 * PV action on viewing containing page
	 */
	public void PV(int pageNumber) {
		if(showMethods)
			System.out.println("DefaultActionHandler.PV()");

		//<start-os>
		javascript.executeAction(pageNumber, ActionHandler.PV);
		//<end-os>
	}

	/**
	 * PI action when no longer visible in viewer
	 */
	public void PI(int pageNumber) {
		if(showMethods)
			System.out.println("DefaultActionHandler.PI()");

		//<start-os>
		javascript.executeAction(pageNumber, ActionHandler.PI);
		//<end-os>
	}

	/**
	 * K action on - [javascript]
	 * keystroke in textfield or combobox
	 * modifys the list box selection
	 * (can access the keystroke for validity and reject or modify)
	 */
	public int K(Object ex, FormObject formObj, int actionID) {
		if(showMethods)
			System.out.println("DefaultActionHandler.K()");

		int val = 0;

		//<start-os>
		val = javascript.execute(formObj.getFieldName(), ActionHandler.K, actionID, actionFactory.getKeyPressed(ex));
		//<end-os>

		return val;
	}


	/**
	 * F the display formatting of the field (e.g 2 decimal places) [javascript]
	 */
	public void F(Object e, FormObject formObj) {
		if(showMethods)
			System.out.println("DefaultActionHandler.F()");

		//<start-os>
		javascript.execute(formObj.getFieldName(), ActionHandler.F, ActionHandler.FOCUS_EVENT, ' ');
		//<end-os>
	}

	/**
	 * V action when fields value is changed [javascript]
	 */
	public void V(Object ex, FormObject formObj, int actionID) {
		if(showMethods)
			System.out.println("DefaultActionHandler.V()");

		//<start-os>
		javascript.execute(formObj.getFieldName(), ActionHandler.V, actionID, actionFactory.getKeyPressed(ex));
		//<end-os>
	}

	/**
	 * C action when another field changes (recalculate this field) [javascript]
	 * <p/>
	 * NOT actually called as called from other other objects but here for completeness
	 */
	public void C(Object e, FormObject formObj) {
		if(showMethods)
			System.out.println("DefaultActionHandler.C()");

		//<start-os>
		javascript.execute(formObj.getFieldName(), ActionHandler.C2, ActionHandler.FOCUS_EVENT, ' ');
		//<end-os>
	}

	private String removeBrackets(String text) {
		if (text.startsWith("(") || text.startsWith("[") || text.startsWith("{")) {

			if (text.endsWith(")"))
				return text.substring(1, text.length() - 1);
			else
				return text.substring(1, text.length() - 2);
		} else {
			return text;
		}
	}

	/**
	 * goes through the map and adds the required data to the hideMap and returns it
	 */
	private void getHideMap(Map aDataMap, Map hideMap) {
		if(showMethods)
			System.out.println("DefaultActionHandler.getHideMap()");

		if (!Strip.checkRemoveLeadingSlach((String)/*currentPdfFile.resolveToMapOrString("S", */aDataMap.get("S")/*)*/).equals("Hide")) {
			LogWriter.writeFormLog("{stream} getHideMap has a NON Hide value field=" + aDataMap, FormStream.debugUnimplemented);
		}

		String[] fields;
		if (hideMap.containsKey("fields")) {
			String[] mapToAdd = (String[]) hideMap.get("fields");
			fields = new String[mapToAdd.length + 1];
			System.arraycopy(mapToAdd, 0, fields, 0, mapToAdd.length);
			fields[fields.length - 1] = (String)/*currentPdfFile.resolveToMapOrString("T", */aDataMap.get("T")/*)*/;
		} else {
			fields = new String[]{(String)/*currentPdfFile.resolveToMapOrString("T", */aDataMap.get("T")/*)*/};
		}
		hideMap.put("fields", fields);


		Boolean hideFlag = Boolean.TRUE;
		if (aDataMap.containsKey("H")) {
			hideFlag = Boolean.valueOf((String)/* currentPdfFile.resolveToMapOrString("H", */aDataMap.get("H")/*)*/);
		}

		Boolean[] hideFlags;
		if (hideMap.containsKey("hide")) {
			Boolean[] mapToAdd = (Boolean[]) hideMap.get("hide");
			hideFlags = new Boolean[mapToAdd.length + 1];
			System.arraycopy(mapToAdd, 0, hideFlags, 0, mapToAdd.length);
			hideFlags[hideFlags.length - 1] = hideFlag;
		} else {
			hideFlags = new Boolean[]{hideFlag};
		}
		hideMap.put("hide", hideFlags);


		if (aDataMap.containsKey("Next")) {
			getHideMap((Map)/*currentPdfFile.resolveToMapOrString("Next", */aDataMap.get("Next")/*)*/, hideMap);
		}
	}
}

