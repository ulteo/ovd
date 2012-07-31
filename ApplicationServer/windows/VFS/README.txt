Proj VFSHook:
	The dll project.
	Write log to "D:\\HookTest\\HookLog32.txt"

	The hook testing, check the myCreateFileW function, 
	it rename the file wrote by VFSHookTester.exe from "AppOutput.txt" to "AppOutputRedirect.txt"
	

Proj VFSHookTester:
	Program to run up dll for testing.
	This program load VFSHook.dll and run api tests.

	For example, write test file to "D:\\HookTest\\AppOutput.txt"


NOTE:
	The originAPIFunctions not working will outside the myAPIFunctions.
	In this case we might need to find other way to do the log.		