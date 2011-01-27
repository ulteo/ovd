/* Subversion properties, do not modify!
 * 
 * $Date: 2008/01/28 13:47:42 $
 * $Revision: 1.2 $
 * $Author: tome.he $
 */

package net.propero.rdp.rdp5.rdpsnd;

public class ADPCMChannelStatus {
	public int	predictor		= 0;
	public short	step_index	= 0;
	public int	step			= 0;
	/* for encoding */
	public int	prev_sample;

	/* MS version */
	public short	sample1;
	public short	sample2;
	public int	coeff1;
	public int	coeff2;
	public int	idelta;
}
