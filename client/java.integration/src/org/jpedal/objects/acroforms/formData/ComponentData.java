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
* ComponentData.java
* ---------------
*/
package org.jpedal.objects.acroforms.formData;


import java.awt.Component;
import java.util.HashMap;
import java.util.Map;
import java.util.StringTokenizer;

import javax.swing.JScrollPane;

import org.jpedal.objects.Javascript;
import org.jpedal.objects.PdfPageData;
import org.jpedal.objects.acroforms.creation.FormFactory;


/**holds all data not specific to Swing/SWT/ULC*/
public class ComponentData {

	/**
	 * holds forms data array of formObjects data from PDF as Map for fast lookup
	 **/
	protected Map rawFormData=new HashMap(),convertFormIDtoRef=new HashMap(), nameToRef=new HashMap(),duplicateNames=new HashMap();

	protected int insetW;

	protected int insetH;

	protected PdfPageData pageData;

	protected Javascript javascript;

	/**
	 * local copy needed in rendering
	 */
	protected int pageHeight, indent;

	protected int[] cropOtherY;
	/**
	 * track page scaling
	 */
	protected float displayScaling, scaling = 0;
	protected int rotation;

	/**
	 * used to only redraw as needed
	 */
	protected float lastScaling = -1, oldRotation = 0, oldIndent = 0;

	/**
	 * used for page tracking
	 */
	protected int startPage, endPage, currentPage;

	/**
	 * the last name added to the nameToCompIndex map
	 */
	protected String lastNameAdded = "";

	/** stores map of names to indexs of components in allfields*/
	protected Map duplicates = new HashMap();

	protected Map lastValidValue=new HashMap();
	protected Map lastUnformattedValue=new HashMap();

	/**
	 * map that stores the field number for fields that have parts
	 * that need to be displayed on a different page
	 */
	protected Map additionFieldsMap = new HashMap();

	/**
	 * stores the name and component index in allFields array
	 */
	protected Map nameToCompIndex;

	protected Map typeValues;

	/**
	 * next free slot
	 */
	protected int nextFreeField = 0;



	/**
	 * holds the location and size for each field,
	 * <br>
	 * [][0] = x1;
	 * [][1] = y1;
	 * [][2] = x2;
	 * [][3] = y2;
	 */
	protected float[][] boundingBoxs;

	protected int[] fontSize;

	/**
	 * used to draw pages offset if not in SINGLE_PAGE mode
	 */
	protected int[] xReached, yReached;

	/**
	 * table to store if page components already built
	 */
	protected int[] trackPagesRendered;

	/**
	 * flag to show if component has been displayed for the first time
	 */
	protected boolean[] firstTimeDisplayed;

	/**
	 * array to store fontsizes as the field is set up for use on rendering
	 */
	protected int[] fontSizes;

	/**
	 * the reset to value of the indexed field
	 */
	protected String[] defaultValues;

	/**
	 * array to hold page for each component so we can scan quickly on page change
	 */
	public int[] pageMap;
    private int formCount;

    /**last value set by user in GUI or null if none*/
	public Object getLastValidValue(String fieldName) {
		return lastValidValue.get(fieldName);
	}

	/**last value set by user in GUI or null if none*/
	public Object getLastUnformattedValue(String fieldName) {
		return lastUnformattedValue.get(fieldName);
	}

	public void reset() {

		lastValidValue.clear();
		lastUnformattedValue.clear();

    }

    /**
     * returns the Type of pdf form, of the named field
     */
    public Integer getTypeValueByName(String fieldName) {

        Object key=typeValues.get(fieldName);
        if(key==null)
            return  FormFactory.UNKNOWN;
        else
            return (Integer) typeValues.get(fieldName);
    }

	/**
	 * return next ID for this page and also set pointer
	 * @param page
	 * @return
	 */
	private int setStartForPage(int page) {

		//flag start
        trackPagesRendered[page] = nextFreeField;

		return nextFreeField;
	}

	/**
	 * get next free field slot
	 * @return
	 */
	public int getNextFreeField() {
		return nextFreeField;
	}

    /**
	 * max number of form slots
	 */
	public int getMaxFieldSize() {
		return formCount;
	}

    /**
     * return start component ID or -1 if not set or -999 if trackPagesRendered not initialised
     * @param page
     * @return
     */
	public int getStartComponentCountForPage(int page) {
		if(trackPagesRendered==null)
			return -999;
		else if(trackPagesRendered.length>page)
			return trackPagesRendered[page];
		else
			return -1;
	}

	/**
	 * setup values needed for drawing page
	 * @param pageData
	 * @param page
	 */
	public void initParametersForPage(PdfPageData pageData, int page) {

		//ensure setup
		if(cropOtherY==null || cropOtherY.length<=page)
			this.resetComponents(0, page+1, false);
		
		int mediaHeight = pageData.getMediaBoxHeight(page);
		int cropTop = (pageData.getCropBoxHeight(page) + pageData.getCropBoxY(page));

		//take into account crop		
		if (mediaHeight != cropTop)
			cropOtherY[page] = (mediaHeight - cropTop);
		else
			cropOtherY[page] = 0;
		
		this.pageHeight = mediaHeight;
		this.currentPage = page; //track page displayed

		//set for page
		setStartForPage(page);

	}

	/**
	 * used to flush/resize data structures on new document/page
	 * @param formCount
	 * @param pageCount
	 * @param keepValues
	 */
	public void resetComponents(int formCount,int pageCount,boolean keepValues) {

		nextFreeField = 0;
        this.formCount=formCount;

        additionFieldsMap.clear();

		if(!keepValues){

			nameToCompIndex = new HashMap(formCount + 1);
			typeValues = new HashMap(formCount+1);

			pageMap = new int[formCount + 1];
			fontSize = new int[formCount + 1];

			//start up boundingBoxs
			boundingBoxs = new float[formCount + 1][4];

			fontSizes = new int[formCount + 1];

			defaultValues = new String[formCount + 1];

			firstTimeDisplayed = new boolean[formCount + 1];

			//flag all fields as unread
			trackPagesRendered = new int[pageCount + 1];
			for (int i = 0; i < pageCount + 1; i++)
				trackPagesRendered[i] = -1;
			
			//reset offsets
			cropOtherY=new int[pageCount+1];
			
		}else if(pageMap!=null){

			boolean[] tmpFirstTimeDisplayed = firstTimeDisplayed;
			int[] tmpMap = pageMap;
			int[] tmpSize = fontSize;
			String[] tmpValues = defaultValues;
			float[][] tmpBoxs = boundingBoxs;
			int[] tmpSizes = fontSizes;

			firstTimeDisplayed = new boolean[formCount + 1];
			// allFields = new Component[formCount + 1];
			pageMap = new int[formCount + 1];
			fontSize = new int[formCount + 1];
			defaultValues = new String[formCount + 1];

			//start up boundingBoxs
			boundingBoxs = new float[formCount + 1][4];

			fontSizes = new int[formCount + 1];

			int origSize=tmpMap.length;

			//populate
			for (int i = 0; i < formCount+1; i++) {

				if(i==origSize)
					break;
				//if (tmpFields[i] == null)
				//     break;

				firstTimeDisplayed[i] = tmpFirstTimeDisplayed[i];
				pageMap[i] = tmpMap[i];

				fontSize[i] = tmpSize[i];
				defaultValues[i] = tmpValues[i];


				System.arraycopy(tmpBoxs[i], 0, boundingBoxs[i], 0, 4);

				fontSizes[i] = tmpSizes[i];

				nextFreeField++;
			}
		}
	}

		/**
		 * pass in current values used for all components
		 * @param scaling
		 * @param rotation
		 */
		public void setPageValues(float scaling, int rotation) {

			this.scaling=scaling;
			this.rotation=rotation;
			this.displayScaling=scaling;

		}

		/**
		 * used to pass in offsets and PdfPageData object so we can access in rendering
		 * @param pageData
		 * @param insetW
		 * @param insetH
		 */
		public void setPageData(PdfPageData pageData, int insetW, int insetH) {

			//track inset on page
			this.insetW = insetW;
			this.insetH = insetH;

			this.pageData = pageData;

		}

		/**
		 * returns the default values for all the forms in this document
		 */
		public String[] getDefaultValues() {
			return defaultValues;
		}

		/**
		 * offsets for forms in multi-page mode
		 */
		public void setPageDisplacements(int[] xReached, int[] yReached) {

			this.xReached = xReached;
			this.yReached = yReached;

		}

		/**
		 * provide access to Javascript object
		 * @param javascript
		 */
		public void setJavascript(Javascript javascript) {
			this.javascript=javascript;

		}

		public void resetDuplicates() {
			duplicates.clear();

		}
		
		/**
		 * store form data and allow lookup by PDF ref or name 
		 * (name may not be unique)
		 * @param formObject
		 */
		public void storeRawData(FormObject formObject) {
			
			String fieldName=formObject.getFieldName();
			String ref=formObject.getPDFRef();
			nameToRef.put(fieldName,ref);
			rawFormData.put(ref,formObject);
			
			/**
			 * track duplicates
			 */
			String duplicate=(String) duplicateNames.get(fieldName);
			if(duplicate==null){ //first case
				duplicateNames.put(fieldName,ref);
			}else{ //is a duplicate
				duplicate=duplicate+","+ref; // comma separated list
				duplicateNames.put(fieldName,duplicate);
			}
			
		}
		
		public void flushFormData() {
			
			nameToRef.clear();
			rawFormData.clear();
			duplicateNames.clear();
            convertFormIDtoRef.clear();
        }

        /**
         * convert ID used for GUI components to PDF ref for underlying object used
         * so we can access form object knowing ID of component
         * @param objectID
         * @return
         */
        public String convertIDtoRef(int objectID){
            return (String)convertFormIDtoRef.get(new Integer(objectID));
        }


        /**
		 * return 1 or more values
		 * @param objectName
		 * @return
		 */
		public Object getRawForm(String objectName) {
			
			//if name see if duplicates
			String matches=(String) duplicateNames.get(objectName);
			
			if(matches==null || (matches.indexOf(",")==-1 && matches.indexOf(" R")!=-1 )){//single form
			
				//convert to PDFRef if name first
				String possRef=(String) nameToRef.get(objectName);
				if(possRef!=null)
					objectName=possRef;
			
				return rawFormData.get(objectName);
			}else{//duplicates
				
				StringTokenizer comps=new StringTokenizer(matches,",");
				int count=comps.countTokens();
				Object[] values=new Object[count];
				
				for(int ii=0;ii<count;ii++){
					String next=comps.nextToken();
					
					if(next!=null)
					values[ii]=rawFormData.get(next);
				}
				return values;
			}
		}


	}
