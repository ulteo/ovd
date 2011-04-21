set INITIAL_DIR=%cd%

call setenv.bat %WINDDK_PATH%  x86 fre WXP no_oacr

cd /D "%INITIAL_DIR%"

cd dokan
build
cd ..


cd dokan_np
build
cd ..

cd dokan_webdav
build
cd ..
