# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
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

from multiprocessing.reduction import reduce_socket
import pickle
from SocketServer import BaseRequestHandler

from Config import Config
from ovd.Logger import Logger


class GatewayTCPHandler(BaseRequestHandler):

	def handle(self):
		self.role.kill_mutex.acquire()
		
		best_proc = None
		for pid, proc in self.role.processes.items():
			ctrl = proc[0][1]
			nb_conn = ctrl.send('nb_conn')
			self.role.processes[pid][1] = nb_conn
			if nb_conn < Config.max_connection:
				best_proc = pid
				break
		if best_proc is None:
			if len(self.role.processes) < Config.max_process:
				best_proc = self.role.create_process()
			else:
				best_proc = min(self.role.processes, key=lambda pid:self.role.processes[pid][1])
				Logger.warn("Gateway service has reached the open connections limit")
		
		ctrl = self.role.processes[best_proc][0][1]
		pickled_sock = pickle.dumps(reduce_socket(self.request)[1])
		ctrl.send(('socket', pickled_sock))
		
		self.role.kill_mutex.release()
