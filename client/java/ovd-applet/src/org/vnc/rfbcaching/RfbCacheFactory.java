package org.vnc.rfbcaching;


// Factory class to create RFBcaches of different type
public class RfbCacheFactory implements IRfbCacheFactory {
	
//	public RfbCacheFactory()
//		{
////		super();
//		}

	public IRfbCache CreateRfbCache(RfbCacheProperties p)
	{	
		try{
			return new RfbCache(p);
		}catch(RfbCacheCreateException e){
			System.out.println("Could not create Cache");
			return null;
		}
	}

}
