from SOAPpy import WSDL

class Proxy(WSDL.Proxy):
	#/usr/lib/pymodules/python2.6/SOAPpy/
	def __getattr__(self, name):
		if not self.methods.has_key(name): raise AttributeError, name
		
		callinfo = self.methods[name]
		#self.soapproxy.proxy = SOAPAddress(callinfo.location)
		self.soapproxy.namespace = callinfo.namespace
		self.soapproxy.soapaction = callinfo.soapAction
		return self.soapproxy.__getattr__(name)

# http://pypi.python.org/pypi/SOAPpy/
#  888345: Python 2.3 boolean type serialized as int


if __name__ == "__main__":
	LOGIN = "admin"
	PASSWORD = "admin"
	SM_HOST = "127.0.0.1"
	WSDL_PATH = "ovd_admin.wsdl"
	
	SERVICE_URL = "https://%s:%s@%s/ovd/service/admin"%(LOGIN, PASSWORD, SM_HOST)
	
	proxy = Proxy(WSDL_PATH)
	proxy.soapproxy = WSDL.SOAPProxy(SERVICE_URL)
	
	r = proxy.publication_add("static_2", "2")
	
	print r
