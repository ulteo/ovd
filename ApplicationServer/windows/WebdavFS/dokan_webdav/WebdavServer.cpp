/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
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

#define COBJMACROS

#include "WebdavServer.h"
#include "XMLDavParser.h"
#include "debug.h"

extern BOOL g_UseStdErr;
extern BOOL g_DebugMode;


WebdavServer::WebdavServer(wchar_t* address_, int port_, wchar_t* prefixe_, wchar_t* username_, wchar_t* password_, BOOL useHTTPS_) {
	int len = 0;
	port = port_;
	wcscpy_s(prefixe, MAX_PATH, prefixe_ );
	wcscpy_s(address, MAX_PATH, address_);

	len = lstrlen(prefixe);
	if (prefixe[len-1] == '/') {
		prefixe[len-1] = '\0';
	}

	hSession = NULL;
	hConnect = NULL;
	useHTTPS = useHTTPS_;

	if (username_) {
		wcscpy_s(user, 256, username_);
		wcscpy_s(password, 256, password_);
	}
}

WebdavServer::~WebdavServer() {
	if (hSession)
		WinHttpCloseHandle(hSession);
}


HRESULT WebdavServer::init() {
	DWORD dwErr = 0;
	hSession = WinHttpOpen( L"Ulteo WebdavFS", WINHTTP_ACCESS_TYPE_DEFAULT_PROXY, WINHTTP_NO_PROXY_NAME, WINHTTP_NO_PROXY_BYPASS, 0 );

	if( hSession == NULL)
	{
		dwErr = GetLastError();
		switch (dwErr)
		{
		case ERROR_WINHTTP_AUTO_PROXY_SERVICE_ERROR:
			DbgPrint(L"ERROR_WINHTTP_AUTO_PROXY_SERVICE_ERROR\n");
		break;
		case ERROR_WINHTTP_INTERNAL_ERROR:
			DbgPrint(L"ERROR_WINHTTP_INTERNAL_ERROR\n");
		break;
		case ERROR_WINHTTP_OPERATION_CANCELLED:
			DbgPrint(L"ERROR_WINHTTP_OPERATION_CANCELLED\n");
		break;
		case ERROR_NOT_ENOUGH_MEMORY:
			DbgPrint(L"ERROR_NOT_ENOUGH_MEMORY\n");
		break;
		case ERROR_WINHTTP_AUTODETECTION_FAILED:
			DbgPrint(L"ERROR_WINHTTP_AUTODETECTION_FAILED\n");
		break;
		case ERROR_WINHTTP_BAD_AUTO_PROXY_SCRIPT:
			DbgPrint(L"ERROR_WINHTTP_BAD_AUTO_PROXY_SCRIPT\n");
		break;
		case ERROR_WINHTTP_INCORRECT_HANDLE_TYPE:
			DbgPrint(L"ERROR_WINHTTP_INCORRECT_HANDLE_TYPE\n");
		break;
		case ERROR_WINHTTP_INVALID_URL:
			DbgPrint(L"ERROR_WINHTTP_INVALID_URL\n");
		break;
		case ERROR_WINHTTP_LOGIN_FAILURE:
			DbgPrint(L"ERROR_WINHTTP_LOGIN_FAILURE\n");
		break;
		case ERROR_WINHTTP_UNABLE_TO_DOWNLOAD_SCRIPT:
			DbgPrint(L"ERROR_WINHTTP_UNABLE_TO_DOWNLOAD_SCRIPT\n");
		break;
		case ERROR_WINHTTP_UNRECOGNIZED_SCHEME:
			DbgPrint(L"ERROR_WINHTTP_UNRECOGNIZED_SCHEME\n");
		break;
		default:
			DbgPrint(L"Unknow Error\n");
		}
	}
	return dwErr;
}

void WebdavServer::getAbsolutePath(WCHAR* dest, WCHAR* path) {
	WCHAR prefix[MAX_PATH] = {0};
	WCHAR* escaped = NULL;

	if (useHTTPS)
		wcscpy_s(prefix, sizeof(prefix), L"https://");
	else
		wcscpy_s(prefix, sizeof(prefix), L"http://");

	wcscat_s(prefix, sizeof(prefix), address);
	swprintf_s(dest, MAX_PATH, L"%s:%i%s", prefix, port, path);
	escaped = DavEntry::urlencode(dest);
	if (escaped == NULL) {
		return;
	}
	wcscpy_s(dest, MAX_PATH, escaped);
	free(escaped);
}


BOOL WebdavServer::connect() {
	// Specify an HTTP server.
	if (! hSession )
	{
		DbgPrint(L"WinHTTP session did not exist\n");
		return FALSE;
	}
	hConnect = WinHttpConnect( hSession, address, (INTERNET_PORT)port, 0);
	// Create an HTTP request handle.
	if( hConnect ) {
		return TRUE;
	}
	DbgPrint(L"Failed to etablish conncetion to %s:%i\n",address, port );
	return FALSE;
}

BOOL WebdavServer::disconnect() {
	// Specify an HTTP server.
	if (! hSession )
	{
		DbgPrint(L"WinHTTP session did not exist\n");
		return FALSE;
	}
	if ( hConnect) {
	  WinHttpCloseHandle(hConnect);
	  hConnect = NULL;
	  return TRUE;
	}
	DbgPrint(L"Failed to etablish conncetion to %s:%i\n", address, port );
	return FALSE;
}


HINTERNET WebdavServer::requestNew(wchar_t* method, wchar_t* path, BOOL redirected)
{
	HINTERNET hRequest = NULL;
	DWORD dwOptionValue = 0;
	DWORD dwFlags = 0;
	BOOL bSetOk = FALSE;
	WCHAR* escaped = NULL;
	WCHAR* encodedPath = NULL;

	encodedPath = path;
	if (! redirected) {
		escaped = DavEntry::urlencode(path);
		encodedPath = escaped;
	}

	if (! hConnect) {
		DbgPrint(L"No connection to server: %s:%i\n", address, port);
		return NULL;
	}

	if (useHTTPS)
	{
		dwFlags |= WINHTTP_FLAG_SECURE;
	}
	hRequest = WinHttpOpenRequest(hConnect, method, encodedPath, HTTP_VERSION, WINHTTP_NO_REFERER, WINHTTP_DEFAULT_ACCEPT_TYPES, dwFlags );

	if (hRequest && user)
	{
		if (useHTTPS) {
			//Used to bypass cert.
			dwOptionValue = SECURITY_FLAG_IGNORE_CERT_CN_INVALID
							| SECURITY_FLAG_IGNORE_CERT_DATE_INVALID
							| SECURITY_FLAG_IGNORE_UNKNOWN_CA
							| SECURITY_FLAG_IGNORE_CERT_WRONG_USAGE;

			bSetOk = WinHttpSetOption(hRequest, WINHTTP_OPTION_SECURITY_FLAGS, &dwOptionValue, sizeof(DWORD));
		}
		if (! WinHttpSetCredentials( hRequest, 	WINHTTP_AUTH_TARGET_SERVER,	WINHTTP_AUTH_SCHEME_BASIC, user, password, NULL ))
		{
			DbgPrint(L"Unable to set credentials\n");
		}

		dwOptionValue = WINHTTP_DISABLE_REDIRECTS;

		bSetOk = WinHttpSetOption(hRequest,	WINHTTP_OPTION_DISABLE_FEATURE,	&dwOptionValue,	sizeof(dwOptionValue));
	}


	if (escaped)
		free(escaped);

	if (hRequest)
		return hRequest;

	DbgPrint(L"Unable to create a DAV request for method %s\n", method );
	return NULL;
}


BOOL WebdavServer::requestDel(HINTERNET hRequest ) {
	if ( hRequest)
	{
		WinHttpCloseHandle(hRequest);
		return TRUE;
	}
	DbgPrint(L"Unable to delete request\n");
	return FALSE;
}

DWORD WebdavServer::getStatus(HINTERNET hRequest) {
	DWORD size;
	DWORD status;
	BOOL bResults;

	if (hRequest) {
		size = sizeof(DWORD);
		WinHttpReceiveResponse(hRequest,0);
		bResults = WinHttpQueryHeaders(hRequest, WINHTTP_QUERY_STATUS_CODE| WINHTTP_QUERY_FLAG_NUMBER, NULL, &status, &size, NULL );
		if (bResults)
			return status;
	}
	DbgPrint(L"Unable get status\n");
	return 0;
}

BOOL WebdavServer::path_join(WCHAR* dest_, WCHAR* prefixe_, WCHAR* path_) {
	if (dest_ == NULL || prefixe_ == NULL || path_ == NULL) {
		return FALSE;
	}

	if (path_[0] == '/') {
		swprintf_s(dest_, MAX_PATH, L"%s%s", prefixe_, path_);
	}
	else {
		swprintf_s(dest_, MAX_PATH, L"%s/%s", prefixe_, path_);
	}
	return TRUE;
}


WCHAR* WebdavServer::getRedirectPath(HINTERNET hRequest)
{
	BOOL bResults;
	LPWSTR redirectURL;
	DWORD dwSize;
	URL_COMPONENTS urlComp;
	WCHAR* ret = NULL;

	WinHttpQueryHeaders( hRequest, WINHTTP_QUERY_LOCATION, WINHTTP_HEADER_NAME_BY_INDEX, NULL, &dwSize, WINHTTP_NO_HEADER_INDEX);

	if( GetLastError( ) == ERROR_INSUFFICIENT_BUFFER )
	{
		redirectURL = (WCHAR*)malloc(dwSize*sizeof(WCHAR));
		// Now, use WinHttpQueryHeaders to retrieve the header.
		bResults = WinHttpQueryHeaders( hRequest, WINHTTP_QUERY_LOCATION, WINHTTP_HEADER_NAME_BY_INDEX, redirectURL, &dwSize, WINHTTP_NO_HEADER_INDEX);
		if (! bResults)
		{
			return NULL;
		}
	}
	else
	{
		DbgPrint(L"Unknow error : %u\n", GetLastError());
		return NULL;
	}
	//crack the url
	ZeroMemory(&urlComp, sizeof(urlComp));

	// Set required component lengths to non-zero
	// so that they are cracked.
	urlComp.dwSchemeLength    = (DWORD)MAX_PATH;
	urlComp.dwHostNameLength  = (DWORD)MAX_PATH;
	urlComp.dwUrlPathLength   = (DWORD)MAX_PATH;
	urlComp.lpszScheme    = (WCHAR*)malloc(MAX_PATH*sizeof(WCHAR));
	urlComp.lpszHostName  = (WCHAR*)malloc(MAX_PATH*sizeof(WCHAR));
	urlComp.lpszUrlPath   = (WCHAR*)malloc(MAX_PATH*sizeof(WCHAR));
	urlComp.dwExtraInfoLength = (DWORD)-1;
	urlComp.dwStructSize = sizeof(urlComp);

	// Crack the URL.
	bResults = WinHttpCrackUrl(redirectURL, (DWORD)wcslen(redirectURL), 0, &urlComp);
	if(redirectURL)
	{
		free(redirectURL);
		redirectURL = NULL;
	}

	if (!bResults)
	{
		DbgPrint(L"Unable to separate component of the url (%u)\n", GetLastError());
		ret = NULL;
	}
	else
	{
		ret = _wcsdup(urlComp.lpszUrlPath);
	}

	free(urlComp.lpszHostName);
	free(urlComp.lpszScheme);
	free(urlComp.lpszUrlPath);
	return ret;
}


LPSTR WebdavServer::getData( HINTERNET hRequest, PDWORD dwSize, PDWORD status)
{
	DWORD tempSize = 0;
	BOOL bResults;
	DWORD size = 0;
	LPSTR pszOutBuffer = NULL;
	DWORD dwDownloaded = 0;
	LPSTR pszBuffer = NULL;
	LPSTR temp = NULL;
//	HINTERNET hRequest2;


	if (!hRequest)
	{
		DbgPrint(L"No request data to get\n");
		return NULL;
	}
	size = sizeof(DWORD);
	*status =400;
	bResults = WinHttpQueryHeaders(hRequest, WINHTTP_QUERY_STATUS_CODE| WINHTTP_QUERY_FLAG_NUMBER,
	                               NULL, status, &size, NULL );

	if (!bResults)
	{
		DbgPrint(L"Unable to query the http header %i\n", GetLastError());
		return NULL;
	}
	if ((*status <200) || (*status >= 400))
	{
		return NULL;
	}

	*dwSize = 0;
	tempSize = 1;
	while (tempSize > 0)
	{
		// Check for available data.

		tempSize = 0;
		if (!WinHttpQueryDataAvailable( hRequest, &tempSize)) {
			DbgPrint(L"Error %u in WinHttpQueryDataAvailable.\n", GetLastError());
			return NULL;
		}
		if (tempSize == 0)
		{
			break;
		}
		// Allocate space for the buffer.
		pszOutBuffer = (LPSTR)malloc(tempSize+1);

		if (!pszOutBuffer)
		{
			DbgPrint(L"Out of memory\n");
			tempSize=0;
			return NULL;
		}
		else
		{
			// Read the Data.

			ZeroMemory(pszOutBuffer, tempSize+1);
			if (!WinHttpReadData( hRequest, (LPVOID)pszOutBuffer, tempSize, &dwDownloaded))
			{
				DbgPrint(L"Error %u in WinHttpReadData.\n", GetLastError());
			}
			if (*dwSize == 0)
			{
				pszBuffer = (LPSTR)malloc(tempSize+1);
				ZeroMemory(pszBuffer, tempSize+1);
				if (! pszBuffer)
				{
					DbgPrint(L"Error %u in malloc.\n", GetLastError());
					return NULL;
				}
				memcpy(pszBuffer, pszOutBuffer, tempSize);
			}
			else
			{
				temp = (LPSTR)realloc(pszBuffer, *dwSize+tempSize+1);
				if (temp)
				{
					pszBuffer = temp;
				}
				else
				{
					DbgPrint(L"Error while realloc: %u.\n", GetLastError());
					free(pszBuffer);
					return NULL;
				}

				pszBuffer[*dwSize+tempSize+1] = 0;
				memcpy(&pszBuffer[*dwSize], pszOutBuffer, tempSize);
			}
			*dwSize += tempSize;
			free(pszOutBuffer);
			pszOutBuffer = NULL;

  		}
	};
	if (pszBuffer)
	{
		pszBuffer[*dwSize] = 0;
	}
	return pszBuffer;
 }

DAVFILEINFO* WebdavServer::DAVGetFileInformations(LPCWSTR path) {
	WCHAR* path2;
	DAVFILEINFO* fsinfo;
	HINTERNET hRequest;
	FILETIME* time;
	DavEntry::FileType type = DavEntry::file;
	LARGE_INTEGER file_size;
	int filename_length = 0;

	fsinfo = (DAVFILEINFO*)malloc(sizeof(DAVFILEINFO));
	path2 = (WCHAR*)malloc(MAX_PATH*sizeof(WCHAR));
	fsinfo->isDir = FALSE;

	if (! path_join(path2, prefixe, (WCHAR*)path)) {
		DbgPrint(L"Unable to merge %s and %s\n", prefixe, path);
		return NULL;
	}
	hRequest = DAVPROPFind(&path2, DEFAULT_PROPFIND, 0, FALSE);
	if (! hRequest) {
		if (hRequest) requestDel(hRequest);
		return NULL;
	}
	
	XMLDavParser pouet(hRequest);
	if (pouet.getLastError() != S_OK) {
		DbgPrint(L"Unable to initialize the dav parser\n");
		return NULL;
	}

	pouet.init();
	pouet.parse();
	std::list<DavEntry> result = pouet.getResult();

	fsinfo = (DAVFILEINFO*)malloc(sizeof(DAVFILEINFO));
	ZeroMemory(fsinfo, sizeof(DAVFILEINFO));
	DavEntry &entry = (DavEntry&)result.front();
	WCHAR* filename = NULL;
	type = entry.getType();

	if (type == DavEntry::directory) {
		fsinfo->isDir = TRUE;
		fsinfo->fileAttributes = FILE_ATTRIBUTE_DIRECTORY;
	}
	else {
		fsinfo->isDir = FALSE;
		fsinfo->fileAttributes = FILE_ATTRIBUTE_NORMAL;
	}
	file_size.QuadPart = entry.getLength();
	if (file_size.QuadPart == 0) {
		fsinfo->nFileSizeLow = 0 ;
		fsinfo->nFileSizeHigh = 0 ;
	}
	else {
		fsinfo->nFileSizeLow = file_size.LowPart;
		fsinfo->nFileSizeHigh = file_size.HighPart;
	}
	time = entry.getCreationTime();
	fsinfo->creationTime.dwLowDateTime = time->dwLowDateTime;
	fsinfo->creationTime.dwHighDateTime = time->dwHighDateTime;
	time = entry.getLastModifiedTime();
	fsinfo->lastModified.dwLowDateTime = time->dwLowDateTime;
	fsinfo->lastModified.dwHighDateTime = time->dwHighDateTime;

	filename = PathFindFileName(entry.getPath());
	filename_length = lstrlen(filename);
	if (filename[filename_length-1] == '/') {
		filename[filename_length-1] = '\0';
	}

	wcscpy_s(fsinfo->name, MAX_PATH, filename);
	pouet.release();

	if (hRequest) requestDel(hRequest);

	return fsinfo;
}


DAVFILEINFO* WebdavServer::DAVGetDirectoryList(LPCWSTR path, PDWORD count ) {
	WCHAR* path2;
	HINTERNET hRequest;
	XMLDavParser* parser = NULL;
	DAVFILEINFO* dirList = NULL;
	FILETIME* time;
	DavEntry::FileType type = DavEntry::file;
	LARGE_INTEGER file_size;
	int index = 0;
	int filename_length = 0;

	path2 = (WCHAR*)malloc(MAX_PATH*sizeof(WCHAR));
	if (! path_join(path2, prefixe, (WCHAR*)path)) {
		DbgPrint(L"Unable to merge %s and %s\n", prefixe, path);
		return NULL;
	}
	hRequest = DAVPROPFind(&path2, DEFAULT_PROPFIND, 1, FALSE);
	if (!hRequest)
		DbgPrint(L"ERROR while DAVPROPFIND\n");
	
	parser = new XMLDavParser(hRequest);
	if (parser->getLastError() != S_OK) {
		DbgPrint(L"Unable to initialize the dav parser\n");
		delete parser;
		return NULL;
	}
	parser->init();
	parser->parse();
	std::list <DavEntry>::const_iterator iter;
	std::list<DavEntry> result = parser->getResult();

	*count = result.size() - 1;
	if (*count <= 0) {
		parser->release();
		delete parser;

		if (hRequest) requestDel(hRequest);
		return NULL;
	}
	dirList = (DAVFILEINFO*)malloc(sizeof(DAVFILEINFO)*(*count));
	ZeroMemory(dirList, sizeof(DAVFILEINFO)*(*count));

	for ( iter = result.begin(); iter != result.end( ); iter++ ) {
		if (index == 0) {
			iter++;
		}
		DAVFILEINFO* fsinfo = &dirList[index];
		DavEntry &entry = (DavEntry&)*iter;
		WCHAR* filename = NULL;

		type = entry.getType();
		if (type == DavEntry::directory) {
			fsinfo->isDir = TRUE;
			fsinfo->fileAttributes = FILE_ATTRIBUTE_DIRECTORY;
		}
		else {
			fsinfo->isDir = FALSE;
			fsinfo->fileAttributes = FILE_ATTRIBUTE_NORMAL;
		}
		file_size.QuadPart = entry.getLength();
		if (file_size.QuadPart == 0) {
			fsinfo->nFileSizeLow = 10 ;
			fsinfo->nFileSizeHigh = 0 ;
		}
		else {
			fsinfo->nFileSizeLow = file_size.LowPart;
			fsinfo->nFileSizeHigh = file_size.HighPart;
		}

		time = entry.getCreationTime();
		fsinfo->creationTime.dwLowDateTime = time->dwLowDateTime;
		fsinfo->creationTime.dwHighDateTime = time->dwHighDateTime;
		time = entry.getLastModifiedTime();
		fsinfo->lastModified.dwLowDateTime = time->dwLowDateTime;
		fsinfo->lastModified.dwHighDateTime = time->dwHighDateTime;

		filename = PathFindFileName(entry.getPath());

		filename_length = lstrlen(filename);
		if (filename[filename_length-1] == '/') {
			filename[filename_length-1] = '\0';
		}
		wcscpy_s(fsinfo->name, MAX_PATH, filename);

		index ++;
	}
	parser->release();
	delete parser;

	if (hRequest) requestDel(hRequest);
	return dirList;
}


BOOL WebdavServer::DAVOpen(wchar_t* path ) {
	WCHAR* path2 = NULL;
	HINTERNET hRequest;
	DWORD status;

	path2 = (WCHAR*)malloc(MAX_PATH*sizeof(WCHAR));
	if (! path_join(path2, prefixe, path)) {
		DbgPrint(L"Unable to merge %s and %s\n", prefixe, path);
		return FALSE;
	}

	hRequest = DAVPROPFind(&path2, DEFAULT_PROPFIND, 0, FALSE);
	if (path2) {
		free(path2);
	}
	if (hRequest) {
		status = getStatus(hRequest);
		if (hRequest) requestDel(hRequest);
		if (status != 207) {
			return FALSE;
		}
		return TRUE;
	}
	return FALSE;
}

HINTERNET WebdavServer::DAVPROPFind(wchar_t** path, wchar_t* body, int depth, BOOL redirected ) {
	BOOL  bResults = FALSE;
	int bodyLength = 0;
	int status;
	HINTERNET  hRequest;
	WCHAR depthProperty[256];
	

	hRequest = requestNew(L"PROPFIND", *path, redirected);
	if (! hRequest)
	{
		return NULL;
	}
	//set Depth
	swprintf_s(depthProperty, 256, L"Depth: %i\r\n", depth);
	bodyLength = lstrlen(body)*sizeof(WCHAR);

	WinHttpAddRequestHeaders(hRequest, depthProperty, (DWORD)-1, WINHTTP_ADDREQ_FLAG_REPLACE|WINHTTP_ADDREQ_FLAG_ADD);
	bResults = WinHttpSendRequest( hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, body, bodyLength, bodyLength, 0);
	if (! bResults) {
		fprintf(stderr, "Failed to send request with error %u\n", GetLastError());
		if (hRequest) WinHttpCloseHandle(hRequest);
	}
	bResults = WinHttpReceiveResponse( hRequest, NULL);
	status = getStatus(hRequest);

	if (status == 301)
	{
		WCHAR* redirect;
		redirect = getRedirectPath(hRequest);
		if (redirect != NULL)
		{
			free(*path);
			*path = redirect;
			DbgPrint(L"redirect to %ls\n", *path);
			return DAVPROPFind(path, body, depth, TRUE);
		}
	}
	
	return hRequest;
}


BOOL WebdavServer::DAVGetFileContent(wchar_t* path, LPDWORD ReadLength, LONGLONG	Offset, DWORD BufferLength, LPVOID Buffer, BOOL redirected ) {
  BOOL  bResults = FALSE;
//  LPSTR pszBuffer;
  DWORD dwStatus = 0;
  HINTERNET  hRequest = NULL;
  WCHAR rangeProperty[256];
  WCHAR path2[MAX_PATH];
  DWORD tempSize = 0;
  DWORD size = 0;
  DWORD dwDownloaded = 0;
//	LPSTR temp = NULL;
//	HINTERNET hRequest2;
  char* buffer = (char*) Buffer;

  if (redirected) {
	  wcscpy_s(path2, MAX_PATH, path);
  }
  else {
		if (! path_join(path2, prefixe, path)) {
			DbgPrint(L"Unable to merge %s and %s\n", prefixe, path);
			return FALSE;
		}
  }
  hRequest = requestNew(L"GET", path2, redirected);
  if (! hRequest)
  {
  	return FALSE;
  }

  //set Range
  swprintf_s(rangeProperty, 256, L"Range: bytes=%i-%i\r\n", (int)Offset, (int)(Offset+BufferLength));
  DbgPrint(L"RANGE : %ls\n", rangeProperty);

  WinHttpAddRequestHeaders(hRequest, rangeProperty, (DWORD)-1, WINHTTP_ADDREQ_FLAG_REPLACE|WINHTTP_ADDREQ_FLAG_ADD);
  bResults = WinHttpSendRequest( hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, WINHTTP_NO_REQUEST_DATA, 0, 0, 0);
  if (! bResults) {
  	fprintf(stderr, "Failed to send request with error %u\n", GetLastError());
	  if (hRequest) WinHttpCloseHandle(hRequest);
  }
  bResults = WinHttpReceiveResponse( hRequest, NULL);
  // Keep checking for data until there is nothing left.
  if (bResults)
  {
  	size = sizeof(DWORD);
  	bResults = WinHttpQueryHeaders(hRequest, WINHTTP_QUERY_STATUS_CODE| WINHTTP_QUERY_FLAG_NUMBER,
  	                               NULL, &dwStatus, &size, NULL );
  	if ((dwStatus <200) || (dwStatus >= 400))
  	{
  		DbgPrint(L"Status %i\n", dwStatus);
  		return FALSE;
  	}
		if ((dwStatus > 300) && (dwStatus < 400))
		{
			WCHAR* redirect;
			redirect = getRedirectPath(hRequest);
			if (redirect)
			{
				*ReadLength = 0;
				bResults = DAVGetFileContent(redirect, ReadLength, Offset, BufferLength, Buffer, TRUE );
			}
		}
		else
		{
			*ReadLength = 0;
			tempSize = 1;
			while (tempSize > 0)
			{
				// Check for available data.
				tempSize = 0;
				if (!WinHttpQueryDataAvailable( hRequest, &tempSize))
				{
					DbgPrint(L"Error %u in WinHttpQueryDataAvailable.\n", GetLastError());
					bResults = FALSE;
					break;
				}
				if (*ReadLength+tempSize > BufferLength)
				{
					tempSize = BufferLength - *ReadLength;
				}
				if (tempSize == 0)
				{
					bResults= TRUE;
					break;
				}
					// Read the Data.
				DbgPrint(L"READ DATA %i %i\n", tempSize, *ReadLength+tempSize );
				if (!WinHttpReadData( hRequest, buffer, tempSize, &dwDownloaded))
				{
					DbgPrint(L"Error %u in WinHttpReadData.\n", GetLastError());
					bResults = FALSE;
					break;
				}
				*ReadLength += dwDownloaded;
				buffer+= dwDownloaded;
			};
		}
		// Keep checking for data until there is nothing left.
		// Report any errors.
		if (!bResults)
		{
			DbgPrint(L"Error %d has occurred.\n", GetLastError());
		}
  }
  // Close any open handles.
  if (hRequest) requestDel(hRequest);
  return bResults;
}


BOOL WebdavServer::DAVImportFileContent(wchar_t* remotePath, wchar_t* localPath, BOOL redirected ) {
	BOOL  bResults = FALSE;
	DWORD dwStatus = 0;
	HINTERNET  hRequest = NULL;
	WCHAR path2[MAX_PATH];
	DWORD tempSize = 0;
	DWORD size = 0;
	DWORD dwDownloaded = 0;
	DWORD NumberOfBytesWritten = 0;
	HANDLE	handle = 0;
	char buffer[DAV_DATA_CHUNCK] = {0};

	if (redirected) {
		wcscpy_s(path2, MAX_PATH, remotePath);
	}
	else {
		if (! path_join(path2, prefixe, remotePath)) {
			DbgPrint(L"Unable to merge %s and %s\n", prefixe, remotePath);
			return FALSE;
		}
	}
	hRequest = requestNew(L"GET", path2, redirected);

	if (! hRequest)
	{
		return FALSE;
	}

	bResults = WinHttpSendRequest( hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, WINHTTP_NO_REQUEST_DATA, 0, 0, 0);
	if (! bResults) {
		DbgPrint(L"Failed to send request with error %u\n", GetLastError());
		if (hRequest)
			WinHttpCloseHandle(hRequest);
	}

	bResults = WinHttpReceiveResponse( hRequest, NULL);
	// Keep checking for data until there is nothing left.
	if (bResults)
	{
		size = sizeof(DWORD);
		bResults = WinHttpQueryHeaders(hRequest, WINHTTP_QUERY_STATUS_CODE| WINHTTP_QUERY_FLAG_NUMBER,
										NULL, &dwStatus, &size, NULL );
		if ((dwStatus <200) || (dwStatus >= 400))
		{
			DbgPrint(L"Status %i\n", dwStatus);
			return FALSE;
		}
		if ((dwStatus > 300) && (dwStatus < 400))
		{
			WCHAR* redirect;
			redirect = getRedirectPath(hRequest);
			DbgPrint(L"status %u \n", dwStatus);
			if (redirect)
			{
				bResults = DAVImportFileContent(redirect, localPath, TRUE );
			}
		}
		else
		{
			tempSize = 1;
			if (!buffer)
			{
				DbgPrint(L"Unable to allocate memory for buffer\n");
				return FALSE;
			}
			handle = CreateFile( localPath,
		                         GENERIC_READ|GENERIC_WRITE|GENERIC_EXECUTE,
		                         0,
		                         NULL, // security attribute
		                         CREATE_ALWAYS,
		                         FILE_ATTRIBUTE_NORMAL,
		                         NULL); // template file handle

			if (handle == INVALID_HANDLE_VALUE) {
				DbgPrint(L"Unable to open the file %ls [error code = %u]\n", localPath, GetLastError());
				return  -1;
			}
			dwDownloaded = 1;
			while (dwDownloaded > 0)
			{
				// Check for available data.
				tempSize = 0;
				if (!WinHttpQueryDataAvailable( hRequest, &tempSize))
				{
					DbgPrint(L"Error %u in WinHttpQueryDataAvailable.\n", GetLastError());
					bResults = FALSE;
					break;
				}
				// Read the Data.
				if (!WinHttpReadData( hRequest, buffer, DAV_DATA_CHUNCK, &dwDownloaded))
				{
					DbgPrint(L"Error %u in WinHttpReadData.\n", GetLastError());
					bResults = FALSE;
					break;
				}
				if (!WriteFile(handle, buffer, dwDownloaded, &NumberOfBytesWritten, NULL))
				{
					DbgPrint(L"Unable to write in the file %ls [error : %u]\n", localPath, GetLastError());
					bResults = FALSE;
					break;
				}
			};
			CloseHandle(handle);
		}
		// Keep checking for data until there is nothing left.
		// Report any errors.
		if (!bResults)
		{
			DbgPrint(L"Error %d has occurred.\n", GetLastError());
		}
	}
	// Close any open handles.
	if (hRequest)
		requestDel(hRequest);

	return bResults;
}



BOOL WebdavServer::DAVWriteFile(wchar_t* path, LPCVOID Buffer, LPDWORD NumberOfBytesWritten, LONGLONG	Offset, DWORD NumberOfBytesToWrite, BOOL redirected ) {
  BOOL  bResults = FALSE;
  BOOL ret = FALSE;
  LPSTR pszBuffer;
  DWORD dwStatus = 0;
  DWORD readLength = 0;
  HINTERNET  hRequest = NULL;
  WCHAR rangeProperty[256];
  WCHAR path2[MAX_PATH];

  if (redirected) {
    wcscpy_s(path2, MAX_PATH, path);
  }
  else {
    if (! path_join(path2, prefixe, path)) {
      DbgPrint(L"Unable to merge %s and %s\n", prefixe, path);
      return FALSE;
    }
  }

  hRequest = requestNew(L"PUT", path2, redirected);
  if (! hRequest) {
  	return FALSE;
  }

  //set Range
  if (NumberOfBytesToWrite != 0){
  	swprintf_s(rangeProperty, 256, L"Range: bytes=%i-%i\r\n", (int)Offset, (int)(Offset+NumberOfBytesToWrite));
  	DbgPrint(L"RANGE : %ls\n", rangeProperty);
  	WinHttpAddRequestHeaders(hRequest, rangeProperty, (DWORD)-1L, WINHTTP_ADDREQ_FLAG_REPLACE|WINHTTP_ADDREQ_FLAG_ADD);
  }

  bResults = WinHttpSendRequest( hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, (LPVOID)Buffer, NumberOfBytesToWrite, NumberOfBytesToWrite, 0);
  if (! bResults)
  {
    DbgPrint(L"Failed to send request with error %u\n", GetLastError());
    if (hRequest) WinHttpCloseHandle(hRequest);
  }
  bResults = WinHttpReceiveResponse( hRequest, NULL);
  // Keep checking for data until there is nothing left.

  if (bResults)
  {
  	pszBuffer = getData(hRequest, &readLength, &dwStatus);
  	if (pszBuffer)
  	{
  		free(pszBuffer);
  		pszBuffer = NULL;
  	}
		if ((dwStatus > 200) && (dwStatus < 300))
		{
			*NumberOfBytesWritten = NumberOfBytesToWrite;
			ret = TRUE;
		}
		if ((dwStatus > 300) && (dwStatus < 400))
		{
			WCHAR* redirect;
			redirect = getRedirectPath(hRequest);
			if (redirect)
			{
				ret = DAVWriteFile(redirect, Buffer, NumberOfBytesWritten, Offset, NumberOfBytesToWrite, TRUE );
			}
		}

  }
	// Keep checking for data until there is nothing left.
	// Report any errors.
	if (!bResults)
		DbgPrint(L"Error %d has occurred.\n", GetLastError());

	// Close any open handles.
	if (hRequest) requestDel(hRequest);
	return ret;
}

BOOL WebdavServer::DAVExportFileContent(wchar_t* remotePath, wchar_t* localPath, BOOL redirected ) {
	BOOL  bResults = FALSE;
	BOOL ret = FALSE;
	DWORD dwStatus = 0;
	// DWORD readLength = 0;
	LARGE_INTEGER fileLength;
	DWORD NumberOfBytesWrite = 0;
	DWORD NumberOfBytesWritten = 0;
	HINTERNET  hRequest = NULL;
//	WCHAR rangeProperty[256];
	WCHAR path2[MAX_PATH];
//	DWORD dwUploaded = 0;
	char buffer[DAV_DATA_CHUNCK] = {0};
	HANDLE handle = 0;

	if (redirected) {
		wcscpy_s(path2, MAX_PATH, remotePath);
	}
	else {
		if (! path_join(path2, prefixe, remotePath)) {
			DbgPrint(L"Unable to merge %s and %s\n", prefixe, remotePath);
			return FALSE;
		}
	}

	hRequest = requestNew(L"PUT", path2, redirected);
	if (! hRequest)
	{
		return FALSE;
	}

	if (!buffer)
	{
		DbgPrint(L"Unable to allocate memory for buffer\n");
		return FALSE;
	}

	handle = CreateFile( localPath,
                         GENERIC_READ|GENERIC_WRITE|GENERIC_EXECUTE,
                         0,
                         NULL, // security attribute
                         OPEN_EXISTING,
                         FILE_ATTRIBUTE_NORMAL,
                         NULL);
	
	if (handle == INVALID_HANDLE_VALUE) {
		DbgPrint(L"Unable to open the file %ls [error code = %u]\n", localPath, GetLastError());
		return  FALSE;
	}
	bResults = GetFileSizeEx(handle, &fileLength);
	if (! bResults) {
		DbgPrint(L"Failed to get size of the file %s\n", localPath);
		if (hRequest) WinHttpCloseHandle(hRequest);
		return FALSE;
	}
	
	bResults = WinHttpSendRequest( hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, WINHTTP_NO_REQUEST_DATA, 0, (DWORD)fileLength.QuadPart, 0);
	if (! bResults) {
		DbgPrint(L"Failed to send request with error %u\n", GetLastError());
		if (hRequest) WinHttpCloseHandle(hRequest);
		return FALSE;
	}

	do {
		if (!ReadFile(handle, buffer, DAV_DATA_CHUNCK, &NumberOfBytesWrite, NULL))
		{
			DbgPrint(L"Unable to read in the file %ls [error : %u]\n", localPath, GetLastError());
			bResults = FALSE;
			break;
		}
		if (NumberOfBytesWrite == 0)
			break;

		bResults = WinHttpWriteData( hRequest, (LPVOID)buffer, NumberOfBytesWrite, &NumberOfBytesWritten);

		if (! bResults)
		{
			DbgPrint(L"Failed to send request with error %u\n", GetLastError());
			if (hRequest) WinHttpCloseHandle(hRequest);
		}

	} while (NumberOfBytesWrite > 0);
	bResults = WinHttpReceiveResponse( hRequest, NULL);
	// Keep checking for data until there is nothing left.
	if (bResults) {
		dwStatus = getStatus(hRequest);
		if ((dwStatus > 200) && (dwStatus < 300)) {
			ret = TRUE;
		}
		if ((dwStatus > 300) && (dwStatus < 400))
		{
			WCHAR* redirect;
			redirect = getRedirectPath(hRequest);
			if (redirect)
			{
				CloseHandle(handle);
				ret = DAVExportFileContent(remotePath, localPath, TRUE );
			}
		}
	}

	CloseHandle(handle);
	// Keep checking for data until there is nothing left.
	// Report any errors.
	if (!bResults)
		DbgPrint(L"Error %d has occurred.\n", GetLastError());

	// Close any open handles.
	if (hRequest) requestDel(hRequest);

	return ret;
}


BOOL WebdavServer::DAVMKCOL(wchar_t* path, BOOL redirected ) {
  BOOL  bResults = FALSE;
  LPSTR pszBuffer;
  DWORD dwStatus = 0;
  DWORD dwSize = 0;
  HINTERNET  hRequest = NULL;
  WCHAR path2[MAX_PATH];
  BOOL ret = FALSE;

	if (redirected) {
		wcscpy_s(path2, MAX_PATH, path);
	}
	else {
		if (! path_join(path2, prefixe, path)) {
			DbgPrint(L"Unable to merge %s and %s\n", prefixe, path);
			return FALSE;
		}
	}

  hRequest = requestNew(L"MKCOL", path2, redirected);
  if (! hRequest)
  {
  	return FALSE;
  }

  //set Range
  bResults = WinHttpSendRequest( hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, WINHTTP_NO_REQUEST_DATA, 0, 0, 0);
  if (! bResults) {
  	fprintf(stderr, "Failed to send request with error %u\n", GetLastError());
	  if (hRequest) WinHttpCloseHandle(hRequest);
	  ret = FALSE;
  }
  bResults = WinHttpReceiveResponse( hRequest, NULL);
  // Keep checking for data until there is nothing left.
  if (bResults)
  {
		pszBuffer = getData(hRequest, &dwSize, &dwStatus);
		if (pszBuffer)
		{
			free(pszBuffer);
			pszBuffer = NULL;
		}
		if (dwStatus == 201)
		{
			ret = TRUE;
		}
		if ((dwStatus > 300) && (dwStatus < 400))
		{
			WCHAR* redirect;
			redirect = getRedirectPath(hRequest);
			if (redirect)
			{
				ret = DAVMKCOL(redirect ,TRUE);
			}
		}
  }
	// Keep checking for data until there is nothing left.
	// Report any errors.
	if (!bResults)
		DbgPrint(L"Error %d has occurred.\n", GetLastError());

	// Close any open handles.
	if (hRequest) requestDel(hRequest);
	return ret;
}


BOOL WebdavServer::DAVDELETE(wchar_t* path, BOOL redirected ) {
  BOOL  bResults = FALSE;
  LPSTR pszBuffer;
  DWORD dwStatus = 0;
  DWORD dwSize = 0;
  HINTERNET  hRequest = NULL;
  WCHAR path2[MAX_PATH];
  BOOL ret = FALSE;

	if (redirected) {
		wcscpy_s(path2, MAX_PATH, path);
	}
	else {
		if (! path_join(path2, prefixe, path)) {
			DbgPrint(L"Unable to merge %s and %s\n", prefixe, path);
			return FALSE;
		}
	}
  hRequest = requestNew(L"DELETE", path2, redirected);
  if (! hRequest)
  {
  	return FALSE;
  }

  //set Range
  bResults = WinHttpSendRequest( hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, WINHTTP_NO_REQUEST_DATA, 0, 0, 0);
  if (! bResults) {
	  DbgPrint(L"Failed to send request with error %u\n", GetLastError());
	  if (hRequest) WinHttpCloseHandle(hRequest);
	  ret = FALSE;
  }
  bResults = WinHttpReceiveResponse( hRequest, NULL);
  // Keep checking for data until there is nothing left.
  if (bResults)
  {
		pszBuffer = getData(hRequest, &dwSize, &dwStatus);
		if (pszBuffer)
		{
			free(pszBuffer);
			pszBuffer = NULL;
		}
		if (dwStatus == 204)
		{
			ret = TRUE;
		}
		if ((dwStatus > 300) && (dwStatus < 400))
		{
			WCHAR* redirect;
			redirect = getRedirectPath(hRequest);
			if (redirect)
			{
				ret = DAVDELETE(redirect, TRUE );
			}
		}
  }
	// Keep checking for data until there is nothing left.
	// Report any errors.
	if (!bResults)
		DbgPrint(L"Error %d has occurred.\n", GetLastError());

	// Close any open handles.
	if (hRequest) requestDel(hRequest);
	return ret;
}


BOOL WebdavServer::DAVMOVE(wchar_t* from, wchar_t* to, BOOL redirected, BOOL replaceIfExisting) {
  BOOL  bResults = FALSE;
  DWORD dwStatus = 0;
  int len = 0;
  HINTERNET  hRequest = NULL;
  WCHAR from_path[MAX_PATH];
  WCHAR to_path[MAX_PATH];
  WCHAR destination_property[MAX_PATH+15];
  WCHAR destination[MAX_PATH];
  
  BOOL ret = FALSE;

	if (redirected) {
		wcscpy_s(from_path, MAX_PATH, from);
		wcscpy_s(to_path, MAX_PATH, to);
	}
	else {
		if (! path_join(from_path, prefixe, from)) {
			DbgPrint(L"Unable to merge %s and %s\n", prefixe, from);
			return FALSE;
		}
		if (! path_join(to_path, prefixe, to)) {
			DbgPrint(L"Unable to merge %s and %s\n", prefixe, to);
			return FALSE;
		}

	}

	hRequest = requestNew(L"MOVE", from_path, redirected);
	if (! hRequest)
		return FALSE;

	//set destination
	getAbsolutePath(destination, to_path);
	len = lstrlen(to_path);
	if (to_path[len-1] == L'/')
		wcscat_s(destination, sizeof(from_path), L"/");

	swprintf_s(destination_property, MAX_PATH, L"Destination: %s", destination);
  
	WinHttpAddRequestHeaders(hRequest, destination_property, (DWORD)-1, WINHTTP_ADDREQ_FLAG_REPLACE|WINHTTP_ADDREQ_FLAG_ADD);
	if (replaceIfExisting)
		WinHttpAddRequestHeaders(hRequest, L"Overwrite:T", (DWORD)-1, WINHTTP_ADDREQ_FLAG_REPLACE|WINHTTP_ADDREQ_FLAG_ADD);


	bResults = WinHttpSendRequest( hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, WINHTTP_NO_REQUEST_DATA, 0, 0, 0);
	if (! bResults) {
		DbgPrint(L"Failed to send request with error %u\n", GetLastError());
		if (hRequest) WinHttpCloseHandle(hRequest);
		ret = FALSE;
	}
	bResults = WinHttpReceiveResponse( hRequest, NULL);
	// Keep checking for data until there is nothing left.
	if (bResults)
	{
		dwStatus = getStatus(hRequest);
		if (dwStatus == 201)
			ret = TRUE;
		if ((dwStatus > 300) && (dwStatus < 400)) {
			WCHAR* redirect;
			redirect = getRedirectPath(hRequest);
			if (redirect)
			{
				ret = DAVMOVE(redirect, to_path, TRUE, replaceIfExisting);
			}
		}
		else 
			ret = FALSE;
		
	}
	// Keep checking for data until there is nothing left.
	// Report any errors.
	if (!bResults)
		DbgPrint(L"Error %d has occurred.\n", GetLastError());

	// Close any open handles.
	if (hRequest) requestDel(hRequest);
	return ret;
}
