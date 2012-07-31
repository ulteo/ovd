#include <stdio.h>
#include <cstdio>
#include <ctime>
#include <windows.h>

#define DLL_PATH "VFSHook.dll"
#define TEST_FILE_PATH "D:\\HookTest\\AppOutput.txt"

void writeFileTest(char *fmt,...)
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

	printf("write file test: output file to : %s \n", TEST_FILE_PATH);
	writeFileTest("Origin name of file wrote to : %s", TEST_FILE_PATH);

	//TODO:
	printf("read file test: read file from: ... \n");
	
	FreeLibrary(hDLL);
	system("pause");
    return 0;
}
