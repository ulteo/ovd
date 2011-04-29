set INITIAL_DIR=%cd%

call setenv.bat %WINDDK_PATH%  x86 fre WXP no_oacr

cd /D "%INITIAL_DIR%"

build
if not %errorlevel%==0 goto :error

ren build common
if not %errorlevel%==0 goto :error

exit 0


:error
exit 1
