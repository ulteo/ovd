/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009
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

package net.propero.rdp.rdp5.seamless;

import net.propero.rdp.Options;

import java.awt.Frame;
import java.awt.TextField;
import java.awt.Button;
import java.awt.FlowLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.KeyEvent;
import java.awt.event.KeyListener;
import java.util.ArrayList;

public class SeamForm extends Frame 
	implements ActionListener, KeyListener{
	private SeamlessChannel channel;
	private TextField tf;
	private Button button;
	private ArrayList<String> cmds;
	private int cpt_cmd;

	public SeamForm(SeamlessChannel channel_, Options opt_) {
		this.channel = channel_;
		
		this.cmds = new ArrayList<String>();
		cpt_cmd = 0;
		
		this.setSize(450, 70);
		tf = new TextField(40);
		tf.addActionListener(this);
		tf.addKeyListener(this);
		button = new Button("Send");
		button.setSize(70, 50);
		button.addActionListener(this);
		this.setLayout(new FlowLayout());
		this.add(tf);
		this.add(button);
		this.setResizable(false);
		this.setTitle("SeamForm");
		this.setLocation(opt_.width-450, (int)this.getLocation().getY());
		this.setAlwaysOnTop(true);
		this.setVisible(true);
	}

	public void actionPerformed(ActionEvent e) {
		if(e.getSource() == button || e.getSource() == tf) {
			try {
				String cmd = tf.getText();
				
				if (cmd.length() > 0)
				{
					if (this.cpt_cmd < this.cmds.size()) {
						for (int i = this.cpt_cmd; i < this.cmds.size(); i++)
							this.cmds.remove(i);
					}
					this.cmds.add(cmd);
					this.cpt_cmd++;
					channel.send_spawn(cmd);
				}
			} catch(Exception ex) {}
			tf.setText("");
		}
	}
	
	public void keyTyped(KeyEvent e) {}
	
	public void keyPressed(KeyEvent e) {
		switch (e.getKeyCode()) {
			case KeyEvent.VK_UP:
				if(this.cpt_cmd < 0)
					break;
				
				if (this.cpt_cmd > 0)
					this.cpt_cmd--;
				
				this.tf.setText(this.cmds.get(this.cpt_cmd));
				break;
			case KeyEvent.VK_DOWN:
				if (this.cpt_cmd >= this.cmds.size())
					break;
				
				if (this.cpt_cmd == this.cmds.size() - 1) {
					this.tf.setText("");
					break;
				}
				
				this.cpt_cmd++;
				this.tf.setText(this.cmds.get(this.cpt_cmd));
				break;
			default:
				break;
		}
	}

	public void keyReleased(KeyEvent e) {}
}
