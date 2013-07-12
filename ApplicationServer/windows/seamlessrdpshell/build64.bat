IF EXIST build64 RD /Q /S build64

md build64
if not %errorlevel%==0 goto :error

cd build64
if not %errorlevel%==0 goto :error

cmake -DCMAKE_INSTALL_PREFIX=..\build -G "Visual Studio 10 Win64" ..
if not %errorlevel%==0 goto :error

cmake --build . --config Release --target install
if not %errorlevel%==0 goto :error


error:
exit 1

