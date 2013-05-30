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

#include "VFSRule.h"
#include <common/Logger.h>
#include <exception>


VFSRule::VFSRule(const std::wstring& rule, const std::wstring& unionName): rule(rule), unionName(unionName), reg(NULL) { }

VFSRule::~VFSRule() { }


bool VFSRule::compile() {
	try {
		this->reg = new std::wregex(rule);
	}
	catch (const std::exception& e) {
		log_error(L"Failed to compile rule: %s: %s", this->rule.c_str(), e.what());
		return false;
	}


	return true;
}


bool VFSRule::match(std::wstring path) {

	return true;
}
