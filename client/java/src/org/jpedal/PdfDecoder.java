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
* PdfDecoder.java
* ---------------
*/

package org.jpedal;

import org.jpedal.objects.raw.PdfDictionary;
import org.jpedal.objects.raw.PdfObject;
import org.jpedal.objects.raw.PdfPageObject;

import java.awt.*;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.ComponentAdapter;
import java.awt.event.ComponentEvent;
import java.awt.geom.AffineTransform;
import java.awt.geom.Line2D;
import java.awt.image.BufferedImage;
import java.awt.print.PageFormat;
import java.awt.print.Pageable;
import java.awt.print.Paper;
import java.awt.print.Printable;
import java.awt.print.PrinterException;
import java.io.BufferedReader;
import java.io.ByteArrayInputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.JarURLConnection;
import javax.print.attribute.SetOfIntegerSyntax;
import java.net.URL;
import java.util.ArrayList;
import java.util.Enumeration;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Hashtable;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.StringTokenizer;
import java.util.TimerTask;
import java.util.Vector;
import java.util.jar.JarEntry;
import java.util.jar.JarFile;

import javax.swing.*;

//<start-jfr>
import org.jpedal.objects.acroforms.creation.FormFactory;
import org.jpedal.objects.acroforms.decoding.FormStream;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.objects.acroforms.rendering.DefaultAcroRenderer;
import org.jpedal.objects.acroforms.utils.ConvertToString;
//<start-adobe>
import org.jpedal.grouping.PdfGroupingAlgorithms;
//<end-adobe>
//<start-os>

import org.jpedal.objects.javascript.ExpressionEngine;
//<end-os>

//<end-jfr>

import org.jpedal.color.ColorSpaces;

//<start-adobe><start-thin>
import org.jpedal.examples.simpleviewer.Values;
import org.jpedal.examples.simpleviewer.utils.FileFilterer;
//<end-thin><end-adobe>
import org.jpedal.exception.PdfException;
import org.jpedal.exception.PdfFontException;

import org.jpedal.io.*;
import org.jpedal.objects.*;

//<start-jfr>

//<start-adobe>

import org.jpedal.gui.Hotspots;

import org.jpedal.objects.outlines.OutlineData;
import org.jpedal.objects.structuredtext.MarkedContentGenerator;

//<end-adobe>
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Messages;
import org.jpedal.utils.Strip;
import org.jpedal.external.Options;
import org.jpedal.external.ImageHandler;
import org.w3c.dom.Document;

//<<end-jfr>

import org.jpedal.parser.PdfStreamDecoder;
import org.jpedal.parser.DecodeStatus;
import org.jpedal.render.DynamicVectorRenderer;
import org.jpedal.fonts.FontMappings;
import org.jpedal.fonts.StandardFonts;
import org.jpedal.fonts.objects.FontData;
import org.jpedal.constants.JPedalSettings;
import org.jpedal.constants.SpecialOptions;


/**
 * Provides an object to decode pdf files and provide a rasterizer if required -
 * Normal usage is to create instance of PdfDecoder and access via public
 * methods. Examples showing usage in org.jpedal.examples - Inherits indirectly
 * from JPanel so can be used as a standard Swing component -
 * <p/>
 * Extends other classes to separate out GUI and business logic but should be
 * regarded as ONE object and PdfPanel should not be instanced - We recommend
 * you access JPedal using only public methods listed in API
 */
public class PdfDecoder extends PdfPanel implements Printable, Pageable {

	private static final long serialVersionUID = 1107156907326450528L;

	public static final String version = "3.51b12";

	public static final boolean optimiseType3Rendering = false;

    boolean isOptimisedImage=false;

	//Load file from URL into file then open file
	File tempURLFile;

	//Components required to make the download window for online pdfs
	boolean downloadCreated = false;
	JFrame download = null;
	JPanel p;
	JProgressBar pb;
	JLabel downloadMessage;
	JLabel downloadFile;
	JLabel turnOff;
	//JButton saveLocal;
	int downloadCount = 0;
	
	//Use Download Windom
	boolean useDownloadWindow = true;

	// flag to allow quick chnaging between old and new print turning alg.
	boolean oldSetup = false;

    // on/off switch for transparent images over text fix.
    boolean useARGBFix = true;

    // added for privt visbile
    boolean docIsLandscaped = false;

    // print only the visible area of the doc.
    boolean printOnlyVisible = false;

    //debug print visible
    private Rectangle clipVr = null;

	private int duplexGapEven = 0;
	private int duplexGapOdd = 0;

	private boolean isPDf = false;
	private boolean isMultiPageTiff = false;

    /**list of kids - we put in form even if Annots as have kid settings*/
    private Map formKids=new HashMap();

    private final Map overlayType = new HashMap();
	private final Map overlayColors = new HashMap();
	private final Map overlayObj = new HashMap();

	private final Map overlayTypeG = new HashMap();
	private final Map overlayColorsG = new HashMap();
	private final Map overlayObjG = new HashMap();




	//data from external FDF file
	private Map fdfData = null;

	/**
	 * flag for xfa form
	 */
	private boolean isXFA = false;



	//<start-jfr>
	private Javascript javascript = null;

	ImageHandler customImageHandler = null;

	/**
	 * provide access to pdf file objects
	 */
	PdfObjectReader currentPdfFile;

	//used to debug font substitution
	private static boolean debugFonts=false;
    private PdfAnnots printAnnots=null;

    /**
	 * given a ref, what is the page
	 * @param ref - PDF object reference
	 * @return - page number with  being first page
	 */
	public int getPageFromObjectRef(String ref) {
		return pageLookup.convertObjectToPageNumber(ref);
	}

	/**
	 * page lookup table using objects as key
	 */
	private PageLookup pageLookup = new PageLookup();

	/**
	 * flag to stop multiple access to background decoding
	 */
	private boolean isBackgroundDecoding = false;

	//<start-adobe>
	/**
	 * store outline data extracted from pdf
	 */
	private OutlineData outlineData = null;

	//<end-adobe>

	/**
	 * store outline data object reference so can be read if needed
	 */
	private Object outlineObject = null;

	/**
	 * store information object
	 */
	private String XMLObject;

	//<start-adobe>
	/**
	 * marked content
	 */
	private final MarkedContentGenerator content = new MarkedContentGenerator();
	//<end-adobe>

	/**
	 * store image data extracted from pdf
	 */
	private PdfImageData pdfImages = new PdfImageData();

	/**
	 * store image data extracted from pdf
	 */
	private PdfImageData pdfBackgroundImages = new PdfImageData();

	/**
	 * store text data and can be passed out to other classes
	 */
	private PdfData pdfData;

	/**
	 * store text data and can be passed out to other classes
	 */
	private PdfData pdfBackgroundData;

	/**
	 * lookup table to precalculated height values
	 */
	public static org.jpedal.fonts.PdfHeightTable currentHeightLookupData = null;

	//<end-jfr>

	/**
	 * flag to show if on mac so we can code around certain bugs
	 */
	public static boolean isRunningOnMac = false;
	public static boolean isRunningOnWindows = false;
	public static boolean isRunningOnLinux = false;

    public static boolean clipOnMac=false;

    /**
	 * provide print debug feature - used for internal development only
	 */
	public static boolean debugPrint = false;


	private boolean hasViewListener = false;

	//<start-adobe>
	//<start-os>
	private RefreshLayout viewListener = new RefreshLayout();
	// <end-os>
	//<end-adobe>

	private boolean oddPagesOnly = false, evenPagesOnly = false;

	private boolean pagesPrintedInReverse = false;
	private boolean stopPrinting = false;

	/**
	 * PDF version
	 */
	private String pdfVersion = "";

	/**
	 * Used to calculate displacement
	 */
	private int lastWidth;

	private int lastPage;

	public static boolean isDraft = true;

	private boolean useForms = true;

	/**
	 * direct graphics 2d to render onto
	 */
	private Graphics2D g2 = null;



	/**
	 * flag to show embedded fonts present
	 */
	private boolean hasEmbeddedFonts = false;

	/**
	 * list of fonts for decoded page
	 */
	private String fontsInFile = "";

	/**
	 * dpi for final images
	 */
	public static int dpi = 72;

	/**
	 * flag to tell software to embed x point after each character so we can
	 * merge any overlapping text together
	 */
	public static boolean embedWidthData = false;

	/**
	 * flag to show outline
	 */
	private boolean hasOutline = false;

	/**
	 * actual page range to print
	 */
	private int start = 0, end = -1;

	/**
	 * id demo flag disables output in demo
	 */
	// <start-demo>
	public static final boolean inDemo = false;

	
	/**custom upscale val for JPedal settings*/
	public static float multiplyer = 1;
	
	/**custom hi-res val for JPedal settings*/
	public static boolean hires = false;
	
	/**global value for IMAGE_UPSCALE param*/
	private static int GLOBAL_IMAGE_UPSCALE = 1;

	/**
	 * printing object
	 */
	private PdfStreamDecoder currentPrintDecoder = null;

	/**
	 * used by Canoo for printing
	 */
	private DynamicVectorRenderer printRender = null;

	/**
	 * last page printed
	 */
	private int lastPrintedPage = -1;

	/**
	 * flag to show extraction mode includes any text
	 */
	public static final int TEXT = 1;

	/**
	 * flag to show extraction mode includes original images
	 */
	public static final int RAWIMAGES = 2;

	/**
	 * flag to show extraction mode includes final scaled/clipped
	 */
	public static final int FINALIMAGES = 4;

	/**
	 * undocumented flag to allow shape extraction
	 */
	protected static final int PAGEDATA = 8;

	/**
	 * flag to show extraction mode includes final scaled/clipped
	 */
	public static final int RAWCOMMANDS = 16;

	/**
	 * flag to show extraction of clipped images at highest res
	 */
	public static final int CLIPPEDIMAGES = 32;

	/**
	 * flag to show extraction of clipped images at highest res
	 */
	public static final int TEXTCOLOR = 64;

	/**
	 * flag to show extraction of raw cmyk images
	 */
	public static final int CMYKIMAGES = 128;

	/**
	 * flag to show extraction of xforms metadata
	 */
	public static final int XFORMMETADATA = 256;

	/**
	 * flag to show extraction of colr required (used in Storypad grouping)
	 */
	public static final int COLOR = 512;

	/**
	 * flag to show render mode includes any text
	 */
	public static final int RENDERTEXT = 1;

	/**
	 * flag to show render mode includes any images
	 */
	public static final int RENDERIMAGES = 2;

	
	
	private byte[][] annotList;

    //flag Annot page read so we can cache
    private int annotPage=-1;

    /**
	 * flag to show if form data contained in current file
	 */
	private boolean isForm = false;

	/**
	 * current extraction mode
	 */
	private static int extractionMode = 7;

	/**
	 * current render mode
	 */
	protected static int renderMode = 7;

	/**
	 * decodes page
	 */
	private PdfStreamDecoder current;


	/**
	 * holds pdf id (ie 4 0 R) which stores each object
	 */
	Map pagesReferences = new Hashtable();

	/**
	 * flag to show if page read to stop multiple reads on Annots in multipage mode
	 */
	private Map pagesRead = new HashMap();


	
	
	PdfObject globalResources;


	/**
	 * flag to show if there must be a mapping value (program exits if none
	 * found)
	 */
	public static boolean enforceFontSubstitution = false;

	/**
	 * flag to show user wants us to display printable area when we print
	 */
	private boolean showImageable = false;

	/**
	 * font to use in preference to Lucida
	 */
	public static String defaultFont = null;

	/**
	 * holds pageformats
	 */
	private Map pageFormats = new Hashtable();

	final private static String separator = System.getProperty("file.separator");

	/**
	 * flag to show if data extracted as text or XML
	 */
	private static boolean isXMLExtraction = true;

	/**
	 * used by Storypad to include images in PDFData)
	 */
	private boolean includeImages;

	/**
	 * interactive status Bar
	 */
	private StatusBar statusBar = null;

	/**
	 * flag to say if java 1.3 version should be used for JPEG conversion (new
	 * JPEG bugs in Suns 1.4 code)
	 */
	public static boolean use13jPEGConversion = false;

	/**
	 * uses the scaling applied to the page unless over 1. In this case it uses
	 * a value of 1
	 */
	private boolean usePageScaling = false;

	/**
	 * tells JPedal to display screen using hires images
	 */
	boolean useHiResImageForDisplay = false;

	/**
	 * flag used to show if printing worked
	 */
	private boolean operationSuccessful = true;

	/**
	 * Any printer errors
	 */
	private String pageErrorMessages = "";

	String filename;

	private ObjectStore backgroundObjectStoreRef = new ObjectStore();

	// <start-13>
	private SetOfIntegerSyntax range;


	//list of pages in range for quick lookup
	private int[] listOfPages;

	// <end-13>

	private final static boolean flattenDebug = false;


	/**
	 * printing mode using inbuilt java fonts and getting java to rasterize
	 * fonts using Java font if match found (added to get around limitations in
	 * PCL printing via JPS)
	 */
	public static final int TEXTGLYPHPRINT = 1;

	/**
	 * printing mode using inbuilt java fonts and getting java to rasterize
	 * fonts using Java font if match found (added to get around limitations in
	 * PCL printing via JPS) - this is the default off setting
	 */
	public static final int NOTEXTPRINT = 0;

	/**
	 * printing mode using inbuilt java fonts and getting java to rasterize
	 * fonts using Java font if match found (added to get around limitations in
	 * PCL printing via JPS) - this is the default off setting
	 */
	public static final int TEXTSTRINGPRINT = 2;


	//flag to track if page decoded twice
	private int lastPageDecoded = -1;

	/**
	 * used is bespoke version of JPedal - do not use
	 */
	private boolean isCustomPrinting = false;

	public static final int SUBSTITUTE_FONT_USING_FILE_NAME = 1;
	public static final int SUBSTITUTE_FONT_USING_POSTSCRIPT_NAME = 2;
	public static final int SUBSTITUTE_FONT_USING_FAMILY_NAME = 3;
	public static final int SUBSTITUTE_FONT_USING_FULL_FONT_NAME = 4;
    public static final int SUBSTITUTE_FONT_USING_POSTSCRIPT_NAME_USE_FAMILY_NAME_IF_DUPLICATES= 5;

    /**
	 * determine how font substitution is done
	 */
    private static int fontSubstitutionMode = PdfDecoder.SUBSTITUTE_FONT_USING_FILE_NAME;
	//private static int fontSubstitutionMode=PdfDecoder.SUBSTITUTE_FONT_USING_POSTSCRIPT_NAME;
	//private static int fontSubstitutionMode=PdfDecoder.SUBSTITUTE_FONT_USING_FULL_FONT_NAME;
	//private static int fontSubstitutionMode=PdfDecoder.SUBSTITUTE_FONT_USING_FAMILY_NAME;
	//private static int fontSubstitutionMode=PdfDecoder.SUBSTITUTE_FONT_USING_POSTSCRIPT_NAME_USE_FAMILY_NAME_IF_DUPLICATES;

    /** the ObjectStore for this file for printing */
	ObjectStore objectPrintStoreRef = new ObjectStore();

	public static final int BORDER_SHOW=1;
	public static final int BORDER_HIDE=0;
	public static int CURRENT_BORDER_STYLE = 1;

	public void setBorderStyle(int style){
		CURRENT_BORDER_STYLE = style;
	}

	public int getBorderStyle(){
		return CURRENT_BORDER_STYLE;
	}


	//<start-adobe>
	//<start-jfr>

	/**
	 * pass current locations into Renderer so it can draw forms on
	 * other pages correctly offset
	 *
	 * @param xReached
	 * @param yReached
	 */
	protected void setMultiPageOffsets(int[] xReached, int[] yReached) {
		/**pass in values for forms/annots*/
		if (formsAvailable && formRenderer != null)
				formRenderer.getCompData().setPageDisplacements(xReached, yReached);

	}
	//<end-adobe>

	//<start-os>

	/**
	 * set a left margin for printing pages (ie for duplex)
	 * (ent only)
	 * @param oddPages
	 */
	public void setPrintIndent(int oddPages, int evenPages) {

		this.duplexGapOdd = oddPages;
		this.duplexGapEven = evenPages;

	}
	//<end-os>

	/**
	 * see if file open - may not be open if user interrupted open or problem
	 * encountered
	 */
	public boolean isOpen() {
		return isOpen;
	}

	//<start-adobe>
	/**
	 * return markedContent object as XML Document
	 * @return Document containing XML structure with data
	 */
	public Document getMarkedContent() {

		return content.getMarkedContentTree(currentPdfFile, this, pageLookup);
	}

	//<end-jfr>

	/**
	 * used by remote printing to pass in page metrics
	 *
	 * @param pageData
	 */
	public void setPageData(PdfPageData pageData) {
		this.pageData = pageData;
	}

	//used by Storypad to create set of outlines - not part of API
	//and will change
	public void setAlternativeOutlines(Rectangle[] outlines, String altName) {
		this.alternateOutlines = outlines;
		this.altName = altName;

		this.repaint();
	}

	/**
	 * used by Storypad to display split spreads not part of API
	 */
	public void flushAdditionalPages() {
		pages.clearAdditionalPages();
		xOffset = 0;
		additionalPageCount = 0;

	}

	/**
	 * used by Storypad to display split spreads not aprt of API
	 */
	public void addAdditionalPage(DynamicVectorRenderer dynamicRenderer, int pageWidth, int origPageWidth) {

		//pageWidth=pageWidth+this.insetW+this.insetW;
		pages.addAdditionalPage(dynamicRenderer, pageWidth, origPageWidth);

		if (additionalPageCount == 0) {
			lastWidth = xOffset + origPageWidth;
			xOffset = xOffset + pageWidth;
		} else {
			xOffset = xOffset + pageWidth;
			lastWidth = lastWidth + lastPage;
		}
		additionalPageCount++;
		lastPage = pageWidth;
		this.updateUI();
	}

	public int getXDisplacement() {
		return lastWidth;
	}

	public int getAdditionalPageCount() {
		return additionalPageCount;
	}

	//<end-adobe>
	/**
	 * used by Javascript to update page number
	 */
	public void updatePageNumberDisplayed(int page) {

		//update page number
		if (page != -1 && customSwingHandle != null)
			((org.jpedal.gui.GUIFactory) customSwingHandle).setPage(page);

	}

    /**
     * return page number for last page decoded (only use in SingleDisplay mode)
     */
    public int getlastPageDecoded() {
        return lastPageDecoded;  //To change body of created methods use File | Settings | File Templates.
    }

    /**
     * set page number for last page decoded (only use in SingleDisplay mode)
     * Only used when file is not PDf but has multiple pages (i.e multipaged tiff)
     */
    public void setlastPageDecoded(int page) {
        lastPageDecoded = page;
    }

    //<start-adobe>

	//<start-os>

	/**
	 * class to repaint multiple views
	 */
	private class RefreshLayout extends ComponentAdapter {

		Timer t = null;

		java.util.Timer t2 = null;

		/*
		 * (non-Javadoc)
		 *
		 * @see java.awt.event.ComponentListener#componentMoved(java.awt.event.ComponentEvent)
		 */
		public void componentMoved(ComponentEvent e) {
			startTimer();
			//	screenNeedsRedrawing=true;

		}

		/*
		 */
		public void componentResized(ComponentEvent e) {
			startTimer();
			//	screenNeedsRedrawing=true;

		}

		private void startTimer() {

			//whatever else, stop current decode
			//pages.stopGeneratingPage();

			//turn if off if running
			if (t2 != null)
				t2.cancel();

			//restart - if its not stopped it will trigger page update
			TimerTask listener = new PageListener();
			t2 = new java.util.Timer();
			t2.schedule(listener, 500);

		}

		/**
		 * used to update statusBar object if exists
		 */
		class PageListener extends TimerTask {

			public void run() {

				if (Display.debugLayout)
					System.out.println("ActionPerformed " + pageCount);

				pages.decodeOtherPages(pageNumber, pageCount);


			}
		}
	}

	// <end-os>
	//<end-adobe>
	/**
	 * work out machine type so we can call OS X code to get around Java bugs.
	 */
	static {

		/**
		 * see if mac
		 */
		try {
			String name = System.getProperty("os.name");
			if (name.equals("Mac OS X"))
                PdfDecoder.isRunningOnMac = true;
			else if (name.startsWith("Windows")) {
                PdfDecoder.isRunningOnWindows = true;
			} else {
				if (name.equals("Linux")) {
                    PdfDecoder.isRunningOnLinux = true;
				}
			}
		} catch (Exception e) {
			e.printStackTrace();
		}



	}

	/**
	 * allows a number of fonts to be mapped onto an actual font and provides a
	 * way around slightly differing font naming when substituting fonts - So if
	 * arialMT existed on the target machine and the PDF contained arial and
	 * helvetica (which you wished to replace with arialmt), you would use the
	 * following code -
	 * <p/>
	 * String[] aliases={"arial","helvetica"};
	 * currentPdfDecoder.setSubstitutedFontAliases("arialmt",aliases); -
	 * <p/>
	 * comparison is case-insensitive and file type/ending should not be
	 * included - For use in conjunction with -Dorg.jpedal.fontdirs options which allows
	 * user to pass a set of comma separated directories with Truetype fonts
	 * (directories do not need to exist so can be multi-platform setting)
	 */
	public void setSubstitutedFontAliases(String fontFileName, String[] aliases) {

		if (aliases != null) {

			String name = fontFileName.toLowerCase(), alias;
			int count = aliases.length;
			for (int i = 0; i < count; i++) {
				alias = aliases[i].toLowerCase();
				if (!alias.equals(name))
					FontMappings.fontSubstitutionAliasTable.put(alias, name);
			}
		}
	}

	/**
	 * takes a comma separated list of font directories and add to substitution
	 */
	private String addFonts(String fontDirs, String failed) {

		StringTokenizer fontPaths = new StringTokenizer(fontDirs, ",");

		while (fontPaths.hasMoreTokens()) {

			String fontPath = fontPaths.nextToken();

			if (!fontPath.endsWith("/") & !fontPath.endsWith("\\"))
				fontPath = fontPath + separator;

			//LogWriter.writeLog("Looking in " + fontPath + " for TT fonts");

			addTTDir(fontPath, failed);
		}

		return failed;
	}

	//<start-adobe>
	/**
	 * turns off the viewable area, scaling the page back to original scaling
	 * <br>
	 * <br>
	 * NOT RECOMMENDED FOR GENERAL USE (this has been added for a specific
	 * client and we have found it can be unpredictable on some PDF files).
	 */
	public void resetViewableArea() {

		if (viewableArea != null) {
			viewableArea = null;
			// @fontHandle currentDisplay.setOptimiseDrawing(true);
			setPageRotation(displayRotation);
			repaint();
		}
	}

	/**
	 * allows the user to create a viewport within the displayed page, the
	 * aspect ratio is keep for the PDF page <br>
	 * <br>
	 * Passing in a null value is the same as calling resetViewableArea()
	 * <p/>
	 * <br>
	 * <br>
	 * The viewport works from the bottom left of the PDF page <br>
	 * The general formula is <br>
	 * (leftMargin, <br>
	 * bottomMargin, <br>
	 * pdfWidth-leftMargin-rightMargin, <br>
	 * pdfHeight-bottomMargin-topMargin)
	 * <p/>
	 * <br>
	 * <br>
	 * NOT RECOMMENDED FOR GENERAL USE (this has been added for a specific
	 * client and we have found it can be unpredictable on some PDF files).
	 * <p/>
	 * <br>
	 * <br>
	 * The viewport will not be incorporated in printing <br>
	 * <br>
	 * Throws PdfException if the viewport is not totally enclosed within the
	 * 100% cropped pdf
	 */
	public AffineTransform setViewableArea(Rectangle viewport)
	throws PdfException {

		if (viewport != null) {

			double x = viewport.getX();
			double y = viewport.getY();
			double w = viewport.getWidth();
			double h = viewport.getHeight();

			// double crx = pageData.getCropBoxX(pageNumber);
			// double cry = pageData.getCropBoxY(pageNumber);
			double crw = pageData.getCropBoxWidth(pageNumber);
			double crh = pageData.getCropBoxHeight(pageNumber);

			// throw exception if viewport cannot fit in cropbox
			if (x < 0 || y < 0 || (x + w) > crw || (y + h) > crh) {
				throw new PdfException(
				"Viewport is not totally enclosed within displayed panel.");
			}

			// if viewport exactlly matches the cropbox
			if (crw == w && crh == h) {
			} else {// else work out scaling ang apply

				viewableArea = viewport;
				currentDisplay.setOptimiseDrawing(false);
				setPageRotation(displayRotation);
				repaint();
			}
		} else {
			resetViewableArea();
		}

		return viewScaling;
	}
	//<end-adobe>

	/**
	 * takes a String[] of font directories and adds to substitution - Can just
	 * be called for each JVM - Should be called before file opened - this
	 * offers an alternative to the call -DFontDirs - Passing a null value
	 * flushes all settings
	 *
	 * @return String which will be null or list of directories it could not
	 *         find
	 */
	public static String setFontDirs(String[] fontDirs) {

		String failed = null;

		if (FontMappings.fontSubstitutionTable == null) {
			FontMappings.fontSubstitutionTable = new HashMap();
			FontMappings.fontSubstitutionFontID = new HashMap();
            FontMappings.fontPossDuplicates = new HashMap();
            FontMappings.fontPropertiesTable = new HashMap();
        }

		try {
			if (fontDirs == null) { // idiot safety test
				LogWriter.writeLog("Null font parameter passed");
				FontMappings.fontSubstitutionAliasTable.clear();
				FontMappings.fontSubstitutionLocation.clear();
				FontMappings.fontSubstitutionTable.clear();
				FontMappings.fontSubstitutionFontID.clear();
                FontMappings.fontPossDuplicates.clear();
                FontMappings.fontPropertiesTable.clear();
            } else {

				int count = fontDirs.length;

				for (int i = 0; i < count; i++) {

					String fontPath = fontDirs[i];

					// allow for 'wrong' separator
					if (!fontPath.endsWith("/") & !fontPath.endsWith("\\"))
						fontPath = fontPath + separator;

					if(debugFonts)
						System.out.println("Looking in " + fontPath
								+ " for fonts");
					//LogWriter.writeLog("Looking in " + fontPath
					//		+ " for TT fonts");

					failed = addTTDir(fontPath, failed);
				}
			}
		} catch (Exception e) {
			LogWriter.writeLog("Unable to run setFontDirs " + e.getMessage());
		}

		return failed;
	}

	/**
	 * add a truetype font directory and contents to substitution
	 */
	private static String addTTDir(String fontPath, String failed) {

		if (FontMappings.fontSubstitutionTable == null) {
			FontMappings.fontSubstitutionTable = new HashMap();
			FontMappings.fontSubstitutionFontID = new HashMap();
            FontMappings.fontPossDuplicates = new HashMap();
            FontMappings.fontPropertiesTable = new HashMap();
        }

		File currentDir = new File(fontPath);

		if ((currentDir.exists()) && (currentDir.isDirectory())) {

			String[] files = currentDir.list();

			if (files != null) {
				int count = files.length;

				for (int i = 0; i < count; i++) {
					String currentFont = files[i];

					addFontFile(currentFont, fontPath);

				}
			}
		} else {
			if (failed == null) {
				failed = fontPath;
			} else {
				failed = failed + ',' + fontPath;
			}
		}

		return failed;
	}


	/**
	 * set mode to use when substituting fonts (default is to use Filename (ie arial.ttf)
	 * Options are  SUBSTITUTE_* values from PdfDecoder
	 */
	public static void setFontSubstitutionMode(int mode) {
		fontSubstitutionMode = mode;
	}

    /**
	 * set mode to use when substituting fonts (default is to use Filename (ie arial.ttf)
	 * Options are  SUBSTITUTE_* values from PdfDecoder
	 */
	public static int getFontSubstitutionMode() {
		return fontSubstitutionMode;
	}

    /**
	 * method to add a single file to the PDF renderer
	 *
	 * @param currentFont - actual font name we use to identify
	 * @param fontPath    - full path to font file used for this font
	 */
	public static void addFontFile(String currentFont, String fontPath) {

		if (FontMappings.fontSubstitutionTable == null) {
			FontMappings.fontSubstitutionTable = new HashMap();
			FontMappings.fontSubstitutionFontID = new HashMap();
            FontMappings.fontPossDuplicates = new HashMap();
            FontMappings.fontPropertiesTable = new HashMap();
        }

		//add separator if needed
		if (fontPath != null && !fontPath.endsWith("/") && !fontPath.endsWith("\\"))
			fontPath = fontPath + separator;

		String name = currentFont.toLowerCase();

		//decide font type
		int type = StandardFonts.getFontType(name);


		if(debugFonts)
			System.out.println(type+" "+name);

		if (type != StandardFonts.FONT_UNSUPPORTED) {
			// see if root dir exists
			InputStream in = null;
			try {
				in = new FileInputStream(fontPath + currentFont);

			} catch (FileNotFoundException e) {
				e.printStackTrace();
			}

			// if it does, add
			if (in != null) {

				String fontName;

				//name from file
				int pointer = currentFont.indexOf('.');
				if (pointer == -1)
					fontName = currentFont.toLowerCase();
				else
					fontName = currentFont.substring(0, pointer).toLowerCase();

				if(debugFonts)
					System.out.println("Added "+fontName);

                //choose filename
                if (fontSubstitutionMode == PdfDecoder.SUBSTITUTE_FONT_USING_FILE_NAME) {
					if(type==StandardFonts.TYPE1)
						FontMappings.fontSubstitutionTable.put(fontName, "/Type1");
					else
						FontMappings.fontSubstitutionTable.put(fontName, "/TrueType");

					FontMappings.fontSubstitutionLocation.put(fontName, fontPath + currentFont);

                    //store details under file
                    FontMappings.fontPropertiesTable.put(fontName,StandardFonts.getFontDetails(type, fontPath + currentFont));


                } else if (type == StandardFonts.TRUETYPE_COLLECTION || type == StandardFonts.TRUETYPE) {

                    if(fontSubstitutionMode==PdfDecoder.SUBSTITUTE_FONT_USING_POSTSCRIPT_NAME_USE_FAMILY_NAME_IF_DUPLICATES){

                        //get both possible values
                        String[] postscriptNames = new String[0];
                        try {
                            postscriptNames = StandardFonts.readNamesFromFont(type, fontPath + currentFont, PdfDecoder.SUBSTITUTE_FONT_USING_POSTSCRIPT_NAME);
                        } catch (Exception e) {
                            e.printStackTrace();
                        }

                        String[] familyNames = new String[0];
                        try {
                            familyNames = StandardFonts.readNamesFromFont(type, fontPath + currentFont, PdfDecoder.SUBSTITUTE_FONT_USING_FAMILY_NAME);
                        } catch (Exception e) {
                            e.printStackTrace();
                        }

                        int fontCount=postscriptNames.length;
                        for(int ii=0;ii<fontCount;ii++){


                            //allow for null and use font name
                            if (postscriptNames[ii] == null)
                                postscriptNames[ii] = Strip.stripAllSpaces(fontName);

                            //allow for null and use font name
                            if (familyNames[ii] == null)
                                familyNames[ii] = Strip.stripAllSpaces(fontName);

                            Object fontSubValue= FontMappings.fontSubstitutionTable.get(postscriptNames[ii]);
                            Object possDuplicate=FontMappings.fontPossDuplicates.get(postscriptNames[ii]);
                            if(fontSubValue==null && possDuplicate==null){ //first time so store and track

                                //System.out.println("store "+postscriptNames[ii]);

                                FontMappings.fontSubstitutionTable.put(postscriptNames[ii], "/TrueType");
                                FontMappings.fontSubstitutionLocation.put(postscriptNames[ii], fontPath + currentFont);
                                FontMappings.fontSubstitutionFontID.put(postscriptNames[ii], new Integer(ii));

                                //and remember in case we need to switch
                                FontMappings.fontPossDuplicates.put(postscriptNames[ii],familyNames[ii]);

                            }else if(!familyNames[ii].equals(postscriptNames[ii])){
                                //if no duplicates,add to mappings with POSTSCRIPT and log filename
                                //both lists should be in same order and name

                                //else save as FAMILY_NAME
                                FontMappings.fontSubstitutionTable.put(postscriptNames[ii], "/TrueType");
                                FontMappings.fontSubstitutionLocation.put(postscriptNames[ii], fontPath + currentFont);
                                FontMappings.fontSubstitutionFontID.put(postscriptNames[ii], new Integer(ii));

                                //store details under file
                                FontMappings.fontPropertiesTable.put(postscriptNames[ii],StandardFonts.getFontDetails(type, fontPath + currentFont));

                                //if second find change first match
                                if(!possDuplicate.equals("DONE")){

                                    //System.out.println("replace "+postscriptNames[ii]+" "+familyNames[ii]);

                                    //flag as done
                                    FontMappings.fontPossDuplicates.put(postscriptNames[ii],"DONE");

                                    //swap over
                                    FontMappings.fontSubstitutionTable.remove(postscriptNames[ii]);
                                    FontMappings.fontSubstitutionTable.put(familyNames[ii], "/TrueType");

                                    String font=(String)FontMappings.fontSubstitutionLocation.get(postscriptNames[ii]);
                                    FontMappings.fontSubstitutionLocation.remove(postscriptNames[ii]);
                                    FontMappings.fontSubstitutionLocation.put(familyNames[ii], font);

                                    FontMappings.fontSubstitutionFontID.remove(postscriptNames[ii]);
                                    FontMappings.fontSubstitutionFontID.put(familyNames[ii], new Integer(ii));

                                    //store details under file
                                    FontMappings.fontPropertiesTable.remove(familyNames[ii]);
                                    FontMappings.fontPropertiesTable.put(familyNames[ii],StandardFonts.getFontDetails(type, fontPath + currentFont));

                                }
                            }
                        }

                    }else{ //easy version
                        //read 1 or more font mappings from file
                        String[] fontNames = new String[0];
                        try {
                            fontNames = StandardFonts.readNamesFromFont(type, fontPath + currentFont, fontSubstitutionMode);
                        } catch (Exception e) {
                            e.printStackTrace();
                        }

                        for (int i = 0; i < fontNames.length; i++) {

                            //allow for null and use font name
                            if (fontNames[i] == null)
                                fontNames[i] = Strip.stripAllSpaces(fontName);

                            FontMappings.fontSubstitutionTable.put(fontNames[i], "/TrueType");
                            FontMappings.fontSubstitutionLocation.put(fontNames[i], fontPath + currentFont);
                            FontMappings.fontSubstitutionFontID.put(fontNames[i], new Integer(i));

                            //store details under file
                            FontMappings.fontPropertiesTable.put(fontNames[i],StandardFonts.getFontDetails(type, fontPath + currentFont));

                        }
                    }
                }
			} else {
				LogWriter.writeLog("No fonts found at " + fontPath);
			}
		}
	}

    //<start-adobe>
	/**
	 * return type of alignment for pages if smaller than panel
	 * - see options in Display class.
	 */
	public int getPageAlignment() {
		return alignment;
	}


	/**
	 * This will be needed for text extraction as it paramter makes sure widths
	 * included in text stream
	 *
	 * @param newEmbedWidthData -
	 *                          flag to embed width data in text fragments for use by grouping
	 *                          algorithms
	 */
	public final void init(boolean newEmbedWidthData) {

		/** get local handles onto objects/data passed in */
		embedWidthData = newEmbedWidthData;

	}
	//<end-adobe>

	/**
	 * Recommend way to create a PdfDecoder if no rendering of page may be
	 * required<br>
	 * Otherwise use PdfDecoder()
	 *
	 * @param newRender flag to show if pages being rendered for JPanel or extraction
	 */
    public PdfDecoder(boolean newRender) {

		pages = new SingleDisplay(this);

		/** get local handles onto flag passed in */
		this.renderPage = newRender;

		setLayout(null);

		startup();
	}

	//<start-adobe>
	/**
	 * Not part of API - internal IDR method subject to frequent change
	 */
    public PdfDecoder(int mode, boolean newRender) {

		pages = new SingleDisplay(this);

		/** get local handles onto flag passed in */
		this.renderPage = newRender;

		extractionMode = 1;

		setLayout(null);

		init(true);

		startup();

		PdfStreamDecoder.runningStoryPad = true;

	}
	//<end-adobe>

	/**
	 *
	 */
	private void startup() {
//System.out.println(this+" PdfDecoder.startup()");
//ConvertToString.printStackTrace(5);
		formsAvailable = PdfStreamDecoder.isFormSupportAvailable();

		if (this.formsAvailable) {
			
			formRenderer = new DefaultAcroRenderer();

			//<start-os>
			setJavascript();
			//<end-os>

			//pass in user handler if set
			formRenderer.resetHandler(null, this,Options.FormsActionHandler);

			//pass in user handler if set
			formRenderer.resetHandler(null, this,Options.LinkHandler);

		}

		/**
		 * set global flags
		 */
		String debugFlag = System.getProperty("debug");

		if (debugFlag != null)
			LogWriter.setupLogFile(true, 1, "", "v", false);

		// <start-13>
		/**
		 * pick up D options and use settings
		 */
		try {
			String fontMaps = System.getProperty("org.jpedal.fontmaps");

			if (fontMaps != null) {
				StringTokenizer fontPaths = new StringTokenizer(fontMaps, ",");

				while (fontPaths.hasMoreTokens()) {

					String fontPath = fontPaths.nextToken();
					StringTokenizer values = new StringTokenizer(fontPath, "=:");

					int count = values.countTokens() - 1;
					String nameInPDF[] = new String[count];
					String key = values.nextToken();
					for (int i = 0; i < count; i++)
						nameInPDF[i] = values.nextToken();

					setSubstitutedFontAliases(key, nameInPDF); //$NON-NLS-1$

				}
			}

		} catch (Exception e) {
			LogWriter.writeLog("Unable to read org.jpedal.fontmaps " + e.getMessage());
		}

		/**
		 * pick up D options and use settings
		 */
		try {
			String fontDirs = System.getProperty("org.jpedal.fontdirs");
			String failed = null;
			if (fontDirs != null)
				failed = addFonts(fontDirs, failed);
			if (failed != null)
				LogWriter.writeLog("Could not find " + failed);
		} catch (Exception e) {
			LogWriter.writeLog("Unable to read FontDirs " + e.getMessage());
		}
		// <end-13>

		// needs to be set so we can over-ride
		if (renderPage) {
			setToolTipText("image preview");

			// initialisation on font
			highlightFont = new Font("Lucida", Font.BOLD, size);

			setPreferredSize(new Dimension(100, 100));
		}
	}

	/**
	 * flag to enable popup of error messages in JPedal
	 */
	public static boolean showErrorMessages = false;

	public static boolean useFullSizeImage;

	public static int samplingUsed = -1;

    protected int specialMode= SpecialOptions.NONE;


    /**
	 * Recommend way to create a PdfDecoder for renderer only viewer (not
	 * recommended for server extraction only processes)
	 */
    public PdfDecoder() {

		pages = new SingleDisplay(this);

		this.renderPage = true;

		setLayout(null);

		startup();
	}

	private boolean isOpen = false;

	//<start-adobe>
	//<start-jfr>
	//<end-adobe>


	/**
	 * convenience method to close the current PDF file
	 */
	final public void closePdfFile() {

		if (!isOpen)
			return;
		isOpen = false;

		displayScaling = null;


		lastPageDecoded = -1;

		// ensure no previous file still being decoded
		stopDecoding();
		pages.disableScreen();

		//flag all pages unread
		pagesRead.clear();

		//<start-jfr>
		//<start-os>
		if (javascript != null)
			javascript.closeFile();
		//<end-os>
		//<end-jfr>

        //flush arrays
		overlayType.clear();
		overlayColors.clear();
		overlayObj.clear();

		//flush arrays
		overlayTypeG.clear();
		overlayColorsG.clear();
		overlayObjG.clear();


		// pass handle into renderer
		if (formsAvailable) {
			currentAcroFormData = null;
			if (formRenderer != null) {
				formRenderer.openFile(pageCount);

				formRenderer.resetFormData(currentAcroFormData, insetW,
						insetH, pageData, currentPdfFile, formKids);

			}
		}

		// <start-os>
		// remove listener if setup
		if (hasViewListener) {
			hasViewListener = false;

			//flush any cached pages
			pages.flushPageCaches();

			//<start-adobe>
			removeComponentListener(viewListener);
			//<end-adobe>

		}
		// <end-os>

		if (currentPdfFile != null)
			currentPdfFile.closePdfFile();

		currentPdfFile = null;

		pages.disableScreen();
		currentDisplay.flush();
		objectStoreRef.flush();

		ObjectStore.flushPages();

		objectPrintStoreRef.flush();

		oldScaling = -1;
		
		pageCount=0;

	}

	/**
	 * convenience method to get the PDF data as a byte array - works however
	 * file was opened.
	 *
	 * @return byte array containing PDF file
	 */
	final public byte[] getPdfBuffer() {

		byte[] buf = null;
		if (currentPdfFile != null)
			buf = currentPdfFile.getPdfBuffer();

		return buf;
	}

	//<start-adobe>
	/**
	 * Access should not generally be required to
	 * this class. Please look at getBackgroundGroupingObject() - provide method
	 * for outside class to get data object containing text and metrics of text. -
	 * Viewer can only access data for finding on page
	 *
	 * @return PdfData object containing text content from PDF
	 */
	final public PdfData getPdfBackgroundData() {

		return pdfBackgroundData;
	}

	/**
	 * Access should not generally be required to
	 * this class. Please look at getGroupingObject() - provide method for
	 * outside class to get data object containing raw text and metrics of text<br> -
	 * Viewer can only access data for finding on page
	 *
	 * @return PdfData object containing text content from PDF
	 */
	final public PdfData getPdfData() throws PdfException {
        if ((extractionMode & PdfDecoder.TEXT) == 0)
			throw new PdfException(
			"[PDF] Page data object requested will be empty as text extraction disabled. Enable with PdfDecoder method setExtractionMode(PdfDecoder.TEXT | other values");
		else
			return pdfData;
	}

	/**
	 * <B>Not part of API</B> provide method for outside class to get data
	 * object containing information on the page for calculating grouping <br>
	 * Please note: Structure of PdfPageData is not guaranteed to remain
	 * constant. Please contact IDRsolutions for advice.
	 *
	 * @return PdfPageData object
	 * @deprecated from 2.50
	 */
	final public PdfPageData getPdfBackgroundPageData() {
		return pageData;
	}

	/**
	 * flag to show if PDF document contains an outline
	 */
	final public boolean hasOutline() {
		return hasOutline;
	}

	/**
	 * return a DOM document containing the PDF Outline object as a DOM Document - may return null
	 */
	final public Document getOutlineAsXML() {

		if (outlineData == null) {
			if (outlineObject != null) {

				/**/
				try {
					outlineData = new OutlineData(pageCount);
					outlineData.readOutlineFileMetadata(outlineObject,
							currentPdfFile, pageLookup);

				} catch (Exception e) {
					System.out.println("Exception " + e + " accessing outline "
							+ outlineObject);
					outlineData = null;
				}
				/***/

			}
		}
		if (outlineData != null)
			return outlineData.getList();
		else return null;
	}

	/**
	 * Provides method for outside class to get data
	 * object containing information on the page for calculating grouping <br>
	 * Please note: Structure of PdfPageData is not guaranteed to remain
	 * constant. Please contact IDRsolutions for advice.
	 *
	 * @return PdfPageData object
	 */
	final public PdfPageData getPdfPageData() {
		return pageData;
	}
	//<end-adobe>

	/**
	 * set page range (inclusive) -
	 * If end is less than start it will print them
	 * backwards (invalid range will throw PdfException)
	 *
	 * @throws PdfException
	 */
	public void setPagePrintRange(int start, int end) throws PdfException {
		this.start = start;
		this.end = end;

		//all returns huge number not page end range
		if (end == 2147483647)
			end = pageCount;

		//if actually backwards, reverse order
		if (start > end) {
			int tmp = start;
			start = end;
			end = tmp;
		}
		if ((start < 1) || (end < 1) || (start > this.pageCount) || (end > this.pageCount))
			throw new PdfException(Messages.getMessage("PdfViewerPrint.InvalidPageRange") + ' ' + start + ' ' + end);

	}

	/**
	 * allow user to select only odd or even pages to print
	 */
	public void setPrintPageMode(int mode) {
		oddPagesOnly = (mode & PrinterOptions.ODD_PAGES_ONLY) == PrinterOptions.ODD_PAGES_ONLY;
		evenPagesOnly = (mode & PrinterOptions.EVEN_PAGES_ONLY) == PrinterOptions.EVEN_PAGES_ONLY;

		pagesPrintedInReverse = (mode & PrinterOptions.PRINT_PAGES_REVERSED) == PrinterOptions.PRINT_PAGES_REVERSED;

	}

	// <start-13>
	/**
	 * set inclusive range to print (see SilentPrint.java and SimpleViewer.java
	 * for sample print code (invalid range will throw PdfException)
	 * can  take values such as  new PageRanges("3,5,7-9,15");
	 */
	public void setPagePrintRange(SetOfIntegerSyntax range) throws PdfException {

		if (range == null)
			throw new PdfException("[PDF] null page range entered");

		this.range = range;
		this.start = range.next(0); // find first

		int rangeCount = 0;

		//get number of items
		for (int ii = 0; ii < this.pageCount; ii++) {
			if (range.contains(ii))
				rangeCount++;
		}


		//setup array
		listOfPages = new int[rangeCount + 1];

		// find last
		int i = start;
		this.end = start;
		if (range.contains(2147483647)) //allow for all returning largest int
			end = pageCount;
		else {
			while (range.next(i) != -1)
				i++;
			end = i;
		}

		//if actually backwards, reverse order
		if (start > end) {
			int tmp = start;
			start = end;
			end = tmp;
		}

		//populate table
		int j = 0;

		for (int ii = start; ii < end + 1; ii++) {
			if (range.contains(ii) && (!oddPagesOnly || (ii & 1) == 1) && (!evenPagesOnly || (ii & 1) == 0)) {
				listOfPages[j] = ii - start;
				j++;
			}
		}

		if ((start < 1) || (end < 1) || (start > this.pageCount) || (end > this.pageCount))
			throw new PdfException(Messages.getMessage("PdfViewerPrint.InvalidPageRange") + ' ' + start + ' ' + end);

	}

	// <end-13><end-jfr>

	/**
	 * tells program to try and use Java's font printing if possible as work
	 * around for issue with PCL printing - values are PdfDecoder.TEXTGLYPHPRINT
	 * (use Java to rasterize font if available) PdfDecoder.TEXTSTRINGPRINT(
	 * print as text not raster - fastest option) PdfDecoder.NOTEXTPRINT
	 * (default - highest quality)
	 */
	public void setTextPrint(int textPrint) {
		this.textPrint = textPrint;
	}

	/**
	 * flag to use Java's inbuilt font renderer if possible
	 */
	private int textPrint = 0;

	/**
	 * the size above which objects stored on disk (-1 is off)
	 */
	private int minimumCacheSize = -1;

	/**
	 * return any messages on decoding
	 */
	String decodeStatus = "";

	/**
	 * current print page or -1 if finished
	 */
	private int currentPrintPage = 0;

	private boolean imagesProcessedFully,hasNonEmbeddedCIDFonts;
    private String nonEmbeddedCIDFonts="";

    private Object customSwingHandle;

	private Object userExpressionEngine;

	private boolean generateGlyphOnRender;

	private boolean thumbnailsBeingDrawn;

	private float oldScaling = -1;

	/**
	 * switch on Javascript
	 */
	private boolean useJavascript = true;

	private boolean centerOnScaling = true;

	public static boolean extAtBestQuality = false;

	public void setCenterOnScaling(boolean center){
		centerOnScaling = center;
	}

	/**
	 * implements the standard Java printing functionality if start <end it will
	 * print it in reverse
	 *
	 * @param graphics -
	 *                 object page rendered onto
	 * @param pf       PageFormat object used to print
	 * @param page     -
	 *                 current page index (less 1 so start at page 0)
	 * @return int Printable.PAGE_EXISTS or Printable.NO_SUCH_PAGE
	 * @throws PrinterException
	 */
	public int print(Graphics graphics, PageFormat pf, int page)
	throws PrinterException {

		//used to debug remote printing
		final boolean debugRemote = false;

		if (debugRemote)
			System.out.println("Print called");

		PdfObject pdfObject=null,Resources=null;

		//exit if requested
		if (stopPrinting) {
			stopPrinting = false;
			start = 0;
			end = 0;

			return Printable.NO_SUCH_PAGE;
		}

		int i = Printable.PAGE_EXISTS;

		float dx = 0, dy = 0;

		//<start-13>
		/**
		 * needs to double up to get correct page
		 */

		/*adjust if just printing half pages*/
		if ((range == null) && (oddPagesOnly || evenPagesOnly)) {
			page = page * 2;

			// convert page ranges
		} else if (this.range != null) {

			page = listOfPages[page];

		}
		// <end-13>

		/**
		 * decode and render page onto 2D object
		 */

		try {

			double scaling = 1;

			/**
			 * set scaling value - pageScaling is deprecated but left in for
			 * moment to provide backwards compatability
			 */
			if (usePageScaling)
				scaling = this.scaling;

			// increase index to match out page numbers- JavaPrint starts at 0
			// also allow for reverse
			int lastPage = end, firstPage = start;

			if (pagesPrintedInReverse) {
				firstPage = start;
				lastPage = end;
				page = end - page;
			} else if (((end != -1) && (end < start))) {
				firstPage = end;
				lastPage = start;
				page = start - page;
			} else
				page = start + page;

			if (end == -1)
				page++;

			currentPrintPage = page;

			// make sure in range
			if ((page > pageCount) | ((end != -1) & (page > lastPage))) {

				currentPrintPage = -1;

				if (debugPrint)
					System.out.println("no such page");

				return Printable.NO_SUCH_PAGE;
			}

			/**
			 * passes validation for page range so actually get page contents and decode
			 */
			if ((end == -1) | ((page >= firstPage) & (page <= lastPage))) {

				operationSuccessful = true;
				pageErrorMessages = "";

				try {

					/**
					 * setup for decoding page
					 */

					/** get pdf object id for page to decode */
					String currentPageOffset = (String) pagesReferences.get(new Integer(page));

					if (currentPageOffset != null || isCustomPrinting) {

						if (currentPdfFile == null && !isCustomPrinting)
							throw new PrinterException("File not open - did you call closePdfFile() inside a loop and not reopen");

						if (!isCustomPrinting) {

							//read page or next pages
							pdfObject=new PdfPageObject(currentPageOffset);
							currentPdfFile.readObject(pdfObject, currentPageOffset,false, null);
							Resources=pdfObject.getDictionary(PdfDictionary.Resources);

							byte[][] annotList = pdfObject.getKeyArray(PdfDictionary.Annots);

                            //System.out.println("Annots for print="+annotObject);
                            printAnnots = null;
							if (annotList != null) {
					            printAnnots = new PdfAnnots(currentPdfFile,null);
					            printAnnots.readAnnots(annotList);
				            }
                        }

						//<start-adobe>
						/** flush annotations */
						if (printHotspots != null && !isCustomPrinting)
							printHotspots.flushAnnotationsDisplayed();
						//<end-adobe>

						/** decode page only on first pass */
						if ((lastPrintedPage != page)) {

							currentPrintDecoder = new PdfStreamDecoder(true);
							currentPrintDecoder.setExternalImageRender(customImageHandler);
							currentPrintDecoder.setTextPrint(textPrint);

							currentPrintDecoder.optimiseDisplayForPrinting();
							currentPrintDecoder.setStore(objectPrintStoreRef);

							if (!isCustomPrinting) {
								/** get information for the page */
								
								try {
									currentPrintDecoder.init(true, renderPage, renderMode, 0, pageData, page, null, currentPdfFile);
								} catch (PdfException ee) {
									throw new PdfFontException(ee.getMessage());
								}
								
								if (globalResources != null)
									currentPrintDecoder.readResources(globalResources,true);

								/**read the resources for the page*/
								if (Resources != null)
									currentPrintDecoder.readResources(Resources,true);

								
							}
						}

						/**
						 * ready to actually print here so setup transformations
						 */


						// setup transformations
						AffineTransform printScaling = new AffineTransform();

						/**
						 * copy of crop values for clipping
						 */
						int clipW, clipH, clipX, clipY, crx, crw, cry, crh, mediaW, mediaH, mediaX, mediaY, rotation;

						/**
						 * page sizes for PDF
						 */

						/** get media box - ie page size*/
						mediaW = pageData.getMediaBoxWidth(page);
						mediaH = pageData.getMediaBoxHeight(page);
						mediaX = pageData.getMediaBoxX(page);
						mediaY = pageData.getMediaBoxY(page);

						//System.out.println("Media box at : " + mediaX +","+mediaY + " size " + mediaW + " " + mediaH );

						/** get crop box and rotation*/
						crx = clipX = pageData.getCropBoxX(page);
						cry = clipY = pageData.getCropBoxY(page);
						crw = clipW = pageData.getCropBoxWidth(page) + 1;
						crh = clipH = pageData.getCropBoxHeight(page) + 1;

						//System.out.println("Crop box at : " + clipX +","+clipY + " size " + clipW + " " + clipH );

						rotation = pageData.getRotation(currentPrintPage);




						if (usePDFPaperSize) {
							createCustomPaper(pf, clipW, clipH);
						}


						/**
						 * get imageble area - ie box we print into PAPER SIZE
						 */
						int iX = (int) pf.getImageableX();
						int iY = (int) pf.getImageableY();
						int iW = (int) pf.getImageableWidth() - 1;
						int iH = (int) pf.getImageableHeight() - 1;


						//System.out.println("image=" + iX + " " + iY + " " + iW + " " + iH);
						//shows if page rotated
						boolean needsTurning = (((iW > iH) && (crw < crh)) || ((iW < iH) && (crw > crh)));
						//System.out.println("needs Turning: " + needsTurning + " Rotation: " + rotation + " PageNumber: " + pageNumber + " CurrentPrintPage: " + currentPrintPage);
						// quick fix for landscape printing. Tested and works, if any error
						// should arise uncoment code below.


						// During printing of the current view we don't want it rotaded and centered in a couple of cases.

						docIsLandscaped = false;
                        if((this.displayRotation == 0 && needsTurning == true) || (crw>=crh))
                        	if(printOnlyVisible){
                        		docIsLandscaped = true;
                        	}

						if (!isPrintAutoRotateAndCenter && !this.usePDFPaperSize) {

							if (rotation == 90 || rotation == 270)
								needsTurning = !needsTurning;
							else
								needsTurning = false;
						}


						// test #1
						if(docIsLandscaped && isPrintAutoRotateAndCenter){
							needsTurning = false;
						}



						if (needsTurning) {
							/** get media box - ie page size*/
							mediaW = pageData.getMediaBoxHeight(page);
							mediaH = pageData.getMediaBoxWidth(page);
							mediaX = pageData.getMediaBoxY(page);
							mediaY = pageData.getMediaBoxX(page);

							/*** get crop box and rotation*/
							crx = clipX = pageData.getCropBoxY(page);
							cry = clipY = pageData.getCropBoxX(page);
							crw = clipW = pageData.getCropBoxHeight(page) + 1;
							crh = clipH = pageData.getCropBoxWidth(page) + 1;

						}

						//USEFUL DEBUG CODE!!!!!
						//showImageable = true;

						// put border on printable area if requrested
						if (showImageable) {

							Rectangle printableArea = new Rectangle(iX, iY, iW, iH);

							//System.out.println("image=" + iX + ' ' + iY + ' ' + iW + ' ' + iH);
							Graphics2D printableG2 = (Graphics2D) graphics;

							printableG2.setColor(Color.red);
							printableG2.fill(printableArea);
							printableG2.setColor(Color.white);
							for (int xx = 0; xx < 1000; xx = xx + 50) {
								printableG2.draw(new Line2D.Float(xx, 0, xx, 1000));
								printableG2.draw(new Line2D.Float(0, xx, 1000, xx));
							}

							printableG2.draw(printableArea);
							printableG2.draw(new Line2D.Float(iX, iY, iX + iW, iY + iH));
							printableG2.draw(new Line2D.Float(iX, iY + iH, iX + iW, iY));
						}

						double pScale = 1.0;

						/**
						 * size of the page we are printing
						 */
						int print_x_size = crw, print_y_size = crh;

						//old scaling code (should not be used)
						if (usePageScaling) {
							/** avoid oversize scaling shrinking page */
							print_x_size = (int) ((crw) * scaling);
							print_y_size = (int) ((crh) * scaling);

							if (((print_x_size > iW) | (print_y_size > iH))) {
								print_x_size = crw;
								print_y_size = crh;
								scaling = 1;
							}
						}

						/**
						 * workout scaling factor and use the smaller scale
						 * factor
						 */

						double pageScaleX = (double) iW / (print_x_size);
						double pageScaleY = (double) iH / (print_y_size);
						double newScaling;
						boolean scaledOnX = true;

						if (pageScaleX < pageScaleY)
							newScaling = pageScaleX;
						else {
							scaledOnX = false;
							newScaling = pageScaleY;
						}

						boolean hasScaling = false;


						//assume usePageScaling is false as legacy function
						if (usePageScaling) {

							pScale = newScaling;

							//old scaling code (should not beused)
							if (((print_x_size > iW) | (print_y_size > iH))) {

								/** adjust settings to fit page */
								print_x_size = (int) (print_x_size * pScale);
								print_y_size = (int) (print_y_size * pScale);
							}

						} else { // new scaling code

							switch (pageScalingMode) {

							case PrinterOptions.PAGE_SCALING_FIT_TO_PRINTER_MARGINS:
								pScale = newScaling;
								hasScaling = true;
								break;

								/** adjust settings to fit page */
							case PrinterOptions.PAGE_SCALING_REDUCE_TO_PRINTER_MARGINS:
								if (newScaling < 1)
									pScale = newScaling;

								hasScaling = true;
								break;

								// do nothing
							case PrinterOptions.PAGE_SCALING_NONE:
								break;

							}


							/** center image in middle of page
							 * if needs centering
							 * */
							//dx =  (float)(((iW) - (crw * pScale)) / 2);
							//dy =  (float)(((iH) - (crh * pScale)) / 2);

							dx =  -iX;
							dy =  -iY;

							/**correctly align*/
							if ((pageScalingMode == PrinterOptions.PAGE_SCALING_FIT_TO_PRINTER_MARGINS) || (pScale < 1)) {
								if (scaledOnX)
									dx = 0;
								else
									dy = 0;
							}


							//printScaling.translate((iW-(crw*pScale))/2, (iH-(crh*pScale))/2);

							if (centerOnScaling && (hasScaling || isPrintAutoRotateAndCenter)){  //!needsTurning &&
									if(docIsLandscaped && isPrintAutoRotateAndCenter){

									} else {
										printScaling.translate((iW-(crw*pScale))/2, (iH-(crh*pScale))/2);
									}
							}


						}

						/**
						 * turn around if needed
						 */
						if (needsTurning) {
							if ((this.usePDFPaperSize && iW<iH) && !oldSetup){
								printScaling.scale(-1, 1);
								printScaling.translate(-iX, 0);
							} else if (!isPrintAutoRotateAndCenter && (rotation == 0 || rotation == 180)) {
								printScaling.scale(1, -1);
								printScaling.translate(0, -iY);
							} else {
								printScaling.scale(-1, 1);
								printScaling.translate(-iX, 0);
							}

							printScaling.scale(pScale, pScale);

							printScaling.rotate(Math.PI / 2);

							if (hasScaling || isPrintAutoRotateAndCenter){
								printScaling.translate(-dy / pScale, (-dx / pScale));
							}

							printScaling.translate((cry), -(crx));

						} else {
							printScaling.translate(-(crx * pScale), (cry * pScale));
							printScaling.translate(iX, iY);

							/**
							 * set appropiate scaling
							 */
							if (pScale != 1) {
								if (this.usePageScaling) {
									printScaling.translate(print_x_size, print_y_size);
									printScaling.scale(1, -1);
									printScaling.translate(-print_x_size, 0);
								} else {
									printScaling.translate(print_x_size * pScale, print_y_size * pScale);
									printScaling.scale(1, -1);
									printScaling.translate(-print_x_size * pScale, 0);
								}
								printScaling.scale(pScale, pScale);

							} else {
								printScaling.translate(print_x_size, print_y_size);
								printScaling.scale(1, -1);
								printScaling.translate(-print_x_size, 0);
								printScaling.scale(scaling, scaling);// &&
							}
						}

						/** reassign of crop values for clipping */
						crx = clipX;
						cry = clipY;
						crw = clipW;
						crh = clipH;

						// turn off double buffering
						RepaintManager currentManager = RepaintManager.currentManager(this);
						currentManager.setDoubleBufferingEnabled(false);

						Graphics2D g2 = (Graphics2D) graphics;

						g2.setRenderingHints(ColorSpaces.hints);
						g2.transform(printScaling);

						/**
						 * add on transformation for just visible
                         */


						//<start-os>

						// usefull debug option, turned on shows the clip on the selected area
                        final boolean drawOutline = false;


                        SingleDisplay sd = new SingleDisplay(this);
                        Rectangle vr = sd.getDisplayedRectangle();

                        if(printOnlyVisible){

                        	// aligning the current view in the bottom left corner
                        	AffineTransform translation = new AffineTransform();
                        	// scaling
                        	AffineTransform enlarge = new AffineTransform();
                        	// aligning the page in the bottom left corner
                        	AffineTransform moveToCorner = new AffineTransform();
                        	// center cliped image on the screen
                        	AffineTransform centerOnScreen = new AffineTransform();

                        	// scaling for height (selected area vs the page size)
                        	double sCH = 0;
                        	// scaling for width (selected area vs the page size)
                        	double sCW = 0;
                        	// final sclaing value used to blow up the selected area
                        	double sCFinal = 0;


                        	boolean override = false;

                        	// gives the x,y width and the height of the selected area (takes rotation into account)
                        	double[] newVr = this.workoutParameters(this.displayRotation,this.scaling,vr,print_x_size, print_y_size);
                        	// coordinates of the clip rectangle
                        	clipVr = this.workoutClipping(this.displayRotation,this.scaling,vr,print_x_size, print_y_size);

                        	if(this.docIsLandscaped){

                            	/**
                            	 *  Landscape doc.
                            	 */

                        		// override autorotate and center if sacling is by height
                        		if(isPrintAutoRotateAndCenter){
                        			sCH = (iH-(2*insetH))/((newVr[2]*pScale));
		                        	sCW = (iW-(2*insetW))/((newVr[3]*pScale));

		                        	sCFinal = sCW;
		                        	override = true;

		                        	if(sCH<sCFinal){
		                        		sCFinal = sCH;
		                        		override = false;
		                        	}
                        		}



                        		if(override){
                        			isPrintAutoRotateAndCenter = false;
                        		}

                            	if(!isPrintAutoRotateAndCenter){

                            		// align to the bottom left corner
                            		moveToCorner.translate(((-((iW-((crw+((0)/pScale))*pScale))/2))/pScale), ((-((iH-((crh+((0)/pScale))*pScale))/2))/pScale));
                            		g2.transform(moveToCorner);

                            		//work out the scalings
                            		sCH = (iH-(2*insetH))/((newVr[3]*pScale));
		                        	sCW = (iW-(2*insetW))/((newVr[2]*pScale));

		                        	sCFinal = sCW;

		                        	if(sCH<sCFinal)
		                        		sCFinal = sCH;

		                        	// align the view in the bottom left corner
	                        		translation.translate(newVr[0]+(insetW/sCFinal), newVr[1]+(insetH/sCFinal));

		                        	enlarge.scale(sCFinal, sCFinal);
		                        	g2.transform(enlarge);
		                        	g2.transform(translation);

		                        	// now centre the view on paper
		                        	if(sCFinal==sCW)
		                        		centerOnScreen.translate(0, (((iH/2)-(((newVr[3]*pScale)*sCFinal)/2))/sCFinal)/pScale);
		                        	else
		                        		centerOnScreen.translate((((iW/2)-(((newVr[2]*pScale)*sCFinal)/2))/sCFinal)/pScale, 0);

		                        	g2.transform(centerOnScreen);

		                        	// and clip
		                        	g2.clip(clipVr);

                            	} else if (isPrintAutoRotateAndCenter) {

                            		// align to the bottom left corner
                            		moveToCorner.translate(0, ((-((iH-((crh+((0)/pScale))*pScale))))/pScale));
                            		g2.transform(moveToCorner);

                            		//work out the scalings
                            		sCH = (iH-(2*insetH))/((newVr[2]*pScale));
		                        	sCW = (iW-(2*insetW))/((newVr[3]*pScale));

		                        	sCFinal = sCW;

		                        	if(sCH<sCFinal)
		                        		sCFinal = sCH;

		                        	// rotate
		                        	translation.rotate(Math.PI/2);
		                        	translation.translate(0, -newVr[3]);
		                        	// align the view in the bottom left corner
		                        	translation.translate(newVr[0]+(insetW/sCFinal), newVr[1]-(insetH/sCFinal));

		                        	enlarge.scale(sCFinal, sCFinal);
		                        	g2.transform(enlarge);
		                        	g2.transform(translation);

		                        	// now centre the view on paper
		                        	if(sCFinal==sCW)
		                        		centerOnScreen.translate((((iH/2)-(((newVr[2]*pScale)*sCFinal)/2))/sCFinal)/pScale, 0);
		                        	else
		                        		centerOnScreen.translate(0, (-((iW/2)-(((newVr[3]*pScale)*sCFinal)/2))/sCFinal)/pScale);

		                        	g2.transform(centerOnScreen);

		                        	// and clip
		                        	g2.clip(clipVr);

                            	}

                            } else {

                            	/**
                            	 *  Portrait doc.
                            	 */
                            	/// detrmine if we should overrid the autorotate when SCH is used
                            	sCH = (iH - (2*insetW))/((newVr[2]*pScale));
	                        	sCW = (iW)/((newVr[3]*pScale));


	                        	sCFinal = sCW;

	                        	if(!(sCH<sCFinal))
	                        		override = true;

	                        	if(override){
                        			isPrintAutoRotateAndCenter = false;
                        		}

	                        	/// ==========================

                            	if(!isPrintAutoRotateAndCenter){

                            		// align to the bottom left corner
                            		moveToCorner.translate(-((Math.abs((iW-((crw+((0)/pScale))*pScale))/2))/pScale), -((Math.abs((iH-((crh+((0)/pScale))*pScale))/2))/pScale));
                            		g2.transform(moveToCorner);

                            		//work out the scalings
                            		sCH = (iH-(2*insetH))/((newVr[3]*pScale));
		                        	sCW = (iW-(2*insetW))/((newVr[2]*pScale));

		                        	sCFinal = sCW;

		                        	if(sCH<sCFinal)
		                        		sCFinal = sCH;

		                        	// align the view in the bottom left corner
	                        		translation.translate(newVr[0]+(insetW/sCFinal), newVr[1]+(insetH/sCFinal));

		                        	enlarge.scale(sCFinal, sCFinal);
		                        	g2.transform(enlarge);
		                        	g2.transform(translation);

		                        	// now centre the view on paper
		                        	if(sCFinal==sCW)
		                        		centerOnScreen.translate(0, ((iH/2)-((newVr[3]*sCFinal)/2))/sCFinal);
		                        	else
		                        		centerOnScreen.translate(((iW/2)-((newVr[2]*sCFinal)/2))/sCFinal, 0);

		                        	g2.transform(centerOnScreen);

		                        	// and clip
		                        	g2.clip(clipVr);

	                        	} else {

	                        		// align to the bottom left corner
                            		moveToCorner.translate(((-((iW-((crw+((0)/pScale))*pScale))/2))/pScale), ((-((iH-((crh+((0)/pScale))*pScale))/2))/pScale));
                            		g2.transform(moveToCorner);

                            		//work out the scalings
		                        	sCH = (iH-(2*insetH))/((newVr[2]*pScale));
		                        	sCW = (iW-(2*insetW))/((newVr[3]*pScale));

		                        	sCFinal = sCW;

		                        	if(sCH<sCFinal)
		                        		sCFinal = sCH;

		                        	// rotate
		                        	translation.rotate(Math.PI/2);
		                        	translation.translate(0, -newVr[3]);
		                        	// align the view in the bottom left corner
		                        	translation.translate(newVr[0]+(insetW/sCFinal), newVr[1]-(insetH/sCFinal));

		                        	enlarge.scale(sCFinal, sCFinal);
		                        	g2.transform(enlarge);
		                        	g2.transform(translation);

		                        	// now centre the view on paper
		                        	if(sCFinal==sCW)
		                        		centerOnScreen.translate((((iH/2)-((newVr[2]*sCFinal)/2))/sCFinal), 0);
		                        	else
		                        		centerOnScreen.translate(0, (-((iW/2)-((newVr[3]*sCFinal)/2))/sCFinal));

		                        	g2.transform(centerOnScreen);

		                        	// and clip
		                        	g2.clip(clipVr);
	                        	}
                            }
                        }


                        //<end-os>

                        /**
                         *  "Fix" for translucent image.
                         *  @Mariusz to show Mark on Tuesday.
                         */
                        if(useARGBFix && ColorSpaceConvertor.isUsingARGB){
                        	g2.translate(pf.getImageableX(), pf.getImageableY());
                        	Color alphaWhite = new Color(255,255,255,1);
                        	g2.setColor(alphaWhite);
                        	g2.drawLine(0,0,1,1); // this MUST be within the imageable area
                        }

						/**
						 *  End of fix.
						 */

						if (showImageable) {

							g2.setColor(Color.black);
							if (needsTurning) {
								g2.draw(new Rectangle(mediaY, mediaX, mediaH, mediaW));
								g2.drawLine(mediaY, mediaX, mediaH + mediaY, mediaW + mediaX);
								g2.drawLine(mediaY, mediaW + mediaX, mediaH + mediaY, mediaX);
							} else {
								g2.draw(new Rectangle(mediaX, mediaY, mediaW, mediaH));
								g2.drawLine(mediaX, mediaY, mediaW + mediaX, mediaH + mediaY);
								g2.drawLine(mediaX, mediaH + mediaY, mediaW + mediaX, mediaY);
							}


							g2.setColor(highlightColor);
							if (needsTurning) {
								g2.draw(new Rectangle(cry, crx, crh, crw));
								g2.drawLine(cry, crx, crh + cry, crw + crx);
								g2.drawLine(cry, crw + crx, crh + cry, crx);
							} else {
								g2.draw(new Rectangle(crx, cry, crw, crh));
								g2.drawLine(crx, cry, crw + crx, crh + cry);
								g2.drawLine(crx, crh + cry, crw + crx, cry);
							}
						} else if (needsTurning)
							g2.clip(new Rectangle(cry, crx, crh, crw));
						else
							g2.clip(new Rectangle(crx, cry, crw, crh));

						/**
						 * pass in values as needed for patterns
						 */
						currentPrintDecoder.getRenderer().setScalingValues(crx, crh + cry, (float) pScale);

						/** decode page only on first pass */
						if (lastPrintedPage != page) {

//							if (debugPrint)
//							System.out.println("About to decode stream");

							if (!isCustomPrinting) {
								try {
									currentPrintDecoder.decodePageContent(pdfObject, 0, 0, null, null);

									//store for printing (global first)
									Integer keyG = new Integer(-1);
									int[] typeG = (int[]) overlayTypeG.get(keyG);
									Color[] colorsG = (Color[]) overlayColorsG.get(keyG);
									Object[] objG = (Object[]) overlayObjG.get(keyG);

                                    //add to screen display
									currentPrintDecoder.getRenderer().drawAdditionalObjectsOverPage(typeG, colorsG, objG);

									//store for printing
									Integer key = new Integer(page);
								
									int[] type = (int[]) overlayType.get(key);
									Color[] colors = (Color[]) overlayColors.get(key);
									Object[] obj = (Object[]) overlayObj.get(key);

                                    //add to screen display
									currentPrintDecoder.getRenderer().drawAdditionalObjectsOverPage(type, colors, obj);
								} catch (PdfException e2) {
									e2.printStackTrace();
									throw new PrinterException(e2.getMessage());
								}
							} else {
							}

							lastPrintedPage = page;

//							if (debugPrint)
//							System.out.println("Decoded stream");
						}


//						if (debugPrint)
//						System.out.println("About to print stream");

						/**
						 * set any indent
						 */
						//alter to create guttering in duplex
						if (duplexGapOdd != 0 || duplexGapEven != 0) {

							Shape clip = g2.getClip();
							if (page % 2 != 1)
								g2.translate(duplexGapEven, 0);
							else
								g2.translate(duplexGapOdd, 0);

							if (clip != null)
								g2.setClip(clip);
						}

						/**
						 * print
						 */


						if (!isCustomPrinting)
							currentPrintDecoder.print(g2, null, showImageable,currentPrintPage);
						else if (printRender == null) {
							//<start-os>
							System.out.println("No data for page " + page);
							//<end-os>
							LogWriter.writeLog("No data for page " + page);
						} else{
							printRender.paint(g2, null, null, null, false,false);
						}

//						if (debugPrint)
//						System.out.println("Rendered");

						g2.setClip(null);

						//<start-adobe>
						// set up page hotspots
						if (!isCustomPrinting && printAnnots != null && printHotspots != null)
							printHotspots.setHotspots(printAnnots);
                        //<end-adobe>

						
						// <start-os>
						// add demo cross if needed
                        if (PdfDecoder.inDemo) {
							g2.setColor(Color.red);
							if (needsTurning) {
								g2.drawLine(cry, crx, crh + cry, crw + crx);
								g2.drawLine(crh + cry, crx, cry, crw + crx);
							} else {
								g2.drawLine(crx, cry, crw + crx, crh + cry);
								g2.drawLine(crx, crh + cry, crw + crx, cry);
							}

						} // <end-os>

						//<start-adobe>

						/** draw any annotations */
						if (printHotspots != null)
                            printHotspots.addHotspotsToDisplay(g2, userAnnotIcons, page);

						//<end-adobe>

//						if (debugPrint)
//						System.out.println("About to add annots/forms");

						if (formsAvailable) {
							/**
							 * draw acroform data onto Panel
							 */
							if (formRenderer != null) {

								/** make sure they exist */
                            	formRenderer.getCompData().setPageValues((float) scaling, 0);
                                formRenderer.createDisplayComponentsForPage(page);

								// always use 0 for printing and extraction on forms
                                formRenderer.getCompData().renderFormsOntoG2(g2, page, (float) scaling, 0, this.displayRotation);
                            }
						}


                        // fudge to get border round just the page
                        if ((useBorder) && (myBorder != null))
                            myBorder.paintBorder(this, g2, crx, cry, crw, crh);

						// turn on double buffering
						currentManager.setDoubleBufferingEnabled(true);

						if (!currentPrintDecoder.isPageSuccessful()) {
							operationSuccessful = false;
							pageErrorMessages = pageErrorMessages + currentPrintDecoder.getPageFailureMessage();
						}

						if (!operationSuccessful && debugPrint)
							System.out.println("Not Successful=" + operationSuccessful + '\n' + pageErrorMessages);
					}

				} catch (PdfFontException e) {
					operationSuccessful = false;
					pageErrorMessages = pageErrorMessages + "Missing substitute fonts\n";

					if (debugPrint)
						System.out.println("Exception e=" + e);
				} catch (PdfException e) {
                    e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
                }
            }

		} catch (Error err) {
			operationSuccessful = false;
			pageErrorMessages = pageErrorMessages + "Memory Error on printing\n";

			if (debugPrint)
				System.out.println("Error=" + err);

		}

		/** force printing to terminate on failure */
		if (!operationSuccessful)
			i = Printable.NO_SUCH_PAGE;

//		if (debugPrint)
//		System.out.println("return i=" + i);

		return i;

	}

	private Rectangle workoutClipping(int displayRotation, float scaling,Rectangle vr, int print_x_size, int print_y_size) {

		Rectangle cRect = null;


		double x = vr.getX();
		double y = vr.getY();
		double w = vr.getWidth();
		double h = vr.getHeight();

		/**
		 * g2.clipRect((int)((vr.x-insetW)/this.scaling),(int) ((print_y_size) - ((vr.y+vr.height-insetH)/this.scaling))
		 * ,(int) (vr.width/this.scaling)-1,(int) (vr.height/this.scaling));
		 */

		int newX = 0;
		int newY = 0;
		int newW = 0;
		int newH = 0;

		if(true || !docIsLandscaped){
			switch(displayRotation){
				case(0):
					newX = (int) ((vr.x-insetW)/this.scaling);
					newY = (int) ((print_y_size) - ((vr.y+vr.height-insetH)/this.scaling));
					newW = (int) (vr.width/this.scaling)-1;
					newH = (int) (vr.height/this.scaling);
					break;

				case(90):
					newX = (int) (((y-insetH)/this.scaling)) ;
					newY = (int) (((x-insetW)/this.scaling));
					newW = (int) (h/this.scaling);
					newH = (int) (w/this.scaling);
					break;

				case(180):
					newY = (int) ((y/this.scaling) - (insetH/this.scaling) );
					newX = (int) (print_x_size - ((x+w-insetW)/this.scaling));
					newW = (int) (w/this.scaling);
					newH = (int) (h/this.scaling);
					break;

				case(270):
					newX = (int) ((print_x_size - (y+h-insetH)/this.scaling) );
					newY = (int) ((print_y_size - (x+w-insetW)/this.scaling) );
					newW = (int) (h/this.scaling);
					newH = (int) (w/this.scaling);
					break;
			}
		} else {
			switch(displayRotation){
				case(0):
					newX = (int) ((vr.x-insetW)/this.scaling);
					newY = (int) ((print_x_size) - ((vr.y+vr.height-insetH)/this.scaling));
					newW = (int) (vr.width/this.scaling)-1;
					newH = (int) (vr.height/this.scaling);
					break;

				case(90):
					newX = (int) (((y-insetH)/this.scaling)) ;
					newY = (int) (((x-insetW)/this.scaling));
					newW = (int) (h/this.scaling);
					newH = (int) (w/this.scaling);
					break;

				case(180):
					newY = (int) ((y/this.scaling) - (insetH/this.scaling) );
					newX = (int) (print_y_size - ((x+w-insetW)/this.scaling));
					newW = (int) (w/this.scaling);
					newH = (int) (h/this.scaling);
					break;

				case(270):
					newX = (int) ((print_y_size - (y+h-insetH)/this.scaling) );
					newY = (int) ((print_x_size - (x+w-insetW)/this.scaling) );
					newW = (int) (h/this.scaling);
					newH = (int) (w/this.scaling);
					break;
			}
		}


		cRect = new Rectangle(newX,newY,newW,newH);

		return cRect;
	}

	private double[] workoutParameters(int rotation, float scale,Rectangle rect, int xSize, int ySize) {

		double[] nRect = new double[4];


		double x = rect.getX();
		double y = rect.getY();
		double w = rect.getWidth();
		double h = rect.getHeight();

		double newX = 0;
		double newY = 0;
		double newW = 0;
		double newH = 0;

			switch(rotation){
				case(0):
					newX =  -(((x-insetW)/this.scaling));
					newY =  -((ySize) - ((y+h-insetH)/this.scaling));
					newW =  (w/this.scaling);
					newH =  (h/this.scaling);
					break;

				case(90):
					newX =  -(((y-insetH)/this.scaling));
					newY =  -(((x-insetW)/this.scaling));
					newW =  (h/this.scaling);
					newH =  (w/this.scaling);
					break;

				case(180):
					newY =  -((y-insetH)/this.scaling);
					newX =  -(xSize - ((x+w-insetW)/this.scaling));
					newW =  (w/this.scaling);
					newH =  (h/this.scaling);
					break;

				case(270):
					newX =  -((xSize) - ((y+h-insetW)/this.scaling) );
					newY =  -((ySize) - ((x+w-insetH)/this.scaling) );
					newW =  (h/this.scaling);
					newH =  (w/this.scaling);
					break;
			}

		nRect[0] = newX;
		nRect[1] = newY;
		nRect[2] = newW;
		nRect[3] = newH;

		return nRect;
	}

	private void createCustomPaper(PageFormat pf, int clipW, int clipH) {
		Paper customPaper = new Paper();

		// Do not change this code, if you have check if it doesn't break
		// the barcode file (ABACUS) !
		// The barcode is to be printed horizontally on the page!

		if(this.pageCount==1){
				customPaper.setSize(clipW, clipH);
				customPaper.setImageableArea(0, 0, clipW, clipH);
		} else {

				// Due to the way printing (different sized pages in one go) works in Java
				// we work out the biggest for the printed selection and apply it to all
				// printed pages.
				int paperClipW = 0;
				int paperClipH = 0;

				for(int t=this.start;t<=this.end;t++){
					if(clipW <= (this.pageData.getMediaBoxWidth(t)+1) && clipH <= (this.pageData.getMediaBoxHeight(t)+1)){
						paperClipW = this.pageData.getMediaBoxWidth(t)+1;
						paperClipH = this.pageData.getMediaBoxHeight(t)+1;
					}
				}

				//System.err.println("-> (w/h) " + clipW + " " + clipH);
				customPaper.setSize(paperClipW, paperClipH);
				customPaper.setImageableArea(0, 0, clipW, clipH);

				//customPaper.setSize(595, 842);
				//customPaper.setImageableArea(0, 0, clipW, clipH);

		}

		//System.err.println("WIdth dims: " + customPaper.getWidth() + " " + customPaper.getHeight() + "\n");
		pf.setPaper(customPaper);


	}

	/**
	 * generate BufferedImage of a page in current file
	 */
	public BufferedImage getPageAsImage(int pageIndex) throws PdfException {
		return getPageAsImage(pageIndex, false);
	}

	/**
	 * generate BufferedImage of a page in current file
	 */
	public BufferedImage getPageAsTransparentImage(int pageIndex)
	throws PdfException {
		return getPageAsImage(pageIndex, true);
	}
	
	private BufferedImage getPageAsImage(int pageIndex,
			boolean imageIsTransparent) throws PdfException {
		
		BufferedImage image = null;
		
		String upScale = System.getProperty("org.jpedal.upscale");
		
		if(upScale!=null){

            isOptimisedImage=true;
		
			try{
				PdfDecoder.GLOBAL_IMAGE_UPSCALE = Integer.parseInt(upScale);
			}catch (NumberFormatException nfe){
			}
		}
		
		
		if(PdfDecoder.extAtBestQuality){
			PdfDecoder.useFullSizeImage = true;
			PdfDecoder.multiplyer = PdfDecoder.GLOBAL_IMAGE_UPSCALE;
			
			image = getPageAsImageSub(pageIndex, imageIsTransparent);
			if(PdfDecoder.samplingUsed!=-1){
				PdfDecoder.multiplyer = PdfDecoder.samplingUsed;
				image = getPageAsImageSub(pageIndex, imageIsTransparent);
				PdfDecoder.samplingUsed = -1;
				PdfDecoder.multiplyer = 1;
			}
			
			//@mariusz What does this code do????
			//It does not return anything.
			PdfDecoder.useFullSizeImage = false;
			getPageAsImageSub(pageIndex, imageIsTransparent);
			
		}else{
			PdfDecoder.multiplyer = PdfDecoder.GLOBAL_IMAGE_UPSCALE;
			image = getPageAsImageSub(pageIndex, imageIsTransparent);
		}

        isOptimisedImage=false;
		
		return image;
	}
	

	/**
	 * generate BufferedImage of a page in current file
	 */
	private BufferedImage getPageAsImageSub(int pageIndex,
			boolean imageIsTransparent) throws PdfException {

		BufferedImage image = null;

		// make sure in range
		if ((pageIndex > pageCount) | (pageIndex < 1)) {
			LogWriter.writeLog("Page " + pageIndex + " not in range");
		} else {

			/**
			 * setup for decoding page
			 */
			PdfAnnots printAnnots = null;
			String printAnnotObject = null;

			/** get pdf object id for page to decode */
			String currentPageOffset = (String) pagesReferences.get(new Integer(pageIndex));

			if (currentPageOffset != null) {

				if (currentPdfFile == null)
					throw new PdfException(
					"File not open - did you call closePdfFile() inside a loop and not reopen");

				/** read page or next pages */
				PdfObject pdfObject=new PdfPageObject(currentPageOffset);
                currentPdfFile.resetCache();
                currentPdfFile.readObject(pdfObject, currentPageOffset,false, null);
				PdfObject Resources=pdfObject.getDictionary(PdfDictionary.Resources);

				//<start-adobe>
				/** flush annotations */
				if (printHotspots != null)
					printHotspots.flushAnnotationsDisplayed();

				//<end-adobe>

                /**
				 * setup transformations and image
				 */
				
				if(!PdfDecoder.extAtBestQuality){
					multiplyer = pageData.getScalingValue();
				}
				
                AffineTransform imageScaling = setPageParametersForImage(1*multiplyer, pageIndex);
                
                int mediaW = pageData.getMediaBoxWidth(pageIndex);
				int mediaH = pageData.getMediaBoxHeight(pageIndex);
				int rotation = pageData.getRotation(pageIndex);

				int crw = pageData.getCropBoxWidth(pageIndex);
				int crh = pageData.getCropBoxHeight(pageIndex);
				int crx = pageData.getCropBoxX(pageIndex);
				int cry = pageData.getCropBoxY(pageIndex);
				
                boolean rotated = false;
				int w, h;
				if ((rotation == 90) || (rotation == 270)) {
					h = (int) (crw*multiplyer); // * scaling);
					w = (int) (crh*multiplyer); // * scaling);
					rotated = true;
				} else {
					w = (int) (crw*multiplyer); // * scaling);
					h = (int) (crh*multiplyer); // * scaling);
				}
				

				image = new BufferedImage(w, h, BufferedImage.TYPE_INT_ARGB);

				Graphics graphics = image.getGraphics();

				Graphics2D g2 = (Graphics2D) graphics;
				if (!imageIsTransparent) {
					g2.setColor(Color.white);
					g2.fillRect(0, 0, w, h);
				}

				/**
				 * decode the contents or fill in white
				 */
//				if (value != null) {

				ObjectStore localStore = new ObjectStore();
				PdfStreamDecoder currentImageDecoder = new PdfStreamDecoder();

				currentImageDecoder.setExternalImageRender(customImageHandler);
				currentImageDecoder.setName(filename);
				currentImageDecoder.setStore(localStore);

				if (imageIsTransparent) {

					DynamicVectorRenderer imageDisplay = new DynamicVectorRenderer(pageNumber,
							false, 5000, localStore);
					currentImageDecoder.init(true, renderPage, renderMode,
							0, pageData, pageIndex, imageDisplay,
							currentPdfFile);

				} else {
					currentImageDecoder.init(true, renderPage, renderMode,
							0, pageData, pageIndex, null, currentPdfFile);
				}
				
				if (globalResources != null)
					currentImageDecoder.readResources(globalResources,true);

				/**read the resources for the page*/
				if (Resources != null)
					currentImageDecoder.readResources(Resources,true);

				/**
				 * pass in values as needed for patterns
				 */
				currentImageDecoder.getRenderer().setScalingValues(crx, (crh*multiplyer) + cry, 1);

				g2.setRenderingHints(ColorSpaces.hints);
				g2.transform(imageScaling);

                if (rotated){

                    //System.out.println("rotated="+crx+" "+cry+" rotation="+rotation+" w="+w);

                    if(rotation==90){
					if(cry<0)
						g2.translate(-crx, -cry);
                    else
                        g2.translate(-crx, cry);
                    }else{ //270
                        if(cry<0)
					        g2.translate(-crx, mediaH-crh+cry);
						else
						    g2.translate(-crx,mediaH-crh-cry);
                    }
				}

				/** decode and print in 1 go */
				currentImageDecoder.setDirectRendering(g2);//(Graphics2D) graphics);
				if (pdfObject != null)
					currentImageDecoder.decodePageContent(pdfObject, 0, 0, null, null);// mediaX,mediaY);
                
				g2.setClip(null);


				//<start-adobe>

				// set up page hotspots
				if ((printAnnots != null) && (printHotspots != null))
					printHotspots.setHotspots(printAnnots);

				/** draw any annotations */
				if (printHotspots != null)
					printHotspots.addHotspotsToDisplay(g2, userAnnotIcons,
							pageIndex);

				//<end-adobe>

				if (formsAvailable) {
					/**
					 * draw acroform data onto Panel
					 */
                    if (formRenderer != null) {

						/** make sure they exist */
                    	formRenderer.getCompData().setPageValues((float) scaling, 0);

                        formRenderer.createDisplayComponentsForPage(pageIndex);

                        formRenderer.getCompData().renderFormsOntoG2(g2,pageIndex, scaling, 0, this.displayRotation);

                        formRenderer.getCompData().resetScaledLocation(oldScaling, displayRotation, 0);

                    }
				}

				// <start-os>
				// add demo cross if needed
                if (PdfDecoder.inDemo) {
					g2.setColor(Color.red);
					g2.drawLine(0, 0, mediaW, mediaH);
					g2.drawLine(0, mediaH, mediaW, 0);
				}
				// <end-os>

				localStore.flush();
			}
//			}
			
            if (!isOptimisedImage && !imageIsTransparent && image != null)
				image = ColorSpaceConvertor.convertToRGB(image);

		}

		return image;

	}

	/**
	 * provide method for outside class to clear store of objects once written
	 * out to reclaim memory
	 *
	 * @param reinit lag to show if image data flushed as well
	 */
	final public void flushObjectValues(boolean reinit) {

		if (pdfData != null)
			pdfData.flushTextList(reinit);

		if (currentAcroFormData != null)
			currentAcroFormData = new PdfFormData(inDemo);

		if ((pdfImages != null) && (reinit))
			pdfImages.clearImageData();

	}

	//<start-jfr>

	//<start-adobe>

	/**
	 * provide method for outside class to get data object
	 * containing images
	 *
	 * @return PdfImageData containing image metadata
	 */
	final public PdfImageData getPdfImageData() {
		return pdfImages;
	}

	/**
	 * provide method for outside class to get data object
	 * containing images.
	 *
	 * @return PdfImageData containing image metadata
	 */
	final public PdfImageData getPdfBackgroundImageData() {
		return pdfBackgroundImages;
	}

	//<end-adobe>

	/**
	 * provide method for outside class to get Annots data object<br>
	 * Please note: Structure of PdfPageData is not guaranteed to remain
	 * constant<br>
	 * Please contact IDRsolutions for advice (pass in null object if using
	 * externally)
	 *
	 * @return PdfAnnots object containing annotation data
	 */
	final public PdfAnnots getPdfAnnotsData(AcroRenderer formRenderer) {

        //<start-os>
		/**
         //<end-os>
         //not supported
         if(isXFA)
         return null;
         /**/

        if (annotsData == null) {
			try {

				if (annotList != null) {
					annotsData = new PdfAnnots(currentPdfFile,null);
					annotsData.readAnnots(annotList);
				}
			} catch (Exception e) {
				LogWriter.writeLog("[PDF] " + e + " with annotation");
			}
		}

		return annotsData;
	}

	/**
	 * read the form data from the pdf file<br>
	 *
	 * @param currentFormOffset -
	 *                          object reference to form
	 */
	private void readAcroForm(Object currentFormOffset) {

		String value, formObject = "";

        //flush list of kids
        formKids.clear();

        // table to hold children
		Map kidData = new HashMap();

		// System.out.println(currentFormOffset);

		if (!useForms)
			return;

		LogWriter.writeLog("Form data being read");

		/** start the queue */
		Vector queue = new Vector();

		/**
		 * read form object metadata
		 */
		Map values;
		if (currentFormOffset instanceof String)
			values = currentPdfFile.readObject(new PdfObject((String) currentFormOffset), (String) currentFormOffset,
					false, null);
		else
			values = (Map) currentFormOffset;

		/** flag if XFA */
		isXFA = (values.get("XFA") != null);

		//<start-os>
		/**
         //<end-os>
         if(isXFA)
         return ;
         /**/

		//<start-os>

		if (isXFA) {

			/** setup forms object */
			if (currentAcroFormData == null)
				currentAcroFormData = new PdfFormData(inDemo);

			Object vals = values.get("XFA");
			if (vals instanceof String) {


				String xfaValue = (String) vals;

				/** decide if ref to object or list of objects */
				if(xfaValue.endsWith(" R")){  //data as 1 XML stream

					//@chris 683 - alternative way to specify components using one XML stream

					//use to debug
					//Map obj=currentPdfFile.readObject((String) xfaValue,false,null);
					//System.out.println("new obj="+obj);

					//read the data which is an XML stream - can convert into XML object (examples in printCode or setXFAFormData)
					// and parse or turn into string
					byte[] stream=currentPdfFile.readStream(xfaValue,true);
					//String aa=new String(stream);
					//System.out.println(aa);

				}else if (xfaValue.indexOf('[') == -1) {// direct reference
					//StringBuffer xfaStream = currentPdfFile
					//		.convertStreamToXML(xfaValue);

					LogWriter.writeFormLog("{PdfDecoder.readAcroForm} Not implemented - XFA form stream in PdfDecoder as Object is direct eg. /XFA 23 0 R",FormStream.debugUnimplemented);

				} else { // ie [(xdp::xdp) 10 0 R (template) 11 0 R ....) as separate objects
					xfaValue = Strip.removeArrayDeleminators(xfaValue);
//					System.out.println(">>" + xfaValue + "<<");

					// extract the values
					StringTokenizer tokens = new StringTokenizer(xfaValue,
							"()", true);
					while (tokens.hasMoreTokens()) {
						String nextValue = tokens.nextToken();
						if (nextValue.equals("(")) {
							nextValue = tokens.nextToken(); // 'command'
							tokens.nextToken(); // lose the close bracket

							if (nextValue.equals("template")) {
								String dataRef = tokens.nextToken().trim();
								//commented out as we are doing it TWICE!
								//convertXMLtoValues(currentPdfFile.convertStreamToXML(dataRef));
								try {
									currentAcroFormData
									.setXFAFormData(
											PdfFormData.XFA_TEMPLATE,
											currentPdfFile.readStream(dataRef, true));
								} catch (PdfException e) {
									e.printStackTrace();
								}
							} else if (nextValue.equals("datasets")) {
								String dataRef = tokens.nextToken().trim();
								//convertXMLtoValues(currentPdfFile
								//		.convertStreamToXML(dataRef));

								try {
									currentAcroFormData
									.setXFAFormData(
											PdfFormData.XFA_DATASET,
											currentPdfFile.readStream(dataRef, true));
								} catch (PdfException e) {
									e.printStackTrace();
								}

							} else if (nextValue.equals("config")) {

								String dataRef = tokens.nextToken().trim();
								//convertXMLtoValues(currentPdfFile
								//		.convertStreamToXML(dataRef));
								try {
									currentAcroFormData
									.setXFAFormData(
											PdfFormData.XFA_CONFIG,
											currentPdfFile.readStream(dataRef, true));
								} catch (PdfException e) {
									e.printStackTrace();
								}
							}
						}
					}
//					if(PdfDecoder.showErrorMessages)
//					JOptionPane.showMessageDialog(null,
//					"XFA list of Objects- implemented in PdfFormData.setXFAFormData");
				}
			} else {
				System.out.println("Not implemented -XFA form function in PdfDecoder");

                if (PdfDecoder.showErrorMessages)
					JOptionPane.showMessageDialog(null,
					"Not implemented -XFA form function in PdfDecoder");
			}
		}

		//<end-os>

		/** read the fields */
		value = (String) values.get("Fields");
		if (value != null) {

			/** allow for values after fields */
			int p = value.indexOf(']');
			if (p != 0)
				value = value.substring(0, p + 1);

			/** strip the array braces */
			value = Strip.removeArrayDeleminators(value);

			boolean fieldsToProcess = true;

			/** put in queue */
			StringTokenizer initialValues = new StringTokenizer(value, "R");

			// allow for empty list

			// text fields
			Map fields = new HashMap();
			// setup a list of fields which are string values
			fields.put("T", "x");
			fields.put("TM", "x");
			fields.put("TU", "x");
			fields.put("CA", "x");
			fields.put("R", "x");
			fields.put("V", "x");
			fields.put("RC", "x");
			fields.put("DA", "x");
			fields.put("DV", "x");
			fields.put("JS", "x");

            /** allow for empty queue */
			if (initialValues.hasMoreTokens()) {

				formObject = stripNulls(initialValues.nextToken().trim() + " R");
				// first value
				while (initialValues.hasMoreTokens())
					queue.addElement(stripNulls(initialValues.nextToken().trim() + " R"));
			} else
				fieldsToProcess = false;

			StringTokenizer kidObjects = null;
			int kidCount;

			Map names = new HashMap();

			/** read each form object */
			while (fieldsToProcess) {

				/** read each form object */
				Map formData = currentPdfFile.readObject(new PdfObject(formObject), formObject, false, fields);

				/** if its a kid with 1 element, add in data from parent */
				Map parentData = (Map) kidData.get(formObject);

				Map parentObj;
				String parentName;

                //handle form names
				if (formData.containsKey("T")) {
                    if (formData.containsKey("Kids")) { //build list of names, scanning backwards recursively

                        String name = (String) currentPdfFile.resolveToMapOrString("T", formData.get("T"));
						String parentRef = (String) formData.get("Parent");

                        while (parentRef != null) {
							//read parent object
							parentObj = currentPdfFile.readObject(new PdfObject(parentRef), parentRef, false, fields);

                            //get any name set there and append if not null. Try again
							parentName = (String) currentPdfFile.resolveToMapOrString("T", parentObj.get("T"));
							if (parentName != null) {
								name = parentName + '.' + name;

								//carry on up tree
								parentRef = (String) parentObj.get("Parent");
							}
                        }

                        //we also need to reset so picked up elsewhere in this routine if
                        //this object then used as base in this class
                        formData.put("T",name);

                        //set fully qualified name
						names.put(formObject, name);
					} else {

						//String str = (String)currentPdfFile.resolveToMapOrString("T", formData.get("T"));

						//if(str.indexOf("Arbeitsverh�ltnis_4")!= -1){
						//System.out.println(str+"         ...>>>>>><<<<<<");
						//}

						Object parent = formData.get("Parent");
						if (parent == null) {
							formData.put("T", currentPdfFile.resolveToMapOrString("T", formData.get("T")));
						} else {
							formData.put("T", names.get(parent) + "." + currentPdfFile.resolveToMapOrString("T", formData.get("T")));
						}
					}

					//add in external data
					if (fdfData != null) {
						String name = (String) formData.get("T");
						String valueFromFDF = (String) fdfData.get(name);
						//System.out.println(name+" "+valueFromFDF+" "+fdfData+" "+formData);

						if (valueFromFDF != null)
							formData.put("V", valueFromFDF);
					}
				}

				if (parentData != null) {
					Iterator i = parentData.keySet().iterator();

					while (i.hasNext()) {
						Object key = i.next();
						Object kidValue = parentData.get(key);

						if (!formData.containsKey(key))
							formData.put(key, kidValue);

					}
				}

				//check for if we should flatten the kids if any exist
				boolean flatten = true;
				String fieldType = (String) (formData.get("FT"));
				if (fieldType != null && fieldType.indexOf("Btn") != -1) {
					String flag = (String)formData.get("Ff");
					if(flag==null){
						flatten = false;
					}else {
						int flagValue = Integer.parseInt(flag);

						//if not a pushbutton, then do not flatten, ie its a checkbox, or radio button group
						if (!((flagValue & FormStream.PUSHBUTTON) == FormStream.PUSHBUTTON)) {
							flatten = false;
                        }
					}
				}

				// System.out.println(formObject+" "+formData);
				String kids = (String) formData.get("Kids");
                if (kids != null) {
					String initialPageList = kids;
					if (initialPageList.startsWith("["))
						// handle any square brackets (ie arrays)
						initialPageList = Strip.removeArrayDeleminators(initialPageList).trim();

					// put kids in the queue
					kidObjects = new StringTokenizer(initialPageList, "R");
					/**
					 * allow for kids being used as a way to separate out
					 * distinct objects (ie tree structure) rather than kids as
					 * part of composite object (ie buttons in group)
					 */
					if (flatten)
						kidCount = 1;
					else
						kidCount = kidObjects.countTokens();
				}else {
					kidCount = 0;
				}

				String type = (String) formData.get("Type");
				if (kidCount > 1
						|| (type != null && type.equals("/Annot"))) {

					/** setup forms object */
					if (currentAcroFormData == null)
						currentAcroFormData = new PdfFormData(inDemo);

					if (kidCount == 0)
						kidCount = 1;
					currentAcroFormData.incrementCount(kidCount);

					if (flattenDebug) {//false

						/**
						 * convert any indirect values into actual data and put
						 * in array
						 */
						Map cleanedFormData = new HashMap();
						currentPdfFile.flattenValuesInObject(true, true,
								formData, cleanedFormData, fields, pageLookup,
								formObject);

						formData = cleanedFormData;

					} else { // setup page
						// removed to fix abacus file as need original
						// PageNumber to divide
						if (pageCount < 2) {
							formData.put("PageNumber", "1");
						}

						// add page
						if (formData.containsKey("P")) {
							try {
								Object rawValue = formData.get("P");

								if (rawValue != null && pageLookup != null && rawValue instanceof String) {
									int page = pageLookup.convertObjectToPageNumber((String) rawValue);
									formData.put("PageNumber", String.valueOf(page));
									// currentForm.remove("P");
								}
							} catch (Exception e) {
							}

						}

						// flatten any kids (not Issie or Patrick)
						if (kidCount > 1) {

							String kidrefs = (String) formData.get("Kids");

							// put kids in the queue
							kidObjects = new StringTokenizer(Strip.removeArrayDeleminators(kidrefs), "R");

							Map formObjects = new HashMap();

							while (kidObjects.hasMoreTokens()) {

                                String next_value = kidObjects.nextToken().trim()+ " R";

                                //track kids in case on Annots as well
                                formKids.put(next_value,formObject);

                                Map kidValue = currentPdfFile.readObject(new PdfObject(next_value), next_value, false, fields);

								kidValue.put("PageNumber", "1");

                                // add name as well
                                String correctedName=(String) formData.get("T");
                                if(correctedName!=null)
                                    kidValue.put("T",correctedName);


                                // add page
								if (kidValue.containsKey("P")) {
									try {
										Object rawValue = kidValue.get("P");

										if (rawValue != null
												&& pageLookup != null
												&& rawValue instanceof String) {
											int page = pageLookup
												.convertObjectToPageNumber((String) rawValue);
											kidValue.put("PageNumber", String.valueOf(page));

										}
									} catch (Exception e) {
									}
								}
                                formObjects.put(next_value, kidValue);
							}

							formData.put("Kids", formObjects);
						}

					}

                    formData.put("obj", formObject);
//					System.out.println("form="+formData);
					/** store the element in our form object */
					try {
						currentAcroFormData.addFormElement(formData);
					} catch (Exception e) {
						e.printStackTrace();

					}

				} else if (kidCount == 1) { // separate out indirect (which we
					// flatten) from genuine groups

					// its an indirect kid [1 0 R] so flatten
					while (kidObjects.hasMoreTokens()) {
						String next_value = kidObjects.nextToken().trim()
						+ " R";
						queue.addElement(next_value);
						formData.remove("Kids");
						kidData.put(next_value, formData);
					}

				}

				// exit when all pages read
				if (queue.isEmpty())
					break;

				// get next page from queue
				formObject = (String) queue.firstElement();

				// and remove from our queue to avoid infinite loop
				queue.removeElement(formObject);

			}
		}
	}

	//remove any null at start of sequence (assumes must be at start before any values)
	private String stripNulls(String rawString) {

		int ptr=rawString.lastIndexOf("null");

		if(ptr==-1)
			return rawString;
		else
			return rawString.substring(ptr+4).trim();
	}

	/**
	 * set render mode to state what is displayed onscreen (ie
	 * RENDERTEXT,RENDERIMAGES) - only generally required if you do not wish to
	 * show all objects on screen (default is all). Add values together to
	 * combine settings.
	 */
	final public void setRenderMode(int mode) {

		renderMode = mode;

        extractionMode = mode;

	}

	/**
	 * set extraction mode telling JPedal what to extract -
	 * (TEXT,RAWIMAGES,FINALIMAGES - add together to combine) - See
	 * org.jpedal.examples for specific extraction examples
	 */
	final public void setExtractionMode(int mode) {

		extractionMode = mode;

	}

	/**
	 * allow user to alter certain values in software
	 * such as Colour,
	 */
	public static void modifyJPedalParameters(Map values) throws PdfException {

		//read values
		Iterator keys=values.keySet().iterator();

		while(keys.hasNext()){
			Object nextKey=keys.next();

			//check it is valid
			if(nextKey instanceof Integer){

				Integer key=(Integer) nextKey;
				Object rawValue=values.get(key);

                if(key.equals(JPedalSettings.TEXT_INVERTED_COLOUR)){
                    //set colour if valid

                    if(rawValue instanceof Color)
                        backgroundColor=(Color) rawValue;
                    else
                        throw new PdfException("JPedalSettings.TEXT_INVERTED_COLOUR expects a Color value");

                }else if(key.equals(JPedalSettings.TEXT_HIGHLIGHT_COLOUR)){
					//set colour if valid

					if(rawValue instanceof Color)
						highlightColor=(Color) rawValue;
					else
						throw new PdfException("JPedalSettings.TEXT_HIGHLIGHT_COLOUR expects a Color value");

				}else if(key.equals(JPedalSettings.TEXT_PRINT_NON_EMBEDDED_FONTS)){

					if(rawValue instanceof Boolean){

						Boolean value= (Boolean)rawValue;
						PdfStreamDecoder.useTextPrintingForNonEmbeddedFonts = value.booleanValue();
					}else
						throw new PdfException("JPedalSettings.TEXT_PRINT_NON_EMBEDDED_FONTS expects a Boolean value");

				}else if(key.equals(JPedalSettings.DISPLAY_INVISIBLE_TEXT)){

					if(rawValue instanceof Boolean){

						Boolean value= (Boolean)rawValue;
						PdfStreamDecoder.showInvisibleText = value.booleanValue();
					}else
						throw new PdfException("JPedalSettings.DISPLAY_INVISIBLE_TEXT expects a Boolean value");

				}else if(key.equals(JPedalSettings.CACHE_LARGE_FONTS)){

					if(rawValue instanceof Integer){

						Integer value= (Integer)rawValue;
						FontData.maxSizeAllowedInMemory = value.intValue();
					}else
						throw new PdfException("JPedalSettings.CACHE_LARGE_FONTS expects an Integer value");

				}else if(key.equals(JPedalSettings.IMAGE_UPSCALE)){
					
					int upScaleVal = 0;
					
					if(rawValue instanceof Integer){

						String upScale = System.getProperty("org.jpedal.upscale");
						
						PdfDecoder.GLOBAL_IMAGE_UPSCALE = ((Integer)rawValue).intValue();

						if(upScale != null){
							try{
								upScaleVal = Integer.parseInt(upScale);
	
							}catch (NumberFormatException nfe){
								System.out.println("Parameter of \"org.jpedal.upscale\" is not a valid number, the value form the map will be used instead");
								upScaleVal = 1;
							}
							
							if(upScaleVal!=PdfDecoder.GLOBAL_IMAGE_UPSCALE){
								PdfDecoder.GLOBAL_IMAGE_UPSCALE = ((Integer)rawValue).intValue();
								throw new PdfException("JPedalSettings.IMAGE_UPSCALE - second parameter of a different value has already been passed as JVM command");
							}else{
								PdfDecoder.GLOBAL_IMAGE_UPSCALE = upScaleVal;
							}
						}

						if(PdfDecoder.GLOBAL_IMAGE_UPSCALE<1){
							PdfDecoder.GLOBAL_IMAGE_UPSCALE = 1;
						}
						
						
					}else
						throw new PdfException("JPedalSettings.IMAGE_UPSCALE expects an Integer value");
					
				}else if(key.equals(JPedalSettings.IMAGE_HIRES)){
					
					if(rawValue instanceof Boolean){

						Boolean value= (Boolean)rawValue;
						PdfDecoder.hires = value.booleanValue();
					}else
						throw new PdfException("JPedalSettings.IMAGE_HIRES expects a Boolean value");	
				
				}else if(key.equals(JPedalSettings.EXTRACT_AT_BEST_QUALITY)){
					
					if(rawValue instanceof Boolean){

						Boolean value= (Boolean)rawValue;
						PdfDecoder.extAtBestQuality = value.booleanValue();

					}else
						throw new PdfException("JPedalSettings.EXTRACT_AT_BEST_QUALITY expects a Boolean value");	
					//expansion room here

				}else
					throw new PdfException("Unknown or unsupported key "+key);

			}else
				throw new PdfException("Unknown or unsupported key (not Integer) "+nextKey);

		}
	}


	/**
	 * General reset routine which should be called when new
	 * file opened if PdfDecoder is reused.
	 * @deprecated
	 */
	final public void markAllPagesAsUnread() {

	}

	/**
	 * flag which is set to true if current PDF is a form
	 */
	final public boolean isForm() {
		return isForm;
	}

	/**
	 * provide method for
	 * outside class to get data object containing all form data - Returns null
	 * in demo version
	 */
	final public PdfFormData getPdfFormData() {

		return currentAcroFormData;

	}

	/**
	 * read object and setup Annotations for multipage view
	 */
	protected Map readObjectForPage(PdfObject pdfObject, String currentPageOffset, int page, boolean redraw) {

		/** read page or next pages */
		Map values = currentPdfFile.readObject(pdfObject, currentPageOffset, false, null);

		/**
		 * draw acroform data onto Panel
		 */
        if (formsAvailable && renderPage && !stopDecoding) {

            if (formRenderer != null && currentAcroFormData != null && !stopDecoding) {
            	formRenderer.getCompData().setPageValues((float) scaling, displayRotation);
                formRenderer.createDisplayComponentsForPage(page);
			}

			//force redraw
			if (redraw) {
				lastFormPage = -1;
				lastEnd = -1;
				lastStart = -1;
			}

			//	this.validate();
		}

		return values;
	}


	/**
	 * method to return null or object giving access info fields and metadata.
	 */
	final public PdfFileInformation getFileInformationData() {

		if (currentPdfFile != null)
			return currentPdfFile.readPdfFileMetadata(XMLObject);
		else
			return null;

	}

	//<start-adobe>


	/**
	 * @deprecated
	 * 
	 * Please do not use. Use setPageParameters(scalingValue, pageNumber) instead;
	 */
	
	final public void setExtractionMode(int mode, int imageDpi, float scaling) {

		if (dpi % 72 != 0)
			LogWriter.writeLog("Dpi is not a factor of 72- this may cause problems");

		dpi = imageDpi;
		
		//if (scaling < .5)
		//	scaling = .5f;

		this.scaling = scaling;

        pageData.setScalingValue(scaling); //ensure aligned

        extractionMode = mode;

	}

	/**
	 * just extract annotations for a page - if you want to decode the page and
	 * extract the annotations use decodePage(int pageNumber) which does both.
	 * <p/>
	 * Now returns PdfAnnots object
	 */
	final public PdfAnnots decodePageForAnnotations(int i) {


        if(i==annotPage)
            return annotsData;

        /** reset general value */
        annotsData = null;

		/** check in range */
		if (i > pageCount) {

			LogWriter.writeLog("Page out of bounds");

		} else {

			/** get pdf object id for page to decode */
			String currentPageOffset = (String) pagesReferences.get(new Integer(i));

			/**
			 * decode the file if not already decoded, there is a valid object
			 * id and it is unencrypted
			 */
			if (currentPageOffset != null) {

				/**
				 * read the annotationations for the page 
				 */
				PdfObject pdfObject=new PdfPageObject(currentPageOffset);
				currentPdfFile.readObject(pdfObject, currentPageOffset, false, null);
				byte[][] annotList = pdfObject.getKeyArray(PdfDictionary.Annots);

				if (annotList != null) {
					annotsData = new PdfAnnots(currentPdfFile,null);
					annotsData.readAnnots(annotList);
				}

                annotPage=i;

            }
		}

		return annotsData;
	}
	//<end-adobe>

	/**
	 * get pdf as Image of any page scaling is size (100 = full size)
	 * Use getPageAsImage to create images
	 *
	 * @deprecated
	 */
	final public BufferedImage getPageAsThumbnail(int pageNumber, int h) {

		BufferedImage image;
		int mediaX, mediaY, mediaW, mediaH;

		/** the actual display object */

		DynamicVectorRenderer imageDisplay = new DynamicVectorRenderer(pageNumber, true,
				1000, this.objectStoreRef); //
		imageDisplay.setHiResImageForDisplayMode(useHiResImageForDisplay);
		// simageDisplay.setDirectRendering((Graphics2D) graphics);

		try {

			/** check in range */
			if (pageNumber > pageCount) {

				LogWriter.writeLog("Page " + pageNumber + " out of bounds");

			} else {

				/** resolve page size */
				mediaX = pageData.getMediaBoxX(pageNumber);
				mediaY = pageData.getMediaBoxY(pageNumber);
				mediaW = pageData.getMediaBoxWidth(pageNumber);
				mediaH = pageData.getMediaBoxHeight(pageNumber);

				/** get pdf object id for page to decode */
				String currentPageOffset = (String) pagesReferences.get(new Integer(pageNumber));

				/**
				 * decode the file if not already decoded, there is a valid
				 * object id and it is unencrypted
				 */
				if ((currentPageOffset != null)) {

					//@speed
					/** read page or next pages */
					PdfObject pdfObject=new PdfPageObject(currentPageOffset);
					currentPdfFile.readObject(pdfObject, currentPageOffset,false, null);
					PdfObject Resources=pdfObject.getDictionary(PdfDictionary.Resources);

					/** get information for the page */
					if (pdfObject != null) {

						PdfStreamDecoder imageDecoder = new PdfStreamDecoder(useHiResImageForDisplay);
						imageDecoder.setExternalImageRender(customImageHandler);
						imageDecoder.setName(filename);
						imageDecoder.setStore(objectStoreRef);
						
						imageDecoder.init(true, true, renderMode, 0, pageData,
								pageNumber, imageDisplay, currentPdfFile);

						if (globalResources != null)
							imageDecoder.readResources(globalResources,true);

						/**read the resources for the page*/
						if (Resources != null)
							imageDecoder.readResources(Resources,true);
						
						int rotation = pageData.getRotation(pageNumber);
						imageDisplay.init(mediaW, mediaH, rotation);
						imageDecoder.decodePageContent(pdfObject, mediaX,mediaY, null, null);

					}
				}
			}
		} catch (Exception e) {
			e.printStackTrace();

		}

		/**
		 * workout scaling and get image
		 */
		image = getImageFromRenderer(h, imageDisplay, pageNumber);

		return image;

	}

	//<start-adobe>
	/**
	 * set status bar to use when decoding a page - StatusBar provides a GUI
	 * object to display progress and messages.
	 */
	public void setStatusBarObject(StatusBar statusBar) {
		this.statusBar = statusBar;
	}

	/**
	 * wait for rendering to finiah
	 */
	public void waitForRenderingToFinish() {

		//wait to die
		while (isDecoding()) {
			// System.out.println("Waiting to die");
			try {
				Thread.sleep(100);
			} catch (InterruptedException e) {
				// should never be called
				e.printStackTrace();
			}
		}
	}
	//<end-adobe>

	/**
	 * ask JPedal if stopDecoding() request completed
	 */
	public boolean isDecoding() {

		boolean decodeStatus = true;

		if ((!isDecoding) && (!pages.isDecoding()) && ((current == null) || (current.exitedDecoding())))
			decodeStatus = false;
		//System.out.println(isDecoding+" "+current+" "+current);
		return decodeStatus;
	}

	/**
	 * ask JPedal to stop printing a page
	 */
	final public void stopPrinting() {
		stopPrinting = true;
	}

	/**
	 * ask JPedal to stop decoding a page - this is not part
	 * of api and not a
	 * recommend way to shutdown a thread
	 */
	public void stopDecoding() {

		if (stopDecoding)
			return;

		pages.stopGeneratingPage();
		stopDecoding = true;

		if (currentPdfFile != null)
			currentPdfFile.setInterruptRefReading(true);

		if (current != null)
			current.terminateDecoding();

		// wait to die
		while (isDecoding()) {
			// System.out.println("Waiting to die");
			try {
				Thread.sleep(100);
			} catch (InterruptedException e) {
				// should never be called
				e.printStackTrace();
			}
		}

		if (currentPdfFile != null)
			currentPdfFile.setInterruptRefReading(false);
		stopDecoding = false;

		//currentDisplay.flush();
		//screenNeedsRedrawing = true;

		if (renderPage && formsAvailable&& formRenderer != null)
            formRenderer.removeDisplayComponentsFromScreen();

			//invalidate();

	}

	//<start-adobe>

	//<end-jfr>

	/**
	 * gets DynamicVector Object - NOT PART OF API and subject to change (DO NOT USE)
	 */
	public DynamicVectorRenderer getDynamicRenderer() {
		return currentDisplay;
	}

    /**
	 * gets DynamicVector Object - NOT PART OF API and subject to change (DO NOT USE)
	 */
	public DynamicVectorRenderer getDynamicRenderer(boolean reset) {

        DynamicVectorRenderer latestVersion=currentDisplay;

        if(reset)
        currentDisplay=new DynamicVectorRenderer(0,objectStoreRef,false);

        return latestVersion;
	}

    //<start-jfr>
	/**
	 * extract marked content - not yet live
	 */
	final public void decodePageForMarkedContent(String ref, Object pageStream) throws Exception {

		//ref can page 1 (ie page number) or 12 0 R (ie ref)

		/** check in range */
		if (!stopDecoding) {

			int page;
			if (ref.indexOf(' ') != -1) {
				page = pageLookup.convertObjectToPageNumber(ref);
			} else {
				page = Integer.parseInt(ref);
			}
			String currentPageOffset = (String) pagesReferences.get(new Integer(page));
			
			/**
			 * decode the file if not already decoded, there is a valid
			 * object id and it is unencrypted
			 */
			if (currentPageOffset != null && currentPdfFile == null)
				throw new PdfException("File not open - did you call closePdfFile() inside a loop and not reopen");
			//get Page data in Map

			/** get pdf object id for page to decode */
			PdfObject pdfObject=new PdfPageObject(currentPageOffset);
			currentPdfFile.readObject(pdfObject, currentPageOffset,false, null);
			PdfObject Resources=pdfObject.getDictionary(PdfDictionary.Resources);

			/** read page or next pages */
			if (pdfObject != null) {

				PdfStreamDecoder current = new PdfStreamDecoder(useHiResImageForDisplay);

				current.setName(filename);
				current.setStore(objectStoreRef);
				current.includeImages();

				/** pass in statusBar */
				current.setStatusBar(statusBar);

				int mode = PdfDecoder.TEXT + PdfDecoder.RAWIMAGES + PdfDecoder.FINALIMAGES;
				if (!stopDecoding) {
					current.init(true, false, renderMode,
							mode, pageData, page,
							null, currentPdfFile);

					if (globalResources != null)
						current.readResources(globalResources,true);

					/**read the resources for the page*/
					if (Resources != null)
						current.readResources(Resources,true);

				}

				current.setMapForMarkedContent(pageStream);

				current.decodePageContent(pdfObject, 0, 0, null, null);


				//pdfImages = current.getImages();


			}
		}
	}

	/**
	 * used to decode multiple pages on views
	 */
	public final void decodeOtherPages(int pageCount) {
		pages.decodeOtherPages(pageNumber, pageCount);
	}
	//<end-adobe>

	/**
	 * decode a page, - <b>page</b> must be between 1 and
	 * <b>PdfDecoder.getPageCount()</b> - Will kill off if already running
	 */
	final public void decodePage(int page) throws Exception {


		lastPageDecoded = page;

		decodeStatus = "";

		
		if (this.displayView != Display.SINGLE_PAGE) {
			return;
		}

		
		/**
		 * shutdown if already decoding previous page
		 */
		stopDecoding();
		
		currentPdfFile.resetCache();


		if (isDecoding) {
			LogWriter.writeLog("[PDF]WARNING - this file is being decoded already");
			isDecoding = false; // can be switched off to instrcut method to
			// exit asap

		} else {


			try{

				isDecoding = true; // can be switched off to instrcut method to
				// exit asap

				cursorBoxOnScreen = null;

				if (renderPage && formsAvailable && formRenderer != null){
				    formRenderer.removeDisplayComponentsFromScreen();
                    lastFormPage=-1; //reset so will appear on reparse
                }

                    //invalidate();

				/** flush renderer */
				currentDisplay.flush();
				pages.refreshDisplay();

				/** check in range */
				if (page > pageCount || page < 1) {

					LogWriter.writeLog("Page out of bounds");

				} else if (!stopDecoding) {

					//<start-adobe>
					// <start-13>
					/**
					 * title changes to give user something to see under timer
					 * control
					 */
					Timer t = null;
					if (statusBar != null) {
						ActionListener listener = new ProgressListener();
						t = new Timer(500, listener);
						t.start(); // start it
					}
					// <end-13>

					//<end-adobe>

					this.pageNumber = page;
					String currentPageOffset = (String) pagesReferences.get(new Integer(page));
					
					/**
					 * decode the file if not already decoded, there is a valid
					 * object id and it is unencrypted
					 */
					if (currentPageOffset != null && currentPdfFile == null)
						throw new PdfException("File not open - did you call closePdfFile() inside a loop and not reopen");

					//get Page data in Map

					/** get pdf object id for page to decode */

					PdfObject pdfObject=new PdfPageObject(currentPageOffset);
					currentPdfFile.readObject(pdfObject, currentPageOffset,false, null);
					PdfObject Resources=pdfObject.getDictionary(PdfDictionary.Resources);

                    /** read page or next pages */
					if (pdfObject != null && !stopDecoding) {

						/**
						 * read the annotations reference for the page if
						 * showAnnotations is false
						 */
						if(!showAnnotations || page>100){

                            if(annotPage!=page){
                                annotPage=page;
                                annotList = pdfObject.getKeyArray(PdfDictionary.Annots);

                                annotsData = null;
                            }

                            if (renderPage && !stopDecoding) {
								pagesRead.put(new Integer(page), "x");
								annotsData = getPdfAnnotsData(formRenderer);
							}


                            // pass handle into renderer
							if (formsAvailable && showAnnotations && annotsData!=null)
									formRenderer.resetAnnotData(annotsData, insetW, insetH, pageData, currentPdfFile, formKids);

						}

						byte[][] pageContents= pdfObject.getKeyArray(PdfDictionary.Contents);


						//<start-adobe>
						/** flush annotations */
						if ((displayHotspots != null) && (!stopDecoding))
							displayHotspots.flushAnnotationsDisplayed();
						//<end-adobe>

						// if (!value.equals("null"))
						if (pageContents != null && !stopDecoding) {

							current = new PdfStreamDecoder(useHiResImageForDisplay);
							current.setExternalImageRender(customImageHandler);


							current.setName(filename);
							current.setStore(objectStoreRef);
							if (includeImages)
								current.includeImages();

							/** pass in statusBar */
							current.setStatusBar(statusBar);

							/** set hires mode or not for display */
							currentDisplay.setHiResImageForDisplayMode(useHiResImageForDisplay);


							if (!stopDecoding) {
								current.init(true, renderPage, renderMode,
										extractionMode, pageData, page,
										currentDisplay, currentPdfFile);
							}
							
							if(PdfStreamDecoder.debugRes)
								System.out.println("Called readResources from decodePage for Global");
							
							if (globalResources != null)
								current.readResources(globalResources,true);

							
							/**read the resources for the page*/
							if (Resources != null)
								current.readResources(Resources,true);
							
							/** pass in visual multithreaded status bar */
							if (!stopDecoding)
								current.setStatusBar(statusBar);

							int mediaW = pageData.getMediaBoxWidth(pageNumber);
							int mediaH = pageData.getMediaBoxHeight(pageNumber);
							//int mediaX = pageData.getMediaBoxX(pageNumber);
							//int mediaY = pageData.getMediaBoxY(pageNumber);
							int rotation = pageData.getRotation(pageNumber);
							currentDisplay.init(mediaW, mediaH, rotation);
							/** toke out -min's%% */

							if (g2 != null)
								current.setDirectRendering(g2);

							try {
								if (!stopDecoding)
									current.decodePageContent(pdfObject, 0, 0, null, null);// mediaX,mediaY);/**removed
								// min_x,min_y%%*/
							} catch (Error err) {
								
								decodeStatus = decodeStatus
								+ "Error in decoding page "
								+ err.toString();
							}

							if (!stopDecoding) {
								hasEmbeddedFonts = current.hasEmbeddedFonts();

								fontsInFile = PdfStreamDecoder.getFontsInFile();

								pdfData = current.getText();
								if (embedWidthData)
									pdfData.widthIsEmbedded();

								// store page width/height so we can translate 270
								// rotation co-ords
								pdfData.maxX = mediaW;
								pdfData.maxY = mediaH;

								pdfImages = current.getImages();

								//<start-adobe>
								/** get shape info */
								pageLines = current.getPageLines();
								//<end-adobe>
							}

                            //read flags
                            imagesProcessedFully = current.getPageDecodeStatus(DecodeStatus.ImagesProcessed);
                            hasNonEmbeddedCIDFonts= current.getPageDecodeStatus(DecodeStatus.NonEmbeddedCIDFonts);
                            nonEmbeddedCIDFonts= current.getPageDecodeStatusReport(DecodeStatus.NonEmbeddedCIDFonts);

                            current = null;

						}
					}

					/** turn off status bar update */
					// <start-adobe>
					if (t != null) {

						t.stop();
						statusBar.setProgress(100);

					}
					// <end-adobe>

					isDecoding = false;
					//pages.refreshDisplay();

					/**
					 * handle acroform data to display
					 */
                    if (formsAvailable && renderPage && !stopDecoding) {

                        if (formRenderer != null && !formRenderer.ignoreForms()) {

                            formRenderer.getCompData().setPageValues(scaling, displayRotation);

                            formRenderer.createDisplayComponentsForPage(page);

                        }

						//<start-os>
						//call actions on open
						if (javascript != null && this.useJavascript && formRenderer!=null) {

							//call handlers
							formRenderer.getActionHandler().PO(pageNumber);
							formRenderer.getActionHandler().O(pageNumber);
						}
						//<end-os>

						this.validate();
					}

					// set up page hotspots
					if ((annotsData != null) && (displayHotspots != null)
							&& (!stopDecoding))
						displayHotspots.setHotspots(annotsData);

				}

				current = null;

                //tell software page all done
                currentDisplay.flagDecodingFinished();



			} finally {
				isDecoding = false;
			}
		}
	}

	
	//<end-jfr>

	/**
     * store objects to use on a print
     * @param page
     * @param type
     * @param colors
     * @param obj
     * @throws PdfException
     */
    public void printAdditionalObjectsOverPage(int page, int[] type, Color[] colors, Object[] obj) throws PdfException {

    
		Integer key = new Integer(page);

		if (obj == null) { //flush page

            overlayType.remove(key);
			overlayColors.remove(key);
			overlayObj.remove(key);

		} else { //store for printing and add if items already there



			int[] oldType = (int[]) overlayType.get(key);
			if (oldType == null){
				overlayType.put(key, type);

            }else { //merge items

                int oldLength = oldType.length;
				int newLength = type.length;
				int[] combined = new int[oldLength + newLength];

				System.arraycopy(oldType, 0, combined, 0, oldLength);

				System.arraycopy(type, 0, combined, oldLength, newLength);

				overlayType.put(key, combined);
			}


			Color[] oldCol = (Color[]) overlayColors.get(key);
			if (oldCol == null)
				overlayColors.put(key, colors);
			else { //merge items

				int oldLength = oldCol.length;
				int newLength = colors.length;
				Color[] combined = new Color[oldLength + newLength];

				System.arraycopy(oldCol, 0, combined, 0, oldLength);

				System.arraycopy(colors, 0, combined, oldLength, newLength);

				overlayColors.put(key, combined);
			}



			Object[] oldObj = (Object[]) overlayObj.get(key);



			if (oldType == null)
				overlayObj.put(key, obj);
				else { //merge items

					int oldLength = oldObj.length;
					int newLength = obj.length;
					Object[] combined = new Object[oldLength + newLength];

					System.arraycopy(oldObj, 0, combined, 0, oldLength);

					System.arraycopy(obj, 0, combined, oldLength, newLength);

					overlayObj.put(key, combined);
				}
			}

	}

    /**
     * store objects to use on a print
     * @param type
     * @param colors
     * @param obj
     * @throws PdfException
     */
    public void printAdditionalObjectsOverAllPages(int[] type, Color[] colors, Object[] obj) throws PdfException {


		Integer key = new Integer(-1);

		if (obj == null) { //flush page

            overlayTypeG.remove(key);
			overlayColorsG.remove(key);
			overlayObjG.remove(key);

		} else { //store for printing and add if items already there

			int[] oldType = (int[]) overlayTypeG.get(key);
			if (oldType == null){
				overlayTypeG.put(key, type);

            }else { //merge items

                int oldLength = oldType.length;
				int newLength = type.length;
				int[] combined = new int[oldLength + newLength];

				System.arraycopy(oldType, 0, combined, 0, oldLength);

				System.arraycopy(type, 0, combined, oldLength, newLength);

				overlayTypeG.put(key, combined);
			}


			Color[] oldCol = (Color[]) overlayColorsG.get(key);
			if (oldCol == null)
				overlayColorsG.put(key, colors);
			else { //merge items

				int oldLength = oldCol.length;
				int newLength = colors.length;
				Color[] combined = new Color[oldLength + newLength];

				System.arraycopy(oldCol, 0, combined, 0, oldLength);

				System.arraycopy(colors, 0, combined, oldLength, newLength);

				overlayColorsG.put(key, combined);
			}



			Object[] oldObj = (Object[]) overlayObjG.get(key);



			if (oldType == null)
				overlayObjG.put(key, obj);
				else { //merge items

					int oldLength = oldObj.length;
					int newLength = obj.length;
					Object[] combined = new Object[oldLength + newLength];

					System.arraycopy(oldObj, 0, combined, 0, oldLength);

					System.arraycopy(obj, 0, combined, oldLength, newLength);

					overlayObjG.put(key, combined);
				}
			}

	}



    /**
	 * allow user to add grapical content on top of page - for display ONLY
	 * Additional calls will overwrite current settings on page
	 * ONLY works in SINGLE VIEW displaymode
	 */
	public void drawAdditionalObjectsOverPage(int page, int[] type, Color[] colors, Object[] obj) throws PdfException {

        //add to screen display
        if (page == this.pageNumber)
            currentDisplay.drawAdditionalObjectsOverPage(type, colors, obj);
        
		//ensure redraw
		pages.refreshDisplay();
	}
	
	/**
	 * allow user to remove all additional grapical content from the page (only for display)
	 * ONLY works in SINGLE VIEW displaymode
	 */
	public void flushAdditionalObjectsOnPage(int page) throws PdfException {

        //add to screen display
        if (page == this.pageNumber){
                currentDisplay.flushAdditionalObjOnPage();
        }

		//ensure redraw
		pages.refreshDisplay();
	}

	/**
	 * uses hires images to create a higher quality display - downside is it is
	 * slower and uses more memory (default is false).- Does nothing in OS
	 * version
	 *
	 * @param value
	 */
	public void useHiResScreenDisplay(boolean value) {
		// <start-os>
		useHiResImageForDisplay = value;
		// <end-os>
	}

	//<start-adobe>
	//<start-jfr>
	/**
	 * decode a page as a background thread (use
	 * other background methods to access data).
	 */
	final public void decodePageInBackground(int i) throws Exception {

		if (isBackgroundDecoding) {
			LogWriter
			.writeLog("[PDF]WARNING - this file is being decoded already in background");
		} else {
			isBackgroundDecoding = true;

			/** check in range */
			if (i > pageCount) {

				LogWriter.writeLog("Page out of bounds");

			} else {

				/** get pdf object id for page to decode */
				String currentPageOffset = (String) pagesReferences
				.get(new Integer(i));

				/**
				 * decode the file if not already decoded, there is a valid
				 * object id and it is unencrypted
				 */
				if ((currentPageOffset != null)) {

					if (currentPdfFile == null)
						throw new PdfException(
						"File not open - did you call closePdfFile() inside a loop and not reopen");

					/** read page or next pages */
					PdfObject pdfObject=new PdfPageObject(currentPageOffset);
					currentPdfFile.readObject(pdfObject, currentPageOffset,false, null);
					PdfObject Resources=pdfObject.getDictionary(PdfDictionary.Resources);

					// if (!value.equals("null"))
					if (pdfObject != null) {

						PdfStreamDecoder backgroundDecoder = new PdfStreamDecoder();
						backgroundDecoder.setExternalImageRender(customImageHandler);
						backgroundDecoder.setName(filename);
						backgroundDecoder.setStore(backgroundObjectStoreRef);

						
						backgroundDecoder.init(true, false, 0, extractionMode,
								pageData, i, null, currentPdfFile);
						
						if (globalResources != null)
							backgroundDecoder.readResources(globalResources,true);

						/**read the resources for the page*/
						if (Resources != null)
							backgroundDecoder.readResources(Resources,true);


						backgroundDecoder.decodePageContent(pdfObject, 0, 0, null, null);
						/** removed min_x,min_y%% */

						pdfBackgroundData = backgroundDecoder.getText();
						if (embedWidthData)
							pdfBackgroundData.widthIsEmbedded();

						// store page width/height so we can translate 270
						// rotation co-ords
						int mediaW = pageData.getMediaBoxWidth(i);
						int mediaH = pageData.getMediaBoxHeight(i);
						//int mediaX = pageData.getMediaBoxX(i);
						//int mediaY = pageData.getMediaBoxY(i);

						pdfBackgroundData.maxX = mediaW;
						pdfBackgroundData.maxY = mediaH;

						pdfBackgroundImages = backgroundDecoder.getImages();

					}
				}

			}
			isBackgroundDecoding = false;
		}
	}
	//<end-adobe>

	/**
	 * get page count of current PDF file
	 */
	final public int getPageCount() {
		return pageCount;
	}

	/**
	 * return true if the current pdf file is encrypted <br>
	 * check <b>isFileViewable()</b>,<br>
	 * <br>
	 * if file is encrypted and not viewable - a user specified password is
	 * needed.
	 */
	final public boolean isEncrypted() {
		if (currentPdfFile != null)
			return currentPdfFile.isEncrypted();
		else
			return false;
	}

	/**
	 * show if encryption password has been supplied
	 */
	final public boolean isPasswordSupplied() {
		if (currentPdfFile != null)
			return currentPdfFile.isPasswordSupplied();
		else
			return false;
	}

	/**
	 * show if encrypted file can be viewed,<br>
	 * if false a password needs entering
	 */
	public boolean isFileViewable() {
		if (currentPdfFile != null)
			return currentPdfFile.isFileViewable();
		else
			return false;
	}

	/**
	 * show if content can be extracted
	 */
	public boolean isExtractionAllowed() {
		if (currentPdfFile != null)
			return currentPdfFile.isExtractionAllowed();
		else
			return false;
	}

	/**
	 * give user access to PDF flag value
	 * - if file not open or input not valid
	 * returns -1
	 * <p/>
	 * Possible values in PdfFLAGS
	 * <p/>
	 * ie PDFflags.USER_ACCESS_PERMISSIONS - return P value
	 * PDFflags.VALID_PASSWORD_SUPPLIED - tell if password supplied and if owner or user
	 */
	public int getPDFflag(Integer i) {
		if (currentPdfFile != null)
			return currentPdfFile.getPDFflag(i);
		else
			return -1;
	}

	/**
	 * used to retest access and see if entered password is valid,<br>
	 * If so file info read and isFileViewable will return true
	 */
	private void verifyAccess() {
		if (currentPdfFile != null) {
			try {
				openPdfFile();
			} catch (Exception e) {
				LogWriter.writeLog("Exception " + e + " opening file");
			}
		}
	}
	//<end-jfr>


	/**
	 * set the font used for default from Java fonts on system - Java fonts are
	 * case sensitive, but JPedal resolves this internally, so you could use
	 * Webdings, webdings or webDings for Java font Webdings - checks if it is a
	 * valid Java font (otherwise it will default to Lucida anyway)
	 */
	public final void setDefaultDisplayFont(String fontName)
	throws PdfFontException {

		boolean isFontInstalled = false;

		// get list of fonts and see if installed
		String[] fontList = GraphicsEnvironment.getLocalGraphicsEnvironment()
		.getAvailableFontFamilyNames();

		int count = fontList.length;

		for (int i = 0; i < count; i++) {
			if (fontList[i].toLowerCase().equals(fontName.toLowerCase())) {
				isFontInstalled = true;
				defaultFont = fontList[i];
				i = count;
			}
		}

		if (!isFontInstalled)
			throw new PdfFontException("Font " + fontName
					+ " is not available.");

	}

	//<start-jfr>
	/**
	 * set a password for encryption - software will resolve if user or owner
	 * password- calls verifyAccess() from 2.74 so no separate call needed
	 */
	final public void setEncryptionPassword(String password) throws PdfException {

		if (currentPdfFile == null)
			throw new PdfException("Must open PdfDecoder file first");

		currentPdfFile.setEncryptionPassword(password);
        
        verifyAccess();
	}

	/**
	 * routine to open a byte stream cntaining the PDF file and extract key info
	 * from pdf file so we can decode any pages. Does not actually decode the
	 * pages themselves.
	 */
	final public void openPdfArray(byte[] data) throws PdfException {

		LogWriter.writeMethod("{openPdfArray}", 0);

		globalResources=null;
		pagesReferences.clear();

		try {

			currentPdfFile = new PdfReader();

			/** get reader object to open the file */
			currentPdfFile.openPdfFile(data);

			openPdfFile();

			if (stopDecoding)
				closePdfFile();

			/** store file name for use elsewhere as part of ref key without .pdf */
			objectStoreRef.storeFileName("r" + System.currentTimeMillis());

		} catch (Exception e) {
			throw new PdfException("[PDF] OpenPdfArray generated exception "
					+ e.getMessage());
		}
	}

    /**
	 * routine to open PDF file and extract key info from pdf file so we can
	 * decode any pages. Does not actually decode the pages themselves. Also
	 * reads the form data. You must explicitly close any open files with
	 * closePdfFile() to Java will not release all the memory
	 */
	final public void openPdfFile(final String filename) throws PdfException {

        displayScaling = null;


		LogWriter.writeMethod("{openPdfFile " + filename + '}', 0);

		//System.out.println(filename);
		
		this.filename = filename;
		globalResources=null;
		pagesReferences.clear();

		/** store file name for use elsewhere as part of ref key without .pdf */
		objectStoreRef.storeFileName(filename);

		/**
		 * possible caching of code File testFile=new File(filename);
		 *
		 * int size=(int)testFile.length(); if(size<300*1024){ byte[]
		 * fileData=new byte[size]; // read the object try {
		 *
		 * FileInputStream fis=new FileInputStream(testFile);
		 *
		 * //get binary data fis.read( fileData ); } catch( Exception e ) {
		 * LogWriter.writeLog( "Exception " + e + " reading from file" ); }
		 *
		 * openPdfFile(fileData); }else
		 */

		currentPdfFile = new PdfReader();

		/** get reader object to open the file */
		if (!stopDecoding)
			currentPdfFile.openPdfFile(filename);

		if (!stopDecoding)
			openPdfFile();


		if (stopDecoding)
			closePdfFile();

    }

    /**
	 * routine to open PDF file and extract key info from pdf file so we can
	 * decode any pages which also sets password.
     * Does not actually decode the pages themselves. Also
	 * reads the form data. You must explicitly close any open files with
	 * closePdfFile() or Java will not release all the memory
	 */
	final public void openPdfFile(final String filename,String password) throws PdfException {

        displayScaling = null;


		LogWriter.writeMethod("{openPdfFile " + filename + '}', 0);

		this.filename = filename;
		globalResources=null;
		pagesReferences.clear();

		/** store file name for use elsewhere as part of ref key without .pdf */
		objectStoreRef.storeFileName(filename);

		/**
		 * possible caching of code File testFile=new File(filename);
		 *
		 * int size=(int)testFile.length(); if(size<300*1024){ byte[]
		 * fileData=new byte[size]; // read the object try {
		 *
		 * FileInputStream fis=new FileInputStream(testFile);
		 *
		 * //get binary data fis.read( fileData ); } catch( Exception e ) {
		 * LogWriter.writeLog( "Exception " + e + " reading from file" ); }
		 *
		 * openPdfFile(fileData); }else
		 */

		currentPdfFile = new PdfReader(password);

		/** get reader object to open the file */
		if (!stopDecoding)
			currentPdfFile.openPdfFile(filename);

		if (!stopDecoding)
			openPdfFile();


		if (stopDecoding)
			closePdfFile();

    }

	//Set the main screen coords
	Point coords = null;
	public void setDownloadWindomPosition(int x, int y){
		coords = new Point(x,y);
	}
	
    //@kieran - we use a ByteArrayOutputStream which blows away if large files.
    //Can we alter it so it saves data to a Temp file (use File.createTemp)
    //Pass name of file into open method which takes String)
    //We need file to be global and then check in close file and if it exists, delete
    //Also use File.deleteOnExit when you create in this method
    //and then opens that file.
    //
    /**
	 * routine to open PDF file via URL and extract key info from pdf file so we
	 * can decode any pages - Does not actually decode the pages themselves -
	 * Also reads the form data - Based on an idea by Peter Jacobsen
	 * <p/>
	 * You must explicitly close any open files with closePdfFile() to Java will
	 * not release all the memory
	 */
	final public void openPdfFileFromURL(String pdfUrl) throws PdfException {

		LogWriter.writeMethod("{openPdfFileFromURL " + pdfUrl + '}', 0);

		displayScaling = null;
		globalResources=null;
		pagesReferences.clear();

		if(download==null){
			
			download = new JFrame();
			p = new JPanel(new GridBagLayout());
			pb = new JProgressBar();
			downloadMessage = new JLabel();
			downloadFile = new JLabel();
			turnOff = new JLabel();
			//saveLocal = new JButton("Save Local");
			
		}
		
		URL url;
		InputStream is;

		try {
			url = new URL(pdfUrl);
			is = url.openStream();
			final String filename = url.getPath().substring(url.getPath().lastIndexOf('/')+1);

			tempURLFile = File.createTempFile(filename.substring(0, filename.lastIndexOf('.')), filename.substring(filename.lastIndexOf('.')));

			FileOutputStream fos = new FileOutputStream(tempURLFile);

            //<start-adobe><start-thin>
			if(useDownloadWindow){
				if(!downloadCreated)
					createDownloadWindow();
			
			download.setLocation((coords.x-(download.getWidth()/2)), (coords.y-(download.getHeight()/2)));
			download.setVisible(true);
			}
            //<end-thin><end-adobe>
			
            int fileLength = url.openConnection().getContentLength();
            
			pb.setMinimum(0);
			pb.setMaximum(fileLength);
			//saveLocal.setEnabled(false);
			
			String message = Messages.getMessage("PageLayoutViewMenu.DownloadWindowMessage");
			message = message.replaceAll("FILENAME", filename);
			downloadFile.setText(message);
			
			Font f = turnOff.getFont();
			turnOff.setFont(new Font(f.getName(), f.getStyle(),  8));
			turnOff.setAlignmentY(JLabel.RIGHT_ALIGNMENT);
			turnOff.setText(Messages.getMessage("PageLayoutViewMenu.DownloadWindowTurnOff"));
			
			//download.setVisible(true);
			// Download buffer
			byte[] buffer = new byte[4096];
			// Download the PDF document
			int read;
			int current = 0;

			String rate = "kb"; //mb
			int mod = 1000; //1000000

			if(fileLength>1000000){
				rate = "mb";
				mod = 1000000;
			}

			String progress = Messages.getMessage("PageLayoutViewMenu.DownloadWindowProgress");
			if(fileLength<1000000)
				progress = progress.replaceAll("DVALUE", (fileLength/mod)+" "+rate);
			else
				progress = progress.replaceAll("DVALUE", (fileLength/mod)+"."+((fileLength%mod)/10000)+" "+rate);
			
			while ((read = is.read(buffer)) != -1) {
				current = current + read;
				downloadCount = downloadCount+read;
				
				if(fileLength<1000000)
					downloadMessage.setText(progress.replaceAll("DSOME", (current/mod)+""+rate));
				else
					downloadMessage.setText(progress.replaceAll("DSOME", (current/mod)+"."+((current%mod)/10000)+" "+rate));
				
				pb.setValue(current);
				
				download.repaint();
				fos.write(buffer, 0, read);
			}
			fos.flush();
			// Close streams
			is.close();
			fos.close();

			//File completed download, show the save button
			downloadMessage.setText("Download of "+filename+" is complete.");
			//saveLocal.setEnabled(true);

		} catch (IOException e) {
			LogWriter.writeLog("[PDF] Exception " + e + " opening URL "
					+ pdfUrl);
			e.printStackTrace();
		}
		
		download.setVisible(false);
		
		currentPdfFile = new PdfReader();

		/** get reader object to open the file */
		openPdfFile(tempURLFile.getAbsolutePath());

		/** store file name for use elsewhere as part of ref key without .pdf */
		objectStoreRef.storeFileName(tempURLFile.getName().substring(0, tempURLFile.getName().lastIndexOf('.')));

		if (stopDecoding)
			closePdfFile();
	}

    //<start-adobe><start-thin>

    /*
	 * Create window to display the progess of downloading pdf files
	 */
	private void createDownloadWindow(){
		
		if(download==null){
		
			download = new JFrame();
			p = new JPanel(new GridBagLayout());
			pb = new JProgressBar();
			downloadMessage = new JLabel();
			downloadFile = new JLabel();
			turnOff = new JLabel();
			//saveLocal = new JButton("Save Local");
		}
		
		download.setResizable(false);
		download.setTitle(Messages.getMessage("PageLayoutViewMenu.DownloadWindowTitle"));

		//saveLocal.setEnabled(false);

		//Add save local copy functionality
		/*saveLocal.addActionListener(new ActionListener(){
			public void actionPerformed(ActionEvent e) {
				FileFilterer ff = new FileFilterer(new String[]{".pdf"},".pdf");
				JFileChooser jfc = new JFileChooser();
				jfc.setFileFilter(ff);
				int result = jfc.showSaveDialog(null);
				if(result == JFileChooser.APPROVE_OPTION){
					File save = jfc.getSelectedFile();

					if(save.exists())
						save.delete();

					if(!save.getAbsolutePath().toLowerCase().endsWith(".pdf")){
						save = new File(save.getAbsolutePath()+".pdf");
					}

					try {
						save.createNewFile();
					} catch (IOException e3) {
						e3.printStackTrace();
					}

					try {
						FileInputStream fis = new FileInputStream(tempURLFile);
						FileOutputStream fos = new FileOutputStream(save);

						byte[] buffer = new byte[1024];
						int i = 0;
						while((i=fis.read(buffer))!=-1) {
							fos.write(buffer, 0, i);
						}
						//Close the input
						fis.close();

						//Flush and close the output
						fos.flush();
						fos.close();

					} catch (FileNotFoundException e1) {
						e1.printStackTrace();
					} catch (IOException e2) {
						e2.printStackTrace();
					}
				}
			}
		});*/
		BoxLayout bl = new BoxLayout(p, BoxLayout.X_AXIS);
		GridBagConstraints gbc = new GridBagConstraints();
		gbc.fill=GridBagConstraints.BOTH;
		gbc.gridy=0;
		gbc.gridx=0;
		gbc.gridwidth=2;
		//gbc.fill = GridBagConstraints.BOTH;
		downloadFile.setSize(250,downloadFile.getHeight());
		downloadFile.setMinimumSize(new Dimension(250,15));
		downloadFile.setMaximumSize(new Dimension(250,15));
		downloadFile.setPreferredSize(new Dimension(250,15));
		p.add(downloadFile, gbc);

		gbc.gridy=1;
		downloadMessage.setSize(250,downloadFile.getHeight());
		downloadMessage.setMinimumSize(new Dimension(250,15));
		downloadMessage.setMaximumSize(new Dimension(250,15));
		downloadMessage.setPreferredSize(new Dimension(250,15));
		p.add(downloadMessage,gbc);

		gbc.gridy=2;
		pb.setSize(260,downloadFile.getHeight());
		pb.setMinimumSize(new Dimension(260,20));
		pb.setMaximumSize(new Dimension(260,20));
		pb.setPreferredSize(new Dimension(260,20));
		p.add(pb,gbc);

		gbc.gridy=3;
		p.add(turnOff,gbc);
		/*
		gbc.gridy=2;
		gbc.gridx=2;
		gbc.gridwidth=1;
		p.add(saveLocal,gbc);
		*/

		download.getContentPane().add(p);
		download.setSize(320, 100);
		
		downloadCreated = true;
	}

    //<end-thin><end-adobe>

    /**
	 * common code to all open routines
	 */
	private void openPdfFile() throws PdfException {

		isOpen = false;

        //System.out.println("filename="+filename);

        LogWriter.writeMethod("{openPdfFile}", 0);

        // ensure no previous file still being decoded
		//and preserve status if stop request issued
		boolean decodingStaus = this.stopDecoding;
		stopDecoding();
		stopDecoding = decodingStaus;

		pageNumber = 1; // reset page number for metadata

		lastFormPage = -1;
		lastEnd = -1;
		lastStart = -1;

        annotPage=-1;

        //handle fdf
		if (filename != null && filename.toLowerCase().endsWith(".fdf")) {

			int i = filename.lastIndexOf('/');

			if (i == -1)
				i = filename.lastIndexOf('\\');

			String path = "";
			if (i != -1)
				path = filename.substring(0, i + 1);

			/**read in data from fdf*/

			Map data = currentPdfFile.readFDF();

			/** store file name for use elsewhere as part of ref key without .pdf */
			byte[] pdfFile = currentPdfFile.getByteTextStringValue(data.get("F"), fdfData);

			if (pdfFile != null)
				filename = path + currentPdfFile.getTextString(pdfFile);

            objectStoreRef.storeFileName(filename);

			//open actual PDF
			this.currentPdfFile.openPdfFile(filename);

			//make it just the fields
			fdfData = (Map) data.get("Fields");
		} else
			fdfData = null;

		try {
			isDecoding = true;

			pages.resetCachedValues();

			// <start-os>
			// remove listener if not removed by close
			if (hasViewListener) {
				//hasViewListener = false;

				//flush any cached pages
				pages.flushPageCaches();

				//<start-adobe>
				removeComponentListener(viewListener);
				//<end-adobe>

			}
			// <end-os>

			// set cache size to use
			if (!stopDecoding) {
				currentPdfFile.setCacheSize(minimumCacheSize);

				// reset printing
				lastPrintedPage = -1;
				this.currentPrintDecoder = null;

				//<start-os>
				//reset javascript object
				if (javascript != null)
					javascript.reset();
				//<end-os>

				if (formsAvailable && formRenderer != null) {
					formRenderer.getCompData().setRootDisplayComponent(this);
                    formRenderer.removeDisplayComponentsFromScreen();
				}
				//invalidate();

			}

			if (!stopDecoding) {

				// reset page data - needed to flush crop settings
				pageData = new PdfPageData();
				// read and log the version number of pdf used
				pdfVersion = currentPdfFile.getType();
				LogWriter.writeLog("Pdf version : " + pdfVersion);

				if (pdfVersion == null) {
					currentPdfFile = null;
					isDecoding = false;

                	throw new PdfException( "No version on first line ");

				}

                if (pdfVersion.indexOf("1.5") != -1)
					LogWriter.writeLog("Please note Pdf version 1.5  some features not fully supported ");
				else if (pdfVersion.indexOf("1.6") != -1)
					LogWriter.writeLog("Please note Pdf version 1.6  new features not fully supported ");

				LogWriter.writeMethod("{about to read ref table}", 0);

			}
			// read reference table so we can find all objects and say if
			// encrypted
			final String root_id= currentPdfFile.readReferenceTable();

            Map values = null;
            PdfObject pdfObject=null;
            
            //new load code - be more judicious in how far down tree we scan
            final boolean ignoreRecursion=true;

			// read the catalog
			LogWriter.writeMethod("{about to read catalog}", 0);

			if (root_id != null){
				values = currentPdfFile.readObject(new PdfObject(root_id), root_id, false, null);
				
				pdfObject=new PdfPageObject(root_id);
        		pdfObject.ignoreRecursion(ignoreRecursion);

        		currentPdfFile.readObject(pdfObject, root_id, false, null);

			}
			
            // open if not encrypted or has password
            if (!isEncrypted() || isPasswordSupplied()) {

            	int type=pdfObject.getParameterConstant(PdfDictionary.Type);
                if(type!=PdfDictionary.Page){

                    // get pointer to pages and read the read page info
                    String value = (String) values.get("Pages");

                    //byte[][] pageList = pdfObject.getKeyArray(PdfDictionary.Pages);

                    if(value!=null){
                        pdfObject=new PdfPageObject(value);
                        pdfObject.ignoreRecursion(false);
                        currentPdfFile.readObject(pdfObject, value, false, null);
                    }
                }

				if (pdfObject != null) {
					LogWriter.writeLog("Pages being read ");
					pageNumber = 1; // reset page number for metadata

					// reset lookup table
					pageLookup = new PageLookup();

					//flush annots before we reread
					if(formRenderer!=null)
					formRenderer.resetAnnotData(null, insetW, insetH, pageData, currentPdfFile, formKids);

                    readAllPageReferences(ignoreRecursion, pdfObject, new HashMap(), new HashMap());

					pageCount = pageNumber - 1; // save page count

					pageNumber = 0; // reset page number for metadata;

					if (this.pageCount == 0)
						LogWriter.writeLog("No pages found");
				}

				// read any info and assign to global value
                XMLObject = (String) values.get("Metadata");

				//<start-os>
				setJavascript();
				//<end-os>

				// read any names
				Object names = null;
				try {
					names = values.get("Names");
					if (names != null)
						currentPdfFile.readNames(names, javascript);

				} catch (Exception e) {
					LogWriter.writeLog("Exception reading Names " + names + ' ' + objectStoreRef.fullFileName);
				}
				
				isXFA = false;

				//<start-adobe>

				// read any info and assign to global value
				outlineObject = values.get("Outlines");
				outlineData = null;
				hasOutline = outlineObject != null;

				//<end-adobe>

				// Read any form data
				Object rawValue = values.get("AcroForm");
                if (rawValue != null) {
					readAcroForm(rawValue);
					isForm = true;
				} else {			
					currentAcroFormData = null;
					isForm = false;
				}

				/**
				 * objects for structured content
				 */
				//read any structured info
				Object obj = values.get("StructTreeRoot");
				Map markInfo = null, structTreeRoot = null;
				if (obj != null) {
					if (obj instanceof String)
						structTreeRoot = currentPdfFile.readObject(new PdfObject((String)obj), (String) obj, false, null);
					else
						structTreeRoot = (Map) obj;
				}

				//mark info object
				obj = values.get("MarkInfo");
				if (obj != null) {
					if (obj instanceof String)
						markInfo = currentPdfFile.readObject(new PdfObject((String)obj), (String) obj, false, null);
					else
						markInfo = (Map) obj;
				}

				//<start-adobe>
				content.setRootValues(structTreeRoot, markInfo);
				//<end-adobe>

				// pass handle into renderer
				if (formsAvailable && formRenderer != null) {
					formRenderer.openFile(pageCount);
					formRenderer.resetFormData(currentAcroFormData, insetW, insetH, pageData, currentPdfFile, formKids);
				}
			}
			

			currentOffset = null;

			// reset so if continuous view mode set it will be recalculated for
			// page
			pages.disableScreen();

			if (!stopDecoding)
				pages.stopGeneratingPage();

			//force back if only 1 page
			if (pageCount < 2)
				displayView = Display.SINGLE_PAGE;
			else
				displayView = pageMode;


			//<start-adobe>
			setDisplayView(this.displayView, alignment); //force reset and add back listener
			//<end-adobe>

			isOpen = true;
		} catch (PdfException e) {
			isDecoding = false;
			throw new PdfException(e.getMessage() + " opening file");
		}

		isDecoding = false;

	}

	/**Set default page Layout*/
	private int pageMode = Display.SINGLE_PAGE;

	public void setPageMode(int mode){
		pageMode = mode;
	}

	/**
	 * allow access to Javascript object. Not part of API and not recommended for general usage
	 */
	public Javascript getJavascript() {
		return javascript;
	}

	//<start-os>
	private void setJavascript() {
		if (javascript == null && this.useJavascript)
			javascript = new Javascript();

		if (Javascript.debugActionHandler)
			System.out.println("setJavascript() " + userExpressionEngine + " with Javascript object " + this);

		if (javascript != null)
			javascript.setUserExpressionEngine((ExpressionEngine) userExpressionEngine);

        /**
         * pass into forms handler so can interact
         */
		formRenderer.setJavaScriptObject(javascript, userExpressionEngine);

        javascript.setRenderer(formRenderer);

        if (currentPdfFile != null)
            currentPdfFile.setJavaScriptObject(javascript);
    }
    //<end-os>

	/**
	 * read the data from pages lists and pages so we can open each page.
	 *
	 * object reference to first trailer
	 */
	private void readAllPageReferences(boolean ignoreRecursion, PdfObject pdfObject , Map rotations, Map parents) {

		LogWriter.writeMethod("{readAllPageReferences}", 0);

        final boolean debug=false;

        int rotation=0;
        String currentPageOffset=pdfObject.getObjectRefAsString();

        int type=pdfObject.getParameterConstant(PdfDictionary.Type);
        if(type== PdfDictionary.Unknown)
        type= PdfDictionary.Pages;

        if(debug)
            System.out.println("currentPageOffset="+currentPageOffset);

        /**
		 * handle common values which can occur at page level or higher
		 */

        /** page rotation for this or up tree*/
        int rawRotation=pdfObject.getInt(PdfDictionary.Rotate);
        String parent=pdfObject.getStringKey(PdfDictionary.Parent);

        if(rawRotation==-1 ){

            while(parent!=null && rawRotation==-1){

                if(parent!=null){
                	Object savedRotation=rotations.get(parent);
                	if(savedRotation!=null)
                		rawRotation=((Integer)savedRotation).intValue();
                }

                if(rawRotation==-1)
                parent=(String) parents.get(parent);

            }

            //save
            if(rawRotation!=-1){
                rotations.put(currentPageOffset,new Integer(rawRotation));
                parents.put(currentPageOffset,parent);
            }
        }else{ //save so we can lookup
           rotations.put(currentPageOffset,new Integer(rawRotation));
           parents.put(currentPageOffset,parent);
        }

        if(rawRotation!=-1)
                rotation=rawRotation;

        pageData.setPageRotation(rotation, pageNumber);

		/**
		 * handle media and crop box, defaulting to higher value if needed (ie
		 * Page uses Pages and setting crop box
		 */
        float[] mediaBox=pdfObject.getFloatArray(PdfDictionary.MediaBox);
        float[] cropBox=pdfObject.getFloatArray(PdfDictionary.CropBox);

        if (mediaBox != null)
			pageData.setMediaBox(mediaBox);

		if (cropBox != null)
			pageData.setCropBox(cropBox);

		if (stopDecoding)
			return;

		/** process page to read next level down */
		if (type==PdfDictionary.Pages) {

			globalResources=pdfObject.getDictionary(PdfDictionary.Resources);

            byte[][] kidList = pdfObject.getKeyArray(PdfDictionary.Kids);

            int kidCount=0;
            if(kidList!=null)
            kidCount=kidList.length;

            if(debug)
            System.out.println("PAGES---------------------currentPageOffset="+currentPageOffset+" kidCount="+kidCount);

            /** allow for empty value and put next pages in the queue */
            if (kidCount> 0) {

                if(debug)
                                System.out.println("KIDS---------------------currentPageOffset="+currentPageOffset);

                for(int ii=0;ii<kidCount;ii++){

                    String nextValue=new String(kidList[ii]);
                    
                    PdfObject nextObject=new PdfPageObject(nextValue);
                    nextObject.ignoreRecursion(ignoreRecursion);

            		currentPdfFile.readObject(nextObject, nextValue, false, null);

                    readAllPageReferences(ignoreRecursion, nextObject, rotations, parents);
                }
                    
            }
            
		} else if (type==PdfDictionary.Page) {

            if(debug)
                System.out.println("PAGE---------------------currentPageOffset="+currentPageOffset);

            // store ref for later
			pagesReferences.put(new Integer(pageNumber), currentPageOffset);
			pageLookup.put(currentPageOffset, pageNumber);

			pageData.checkSizeSet(pageNumber); // make sure we have min values
			// for page size

			/**if(structTreeRoot!=null){
             int structParents=Integer.parseInt((String)values.get("StructParents"));
             lookupStructParents.put(new Integer(pageNumber),new Integer(structParents));
             }*/


			/**
			 * add Annotations
			 */
			if (formRenderer != null && pageNumber<101) {

				/**
				 * read the annotations reference for the page we have
				 * found lots of issues with annotations so trap errors
				 */
				
				byte[][] annotList = pdfObject.getKeyArray(PdfDictionary.Annots);

                if (annotList != null) {

					PdfAnnots annotsData=null;

					try {

                        if (annotList != null) {
							annotsData = new PdfAnnots(currentPdfFile,""+pageNumber);

							annotsData.readAnnots(annotList);

						}
					} catch (Exception e) {
						LogWriter.writeLog("[PDF] " + e + " with annotation");
					}

					// pass handle into renderer
					if (formsAvailable && showAnnotations && annotsData!=null)
							formRenderer.resetAnnotData(annotsData, insetW, insetH, pageData, currentPdfFile, formKids);
				}
			}

			pageNumber++;
		}
	}
	//<end-jfr>

	// <start-13>

	private static ArrayList getDirectoryMatches(String sDirectoryName)
	throws IOException {

		sDirectoryName = sDirectoryName.replaceAll("\\.", "/");
		URL u = Thread.currentThread().getContextClassLoader().getResource(
				sDirectoryName);
		ArrayList retValue = new ArrayList(0);
		String s = u.toString();

		System.out.println("scanning " + s);

		if (s.startsWith("jar:") && s.endsWith(sDirectoryName)) {
			int idx = s.lastIndexOf(sDirectoryName);
			s = s.substring(0, idx); // isolate entry name

			System.out.println("entry= " + s);

			URL url = new URL(s);
			// Get the jar file
			JarURLConnection conn = (JarURLConnection) url.openConnection();
			JarFile jar = conn.getJarFile();

			for (Enumeration e = jar.entries(); e.hasMoreElements();) {
				JarEntry entry = (JarEntry) e.nextElement();
				if ((!entry.isDirectory())
						& (entry.getName().startsWith(sDirectoryName))) { // this
					// is how you can match
					// to find your fonts.
					// System.out.println("Found a match!");
					String fontName = entry.getName();
					int i = fontName.lastIndexOf('/');
					fontName = fontName.substring(i + 1);
					retValue.add(fontName);
				}
			}
		} else {
			// Does not start with "jar:"
			// Dont know - should not happen
			LogWriter.writeLog("Path: " + s);
		}
		return retValue;
	}

	/**
	 * read values from the classpath
	 */
	private static ArrayList readIndirectValues(InputStream in)
	throws IOException {
		ArrayList fonts;
		BufferedReader inpStream = new BufferedReader(new InputStreamReader(in));
		fonts = new ArrayList(0);
		while (true) {
			String nextValue = inpStream.readLine();
			if (nextValue == null)
				break;

			fonts.add(nextValue);
		}

		inpStream.close();

		return fonts;
	}

	/**
	 * This routine allows the user to add truetype,
	 * type1 or type1C fonts which will be used to disalay the fonts in PDF
	 * rendering and substitution as if the fonts were embedded in the PDF <br>
	 * This is very useful for clients looking to keep down the size of PDFs
	 * transmitted and control display quality -
	 * <p/>
	 * Thanks to Peter for the idea/code -
	 * <p/>
	 * How to set it up -
	 * <p/>
	 * JPedal will look for the existence of the directory fontPath (ie
	 * com/myCompany/Fonts) -
	 * <p/>
	 * If this exists, Jpedal will look for 3 possible directories (tt,t1c,t1)
	 * and make a note of any fonts if these directories exist -
	 * <p/>
	 * When fonts are resolved, this option will be tested first and if a font
	 * if found, it will be used to display the font (the effect will be the
	 * same as if the font was embedded) -
	 * <p/>
	 * If the enforceMapping is true, JPedal assumes there must be a match and
	 * will throw a PdfFontException -
	 * <p/>
	 * Otherwise Jpedal will look in the java font path for a match or
	 * approximate with Lucida -
	 * <p/>
	 * The Format is defined as follows: -
	 * <p/>
	 * fontname = filename
	 * <p/>
	 * Type1/Type1C Font names exclude any prefix so /OEGPNB+FGHeavyItalic is
	 * resolved to FGHeavyItalic -
	 * <p/>
	 * Each font have the same name as the font it replaces (so Arial will
	 * require a font file such as Arial.ttf) and it must be unique (there
	 * cannot be an Arial font in each sub-directory) -
	 * <p/>
	 * So to use this functionality, place the fonts in a jar or add to the
	 * JPedal jar and call this method after instancing PdfDecoder - JPedal will
	 * do the rest
	 *
	 * @param fontPath       -
	 *                       root directory for fonts
	 * @param enforceMapping -
	 *                       tell JPedal if all fonts should be in this directory
	 * @return flag (true if fonts added)
	 */
	public boolean addSubstituteFonts(String fontPath, boolean enforceMapping) {

		boolean hasFonts = false;

		try {
			String[] dirs = {"tt", "t1c", "t1"};
			String[] types = {"/TrueType", "/Type1C", "/Type1"};

			// check fontpath ends with separator - we may need to check this.
			// if((!fontPath.endsWith("/"))&(!fontPath.endsWith("\\")))
			// fontPath=fontPath=fontPath+separator;

			enforceFontSubstitution = enforceMapping;

			ClassLoader loader = this.getClass().getClassLoader();

			// see if root dir exists
			InputStream in = loader.getResourceAsStream(fontPath);

			LogWriter.writeLog("Looking for root " + fontPath);

			// if it does, look for sub-directories
			if (in != null) {

				LogWriter
				.writeLog("Adding fonts fonts found in  tt,t1c,t1 sub-directories of "
						+ fontPath);

				hasFonts = true;

				for (int i = 0; i < dirs.length; i++) {

					if (!fontPath.endsWith("/"))
						fontPath = fontPath + '/';

					String path = fontPath + dirs[i] + '/';

					// see if it exists
					in = loader.getResourceAsStream(path);

					// if it does read its contents and store
					if (in != null) {
						System.out.println("Found  " + path + ' ' + in);

						ArrayList fonts;

						try {

							// works with IDE or jar
							if (in instanceof ByteArrayInputStream)
								fonts = readIndirectValues(in);
							else
								fonts = getDirectoryMatches(path);

							String value, fontName;

							// now assign the fonts
							int count = fonts.size();
							for (int ii = 0; ii < count; ii++) {

								value = (String) fonts.get(ii);

								if (value == null)
									break;

								int pointer = value.indexOf('.');
								if (pointer == -1)
									fontName = value;
								else
									fontName = value.substring(0, pointer);

								FontMappings.fontSubstitutionTable.put(fontName
										.toLowerCase(), types[i]);
								FontMappings.fontSubstitutionLocation.put(fontName
										.toLowerCase(), path + value);
								//LogWriter.writeLog("Added from jar ="
								//		+ fontName + " path=" + path + value);

							}

						} catch (Exception e) {
							LogWriter.writeLog("Exception " + e
									+ " reading substitute fonts");
							System.out.println("Exception " + e
									+ " reading substitute fonts");
							// <start-demo>
							// <end-demo>
						}
					}

				}
			} else
				LogWriter.writeLog("No fonts found at " + fontPath);

		} catch (Exception e) {
			LogWriter.writeLog("Exception adding substitute fonts "
					+ e.getMessage());
		}

		return hasFonts;

	}

	// <end-13>

	//<start-adobe>

	/**
	 * return Map with user-defined annotation icons for display
	 */
	public Map getUserIconsForAnnotations() {
		return userAnnotIcons;
	}

	/**
	 * allow user to set own icons for annotation hotspots to display in
	 * renderer - pass user selection of hotspots as an array of format
	 * Image[number][page] where number is Annot number on page and page is
	 * current page -1 (ie 0 is page 1).
	 */
	public void addUserIconsForAnnotations(int page, String type, Image[] icons) {

        if (userAnnotIcons == null)
			userAnnotIcons = new Hashtable();

		userAnnotIcons.put((page) + "-" + type, icons);

		if (displayHotspots == null) {
			displayHotspots = new Hotspots();
			printHotspots = new Hotspots();
		}

		/** ensure type logged */
		displayHotspots.checkType(type);
		printHotspots.checkType(type);
    }

	/**
	 * initialise display hotspots and save global values
	 */
	public void createPageHostspots(String[] annotationTypes, String string) {
		displayHotspots = new Hotspots(annotationTypes, string);
		printHotspots = new Hotspots(annotationTypes, string);

	}


	//<end-adobe>

	//<end-canoo><end-os>


	public void setThumbnailsDrawing(boolean b) {
		thumbnailsBeingDrawn=b;
		pages.setThumbnailsDrawing(b);

	}

	/**
	 * show the imageable area in printout for debugging purposes
	 */
	public void showImageableArea() {

		showImageable = true;

	}

	/**
	 * part of pageable interface
	 *
	 * @see java.awt.print.Pageable#getNumberOfPages()
	 */
	public int getNumberOfPages() {

		//handle 1,2,5-7,12
		if (range != null) {
			int rangeCount = 0;
			for (int ii = 1; ii < this.pageCount + 1; ii++) {
				if (range.contains(ii) && (!oddPagesOnly || (ii & 1) == 1) && (!evenPagesOnly || (ii & 1) == 0))
					rangeCount++;
			}
			return rangeCount;
		}

		int count = 1;


		if (end != -1) {
			count = end - start + 1;
			if (count < 0) //allow for reverse order
				count = 2 - count;
		}

		if (oddPagesOnly || evenPagesOnly) {
			return (count + 1) / 2;
		} else {
			return count;
		}
	}

	/**
	 * part of pageable interface
	 *
	 * @see java.awt.print.Pageable#getPageFormat(int)
	 */
	public PageFormat getPageFormat(int p) throws IndexOutOfBoundsException {

		Object returnValue;

		int actualPage;

		if (end == -1)
			actualPage = p + 1;
		else if (end > start)
			actualPage = start + p;
		else
			actualPage = start - p;

		returnValue = pageFormats.get(new Integer(actualPage));

		if (debugPrint)
			System.out.println("======================================================\nspecific for page="+returnValue + " Get page format for page p=" + p
					+ " start=" + start + " pf=" + pageFormats + ' '
					+ pageFormats.keySet());

		if (returnValue == null)
			returnValue = pageFormats.get("standard");

		PageFormat pf = new PageFormat();

		if (returnValue != null)
			pf = (PageFormat) returnValue;

		//usePDFPaperSize=true;
		if (usePDFPaperSize) {

			int crw = pageData.getCropBoxWidth(actualPage);
			int crh = pageData.getCropBoxHeight(actualPage);

			createCustomPaper(pf, crw,crh);

		}

		if (!isPrintAutoRotateAndCenter) {

			pf.setOrientation(PageFormat.PORTRAIT);

		} else {
			//int crw = pageData.getCropBoxWidth(actualPage);
			//int crh = pageData.getCropBoxHeight(actualPage);

			//Set PageOrientation to best use page layout
			//int orientation = crw < crh ? PageFormat.PORTRAIT: PageFormat.LANDSCAPE;
			//pf.setOrientation(orientation);

		}

		if (debugPrint){
			System.out.println("Page format used="+pf);
			System.out.println("Orientation="+pf.getOrientation());
			System.out.println("Width="+pf.getWidth()+" imageableW="+pf.getImageableWidth());
			System.out.println("Height="+pf.getHeight()+" imageableH="+pf.getImageableHeight());
		}
		return pf;
	}

	/**
	 * part of pageable interface
	 *
	 * @see java.awt.print.Pageable#getPrintable(int)
	 */
	public Printable getPrintable(int page) throws IndexOutOfBoundsException {

		return this;
	}

	/**
	 * set pageformat for a specific page - if no pageFormat is set a default
	 * will be used. Recommended to use setPageFormat(PageFormat pf)
	 */
	public void setPageFormat(int p, PageFormat pf) {

		if (debugPrint)
			System.out.println("Set page format for page " + p);

		pageFormats.put(new Integer(p), pf);

	}

	/**
	 * set pageformat for a specific page - if no pageFormat is set a default
	 * will be used.
	 */
	public void setPageFormat(PageFormat pf) {

		if (debugPrint){
			System.out.println("Set page format Standard for page");
			System.out.println("---------------------------------");
			System.out.println("Page format used="+pf);
			System.out.println("Orientation="+pf.getOrientation());
			System.out.println("Width="+pf.getWidth()+" imageableW="+pf.getImageableWidth());
			System.out.println("Height="+pf.getHeight()+" imageableH="+pf.getImageableHeight());
			System.out.println("---------------------------------");

		}

		pageFormats.put("standard", pf);

	}

	/**
	 * shows if text extraction is XML or pure text
	 */
	public static boolean isXMLExtraction() {

		return isXMLExtraction;
	}

	/**
	 * XML extraction is the default - pure text extraction is much faster
	 */
	public static void useTextExtraction() {

		isXMLExtraction = false;
	}

	/**
	 * XML extraction is the default - pure text extraction is much faster
	 */
	public static void useXMLExtraction() {

		isXMLExtraction = true;
	}

	/**
	 * remove all displayed objects for JPanel display (wipes current page)
	 */
	public void clearScreen() {
        currentDisplay.flush();
		pages.refreshDisplay();
	}

	//<start-jfr>
	/**
	 * allows user to cache large objects to disk to avoid memory issues,
	 * setting minimum size in bytes (of uncompressed stream) above which object
	 * will be stored on disk if possible (default is -1 bytes which is all
	 * objects stored in memory) - Must be set before file opened.
	 *
	 */
	public void setStreamCacheSize(int size) {
		this.minimumCacheSize = size;
	}

	/**
	 * used to display non-PDF files
	 */
	public void addImage(BufferedImage img) {
		currentDisplay.drawImage(img);

	}

	/**
	 * shows if embedded fonts present on page just decoded
	 */
	public boolean hasEmbeddedFonts() {
		return hasEmbeddedFonts;
	}

	/**
	 * convert form ref into actual object
	 */
	public Map resolveFormReference(String ref) {

		// text fields
		Map fields = new HashMap();

		// setup a list of fields which are string values
		fields.put("T", "x");
		fields.put("TM", "x");
		fields.put("TU", "x");
		fields.put("CA", "x");
		fields.put("R", "x");
		fields.put("V", "x");
		fields.put("RC", "x");
		fields.put("DA", "x");
		fields.put("DV", "x");

		return currentPdfFile.readObject(new PdfObject(ref), ref, false, fields);

	}

	/**
	 * shows if whole document contains embedded fonts and uses them
	 */
	final public boolean PDFContainsEmbeddedFonts() throws Exception {

		boolean hasEmbeddedFonts = false;

		/**
		 * scan all pages
		 */
		for (int page = 1; page < pageCount + 1; page++) {

			/** get pdf object id for page to decode */
			String currentPageOffset = (String) pagesReferences
			.get(new Integer(page));

			/**
			 * decode the file if not already decoded, there is a valid object
			 * id and it is unencrypted
			 */
			if ((currentPageOffset != null)) {

				//@speed
				/** read page or next pages */
				PdfObject pdfObject=new PdfPageObject(currentPageOffset);
				Map values = currentPdfFile.readObject(pdfObject, currentPageOffset,false, null);
				PdfObject Resources=pdfObject.getDictionary(PdfDictionary.Resources);

				/** get information for the page */
				String value = (String) values.get("Contents");

				if (value != null) {

					PdfStreamDecoder current = new PdfStreamDecoder();

					current.setExternalImageRender(customImageHandler);
					
					current.init(true, renderPage, renderMode, extractionMode,
							pageData, page, currentDisplay, currentPdfFile);

					if (globalResources != null)
						current.readResources(globalResources,true);

					/**read the resources for the page*/
					if (Resources != null)
						current.readResources(Resources,true);

					hasEmbeddedFonts = current.hasEmbeddedFonts();

					// exit on first true
					if (hasEmbeddedFonts)
						page = this.pageCount;
				}
			}
		}

		return hasEmbeddedFonts;
	}

	/**
	 * Returns list of the fonts used on the current page decoded
	 */
	public String getFontsInFile() {
		if (fontsInFile == null)
			return "No fonts defined";
		else
			return fontsInFile;
	}

	/**
	 * include image data in PdfData - <b>not part of API, please do not use</b>
	 */
	public void includeImagesInStream() {
		includeImages = true;
	}

	//<start-adobe>
	/**
	 * return lines on page after decodePage run - <b>not part of API, please do
	 * not use</b>
	 */
	public PageLines getPageLines() {
		return this.pageLines;
	}
	//<end-adobe>

	/**
	 * if <b>true</b> uses the original jpeg routines provided by sun, else
	 * uses the imageIO routine in java 14 which is default<br>
	 * only required for PDFs where bug in some versions of ImageIO fails to
	 * render JPEGs correctly
	 */
	public void setEnableLegacyJPEGConversion(boolean newjPEGConversion) {

		use13jPEGConversion = newjPEGConversion;
	}

	/**
	 * used to update statusBar object if exists
	 */
	private class ProgressListener implements ActionListener {

		public void actionPerformed(ActionEvent evt) {

			statusBar.setProgress((int) (statusBar.percentageDone));
		}

	}

    /**
     * Allow user to access Forms renderer - returns null not available
	 * (should not generally be needed)
     */
    public AcroRenderer getFormRenderer() {
        if (!this.formsAvailable)
            return null;
        else
            return formRenderer;
    }

	/**
	 * shows if page reported any errors while printing or being decoded. Log
	 * can be found with getPageFailureMessage()
	 *
	 * @return Returns the printingSuccessful.
	 */
	public boolean isPageSuccessful() {
		return operationSuccessful;
	}

	/**
	 * return any errors or other messages while calling decodePage() - zero
	 * length is no problems
	 */
	public String getPageDecodeReport() {
		return decodeStatus;
	}

	/**
	 * Return String with all error messages from last printed (useful for
	 * debugging)
	 */
	public String getPageFailureMessage() {
		return pageErrorMessages;
	}

	/**
	 * If running in GUI mode, will extract a section of rendered page as
	 * BufferedImage -coordinates are PDF co-ordinates. If you wish to use hires
	 * image, you will need to enable hires image display with
	 * decode_pdf.useHiResScreenDisplay(true);
	 *
	 * @param t_x1
	 * @param t_y1
	 * @param t_x2
	 * @param t_y2
	 * @param scaling
	 * @return pageErrorMessages - Any printer errors
	 */
	public BufferedImage getSelectedRectangleOnscreen(float t_x1, float t_y1,
			float t_x2, float t_y2, float scaling) {

		/** get page sizes */
		//int mediaBoxW = pageData.getMediaBoxWidth(pageNumber);
		int mediaBoxH = pageData.getMediaBoxHeight(pageNumber);
		//int mediaBoxX = pageData.getMediaBoxX(pageNumber);
		//int mediaBoxY = pageData.getMediaBoxY(pageNumber);
		int crw = pageData.getCropBoxWidth(pageNumber);
		int crh = pageData.getCropBoxHeight(pageNumber);
		int crx = pageData.getCropBoxX(pageNumber);
		int cry = pageData.getCropBoxY(pageNumber);

		// check values for rotated pages
		if (t_y2 < cry)
			t_y2 = cry;
		if (t_x1 < crx)
			t_x1 = crx;
		if (t_y1 > (crh + cry))
			t_y1 = crh + cry;
		if (t_x2 > (crx + crw))
			t_x2 = crx + crw;

		if ((t_x2 - t_x1) < 1 || (t_y1 - t_y2) < 1)
			return null;

		float scalingFactor = scaling / 100;
		float imgWidth = t_x2 - t_x1;
		float imgHeight = t_y1 - t_y2;

		/**
		 * create the image
		 */
		BufferedImage img = new BufferedImage((int) (imgWidth * scalingFactor),
				(int) (imgHeight * scalingFactor), BufferedImage.TYPE_INT_RGB);

		Graphics2D g2 = img.createGraphics();

		/**
		 * workout the scaling
		 */

		if (cry > 0)// fix for negative pages
			cry = mediaBoxH - crh - cry;

		// use 0 for rotated extraction
		AffineTransform scaleAf = getScalingForImage(pageNumber, 0, scalingFactor);// (int)(mediaBoxW*scale),
		// (int)(mediaBoxH*scale),
		int cx = -crx, cy = -cry;

		scaleAf.translate(cx, -cy);
		scaleAf.translate(-(t_x1 - crx), mediaBoxH - t_y1 - cry);

		AffineTransform af = g2.getTransform();

		g2.transform(scaleAf);

		if ((currentDisplay.addBackground())) {
			g2.setColor(currentDisplay.getBackgroundColor());
			g2.fill(new Rectangle(crx, cry, crw, crh));
		}

		currentDisplay.setOptimsePainting(true); //ensure drawn
		currentDisplay.paint(g2, null, null, null, false,false);

		if (formsAvailable) {
			/**
			 * draw acroform data onto Panel
			 */
			if (formRenderer != null) {

                formRenderer.getCompData().renderFormsOntoG2(g2, pageNumber, scaling, 0, this.displayRotation);
                formRenderer.getCompData().resetScaledLocation(oldScaling, displayRotation, 0);
            }
		}

		// set up page hotspots
		if (!showAnnotations && annotsData != null && displayHotspots != null)
			displayHotspots.setHotspots(annotsData);

		g2.setTransform(af);

		// <start-os>
		// add demo cross if needed
        if (PdfDecoder.inDemo) {
			g2.setColor(Color.red);
			g2.drawLine(0, 0, img.getWidth(), img.getHeight());
			g2.drawLine(0, img.getHeight(), img.getWidth(), 0);
		} // <end-os>

		g2.dispose();

		return img;
	}
	//<end-jfr>

	/**
	 * return object which provides access to file images and name
	 */
	public ObjectStore getObjectStore() {
		return objectStoreRef;
	}

	/**
	 * return object which provides access to file images and name (use not
	 * recommended)
	 */
	public void setObjectStore(ObjectStore newStore) {
		objectStoreRef = newStore;
	}

	//<start-adobe>
	//<start-jfr>
	/**
	 * returns object containing grouped text - Please see
	 * org.jpedal.examples.text for example code.
	 */
	public PdfGroupingAlgorithms getGroupingObject() throws PdfException {

		PdfData textData = getPdfData();
		if (textData == null)
			return null;
		else
			return new PdfGroupingAlgorithms(textData);
	}

	/**
	 * returns object containing grouped text from background grouping - Please
	 * see org.jpedal.examples.text for example code
	 */
	public PdfGroupingAlgorithms getBackgroundGroupingObject() {

		PdfData textData = this.pdfBackgroundData;
		if (textData == null)
			return null;
		else
			return new PdfGroupingAlgorithms(textData);
	}


	//<end-adobe>




	/**
	 * get PDF version in file
	 */
	final public String getPDFVersion() {
		return pdfVersion;
	}

	/**
	 * returns object, handling any indirect references
	 *
	 * @param string
	 * @param rawAnnotDetails
	 */
	public Map resolveToMapOrString(String string, Object rawAnnotDetails) {

		Map returnValue = null;
		if (rawAnnotDetails instanceof Map) {
			returnValue = (Map) ((Map) rawAnnotDetails).get(string);
		}

		if (returnValue == null)
			return (Map) currentPdfFile.resolveToMapOrString(string, rawAnnotDetails);
		else
			return returnValue;
	}
	//<end-jfr>

	//<start-adobe>

	/**
	 * used for non-PDF files to reset page
	 */
	public void resetForNonPDFPage() {

		displayScaling = null;

		/** set hires mode or not for display */
		currentDisplay.setHiResImageForDisplayMode(false);

		fontsInFile = "";
		pageCount = 1;
		hasOutline = false;

		if (formsAvailable  && formRenderer != null)
                formRenderer.removeDisplayComponentsFromScreen();
			//invalidate();

		// reset page data
		this.pageData = new PdfPageData();
	}
	//<end-adobe>

	/**
	 * provides details on printing to enable debugging info for IDRsolutions
	 */
	public static void setDebugPrint(boolean newDebugPrint) {
		debugPrint = newDebugPrint;
	}

	//<start-adobe>
	/**
	 * set view mode used in panel and redraw in new mode
	 * SINGLE_PAGE,CONTINUOUS,FACING,CONTINUOUS_FACING delay is the time in
	 * milli-seconds which scrolling can stop before background page drawing
	 * starts
	 * Multipage views not in OS releases
	 */
	public void setDisplayView(int displayView, int orientation) {

		this.alignment = orientation;

		if (pages != null)
			pages.stopGeneratingPage();

		//<start-os><start-13>
		/**
         //<end-13><end-os>
         pages=new SingleDisplay(pageNumber,pageCount,currentDisplay);
         /**/

		boolean needsReset = (displayView != Display.SINGLE_PAGE || this.displayView != Display.SINGLE_PAGE);
		if (needsReset && (this.displayView == Display.FACING || displayView == Display.FACING))
			needsReset = false;

		if (displayView != Display.SINGLE_PAGE)
			needsReset = true;

		boolean hasChanged = displayView != this.displayView;

		this.displayView = displayView;

		//<start-os><start-thin><start-13>
		if (displayView == Display.SINGLE_PAGE) {

			pages = new SingleDisplay(pageNumber, pageCount, currentDisplay);
		} else {

			//ensure highlight turned off
			currentHighlightedObject = null;

			if (needsReset) {
				setPageRotation(this.displayRotation); //force update
				pages = new MultiDisplay(pageNumber, pageCount, null, displayView, customSwingHandle);
			} else
				pages = new MultiDisplay(pageNumber, pageCount, currentDisplay, displayView, customSwingHandle);
		}

		//<end-13><end-thin><end-os>
		/***/

		//<start-os>
		// remove listener if setup
		if (hasViewListener) {
			hasViewListener = false;

			removeComponentListener(viewListener);

		}
		//<end-os>

		/**
		 * setup once per page getting all page sizes and working out settings
		 * for views
		 */
		if (currentOffset == null)
			currentOffset = new PageOffsets(pageCount, pageData);

		pages.setup(useAcceleration, currentOffset, this);
		pages.init(scaling, pageCount, displayRotation, pageNumber, currentDisplay, true, pageData, insetW, insetH);

		// force redraw
		lastFormPage = -1;
		lastEnd = -1;
		lastStart = -1;

		pages.refreshDisplay();
		updateUI();

		//<start-os>
		// add listener if one not already there
		if (!hasViewListener) {
			hasViewListener = true;
			addComponentListener(viewListener);
		}

		//move to correct page
		if (pageNumber > 0) {
			if (hasChanged && displayView == Display.SINGLE_PAGE) {
				try {
					unsetScaling();
					setPageParameters(scaling, pageNumber, displayRotation);
					invalidate();
					updateUI();
					decodePage(pageNumber);
				} catch (Exception e) {
					e.printStackTrace();
				}
			} else if (displayView != Display.SINGLE_PAGE) {

				int yCord = getYCordForPage(pageNumber, -2);
				Rectangle r = this.getVisibleRect();

				scrollRectToVisible(new Rectangle(0, yCord, (int) r.getWidth() - 1, (int) r.getHeight() - 1));
				scrollRectToVisible(new Rectangle(0, yCord, (int) r.getWidth() - 1, (int) r.getHeight() - 1));

			}
		}

		//<end-os>
	}
	//<end-adobe>

	//<start-jfr>

	/**
	 * show if page is an XFA form
	 */
	public boolean isXFAForm() {
		return isXFA;
	}

	//<start-os>
	/**
	 * show if page is an XFA form
	 */
	public boolean hasJavascript() {

		return javascript != null && javascript.hasJavascript();
	}
	//<end-os>

	/**
	 * return page currently being printed or -1 if finished
	 */
	public int getCurrentPrintPage() {
		return currentPrintPage;
	}

	public void resetCurrentPrintPage() {
		currentPrintPage = 0;
	}

	/**
	 * flag to show if we suspect problem with some images
	 */
	public boolean hasAllImages() {
		return imagesProcessedFully;
	}

    public boolean getPageDecodeStatus(int status) {

        /**if(status.equals(DecodeStatus.PageDecodingSuccessful))
            return pageSuccessful;
        else*/ if(status==(DecodeStatus.NonEmbeddedCIDFonts)){
            return hasNonEmbeddedCIDFonts;
        }else if(status==(DecodeStatus.ImagesProcessed))
            return imagesProcessedFully;
        else
            new RuntimeException("Unknown paramter");

        return false;
    }

    /**
     * get page statuses
     */
    public String getPageDecodeStatusReport(int status) {

        if(status==(DecodeStatus.NonEmbeddedCIDFonts)){
            return nonEmbeddedCIDFonts;
        }else
            new RuntimeException("Unknown paramter");

        return "";
    }

    /**
	 * set print mode (Matches Abodes Auto Print and rotate output
	 */
	public void setPrintAutoRotateAndCenter(boolean value) {
		isPrintAutoRotateAndCenter = value;

	}

	public void setPrintCurrentView(boolean value) {
		this.printOnlyVisible = value;
	}

	/**
	 * allows external helper classes to be added to JPedal to alter default functionality -
	 * not part of the API and should be used in conjunction with IDRsolutions only
	 * <br>if Options.FormsActionHandler is the type then the <b>newHandler</b> should be
	 * of the form <b>org.jpedal.objects.acroforms.ActionHandler</b>
	 *
	 * @param newHandler
	 * @param type
	 */
	public void addExternalHandler(Object newHandler, int type) {
//System.out.println("PdfDecoder.addExternalHandler()");
		switch (type) {
		case Options.ImageHandler:
			customImageHandler = (ImageHandler) newHandler;
			break;

		case Options.Renderer:
			//cast and assign here
			break;

		case Options.FormFactory:
			if (formsAvailable)
				formRenderer.setFormFactory((FormFactory) newHandler);
			break;

		case Options.MultiPageUpdate:
			customSwingHandle = newHandler;
			break;

			//<start-os>
		case Options.ExpressionEngine:

			userExpressionEngine = newHandler;

			if (Javascript.debugActionHandler)
				System.out.println("User expression engine set to " + userExpressionEngine + ' ' + newHandler);

			//javascript=null; //force reset
			setJavascript();
			break;
			//<end-os>

		case Options.LinkHandler:

			if (formRenderer != null)
				formRenderer.resetHandler(newHandler, this,Options.LinkHandler);

			break;

		case Options.FormsActionHandler:

			if (formRenderer != null)
				formRenderer.resetHandler(newHandler, this,Options.FormsActionHandler);

			break;

			//<start-thin><start-adobe>
		case Options.ThumbnailHandler:
			pages.setThumbnailPanel((org.jpedal.examples.simpleviewer.gui.generic.GUIThumbnailPanel) newHandler);
			break;
			//<end-adobe><end-thin>

		default:
			throw new IllegalArgumentException("Unknown type");

		}
	}

	/**
	 * used internally by multiple pages
	 * scaling -1 to ignore, -2 to force reset
	 */
	public int getYCordForPage(int page, float scaling) {

		if (scaling == -2 || (scaling != -1f && scaling != oldScaling)) {
			oldScaling = scaling;
			pages.setPageOffsets(this.pageCount, page);

			//System.out.println("xxxxxxx  RESET xxxxxxxxxxx "+scaling);
		}
		return pages.getYCordForPage(page);
	}

	public void unsetScaling() {

		displayScaling = null;


	}


	/**
	 * return PDF data object or Objects
	 * for field containing values from PDF file
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
	public Object getFormDataForField(String formName) {

		Object formData=null;

		//test first then form if no value
		if (formRenderer != null)
            formData= formRenderer.getFormDataAsObject(formName);

		return formData;
	}

	/**
	 * return full list of Fields for Annots and Forms
	 */
	public Set getNamesForAllFields() throws PdfException {

		if(formRenderer==null){

			System.out.println("================No DATA=====================");
			return new HashSet();
		}

		Set set = new HashSet();

		List forms = formRenderer.getComponentNameList();

		if (forms != null)
			set.addAll(forms);

		return set;

	}

    /**
     * return swing widget regardless of whether it came from Annot or form
     * -1 if not found values in FormFactory (ie UNKNOWN)
     */
	public Integer getFormComponentType(String name) {

		Integer type=FormFactory.UNKNOWN;

        if(formRenderer!=null)
            type= formRenderer.getCompData().getTypeValueByName(name);

		return type;

	}

    /**
     * return swing widget regardless of whether it came from Annot or form
     */
//	public Object[] getFormComponent(String name) {
//
//		Component[] comps = null;
//
//		if(formRenderer!=null)
//			comps= (Component[]) formRenderer.getComponentsByName(name);
//
//		return comps;
//
//	}


	public PdfObjectReader getIO() {
		return currentPdfFile;
	}



	public boolean isThumbnailsDrawing() {
		return thumbnailsBeingDrawn;
	}

	public void setPageCount(int numPages) {
		pageCount = numPages;
	}

	public boolean isPDF() {
		return isPDf;
	}

	public void setPDF(boolean isPDf) {
		this.isPDf = isPDf;
	}

	public boolean isMultiPageTiff() {
		return isMultiPageTiff;
	}

	public void setMultiPageTiff(boolean isMultiPageTiff) {
		this.isMultiPageTiff = isMultiPageTiff;
	}
	//<end-jfr>

	public String getFileName() {
		return filename;
	}

	public boolean isUseDownloadWindow() {
		return useDownloadWindow;
	}

	public void setUseDownloadWindow(boolean useDownloadWindow) {
		this.useDownloadWindow = useDownloadWindow;
	}
}
