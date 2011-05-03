@echo off

cmd /C common.bat
if not %errorlevel%==0 goto :error

cmd /C make86.bat
if not %errorlevel%==0 goto :error

cmd /C make64.bat
if not %errorlevel%==0 goto :error

rmdir /Q /S build
if not %errorlevel%==0 goto :error

exit 0


:error
exit 1
