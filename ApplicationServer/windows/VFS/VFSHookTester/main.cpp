////#include <stdio.h>
////#include <cstdio>
////#include <ctime>
////#include <windows.h>
////
////
////#include <shlobj.h>
////
////
////#define DLL_PATH				"VFSHook.dll"
////
////#define VFS_DLL_LOG_PATH		"D:\\HookTest\\"
////#define TEST_DESKTOP_FILE_PATH	"C:\\Users\\Wei\\Desktop\\DesktopPathOutput.txt"
////#define TEST_DOC_FILE_PATH		"C:\\Users\\Wei\\Documents\\DocPathOutput.txt"
////
////#define	TEST_REG_MAIN_KEY		HKEY_LOCAL_MACHINE
////#define	TEST_REG_SUB_KEY		TEXT("Software")
////
////void WriteFileTest(LPCSTR path, char *fmt,...)
////{
////	va_list args;
////	char temp[5000];
////	HANDLE hFile;
////
////	if((hFile = CreateFileA(path, GENERIC_WRITE, 0, NULL, OPEN_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL)) <0)
////	{
////		return;
////	}
////	
////	_llseek((HFILE)hFile, 0, SEEK_END);
////		
////	DWORD dw;
////		
////	va_start(args,fmt);
////	vsprintf_s(temp, fmt, args);
////	va_end(args);
////	WriteFile(hFile, temp, strlen(temp), &dw, NULL);
////
////	wsprintfA(temp, "\r\n");
////	WriteFile(hFile, temp, strlen(temp), &dw, NULL);
////
////	_lclose((HFILE)hFile);
////}
////
////void RegOpenKeyTest()
////{
////	HKEY	hKey;
////	HKEY	hMainKey = TEST_REG_MAIN_KEY;
////	LPCSTR	lpSubKey = TEST_REG_SUB_KEY;
////
////	LONG lErrorCode = RegOpenKey(hMainKey, lpSubKey, &hKey);;
////	if (lErrorCode != ERROR_SUCCESS)
////	{
////		printf("RegOpenKeyEx failed.");
////	}
////
////	lErrorCode = RegCloseKey(hKey);
////	if (lErrorCode != ERROR_SUCCESS)
////	{
////		printf("RegCloseKey failed.");
////	}		
////}
////void RegOpenKeyExTest()
////{
////	HKEY	hKey;
////	HKEY	hMainKey = TEST_REG_MAIN_KEY;
////	LPCSTR	lpSubKey = TEST_REG_SUB_KEY;
////	
////	LONG lErrorCode = RegOpenKeyEx(hMainKey, lpSubKey, 0, KEY_NOTIFY | KEY_READ, &hKey);
////	if (lErrorCode != ERROR_SUCCESS)
////	{
////		printf("RegOpenKeyEx failed.");
////	}
////
////	lErrorCode = RegCloseKey(hKey);
////	if (lErrorCode != ERROR_SUCCESS)
////	{
////		printf("RegCloseKey failed.");
////	}
////}
////
////
////#include <Winternl.h>	//Nt 
////
////#include <stdlib.h>
////#include <stdio.h>
////#include <windows.h>
////#include <ctime>
////#include <shlobj.h> 
////#include <shlwapi.h> 
////#include <string>
////
////
////WCHAR g_szOriginDesktop[MAX_PATH] = {0};
////void obtainUserSHFolders()
////{
////	// target path : Desktop & Document
////	SHGetSpecialFolderPathW(NULL, g_szOriginDesktop, CSIDL_DESKTOP, 0);
////}
////
////bool filter(LPCWSTR lpFilePath, 
////			std::wstring szTargetPath, 
////			std::wstring szRedirectPath, 
////			std::wstring* pszOutputPath)
////{
////	bool bRedirected = false;
////	std::wstring szResult(lpFilePath);//reusult == input path by default
////
////	std::wstring szFileTargetRoot = std::wstring(lpFilePath).substr(0, szTargetPath.length());
////
////	//is in target folder
////	if( wcscmp(szFileTargetRoot.c_str(), szTargetPath.c_str()) == 0 )
////	{
////		bRedirected = true;
////		//is children folder/file of the target folder
////		if(std::wstring(lpFilePath).length() > szFileTargetRoot.length())
////		{
////			std::wstring szRemainPath = std::wstring(lpFilePath).substr(szTargetPath.length() + 1);
////			szResult = szRedirectPath + L"\\" + szRemainPath;
////		}
////		//is target folder
////		else 
////		{
////			szResult = szRedirectPath;
////		}
////	}
////
////	*pszOutputPath = szResult;
////	return bRedirected;
////}
////
////BOOL GetPath(POBJECT_ATTRIBUTES ObjectAttributes, WCHAR* strPath)
////{
////	if (!ObjectAttributes->RootDirectory && !ObjectAttributes->ObjectName)
////	{
////		return FALSE;
////	}
////
////	if (NULL != ObjectAttributes && NULL != ObjectAttributes->ObjectName && ObjectAttributes->ObjectName->Length > 0)
////	{
////		lstrcatW(strPath, ObjectAttributes->ObjectName->Buffer);
////	}
////	return TRUE;
////}
////
////void redirectUserPath(WCHAR* strTargetPath, PUNICODE_STRING* punistrPathOutput)
////{	
////	std::wstring out;
////	WCHAR szNewP[MAX_PATH] = {0};
////	lstrcatW(szNewP, L"\\??\\");
////	lstrcatW(szNewP, g_szOriginDesktop);
////	//lstrcatW(szNewP, L"\\??\\C:\\Users\\Administrator");
////	if ( filter(strTargetPath, szNewP, std::wstring(L"U:") + L"\\" + L"Desktop", &out) )
////	//if ( filter(strTargetPath, szNewP, std::wstring(REDIRECT_PATH), &out) )
////	{			
////		WCHAR temp[MAX_PATH]  = {0};
////		lstrcatW(temp, L"\\??\\");
////		lstrcatW(temp, out.c_str());
////
////		printf("NEW filename=%S\n", temp);
////
////		//TODO: manage the memory, maybe kept the original value instead of overwriting.
////		(*punistrPathOutput)->Buffer = temp;   
////		(*punistrPathOutput)->Length = (4 + out.length()) * 2;  // 4 for \\??\\, * 2 for wchar 
////		(*punistrPathOutput)->MaximumLength = (4 + out.length()) * 2 + 2; // + 2 for "/0"
////	}	
////}
////
////void myZwOpenFile(POBJECT_ATTRIBUTES ObjectAttributes)
////{
////	printf("start ZwOpenFile: filename=%wZ", ObjectAttributes->ObjectName);
////	printf("\n");
////
////	UNICODE_STRING uni = *(ObjectAttributes->ObjectName);
////
////	WCHAR szFilePath[MAX_PATH] = {0};
////	if(GetPath(ObjectAttributes, szFilePath))
////	{
////		redirectUserPath(szFilePath, &ObjectAttributes->ObjectName);
////	}	
////	
////	ObjectAttributes->ObjectName->Buffer = uni.Buffer; 
////	ObjectAttributes->ObjectName->Length = uni.Length; 
////	ObjectAttributes->ObjectName->MaximumLength = uni.MaximumLength; 
////	printf("end ZwOpenFile: filename=%wZ", ObjectAttributes->ObjectName);
////	printf("\n");
////}
////
////void resetString(PUNICODE_STRING* punistrPathOutput)
////{
////	std::wstring t = L"\\LOCAL_VARIABLE";
////	WCHAR temp[MAX_PATH]  = {0};
////	std::wstring out = L"Modified\\Desktop";
////	lstrcatW(temp, out.c_str());
////	lstrcatW(temp, t.c_str());
////	
////	(*punistrPathOutput)->Buffer = temp;   
////	(*punistrPathOutput)->Length = (4 + out.length()) * 2;  // 4 for \\??\\, * 2 for wchar 
////	(*punistrPathOutput)->MaximumLength = (4 + out.length()) * 2 + 2; // + 2 for "/0"
////
////	printf("IN filename=%wZ, Length = %d \n", (*punistrPathOutput), (*punistrPathOutput)->Length);	
////}
////
////// CSIDL_FLAG_MASK is mask for all possible CSIDL flag values
////WCHAR g_szOriginCSIDLFolderFullPath[CSIDL_FLAG_MASK][MAX_PATH];
////WCHAR g_szOriginCSIDLFolderName[CSIDL_FLAG_MASK][MAX_PATH];
////
////#include <vector>
//////CSIDL_FLAG_ID, full path wsting
//////std::map<int, WCHAR[MAX_PATH]>	g_vOriginCSIDLFolderFullPath; 
//////CSIDL_FLAG_ID
////std::vector<int>				g_vRedirectCSIDLFFlag;
////
////#include <String>
////
////#define REDIRECT_PATH	L"D:"
//////#define REDIRECT_PATH	L"U:"
////#define	SEPERATOR		L"\\"
////#define DEVICE_PREFIX	L"\\??\\"
////#define SAME_DIR(d0, d1)	wcscmp(d0, d1) == 0
////
////std::wstring g_szUserProfilePath;
////
////bool substituteUserProfilePath(std::wstring szPath, std::wstring* pszSubstitutedPath)
////{
////	//If path is child of UserProfile, always contains a SEPERATOR after user profile path
////	//szPath is "C:/Users/USER/PATH"
////	//szPathParent, g_szUserProfilePath are path "C:/Users/USER/" containing the SEPERATOR at end
////	//szPathRemain is the remain path "PATH" without SEPERATOR at begin
////	if(szPath.length() >= g_szUserProfilePath.length())
////	{
////		std::wstring szPathParent = szPath.substr(0, g_szUserProfilePath.length());
////		if(SAME_DIR(szPathParent.c_str(), g_szUserProfilePath.c_str()))
////		{
////			std::wstring szPathRemain = szPath.substr(g_szUserProfilePath.length());
////			*pszSubstitutedPath = std::wstring(REDIRECT_PATH) + SEPERATOR + szPathRemain;
////			//pszSubstitutedPath->append(SEPERATOR);
////			//pszSubstitutedPath->append(szPathRemain);
////			return true;
////		}
////	}
////	return false;
////}
////
////bool filter1(std::wstring szPath, 
////					std::wstring szTargetPath, 
////					std::wstring szRedirectPath, 
////					bool bSubstituteSelf,
////					std::wstring* pszSubstitutedPath)
////{
////	bool bRet = false;
////	std::wstring szPathParent = szPath.substr(0, szTargetPath.length());
////
////	//Example
////	//szPath: "/??/C:/Users/USER/PATH"
////	//szTargetPath: "/??/C:/Users/USER"
////	//szPathParent, g_szUserProfilePath: "/??/C:/Users/USER/" containing the SEPERATOR at end
////	//szPathRemain: the remain path "PATH" without SEPERATOR at begin
////
////	//Path contains target folder
////	if( SAME_DIR(szPathParent.c_str(), szTargetPath.c_str()))
////	{
////		//Path is children of the target folder
////		if(szPath.length() > szPathParent.length())
////		{
////			std::wstring szRemainPath = szPath.substr(szTargetPath.length());
////			
////			//Check if szRemainPath is in black list
////			//if(isPathInList(szRemainPath, ))
////			{
////				;
////			}
////			//else
////			{
////				*pszSubstitutedPath = szRedirectPath + SEPERATOR + szRemainPath;
////				bRet = true;
////			}
////		}
////		//Path is target path
////		else 
////		{
////			if(bSubstituteSelf)
////			{
////				*pszSubstitutedPath = szRedirectPath;
////				bRet = true;
////			}
////		}
////	}
////
////	return bRet;
////}
//////blacklist of folders should not be redirected ("Desktop/Temp" for example, non full path name)
////std::vector<std::wstring>		g_vBlacklistFolder;
//////blacklist of full name path 
////std::vector<std::wstring>		g_vBlacklistFullPath;
////
////
////bool hasFolder(std::wstring szFolder, std::vector<std::wstring> vList)
////{
////	for(int i=0; i<vList.size(); ++i)
////	{
////		std::wstring blackItm = vList[i];
////		int len;
////		if(blackItm[blackItm.length()-1] == L'*')
////		{
////			len = blackItm.length() - 1;
////			std::wstring cmp = szFolder.substr(0, len);
////			blackItm = blackItm.substr(0, len);
////			printf("%S", blackItm.c_str());
////		}
////		else
////		{
////			len = blackItm.length();
////		}
////		std::wstring cmp = szFolder.substr(0, len);
////
////		if(SAME_DIR(cmp.c_str(), blackItm.c_str()))
////		{
////			return true;
////		}
////	}
////	return false;
////}
////
////int main()
////{		
////	//Obtain USERPROFILE path
////	WCHAR szUserProfilePatha[MAX_PATH];
////	SHGetSpecialFolderPathW(NULL, szUserProfilePatha, CSIDL_PROFILE, 0);
////	
////	g_vBlacklistFolder.push_back(std::wstring(L"K*"));
////	hasFolder(L"Kli", g_vBlacklistFolder);
////	hasFolder(L"Kli", g_vBlacklistFolder);
////	hasFolder(L"Kli", g_vBlacklistFolder);
////	
////	/*g_vBlacklistFolder.push_back(std::wstring(szUserProfilePatha));
////
////	{
////		WCHAR temp[MAX_PATH];
////		SHGetSpecialFolderPathW(NULL, temp, CSIDL_APPDATA, 0);
////		std::wstring path = std::wstring(temp).substr(std::wstring(szUserProfilePatha).length()+1);
////		g_vBlacklistFolder.push_back(path);
////	}
////	{
////		WCHAR temp[MAX_PATH];
////		SHGetSpecialFolderPathW(NULL, temp, CSIDL_LOCAL_APPDATA, 0);
////		std::wstring path = std::wstring(temp).substr(std::wstring(szUserProfilePatha).length()+1);
////		g_vBlacklistFolder.push_back(path);
////	}
////	for(int i =0; i<g_vBlacklistFolder.size(); ++i)
////	{
////		printf("%S\n", g_vBlacklistFolder[i].c_str());
////	}*/
////	system("pause");
////	return 0;
////
////	WCHAR szUserProfilePath[MAX_PATH];
////	SHGetSpecialFolderPathW(NULL, szUserProfilePath, CSIDL_PROFILE, 0);
////	// make "//??//USERPROFILE//"
////	//g_szUserProfilePath.append(DEVICE_PREFIX);
////	g_szUserProfilePath.append(szUserProfilePath);
////	g_szUserProfilePath.append(SEPERATOR);
////
////	std::wstring szPath = L"C:\\Users\\Wei\\Tracing\\Data";
////
////	std::wstring szPathParent = szPath.substr(0, 1000);
////	printf("output : %S\n", szPathParent);
////
////
////	//TODO: case szPath last character is "\\"
////	/*if(szPath.length() >= szUserprofile.length())
////	{
////		std::wstring szPathParent = szPath.substr(0, szUserprofile.length());
////		std::wstring szPathRemain = szPath.substr(szUserprofile.length(), szPath.length());
////		if(SAME_DIR(szPathParent.c_str(), szUserprofile.c_str()))
////		{
////			printf("szPathParent : %S\n", szPathParent);
////			printf("szPathRemain : %S\n", szPathRemain);
////		}
////	}*/
////
////	std::wstring output;
////	substituteUserProfilePath(szPath, &output);
////	printf("szPath : %s\n", szPath);
////	printf("output : %s\n", output);
////	
////	std::wstring szResult;
////	filter1( L"C:\\Users\\Wei\\Tracing\\Data", 
////		g_szUserProfilePath, 
////		std::wstring(REDIRECT_PATH),
////		false,
////		&szResult );
////
////	printf("szResult : %s\n", szResult);
////
////	system("pause");
////	return 0;
////
/////*	LPCWSTR	lpNewFileName = L"Hello";
////	ULONG   FileNameLength = wcslen (lpNewFileName);
////	FileNameLength *= 2;
////	FileNameLength += 2;
////
////	WCHAR   FileName[1];
////
////	memcpy (FileName,
////	        lpNewFileName,
////	        min(FileNameLength, MAX_PATH));
////	
////	std::wstring ws(FileName, 2);
////	
////	printf("%d \n", FileNameLength);
////	printf("'%S' \n", lpNewFileName);
////	printf("'%S' \n", FileName);
////	printf("'%S' \n", ws);
////	system("pause");
////	return 0;
////
////
////	/*WCHAR* tmp = L"Hello World";
////	PVOID lpOutBuffer;
////	wchar_t** t =  (wchar_t**)lpOutBuffer;
////	std::wstring str = std::wstring(*t);
////	printf("%s", str.c_str());
////	system("pause");
////	return 0;*/
////
////	/*
////	char *s = "Hello World";
////	int len = strlen(s);
////	std::wstring str;
////	WCHAR* tmp = new WCHAR[len+1];
////	mbstowcs(tmp, s, len+1);
////	str = tmp;
////	delete[] tmp;
////
////	printf("%s", str.c_str());
////	system("pause");
////	return 0;
////
////	//LPVOID p; String s=(char * &)p;
////
////
////	g_vRedirectCSIDLFFlag.push_back(CSIDL_DESKTOP);
////	g_vRedirectCSIDLFFlag.push_back(CSIDL_PERSONAL);// == CSIDL_MYDOCUMENTS
////
////	for(int i=0; i<g_vRedirectCSIDLFFlag.size(); ++i)
////	{
////		int CSIDL_flag = g_vRedirectCSIDLFFlag[i];
////		SHGetSpecialFolderPathW(NULL, g_szOriginCSIDLFolderFullPath[CSIDL_flag], CSIDL_flag, 0);
////		SHGetSpecialFolderPathW(NULL, g_szOriginCSIDLFolderName[CSIDL_flag], CSIDL_flag, 0);
////		PathStripPathW(g_szOriginCSIDLFolderName[CSIDL_flag]);
////		printf("CSIDL Full: %S \n", g_szOriginCSIDLFolderFullPath[CSIDL_flag]);
////		printf("CSIDL Name: %S \n", g_szOriginCSIDLFolderName[CSIDL_flag]);
////	}
////
////	
////	//WCHAR g_szOriginDesktop[MAX_PATH] = {0};
////	//SHGetSpecialFolderPathW(NULL, g_szOriginDesktop, CSIDL_COMMON_DOCUMENTS, 0);
////	//printf("CSIDL Full: %S \n", g_szOriginDesktop);
////	//PathStripPathW(g_szOriginDesktop);
////	//printf("CSIDL Name: %s \n", g_szOriginDesktop);
////
////	system("pause");
////	return 0;
////
////
////	obtainUserSHFolders();
////
////	UNICODE_STRING unistr; 
////	WCHAR temp[MAX_PATH]  = {0};
////	lstrcatW(temp, L"\\??\\");
////	lstrcatW(temp, g_szOriginDesktop);
////	std::wstring c(temp);
////	unistr.Buffer = temp;   
////	unistr.Length = (c.length()) * 2;  // 4 for \\??\\, * 2 for wchar 
////	unistr.MaximumLength = (c.length()) * 2 + 2; // + 2 for "/0"
////
////	OBJECT_ATTRIBUTES obj;
////	obj.ObjectName = &unistr;
////
////	printf("1 out filename=''%wZ'', Length = ''%d'' \n", obj.ObjectName, obj.ObjectName->Length);	
////	
////	myZwOpenFile(&obj);
////
////	printf("2 out filename=''%wZ'', Length = ''%d'' \n", obj.ObjectName, obj.ObjectName->Length);
////
////	myZwOpenFile(&obj);
////
////	printf("3 out filename=''%wZ'', Length = ''%d'' \n", obj.ObjectName, obj.ObjectName->Length);	
////	/*
////	UNICODE_STRING unistr; 
////	PUNICODE_STRING punistr = &unistr;
////	
////	WCHAR temp[MAX_PATH]  = {0};
////	std::wstring out = L"Original\\Desktop";
////	lstrcatW(temp, out.c_str());
////	punistr->Buffer = temp;   
////	punistr->Length = (4 + out.length()) * 2;  // 4 for \\??\\, * 2 for wchar 
////	punistr->MaximumLength = (4 + out.length()) * 2 + 2; // + 2 for "/0"
////
////	printf("filename=%wZ, Length = %d \n", punistr, punistr->Length);	
////	resetString(&punistr);	
////	printf("filename=%wZ, Length = %d \n", punistr, punistr->Length);
////	
////	system("pause");
////	return 0;*/
////
////	//NOTE: strange, for some dlls you have to load it so you can hook it. 
////	//		(Maybe previlieges are needed to hook it without loading.)
////	//		The dlls:	Shlwapi, SHELL32
////	LoadLibrary("Shlwapi.dll");	
////	LoadLibrary("SHELL32.dll");	
////	
////	
////	HINSTANCE hDLL;
////	hDLL = LoadLibrary(DLL_PATH);
//// 
////	// Check to see if the library was loaded successfully 
////	if (hDLL != 0)
////		printf("DLL loaded!\n");
////	else
////		printf("DLL failed to load!\n");																											
////	
////	if(!hDLL)
////	{
////		system("pause");
////		return 0;
////	}
////	
////	printf("========= Test Start =========\n");
////	printf("\n");
////	
////	MoveFileExW(L"D:\\HookTest\\test.txt", L"D:\\HookTest\\testRe.txt", MOVEFILE_COPY_ALLOWED);
////	
////	FreeLibrary(hDLL);
////	system("pause");
////    return 0;
////
////	CreateFileA("C:\\Users\\Wei\\Desktop", GENERIC_WRITE, 0, NULL, OPEN_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL);
////
////	printf("WriteFileTest, file : %s \n", TEST_DESKTOP_FILE_PATH);
////	WriteFileTest(TEST_DESKTOP_FILE_PATH, "Origin name of file wrote to : %s", TEST_DESKTOP_FILE_PATH);
////	printf("WriteFileTest, file : %s \n", TEST_DOC_FILE_PATH);
////	WriteFileTest(TEST_DOC_FILE_PATH, "Origin name of file wrote to : %s", TEST_DOC_FILE_PATH);
////	//TODO:
////	//printf("read file test: read file from: ... \n");
////	
////	
////	printf("RegOpenKeyTest, key : %s \n", TEST_REG_SUB_KEY);
////	RegOpenKeyTest();	
////	printf("RegOpenKeyExTest, key : %s \n", TEST_REG_SUB_KEY);
////	RegOpenKeyExTest();
////	
////	printf("\n");
////	printf("========= Test End =========\n");
////
////	printf("========= Check the log : %s =========\n", VFS_DLL_LOG_PATH);
////
////	FreeLibrary(hDLL);
////	system("pause");
////    return 0;
////}
//
//
//#include <stdio.h>
//#include <cstdio>
//#include <ctime>
//#include <windows.h>
//#include <shlobj.h>
//#include <Psapi.h>
//#include <stdlib.h>
//#include <shlwapi.h> 
//
//bool getExplorerProcessHandle(HANDLE* hProcExplorer)
//{
//	DWORD aProcesses[1024]; 
//	DWORD cbNeeded; 
//	DWORD cProcesses;
//
//	// Get the list of process identifiers.
//	if ( EnumProcesses( aProcesses, sizeof(aProcesses), &cbNeeded ) )
//	{
//		// Calculate how many process identifiers were returned.
//		cProcesses = cbNeeded / sizeof(DWORD);
//
//		// Get process Explorer
//		for (int i = 0; i < cProcesses; i++ )
//		{
//			DWORD processID = aProcesses[i];
//			HANDLE hProcess;
//			hProcess = OpenProcess( PROCESS_QUERY_INFORMATION |
//								PROCESS_VM_READ,
//								FALSE, processID );
//			if (hProcess)
//			{
//				WCHAR szProcName[MAX_PATH];
//				if (GetModuleFileNameExW(hProcess, 0, szProcName, MAX_PATH))
//				{
//					//strip and check "Explorer.EXE" ran
//					PathStripPathW(szProcName);
//					if(wcscmp(szProcName, L"Explorer.EXE") == 0)
//					{
//						printf("Find Explorer process! Id = %d", hProcess);
//						*hProcExplorer = hProcess;
//						return true;
//					}
//				}
//			}
//		}
//	}
//
//	return false;
//}
//
//bool isHookDllLoaded(HANDLE hProcess)
//{
//	HMODULE hMods[1024];
//    DWORD cbNeeded;
//
//	if( EnumProcessModules(hProcess, hMods, sizeof(hMods), &cbNeeded))
//    {
//        for ( unsigned int i = 0; i < (cbNeeded / sizeof(HMODULE)); i++ )
//        {
//            WCHAR szModName[MAX_PATH];
//
//            // Get the full path to the module's file.
//            if ( GetModuleFileNameExW( hProcess, hMods[i], szModName,
//                                      sizeof(szModName) / sizeof(TCHAR)))
//            {
//				//strip and check "VFSHook.dll" loaded
//				PathStripPathW(szModName);
//				if(wcscmp(szModName, L"VFSHook.dll") == 0)
//				{
//					return true;
//				}
//
//				printf( "%s ", szModName);
//            }
//        }
//    }
//	
//	return true;
//	//return false;
//}
//
////Refresh desktop on Explorer hooked
//void waitDesktopRefresh()
//{
//	bool conti = true;
//	
//	HANDLE hProcExplorer = NULL;
//
//	while(conti)
//	{
//		if( !hProcExplorer )
//		{
//			if(getExplorerProcessHandle(&hProcExplorer))
//			{
//				printf("Get Explorer process! Id = %d", hProcExplorer);
//			}
//		}
//		else
//		{
//			if( isHookDllLoaded(hProcExplorer) )
//			{
//				conti = false;
//			}
//		}
//	}
//
//	//Refresh Desktop
//	//SHChangeNotify(0x8000000, 0x1000, NULL, NULL);
//}
//
//#include <iostream>
//#include <fstream>
//#include <vector>
//
////0 represents for CSIDL blacklist
//#define IS_CSIDL_BLACK_LIST(x)	x == 0 ? true:false
//
//std::wstring getCSIDLFolderName(int csidl)
//{
//	WCHAR szUserProfilePath[MAX_PATH];
//	SHGetSpecialFolderPathW(NULL, szUserProfilePath, CSIDL_PROFILE, 0);
//
//	WCHAR temp[MAX_PATH];
//	SHGetSpecialFolderPathW(NULL, temp, csidl, 0);
//	return std::wstring(temp).substr(std::wstring(szUserProfilePath).length() + 1);
//}
//
//#define BLACKLIST_CONF_FILE		L"blacklist.conf"
//void setupBlackList(std::vector<std::wstring>* pvBlackList)
//{	
//	//Default blacklist
//	pvBlackList->push_back(std::wstring(L"ntuser*"));
//	pvBlackList->push_back(std::wstring(L"NTUSER*"));
//
//	//Read conf file from CSIDL_COMMON_APPDATA\\ulteo\ovd
//	WCHAR filename[MAX_PATH];
//	lstrcatW(filename, L"D:\\HookTest\\");
//	lstrcatW(filename, BLACKLIST_CONF_FILE);
//		
//	//Check if conf file not exist
//	WIN32_FIND_DATAW FindFileData;
//	if (FindFirstFileW(filename, &FindFileData) == INVALID_HANDLE_VALUE) 
//	{
//		printf("Blacklist configuration file not found. Setting CSIDL folders into blacklist by default.");
//
//		//If conf file not exist, put every folder to black list except CSIDL_DESKTOP and CSIDL_PERSONAL
//		//pvBlackList->push_back( getCSIDLFolderName(CSIDL_DESKTOP) );
//		//pvBlackList->push_back( getCSIDLFolderName(CSIDL_PERSONAL) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_FAVORITES) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYMUSIC) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYVIDEO) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYPICTURES) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_SENDTO) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_STARTMENU) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_NETHOOD) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_APPDATA) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_PRINTHOOD) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_LOCAL_APPDATA) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_TEMPLATES) );
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_COOKIES) );
//		pvBlackList->push_back( L"Downloads");
//		pvBlackList->push_back( L"Links");
//		pvBlackList->push_back( L"Searches");
//		pvBlackList->push_back( L"Contacts");
//		pvBlackList->push_back( L"Saved Games");
//
//		//Don't need to continue since the conf file is not exist
//		return;
//	}
//
//	//Parse CSIDL black list
//	int iDefault = 0;
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_DESKTOP", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_DESKTOP) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_PERSONAL", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_PERSONAL) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_FAVORITES", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_FAVORITES) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_MYMUSIC", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYMUSIC) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_MYVIDEO", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYVIDEO) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_MYPICTURES", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYPICTURES) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_SENDTO", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_SENDTO) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_STARTMENU", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_STARTMENU) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_NETHOOD", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_NETHOOD) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_APPDATA", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_APPDATA) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_PRINTHOOD", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_PRINTHOOD) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_LOCAL_APPDATA", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_LOCAL_APPDATA) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_TEMPLATES", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_TEMPLATES) );
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_COOKIES", iDefault, filename)) )
//	{
//		pvBlackList->push_back( getCSIDLFolderName(CSIDL_COOKIES) );
//	}
//	//The following are not defined CSIDL, but usually comes with UserProfile
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"DOWNLOADS", iDefault, filename)) )
//	{
//		pvBlackList->push_back( L"Downloads");
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"LINKS", iDefault, filename)) )
//	{
//		pvBlackList->push_back( L"Links");
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"SEARCHES", iDefault, filename)) )
//	{
//		pvBlackList->push_back( L"Searches");
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CONTACTS", iDefault, filename)) )
//	{
//		pvBlackList->push_back( L"Contacts");
//	}
//	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"SAVED_GAMES", iDefault, filename)) )
//	{
//		pvBlackList->push_back( L"Saved Games");
//	}
//		
//	//Parse SELF_DEFINE black list
//	int charsCount;
//	WCHAR szBlackList[4096] = {};
//	charsCount = GetPrivateProfileSectionW(L"SELF_DEFINE", szBlackList, 4096, filename);
//	int start = 0;
//	for (int i = 0; i < charsCount; i++)
//	{
//		WCHAR curChar = szBlackList[i];
//		if (curChar == L'\0')
//		{
//			std::wstring item( szBlackList + start, i);
//			pvBlackList->push_back(item);
//			start = i+1;			
//		}
//	}
//}
////int main()
////{
////	/* Since we don't see the entire desktop we must resize windows
////	   immediatly. */
////	SystemParametersInfo(SPI_SETDRAGFULLWINDOWS, TRUE, NULL, 0);
////
////	/* Disable screen saver since we cannot catch its windows. */
////	SystemParametersInfo(SPI_SETSCREENSAVEACTIVE, FALSE, NULL, 0);
////
////	/* We don't want windows denying requests to activate windows. */
////	SystemParametersInfo(SPI_SETFOREGROUNDLOCKTIMEOUT, 0, 0, 0);
////
////	WCHAR filename[MAX_PATH];
////	SHGetSpecialFolderPathW(NULL, filename, CSIDL_COMMON_APPDATA, 0);
////	lstrcatW(filename, L"\\ulteo\\ovd\\");
////	lstrcatW(filename, L"Conf.file");
////
////	WIN32_FIND_DATAW FindFileData;
////	HANDLE hFind;
////
////	hFind = FindFirstFileW(filename, &FindFileData);
////	if (hFind == INVALID_HANDLE_VALUE) 
////	{
////	//	printf("INVALID_HANDLE_VALUE \n");
////	}
////	else
////	{
////	//	printf("SUCCESS_HANDLE_VALUE \n");
////	}
////
////
////	std::vector<std::wstring> vBlackList;
////	setupBlackList(&vBlackList);
////
////	for(int i = 0; i<vBlackList.size(); ++i)
////	{
//////		printf("%S \n", vBlackList[i].c_str());
////	}
////	//Force refresh desktop
////	//waitDesktopRefresh();
////	
////	/* 
////	std::fstream fp;
////
////    fp.open(filename, std::ios::in);//開啟檔案
////
////    if(!fp)
////	{
////       printf("Open file failed!"); 
////    }
////	else
////	{
////       printf("Open file OK!"); 
////	}
////	fp.close();//關閉檔案
////	*/
////	
////	
////	//printf("CSIDL_DESKTOP %d  \n", iCSIDL_DESKTOP);
////	//printf("CSIDL_PERSONAL %d  \n", iCSIDL_PERSONAL);
////
////	
////
////	//WCHAR szBlackList[256] = {};
////	//GetPrivateProfileStringW(L"CSIDL", NULL, NULL, szBlackList, 256, filename);	
////	//WritePrivateProfileString("DB_SETTINGS", "USER_NUM_MAX", "99", "foobar.ini");
////
////	system("pause");
////	return 0;
////}
///*
//int PrintModules( DWORD processID )
//{
//    HMODULE hMods[1024];
//    HANDLE hProcess;
//    DWORD cbNeeded;
//    unsigned int i;
//
//    // Print the process identifier.
//
//    printf( "\nProcess ID: %u\n", processID );
//
//    // Get a handle to the process.
//
//    hProcess = OpenProcess( PROCESS_QUERY_INFORMATION |
//                            PROCESS_VM_READ,
//                            FALSE, processID );
//    if (NULL == hProcess)
//        return 1;
//
//	TCHAR Buffer[MAX_PATH];
//    if (GetModuleFileNameEx(hProcess, 0, Buffer, MAX_PATH))
//    {
//        printf( "%s \n" , Buffer);
//    }
//
//   // Get a list of all the modules in this process.
//
//    if( EnumProcessModules(hProcess, hMods, sizeof(hMods), &cbNeeded))
//    {
//        for ( i = 0; i < (cbNeeded / sizeof(HMODULE)); i++ )
//        {
//            TCHAR szModName[MAX_PATH];
//
//            // Get the full path to the module's file.
//
//            if ( GetModuleFileNameEx( hProcess, hMods[i], szModName,
//                                      sizeof(szModName) / sizeof(TCHAR)))
//            {
//                // Print the module name and handle value.
//
//                printf( TEXT("\t%s (0x%08X)\n"), szModName, hMods[i] );
//            }
//        }
//    }
//    
//    // Release the handle to the process.
//
//    CloseHandle( hProcess );
//
//    return 0;
//}
//
//int main( void )
//{
//
//    DWORD aProcesses[1024]; 
//    DWORD cbNeeded; 
//    DWORD cProcesses;
//    unsigned int i;
//
//    // Get the list of process identifiers.
//
//    if ( !EnumProcesses( aProcesses, sizeof(aProcesses), &cbNeeded ) )
//        return 1;
//
//    // Calculate how many process identifiers were returned.
//
//    cProcesses = cbNeeded / sizeof(DWORD);
//
//    // Print the names of the modules for each process.
//
//    for ( i = 0; i < cProcesses; i++ )
//    {
//        PrintModules( aProcesses[i] );
//    }
//	
//	system("pause");
//    return 0;
//}
//*/
///*
//LRESULT CALLBACK WndProc(HWND hwnd, UINT msg, WPARAM wParam, LPARAM lParam) {	
//		return DefWindowProc(hwnd, msg, wParam, lParam);	
//}
//BOOL createInternalWindow(HINSTANCE instance) 
//{
//	WNDCLASSEXW wc;
//
//	wc.cbSize = sizeof(WNDCLASSEX);
//	wc.style = 0;
//	wc.lpfnWndProc = WndProc;
//	wc.cbClsExtra = 0;
//	wc.cbWndExtra = 0;
//	wc.hInstance = instance;
//	wc.hIcon = NULL;
//	wc.hCursor = NULL;
//	wc.hbrBackground = (HBRUSH)(COLOR_WINDOW+1);
//	wc.lpszMenuName  = NULL;
//	wc.lpszClassName = L"Tester";
//	wc.hIconSm = NULL;
//
//	if(!RegisterClassExW(&wc))
//		return FALSE;
//
//	static HWND hwnd_internal;
//	hwnd_internal = CreateWindowExW(WS_EX_CLIENTEDGE, L"Test", L"Test", WS_POPUP|WS_CLIPCHILDREN|WS_VISIBLE, 0, 0, 0, 0, NULL, NULL, instance, NULL);
//
//	if(hwnd_internal == NULL) {
//		return FALSE;
//	}
//
//	ShowWindow(hwnd_internal, SW_SHOW);
//	UpdateWindow(hwnd_internal);
//
//	return TRUE;
//}
//
//
//int WINAPI
//WinMain(HINSTANCE instance, HINSTANCE prev_instance, LPSTR cmdline, int cmdshow)
//{	
//	HWND hWnd = GetConsoleWindow();
//	ShowWindow( hWnd, SW_HIDE );
//	//createInternalWindow(instance);
//
//	system("pause");
//};*/
//#include <iostream>
//using namespace std;
//#include <windows.h>
//
//// Here's some fun timer stuff for the user.
//// (Notice how he won't see it work when the
////  console is hidden, but it will still work.)
//void timeout(int t)
//  {
//  for (int cntr = t; cntr > 0; cntr--)
//    {
//    cout << "\r" << cntr << flush;
//    Sleep( 1000 );
//    }
//  cout << "\r" << flush;
//  }
//
//// Demonstrate some fun stuff.
//// Notice how hiding the console window causes it to disappear from
//// the Windows task bar. If you only want to make it minimize, use
//// SW_MINIMIZE instead of SW_HIDE.
////
//int main()
////int WINAPI
////WinMain(HINSTANCE instance, HINSTANCE prev_instance, LPSTR cmdline, int cmdshow)
//  {
//
//  cout << "Preparing to hide the console window\n";
//  timeout(3);
//  ShowWindow( GetConsoleWindow(), SW_HIDE );
//
//  cout << "Preparing to show the console window\n";
//  timeout(30);
//  ShowWindow( GetConsoleWindow(), SW_RESTORE );
//
//  cout << "All done!\n";
//	system("pause");
//  return 0;
//  }

#include <stdio.h>
#include <cstdio>
#include <ctime>
#include <windows.h>
#include <boost/function.hpp>
#include <boost/bind.hpp>

typedef boost::function<float(int)> FooFunction;

float foo(int n)
{
	printf("foo = %d", n);
	return n+1;
}
int main()
{
	// Boost bind take max to 9 args, 
	// so you have to wrap this args into struct to fit 9 less args meets.

	/*int a = 1;
	boost::function<float(int)> f1; 
	f1 = boost::bind(&foo, a);

	float v = f1(1);
	printf("foo +1 = %f", v);
	system("pause");*/

	std::string szPathRef("123");
	std::string k = szPathRef.substr( 3, 1 );
	printf("%s", k);

	char p[1];
	std::string mem1("321");
	memcpy(p, mem1.c_str(), mem1.length()+1);
	printf("%s \n", p);

	std::string mem("1234567890");
	memcpy(p, mem.c_str(), mem.length()+1);
	printf("%s \n", p);
	
	

	system("pause");
	return 0;
}