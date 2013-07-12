IF EXIST build32 RD /Q /S build32

md build32
if not %errorlevel%==0 goto :error

cd build32
if not %errorlevel%==0 goto :error

cmake -DCMAKE_INSTALL_PREFIX=..\build -G "Visual Studio 10" ..
if not %errorlevel%==0 goto :error

cmake --build . --config Release --target install
if not %errorlevel%==0 goto :error


error:
exit 1

