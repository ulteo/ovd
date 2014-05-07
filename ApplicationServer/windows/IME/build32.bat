IF EXIST build32 RD /Q /S build32

md BUILD32
cd build32
cmake -DCMAKE_INSTALL_PREFIX=..\dist\32 -G "Visual Studio 10" ..
cmake --build . --config Release --target install

