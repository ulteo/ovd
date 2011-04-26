cmd /c common.bat
move build x86

set INITIAL_DIR=%cd%

call setenv.bat %WINDDK_PATH%  x86 fre WXP no_oacr

cd /D "%INITIAL_DIR%"

cd dokan
build
cd ..

cd sys
build
cd ..


cd dokan_np
build
cd ..

rmdir /Q /S build

