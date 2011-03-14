/* Rdp5.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:39 $
 *
 * Copyright (c) 2005 Propero Limited
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
 *
 * Purpose: Handle RDP5 orders
 */

package net.propero.rdp.rdp5;

import net.propero.rdp.*;
import net.propero.rdp.compress.RdpCompressionException;
import net.propero.rdp.crypto.*;

public class Rdp5 extends Rdp {
	private static final int RDP5_COMPRESSED = 0x80;

	private static final int RDP_MPPC_COMPRESSED = 0x20;

	/* Disable desktop wallpaper */
	public static final int PERF_DISABLE_WALLPAPER =		0x00000001;
	/* Disable full-window drag (only the window outline is displayed when the window is moved) */
	public static final int PERF_DISABLE_FULLWINDOWDRAG =		0x00000002;
	/* Disable menu animations */
	public static final int PERF_DISABLE_MENUANIMATIONS =		0x00000004;
	/* Disable user interface themes */
	public static final int PERF_DISABLE_THEMING =			0x00000008;
	/* Disable mouse cursor shadows */
	public static final int PERF_DISABLE_CURSOR_SHADOW =		0x00000020;
	/* Disable cursor blinking */
	public static final int PERF_DISABLE_CURSORSETTINGS =		0x00000040;
	/* Enable font smoothing */
	public static final int PERF_ENABLE_FONT_SMOOTHING =		0x00000080;
	/* Enable Desktop Composition */
	public static final int PERF_ENABLE_DESKTOP_COMPOSITION =	0x00000100;
	/* Enable all server desktop shell features */
	public static final int PERF_ENABLE_ALL = PERF_ENABLE_FONT_SMOOTHING | PERF_ENABLE_DESKTOP_COMPOSITION | Rdp5.PERF_DISABLE_FULLWINDOWDRAG;
	/* Disable all server desktop shell features */
	public static final int PERF_DISABLE_ALL = PERF_DISABLE_WALLPAPER
						| PERF_DISABLE_FULLWINDOWDRAG
						| PERF_DISABLE_MENUANIMATIONS
						| PERF_DISABLE_THEMING
						| PERF_DISABLE_CURSOR_SHADOW
						| PERF_DISABLE_CURSORSETTINGS;

    private VChannels channels;

    /**
     * Initialise the RDP5 communications layer, with specified virtual channels
     * 
     * @param channels
     *            Virtual channels for RDP layer
     */
    public Rdp5(VChannels channels, Options opt_, Common common_) {
        super(channels, opt_, common_);
        this.channels = channels;
    }

    /**
     * Process an RDP5 packet
     * 
     * @param s
     *            Packet to be processed
     * @param e
     *            True if packet is encrypted
     * @throws RdesktopException
     * @throws OrderException
     * @throws CryptoException
     */
    public void rdp5_process(RdpPacket_Localised s, boolean e)
            throws RdesktopException, OrderException, CryptoException {
        rdp5_process(s, e, false);
    }

    /**
     * Process an RDP5 packet
     * 
     * @param s
     *            Packet to be processed
     * @param encryption
     *            True if packet is encrypted
     * @param shortform
     *            True if packet is of the "short" form
     * @throws RdesktopException
     * @throws OrderException
     * @throws CryptoException
     */
    public void rdp5_process(RdpPacket_Localised s, boolean encryption,
            boolean shortform) throws RdesktopException, OrderException,
            CryptoException {
        logger.debug("Processing RDP 5 order");

        int length, count;
        int type, ctype;
        int next;
        RdpPacket_Localised ts = null;

        while (s.getPosition() < s.getEnd()) {
            type = s.get8();

            if ((type & RDP5_COMPRESSED) != 0) {
                ctype = s.get8();
                length = s.getLittleEndian16();
                type ^= RDP5_COMPRESSED;
            }
            else {
                ctype = 0;
                length = s.getLittleEndian16();
            }
            this.next_packet = next = s.getPosition() + length;

            if ((ctype & RDP_MPPC_COMPRESSED) != 0) {
                try {
                    ts = this.common.decompressor.decompress(s, length, ctype);
                } catch (RdpCompressionException ex) {
                    logger.error(ex.getMessage());
		    continue;
                }
            }
            else
                ts = s;

            logger.debug("RDP5: type = " + type);
            switch (type) {
            case 0: /* orders */
                count = ts.getLittleEndian16();
                orders.processOrders(ts, next, count);
                break;
            case 1: /* bitmap update (???) */
                ts.incrementPosition(2); /* part length */
                processBitmapUpdates(ts);
                break;
            case 2: /* palette */
                ts.incrementPosition(2);
                processPalette(ts);
                break;
            case 3: /* probably an palette with offset 3. Weird */
                break;
            case 5:
                process_null_system_pointer_pdu(ts);
                break;
            case 6: // default pointer
                break;
            case 9:
                process_colour_pointer_pdu(ts);
                break;
            case 10:
                process_cached_pointer_pdu(ts);
                break;
            case 11:
                process_new_pointer_pdu(ts);
                break;                
            default:
                logger.warn("Unimplemented RDP5 opcode " + type);
            }

            s.setPosition(next);
        }
    }

    /**
     * Process an RDP5 packet from a virtual channel
     * @param s Packet to be processed
     * @param channelno Channel on which packet was received
     */
    void rdp5_process_channel(RdpPacket_Localised s, int channelno) {
        VChannel channel = channels.find_channel_by_channelno(channelno);
        if (channel != null) {
            try {
                channel.process(s);
            } catch (Exception e) {
            }
        }
    }

	@Override
    protected void processVirtualChannelCaps(RdpPacket_Localised data, int capsLength) {
	int flags;

	flags = data.getLittleEndian32();
	if (capsLength == 8)
	{
		this.opt.VCChunkMaxSize = data.getLittleEndian32();
	}

	this.opt.VCCompressionIsSupported = ((flags & VChannels.VCCAPS_COMPR_CS_8K) != 0);

	logger.debug("processVirtualChannelCaps: VChannel compression is "+(this.opt.VCCompressionIsSupported ? "" : "not ")+"supported");
	logger.debug("processVirtualChannelCaps: Chunk maximum size: "+this.opt.VCChunkMaxSize);
    }
}
