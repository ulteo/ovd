IF EXIST build64 RD /Q /S build64

md build64
cd build64
cmake -DCMAKE_INSTALL_PREFIX=..\dist\64 -G "Visual Studio 10 Win64" ..
cmake --build . --config Release --target install

