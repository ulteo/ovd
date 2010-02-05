package org.ulteo.ovd.standalone;

import java.awt.Dimension;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.net.URL;
import javax.swing.ImageIcon;
import org.ulteo.ovd.sm.SessionManagerCommunication;

public class AuthenticationPanel extends javax.swing.JPanel implements ActionListener {
	private static final String REMOTEAPPS_MODE_LABEL = "Portal";
	private static final String DESKTOP_MODE_LABEL = "Desktop";

	private Client ovd;
	
	public AuthenticationPanel(Client main_instance) {
		super();
		this.ovd = main_instance;
		this.initComponents();
		this.jButton1.addActionListener(this);
		this.field_server.addActionListener(this);
		this.jTextField1.addActionListener(this);
		this.jTextField2.addActionListener(this);
	}

	public void actionPerformed(ActionEvent e) {
		this.ovd.switch2Load();
	}


	public String getServer() {
		return this.field_server.getText();
	}

	public String getLogin() {
		return this.jTextField1.getText();
	}

	public String getPassword() {
		return this.jTextField2.getText();
	}

	public String getMode() {
		String mode = (String)this.jComboBox1.getSelectedItem();
		if (mode.equals(AuthenticationPanel.DESKTOP_MODE_LABEL))
			return SessionManagerCommunication.SESSION_MODE_DESKTOP;
		else if (mode.equals(AuthenticationPanel.REMOTEAPPS_MODE_LABEL))
			return SessionManagerCommunication.SESSION_MODE_REMOTEAPPS;
		else
			return "Bad Mode";
	}

	private void initComponents() {
		jButton1 = new javax.swing.JButton();
		jLabel1 = new javax.swing.JLabel();
		jLabel2 = new javax.swing.JLabel();
		jLabel3 = new javax.swing.JLabel();
		field_server = new javax.swing.JTextField();
		jTextField1 = new javax.swing.JTextField();
		jTextField2 = new javax.swing.JTextField();
		jLabel4 = new javax.swing.JLabel();
		jLabel5 = new javax.swing.JLabel();
		jLabel6 = new javax.swing.JLabel();
		jComboBox1 = new javax.swing.JComboBox();
		
		jButton1.setText("Connect"); // NOI18N
		jLabel1.setText("Username:"); // NOI18N
		jLabel2.setText("Password:"); // NOI18N
		jLabel3.setText("Server:"); // NOI18N
		
		field_server.setText("localhost");
		jTextField1.setText("mwilson"); // NOI18N

		URL icon_url = this.getClass().getResource("/ressources/logo.png");
		jLabel4.setIcon(new ImageIcon(icon_url)); // NOI18N
		jLabel5.setText("Open Virtual Desktop"); // NOI18N

		jComboBox1.addItem(AuthenticationPanel.DESKTOP_MODE_LABEL);
		jComboBox1.addItem(AuthenticationPanel.REMOTEAPPS_MODE_LABEL);
		Dimension d = jComboBox1.getPreferredSize();
		d.width = 50;
		jComboBox1.setMaximumSize(d);
		jLabel6.setText("Mode:");

		javax.swing.GroupLayout thisLayout = new javax.swing.GroupLayout(this);
		this.setLayout(thisLayout);

		thisLayout.setHorizontalGroup(
			thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
			.addGroup(thisLayout.createSequentialGroup()
				.addGroup(thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
					.addGroup(thisLayout.createSequentialGroup()
						.addContainerGap()
						.addComponent(jLabel4)
						.addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.RELATED, 9, Short.MAX_VALUE)
						.addComponent(jLabel5, javax.swing.GroupLayout.PREFERRED_SIZE, 289, javax.swing.GroupLayout.PREFERRED_SIZE)
						.addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.RELATED)
					)
					.addGroup(thisLayout.createSequentialGroup()
						.addGap(60, 60, 60)
						.addGroup(thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
							.addComponent(jLabel2)
							.addGroup(thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.TRAILING)
								.addComponent(jButton1)
								.addGroup(thisLayout.createSequentialGroup()
									.addGroup(thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
										.addGroup(javax.swing.GroupLayout.Alignment.TRAILING, thisLayout.createSequentialGroup()
											.addComponent(jLabel3)
											.addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.UNRELATED)
										)
										.addGroup(javax.swing.GroupLayout.Alignment.TRAILING, thisLayout.createSequentialGroup()
											.addComponent(jLabel1)
											.addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.UNRELATED)
										)
										.addGroup(javax.swing.GroupLayout.Alignment.TRAILING, thisLayout.createSequentialGroup()
											.addComponent(jLabel6)
											.addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.UNRELATED)
										)
									)
									.addGroup(thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING, false)
										.addComponent(field_server, 0, 196, Short.MAX_VALUE)
										.addComponent(jTextField1)
										.addComponent(jTextField2)
										.addComponent(jComboBox1, 0, 196, Short.MAX_VALUE)
									)
								)
							)
						)
					)
				)
				.addContainerGap()
			)
		);

		thisLayout.setVerticalGroup(
			thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
			.addGroup(thisLayout.createSequentialGroup()
				.addContainerGap()
				.addGroup(thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
					.addComponent(jLabel4)
					.addComponent(jLabel5)
				)
				.addGap(48, 48, 48)
				.addGroup(thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.BASELINE)
					.addComponent(jLabel3)
					.addComponent(field_server, javax.swing.GroupLayout.PREFERRED_SIZE, javax.swing.GroupLayout.DEFAULT_SIZE, javax.swing.GroupLayout.PREFERRED_SIZE)
				)
				.addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.UNRELATED)
				.addGroup(thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.BASELINE)
					.addComponent(jLabel1)
					.addComponent(jTextField1, javax.swing.GroupLayout.PREFERRED_SIZE, javax.swing.GroupLayout.DEFAULT_SIZE, javax.swing.GroupLayout.PREFERRED_SIZE)
				)
				.addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.UNRELATED)
				.addGroup(thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.BASELINE)
					.addComponent(jLabel2)
					.addComponent(jTextField2, javax.swing.GroupLayout.PREFERRED_SIZE, javax.swing.GroupLayout.DEFAULT_SIZE, javax.swing.GroupLayout.PREFERRED_SIZE)
				)
				.addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.UNRELATED)
				.addGroup(thisLayout.createParallelGroup(javax.swing.GroupLayout.Alignment.BASELINE)
					.addComponent(jLabel6)
					.addComponent(jComboBox1, javax.swing.GroupLayout.PREFERRED_SIZE, javax.swing.GroupLayout.DEFAULT_SIZE, javax.swing.GroupLayout.PREFERRED_SIZE)
				)
				.addGap(18, 18, 18)
				.addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.UNRELATED)
				.addComponent(jButton1)
				.addContainerGap(23, Short.MAX_VALUE)
			)
		);
	}// </editor-fold>//GEN-END:initComponents

	// Variables declaration - do not modify//GEN-BEGIN:variables
	private javax.swing.JButton jButton1;
	private javax.swing.JTextField field_server;
	private javax.swing.JLabel jLabel1;
	private javax.swing.JLabel jLabel2;
	private javax.swing.JLabel jLabel3;
	private javax.swing.JLabel jLabel4;
	private javax.swing.JLabel jLabel5;
	private javax.swing.JLabel jLabel6;
	private javax.swing.JTextField jTextField1;
	private javax.swing.JTextField jTextField2;
	private javax.swing.JComboBox jComboBox1;
	//private javax.swing.JPanel this;
	// End of variables declaration//GEN-END:variables
}