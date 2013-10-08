#!/usr/bin/env python
# -*- coding: utf-8 -*-

# Copyright (C) 2012, 2013 Ulteo SAS
# http://www.ulteo.com
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2013
# Author Julien LANGLOIS <julien@ulteo.com> 2013
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

import os
import pygtk
pygtk.require("2.0")
import gtk
import gobject
import time
import logging
import gettext
from optparse import OptionParser
import sys
import locale

from ovd import OvdException
from ovd import OvdExceptionNotAvailable
from ovd import OvdExceptionInternalError
from ovd import Dialog

try:
	_ = gettext.translation("uovdclient", localedir=os.environ.get("LOCALEDIR")).lgettext
except:
	print >>sys.stderr, "Unable to load translation"
	_ = lambda(string): string


tr_codes = {
	"auth_failed":_("Authentication failed: please double-check your password and try again"),
	"in_maintenance":_("The system is on maintenance mode, please contact your administrator for more information"),
	"internal_error":_("An internal error occured, please contact your administrator"),
	"invalid_user":_("You specified an invalid login, please double-check and try again"),
	"service_not_available":_("The service is not available, please contact your administrator for more information"),
	"unauthorized":_("You are not authorized to launch a session. Please contact your administrator for more information"),
	"user_with_active_session":_("You already have an active session"),
	"unable_to_reach_sm":_("Unable to reach the Session Manager"),
	"loading_ovd":_("Connecting to the session manager"),
	"wait_aps":_("Waiting server for session"),
	"session_end_unexpected":_("Your session has ended unexpectedly"),
	"no_sessionmanager_host":_("Usage: missing \"sessionmanager_host\" parameter"),
}


def error(message):
	label = gtk.Label(message)
	dialog = gtk.Dialog(_("Error!"), None, gtk.DIALOG_MODAL | gtk.DIALOG_DESTROY_WITH_PARENT, (gtk.STOCK_OK, gtk.RESPONSE_ACCEPT))
	dialog.set_border_width(10)
	dialog.vbox.pack_start(label)
	label.show()
	dialog.set_position(gtk.WIN_POS_CENTER)
	dialog.run()
	dialog.destroy()


class ProgressBar:
	def __init__(self, client, dialog):
		self.dialog = dialog
		self.client = client
		self.state = 0
		self.timeout = time.time()
		
		self.window = gtk.Window(gtk.WINDOW_TOPLEVEL)
		self.window.set_resizable(False)
		self.window.set_modal(True)
		
		self.window.connect("destroy", self.destroy)
		self.window.set_title(_("Now loading"))
		self.window.set_border_width(10)
		
		vbox = gtk.VBox(False, 5)
		vbox.set_border_width(10)
		self.window.add(vbox)
		vbox.show()
		
		# Create a centering alignment object
		align = gtk.Alignment(0.5, 0.5, 0, 0)
		vbox.pack_start(align, False, False, 5)
		align.show()
		
		self.label = gtk.Label(_("Connecting to the session manager"))
		align.add(self.label)
		self.label.show()

		# Create a centering alignment object
		align = gtk.Alignment(0.5, 0.5, 0, 0)
		vbox.pack_start(align, False, False, 5)
		align.show()
		
		# Create the ProgressBar
		self.pbar = gtk.ProgressBar()
		align.add(self.pbar)
		self.pbar.show()
		
		self.window.set_position(gtk.WIN_POS_CENTER)
		self.window.show()
		
		# Add a timer callback to update the value of the progress bar
		self.timer = gobject.timeout_add(500, self.progress_timeout)


	def progress_timeout(self):
		self.pbar.pulse()
		if self.state == 0:
			extra_args = {}
			try:
				self.dialog.doStartSession(extra_args)
			except OvdExceptionNotAvailable as e:
				error(tr_codes["service_not_available"])
				self.window.destroy()
				return False
			except OvdExceptionInternalError as e:
				error(tr_codes["internal_error"])
				self.window.destroy()
				return False
			except OvdException as e:
				error(tr_codes.get(e.message, _(e.message)))
				self.window.destroy()
				return False
			if self.dialog.sessionProperties["mode"] != "desktop":
				error(_("Internal error: unsupported session mode"))
				return False
			
			self.state = 1
			self.label.set_text(_("Waiting server for session"));
		
		elif self.state == 1:
			status = self.dialog.doSessionStatus()
			logging.debug("status %s"%(str(status)))
			
			if type(status) == type(False):
				logging.error("Error in get status")
				error(_("An error occured, please contact your administrator"))
				self.window.destroy()
				return False
			
			if time.time() - self.timeout > 10.0 and not status in ["init", "ready"]:
				logging.error("Session not ready")
				error(_("An error occured, please contact your administrator"))
				self.window.destroy()
				return False
		
			if status in ["ready"]:
				self.window.hide()
				self.state = 3
		
		elif self.state == 2:
			self.dialog.doLogout()
			logging.debug("end")
			return False
			
		elif self.state == 3:
			self.dialog.doLaunch()
			self.window.destroy()
			logging.debug("end")
			return False
		
		return True
	
	
	def destroy(self, widget, data=None):
		self.state = 2
		self.client.window.show()


class OvdClientGui:
	def __init__(self, options):
		self.options = options
		self.window = gtk.Window(gtk.WINDOW_TOPLEVEL)
		self.window.set_title(options.title)
		
		table = gtk.Table(4, 2, True)
		self.window.add(table)
		
		self.window.connect("delete_event", self.delete_event)
		self.window.connect("destroy", self.destroy)
		self.window.set_border_width(10)
		
		label = gtk.Label(_("Login"))
		label.set_alignment(0, .5)
		table.attach(label, 0, 1, 0, 1)
		label.show()
		self.user = gtk.Entry()
		self.user.set_activates_default(True)
		self.user.set_text(os.environ["USER"])
		table.attach(self.user, 1, 2, 0, 1)
		self.user.show()
		
		label = gtk.Label(_("Password"))
		label.set_alignment(0, .5)
		table.attach(label, 0, 1, 1, 2)
		label.show()
		self.passwd = gtk.Entry()
		self.passwd.set_activates_default(True)
		self.passwd.set_visibility(False)
		table.attach(self.passwd, 1, 2, 1, 2)
		self.passwd.show()
		
		label = gtk.Label(_("Server"))
		label.set_alignment(0, .5)
		table.attach(label, 0, 1, 2, 3)
		self.sm = gtk.Entry()
		self.sm.set_activates_default(True)
		table.attach(self.sm, 1, 2, 2, 3)
		if not options.server:
			label.show()
			self.sm.show()
	
		button = gtk.Button(_("Start!"))
		button.connect("clicked", self.start, None)
		button.set_flags(gtk.CAN_DEFAULT)
		self.window.set_default(button)		
		table.attach(button, 1, 2, 3, 4)
		button.show()
	
		table.show()
		self.window.set_position(gtk.WIN_POS_CENTER)
		self.window.show()
		
		if self.options.username != None:
			self.user.set_text(self.options.username)
			if self.options.username != "":
				self.passwd.grab_focus()
		
		if options.server:
			self.sm.set_text(options.server)


	def start(self, widget, data=None):
		conf = {}
		conf["fullscreen"] = self.options.fullscreen
		if self.options.geometry:
			conf["geometry"] = self.options.geometry.split("x")
		
		conf["host"] = self.sm.get_text()
		conf["login"] = self.user.get_text()
		conf["password"] = self.passwd.get_text()
		conf["client"] = self.options.client
		conf["language"] = self.options.language
		conf["keyboard"] = self.options.keyboard
		conf["compress"] = self.options.compress
		conf["title"] = self.options.title
		conf["use_upn"] = self.options.use_upn
		
		if self.options.drives:
			conf["drives"] = self.options.drives
		
		if conf["login"] == "":
			error("You must specify a username!")
			return
			
		if conf["password"] == "":
			if self.window.get_focus() == self.user:
				self.passwd.grab_focus()
				return
			
			error("You must specify a password!")
			return
		
		if conf["host"] == "":
			if self.window.get_focus() == self.passwd:
				self.sm.grab_focus()
				return
			
			error("You must specify the host field!")
			return
		
		
		self.passwd.set_text("")
		self.passwd.grab_focus()
		d = Dialog(conf)
		
		self.window.hide()
		ProgressBar(self, d)
	
	
	def delete_event(self, widget, event, data=None):
		return False
	
	
	def destroy(self, widget, data=None):
		gtk.main_quit()
	
	
	def main(self):
		gtk.main()


if __name__ == "__main__":
	parser = OptionParser()
	parser.add_option("-v", "--verbose", action="store_true", dest="verbose", default=False, help="Print mode informations")
	parser.add_option("-s", "--server", action="store", type="string", dest="server", default=None, help="Server address")
	parser.add_option("-f", "--fullscreen", action="store_true", dest="fullscreen", default=False, help="Start fullscreen session")
	parser.add_option("-g", "--geometry", action="store", dest="geometry", default=None, help="Set session geometry ex: 800x600")
	parser.add_option("-x", "--freerdp", action="store_const", dest="client", const="freerdp", help="Use freerdp client")
	parser.add_option("-u", "--username", action="store", dest="username", default="", help="Default username")
	parser.add_option("-d", "--drive", action="append", nargs=2, dest="drives", help="Add a redirected drive to the session [name] [path]")
	parser.add_option("-c", "--compress", action="store_true", dest="compress", default=False, help="Enable RDP compression")
	parser.add_option("-t", "--title", action="store", dest="title", default="OVD Light Client", help="Title to show on the windows")
	parser.add_option("--no-upn", action="store_false", dest="use_upn", default=True, help="Use the RDP domain field instead of push the login in UPN syntax (login@domain)")
	
	lang = locale.getdefaultlocale()
	if lang:
		lang = lang[0]
	
	parser.add_option("-l", "--language", action="store", dest="language", default=lang, help="Session language")
	parser.add_option("-k", "--keyboard", action="store", dest="keyboard", default=None, help="Session keyboard")
	
	(options, args) = parser.parse_args()
	
	logger_flags = logging.WARN
	
	if options.verbose:
		logger_flags = logging.DEBUG
	
	logging.basicConfig(level=logger_flags)
	ovd_client_gui = OvdClientGui(options)
	ovd_client_gui.main()
