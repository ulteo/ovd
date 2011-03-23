# -*- coding: utf-8; Mode: Python; indent-tabs-mode: nil; tab-width: 4 -*-
#
# «usersetup» - User creation plugin.
#
# Copyright (C) 2005, 2006, 2007, 2008, 2009, 2010 Canonical Ltd.
#
# Authors:
#
# - Colin Watson <cjwatson@ubuntu.com>
# - Evan Dandrea <evand@ubuntu.com>
# - Roman Shtylman <shtylman@gmail.com>
# - Samuel BOVEE <samuel@ulteo.com>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

import random
import string
import subprocess
import gtk

from ubiquity.install_misc import chroot_setup, chroot_cleanup
from ubiquity.plugin import *

NAME = 'ovd'
AFTER = 'usersetup'
WEIGHT = 10


class PageBase(PluginUI):

    def __init__(self):
        self.allow_password_empty = False

    def set_administrator_login(self, value):
        """Set the user's Unix user name."""
        raise NotImplementedError('set_administrator_login')

    def get_administrator_login(self):
        """Get the user's Unix user name."""
        raise NotImplementedError('get_administrator_login')

    def get_password(self):
        """Get the user's password."""
        raise NotImplementedError('get_password')

    def get_verified_password(self):
        """Get the user's password confirmation."""
        raise NotImplementedError('get_verified_password')

    def administrator_login_error(self, msg):
        """The selected administrator_login was bad."""
        raise NotImplementedError('administrator_login_error')

    def password_error(self, msg):
        """The selected password was bad."""
        raise NotImplementedError('password_error')

    def clear_errors(self):
        pass

    def info_loop(self, *args):
        """Verify user input."""
        pass


class PageGtk(PageBase):

    def __init__(self, controller, *args, **kwargs):
        PageBase.__init__(self, *args, **kwargs)
        self.debug("__init__")
        self.controller = controller

        builder = gtk.Builder()
        self.controller.add_builder(builder)
        builder.add_from_file('/usr/share/ubiquity/gtk/stepUlteo.ui')

        self.page = builder.get_object('stepUlteo')
        self.administrator_login = builder.get_object('administrator_login')
        self.password = builder.get_object('password')
        self.verified_password = builder.get_object('verified_password')
        self.administrator_login_error_reason = builder.get_object('administrator_login_error_reason')
        self.administrator_login_error_box = builder.get_object('administrator_login_error_box')
        self.password_error_reason = builder.get_object('password_error_reason')
        self.password_error_box = builder.get_object('password_error_box')
        self.scrolledwin = builder.get_object('ulteo_scrolledwindow')

        self.administrator_login.set_text('admin')

        # Some signals need to be connected by hand so that we have the
        # handler ids.
        self.password.connect('changed', self.on_passwords_changed)
        self.verified_password.connect('changed', self.on_passwords_changed)

        # A bit ugly, but better to show the scrollbar on the edge cases than
        # not show it when needed.
        if gtk.gdk.get_default_root_window().get_screen().get_height() <= 600:
            self.scrolledwin.set_policy(gtk.POLICY_NEVER, gtk.POLICY_AUTOMATIC)
        self.plugin_widgets = self.page

        # show the IP
        def shell(cmd):
            p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE)
            return p.communicate()[0].strip('\n')
        eth = shell("ifconfig | grep ^eth | head -n 1 | cut -d' ' -f1")
        if eth:
            ip = shell("unset LANG ; ifconfig %s | sed  -rn '/inet addr/ {s/.*addr:(.*)  Bcast.*/\\1/;p}'" % eth)
            if ip:
                builder.get_object('ovd_sm_address').set_text("http://%s/ovd/admin" % ip)
                builder.get_object('ovd_wc_address').set_text("http://%s/ovd" % ip)
                builder.get_object('box_ip').show()

        builder.connect_signals(self)
        self.debug("finished __init__")

    def plugin_translate(self, lang):
        user = self.controller.get_string('administrator_login_inactive_label', lang)
        pasw = self.controller.get_string('password_inactive_label', lang)
        vpas = self.controller.get_string('password_again_inactive_label', lang)
        self.unmatch_passwords_error = self.controller.get_string('ovd_unmatch_passwords', lang)
        self.administrator_login.set_inactive_message(user)
        self.password.set_inactive_message(pasw)
        self.verified_password.set_inactive_message(vpas)

    # Functions called by the Page.

    def set_administrator_login(self, value):
        self.administrator_login.set_text(value)

    def get_administrator_login(self):
        return self.administrator_login.get_text()

    def get_password(self):
        return self.password.get_text()

    def get_verified_password(self):
        return self.verified_password.get_text()

    def administrator_login_error(self, msg):
        self.administrator_login_error_reason.set_text(msg)
        self.administrator_login_error_box.show()

    def password_error(self, msg):
        self.password_error_reason.set_text(msg)
        self.password_error_box.show()

    def clear_errors(self):
        self.administrator_login_error_box.hide()
        self.password_error_box.hide()

    # Callback functions.

    def info_loop(self, widget):
        if self.get_administrator_login() != '' and \
           self.get_password() != '' and \
           self.get_password() == self.get_verified_password():
            self.controller.allow_go_forward(True)
        else:
            self.controller.allow_go_forward(False)

    def on_passwords_changed(self, widget):
        if self.get_password() != self.get_verified_password():
            self.password_error(self.unmatch_passwords_error)
        else:
            self.password_error_box.hide()


class PageDebconf(PageBase):
    plugin_title = 'ubiquity/text/ulteo_heading_label'

    def __init__(self, controller, *args, **kwargs):
        self.controller = controller


class PageNoninteractive(PageBase):
    def __init__(self, controller, *args, **kwargs):
        PageBase.__init__(self, *args, **kwargs)
        self.controller = controller
        self.administrator_login = ''
        self.password = ''
        self.verifiedpassword = ''
        self.console = self.controller._wizard.console

    def set_administrator_login(self, value):
        """Set the user's Unix user name."""
        self.administrator_login = value

    def get_administrator_login(self):
        """Get the user's Unix user name."""
        if self.controller.oem_config:
            return 'oem'
        return self.administrator_login

    def get_password(self):
        """Get the user's password."""
        return self.controller.dbfilter.db.get('passwd/user-password')

    def get_verified_password(self):
        """Get the user's password confirmation."""
        return self.controller.dbfilter.db.get('passwd/user-password-again')

    def administrator_login_error(self, msg):
        """The selected administrator_login was bad."""
        print >>self.console, '\nadministrator_login error: %s' % msg
        self.administrator_login = raw_input('Administrator_Login: ')

    def password_error(self, msg):
        """The selected password was bad."""
        print >>self.console, '\nBad password: %s' % msg
        import getpass
        self.password = getpass.getpass('Password: ')
        self.verifiedpassword = getpass.getpass('Password again: ')

    def clear_errors(self):
        pass


class Page(Plugin):

    def prepare(self, unfiltered=False):
        # We need to call info_loop as we switch to the page so the next button gets disabled.
        self.ui.info_loop(None)

    def ok_handler(self):
        admin_login = self.ui.get_administrator_login().strip()
        self.preseed('ulteo-ovd-session-manager/admin_login', admin_login)
        self.preseed('ulteo-ovd-session-manager/admin_password', self.ui.get_password())
        self.preseed('ulteo-ovd-session-manager/admin_password_again', self.ui.get_password())
        self.preseed('ulteo-ovd-session-manager/tarball_url', "file:///cdrom/Ulteo OVD Archive/base.tar.gz")
        self.preseed('ulteo-ovd-easy-install/mysql_dbuser', admin_login)
        self.preseed('ulteo-ovd-easy-install/mysql_dbpass', self.ui.get_password())

        mysql_passwd = ''.join([random.choice(''.join([string.digits, string.letters, '_'])) for i in range(0, 12)])
        self.preseed('ulteo-ovd-debconf-database/mysql_root_password', mysql_passwd)
        self.preseed('mysql-server/root_password', mysql_passwd)
        self.preseed('mysql-server/root_password_again', mysql_passwd)

        Plugin.ok_handler(self)

    def cleanup(self):
        Plugin.cleanup(self)
        self.frontend.stop_debconf()


class Install(InstallPlugin):
    def prepare(self, unfiltered=False):
        return (['/usr/lib/ubiquity/ovd-setup/ovd-setup'])

    def install(self, target, progress, *args, **kwargs):
        progress.info('ubiquity/install/ovd')
        chroot_setup(target)
        rv = InstallPlugin.install(self, target, progress, *args, **kwargs)
        chroot_cleanup(target)
        return rv

