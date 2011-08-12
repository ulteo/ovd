/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

package org.ulteo.gui;

import java.awt.Component;
import java.awt.Container;
import java.awt.Cursor;
import java.awt.GraphicsDevice;
import java.awt.GraphicsEnvironment;
import java.awt.GridBagConstraints;
import java.awt.Image;
import java.awt.Rectangle;
import java.awt.Toolkit;
import java.awt.Window;
import java.net.URL;
import java.util.List;
import javax.swing.AbstractButton;
import javax.swing.ImageIcon;
import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JDialog;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPasswordField;
import javax.swing.JSlider;
import javax.swing.WindowConstants;
import javax.swing.text.JTextComponent;
import org.ulteo.Logger;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.utils.jni.WorkArea;

public class GUIActions {
	private static GUIActions Actions = new GUIActions();

	public static Image DEFAULT_APP_ICON = null;
	public static Image ULTEO_ICON = null;
	public static Rectangle SCREEN_BOUNDS = null;

	static {
		URL url = GUIActions.class.getClassLoader().getResource("pics/default_icon.png");
		if (url == null) {
			Logger.error("Weird. The icon pics/default_icon.png was not found in the jar");
		}
		else {
			DEFAULT_APP_ICON = Toolkit.getDefaultToolkit().getImage(url);
		}
	}

	private static void initUlteoIcon() {
		if (ULTEO_ICON != null)
			return;

		URL url = GUIActions.class.getClassLoader().getResource("pics/ulteo.png");
		if (url == null) {
			Logger.error("Weird. The icon pics/ulteo.png was not found in the jar");
			return;
		}

		ULTEO_ICON = Toolkit.getDefaultToolkit().getImage(url);
	}

	private static void initScreenBounds() {
		if (SCREEN_BOUNDS != null)
			return;

		SCREEN_BOUNDS = WorkArea.getWorkAreaSize();
	}

	/* SetVisible */
	public static Runnable setVisible(Component component_, boolean visible_) {
		return Actions.new SetVisible(component_, visible_);
	}

	private class SetVisible implements Runnable {
		private Component component = null;
		private boolean visible;

		public SetVisible(Component component_, boolean visible_) {
			this.component = component_;
			this.visible = visible_;
		}

		public void run() {
			if (this.component == null)
				return;

			this.component.setVisible(this.visible);
		}

	}

	/* SetAlwaysOnTop */
	public static Runnable setAlwaysOnTop(Window wnd_, boolean top_) {
		return Actions.new SetAlwaysOnTop(wnd_, top_);
	}

	private class SetAlwaysOnTop implements Runnable {
		private Window window = null;
		private boolean top;

		public SetAlwaysOnTop(Window wnd_, boolean top_) {
			this.window = wnd_;
			this.top = top_;
		}

		public void run() {
			if (this.window == null)
				return;

			if (this.window.isAlwaysOnTopSupported())
				this.window.setAlwaysOnTop(this.top);
			else
				Logger.warn("Always on top not supported");
		}

	}

	/* RequestFocus */
	public static Runnable requestFocus(Component component) {
		return Actions.new RequestFocus(component);
	}

	private class RequestFocus implements Runnable {
		private Component component = null;

		public RequestFocus(Component component_) {
			this.component = component_;
		}

		public void run() {
			if (this.component == null)
				return;

			this.component.requestFocus();
			if (! (this.component instanceof Window))
				this.component.requestFocusInWindow();
		}

	}

	/* DisposeWindow */
	public static Runnable disposeWindow(Window wnd_) {
		return Actions.new DisposeWindow(wnd_);
	}

	private class DisposeWindow implements Runnable {
		private Window wnd = null;

		public DisposeWindow(Window wnd_) {
			this.wnd = wnd_;
		}

		public void run() {
			setVisible(this.wnd, false).run();
			this.wnd.dispose();
		}
	}

	/* SetIconImage */
	public static Runnable setIconImage(Window wnd, Image icon) {
		return Actions.new SetIconImage(wnd, icon);
	}

	private class SetIconImage implements Runnable {
		private Window wnd = null;
		private Image icon = null;

		public SetIconImage(Window wnd_, Image icon_) {
			this.wnd = wnd_;
			this.icon = icon_;
		}

		public void run() {
			if (this.wnd == null)
				return;

			if (this.icon == null) {
				GUIActions.initUlteoIcon();
				this.icon = ULTEO_ICON;
			}

			this.wnd.setIconImage(this.icon);
		}
	}

	/* CreateDialog */
	public static Runnable createDialog(String message, String title) {
		return Actions.new CreateDialog(message, title, JOptionPane.INFORMATION_MESSAGE, JOptionPane.DEFAULT_OPTION, null);
	}
	public static Runnable createDialog(String message, String title, int messageType, int optionType) {
		return Actions.new CreateDialog(message, title, messageType, optionType, null);
	}
	public static Runnable createDialog(String message, String title, int messageType, int optionType, Image icon) {
		return Actions.new CreateDialog(message, title, messageType, optionType, icon);
	}

	private class CreateDialog implements Runnable {
		private String message = null;
		private String title = null;
		private int messageType = JOptionPane.INFORMATION_MESSAGE;
		private int optionType = JOptionPane.DEFAULT_OPTION;
		private Image icon = null;

		public CreateDialog(String message_, String title_, int messageType_, int optionType_, Image icon_) {
			this.message = message_;
			this.title = title_;
			this.messageType = messageType_;
			this.optionType = optionType_;
			this.icon = icon_;
		}

		public void run() {
			JOptionPane pane = new JOptionPane(this.message, this.messageType, this.optionType);
			JDialog dialog = pane.createDialog(this.title);
			dialog.setDefaultCloseOperation(WindowConstants.DISPOSE_ON_CLOSE);
			
			setIconImage(dialog, this.icon).run();
			setVisible(dialog, true).run();
			requestFocus(dialog).run();
			
			// Wait the user click before dispose
			pane.getValue();
			dialog.dispose();
		}
	}
	
	/* SetCursor */
	public static Runnable setCursor(Component component_, Cursor cursor_) {
		return Actions.new SetCursor(component_, cursor_);
	}
	
	private class SetCursor implements Runnable {
		private Component component = null;
		private Cursor cursor = null;
		
		public SetCursor(Component component_, Cursor cursor_) {
			this.component = component_;
			this.cursor = cursor_;
		}
		
		public void run() {
			if (this.component == null)
				return;
			
			this.component.setCursor(this.cursor);
		}
	}

	/* SetEnabledComponents */
	public static Runnable setEnabledComponents(List<Component> components_, boolean enabled_) {
		return Actions.new SetEnabledComponents(components_, enabled_);
	}

	private class SetEnabledComponents implements Runnable {
		private List<Component> components = null;
		boolean enabled;

		public SetEnabledComponents(List<Component> components_, boolean enabled_) {
			this.components = components_;
			this.enabled = enabled_;
		}

		public void run() {
			if (this.components == null)
				return;

			for (Component each : this.components) {
				each.setEnabled(this.enabled);
			}
		}

	}

	/* SetBounds */
	public static Runnable setBounds(Component component, int x, int y, int width, int height) {
		return Actions.new SetBounds(component, new Rectangle(x, y, width, height));
	}
	public static Runnable setBounds(Component component, Rectangle bounds) {
		return Actions.new SetBounds(component, bounds);
	}

	private class SetBounds implements Runnable {
		private Component component = null;
		Rectangle bounds = null;

		public SetBounds(Component component_, Rectangle bounds_) {
			this.component = component_;
			this.bounds = bounds_;
		}

		public void run() {
			if (this.component == null || this.bounds == null)
				return;
			
			this.component.setBounds(this.bounds);
		}

	}

	/* SetLabelText */
	public static Runnable setLabelText(JLabel component_, String text_) {
		return Actions.new SetLabelText(component_, text_);
	}

	private class SetLabelText implements Runnable {
		private JLabel component = null;
		private String text = null;

		public SetLabelText(JLabel component_, String text_) {
			this.component = component_;
			this.text = text_;
		}

		public void run() {
			if (this.component == null)
				return;

			this.component.setText(this.text);
		}

	}

	/* CustomizeTextComponent */
	public static Runnable customizeTextComponent(JTextComponent textComponent_, String text_) {
		return Actions.new CustomizeTextComponent(textComponent_, text_);
	}

	private class CustomizeTextComponent implements Runnable {
		private JTextComponent textComponent = null;
		private String text = null;

		public CustomizeTextComponent(JTextComponent textComponent_, String text_) {
			this.textComponent = textComponent_;
			this.text = text_;
		}

		public void run() {
			if (this.textComponent == null)
				return;

			this.textComponent.setText(this.text);
		}
	}

	/* CustomizeButton */
	public static Runnable customizeButton(AbstractButton button_, ImageIcon img_, String label_) {
		return Actions.new CustomizeButton(button_, img_, label_);
	}

	private class CustomizeButton implements Runnable {
		private AbstractButton button = null;
		private ImageIcon img = null;
		private String label = null;

		public CustomizeButton(AbstractButton button_, ImageIcon img_, String label_) {
			this.button = button_;
			this.img = img_;
			this.label = label_;
		}

		public void run() {
			if (this.button == null)
				return;

			if (this.img != null)
				this.button.setIcon(this.img);

			if (this.label != null)
				this.button.setText(this.label);
		}
	}

	/* CustomizeSlider */
	public static Runnable customizeSlider(JSlider slider_, int value_) {
		return Actions.new CustomizeSlider(slider_, value_);
	}

	private class CustomizeSlider implements Runnable {
		private JSlider slider = null;
		private int value;

		public CustomizeSlider(JSlider slider_, int value_) {
			this.slider = slider_;
			this.value = value_;
		}

		public void run() {
			if (this.slider == null)
				return;

			this.slider.setValue(this.value);
		}
	}

	/* SetBoxChecked */
	public static Runnable setBoxChecked(JCheckBox box_, boolean checked_) {
		return Actions.new SetBoxChecked(box_, checked_);
	}

	private class SetBoxChecked implements Runnable {
		private JCheckBox box = null;
		private boolean checked;

		public SetBoxChecked(JCheckBox box_, boolean checked_) {
			this.box = box_;
			this.checked = checked_;
		}

		public void run() {
			if (this.box == null)
				return;

			this.box.setSelected(this.checked);
		}
	}

	/* DoClick */
	public static Runnable doClick(JButton button_) {
		return Actions.new DoClick(button_);
	}

	private class DoClick implements Runnable {
		private JButton button = null;

		public DoClick(JButton button_) {
			this.button = button_;
		}

		public void run() {
			if (this.button == null)
				return;

			this.button.doClick();
		}
	}

	/* validate */
	public static Runnable validate(Container container_) {
		return Actions.new Validate(container_);
	}
	
	private class Validate implements Runnable {
		private Container container = null;
		
		public Validate(Container container_) {
			this.container = container_;
		}
		
		public void run() {
			if (this.container == null)
				return;
			
			this.container.validate();
		}
	}
		
	/* RemoveComponents */
	public static Runnable removeComponents(Container container_, List<Component> componentList_) {
		return Actions.new RemoveComponents(container_, componentList_);
	}

	private class RemoveComponents implements Runnable {
		private Container container = null;
		private List<Component> componentList = null;

		public RemoveComponents(Container container_, List<Component> componentList_) {
			this.container = container_;
			this.componentList = componentList_;
		}

		public void run() {
			if (this.container == null || this.componentList == null)
				return;

			for (Component each : this.componentList)
				this.container.remove(each);
		}
	}
	
	/* RemoveAll */
	public static Runnable removeAll(Container container_) {
		return Actions.new RemoveAll(container_);
	}
	
	private class RemoveAll implements Runnable {
		private Container container = null;
		
		public RemoveAll(Container container_) {
			this.container = container_;
		}
		
		public void run() {
			if (this.container == null)
				return;
			
			this.container.removeAll();
			this.container.validate();
			this.container.repaint();
		}
	}

	/* AddComponents */
	public static Runnable addComponents(Container container_, List<Component> components_, List<GridBagConstraints> gbcs_) {
		return Actions.new AddComponents(container_, components_, gbcs_);
	}

	private class AddComponents implements Runnable {
		private Container container = null;
		private List<Component> components = null;
		private List<GridBagConstraints> gbcs = null;

		public AddComponents(Container container_, List<Component> components_, List<GridBagConstraints> gbcs_) {
			this.container = container_;
			this.components = components_;
			this.gbcs = gbcs_;
		}

		public void run() {
			if (this.container != null && this.components != null && this.gbcs != null && this.components.size() == this.gbcs.size()) {
				for (int i = 0; i < this.components.size(); i++)
					this.container.add(this.components.get(i), this.gbcs.get(i));

				this.container.validate();
			}
		}
	}

	/* PackWindow */
	public static Runnable packWindow(Window wnd_) {
		return Actions.new PackWindow(wnd_);
	}

	private class PackWindow implements Runnable {
		private Window wnd = null;

		public PackWindow(Window wnd_) {
			this.wnd = wnd_;
		}

		public void run() {
			if (this.wnd == null)
				return;

			this.wnd.pack();
		}
	}

	/* AddComponentsAndPack */
	public static Runnable addComponentsAndPack(Window wnd_, List<Component> components_, List<GridBagConstraints> gbcs_) {
		return Actions.new AddComponentsAndPack(wnd_, components_, gbcs_);
	}

	private class AddComponentsAndPack implements Runnable {
		private Window wnd = null;
		private List<Component> components = null;
		private List<GridBagConstraints> gbcs = null;

		public AddComponentsAndPack(Window wnd_, List<Component> components_, List<GridBagConstraints> gbcs_) {
			this.wnd = wnd_;
			this.components = components_;
			this.gbcs = gbcs_;
		}

		public void run() {
			addComponents(this.wnd, this.components, this.gbcs).run();
			packWindow(this.wnd).run();
		}
	}

	/* RemoveComponentsAndPack */
	public static Runnable removeComponentsAndPack(Window wnd_, List<Component> components_) {
		return Actions.new RemoveComponentsAndPack(wnd_, components_);
	}

	private class RemoveComponentsAndPack implements Runnable {
		private Window wnd = null;
		private List<Component> components = null;

		public RemoveComponentsAndPack(Window wnd_, List<Component> components_) {
			this.wnd = wnd_;
			this.components = components_;
		}

		public void run() {
			removeComponents(this.wnd, this.components).run();
			packWindow(this.wnd).run();
		}
	}

	/* TextComponentRequestFocus */
	public static Runnable textComponentRequestFocus(JTextComponent c) {
		return Actions.new TextComponentRequestFocus(c);
	}

	private class TextComponentRequestFocus implements Runnable {
		private JTextComponent c = null;

		public TextComponentRequestFocus(JTextComponent c_) {
			this.c = c_;
		}

		public void run() {
			if (this.c == null)
				return;

			this.c.requestFocus();


			int pos;
			if (this.c.getClass() == JPasswordField.class)
				pos = ((JPasswordField)this.c).getPassword().length;
			else
				pos = this.c.getText().length();

			this.c.setCaretPosition(pos);
		}
	}

	/* AlignWindowToScreenCenter */
	public static Runnable alignWindowToScreenCenter(Window wnd) {
		return Actions.new AlignWindowToScreenCenter(wnd);
	}

	private class AlignWindowToScreenCenter implements Runnable {
		private Window wnd = null;

		public AlignWindowToScreenCenter(Window wnd) {
			this.wnd = wnd;
		}

		public void run() {
			if (this.wnd == null)
				return;

			initScreenBounds();

			int availableWidth = SCREEN_BOUNDS.width - SCREEN_BOUNDS.x;
			int availableHeight = SCREEN_BOUNDS.height - SCREEN_BOUNDS.y;

			int x = (availableWidth / 2) - (wnd.getWidth() / 2);
			int y = (availableHeight / 2) - (wnd.getHeight() / 2);

			this.wnd.setLocation(x, y);
		}
	}

	public static void setFullscreen(Window wnd) {
		if (! wnd.isVisible()) {
			try {
				SwingTools.invokeAndWait(GUIActions.setVisible(wnd, true));
			} catch (Exception ex) {
				Logger.error("[GUIActions.setFullscreen] Failed to make window visible: "+ex.getMessage());
				return;
			}
		}

		if (OSTools.isLinux()) {
			WorkArea.setFullscreenWindow(wnd, true);
			return;
		}

		GraphicsEnvironment ge = GraphicsEnvironment.getLocalGraphicsEnvironment();
		GraphicsDevice gs = ge.getDefaultScreenDevice();
		gs.setFullScreenWindow(wnd);
	}

	public static void unsetFullscreen(Window wnd) {
		if (OSTools.isLinux()) {
			WorkArea.setFullscreenWindow(wnd, false);
			return;
		}

		GraphicsEnvironment ge = GraphicsEnvironment.getLocalGraphicsEnvironment();
		GraphicsDevice gs = ge.getDefaultScreenDevice();
		gs.setFullScreenWindow(null);
	}
}
