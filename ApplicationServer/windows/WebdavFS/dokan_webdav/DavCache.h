/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2010
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

#ifndef DAVCACHE_H_
#define DAVCACHE_H_

#include <Shlobj.h>
#include <Shellapi.h>
#include "WebdavServer.h"


#define DAV_CACHE_SIZE          1024
#define INVALID_CACHE_HANDLE    DAV_CACHE_SIZE
#define DAV_CACHE_DIR_SUFFIXE   L"Ulteo\\DavCache"

typedef struct _DAVCACHEENTRY
{
	BOOLEAN isSet;
	BOOLEAN needExport;
	BOOLEAN needImport;
	BOOLEAN needRemove;
	WCHAR remotePath[MAX_PATH];
	WCHAR cachePath[MAX_PATH];
} DAVCACHEENTRY, *PDAVCACHEENTRY;


class DavCache {
private:
	WebdavServer *davServer;
	DAVCACHEENTRY cache[DAV_CACHE_SIZE];
	WCHAR* cacheDir;
	int count;


public:
	DavCache();
	~DavCache();

	int init(WebdavServer* server);
	void clean();
	ULONG64 add(WCHAR* path);
	int remove(ULONG64 handle);
	PDAVCACHEENTRY getFromHandle(ULONG64 handle);
	PDAVCACHEENTRY getFromPath(WCHAR* path);
	WCHAR* createCacheDir();
	ULONG64 getNextEmptyEntry();
	ULONG64 DavCache::getHandleFromPath(WCHAR* path);

};

#endif /* DAVCACHE_H_ */
