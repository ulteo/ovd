set INITIAL_DIR=%cd%

call setenv.bat %WINDDK_PATH%  x64 fre WNET no_oacr

cd /D "%INITIAL_DIR%"

build
