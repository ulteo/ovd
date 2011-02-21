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

package org.ulteo.ovd.client.bugreport.gui;

import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Insets;
import java.awt.event.ActionListener;
import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.HashMap;
import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTextArea;
import javax.swing.SwingConstants;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.client.ClientInfos;
import org.ulteo.utils.I18n;

public class BugReportPopup extends JDialog {

	private ActionListener listener = null;

	private JLabel date = null;
	private JLabel version = null;
	private JLabel system = null;
	private JLabel jvm = null;
	private JTextArea description = null;

	public BugReportPopup(ActionListener listener_) {
		super();

		this.listener = listener_;

		this.initWindow();
		this.initGui();
	}

	private void initWindow() {
		this.setTitle(I18n._("Ulteo OVD Bug Report"));
		GUIActions.setIconImage(this, null).run();

		this.addWindowListener(new WindowAdapter() {
			@Override
			public void windowOpened(WindowEvent e) {
				if (e.getWindow() != BugReportPopup.this)
					return;

				//BugReportPopup.this.setMinimumSize(BugReportPopup.this.getSize());
				BugReportPopup.this.setResizable(false);
				System.out.println("description bounds: "+BugReportPopup.this.description.getBounds());

				SwingTools.invokeLater(GUIActions.alignWindowToScreenCenter(BugReportPopup.this));
			}
		});
	}

	private void initGui() {
		this.setLayout(new GridBagLayout());
		GridBagConstraints gbc = new GridBagConstraints();
		gbc.weightx = 1.;

		gbc.gridx = 0;
		gbc.gridy = 0;
		gbc.weighty = 0.;
		gbc.fill = GridBagConstraints.HORIZONTAL;
		gbc.anchor = GridBagConstraints.BASELINE_LEADING;
		this.add(this.initStaticInfosPanel(), gbc);

		gbc.gridx = 0;
		gbc.gridy = 1;
		gbc.weighty = 1.;
		gbc.fill = GridBagConstraints.BOTH;
		gbc.anchor = GridBagConstraints.BASELINE_LEADING;
		this.add(this.initUserInfosPanel(), gbc);

		gbc.gridx = 0;
		gbc.gridy = 2;
		gbc.weighty = 0.;
		gbc.fill = GridBagConstraints.HORIZONTAL;
		gbc.anchor = GridBagConstraints.BASELINE_TRAILING;
		gbc.insets = new Insets(7, 0, 7, 7);
		gbc.fill = GridBagConstraints.NONE;
		JButton validate = new JButton(I18n._("Validate"));
		validate.addActionListener(this.listener);
		this.add(validate, gbc);
	}

	private JPanel initStaticInfosPanel() {
		JPanel staticInfosPanel = new JPanel(new GridBagLayout());
		GridBagConstraints gbc = new GridBagConstraints();
		HashMap<String, JLabel> items = new HashMap<String, JLabel>();
		
		this.date = new JLabel();
		items.put(I18n._("Date"), this.date);
		this.version = new JLabel();
		items.put(I18n._("Version"), this.version);
		this.system = new JLabel();
		items.put(I18n._("System"), this.system);
		this.jvm = new JLabel();
		items.put(I18n._("JVM"), this.jvm);

		gbc.gridy = 0;
		gbc.anchor = GridBagConstraints.BASELINE_LEADING;
		gbc.insets.left = 7;
		gbc.insets.top = 7;
		for (String label : items.keySet()) {
			gbc.gridx = 0;
			gbc.weightx = 0.;
			staticInfosPanel.add(new JLabel(label+":"), gbc);
			
			gbc.gridx = 1;
			gbc.weightx = 1.;
			JLabel value = items.get(label);
			value.setEnabled(false);
			value.setHorizontalTextPosition(SwingConstants.LEFT);
			staticInfosPanel.add(value, gbc);

			gbc.gridy++;
		}
		items.clear();
		items = null;
		gbc = null;

		return staticInfosPanel;
	}

	private JPanel initUserInfosPanel() {
		JPanel userInfosPanel = new JPanel(new GridBagLayout());
		GridBagConstraints gbc = new GridBagConstraints();

		gbc.gridx = 0;
		gbc.gridy = 0;
		gbc.insets.top = 20;
		userInfosPanel.add(new JLabel(I18n._("Description and steps to reproduce:")), gbc);
		
		gbc.gridy = 1;
		gbc.weightx = 1.;
		gbc.weighty = 1.;
		gbc.fill = GridBagConstraints.BOTH;
		gbc.insets = new Insets(7, 7, 0, 7);
		this.description = new JTextArea(10, 30);
		userInfosPanel.add(new JScrollPane(this.description), gbc);

		gbc = null;

		return userInfosPanel;
	}

	private void initValues() {
		SimpleDateFormat sdf = new SimpleDateFormat("MM/dd/yy hh:mm:ss");
		this.setDate(sdf.format(Calendar.getInstance().getTime()));

		ClientInfos clientInfos = ClientInfos.getClientInfos();
		this.setJVM(clientInfos.jvm_infos.jre_vendor+" "+clientInfos.jvm_infos.jre_version);
		this.setVersion(ClientInfos.getOVDVersion());
		this.setSystem(clientInfos.os_infos.name+" "+clientInfos.os_infos.version+" "+clientInfos.os_infos.arch);
	}

	private void clear() {
		this.setDate("");
		this.setVersion("");
		this.setSystem("");
		this.setJVM("");
	}

	@Override
	public void setVisible(boolean visible) {
		if (visible) {
			this.initValues();
			SwingTools.invokeLater(GUIActions.packWindow(this));
		}
		else {
			this.clear();
		}

		super.setVisible(visible);
	}

	private void setDate(String date_) {
		if (this.date == null)
			return;

		this.date.setText(date_);
	}

	private void setVersion(String version_) {
		if (this.version == null)
			return;

		this.version.setText(version_);
	}

	private void setSystem(String system_) {
		if (this.system == null)
			return;

		this.system.setText(system_);
	}

	private void setJVM(String jvm_) {
		if (this.jvm == null)
			return;

		this.jvm.setText(jvm_);
	}

	public String getDate() {
		if (this.date == null)
			return new String();

		return this.date.getText();
	}

	public String getVersion() {
		if (this.version == null)
			return new String();

		return this.version.getText();
	}

	public String getSystem() {
		if (this.system == null)
			return new String();

		return this.system.getText();
	}

	public String getJVM() {
		if (this.jvm == null)
			return new String();

		return this.jvm.getText();
	}

	public String getDescription() {
		if (this.description == null)
			return new String();

		return this.description.getText();
	}
}
