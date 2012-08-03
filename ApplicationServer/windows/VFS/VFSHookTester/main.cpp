#include <stdio.h>
#include <cstdio>
#include <ctime>
#include <windows.h>

#define DLL_PATH			"VFSHook.dll"

#define VFS_DLL_LOG_PATH	"D:\\HookTest\\"
#define TEST_FILE_PATH		"D:\\HookTest\\AppOutput.txt"

#define	TEST_REG_MAIN_KEY	HKEY_LOCAL_MACHINE
#define	TEST_REG_SUB_KEY	TEXT("Software")

void WriteFileTest(char *fmt,...)
{
	va_list args;
	char temp[5000];
	HANDLE hFile;

	if((hFile = CreateFileA(TEST_FILE_PATH, GENERIC_WRITE, 0, NULL, OPEN_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL)) <0)
	{
		return;
	}
	
	_llseek((HFILE)hFile, 0, SEEK_END);
		
	DWORD dw;
		
	va_start(args,fmt);
	vsprintf_s(temp, fmt, args);
	va_end(args);
	WriteFile(hFile, temp, strlen(temp), &dw, NULL);

	wsprintfA(temp, "\r\n");
	WriteFile(hFile, temp, strlen(temp), &dw, NULL);

	_lclose((HFILE)hFile);
}

void RegOpenKeyTest()
{
	HKEY	hKey;
	HKEY	hMainKey = TEST_REG_MAIN_KEY;
	LPCSTR	lpSubKey = TEST_REG_SUB_KEY;

	LONG lErrorCode = RegOpenKey(hMainKey, lpSubKey, &hKey);;
	if (lErrorCode != ERROR_SUCCESS)
	{
		printf("RegOpenKeyEx failed.");
	}

	lErrorCode = RegCloseKey(hKey);
	if (lErrorCode != ERROR_SUCCESS)
	{
		printf("RegCloseKey failed.");
	}		
}
void RegOpenKeyExTest()
{
	HKEY	hKey;
	HKEY	hMainKey = TEST_REG_MAIN_KEY;
	LPCSTR	lpSubKey = TEST_REG_SUB_KEY;
	
	LONG lErrorCode = RegOpenKeyEx(hMainKey, lpSubKey, 0, KEY_NOTIFY | KEY_READ, &hKey);
	if (lErrorCode != ERROR_SUCCESS)
	{
		printf("RegOpenKeyEx failed.");
	}

	lErrorCode = RegCloseKey(hKey);
	if (lErrorCode != ERROR_SUCCESS)
	{
		printf("RegCloseKey failed.");
	}
}
int main()
{	
	HINSTANCE hDLL;
	hDLL = LoadLibrary(DLL_PATH);
 
	// Check to see if the library was loaded successfully 
	if (hDLL != 0)
		printf("DLL loaded!\n");
	else
		printf("DLL failed to load!\n");																											
	
	if(!hDLL)
	{
		system("pause");
		return 0;
	}
	
	printf("========= Test Start =========\n");
	printf("\n");

	printf("WriteFileTest, file : %s \n", TEST_FILE_PATH);
	WriteFileTest("Origin name of file wrote to : %s", TEST_FILE_PATH);
	//TODO:
	//printf("read file test: read file from: ... \n");
	
	printf("RegOpenKeyTest, key : %s \n", TEST_REG_SUB_KEY);
	RegOpenKeyTest();	
	printf("RegOpenKeyExTest, key : %s \n", TEST_REG_SUB_KEY);
	RegOpenKeyExTest();
	
	printf("\n");
	printf("========= Test End =========\n");

	printf("========= Check the log : %s =========\n", VFS_DLL_LOG_PATH);

	FreeLibrary(hDLL);
	system("pause");
    return 0;
}
