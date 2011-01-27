package org.ulteo.utils;

import java.awt.Component;

public interface AbstractFocusManager {

	public void performedFocusGain(Component container);
	public void performedFocusLost(Component container);
	
}
