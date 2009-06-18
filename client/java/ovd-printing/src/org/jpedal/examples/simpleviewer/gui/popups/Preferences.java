package org.jpedal.examples.simpleviewer.gui.popups;

import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Component;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Insets;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.KeyEvent;
import java.awt.event.KeyListener;

import javax.swing.*;

import org.jpedal.examples.simpleviewer.gui.GUI;
import org.jpedal.examples.simpleviewer.gui.SwingGUI;
import org.jpedal.utils.Messages;
import org.jpedal.PdfDecoder;

import com.l2fprod.common.swing.JButtonBar;

public class Preferences extends JPanel {
	
	//Window Components
	JFrame jf = new JFrame("JPedal PDF Preferences");
	
	JButton confirm = new JButton("OK");
	
	JButton cancel = new JButton("Cancel");
	
	//Settings Fields Components
	
	//DPI viewer value
	JTextField dpi_Input;
	String dpiDefaultValue = "96";
	
	//Search window display style
	JComboBox searchStyle;
	int searchStyleDefaultValue = 1;
	
	//Show border around page
	JCheckBox border;
	int borderDefaultValue = 1;
	
	//Show border around page
	JCheckBox downloadWindow;
	boolean downloadWindowDefaultValue = false;
	
	//perform automatic update check
	JCheckBox update = new JCheckBox("Check for updates on startup");
	boolean updateDefaultValue = true;
	
	//max no of multiviewers
	JTextField maxMultiViewers;
	String maxMultiViewersDefaultValue = "20";
	
	//Set autoScroll when mouse at the edge of page
	JCheckBox autoScroll;
	boolean scrollDefaultValue = false;
	
	//Set default page layout
	JComboBox pageLayout = new JComboBox(new String[]{"Single Page","Continuous","Continuous Facing", "Facing"});
	int pageLayoutDefaultValue = 1;

//	private SwingGUI swingGUI;
	
	private JFrame parent;
	
	private boolean preferencesSetup=false;
	
	/**
	 * showPreferenceWindow()
	 *
	 * Ensure current values are loaded then display window.
	 * @param swingGUI 
	 */
	public void showPreferenceWindow(SwingGUI swingGUI){
		
		if(!preferencesSetup){
			preferencesSetup=true;
			
			createPreferenceWindow(swingGUI);
		}
//		this.swingGUI = swingGUI;
		jf.setLocationRelativeTo(parent);
		jf.setVisible(true);
	}
	
	/**
	 * createPreferanceWindow(final GUI gui)
	 * Set up all settings fields then call the required methods to build the window
	 * 
	 * @param gui - Used to allow any changed settings to be saved into an external properties file.
	 * 
	 */
	private void createPreferenceWindow(final GUI gui){
		
		dpi_Input = new JTextField(dpiDefaultValue);
		
		maxMultiViewers = new JTextField(maxMultiViewersDefaultValue);
		
		searchStyle = new JComboBox(new String[]{Messages.getMessage("PageLayoutViewMenu.WindowSearch"),Messages.getMessage("PageLayoutViewMenu.TabbedSearch"),Messages.getMessage("PageLayoutViewMenu.MenuSearch")});
		pageLayout = new JComboBox(new String[]{Messages.getMessage("PageLayoutViewMenu.SinglePage"),Messages.getMessage("PageLayoutViewMenu.Continuous"),Messages.getMessage("PageLayoutViewMenu.Facing"),Messages.getMessage("PageLayoutViewMenu.ContinousFacing")});
		
		autoScroll = new JCheckBox(Messages.getMessage("PdfViewerViewMenuAutoscrollSet.text"));
		
		border = new JCheckBox(Messages.getMessage("PageLayoutViewMenu.Borders_Show"));
		
		downloadWindow = new JCheckBox(Messages.getMessage("PageLayoutViewMenu.DownloadWindow_Show"));
		
		jf.getContentPane().setLayout(new BorderLayout());
		jf.getContentPane().add(this,BorderLayout.CENTER);
		jf.pack();
		jf.setSize(500, 400);
		
		/*
		 * Listeners that are reqired for each setting field
		 */
		confirm.addActionListener(new ActionListener(){
			public void actionPerformed(ActionEvent arg0) {
				dpiDefaultValue = dpi_Input.getText();
				int dpi = Integer.parseInt(dpi_Input.getText());
				int style = searchStyleDefaultValue = searchStyle.getSelectedIndex();
				int pageMode = pageLayoutDefaultValue = (pageLayout.getSelectedIndex()+1);
				int borderStyle = borderDefaultValue = 0;
				
				if(border.isSelected()){
					borderStyle = borderDefaultValue = 1;
				}
				
				updateDefaultValue = update.isSelected();

				boolean toggleScroll = scrollDefaultValue = autoScroll.isSelected();

				int maxNoOfMultiViewers = Integer.parseInt(maxMultiViewers.getText());
				
				gui.setPreferences(dpi, style, borderStyle, toggleScroll, pageMode, updateDefaultValue, maxNoOfMultiViewers, downloadWindow.isSelected());

				jf.setVisible(false);
			}
		});

		cancel.addActionListener(new ActionListener(){
			public void actionPerformed(ActionEvent arg0) {
				jf.setVisible(false);
			}
		});
		
		KeyListener numericalKeyListener = new KeyListener(){

			boolean consume = false;

			public void keyPressed(KeyEvent e) {
				consume = false;
				if((e.getKeyChar()<'0' || e.getKeyChar()>'9') && (e.getKeyCode()!=8 || e.getKeyCode()!=127))
					consume = true;
			}

			public void keyReleased(KeyEvent e) {}

			public void keyTyped(KeyEvent e) {
				if(consume)
					e.consume();
			}

		};
		dpi_Input.addKeyListener(numericalKeyListener);
		maxMultiViewers.addKeyListener(numericalKeyListener);
		
		searchStyle.setSelectedIndex(searchStyleDefaultValue);
		dpi_Input.setText(dpiDefaultValue);
		if(borderDefaultValue==1)
			border.setSelected(true);
		else
			border.setSelected(false);
		
		if(downloadWindowDefaultValue==true)
			downloadWindow.setSelected(true);
		else
			downloadWindow.setSelected(false);
		
		autoScroll.setSelected(scrollDefaultValue);
		
		update.setSelected(updateDefaultValue);
		
		setLayout(new BorderLayout());

		JButtonBar toolbar = new JButtonBar(JButtonBar.VERTICAL);

        if(PdfDecoder.isRunningOnMac)
            toolbar.setPreferredSize(new Dimension(120,0));

        add(new ButtonBarPanel(toolbar), BorderLayout.CENTER);
		
		toolbar.setBorder(BorderFactory.createMatteBorder(0, 0, 0, 1, Color.gray));
		
		JPanel buttonPanel = new JPanel();
		buttonPanel.setLayout(new BoxLayout(buttonPanel, BoxLayout.X_AXIS));
		
		Dimension dimension = new Dimension(5,40);
		Box.Filler filler = new Box.Filler(dimension, dimension, dimension);

		confirm.setPreferredSize(cancel.getPreferredSize());

		buttonPanel.add(Box.createHorizontalGlue());
		
		buttonPanel.add(confirm);
		getRootPane().setDefaultButton(confirm);
		
		buttonPanel.add(filler);
		buttonPanel.add(cancel);
		buttonPanel.add(filler);
		
		buttonPanel.setBorder(BorderFactory.createMatteBorder(1, 0, 0, 0, Color.gray));
		
		add(buttonPanel, BorderLayout.SOUTH);
	}
	
	
	public Preferences() {
		
	}

	/*
	 * Following methods used to load default values when application starts.
	 * 
	 */
	public void setAutoScrollDefaultValue(boolean autoScrollDefaultValue) {
		this.scrollDefaultValue = autoScrollDefaultValue;
	}

	public void setBorderDefaultValue(int borderDefaultValue) {
		this.borderDefaultValue = borderDefaultValue;
	}
	
	
	public void setdownloadWindowDefaultValue(boolean downloadWindowDefaultValue) {
		this.downloadWindowDefaultValue = downloadWindowDefaultValue;
	}
	
	public void setUpdateDefaultValue(boolean updateDefaultValue) {
		this.updateDefaultValue = updateDefaultValue;
	}
	
	public void setMaxMultiViewersDefaultValue(String maxMultiViewersDefaultValue) {
		this.maxMultiViewersDefaultValue = maxMultiViewersDefaultValue;
	}

	public void setDpiDefaultValue(String dpiDefaultValue) {
		this.dpiDefaultValue = dpiDefaultValue;
	}

	public void setSearchStyleDefaultValue(int searchStyleDefaultValue) {
		this.searchStyleDefaultValue = searchStyleDefaultValue;
	}
	
	public void setPageLayoutDefaultValue(int pageLayoutDefaultValue) {
		if(pageLayoutDefaultValue>pageLayout.getItemCount()+1)
			pageLayoutDefaultValue = 1;
		this.pageLayoutDefaultValue = pageLayoutDefaultValue;
	}
	
	class ButtonBarPanel extends JPanel {

		private Component currentComponent;

		public ButtonBarPanel(JButtonBar toolbar) {
			setLayout(new BorderLayout());

			add(toolbar, BorderLayout.WEST);

			ButtonGroup group = new ButtonGroup();

			addButton("Display", "/org/jpedal/examples/simpleviewer/res/display.png", createDisplaySettings(), toolbar, group);

			addButton("Viewer", "/org/jpedal/examples/simpleviewer/res/viewer.png", createViewerSettings(), toolbar, group);

			addButton("Updates", "/org/jpedal/examples/simpleviewer/res/updates.png", createUpdateSettings(), toolbar, group);

			addButton("MulitViewer", "/org/jpedal/examples/simpleviewer/res/multiviewer.png", createMultiViewerSettings(), toolbar, group);
		}

		private JPanel makePanel(String title) {
			JPanel panel = new JPanel(new BorderLayout());
			JLabel top = new JLabel(title);
			top.setBorder(BorderFactory.createEmptyBorder(4, 4, 4, 4));
			top.setFont(top.getFont().deriveFont(Font.BOLD));
			top.setOpaque(true);
			top.setBackground(panel.getBackground().brighter());
			panel.add(top, BorderLayout.NORTH);
			panel.setPreferredSize(new Dimension(400, 300));
			panel.setBorder(BorderFactory.createEmptyBorder(4, 4, 4, 4));
			return panel;
		}

		private JPanel createDisplaySettings(){

			JPanel panel = makePanel("Display");
			
			JPanel pane = new JPanel();
	        pane.setLayout(new GridBagLayout());
			GridBagConstraints c = new GridBagConstraints();
			c.fill = GridBagConstraints.BOTH;
			
			c.insets = new Insets(10,0,0,5);
			c.weighty = 0;
			c.weightx = 0;
			c.gridx = 0;
			c.gridy = 0;
			JLabel label = new JLabel(Messages.getMessage("PdfViewerViewMenu.Dpi"));
			label.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
			pane.add(label, c);
			
			c.insets = new Insets(10,0,0,0);
			c.weightx = 1;
			c.gridx = 1;
			c.gridy = 0;
			pane.add(dpi_Input, c);
			
			c.gridwidth = 2;
			c.gridx = 0;
			c.gridy = 1;
			border.setMargin(new Insets(0,0,0,0));
			border.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
			pane.add(border, c);
			
			c.gridwidth = 2;
			c.gridx = 0;
			c.gridy = 2;
			downloadWindow.setMargin(new Insets(0,0,0,0));
			downloadWindow.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
			pane.add(downloadWindow, c);
			
			c.weighty = 1;
			c.gridx = 0;
			c.gridy = 3;
			pane.add(Box.createVerticalGlue(), c);
			//pane.setBorder(BorderFactory.createTitledBorder(BorderFactory.createLineBorder(new Color(0.3f,0.5f,1f), 1), "Display Settings"));

			panel.add(pane, BorderLayout.CENTER);
			
			return panel;
		}
		
		/*
		 * Creates a pane holding all Viewer settings (e.g Search Style, auto scrolling, etc)
		 */
		private JPanel createViewerSettings(){
			
			
			JPanel panel = makePanel("Viewer");
			
			JPanel pane = new JPanel();
	        pane.setLayout(new GridBagLayout());
			GridBagConstraints c = new GridBagConstraints();
			c.fill = GridBagConstraints.BOTH;
			
			c.insets = new Insets(10,0,0,5);
			c.weighty = 0;
			c.weightx = 0;
			c.gridx = 0;
			c.gridy = 0;
			JLabel label = new JLabel(Messages.getMessage("PageLayoutViewMenu.SearchLayout"));
			label.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
			pane.add(label, c);
			
			c.insets = new Insets(10,0,0,0);
			c.weightx = 1;
			c.gridx = 1;
			c.gridy = 0;
			pane.add(searchStyle, c);
			
			c.insets = new Insets(10,0,0,5);
			c.weighty = 0;
			c.weightx = 0;
			c.gridx = 0;
			c.gridy = 1;
			JLabel label1 = new JLabel(Messages.getMessage("PageLayoutViewMenu.PageLayout"));
			label1.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
			pane.add(label1, c);
			
			c.insets = new Insets(10,0,0,0);
			c.weightx = 1;
			c.gridx = 1;
			c.gridy = 1;
			pageLayout.setSelectedIndex((pageLayoutDefaultValue-1));
			pane.add(pageLayout, c);
			
			c.gridwidth = 2;
			c.gridx = 0;
			c.gridy = 2;
			autoScroll.setMargin(new Insets(0,0,0,0));
			autoScroll.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
			pane.add(autoScroll, c);
			
			c.weighty = 1;
			c.gridx = 0;
			c.gridy = 3;
			pane.add(Box.createVerticalGlue(), c);
			//pane.setBorder(BorderFactory.createTitledBorder(BorderFactory.createLineBorder(new Color(0.3f,0.5f,1f), 1), "Display Settings"));

			panel.add(pane, BorderLayout.CENTER);
			
			return panel;
		}
		
		/*
		 * Creates a pane holding update settings
		 */
		private JPanel createUpdateSettings() {
			
			JPanel panel = makePanel("Updates");
			
			JPanel pane = new JPanel();
	        pane.setLayout(new GridBagLayout());
			GridBagConstraints c = new GridBagConstraints();
			c.fill = GridBagConstraints.BOTH;
			
			c.insets = new Insets(10,0,0,0);
			c.weighty = 0;
			c.weightx = 1;
			c.gridwidth = 2;
			c.gridx = 0;
			c.gridy = 0;
			update.setMargin(new Insets(0,0,0,0));
			update.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
			pane.add(update, c);
			
			c.weighty = 1;
			c.gridx = 0;
			c.gridy = 2;
			pane.add(Box.createVerticalGlue(), c);
			//pane.setBorder(BorderFactory.createTitledBorder(BorderFactory.createLineBorder(new Color(0.3f,0.5f,1f), 1), "Display Settings"));

			panel.add(pane, BorderLayout.CENTER);
			
			return panel;
		}
		
		/*
		 * Creates a pane holding MultiViewer settings
		 */
		private JPanel createMultiViewerSettings() {
			
			
			
			
			JPanel panel = makePanel("MultiViewer");
			
			JPanel pane = new JPanel();
	        pane.setLayout(new GridBagLayout());
			GridBagConstraints c = new GridBagConstraints();
			c.fill = GridBagConstraints.BOTH;
			
			c.insets = new Insets(10,0,0,5);
			c.weighty = 0;
			c.weightx = 0;
			c.gridx = 0;
			c.gridy = 0;
			JLabel label = new JLabel("Maximum number of MultiViewer Windows");
			label.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
			pane.add(label, c);
			
			c.insets = new Insets(10,0,0,0);
			c.weightx = 1;
			c.gridx = 1;
			c.gridy = 0;
			pane.add(maxMultiViewers, c);
			
			c.gridwidth = 2;
			c.weighty = 1;
			c.gridx = 0;
			c.gridy = 1;
			pane.add(Box.createVerticalGlue(), c);
			//pane.setBorder(BorderFactory.createTitledBorder(BorderFactory.createLineBorder(new Color(0.3f,0.5f,1f), 1), "Display Settings"));
//
			panel.add(pane, BorderLayout.CENTER);
			
			return panel;
			
			
//			pane.setLayout(new GridBagLayout());
//			GridBagConstraints c = new GridBagConstraints();
//			pane.setPreferredSize(new Dimension(250,100));
//			pane.setMinimumSize(new Dimension(250,100));
//			c.insets = new Insets(10,0,10,0);
////			c.ipadx = 10;
////			c.ipady = 10;
//			c.fill = GridBagConstraints.BOTH;
//			c.gridx = 0;
//			c.gridy = 0;
//			pane.add(new JLabel("Maximum number of MultiViewer Windows "), c);
//			
//			c.gridx = 2;
//			c.gridy = 0;
//			pane.add(maxMultiViewers, c);
//
//			pane.setBorder(BorderFactory.createTitledBorder(BorderFactory.createLineBorder(new Color(0.3f,0.5f,1f), 1), "MultiViewer Settings"));
//			
//			return pane;
		}
		
		private void show(Component component) {
			if (currentComponent != null) {
				remove(currentComponent);
			}
			
			add("Center", currentComponent = component);
			revalidate();
			repaint();
		}

		private void addButton(String title, String iconUrl, final Component component, JButtonBar bar, ButtonGroup group) {
			Action action = new AbstractAction(title, new ImageIcon(getClass().getResource(iconUrl))) {
				public void actionPerformed(ActionEvent e) {
					show(component);
				}
			};

			JToggleButton button = new JToggleButton(action);

            if(PdfDecoder.isRunningOnMac)
                button.setHorizontalAlignment(AbstractButton.LEFT);

            bar.add(button);

			group.add(button);

			if (group.getSelection() == null) {
				button.setSelected(true);
				show(component);
			}
		}
		
		
	}

	public void setParent(JFrame parent) {
		this.parent = parent;
	}
	
}


