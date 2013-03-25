/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Alexandre CONFIANT-LATOUR <a.confiant@ulteo.com> 2013
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

package net.propero.rdp;

import java.awt.Component;
import java.awt.Container;
import java.awt.TextField;
import java.awt.event.FocusListener;
import java.awt.event.FocusEvent;

public class ImeStateSetter implements FocusListener {

	protected Component component;
	protected Container container;
	protected FocusListener focusListeners[];
	protected TextField textField;

	public ImeStateSetter(Component component, Container container, boolean imeState) {
		this.component = component;
		this.container = container;

		/* Set the IME state */
		component.enableInputMethods(imeState);

		/* Save its FocusListeners */
		this.focusListeners = component.getFocusListeners();

		/* Remove them */
		for (FocusListener focusListener : this.focusListeners) {
			component.removeFocusListener(focusListener);
		}

		/* Add the ImeStateSetter as a FocusListener */
		component.addFocusListener(this);

		/* Add a dummy text box to make "component" to loose the focus */
		this.textField = new TextField();
		container.add(this.textField);
		this.textField.requestFocusInWindow();
	}

	public void focusLost(FocusEvent e) {
		/* Remove the TextField */
		this.container.remove(this.textField);
	}

	public void focusGained(FocusEvent e) {
		/* Remove the ImeStateSetter from the FocusListeners */
		this.component.removeFocusListener(this);

		/* Restore the saved focuslisteners */
		for (FocusListener focusListener : this.focusListeners) {
			this.component.addFocusListener(focusListener);
		}
	}
}
