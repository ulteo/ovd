package net.propero.rdp;


public interface RdpListener {
	public void connected(RdpConnection co);
	public void connecting(RdpConnection co);
	public void failed(RdpConnection co);
	public void disconnected(RdpConnection co);
}
