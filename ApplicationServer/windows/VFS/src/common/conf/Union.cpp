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
#include <common/fs/File.h>



Union::Union(std::wstring& name): name(name), deleteOnClose(false), translate(true) { }


Union::~Union() { }


void Union::setPath(std::wstring& path) {
	this->path = path;
}


void Union::setDeleteOnClose(bool value) {
	this->deleteOnClose = value;
}


void Union::setRsyncSrc(std::wstring src) {
	this->rsyncSrc = src;
}


void Union::setRsyncFilter(std::wstring filter) {
	this->rsyncFilter = filter;
}


std::wstring& Union::getName() {
	return this->name;
}


std::wstring& Union::getPath() {
	return this->path;
}


bool Union::isDeleteOnClose() {
	return this->deleteOnClose;
}


bool Union::needTranslate() {
	return this->translate;
}


void Union::setTranslate(bool value) {
	this->translate = value;
}


std::wstring& Union::getRsyncSrc() {
	return this->rsyncSrc;
}


std::wstring& Union::getRsyncFilter() {
	return this->rsyncFilter;
}


std::list<std::wstring>& Union::getpredefinedDirectoryList() {
	return this->predefinedDirectory;
}


void Union::addPredefinedDirectory(std::wstring& dir){
	File d(dir);
	if (d.isAbsolute()) {
		this->predefinedDirectory.push_back(d.path());
		return;
	}

	File root(this->path);
	root.join(dir);
	this->predefinedDirectory.push_back(root.path());
}
