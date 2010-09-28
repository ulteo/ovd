package net.propero.rdp;


public interface RdpListener {
	public void connecting(RdpConnection co);
	public void seamlessEnabled(RdpConnection co);
	public void connected(RdpConnection co);
	public void disconnected(RdpConnection co);
	public void failed(RdpConnection co, String msg);
}
