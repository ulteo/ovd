/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

package org.ulteo.ovd.client.bugreport;

import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.File;
import javax.swing.JButton;
import javax.swing.JFileChooser;
import javax.swing.JOptionPane;
import org.ulteo.Logger;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.gui.filters.ExtensionFilter;
import org.ulteo.ovd.client.bugreport.gui.BugReportPopup;
import org.ulteo.utils.I18n;

public class BugReporter implements ActionListener {

	public static void showWindow() {
		BugReporter bugReporter = new BugReporter();
		bugReporter.show();
	}

	private BugReportPopup popup = null;

	public BugReporter() {
		this.popup = new BugReportPopup(this);
	}

	public void show() {
		SwingTools.invokeLater(GUIActions.setVisible(this.popup, true));
	}

	private BugReport getBug() {
		if (this.popup == null)
			return null;

		String date = this.popup.getDate();
		String version = this.popup.getVersion();
		String system = this.popup.getSystem();
		String jvm = this.popup.getJVM();
		String description = this.popup.getDescription();

		if (date.length() == 0
		    || version.length() == 0
		    || system.length() == 0
		    || jvm.length() == 0
		    || description.length() == 0)
			return null;

		BugReport bug = new BugReport();
		bug.setDate(date);
		bug.setVersion(version);
		bug.setSystem(system);
		bug.setJVM(jvm);
		bug.setDescription(description);

		return bug;
	}

	private void reportBug(BugReport bug) {
		if (bug == null)
			return;

		JFileChooser fc = new JFileChooser();
		fc.addChoosableFileFilter(new ExtensionFilter("txt", I18n._("Text file (*.txt)")));
		fc.setAcceptAllFileFilterUsed(false);

		switch (fc.showSaveDialog(this.popup)) {
			case JFileChooser.APPROVE_OPTION:
				break;
			case JFileChooser.ERROR_OPTION:
				SwingTools.invokeLater(GUIActions.createDialog(I18n._("An error occured. Please try again."), I18n._("Error"), JOptionPane.ERROR_MESSAGE, JOptionPane.CLOSED_OPTION));
				Logger.error("[reportBug] Failed to get file");
			case JFileChooser.CANCEL_OPTION:
			default:
				return;
		}

		File f = fc.getSelectedFile();
		if (f == null)
			return;
		
		bug.toTxtFile(f);

		String message = I18n._("Bug report saved to %s");
		message = message.replaceFirst("%s", f.getPath());
		try {
			SwingTools.invokeAndWait(GUIActions.createDialog(message, I18n._("Bug report saved"), JOptionPane.INFORMATION_MESSAGE, JOptionPane.CLOSED_OPTION));
		} catch (Exception ex) {}
		
		SwingTools.invokeLater(GUIActions.disposeWindow(this.popup));
		this.popup = null;
	}

	public void actionPerformed(ActionEvent e) {
		if (! (e.getSource() instanceof JButton))
			return;
		
		new Thread(new Runnable() {
			public void run() {
				BugReport bug = getBug();
				if (bug == null) {
					SwingTools.invokeLater(GUIActions.createDialog(I18n._("Please fill description field."), I18n._("Empty field"), JOptionPane.WARNING_MESSAGE, JOptionPane.CLOSED_OPTION));
					return;
				}
				
				reportBug(bug);
			}
		}).start();
	}
}
