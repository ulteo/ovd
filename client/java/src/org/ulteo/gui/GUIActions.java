/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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
import java.awt.GridBagConstraints;
import java.awt.Window;
import java.util.List;
import javax.swing.AbstractButton;
import javax.swing.ImageIcon;
import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JLabel;
import javax.swing.JPasswordField;
import javax.swing.JSlider;
import javax.swing.text.JTextComponent;

public class GUIActions {
	private static GUIActions Actions = new GUIActions();

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
}
