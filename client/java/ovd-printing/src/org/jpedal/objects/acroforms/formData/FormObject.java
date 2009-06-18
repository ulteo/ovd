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
* FormObject.java
* ---------------
*/
package org.jpedal.objects.acroforms.formData;

import java.awt.Color;
import java.awt.Font;
import java.awt.Rectangle;
import java.awt.image.BufferedImage;
import java.util.Map;

import javax.swing.JTextField;

import org.jpedal.objects.GraphicsState;
import org.jpedal.objects.acroforms.decoding.FormStream;
import org.jpedal.objects.acroforms.decoding.OverStream;
import org.jpedal.objects.acroforms.overridingImplementations.PdfSwingPopup;
import org.jpedal.objects.acroforms.utils.ConvertToString;
import org.jpedal.utils.LogWriter;

/**
 * @author Chris Wade
 *         <p/>
 *         This class holds all the data for the form fields
 */
public class FormObject {

    public static final int ANNOTPOPUP = 10;
    public static final int ANNOTTEXT = 11;
    public static final int ANNOTSQUARE = 12;
    public static final int ANNOTINK = 13;
    public static final int ANNOTFREETEXT = 14;
    public static final int ANNOTLINK = 15;
    public static final int ANNOTSTAMP = 16;

    public static final int FORMBUTTON = 0;
    public static final int FORMCHOICE = 1;
    public static final int FORMTEXT = 2;
    public static final int FORMSIG = 3;
    
    /*
     * form flag values for the field flags
     */
    /** fieldFlag 1 */
    public static final int READONLY = 1;
    /** fieldFlag 2 */
    public static final int REQUIRED = 2;
    /** fieldFlag 3 */
    public static final int NOEXPORT = 3;
    /** fieldFlag 12 */
    public static final int MULTILINE = 12;
    /** fieldFlag 13 */
    public static final int PASSWORD = 13;
    /** fieldFlag 14 */
    public static final int NOTOGGLETOOFF = 14;
    /** fieldFlag 15 */
    public static final int RADIO = 15;
    /** fieldFlag 16 */
    public static final int PUSHBUTTON = 16;
    /** fieldFlag 17 */
    public static final int COMBO = 17;
    /** fieldFlag 18 */
    public static final int EDIT = 18;
    /** fieldFlag 19 */
    public static final int SORT = 19;
    /** fieldFlag 20 */
    public static final int FILESELECT = 20;
    /** fieldFlag 21 */
    public static final int MULTISELECT = 21;
    /** fieldFlag 22 */
    public static final int DONOTSPELLCHECK = 22;
    /** fieldFlag 23 */
    public static final int DONOTSCROLL = 23;
    /** fieldFlag 24 */
    public static final int COMB = 24;
    /** fieldFlag 25 */
    public static final int RICHTEXT = 25;//same as RADIOINUNISON (radio buttons)
    /** fieldFlag 25 */
    public static final int RADIOINUNISON = 25;//same as RICHTEXT (text fields)
    /** fieldFlag 26 */
    public static final int COMMITONSELCHANGE = 26;

    //flag used to handle POPUP internally
    public static final int POPUP = 1;

    //internal flag used to store status on additional actions when we decode
    private int popupFlag=0;
    
    private String annotParent=null;


    public FormObject() {

    }

    protected boolean isXFAObject = false;
    private int rotation = 0;
    private String userName;
    private String fieldName,annotName;
    private String parent;
    private String mapName;
    private boolean[] characteristic = new boolean[9];
    protected Rectangle rect = new Rectangle(0, 0, 0, 0);
    private int type = -1;
    private boolean[] flags = new boolean[32];

    private int[] topIndex = null;
    private String selectedItem;
    private String[] itemsList;

    private String PDFobj;
    private Object border;
    private Color borderColor;
    private Color backgroundColor = null;

    protected int alignment = -1;
    private Color textColor;
    private Font textFont;
    private int textSize = -1;
    private String textString;
    private int maxTextLength = -1;
    private int textPosition = -1;
    private String defaultValue;
    private String popupTitle;

    private String normalCaption;
    private String rolloverCaption;
    private String downCaption;

    private boolean appearancesUsed = false;
    private boolean offsetDownIcon = false;
    private boolean noDownIcon = false;
    private boolean invertDownIcon = false;
    private String whenToScaleIcon;
    private String defaultState;
    private String onState;
    private String currentState;
    private String normalOffState, normalOnState;
    private BufferedImage normalOffImage = null;
    private BufferedImage normalOnImage;
    private BufferedImage rolloverOffImage = null;
    private BufferedImage rolloverOnImage;
    private BufferedImage downOffImage = null;
    private BufferedImage downOnImage;
    private boolean hasNormalOffImage = false;
    private boolean hasRolloverOffImage = false;
    private boolean hasDownOffImage = false;
    private boolean hasDownImages = false;
    private boolean hasRolloverOn = false;
    private boolean hasNormalOn = false;
    private Map kidData = null;

    /**
     * returns true if this formObject represents an XFAObject
     */
    public boolean isXFAObject(){
    	return isXFAObject;
    }

    /**
     * the C color for annotations
     */
    private Color cColor;
    /**
     * the contents for any text display on the annotation
     */
    private String contents;
    /**
     * whether the annotation is being displayed or not by default
     */
    private boolean show = false;
    /**
     * the graphics state for the annotation ink list
     */
    private GraphicsState graphicsState = null;
    /**
     * the internal bounds from the Rect of the annotation
     */
    private Rectangle internalBounds;
    private int pageNumber = -1;

    private String stateTocheck = "";
    /**
     * map that references the display value from the export values
     */
    private Map valuesMap = null;
    
    private boolean popupBuilt = false;

    //Additional action fields
    private Object objA;
    private Object popupObj;

    private Object objE;
    private Object objX;
    
    public FormObject duplicate() {
        FormObject newObject = new FormObject();

        newObject.rotation = rotation;
        newObject.userName = userName;
        newObject.fieldName = fieldName;

        newObject.annotName = annotName;
        
        newObject.parent = parent;
        newObject.mapName = mapName;
        newObject.characteristic = characteristic;
        newObject.rect = rect;
        newObject.type = type;
        newObject.flags = flags;

        newObject.topIndex = topIndex;
        newObject.selectedItem = selectedItem;
        newObject.itemsList = itemsList;

        newObject.objA = objA;
        newObject.objE = objE;
        newObject.objX = objX;

        newObject.PDFobj = PDFobj;

        newObject.border = border;
        newObject.borderColor = borderColor;
        newObject.backgroundColor = backgroundColor;

        newObject.alignment = alignment;
        newObject.textColor = textColor;
        newObject.textFont = textFont;
        newObject.textSize = textSize;
        newObject.textString = textString;
        newObject.maxTextLength = maxTextLength;
        newObject.textPosition = textPosition;
        newObject.defaultValue = defaultValue;

        newObject.normalCaption = normalCaption;
        newObject.rolloverCaption = rolloverCaption;
        newObject.downCaption = downCaption;

        newObject.appearancesUsed = appearancesUsed;
        newObject.offsetDownIcon = offsetDownIcon;
        newObject.noDownIcon = noDownIcon;
        newObject.invertDownIcon = invertDownIcon;
        newObject.whenToScaleIcon = whenToScaleIcon;
        newObject.defaultState = defaultState;
        newObject.onState = onState;
        newObject.currentState = currentState;
        newObject.normalOffImage = normalOffImage;
        newObject.normalOnImage = normalOnImage;
        newObject.rolloverOffImage = rolloverOffImage;
        newObject.rolloverOnImage = rolloverOnImage;
        newObject.downOffImage = downOffImage;
        newObject.downOnImage = downOnImage;
        newObject.hasNormalOffImage = hasNormalOffImage;
        newObject.hasRolloverOffImage = hasRolloverOffImage;
        newObject.hasDownOffImage = hasDownOffImage;
        newObject.hasDownImages = hasDownImages;
        newObject.hasRolloverOn = hasRolloverOn;
        newObject.hasNormalOn = hasNormalOn;
        newObject.kidData = kidData;
        newObject.pageNumber = pageNumber;

        //annotations
        newObject.cColor = cColor;
        newObject.contents = contents;
        newObject.show = show;
        newObject.internalBounds = internalBounds;
        newObject.popupTitle = popupTitle;

        newObject.stateTocheck = stateTocheck;

        return newObject;
    }

    /**
     * turns all data into a string
     */
    public String toString() {
        StringBuffer buf = new StringBuffer();

        buf.append("\n PDF object=");
        buf.append(PDFobj);
        buf.append("\n rotation=");
        buf.append(rotation);
        buf.append("\n username=");
        buf.append(userName);
        buf.append("\n fieldName=");
        buf.append(fieldName);
        buf.append("\n annotName=");
        buf.append(annotName);
        
        buf.append("\n parent=");
        buf.append(parent);
        buf.append("\n mapName=");
        buf.append(mapName);
        buf.append("\n characteristic=");
        buf.append(ConvertToString.convertArrayToString(characteristic));
        buf.append("\n Rect=");
        buf.append(rect);
        buf.append("\n type=");
        buf.append(type);
        buf.append("\n flags=");
        buf.append(flags);

        buf.append("\n topIndex=");
        buf.append(topIndex);
        buf.append("\n selectedItem=");
        buf.append(selectedItem);
        buf.append("\n itemsList=");
        buf.append(itemsList);

        buf.append("\n activateAction=");
        buf.append(objA);
        buf.append("\n enteredAction=");
        buf.append(objE);
        buf.append("\n exitedAction=");
        buf.append(objX);

        buf.append("\n border=");
        buf.append(border);
        buf.append("\n borderColor=");
        buf.append(borderColor);
        buf.append("\n backgroundColor=");
        buf.append(backgroundColor);

        buf.append("\n alignment=");
        buf.append(alignment);
        buf.append("\n textColor=");
        buf.append(textColor);
        buf.append("\n textFont=");
        buf.append(textFont);
        buf.append("\n textSize=");
        buf.append(textSize);
        buf.append("\n textString=");
        buf.append(textString);
        buf.append("\n maxTextLength=");
        buf.append(maxTextLength);
        buf.append("\n textPosition=");
        buf.append(textPosition);
        buf.append("\n defaultValue=");
        buf.append(defaultValue);
        buf.append("\n popupTitle=");
        buf.append(popupTitle);

        buf.append("\n normalCaption=");
        buf.append(normalCaption);
        buf.append("\n rolloverCaption=");
        buf.append(rolloverCaption);
        buf.append("\n downCaption=");
        buf.append(downCaption);

        //add images data
        buf.append(toStringImages());

        buf.append("\n kidData=");
        buf.append(kidData);

        //annotation
        buf.append("\n Ccolor=");
        buf.append(cColor);
        buf.append("\n contents=");
        buf.append(contents);
        buf.append("\n show=");
        buf.append(show);
        buf.append("\n internalBounds=");
        buf.append(internalBounds);
        
        return buf.toString();
    }
    
    /**
     * turns all Image data into a string
     */
    public String toStringImages() {
        StringBuffer buf = new StringBuffer();
        buf.append("\n appearancesUsed=");
        buf.append(appearancesUsed);
        buf.append("\n offsetdownicon=");
        buf.append(offsetDownIcon);
        buf.append("\n nodownIcon");
        buf.append(noDownIcon);
        buf.append("\n invertDownIcon=");
        buf.append(invertDownIcon);
        buf.append("\n whentoscaleicon=");
        buf.append(whenToScaleIcon);
        buf.append("\n defaultstate=");
        buf.append(defaultState);
        buf.append("\n onstate=");
        buf.append(onState);
        buf.append("\n currentstate=");
        buf.append(currentState);
        buf.append("\n normaloff=");
        buf.append(normalOffImage);
        buf.append("\n normalon=");
        buf.append(normalOnImage);
        buf.append("\n rolloveroff=");
        buf.append(rolloverOffImage);
        buf.append("\n rolloveron=");
        buf.append(rolloverOnImage);
        buf.append("\n downoff=");
        buf.append(downOffImage);
        buf.append("\n downon=");
        buf.append(downOnImage);
        buf.append("\n hasnormaloff=");
        buf.append(hasNormalOffImage);
        buf.append("\n hasrolloveroff=");
        buf.append(hasRolloverOffImage);
        buf.append("\n hasdownoff=");
        buf.append(hasDownOffImage);
        buf.append("\n hasdownimages=");
        buf.append(hasDownImages);
        buf.append("\n hasrolloveron=");
        buf.append(hasRolloverOn);
        buf.append("\n hasnormalon=");
        buf.append(hasNormalOn);

        return buf.toString();
    }

    /**
     * get actual object reg
     */
    public String getPDFRef() {
        return PDFobj;
    }
    
    /**
     * pass in PDF id
     */
    public void setPDFRef(String ref) {
        PDFobj = ref;
    }

    /**
     * create null border or add the specified border
     */
    public void setBorder(Object borderInfo) {
        border = borderInfo;
    }

    /**
     * creates and sets an alignment variable
     */
    public void setHorizontalAlign(Object field) {
        alignment = JTextField.LEFT;
        if (field.equals("0")) {
            alignment = JTextField.LEFT;//2
        } else if (field.equals("1")) {
            alignment = JTextField.CENTER;//0
        } else if (field.equals("2")) {
            alignment = JTextField.RIGHT;//4
        } else {
            LogWriter.writeFormLog("FormObject.setHorizontalAlign not taking " + field, FormStream.debugUnimplemented);
        }
    }

    /**
     * set link for Annots linked in AcroForm
     */
    public void setAnnotParent(String annotParent) {
        this.annotParent=annotParent;
    }


    /**
     * set link for Annots linked in AcroForm
     */
    public String getAnnotParent() {
         return annotParent;
    }
    
    /**
     * returns the alignment
     */
    public int getAlignment(){
    	return alignment;
    }

    /**
     * sets the text color for this form
     */
    public void setTextColor(Color color) {
        textColor = color;
    }

    /**
     * set the text font for this form
     */
    public void setTextFont(Font font) {
        textFont = font;
    }

    /**
     * sets the text size for this form
     */
    public void setTextSize(int size) {
        textSize = size;
    }

    /**
     * sets the child on state,
     * only applicable to radio buttons
     */
    public void setChildOnState(String curValue) {
        onState = curValue;
    }

    /**
     * sets the current state,
     * only applicable to check boxes
     */
    public void setCurrentState(String curValue) {
        currentState = curValue;
    }

    /**
     * sets the text value
     */
    public void setTextValue(String text) {
		if(textString==null)
        	textString = text;
    }

    /**
     * sets the selected item
     * only applicable to the choices fields
     */
    public void setSelectedItem(String curValue) {
        selectedItem = curValue;
    }

    /**
     * sets the username for this field
     */
    public void setUserName(String field) {
        userName = field;
    }

    /**
     * sets the field name for this field
     */
    public void setFieldName(String field) {

    	fieldName = field;
    }
    
    /**
     * sets the Annot name for this field
     */
    public void setAnnotName(String field) {

    	annotName = field;
    }
    
    /**
     * sets the parent for this field
     */
    public void setParent(String parent) {
        this.parent = parent;
    }
    
    /**
     * gets the parent for this field
     */
    public String getParentRef() {
        return parent;
    }

    /**
     * sets the map name for this field
     */
    public void setMapName(String field) {
        mapName = field;
        if(OverStream.debugUnimplemented){
        	System.out.println("{internal only} mapname never read");
        	ConvertToString.printStackTrace(1);
        }
    }
    
    /**
     * returns the map name for this field
     * used when exporting form data from the document
     */
    public String getMapName(){
    	//TODO never called
    	return mapName;
    }

    /**
     * sets the default state from this fields appearance streams
     */
    public void setDefaultState(String state) {
        defaultState = state;
    }

    /**
     * sets the characterstic's for this form,
     * if <b>characterInt</b> is -1, the booleans are set to <b>newCharacteristicArray</b>,
     * otherwise, the boolean at index <b>characterInt</b>-1 is set to true
     */
    public void setCharacteristic(int characterInt, boolean[] newCharacteristicArray) {
        /**
         * 1 = invisible
         * 2 = hidden
         * 3 = print
         * 4 = nozoom
         * 5= norotate
         * 6= noview
         * 7 = read only (ignored by wiget)
         * 8 = locked
         * 9 = togglenoview
         */
        if (characterInt == -1) {
            characteristic = newCharacteristicArray;
        } else {
            characteristic[characterInt - 1] = true;
        }
    }

    /**
     * turns each individual characteristic on
     */
    public void setCharacteristic(int characterInt) {
        setCharacteristic(characterInt, null);
    }

    /**
     * sets the list of items
     * used with choice fields
     */
    public void setlistOfItems(String[] items) {
        itemsList = items;
    }

    /**
     * sets the top index
     * for the choice fields
     */
    public void setTopIndex(int[] index) {
        topIndex = index;
    }

    /**
     * sets the maximum text length
     */
    public void setMaxTextLength(int length) {
        maxTextLength = length;
    }

    /**
     * sets the bounding rectangle for this form
     */
    public void setBoundingRectangle(Rectangle rectangle) {
        rect = rectangle;
    }
    
    /**
     * return the bounding rectangle for this object
     */
    public Rectangle getBoundingRectangle(){
    	return rect;
    }

    /**
     * sets the type this form specifies
     */
    public void setType(int type) {
        this.type = type;
    }

    /**
     * sets the flag <b>pos</b> to value of <b>flag</b>
     */
    public void setFlag(int pos, boolean flag) {
        /*
          flags[1]=(flagValue & READONLY)==READONLY
          flags[2]=(flagValue & REQUIRED)==REQUIRED;
          flags[3]=(flagValue & NOEXPORT)==NOEXPORT;
          //FORMBUTTON
          flags[14]=(flagValue & NOTOGGLETOOFF)==NOTOGGLETOOFF;
          flags[15]=(flagValue & RADIO)==RADIO;
          flags[16]=(flagValue & PUSHBUTTON)==PUSHBUTTON;
          flags[25]=(flagValue & RADIOINUNISON)==RADIOINUNISON;//same as RICHTEXT
          //FORMTEXT
          flags[12]=(flagValue & MULTILINE)==MULTILINE;
          flags[13]=(flagValue & PASSWORD)==PASSWORD;
          flags[20]=(flagValue & FILESELECT)==FILESELECT;
          flags[22]=(flagValue & DONOTSPELLCHECK)==DONOTSPELLCHECK;
          flags[23]=(flagValue & DONOTSCROLL)==DONOTSCROLL;
          flags[25]=(flagValue & RICHTEXT)==RICHTEXT;//same as RADIOINUNISON
          //FORMCHOICE
          flags[17]=(flagValue & COMBO)==COMBO;
          flags[18]=(flagValue & EDIT)==EDIT;
          flags[19]=(flagValue & SORT)==SORT;
          flags[21]=(flagValue & MULTISELECT)==MULTISELECT;
          flags[26]=(flagValue & COMMITONSELCHANGE)==COMMITONSELCHANGE;
          flags[22]=(flagValue & DONOTSPELLCHECK)==DONOTSPELLCHECK;

          flags[24]=(flagValue & COMB)==COMB;
           */
        flags[pos - 1] = flag;
    }

    /**
     * sets the flags array to be <b>interactiveFlags</b>
     */
    public void setFlags(boolean[] interactiveFlags) {
        flags = interactiveFlags;
    }

    /**
     * returns the flags array (Ff in PDF)
     *  * all
     * 1=readonly - if set there is no interaction
     * 2=required - if set the field must have a value when submit-form-action occures
     * 3=noexport - if set the field must not be exported by a submit-form-action
     *
     * Choice fields
     * 18=combo - set its a combobox, else a list box
     * 19=edit - defines a comboBox to be editable
     * 20=sort - defines list to be sorted alphabetically
     * 22=multiselect - if set more than one items can be selected, else only one
     * 23=donotspellcheck - (only used on editable combobox) don't spell check
     * 27=commitOnselchange - if set commit the action when selection changed, else commit when user exits field
     *
     * text fields
     * 13=multiline - uses multipul lines else uses a single line
     * 14=password - a password is intended
     * 21=fileselect -text in field represents a file pathname to be submitted
     * 23=donotspellcheck - don't spell check
     * 24=donotscroll - once the field is full don't enter anymore text.
     * 25=comb - (only if maxlen is present, (multiline, password and fileselect are CLEAR)), the text is justified across the field to MaxLen
     * 26=richtext - use richtext format specified by RV entry in field dictionary
     *
     * button fields
     * 15=notoggletooff - (use in radiobuttons only) if set one button must always be selected
     * 16=radio - if set is a set of radio buttons
     * 17=pushbutton - if set its a push button
     * 	if neither 16 nor 17 its a check box
     * 26=radiosinunison - if set all radio buttons with the same on state are turned on and off in unison (same behaviour as html browsers)

     */
    public boolean[] getFieldFlags() {
        return flags;
    }

    /**
     * sets the default value
     */
    public void setDefaultValue(String value) {
        defaultValue = value;
    }

    /**
     * sets the entered action
     */
    public void setEaction(Object enteredData) {
        objE = enteredData;
    }

    public Object getEobj() {
        return objE;
    }

    /**
     * sets the exited action
     */
    public void setXaction(Object xData) {
        objX = xData;
    }

    public Object getXobj() {
        return objX;
    }

    /**
     * the normal off image
     * if only one state call this to set normalOffImage to be the default image
     */
    public void setNormalAppOff(BufferedImage image, String state) {
        normalOffState = state;
        normalOffImage = image;
        hasNormalOffImage = true;
        appearancesUsed = true;
    }

    /**
     * the on normal image
     */
    public void setNormalAppOn(BufferedImage image, String state) {
        normalOnState = state;
        normalOnImage = image;
        hasNormalOn = true;
        appearancesUsed = true;
    }

    /**
     * sets the rollover off image
     * if only one state call this to set rolloverOffImage to be the default image
     */
    public void setRolloverAppOff(BufferedImage image) {
        rolloverOffImage = image;
        hasRolloverOffImage = true;
        appearancesUsed = true;
    }

    /**
     * sets the rollover on image
     */
    public void setRolloverAppOn(BufferedImage image) {
        rolloverOnImage = image;
        hasRolloverOn = true;
        appearancesUsed = true;
    }

    /**
     * sets the down off image
     * if only one state call this to set downOffImage to be the default image
     */
    public void setDownAppOff(BufferedImage image) {
        downOffImage = image;
        hasDownOffImage = true;
        hasDownImages = true;
        appearancesUsed = true;
    }

    /**
     * sets the down on image
     */
    public void setDownAppOn(BufferedImage image) {
        downOnImage = image;
        hasDownImages = true;//check can be removed if speed needed
        appearancesUsed = true;
    }

    /**
     * sets the border color
     */
    public void setBorderColor(Color borderColor) {
        this.borderColor = borderColor;
    }

    /**
     * sets the background color for this form
     */
    public void setBackgroundColor(Color backgroundColor) {
        this.backgroundColor = backgroundColor;
    }

    /**
     * sets the normal caption for this form
     */
    public void setNormalCaption(String caption) {
        normalCaption = caption;
    }

    /**
     * sets the text positioning relative to the icon
     */
    public void setTextPosition(int positioning) {
        textPosition = positioning;
    }

    /**
     * sets whether there should be a down looking icon
     */
    public void setOffsetDownApp() {
        offsetDownIcon = true;
    }

    /**
     * sets whether a down icon should be used
     */
    public void setNoDownIcon() {
        noDownIcon = true;
    }

    /**
     * sets whether to invert the normal icon for the down icon
     */
    public void setInvertForDownIcon() {
        invertDownIcon = true;
    }

    /**
     * sets the rotation factor
     */
    public void setRotation(int rotate) {
        rotation = rotate;
    }
    
    /**
     * returnds to rotation of this field object, 
     * currently in stamp annotations only
     */
    public int getRotation(){
    	return rotation;
    }

    /**
     * sets the rollover caption
     */
    public void setRolloverCaption(String caption) {
        rolloverCaption = caption;
    }

    /**
     * sets the down caption
     */
    public void setDownCaption(String caption) {
        downCaption = caption;
    }

    /**
     * sets when the icon should be scaled
     */
    public void setWhenToScaleIcon(String scaleIcon) {
        whenToScaleIcon = scaleIcon;
    }

    /**
     * returns true if has normal of image
     */
    public boolean hasNormalOff() {
        return hasNormalOffImage;
    }

    /**
     * returns true if has rollover off image
     */
    public boolean hasRolloverOff() {
        return hasRolloverOffImage;
    }

    /**
     * returns true if has down off image
     */
    public boolean hasDownOff() {
        return hasDownOffImage;
    }

    /**
     * returns true if has one or more down images set
     */
    public boolean hasDownImage() {
        return hasDownImages;
    }

    /**
     * returns true if has a rollover on image
     */
    public boolean hasRolloverOn() {
        return hasRolloverOn;
    }

    /**
     * returns true if has a normal on image
     */
    public boolean hasNormalOn() {
        return hasNormalOn;
    }

    /**
     * sets the kid data for the radio button group to be setup
     */
    public void setKidData(Map data) {
        kidData = data;
    }

    /**
     * sets the C color for annotations
     */
    public void setCColor(Color newColor) {
        cColor = newColor;
    }

    /**
     * sets the contents for the annotation
     */
    public void setContents(String newString) {
        contents = newString;
    }

    /**
     * resolve what type of field <b>type</b> specifies
     * and return as String
     */
    public String resolveType(int type) {

        if (type == FORMBUTTON)
            return "Button";
        else if (type == FORMCHOICE)
            return "Choice";
        else if (type == FORMTEXT)
            return "Text";
        else if (type == ANNOTPOPUP)
            return "PopUp";
        else if (type == ANNOTSQUARE)
            return "Square";
        else if (type == ANNOTTEXT)
            return "Text Annot";


        return null;
    }

    /**
     * sets the show option for the annotation
     */
    public void setOpenState(boolean newBoolean) {
        show = newBoolean;
    }

    /**
     * returns the already setup graphics stat, if not setup creates a new one
     * this allows the stroke to be setup prior to drawing the shape
     */
    public GraphicsState getGraphicsState() {
        if (graphicsState == null)
            graphicsState = new org.jpedal.objects.GraphicsState();
        return graphicsState;
    }

    /**
     * sets the internal bounding box fro the annotation
     */
    public void setInternalBounds(float left, float top, float right, float bottom) {
        int x = (int) (left + 0.5);
        int y = (int) (top + 0.5);
        int w = (int) (rect.width - (left + right + 0.5));
        int h = (int) (rect.height - (top + bottom + 0.5));
        internalBounds = new Rectangle(x, y, w, h);
    }

    /**
     * sets the popup title bar text
     */
    public void setPopupTitle(String title) {
        popupTitle = title;
    }
    
    /**
     * set the page number for this form
     */
    public void setPageNumber(Object field) {
        if (field instanceof String) {
            try{
            	pageNumber = Integer.parseInt((String) field);
            }catch(NumberFormatException e){
            	pageNumber = 1;
            }
        } else {
            LogWriter.writeFormLog("{FormObject.setPageNumber} pagenumber being set to UNKNOWN type", false);
        }
    }
    
    /**
     * set the page number for this form
     */
    public void setPageNumber(int number) {
            pageNumber = number;        
    }

    public void setStateToCheck(String stateTocheck) {
        this.stateTocheck = stateTocheck;

    }

    public void overwriteWith(FormObject form) {
        if (form == null)
            return;

        if (form.rotation != 0)
            rotation = form.rotation;
        if (form.userName != null)
            userName = form.userName;
        if (form.fieldName != null)
            fieldName = form.fieldName;
        if (form.annotName != null)
            annotName = form.annotName;
        
        if (form.parent != null)
            parent = form.parent;
        if (form.mapName != null)
            mapName = form.mapName;
        if (form.characteristic != null)
            characteristic = form.characteristic;
        if (form.rect != null)
            rect = form.rect;
        if (form.type != -1)
            type = form.type;
        if (form.flags != null)
            flags = form.flags;

        if (form.topIndex != null)
            topIndex = form.topIndex;
        if (form.selectedItem != null)
            selectedItem = form.selectedItem;
        if (form.itemsList != null)
            itemsList = form.itemsList;

        if (form.objA != null)
            objA = form.objA;
        if (form.objE != null)
            objE = form.objE;
        if (form.objX != null)
            objX = form.objX;


        if (form.PDFobj != null)
            PDFobj = form.PDFobj;

        if (form.border != null)
            border = form.border;
        if (form.borderColor != null)
            borderColor = form.borderColor;
        if (form.backgroundColor != null)
            backgroundColor = form.backgroundColor;

        if (form.alignment != -1)
            alignment = form.alignment;
        if (form.textColor != null)
            textColor = form.textColor;
        if (form.textFont != null)
            textFont = form.textFont;
        if (form.textSize != -1)
            textSize = form.textSize;
        if (form.textString!=null)
            textString = form.textString;
        if (form.maxTextLength != -1)
            maxTextLength = form.maxTextLength;
        if (form.textPosition != -1)
            textPosition = form.textPosition;
        if (form.defaultValue != null)
            defaultValue = form.defaultValue;

        if (form.normalCaption != null)
            normalCaption = form.normalCaption;
        if (form.rolloverCaption != null)
            rolloverCaption = form.rolloverCaption;
        if (form.downCaption != null)
            downCaption = form.downCaption;

        if (form.appearancesUsed)
            appearancesUsed = form.appearancesUsed;
        if (form.offsetDownIcon)
            offsetDownIcon = form.offsetDownIcon;
        if (form.noDownIcon)
            noDownIcon = form.noDownIcon;
        if (form.invertDownIcon)
            invertDownIcon = form.invertDownIcon;
        if (form.whenToScaleIcon != null)
            whenToScaleIcon = form.whenToScaleIcon;
        if (form.defaultState != null)
            defaultState = form.defaultState;
        if (form.onState != null)
            onState = form.onState;
        if (form.currentState != null)
            currentState = form.currentState;
        if (form.normalOffImage != null)
            normalOffImage = form.normalOffImage;
        if (form.normalOnImage != null)
            normalOnImage = form.normalOnImage;
        if (form.rolloverOffImage != null)
            rolloverOffImage = form.rolloverOffImage;
        if (form.rolloverOnImage != null)
            rolloverOnImage = form.rolloverOnImage;
        if (form.downOffImage != null)
            downOffImage = form.downOffImage;
        if (form.downOnImage != null)
            downOnImage = form.downOnImage;
        if (form.hasNormalOffImage)
            hasNormalOffImage = form.hasNormalOffImage;
        if (form.hasRolloverOffImage)
            hasRolloverOffImage = form.hasRolloverOffImage;
        if (form.hasDownOffImage)
            hasDownOffImage = form.hasDownOffImage;
        if (form.hasDownImages)
            hasDownImages = form.hasDownImages;
        if (form.hasRolloverOn)
            hasRolloverOn = form.hasRolloverOn;
        if (form.hasNormalOn)
            hasNormalOn = form.hasNormalOn;
        if (form.kidData != null)
            kidData = form.kidData;
        if (form.pageNumber != -1)
            pageNumber = form.pageNumber;

        //annotations
        if (form.cColor != null)
            cColor = form.cColor;
        if (form.contents != null)
            contents = form.contents;
        if (form.show)
            show = form.show;
        if (form.internalBounds != null)
            internalBounds = form.internalBounds;
        if (form.popupTitle != null)
            popupTitle = form.popupTitle;

        if (form.stateTocheck != null)
            stateTocheck = form.stateTocheck;
    }

    public Object getAobj() {
        return objA;
    }

    public void setAaction(Object data) {
        objA = data;
    }
    
    public void setPopupObj(Object data){
    	popupObj = data;
    }
    
    public Object getPopupObj(){
    	return popupObj;
    }

	/**
     * See also {@link org.jpedal.objects.acroforms.formData.FormObject#getAnnotName()}
     * and {@link org.jpedal.objects.acroforms.formData.FormObject#getUserName()}
     * @return the full field name for this form
     */
	public String getFieldName() {
		return fieldName;
	}
	
	/**
	 * @return the annot name for this form
	 */
	public String getAnnotName() {
		return annotName;
	}

	/**
	 * @return the title of the popup window associated to this field
	 */
	public String getPopupTitle() {
		return popupTitle;
	}

	/**
	 * @return the page this field is associated to
	 */
	public int getPageNumber() {
		return pageNumber;
	}

	/**
	 * @return the currently selected State of this field at time of opening.
	 */
	public String getCurrentState() {
		//@MARK could we change this class so the current state is updated, and make this like 
		//a skeleton of the field that is always used, and can be extended by the right form 
		//implementation, then we could extend for swing and canoo.
		return currentState;
	}

	/**
	 * @return the on state for this field
	 */
	public String getOnState() {
		return onState;
	}

	/**
	 * @return the type of form field this is
	 */
	public int getType() {
		return type;
	}

	/**
	 * @return the characteristics for this field
	 */
	public boolean[] getCharacteristics() {
		return characteristic;
	}

	/**
	 * @return the data of any kids associated to this field
	 */
	public Map getKidData() {
		return kidData;
	}

	/**
	 * @return userName for this field (TU value)
	 */
	public String getUserName() {
		return userName;
	}

	/**
	 * @return the state to check for this field,
	 * this is used towards identifying which of a set of radio buttons is on,
	 */
	public String getStateTocheck() {
		return stateTocheck;
	}

	/**
	 * @return the default text size for this field
	 */
	public int getTextSize() {
		return textSize;
	}

	/**
	 * @return the values map for this field,
	 * map that references the display value from the export values
	 */
	public Map getValuesMap() {
		return valuesMap;
	}

	/**
	 * @return the default value for this field
	 */
	public String getDefaultValue() {
		return defaultValue;
	}

	/**
	 * @return the items array list
	 */
	public String[] getItemsList() {
		return itemsList;
	}

	/**
	 * @return the selected Item for this field
	 */
	public String getSelectedItem() {
		
		//if no value set but selection, use that
		if(selectedItem==null && topIndex!=null ){
			String[] items= itemsList;
	          int itemSelected=topIndex[0];
	          if(items!=null && itemSelected>-1 && itemSelected<items.length)
	        	  return items[itemSelected];
	          else
	        	  return null;
		}else
			return selectedItem;
	}

	/**
	 * @return the top index, or item that is visible in the combobox or list first.
	 */
	public int[] getTopIndex() {
		return topIndex;
	}

	/**
	 * @return the text string for this field - if no value set but a default (DV value)
     * set, return that.
	 */
	public String getTextString() {
		if(defaultValue!=null && textString==null)
        	return defaultValue;
        else
        	return textString;
	}

	/**
	 * @return the maximum length of the text in the field
	 */
	public int getMaxTextLength() {
		return maxTextLength;
	}

	/**
	 * @return the normal caption for this button,
	 * the caption displayed when nothing is interacting with the icon, and at all other times unless 
	 * a down and/or rollover caption is present
	 */
	public String getNormalCaption() {
		return normalCaption;
	}

	/**
	 * @return the down caption,
	 * caption displayed when the button is down/pressed
	 */
	public String getDownCaption() {
		return downCaption;
	}

	/**
	 * @return the rollover caption,
	 * the caption displayed when the user rolls the mouse cursor over the button
	 */
	public String getRolloverCaption() {
		return rolloverCaption;
	}

	/**
	 * @return whether or not appearances are used in this field
	 */
	public boolean isAppearancesUsed() {
		return appearancesUsed;
	}

	/**
	 * @return the position of the view of the text in this field
	 */
	public int getTextPosition() {
		return textPosition;
	}

	/**
	 * @return the default state of this field,
	 * the state to return to when the field is reset
	 */
	public String getDefaultState() {
		return defaultState;
	}

	/**
	 * @return the normal on state for this field
	 */
	public String getNormalOnState() {
		return normalOnState;
	}
	
	/**
	 * @return the normal off state for this field
	 */
	public String getNormalOffState() {
		return normalOffState;
	}

	/**
	 * @return the normal off image for this field
	 */
	public BufferedImage getNormalOffImage() {
		return normalOffImage;
	}

	/**
	 * @return the normal On image for this field
	 */
	public BufferedImage getNormalOnImage() {
		return normalOnImage;
	}

	/**
	 * @return if this field has not got a down icon
	 */
	public boolean hasNoDownIcon() {
		return noDownIcon;
	}

	/**
	 * @return whether this field has a down icon as an offset of the normal icon
	 */
	public boolean hasOffsetDownIcon() {
		return offsetDownIcon;
	}

	/**
	 * @return whether this field has a down icon as an inverted image of the normal icon
	 */
	public boolean hasInvertDownIcon() {
		return invertDownIcon;
	}

	/**
	 * @return the down off image for this field
	 */
	public BufferedImage getDownOffImage() {
		return downOffImage;
	}

	/**
	 * @return the down on image for this field
	 */
	public BufferedImage getDownOnImage() {
		return downOnImage;
	}

	/**
	 * @return the rollover image for this field,
	 * the image displayed when the user moves the mouse over the field
	 */
	public BufferedImage getRolloverOffImage() {
		return rolloverOffImage;
	}

	/**
	 * @return the rollover on image,
	 * the image displayed when the user moves the mouse over the field, when in the on state
	 */
	public BufferedImage getRolloverOnImage() {
		return rolloverOnImage;
	}

	/**
	 * @return the text font for this field
	 */
	public Font getTextFont() {
		return textFont;
	}

	/**
	 * @return the text color for this field
	 */
	public Color getTextColor() {
		return textColor;
	}

	/**
	 * @return the border color for this field
	 */
	public Color getBorderColor() {
		return borderColor;
	}

	/**
	 * @return the border style for this field
	 */
	public Object getBorder() {
		return border;
	}

	/**
	 * @return the background color for this field
	 */
	public Color getBackgroundColor() {
		return backgroundColor;
	}

	/**
	 * @param values to be stored as the Map of values for this field
	 */
	public void setValuesMap(Map values) {
		valuesMap = values; 
	}

	/**
	 * @return the contents for the text display on an annotation
	 */
	public String getContents() {
		return contents;
	}

    /**
     * used internally to set status while parsing - should not be called
     * @param popup
     */
    public void setActionFlag(int popup) {
        popupFlag=popup;
	}

    /**
     * get status found during decode
     */
     public int getActionFlag() {
        return popupFlag;
    }

	/**
	 * return true if the popup component has been built
	 */
	public boolean isPopupBuilt() {
		return popupBuilt;
	}

	/**
	 * store the built popup component for use next time
	 * and set popupBuilt to true.
	 */
	public void setPopupBuilt(PdfSwingPopup popup) {
		if(popup==null)
			return;
		
		popupObj = popup;
		popupBuilt = true;
	}


}

