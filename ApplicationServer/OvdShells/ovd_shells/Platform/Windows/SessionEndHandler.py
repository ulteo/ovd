# -*- coding: utf-8 -*-

# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author David LECHEVALIER <david@ulteo.com> 2013
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

import win32con
import win32api
import win32gui
import sys
import time
import threading


instance = None


def wndproc(hwnd, msg, wparam, lparam):
	if instance is not None:
		instance.fire()
		

class SessionEndHandler():
	def __init__(self):
		global instance
		instance = self
		self.handler = []
	
	def register(self, func, arg=None):
		self.handler.append((func, arg))
	
	
	def fire(self):
		for (func, arg) in self.handler:
			if arg is None:
				func()
			else:
				func(arg)
	
		
	def start(self):
		threading.Thread(target=self.run).start()
	
	
	def run(self):
		hinst = win32api.GetModuleHandle(None)
		wndclass = win32gui.WNDCLASS()
		wndclass.hInstance = hinst
		wndclass.lpszClassName = "ulteoEventHandler"
		messageMap = { 	win32con.WM_QUERYENDSESSION : wndproc }
		wndclass.lpfnWndProc = messageMap
		
		try:
			myWindowClass = win32gui.RegisterClass(wndclass)
			hwnd = win32gui.CreateWindowEx(win32con.WS_EX_LEFT, myWindowClass, "ulteoEH", 0, 0, 0, win32con.CW_USEDEFAULT, win32con.CW_USEDEFAULT, 0, 0, hinst, None)
		except Exception, e:
			print ("Failed to create internal windows: %s" % str(e))
		
		while True:
			win32gui.PumpWaitingMessages()
			time.sleep(1)
