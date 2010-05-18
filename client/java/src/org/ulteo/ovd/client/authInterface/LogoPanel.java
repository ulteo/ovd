package org.ulteo.ovd.client.authInterface;

import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.Image;
import java.awt.RenderingHints;
import java.awt.image.BufferedImage;

import javax.swing.JPanel;

public class LogoPanel extends JPanel {
	
	private Image logo = null;
	private Image resizedLogo = null;
	private int panelWidth = 0;
	private int panelHeight = 0;
	private int scaleX = 0;
	private int scaleY = 0;
	
	public LogoPanel() {
		logo = getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.big.png"));
		revalidate();
		repaint();
	}

	public void paintComponent(Graphics g) {
		panelWidth = getWidth();
		panelHeight = getHeight();
		resizedLogo = imageScale(logo, 282, 141);
		scaleX = (panelWidth/2)-(282/2);
		scaleY = (panelHeight/2)-(141/2);
		g.drawImage(resizedLogo, scaleX, scaleY, null);
	}
	
	public  Image imageScale(Image img, int largeur, int hauteur) {
	    /* On cree une nouvelle image aux bonnes dimensions. */
	    BufferedImage buf = new BufferedImage(largeur, hauteur, BufferedImage.TYPE_INT_ARGB);
	    
	    /* On dessine sur le Graphics de l'image bufferisee. */
	    Graphics2D g = buf.createGraphics();
	    g.setRenderingHint(RenderingHints.KEY_INTERPOLATION, RenderingHints.VALUE_INTERPOLATION_BILINEAR);
	    g.drawImage(img, 0, 0, largeur, hauteur, null);
	    g.dispose();
	    
	    /* On retourne l'image bufferisee, qui est une image. */
	    return buf;
	}
}
