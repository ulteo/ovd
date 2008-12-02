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
* DefaultAcroRenderer.java
* ---------------
*/
package org.jpedal.objects.acroforms.rendering;
//<start-os>
import com.idrsolutions.pdf.acroforms.xfa.XFAFormStream;
import org.jpedal.objects.javascript.ExpressionEngine;
//<end-os>
import org.jpedal.PdfDecoder;
import org.jpedal.exception.PdfException;
import org.jpedal.external.LinkHandler;
import org.jpedal.external.Options;
import org.jpedal.io.PdfObjectReader;
import org.jpedal.objects.Javascript;
import org.jpedal.objects.PdfAnnots;
import org.jpedal.objects.PdfFormData;
import org.jpedal.objects.PdfPageData;
import org.jpedal.objects.acroforms.actions.ActionHandler;
import org.jpedal.objects.acroforms.actions.DefaultActionHandler;
import org.jpedal.objects.acroforms.creation.FormFactory;
import org.jpedal.objects.acroforms.creation.SwingFormFactory;
import org.jpedal.objects.acroforms.decoding.AnnotStream;
import org.jpedal.objects.acroforms.decoding.FormDecoder;
import org.jpedal.objects.acroforms.decoding.FormStream;
import org.jpedal.objects.acroforms.formData.FormObject;
import org.jpedal.objects.acroforms.formData.GUIData;
import org.jpedal.objects.acroforms.formData.SwingData;
import org.jpedal.objects.acroforms.utils.ConvertToString;

import org.jpedal.objects.raw.PdfObject;

import java.util.*;

/**
 * Provides top level to forms handling, assisted by separate classes to
 * decode widgets (FormDecoder - default implements Swing set)
 * create Form widgets (implementation of FormFactory), 
 * store and render widgets (GUIData),
 * handle Javascript and Actions (Javascript and ActionHandler)
 * and support for Signature object
 */
public class DefaultAcroRenderer implements AcroRenderer {
	
	private static final boolean showFormsDecoded=false;
	
	/**
	 * holds all the raw data from the PDF in order read from PDF
	 **/
	private List FacroFormDataList;

    private List AacroFormDataList;


	/**
	 * flags used to debug code
	 */
	final private static boolean showMethods = false;
	final private static boolean identifyType = false;
	final private static boolean debug = false;

    /**
     * flag to show we ignore forms
     */
    private boolean ignoreForms=false;

    /**
	 * creates all GUI components from raw data in PDF and stores in GUIData instance
	 */
	private FormFactory formFactory;

	/**holder for all data (implementations to support Swing and ULC)*/
	private GUIData compData=new SwingData();

	/**holds sig object so we can easily retrieve*/
	private Set sigObject=null;

	/**
	 * flags for types of form
	 */
	public static final int ANNOTATION = 1;
	public static final int FORM = 2;
	public static final int XFAFORM = 3;

	/**
	 * holds copy of object to access the mediaBox and cropBox values
	 */
	private PdfPageData pageData;

	/**
	 * number of form fields in total for this document
	 */
	private int AformCount = 0,FformCount;

	/**
	 * number of entries in acroFormDataList, each entry can have a button group of more that one button
	 */
	private int AfieldCount = 0,FfieldCount = 0;

	/**
	 * number of pages in current PDF document
	 */
	protected int pageCount = 0;

	/**
	 * handle on object reader for decoding objects
	 */
	private PdfObjectReader currentPdfFile;

	/**
	 * parses and decodes PDF data into generic data for converting to widgets
	 */
	private FormDecoder fDecoder,annotDecoder;

	/**
	 * handles events like URLS, EMAILS
	 */
	private ActionHandler formsActionHandler;

	/**
	 * allow user to trap own events - not currently used
	 */
	private LinkHandler linkHandler;

	/**
	 * handles Javascript events and updates components
	 */
	private Javascript javascript;

	//<start-os>
	/**
	 * used in Javascript to evaluate and execute Javascript validation
	 */
	private ExpressionEngine userExpressionEngine;
	//<end-os>

	/**
	 * local copy of inset on Height for use in displaying page
	 */
	private int insetH;
    private Map formKids;

    /*flag to show if XFA or FDF*/
    private boolean hasXFA=false;

    /**
	 * setup new instance but does not do anything else
	 */
	public DefaultAcroRenderer() {}

	/**
	 * reset handler (must be called Before page opened)
	 * - null Object resets to default
	 */
	public void resetHandler(Object userActionHandler,PdfDecoder decode_pdf, int type) {

		if(type==Options.FormsActionHandler){
			
			if (userActionHandler != null)
				formsActionHandler = (ActionHandler) userActionHandler;
			else
				formsActionHandler = new DefaultActionHandler();

			formsActionHandler.init(decode_pdf, javascript,this);

			if (formFactory != null){
				formFactory.reset(this, formsActionHandler);
				compData.resetDuplicates();
			}
		}else if(type==Options.LinkHandler){
			if (userActionHandler != null)
				linkHandler = (LinkHandler) userActionHandler;
			else
				linkHandler = null;
		}
	}

	/**
	 * make all components invisible on all pages by removing from Display
	 */
	public void removeDisplayComponentsFromScreen() {

		if (showMethods)
			System.out.println("removeDisplayComponentsFromScreen ");

		if(compData!=null)
		compData.removeAllComponentsFromScreen();

	}

	/**
	 * initialise holders and variables and get a handle on data object
	 * <p/>
	 * Complicated as Annotations stored on a PAGE basis whereas FORMS stored on
	 * a file basis
	 */
	public void resetFormData(Object obj, int insetW, int insetH, PdfPageData pageData, PdfObjectReader currentPdfFile, Map formKids) {

		//System.out.println("init Form");
		
		this.insetH=insetH;
		this.currentPdfFile = currentPdfFile;
		this.pageData = pageData;
        this.formKids=formKids;

        //explicitly flush
		sigObject=null;

		//track inset on page
		compData.setPageData(pageData,insetW,insetH);

		if (obj == null) {
			
			FacroFormDataList = null;
			FformCount = 0;
			FfieldCount = 0;
			
		} else{

			//upcast to form
			PdfFormData acroFormData = (PdfFormData) obj;

            hasXFA=acroFormData.hasXFAFormData();
			/**
			 * choose correct decoder for form data
			 */
			if (hasXFA) {

				//<start-os>
				try {
					fDecoder = new XFAFormStream(
							acroFormData.getXFAFormData(PdfFormData.XFA_CONFIG),
							acroFormData.getXFAFormData(PdfFormData.XFA_DATASET),
							acroFormData.getXFAFormData(PdfFormData.XFA_TEMPLATE),
							currentPdfFile);
				} catch (PdfException e) {
					e.printStackTrace();
				}
				//<end-os>

			} else
				fDecoder = new FormStream(currentPdfFile);

			FacroFormDataList = acroFormData.getFormData();
			FformCount = acroFormData.getTotalCount();
			FfieldCount = this.FacroFormDataList.size();

		}

		resetContainers(true);

	}

	/**
	 * initialise holders and variables and get a handle on data object
	 * <p/>
	 * Complicated as Annotations stored on a PAGE basis whereas FORMS stored on
	 * a file basis
	 */
	public void resetAnnotData(Object obj, int insetW, int insetH, PdfPageData pageData, PdfObjectReader currentPdfFile, Map formKids) {

		this.insetH=insetH;
		this.currentPdfFile = currentPdfFile;
		this.pageData = pageData;
        this.formKids=formKids;

		boolean resetToEmpty = true;

		//track inset on page
		compData.setPageData(pageData,insetW,insetH);

		if (obj == null) {
			
				AacroFormDataList = null;
				AformCount = 0;
				AfieldCount = 0;
			
		}else{

			//upcast to Annot
			PdfAnnots annotData = (PdfAnnots) obj;

			annotDecoder = new AnnotStream(currentPdfFile);

            if(AacroFormDataList==null)
				AacroFormDataList=annotData.getAnnotRawDataList();
			else
				AacroFormDataList.addAll(annotData.getAnnotRawDataList());

            //fix bug in size
            int size = AacroFormDataList.size();
			AformCount = size;
			AfieldCount = size;

            resetToEmpty = false;
		}

		resetContainers(resetToEmpty);

	}
	
	/**
	 * flush or resize data containers
	 */
	protected void resetContainers(boolean resetToEmpty) {
//System.out.println(this+" DefaultAcroRenderer.resetContainers()");
		if (debug)
			System.out.println("DefaultAcroRenderer.resetContainers()");

        /**form or reset Annots*/
		if (resetToEmpty) {

			//flush list
			if(fDecoder!=null)
				fDecoder.resetItems();
            if(annotDecoder!=null)
				annotDecoder.resetItems();

            compData.resetComponents(AformCount+FformCount, pageCount, false);

		}else
		    compData.resetComponents(AformCount+FformCount,pageCount,true);

		if (formFactory == null) {
			//TODO problem 
			formFactory = new SwingFormFactory(this, formsActionHandler);//,currentPdfFile);
//			System.out.println(this+" DefaultAcroRenderer.resetContainers() SWING FACTORY SET HERERER");
			//compData=new SwingData();
			//formFactory.setDataObjects(compData);
		} else {
			//to keep customers formfactory usable
			formFactory.reset(this, formsActionHandler);
			compData.resetDuplicates();
			//formFactory.setDataObjects(compData);
		}

        formsActionHandler.setActionFactory(formFactory.getActionFactory());

    }

	/**
	 * build forms display using standard swing components
	 */
	public void createDisplayComponentsForPage(int page) {

        final boolean debugNew=false;
		
		if (showMethods)
			System.out.println("createDisplayComponentsForPage " + page);


		/**see if already done*/
        int id=compData.getStartComponentCountForPage(page);
        if (id == -1 || id == -999) {

            /**ensure space for all values*/
        //breaks code in Viewer if opne non form then Costena
//            int maxFields=FfieldCount+AfieldCount;
//            if(maxFields>compData.getMaxFieldSize())
//                compData.resetComponents(AformCount+FformCount, pageCount, false);

            compData.initParametersForPage(pageData,page);

            //sync values
            if(formsActionHandler!=null){
            	formsActionHandler.init(currentPdfFile, javascript,this);

                formsActionHandler.setPageAccess(pageData.getMediaBoxHeight(page), insetH);
            }
            
            if(fDecoder != null)
                fDecoder.resetItems();

            /**
             * think this needs to be revised, and different approach maybe storing, and reuse if respecified in file,
             * need to look at other files to work out solution.
             * files :-
             * lettreenvoi.pdf page 2+ no next page field
             * costena.pdf checkboxes not changable
             *
             * maybe if its just reset on multipage files
             */

            /** ATTENTION
             *
             * fieldNum is the index in AcroFormDataList to get the next FORMOBJECT to be setup,
             * the FORMOBJECT can have a button group in which there may be more than one FORMFIELD.
             *
             * formNum is the index in allFields, to which the FORMFIELD will be stored
             */

            //list of forms done
            Map formsProcessed=new HashMap();

            FormObject[] xfaFormList = null, Fforms = new FormObject[FfieldCount],Aforms = new FormObject[AfieldCount];
            FormObject formObject;
            Map currentField;
            int i=0, Fcount=FfieldCount;

            if(debugNew)
                System.err.println("==============get Forms====================FfieldCount="+FfieldCount+" FacroFormDataList="+FacroFormDataList.size());

            //scan list for all relevant values and add to array if valid
            for (int fieldNum =FfieldCount-1; fieldNum >-1; fieldNum--) {

                currentField = (Map) FacroFormDataList.get(fieldNum);

                // work through raw form data parsing and converting into an intermediate Object we use to build components.
                if (page==getPageForComponent(page, currentField)) {

                    //stop child forms being processed twice
                    Object ref= currentField.get("obj");

                    //ensure not done
                    if(formsProcessed.get(ref)==null){

                        formObject = new FormObject();

                        fDecoder.createAppearanceString(formObject, currentField, currentPdfFile,page);

                        if (formObject!= null){

                            //added by mark to store Parent
                            String parent=(String) currentField.get("Parent");
                            if(parent!=null)
                                formObject.setParent(parent);

                            formObject.setPageNumber(page);
                            formObject.setPDFRef((String)ref);

                            Fforms[i++] = formObject;

                            //also flag children done and called in createField()
                            if(ref!=null){
                                formsProcessed.put(ref, "x");

                                Object children=formObject.getKidData();
                                if(children!=null){
                                    Iterator childForms=formObject.getKidData().keySet().iterator();
                                    while(childForms.hasNext()){
                                        String child=(String)childForms.next();
                                        formsProcessed.put(child, "x");
                                    }
                                }
                            }

                            compData.storeRawData(formObject); //store data so user can access
                        }
                    }
                }
            }

            if(debugNew)
                System.err.println("==============get Annots====================AfieldCount="+AfieldCount);

            //reset pointer
            i=0;
            
            Object ref=null;

            //scan list for all relevant values and add to array if valid
            for (int fieldNum =AfieldCount-1; fieldNum >-1; fieldNum--) {

            	//add in start and end for page above
            	
                currentField = (Map) AacroFormDataList.get(fieldNum);
                if(currentField==null)
                	continue;
                
                //currentField=currentPdfFile.readObject(new PdfObject(annot), annot,false, PdfAnnots.fields);

                ref=currentField.get("obj");

                //if(debugNew)
                  //  System.err.println(ref+"=="+" "+page+" "+getPageForComponent(page, currentField)+" "+currentField);

                if(ref!=null && formsProcessed.get(ref)!=null)
                    continue;

                // work through raw form data parsing and converting into an intermediate Object we use to build components.
                if (page==getPageForComponent(page, currentField)) {

                    if(debugNew)
                        System.err.println("=="+" "+page+" "+getPageForComponent(page, currentField)+" "+currentField);

                    currentField.remove("PageNumber"); //remove now as no longer needed and will confuse AnnotStream parser

                    //ensure not done
                    if(formsProcessed.get(ref)==null){

                        currentField.remove("obj"); //remove now as no longer needed and will confuse AnnotStream parser

                        //stop duplicates
                        formObject = new FormObject();

                        annotDecoder.createAppearanceString(formObject, currentField, currentPdfFile,page);

                        AacroFormDataList.set(fieldNum,null); //flush once used
                        
                        if (formObject!= null){

                            //added by mark to store Parent
                            String parent=(String) currentField.get("Parent");

                            if(parent!=null)
                                formObject.setParent(parent);

                            formObject.setPageNumber(page);
                            formObject.setPDFRef((String)ref);

                            Aforms[i++] = formObject;

                            compData.storeRawData(formObject); //store data so user can access

                            //same as forms
                            //we need to include any kids as well
                            formsProcessed.put(ref, "x");

                            Object children=formObject.getKidData();
                            if(children!=null){
                                Iterator childForms=formObject.getKidData().keySet().iterator();
                                while(childForms.hasNext()){
                                    String child=(String)childForms.next();
                                    formsProcessed.put(child, "x");
                                }
                            }
                        }
                    }
                }
            }

            /**
             * process FORMS
             */

            //<start-os>
            if (hasXFA) {
                fDecoder.resetItems();
                xfaFormList = ((XFAFormStream) fDecoder).createAppearanceString(Fforms, currentPdfFile);

                if(xfaFormList!=null)
                    Fcount=xfaFormList.length;
            }
            //<end-os>

            Map formsCreated=new HashMap();

            if (xfaFormList != null) {//catch for null if creating more than once

                for (int k = 0; k < Fcount; k++) {

                    formObject = xfaFormList[k];
                    if (formObject != null && page==formObject.getPageNumber()){
                        createField(page,formObject, compData.getNextFreeField(),false); //now we turn the data into a Swing component

                        formsCreated.put(formObject.getPDFRef(), "x");

                    }
                }
            }

            /**
             * now form items not referenced in XML
             *
             * on /PDFdata/baseline_screens/customers/write_test16_pdfform.pdf
             * we have 29 forms of which only 16 are referenced in XML (rest are Annots).
             * So I was not getting all values as XML parser just did 16 and ignored rest
             * I have added this bit to handle it. I think it previously worked because other
             * Annot instance would have picked up components not displayed and drawn them so
             * only an issue now merged
             */
            for (int k = 0; k < FfieldCount; k++) {

                formObject = Fforms[k];
                    
                if (formObject != null && formsCreated.get(formObject.getPDFRef())==null && page==formObject.getPageNumber()){
                    //if(formObject.getPDFRef().equals("40 0 R"))
                    //System.out.println("-----------"+formObject.getPDFRef()+" "+formObject.getPageNumber());
                    createField(page,formObject, compData.getNextFreeField(),false); //now we turn the data into a Swing component

                    //if(formObject.getPDFRef().equals("40 0 R"))
                    //System.out.println("X");
                    formsCreated.put(formObject.getPDFRef(), "x");
                }
            }

            //System.err.println("page="+page+" "+compData.getNextFreeField());

            //if(currentField.get("obj").equals("40 0 R"))
              //                     System.exit(1);



            if(debugNew)
                System.err.println("----Annotations----");
            
            /**
             * now ANNOTATIONS
             */
            for (int k = 0; k < AfieldCount; k++) {

                formObject = Aforms[k];

                if (formObject != null && page==formObject.getPageNumber()){
                    createField(page,formObject, compData.getNextFreeField(),false); //now we turn the data into a Swing component

                    if(debugNew)
                        System.err.println("Create Annot "+k+" for page "+page+" ");
                }
            }


            if(debugNew)
                System.err.println("------------------------");
		}
		
		//finish off (some may have been picked up on other pages)
		compData.completeFields(page);

	}

	/**
	 * get properties in form as object with getMethods.
	 * 
	 * This will take either the Name or the PDFref 
	 * 
	 * (ie Box or 12 0 R)
	 * 
	 * This can return an object[] if Box is a radio button with multiple
	 * vales so you need to check instanceof Object[] on data
	 
	 * In the case of a PDF with radio buttons Box (12 0 R), Box (13 0 R), Box (14 0 R)
	 * getFormDataAsObject(Box) would return an Object which is actually Object[3]
	 * getFormDataAsObject(12 0 R) would return an Object which is a single value
	 * 
	 */
	public Object getFormDataAsObject(String objectName) {

		return compData.getRawForm(objectName);
	}

	/**
	 * scan object or parents for page number
	 * @param page
	 * @param currentField
	 * @return
	 */
	private int getPageForComponent(int page, Map currentField) {
		
		int formPage = -1;
		Object rawPageNumber = currentPdfFile.resolveToMapOrString("PageNumber", currentField.get("PageNumber"));
		if (rawPageNumber != null)
			formPage = Integer.parseInt((String) rawPageNumber);

		if(formPage==-1 && currentField.containsKey("Kids")){
			
			Object kidData = currentPdfFile.resolveToMapOrString("Kids",currentField.get("Kids"));
			
			if(kidData instanceof Map){
			
				Map kidMap = (Map)kidData;

				Iterator iter = kidMap.keySet().iterator();
				int val=0,kidPage=-1;
				while(iter.hasNext()){
					String key = (String) iter.next();

					Object data = currentPdfFile.resolveToMapOrString(key,kidMap.get(key));
					if(data instanceof Map){
						Object kidPageNum = currentPdfFile.resolveToMapOrString("PageNumber",((Map)data).get("PageNumber"));
						if (kidPageNum != null)
							kidPage = Integer.parseInt((String) kidPageNum);
					}else {
					}

					if(kidPage==page)
						formPage = kidPage;
					val++;
				}
			}
		}
		return formPage;
	}

	/**
	 * display widgets onscreen for range (inclusive)
	 */
	public void displayComponentsOnscreen(int startPage, int endPage) {

		if (showMethods)
			System.out.println(this + " displayComponentsOnscreen " + startPage + ' ' + endPage);

		//make sure this page is inclusive in loop
		endPage++;

		compData.displayComponents(startPage, endPage);

	}

	/**
	 * create a widget to handle FDF button
	 */
	private void createField(int pageNumber,final FormObject formObject, int formNum, boolean isChildObject) {

        //needed for buttons and radio buttons
		if(!isChildObject)
			compData.resetButtonGroup();
			
		if (showMethods)
			System.out.println("createField " + formNum+" "+isChildObject);// + ' ' + formObject);

		Integer widgetType=FormFactory.UNKNOWN; //no value set

		Object retComponent = null;

		//define which type of component will be created
		boolean button = false, text = false, choice = false, signature = false;

		int typeFlag = formObject.getType();
		if (typeFlag != -1) {
			button = typeFlag == FormObject.FORMBUTTON;
			text = typeFlag == FormObject.FORMTEXT;
			choice = typeFlag == FormObject.FORMCHOICE;
			signature = typeFlag == FormObject.FORMSIG;
		}

		//if sig object set global sig object so we can access later
		if(signature){
			if(sigObject==null) //ensure initialised
				sigObject=new HashSet();

			sigObject.add(formObject);
		}

		//flags used to alter interactivity of all fields
		boolean readOnly = false, required = false, noexport = false;

		boolean[] flags = formObject.getFieldFlags();
		if (flags != null) {
			readOnly = flags[FormObject.READONLY];
			required = flags[FormObject.REQUIRED];
			noexport = flags[FormObject.NOEXPORT];

			/*
                 boolean comb=flags[FormObject.COMB];
                 boolean comminOnSelChange=flags[FormObject.COMMITONSELCHANGE];
                 boolean donotScrole=flags[FormObject.DONOTSCROLL];
                 boolean doNotSpellCheck=flags[FormObject.DONOTSPELLCHECK];
                 boolean fileSelect=flags[FormObject.FILESELECT];
                 boolean isCombo=flags[FormObject.COMBO];
                 boolean isEditable=flags[FormObject.EDIT];
                boolean isMultiline=flags[FormObject.MULTILINE];
                boolean isPushButton=flags[FormObject.PUSHBUTTON];
                boolean isRadio=flags[FormObject.RADIO];
                boolean hasNoToggleToOff=flags[FormObject.NOTOGGLETOOFF];
                boolean hasPassword=flags[FormObject.PASSWORD];
                boolean multiSelect=flags[FormObject.MULTISELECT];
                boolean radioinUnison=flags[FormObject.RADIOINUNISON];
                boolean richtext=flags[FormObject.RICHTEXT];
                boolean sort=flags[FormObject.SORT];
			 */
		}

		if (debug) {
			if (flags != null) {
				System.out.println("FLAGS - pushbutton=" + flags[16] + " radio=" + flags[15] + " notoggletooff=" +
						flags[14] + "\n multiline=" + flags[12] + " password=" + flags[13] +
						"\n combo=" + flags[17] + " editable=" + flags[18] + " readOnly=" + readOnly +
						"\n BUTTON=" + button + " TEXT=" + text + " CHOICE=" + choice + " SIGNATURE=" + signature +
						"\n characteristic=" + ConvertToString.convertArrayToString(formObject.getCharacteristics()));
			} else {
				System.out.println("FLAGS - all false");
			}
		}

		if (debug && flags != null && (required || flags[19] || noexport || flags[20] || flags[21] || flags[23] || flags[25] || flags[25]))
			System.out.println("renderer UNTESTED FLAGS - readOnly=" + readOnly + " required=" + required + " sort=" + flags[19] + " noexport=" + noexport +
					" fileSelect=" + flags[20] + " multiSelect=" + flags[21] + " donotScrole=" + flags[23] + " radioinUnison=" + flags[25] +
					" richtext=" + flags[25]);

		/** setup field */
		if (button) {//----------------------------------- BUTTON  ----------------------------------------
			if(identifyType)
				System.out.println("button");
			//flags used for button types
			boolean isPushButton = false, isRadio = false, hasNoToggleToOff = false, radioinUnison = false;
			if (flags != null) {
				isPushButton = flags[FormObject.PUSHBUTTON];
				isRadio = flags[FormObject.RADIO];
				hasNoToggleToOff = flags[FormObject.NOTOGGLETOOFF];
				radioinUnison = flags[FormObject.RADIOINUNISON];
			}

			if (isPushButton) {

				widgetType=FormFactory.PUSHBUTTON;
				retComponent = formFactory.pushBut(formObject);

			}else{ //radio and checkbox

				if (isRadio)
					widgetType=FormFactory.RADIOBUTTON;
				else
					widgetType=FormFactory.CHECKBOXBUTTON;


                /**
                 * handle standard forms link on buttons
                 */
                if (formObject.getKidData() != null) {
					Map kidData = formObject.getKidData();
                    Iterator iter = kidData.keySet().iterator();

                    //System.out.println("Kids>>>>>>"+formNum);

                    while (iter.hasNext()){ //iterate through all parts

                        //ensure references setup correctly so recursive name scan works
                        String key= (String) iter.next();

                        FormObject childObj=(FormObject) kidData.get(key);
                        childObj.setParent(formObject.getParentRef());
                        childObj.setPDFRef(key);


                        //System.out.println(childObj.getPageNumber());
                        if(pageNumber==childObj.getPageNumber()){
                            createField(pageNumber,childObj, ++formNum,true);

                            //save as no longer done in main routine
                            compData.storeRawData(childObj); //store data so user can access
                        }
                    }

                    //System.out.println("<<<<<<<<"+formNum);
                }

                /**
                 * flag up Annots where link is set in AcroData and set link
                 * to be picked up later
                 */
                String ref=formObject.getPDFRef();
                if(ref!=null){
                    String parent=(String)formKids.get(formObject.getPDFRef());

                    if(parent!=null)
                    formObject.setAnnotParent(parent);
                }

                if (isRadio)
					retComponent = formFactory.radioBut(formObject);
				else
					retComponent = formFactory.checkBoxBut(formObject);

            }

		} else
			if (text) { //-----------------------------------------------  TEXT --------------------------------------
				if(identifyType)
					System.out.println("text");
				//flags used for text types
				boolean isMultiline = false, hasPassword = false, doNotScroll = false, richtext = false, fileSelect = false, doNotSpellCheck = false;
				if (flags != null) {
					isMultiline = flags[FormObject.MULTILINE];
					hasPassword = flags[FormObject.PASSWORD];
					doNotScroll = flags[FormObject.DONOTSCROLL];
					richtext = flags[FormObject.RICHTEXT];
					fileSelect = flags[FormObject.FILESELECT];
					doNotSpellCheck = flags[FormObject.DONOTSPELLCHECK];
				}

				if (isMultiline) {

					if (hasPassword) {

						widgetType=FormFactory.MULTILINEPASSWORD;
						retComponent = formFactory.multiLinePassword(formObject);

					} else {

						widgetType=FormFactory.MULTILINETEXT;
						retComponent = formFactory.multiLineText(formObject);

					}
				} else {//singleLine

					if (hasPassword) {

						widgetType=FormFactory.SINGLELINEPASSWORD;
						retComponent = formFactory.singleLinePassword(formObject);

					} else {

						widgetType=FormFactory.SINGLELINETEXT;
						retComponent = formFactory.singleLineText(formObject);


                    }
				}
			}else if (choice) {//----------------------------------------- CHOICE ----------------------------------------------
				if(identifyType)
					System.out.println("choice");
				//flags used for choice types
				boolean isCombo = false, multiSelect = false, sort = false, isEditable = false, doNotSpellCheck = false, comminOnSelChange = false;
				if (flags != null) {
					isCombo = flags[FormObject.COMBO];
					multiSelect = flags[FormObject.MULTISELECT];
					sort = flags[FormObject.SORT];
					isEditable = flags[FormObject.EDIT];
					doNotSpellCheck = flags[FormObject.DONOTSPELLCHECK];
					comminOnSelChange = flags[FormObject.COMMITONSELCHANGE];
				}

				if (isCombo) {// || (type==XFAFORM && ((XFAFormObject)formObject).choiceShown!=XFAFormObject.CHOICE_ALWAYS)){

					widgetType=FormFactory.COMBOBOX;
					retComponent = formFactory.comboBox(formObject);

				} else {//it is a list

					widgetType=FormFactory.LIST;
					retComponent = formFactory.listField(formObject);
				}
			} else if (signature) {
				if(identifyType)
					System.out.println("signature");

				widgetType=FormFactory.SIGNATURE;
				retComponent = formFactory.signature(formObject);

			} else{//assume annotation if (formType == ANNOTATION) {
				if(identifyType)
					System.out.println("annotation");

				widgetType=FormFactory.ANNOTATION;
				retComponent = formFactory.annotationButton(formObject);

//            } else {
//				if(identifyType)
//					System.out.println("else @@@@@@");
//
//				if (debug) {
//					if (flags != null) {
//						System.out.println("UNIMPLEMENTED field=FLAGS - pushbutton=" + flags[16] + " radio=" + flags[15] +
//								" multiline=" + flags[12] + " password=" + flags[13] + " combo=" + flags[17] +
//								" BUTTON=" + button + " TEXT=" + text + " CHOICE=" + choice);
//					} else
//						System.out.println("UNIMPLEMENTED field=BUTTON=" + button + " TEXT=" + text + " CHOICE=" + choice + " FLAGS=all false");
//				}
				}

        //set Component specific values such as Tooltip and mouse listener
		compData.completeField(formObject, formNum, isChildObject, widgetType, retComponent);

    }


	//<start-os>
	/**
	 * give user callback facilty to handle problems reported by Javascript validation
	 */
	public void reportError(int code, Object[] args) {

		boolean errorReported=false;

		if(userExpressionEngine!=null)
			errorReported=userExpressionEngine.reportError(code, args);

		//report error in Swing or appropriate library
		if(!errorReported)
			compData.reportError(code,args);
		
	}
	//<end-os>


	//pass in Javascript object
	public void setJavaScriptObject(Javascript javascript, Object userExpressionEngine) {

		this.javascript=javascript;

		if(compData!=null)
		compData.setJavascript(javascript);

		//<start-os>
		this.userExpressionEngine= (ExpressionEngine) userExpressionEngine;
		//<end-os>
	}
       
	/**
	 * return the component associated with this objectName (returns null if no match). Names are case-sensitive.
	 * Please also see method getComponentNameList(int pageNumber),
	 * if objectName is null then all components will be returned
	 */
	public Object[] getComponentsByName(String objectName) {

		/**make sure all forms decoded*/
		for (int p = 1; p < this.pageCount + 1; p++) //add init method and move scaling/rotation to it
            createDisplayComponentsForPage(p);

        if (showMethods)
			System.out.println("getComponentNameList " + objectName);

		return compData.getComponentsByName(objectName);

	}

	/**
	 * return a List containing the names of  forms on a specific page which has been decoded.
	 * <p/>
	 * <p/>
	 * USE of this method is NOT recommended. Use getNamesForAllFields() in PdfDecoder
	 *
	 * @throws PdfException An exception is thrown if page not yet decoded
	 */
	public List getComponentNameList() throws PdfException {

		if (showMethods)
			System.out.println("getComponentNameList");

		if (AfieldCount==0 && FfieldCount==0)// || compData.trackPagesRendered == null)
			return null;

		/**make sure all forms decoded*/
		for (int p = 1; p < this.pageCount + 1; p++)
            createDisplayComponentsForPage(p);

        return getComponentNameList(-1);

	}

	/**
	 * return a List containing the names of  forms on a specific page which has been decoded.
	 *
	 * @throws PdfException An exception is thrown if page not yet decoded
	 */
	public List getComponentNameList(int pageNumber) throws PdfException {

		if (showMethods)
			System.out.println("getComponentNameList " + pageNumber);

		if(FfieldCount==0 && AfieldCount==0)
			return null;
		else
			return compData.getComponentNameList(pageNumber);
		
		
	}

	/**
	 * setup object which creates all GUI objects
	 */
	public void setFormFactory(FormFactory newFormFactory) {
		//if (showMethods)
//			System.out.println(this+" setFormFactory " + newFormFactory);

		formFactory = newFormFactory;

            formsActionHandler.setActionFactory(formFactory.getActionFactory());

            /**
             * allow user to create custom structure to hold data
             */
            compData=formFactory.getCustomCompData();

        //pass in Javascript
		compData.setJavascript(javascript);
		
		//formFactory.setDataObjects(compData);

    }


	/**
	 * does nothing or FORM except set type, resets annots and last values
	 */
	public void openFile(int pageCount) {

		if (showMethods)
			System.out.println("openFile " + pageCount);

		this.pageCount = pageCount;

        //flush data
		compData.reset();

		compData.flushFormData();

	}

	/**return form data in a Map*/
	public Map getSignatureObject(String ref) {

		Map certObj=this.currentPdfFile.readObject(new PdfObject(ref), ref,false,null);

		Object sigRef=certObj.get("V");

		if(sigRef == null)
			return null;

		Map fields=new HashMap();
		fields.put("Name", "x");
		fields.put("Reason", "x");
		fields.put("Location", "x");
		fields.put("M", "x");
		fields.put("Cert", "x");

		//allow for ref or direct
		if(sigRef instanceof String){
			certObj=currentPdfFile.readObject(new PdfObject((String)sigRef), (String)sigRef,false,fields);
		}else
			certObj=(Map)sigRef;

		//make string into strings
		Iterator strings=fields.keySet().iterator();
		while(strings.hasNext()){
			Object fieldName=strings.next();
			byte[] value=(byte[]) certObj.get(fieldName);
			if(value!=null && !fieldName.equals("Cert"))
				certObj.put(fieldName,currentPdfFile.getTextString(value));

		}

		return certObj;
	}

	/**
	 * get GUIData object with all widgets
	 */
	public GUIData getCompData() {
		return compData;
	}

	/**return Signature as iterator with one or more objects or null*/
	public Iterator getSignatureObjects() {
		if(sigObject==null)
			return null;
		else
			return sigObject.iterator();
	}

	public ActionHandler getActionHandler() {
		return formsActionHandler;
	}

    public FormFactory getFormFactory() {
        return formFactory;
    }
    
    public Map getRawFormData(){
        return compData.getRawFormData();
    }

    public void setIgnoreForms(boolean ignoreForms) {
        this.ignoreForms=ignoreForms;
    }

    public boolean ignoreForms() {
        return ignoreForms;
    }
}
