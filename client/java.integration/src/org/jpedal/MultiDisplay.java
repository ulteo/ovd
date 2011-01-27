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
* MultiDisplay.java
* ---------------
*/
package org.jpedal;

import org.jpedal.objects.raw.PdfDictionary;
import org.jpedal.objects.raw.PdfObject;
import org.jpedal.objects.raw.PdfPageObject;
import org.jpedal.render.DynamicVectorRenderer;
import org.jpedal.io.ObjectStore;

import javax.swing.border.Border;
import javax.swing.*;
import java.awt.*;
import java.awt.geom.AffineTransform;
import java.util.Map;
import java.util.Iterator;
import java.util.HashMap;


/**
 * provides several pages at once in the display
 */
public final class MultiDisplay extends SingleDisplay implements Display {

	//used to redraw multiple pages
	private Thread worker = null;

	private Object customSwingHandle=null;
	private int pageUsedForTransform;

	public MultiDisplay(int pageNumber,int pageCount,DynamicVectorRenderer currentDisplay,int displayView, Object gui) {

		super(pageNumber,pageCount,currentDisplay);

		this.customSwingHandle=gui;

		this.displayView=displayView;

		/**cache current page*/
		if(currentDisplay!=null)
			currentPageViews.put(new Integer(pageNumber),currentDisplay);
	}

	/**used to decode multiple pages on views*/
	public final void decodeOtherPages(int pageNumber, int pageCount) {

		if(debugLayout)
			System.out.println("start decodeOtherPages");

		this.pageNumber=pageNumber;

		if(!isInitialised)
			return;

		//getDisplayedRectangle();

		// make sure forms created and decode other pages
		setPageOffsets(pageCount,this.pageNumber);

		calcDisplayedRange();

		// restart if not running - uses pages to control loop so I hope will
		// pick up change
		if ((worker == null) || (!running)) {

			running = true;

			//ensure thumbnails not being created
			while (thumbnailsRunning) {
				// System.out.println("Waiting to die");
				try {
					Thread.sleep(100);
				} catch (InterruptedException e) {
					// should never be called
					e.printStackTrace();
				}
			}

			worker = new Thread() {
				public void run(){

					try {

						if(debugLayout)
							System.out.println("START=========================Started decoding pages "
									+ startViewPage + ' ' + endViewPage);

						decodeOtherPages();

						if(debugLayout)
							System.out.println("END=========================Pages done");

						running = false;
					} catch (Exception e) {
						running = false;
					}catch(Error err){
						running=false;

					}
				}
			};

			worker.start();
		}
	}

	/**
	 * scans all pages in visible range and decodes
	 */
	private void decodeOtherPages() {

		if(debugLayout)
			System.out.println("decodeOtherPages called");

		isGeneratingOtherPages=true;

		int page = startViewPage, originalStart = startViewPage, originalEnd = endViewPage+1;

		resetPageCaches(startViewPage, endViewPage+1);

		if(debugLayout){
			System.out.println("decoding ------START " + originalStart + " END="+ originalEnd+" isGeneratingOtherPages="+isGeneratingOtherPages);
			System.out.println(startViewPage + " "+ endViewPage);
		}
		while (isGeneratingOtherPages) {

			// detect if restarted
			if ((originalStart != startViewPage)&& (originalEnd != endViewPage)) {

				page = startViewPage;
				originalEnd = endViewPage+1;

				// can be zero in facing mode
				if (page == 0)
					page++;
				originalStart = page;

				resetPageCaches(originalStart, originalEnd);

			}

			// exit if finished
			if (page == originalEnd)
				break;


			if (currentPageViews.get(new Integer(page)) == null) {

				if(debugLayout)
					System.out.println("Decode page "+page+ ' ' +originalStart+ ' ' +originalEnd);

				/** get pdf object id for page to decode */
				String currentPageOffset = (String) pdf.pagesReferences.get(new Integer(page));

				if(debugLayout)
					System.out.println("Decoding page " + page+" currentPageOffset="+currentPageOffset);
				/**
				 * decode the file if not already decoded, there is a valid
				 * object id and it is unencrypted
				 */
				if (currentPageOffset != null) {

					PdfObject pdfObject=new PdfPageObject(currentPageOffset);
					pdf.readObjectForPage(pdfObject, currentPageOffset, page,true);
					PdfObject Resources=pdfObject.getDictionary(PdfDictionary.Resources);

					/**
					 * decode page if needed
					 */
					Integer key=new Integer(page);
					Object currentView = currentPageViews.get(key);

					if (currentView == null  && isGeneratingOtherPages) {

						if(debugLayout)
							System.out.println("recreate page");

						DynamicVectorRenderer currentDisplay=null;

						/**
						 * load if cached
						 */
						currentDisplay = ObjectStore.getCachedPage(key);

						if(currentDisplay!=null){
							currentDisplay.setObjectStoreRef(pdf.objectStoreRef);
							currentPageViews.put(new Integer(page),currentDisplay);
						}else{
							currentDisplay = new DynamicVectorRenderer(page,pdf.objectStoreRef, false);

							if(isGeneratingOtherPages)
								getPageView(Resources, pdfObject, currentDisplay, page);

							currentPageViews.put(new Integer(page),currentDisplay);

							/**
							 * save to disk
							 */
							ObjectStore.cachePage(key, currentDisplay);
						}
						/**
						 *  add this page as thumbnail - we don't need to decode twice
						 */
						if(thumbnails!=null && thumbnails.isShownOnscreen()){
							thumbnails.addDisplayedPageAsThumbnail(page,currentDisplay);
						}
					}

					//screenNeedsRedrawing = true;
					Runnable getTextFieldText = new Runnable() {
						public void run() {
							pdf.repaint();
						}
					};
					SwingUtilities.invokeLater(getTextFieldText);

				}

			}
			page++;
		}

		if(debugLayout)
			System.out.println("decodeOtherPageinins------ENDED");


		//update thumbnail display
		Runnable getTextFieldText = new Runnable() {
			public void run() {
				if (thumbnails!=null && thumbnails.isShownOnscreen())
					thumbnails.generateOtherVisibleThumbnails(pdf.pageNumber);

			}
		};
		SwingUtilities.invokeLater(getTextFieldText);

	}

	public final void initRenderer(Rectangle[] areas, Graphics2D g2,Border myBorder,int indent){

		//if(debugLayout)
		//    System.out.println("initRenderer");

		this.myBorder=myBorder;
		this.rawAf=g2.getTransform();
		this.rawClip=g2.getClip();

		//no highlights
//		this.areas=areas;
		this.areas=null;

		this.g2=g2;

		this.indent=indent;

		pagesDrawn.clear();


		/**
		 * work out any displacement and add to crx,cry
		 */
		//if((isInitialised)){

		setPageOffsets(pageCount,pageNumber);

		/**get pages currently visible on this repaint*/
		calcDisplayedRange();

		//if(displayView==CONTINUOUS)
		//g2.translate(xReached[pageNumber],yReached[pageNumber]);

		//}

		setPageSize(this.pageUsedForTransform, scaling);

	}

	/**
	 * copy pages between WeakMap and vice-versa
	 */
	private void resetPageCaches(int startPage, int endPage) {

		// copy any pages in existence into hashMap so not garbage collected
		//synchronized (cachedPageViews) {
		Iterator keys = this.cachedPageViews.keySet().iterator();
		while (keys.hasNext()) {
			Integer currentKey = (Integer) keys.next();
			int keyValue = currentKey.intValue();
			if ((keyValue >= startPage) && (keyValue <= endPage)) {
				Object obj = cachedPageViews.get(currentKey);
				if (obj != null)
					this.currentPageViews.put(currentKey, obj);
			}
		}
		//}

		// move any pages not visible into cache
		{
			//synchronized (currentPageViews) {
			keys = this.currentPageViews.keySet().iterator();

			Map keysToTrash = new HashMap();

			while (keys.hasNext()) {
				Integer currentKey = (Integer) keys.next();
				int keyValue = currentKey.intValue();
				if ((keyValue < startPage) || (keyValue > endPage)) {
					Object obj = currentPageViews.get(currentKey);
					if (obj != null)
						this.cachedPageViews.put(currentKey, obj);

					// track so we can delete outside loop
					keysToTrash.put(currentKey, "x");

				}
			}

			// now remove
			keys = keysToTrash.keySet().iterator();

			while (keys.hasNext())
				currentPageViews.remove(keys.next());

			//}
		}

		//System.out.println("cache contains " + currentPageViews.keySet());
	}


	/**
	 * workout which pages are displayed
	 */
	private synchronized void calcDisplayedRange() {

		if(debugLayout)
			System.out.println("calcDisplayedRange pageNumber="+pageNumber+" mode="+displayView+" rx="+rx+" ry="+ry);

		if(displayView==SINGLE_PAGE)
			return;

		getDisplayedRectangle();

		if(displayView==FACING){

			if(pageCount==2){ //special case
				startViewPage=1;
				endViewPage=2;
			}else{
				startViewPage=pageNumber;

				if(startViewPage==1){ //special case
					endViewPage=1;
				}else if((startViewPage & 1)!=1){ //right even page selected
					startViewPage=pageNumber;
					endViewPage=pageNumber+1;
				}else{ //left odd page selected
					startViewPage=pageNumber-1;
					endViewPage=pageNumber;
				}
			}
		}else{


			//// START SI'S PAGE COUNTER ////////////

			int newPage = updatePageDisplayed();

			pdf.pageNumber=newPage;

			//// END SI'S PAGE COUNTER ////////////

			//update page number
			if(newPage!=-1 && customSwingHandle!=null)
				( (org.jpedal.gui.GUIFactory) customSwingHandle).setPage(newPage);

			boolean xOverlap,yOverlap;

			//find first page
			for(int i=1;i<pageCount+1;i++){

				xOverlap = ((rx < (xReached[i] + pageW[i]) && ((rw) > xReached[i])));
				yOverlap = ((ry < (yReached[i] + pageH[i]) && ((rh+ry) > yReached[i])));

				//System.out.println(i+" ry="+ry+" rh="+rh+" <> "+" "+yOverlap+" "+(ry < (yReached[i] + pageH[i]))+" "+ ((rh) > yReached[i])+" yReached[i]="+yReached[i]+" pageH="+pageH[i]);

				if((xOverlap)&&(yOverlap)){
					//System.out.println("first page="+i);
					startViewPage=i;
					i=pageCount;
				}
			}

			//allow for  2 page doc with both pages onscreen
			endViewPage=pageCount;

			//find first page not visible
			for(int i=startViewPage;i<pageCount+1;i++){

				//System.out.println("i="+i+" "+xReached.length+" "+pageW.length);
				//System.out.println("page "+i+" x="+x+" xreached"+xReached[i]+" y="+y+" yReached="+yReached[i]+" xOverlap="+xOverlap+" yOverlap="+yOverlap);
				xOverlap=((rx <(xReached[i]+pageW[i])&&((rw)>xReached[i])));
				yOverlap=((ry <(yReached[i]+pageH[i])&&((rh+ry+insetH+currentOffset.pageGap)>yReached[i])));

				if(((displayView==CONTINUOUS_FACING)||(displayView==CONTINUOUS) ||(!xOverlap))&&(!yOverlap)){ //find first page not visible
					//System.out.println("startViewPage="+startViewPage+" last page="+(i-1));
					endViewPage=i-1;
					i=pageCount;
				}
			}
		}

		//move to previous page which may overlap
		if(displayView!=FACING && startViewPage>1)
			startViewPage--;

		if(debugLayout)
			System.out.println("page range start="+startViewPage+" end="+endViewPage);

	}


	private int updatePageDisplayed() {
		int newPage=-1;

		if(displayView==CONTINUOUS){
			for(int i=1;i<pageCount;i++){

				/**
				 * check to see if page is off the top or not, if so
				 * skip page and check next
				 */
				if(((yReached[i] + insetH) - ry) + pageH[i] < 0)
					continue;

				int visibleAmountOfPage1, visibleAmountOfPage2;


				if(ry > (yReached[i] + insetH)){ // top page has gone at least slightly off the top
					visibleAmountOfPage1 = (yReached[i] + insetH - ry) + pageH[i];

					visibleAmountOfPage2 = rh-yReached[i+1];

					if(visibleAmountOfPage2 > pageH[i+1]) // all of page is visible
						visibleAmountOfPage2 = pageH[i+1];

					/**
					 * set the counter to the page that has the most visible on screen
					 */
					if(visibleAmountOfPage1 >= visibleAmountOfPage2){
						newPage=i;
					}else{
						newPage=i+1;
					}

				}else{ // all of top page is visible, so set that as current page
					newPage=i;
				}

				break;
			}
		}else if(displayView==CONTINUOUS_FACING){

			/**
			 * special case to see if first page is visible
			 */
			if(((yReached[1] + insetH) - ry) + pageH[1] >= 0){ // first page is visible

				if(pageCount > 1){

					int visibleAmountOfPage1, visibleAmountOfPage2;

					if(ry > (yReached[1] + insetH)){ // top page has gone at least slightly off the top
						visibleAmountOfPage1 = ((yReached[1] + insetH - ry)) + (pageH[1]);

						visibleAmountOfPage2 = rh-yReached[2];

						if(visibleAmountOfPage2 > pageH[1]) // all of page is visible
							visibleAmountOfPage2 = pageH[1];

						/**
						 * set the counter to the page that has the most visible on screen
						 */
						if(visibleAmountOfPage1 >= visibleAmountOfPage2){
							newPage=1;
						}else{
							newPage=2;
						}

					}else{ // all of top page is visible, so set that as current page
						newPage=1;
					}

				}else{
					newPage=1;
				}
			}else{ // first page is not visible

				for (int i = 2; i < pageCount; i += 2) {

					/**
					 * check to see if page is off the top or not, if so
					 * skip page and check next
					 */
					if(((yReached[i] + insetH) - ry) + pageH[i] < 0)
						continue;

					int visibleAmountOfPage1, visibleAmountOfPage2;

					if(ry > (yReached[i] /*+ insetH*/)  /*&&(i+2>yReached.length)*/){ // top page has gone at least slightly off the top
						visibleAmountOfPage1 = ((yReached[i] + insetH - ry)) + (pageH[i]);

						if(i+2>=pageCount)
							visibleAmountOfPage2=0;
						else
							visibleAmountOfPage2 = rh-yReached[i+2];

						if(visibleAmountOfPage2 > pageH[i]) // all of page is visible
							visibleAmountOfPage2 = pageH[i];

						/**
						 * set the counter to the page that has the most visible on screen
						 */
						if(visibleAmountOfPage1 >= visibleAmountOfPage2){
							newPage=i;
						}else{
							newPage=i+2;
						}

					}else
						newPage=i; // all of top page is visible, so set that as current page


					break;
				}
			}
		}
		return newPage;
	}

	private void drawOtherPages(AffineTransform original,AffineTransform viewScaling,Graphics2D g2,
			Rectangle userAnnot,float scaling,boolean isAccelerated) {

		int startPage=1,endPage=this.pageCount;
		if(debugLayout)
			System.out.println(isAccelerated+" rawPages---------------------"+startPage+ ' ' +endPage);

		/**
		 * if accelerated, all must fit so put on page
		 * otherwise get range
		 */
		startPage=startViewPage;
		endPage=endViewPage;

        if(startPage>endPage)
			return;


		//special case for 2 page facing
		//always draw last page incase just overlaps
		if((this.displayView!=CONTINUOUS)&&(pageCount==2))
			endPage++;

		if(debugLayout)
			System.out.println("----------------drawOtherPages---------------------"+startPage+ ' ' +endPage);

		AffineTransform old=g2.getTransform();
		Shape oldClip=g2.getClip();

		for(int i=startPage;i<endPage+1;i++){

			if(i>this.pageCount)
				break;

			int displayRotation=pageData.getRotation(i);



			if(((!isAccelerated) || (accleratedPagesAlreadyDrawn.get(new Integer(i))==null))){

				DynamicVectorRenderer otherPage=(DynamicVectorRenderer) currentPageViews.get(new Integer(i));

				/**
				 *handle clip - crop box values
				 */
				int dx=pageData.getScaledCropBoxX(i);
				int dy=pageData.getScaledCropBoxY(i);
				double cropX=dx;
				double cropY=dy;
				double cropH=pageData.getCropBoxHeight(i);

				int crx,cry;

				//otherPage=null;
				if((otherPage!=null)){

					if(debugLayout)
						System.out.println("draw "+i+" page views="+otherPage);

					g2.setTransform(original);

					if(!isAccelerated)
						g2.setClip(pdf.getVisibleRect());
					g2.setTransform(old);

					crx= (int) (xReached[i]/scaling);
					cry= (int) (yReached[i]/scaling);
					g2.translate(crx-dx,-dy-cry);

					int diff=0, diffY=0;

					/**
					 * sort out translations to draw pages in correct
					 * locations
					 */

					if(displayView==Display.CONTINUOUS) //centre on continuous
						if(displayRotation==0 || displayRotation==180)
							diff=(int) ((currentOffset.widestPageNR -(pageW[i]/scaling))/2);
						else
							diff=(int) ((currentOffset.widestPageR -(pageW[i]/scaling))/2);

					//adjustment +" "++" "+for scaling value which assumes all pages same size
					if(displayRotation==pageData.getRotation(pageUsedForTransform)){
						if((this.displayRotation==0 || this.displayRotation==180))
							g2.translate(0,(pageH[pageUsedForTransform]-pageH[i])/scaling);
						else
							g2.translate(0,-(pageH[pageUsedForTransform]-pageH[i])/scaling);
					}

					//add in any screen rotation
					displayRotation=displayRotation+this.displayRotation;
					if(displayRotation>=360)
						displayRotation=displayRotation-360;					

					if(displayRotation==180){
						//System.out.println(">>>>>>>>>>>180<<<<<<<<<<<<<");
						AffineTransform aff=g2.getTransform();
						double x=aff.getTranslateX()/scaling;
						double y=aff.getTranslateY()/scaling;

						g2.translate(-x, -y);
						g2.rotate(Math.PI);
						g2.translate(-x-(pageW[i]/scaling)-diff, -(pageH[i]/scaling)-y);
						//g2.translate(0,-diffY);
						g2.translate(-(2*dx),-(2*dy));

						//factor out clip on first page
						//int dx2=pageData.getCropBoxX(pageUsedForTransform);
						//int dy2=pageData.getCropBoxY(pageUsedForTransform);
						//Removed as fix for "pdf ref" and "java O'reilys" issue.
						//g2.translate(-(2*dx2),-(2*dy2));

					}else if(displayRotation==270){
						//System.out.println(">>>>>>>>>>>270<<<<<<<<<<<<<");
						AffineTransform aff=g2.getTransform();
						double x=aff.getTranslateX()/scaling;
						double y=aff.getTranslateY()/scaling;

						g2.translate(-x, y);
						g2.rotate(Math.PI/2);
						g2.translate(-y,-x-(pageW[i]/scaling));

						int diff2=(int) ((pageW[i]-pageH[i])/(scaling*2));
						g2.translate((diff2*2)+diffY,diff);
						g2.translate(0,-(2*dx));
						int dy2=pageData.getScaledCropBoxY(pageUsedForTransform);
						g2.translate(0,-(2*dy2));

					}else if(displayRotation==90){
//						System.out.println(">>>>>>>>>>>90<<<<<<<<<<<<<");
						AffineTransform aff=g2.getTransform();
						double x=aff.getTranslateX()/scaling;
						double y=aff.getTranslateY()/scaling;

						g2.translate(-x, y);
						g2.rotate(-Math.PI/2);
						g2.translate(y-(pageW[i]/scaling),x);

						g2.translate(diffY,diff);
						g2.translate(-(2*dy),0);
						//g2.translate(-(2*dy),(2*dy));
					}else{
						//System.out.println(">>>>>>>>>>>360<<<<<<<<<<<<<");
						g2.translate(diff,diffY);

						//factor out clip on first page
						//int dx2=pageData.getCropBoxX(pageUsedForTransform);
						//int dy2=pageData.getCropBoxY(pageUsedForTransform);

						//Removed as fix for "pdf ref" and "java O'reilys" issue.
						//g2.translate(+(2*dy2),+(2*dx2));
					}

					otherPage.setScalingValues(cropX,cropH+cropY,scaling);

					int dw=(int)(pageW[i]/scaling);
					int dh=(int)(pageH[i]/scaling);

					if(displayRotation==90 || displayRotation==270){
						int tmp=dw;
						dw=dh;
						dh=tmp;

					}

					if(displayRotation==90 || displayRotation==270){

						int tmp=dx;
						dx=dy;
						dy=tmp;
					}

					g2.clip(new Rectangle(dx,dy,dw,dh));
//					g2.setClip(null); //For testing back buffer

					//AffineTransform before=g2.getTransform();	
					//g2.translate(0,-((2*dx)-10));
					otherPage.paint(g2,areas,viewScaling,userAnnot,false,true);

					//g2.setTransform(before);

					pagesDrawn.put(new Integer(i),"x");

					//add demo cross if needed
					if(PdfDecoder.inDemo){

						g2.setColor(Color.red);
						g2.drawLine(dx, dy, dx+dw, dy+dh);
						g2.drawLine(dx, dy+dh, dx+dw,dy);

//						g2.drawLine(0, 0, dw, dh);
//						g2.drawLine(0, dh, dw,0);

					}

					//mark as drawn
					if(isAccelerated)
						accleratedPagesAlreadyDrawn.put(new Integer(i),"x");
					g2.setTransform(old);
					g2.setClip(oldClip);
				}
			}
		}

	}


	public final void drawBorder() {
		if(debugLayout)
			System.out.println("DrawOtherBorders "+startViewPage+ ' ' +endViewPage);

		int startPage=startViewPage,endPage=endViewPage;

		if(startPage==0)
			return;

		if(displayView==Display.CONTINUOUS || displayView==Display.CONTINUOUS_FACING){
			startPage=1;
			endPage=this.pageCount;
		}

		if(rawAf!=null)
			g2.setTransform(rawAf);
		g2.setClip(rawClip);

		//always draw last page incase just overlaps
		if((this.displayView!=FACING)&&(startPage>1)){
			startPage--;

			//special case for 2 page facing
			//always draw last page incase just overlaps
			if(pageCount==2)
				endPage++;
		}

		//move back to align from corner
		int displacementPage=1;
		if((this.displayView!=CONTINUOUS)&&(displacementPage & 1)==1)
			displacementPage--;
		g2.translate(-xReached[displacementPage]+insetW,-yReached[displacementPage]+insetH);

		for(int i=startPage;i<endPage+1;i++){

			//fix for facing pages
			if(i==xReached.length)
				break;

			AffineTransform aff=null;

			/**
			 * add offsets for different views and allow for 2 pages with different orientations in page layouts
			 */
			if(displayView==Display.CONTINUOUS){ //centre on continuous

				aff=g2.getTransform(); //save settings

				int diff=0;
				if(displayRotation==0 || displayRotation==180)
					diff=(int) (((currentOffset.widestPageNR*scaling)-pageW[i])/2);
				else
					diff=(int) (((currentOffset.widestPageR*scaling)-pageW[i])/2);
				g2.translate(diff,0);

			}else if((displayView==Display.CONTINUOUS_FACING || displayView==Display.FACING) && pageCount>1){
				aff=g2.getTransform(); //save settings
				/**
				//factor in so centred
				int gaps=currentOffset.gaps;

				//work out combined total with of both facing pages
				int totalWidth=pageW[i];


				int dy=0;

				//workout pages
				int left,right;
				if((i & 1)==1){
					right=i;
					left=i-1;
				}else{
					left=i;
					right=i+1;
				}

				if(i==1) {  //special case with only 1 page
					totalWidth=totalWidth+pageW[2];
				}
				if(pageCount==2){	//special case when only 2 pages
					totalWidth=pageW[1]+pageW[2];
				}else if((i & 1)==1)//right page
					totalWidth=totalWidth+pageW[i-1];
				else if(i<pageCount)   //left
					totalWidth=totalWidth+pageW[i+1];
				else
					totalWidth=totalWidth+pageW[i];

				int diff=(int)((currentOffset.doublePageWidth*scaling)-totalWidth)/2;

				//g2.translate(diff,0);
				/**/
			}

			if((pagesDrawn.get(new Integer(i))==null)&&
					((accleratedPagesAlreadyDrawn.get(new Integer(i))==null))){

				try{

					g2.setColor(Color.white);
					g2.fillRect(xReached[i], yReached[i],pageW[i],pageH[i]);
				}catch(Exception e){
					e.printStackTrace();
				}
			}

			if(((accleratedPagesAlreadyDrawn.get(new Integer(i))==null))){
				if(PdfDecoder.inDemo){

					g2.setColor(Color.red);
					g2.drawLine(xReached[i], yReached[i], xReached[i]+pageW[i],yReached[i]+pageH[i]);
					g2.drawLine(xReached[i], pageH[i]+yReached[i], pageW[i]+xReached[i],yReached[i]);

				}
			}

			if(PdfDecoder.CURRENT_BORDER_STYLE==PdfDecoder.BORDER_SHOW){
				//fudge to get border round just the page
				if((pageW[i]>0)&&(pageH[i]>0)&&(myBorder!=null)){
					//fix for oversized component
					myBorder.paintBorder(pdf,g2,xReached[i]-1, yReached[i]-1, pageW[i]+2, pageH[i]+2);
				}
			}

			if(aff!=null)
				g2.setTransform(aff); //restore

		}
	}

	public final Rectangle drawPage(AffineTransform viewScaling, AffineTransform displayScaling,int pageUsedForTransform) {

		/**add in other elements*/
		if(displayScaling!=null){
			//recalc if page turned
			if(displayRotation!=oldRotation){
				this.setPageOffsets(this.pageCount,this.pageNumber);
				useAcceleration=true;
			}
			this.pageUsedForTransform=pageUsedForTransform;

			/**
			 * workout user area to view
			 */
			Rectangle userAnnot= null;

			if(!isAccelerated())
				calcVisibleArea(topH, topW);

			/**
			 * pass in values as needed for patterns
			 */
			//currentDisplay.setScalingValues(cropX,cropH+cropY,scaling);

			g2.transform(displayScaling);

			if(DynamicVectorRenderer.debugPaint)
				System.err.println("accelerate or redraw");

			boolean canDrawAccelerated=false;
			//use hardware acceleration - it sucks on large image so
			//we check scaling as well...
			if((useAcceleration)&&(!overRideAcceleration)&&(scaling<2))
				canDrawAccelerated= testAcceleratedRendering();

			if(canDrawAccelerated){

				// rendering to the back buffer:
				Graphics2D gBB = (Graphics2D)backBuffer.getGraphics();

				if(screenNeedsRedrawing){
					//fill background white to prevent memory overRun from previous graphics memory

					gBB.setColor(pdf.getBackground());
					gBB.fill(new Rectangle(0,0,backBuffer.getWidth(),backBuffer.getHeight()));

					screenNeedsRedrawing =false;
				}
				//draw volitile image
				if((isInitialised)&&(xReached !=null)){

					if(debugLayout)
						System.out.println("[acc] draw other pages");

					gBB.setTransform(displayScaling);
					setDisplacementOnG2(gBB);

					drawOtherPages(rawAf,viewScaling,gBB, userAnnot, scaling,true);

				}
				gBB.dispose();

				if(backBuffer !=null){

					//draw the buffer
					AffineTransform affBefore=g2.getTransform();
					g2.setTransform(rawAf);
					g2.setClip(rawClip);

					g2.setTransform(rawAf);
					//System.out.println(g2.getClip()+" ");
					g2.drawImage(backBuffer, insetW,insetH, pdf);
					g2.setTransform(affBefore);
				}
			}else{
				if(DynamicVectorRenderer.debugPaint)
					System.err.println("standard paint called ");

				//ensure empty if not now accelerated
				accleratedPagesAlreadyDrawn.clear();

                currentDisplay.setOptimsePainting(false);

                /**
				 * 	draw other page outlines and any decoded pages so visible
				 */
				drawOtherPages(rawAf,viewScaling,g2, userAnnot, scaling,false);

			}

			//track scaling so we can update dependent values
			oldScaling=scaling;
			oldRotation=displayRotation;


		}

		return null;
	}
}
