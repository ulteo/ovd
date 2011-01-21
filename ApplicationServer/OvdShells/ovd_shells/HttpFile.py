# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
#
# This program is free software; you can redistribute it and/or 
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

import httplib
import mimetools
import mimetypes
import os
import urllib2


class HttpFile:
	def __init__(self, url, path):
		self.url = url
		self.root = os.path.join(os.path.expanduser("~"), "remote")
		self.path = os.path.join(self.root, path)
		
		d = os.path.dirname(self.path)
		if not os.path.exists(d):
			os.makedirs(d)
	
	
	def recv(self):
		req = urllib2.Request(self.url)
		try:
			stream = urllib2.urlopen(req)
		except IOError, err:
			print "IOError:",err
			return False
		except httplib.BadStatusLine, err:
			print "HTTP error:",err
			return False
		
		try:
			f = file(self.path, "wb")
		except IOError, err:
			print "Unable to create file",self.path
		f.write(stream.read())
		f.close()
		
		return True
	
	
	def send(self):
		try:
			f = file(self.path, "rb")
		except IOError, err:
			print "Unable to read file",self.path
			return False
		content = f.read()
		f.close()
		
		content_type, body = self.encode_multipart_formdata([], [("file", self.path, content)])
		headers = {'Content-Type': content_type,
			  'Content-Length': str(len(body))
			  }
		
		req = urllib2.Request(self.url, body, headers)
		
		try:
			stream = urllib2.urlopen(req)
		except IOError, err:
			print "IOError:",err
			return False
		except httplib.BadStatusLine, err:
			print "HTTP error:",err
			return False
		
		return True
	
	
	## Code from http://code.activestate.com/recipes/146306-http-client-to-post-using-multipartform-data/
	@staticmethod
	def encode_multipart_formdata(fields, files):
		"""
		fields is a sequence of (name, value) elements for regular form fields.
		files is a sequence of (name, filename, value) elements for data to be uploaded as files
		Return (content_type, body) ready for httplib.HTTP instance
		"""
		BOUNDARY = mimetools.choose_boundary()
		CRLF = '\r\n'
		L = []
		for (key, value) in fields:
			L.append('--' + BOUNDARY)
			L.append('Content-Disposition: form-data; name="%s"' % key)
			L.append('')
			L.append(value)
		for (key, filename, value) in files:
			L.append('--' + BOUNDARY)
			L.append('Content-Disposition: form-data; name="%s"; filename="%s"' % (key, filename))
			L.append('Content-Type: %s' % (mimetypes.guess_type(filename)[0] or 'application/octet-stream'))
			L.append('')
			L.append(value)
		L.append('--' + BOUNDARY + '--')
		L.append('')
		body = CRLF.join(L)
		content_type = 'multipart/form-data; boundary=%s' % BOUNDARY
		return content_type, body


if __name__ == "__main__":
	http = HttpFile("http://www.ulteo.com", "index.html")
	print http.path
	print http.url
	
	http.recv()
	#http.send()
