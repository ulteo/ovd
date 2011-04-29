set INITIAL_DIR=%cd%

call setenv.bat %WINDDK_PATH%  x64 fre WNET no_oacr

cd /D "%INITIAL_DIR%"

cd dokan
build
if not %errorlevel%==0 goto :error
cd ..

cd sys
build
if not %errorlevel%==0 goto :error
cd ..


cd dokan_np
build
if not %errorlevel%==0 goto :error
cd ..

exit 0


:error
exit 1
