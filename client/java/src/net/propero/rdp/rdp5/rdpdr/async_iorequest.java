/* async_iorequest.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.0
 * Author: tomqq (hekong@gmail.com)
 * Date: 2009/05/16
 *
 * Copyright (c) tomqq
 *
 */
package net.propero.rdp.rdp5.rdpdr;

public class async_iorequest {
	int fd, major, minor, offset, device, id, length, partial_len;
	long timeout,		/* Total timeout */
	  itv_timeout;		/* Interval timeout (between serial characters) */
	char[] buffer;
	int device_id;
}
