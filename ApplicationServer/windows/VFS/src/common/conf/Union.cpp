/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

#include "Union.h"



Union::Union(std::string& name): name(name), deleteOnClose(false) { }


Union::~Union() { }


void Union::setPath(std::string& path) {
	this->path = path;
}


void Union::setDeleteOnClose(bool value) {
	this->deleteOnClose = value;
}


void Union::setRsyncSrc(std::string src) {
	this->rsyncSrc = src;
}


void Union::setRsyncFilter(std::string filter) {
	this->rsyncFilter = filter;
}


std::string& Union::getName() {
	return this->name;
}


std::string& Union::getPath() {
	return this->path;
}


bool Union::isDeleteOnClose() {
	return this->deleteOnClose;
}


std::string& Union::getRsyncSrc() {
	return this->rsyncSrc;
}


std::string& Union::getRsyncFilter() {
	return this->rsyncFilter;
}

