set WINDDK_PATH=C:\WinDDK\7600.16385.1
set PATH=%PATH%;%WINDDK_PATH%\bin
set INITIAL_DIR=%cd%
md \tmp
copy * \tmp

call setenv.bat %WINDDK_PATH%  x86 fre WXP
cd \tmp
build
md "%INITIAL_DIR%\%_BUILDARCH%"
copy "%_BUILDARCH%" "%INITIAL_DIR%\%_BUILDARCH%"
cd "%INITIAL_DIR%"
rmdir /Q /S \tmp
