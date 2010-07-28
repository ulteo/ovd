package org.ulteo.ovd.client.authInterface;

import java.awt.event.KeyEvent;
import java.awt.event.KeyListener;

public class KeyLoginListener implements KeyListener{

	private AuthFrame frame = null;
	public static boolean PUSHED = false;
	
	public KeyLoginListener(AuthFrame frame) {
		this.frame = frame;
	}
	@Override
	public void keyPressed(KeyEvent e) {}

	@Override
	public void keyReleased(KeyEvent e) {}

	@Override
	public void keyTyped(KeyEvent ke) {
		if(ke.getKeyChar() == ke.VK_ENTER && PUSHED == false) {
			PUSHED = true;
			new LoginListener(frame).launchConnection();
		}
	}
}
