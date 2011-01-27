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
* GUIData.java
* ---------------
*/
package org.jpedal.objects.acroforms.formData;

import java.util.List;
import java.util.Map;

import org.jpedal.objects.Javascript;
import org.jpedal.objects.PdfPageData;


/**
 * Abstraction so forms can be rendered in ULC
 * - see SwingData for full details of usage
 */
public interface GUIData {

	void resetDuplicates();

	void removeAllComponentsFromScreen();

	void setPageData(PdfPageData pageData, int insetW, int insetH);

	void completeField(FormObject formObject, int formNum,
			boolean isChildObject, Integer widgetType, Object retComponent);

	void completeFields(int page);

	void displayComponents(int startPage, int endPage);

	int getNextFreeField();

	void reportError(int code, Object[] args);

	void reset();

	List getComponentNameList(int pageNumber);

	Object[] getComponentsByName(String objectName);

	int getStartComponentCountForPage(int page);

	void initParametersForPage(PdfPageData pageData, int page);

	void resetButtonGroup();

	void resetComponents(int formCount, int pageCount, boolean b);

	void setJavascript(Javascript javascript);

	void setValue(String ref, Object result, boolean b, boolean c, boolean reset);

	Object getLastValidValue(String ref);

	Object getLastUnformattedValue(String fieldName);

	String[] getDefaultValues();

	Object getValue(Object fieldName);

	Object getWidget(Object ref);

	void loseFocus();

	void renderFormsOntoG2(Object g2, int page, float scaling, int i, int accDisplay);

	void resetScaledLocation(float scaling, int displayRotation, int indent);

	void setRootDisplayComponent(Object pdfDecoder);

	void setPageValues(float scaling, int rotation);


    void setPageDisplacements(int[] reached, int[] reached2);

	void execute(Map code);

    Integer getTypeValueByName(String name);

	void storeRawData(FormObject formObject);

	void flushFormData();

	Object getRawForm(String objectName);


	Map getRawFormData();

    int getMaxFieldSize();

    void setOffset(int offset);
}
