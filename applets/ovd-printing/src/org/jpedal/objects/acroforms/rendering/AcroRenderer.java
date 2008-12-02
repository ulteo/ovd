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
* AcroRenderer.java
* ---------------
*/
package org.jpedal.objects.acroforms.rendering;

import java.util.Iterator;
import java.util.List;
import java.util.Map;

import org.jpedal.PdfDecoder;
import org.jpedal.exception.PdfException;
import org.jpedal.io.PdfObjectReader;
import org.jpedal.objects.PdfPageData;
import org.jpedal.objects.Javascript;

import org.jpedal.objects.acroforms.actions.ActionHandler;
import org.jpedal.objects.acroforms.creation.FormFactory;
import org.jpedal.objects.acroforms.formData.GUIData;
import org.jpedal.objects.acroforms.formData.SwingData;

/**
 * sets up the display and called to draw objects as needed
 */
public interface AcroRenderer {

    /**
     * reset Handler
     */
    public void resetHandler(Object userActionHandler,PdfDecoder decode_pdf, int type);

    /**
     * called before decode page by clearscreen to remove components  -user should not call
     */
    public void removeDisplayComponentsFromScreen();

    /** called when new file opened - different purpose for Annots and form
     */
    public void openFile(int pageCount);

    /**
     * called before decode page by clearscreen to remove components  -user should not call
     */
    public void resetFormData(Object obj, int insetW, int insetH, PdfPageData pageData, PdfObjectReader curentPdfFile, Map formKids);

    public void resetAnnotData(Object obj, int insetW, int insetH, PdfPageData pageData, PdfObjectReader curentPdfFile, Map formKids);

    /**
     * create display - called inside PDF decoder once page decoded -user should not call
     */
    public void createDisplayComponentsForPage(int page);

    /**
     * return the component associated with this objectName (returns null if no match). Names are case-sensitive.
     * Please also see method getComponentNameList(int pageNumber)
     */
    public Object[] getComponentsByName(String objectName);

    /**
     * return a List containing the names of  forms on a specific page which has been decoded.
     *
     * @throws PdfException An exception is thrown if page not yet decoded
     */
    public List getComponentNameList(int pageNumber) throws PdfException;

    /**
     * return a List containing the names of  forms on a specific page which has been decoded.
     *
     * @throws PdfException An exception is thrown if page not yet decoded
     */
    public List getComponentNameList() throws PdfException;

    /**
     * setup object which creates all GUI objects
     */
    public void setFormFactory(FormFactory newFormFactory);

    /**
     * used to draw forms from multiple pages
     */
    public void displayComponentsOnscreen(int startPage, int endPage);

	/**
	 * removes the forms on the specified pages from the panel
	 */
    //public void removePageRangeFromDisplay(int i, int j, PdfPanel decoder);

    //Javascript getJavaScriptObject();

    void setJavaScriptObject(Javascript javascript, Object userExpressionEngine);

    //<start-os>
    void reportError(int s, Object[] s1);
    //<end-os>

    /**allow user to access Signature objects - null if none*/
    public Iterator getSignatureObjects();

	public Map getSignatureObject(String ref);
    
	public GUIData getCompData();

	public Object getFormDataAsObject(String formName);

	public ActionHandler getActionHandler();

    FormFactory getFormFactory();

	public Map getRawFormData();

    public boolean ignoreForms();

    public void setIgnoreForms(boolean flag);
}
