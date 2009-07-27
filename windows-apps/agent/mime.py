# Copyright (C) 2009 Ulteo SAS
# Author: Gauvain Pocentek <gauvain@ulteo.com>
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

import re
from _winreg import *

# _winreg.ExpandEnvironmentStrings only appears in python 2.6
try:
	ExpandEnvironmentStrings("%TMP%")
except:
	from win32api import ExpandEnvironmentStrings

ROOT = HKEY_CLASSES_ROOT

class MimeInfos():
	def __init__(self):
		ConnectRegistry(None, ROOT)
		self.ext_keys = {}
		self.mime_keys = {}
		self.extensions = []
		self.mimetypes = []
		self.ext_mime = {}
		self.get_all_extensions()
		self.get_all_mimetypes()

		# array mimetype => extension
		for mimetype in self.mimetypes:
			self.get_mime_ext_assoc(mimetype)

		for extension in self.extensions:
			self.ext_keys[extension] = {"apps": [], "type": None}
			self.get_default_for_ext(extension)
			self.get_progids_for_ext(extension)
			self.get_openwithlist_for_ext(extension)
			self.append_mime_apps_for_ext(extension)


	def _replace(self, cmd):
		r = re.compile(r'("?)%[l0-9]+("?)')
		cmd = r.sub(r'\1%f\2', cmd)
		r = re.compile(r'("?)%[L]+("?)')
		cmd = r.sub(r'\1%F\2', cmd)
		return cmd

	def _get_app_path(self, app):
		try:
			key = OpenKey(ROOT, r"%s\shell\open\command"%app)
			value = QueryValue(key, None)
			CloseKey(key)
			return self._replace(value)
		except:
			return

	def _get_command(self, extension, k, action):
		try:
			key = OpenKey(ROOT, r"%s\shell\%s\command"%(k,action))
			ret = ExpandEnvironmentStrings(EnumValue(key, 0)[1])#.split('"')[1]
			if ret and ret not in self.ext_keys[extension]["apps"]:
				self.ext_keys[extension]["apps"].append(self._replace(ret))
			CloseKey(key)
		except:
			return

	def get_all_extensions(self):
		i = 0
		while True:
			try:
				t = EnumKey(ROOT, i)
				if t.startswith('.'):
					self.extensions.append (t)
				i += 1
			except:
				break

	def get_default_for_ext(self,extension):
		key = OpenKey(ROOT, extension)
		if key:
			try:
				value = QueryValue(key, None)
			except:
				value = None
			if value:
				path = self._get_app_path(value)
				if path:
					self.ext_keys[extension]["apps"] = [path]

			try:
				value = QueryValueEx(key,"Content Type")
				self.ext_keys[extension]["type"] = value[0]
			except:
				self.ext_keys[extension]["type"] = "application/x-extension-%s"%extension[1:]
		CloseKey(key)

	def get_progids_for_ext(self, extension):
		try:
			key = OpenKey(ROOT, r"%s\OpenWithProgids"%extension)
			i = 0
			while True:
				try:
					val = EnumValue(key, i)
					path = self._get_app_path(val[0])
					if path and path not in self.ext_keys[extension]["apps"]:
						self.ext_keys[extension]["apps"].append(self._replace(path))
					i += 1
				except:
					break
			CloseKey(key)
		except:
			pass

	def get_openwithlist_for_ext(self, extension):
		try:
			key = OpenKey(ROOT, r"%s\OpenWithList"%extension)
			i = 0
			while True:
				try:
					val = EnumKey(key, i)
					if val:
						self._get_command(extension, "Applications\%s"%val, "open")
						self._get_command(extension, "Applications\%s"%val, "edit")
					i += 1
				except:
					break
			CloseKey(key)
		except:
			pass

	def append_mime_apps_for_ext(self, extension):
		try:
			l = self.ext_mime[self.ext_keys[extension]["type"]]
			if not l:
				return
			for item in self.ext_keys[l]["apps"]:
				if item and item not in self.ext_keys[extension]["apps"]:
					self.ext_keys[extension]["apps"].append (self._replace(item))
		except:
			return



	def get_all_mimetypes(self):
		i = 0
		key = OpenKey(ROOT, r"MIME\Database\Content Type")
		while True:
			try:
				t = EnumKey(key, i)
				self.mimetypes.append (t)
				i += 1
			except:
				break
		CloseKey(key)

	def get_mime_ext_assoc(self, mimetype):
		try:
			key = OpenKey(ROOT, r"MIME\Database\Content Type\%s"%mimetype)
			value = QueryValueEx(key,"Extension")[0]
			self.ext_mime[mimetype] = value

			self.mime_keys[mimetype] = [x for x in self.ext_keys[value]["apps"]]
		except:
			pass


if __name__ == "__main__":
	t = MimeInfos()

	f = open("list.txt", "w")
	for extension in t.extensions:
		f.write("%s => "%extension)
		if t.ext_keys[extension]["type"]:
			f.write(t.ext_keys[extension]["type"])
		f.write("\n")
		for item in t.ext_keys[extension]["apps"]:
			f.write("  %s\n"%item)

	f.close()

