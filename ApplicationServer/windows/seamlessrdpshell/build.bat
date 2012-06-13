rd /s/q build
vcbuild vchannel.vcproj /clean
vcbuild HookDll\hookdll.vcproj /clean
vcbuild hook-launcher\HookLauncher.vcproj /clean
vcbuild clipper.vcproj /clean

mkdir build
vcbuild vchannel.vcproj Release
vcbuild HookDll\hookdll.vcproj Release /platform:Win32
vcbuild HookDll\hookdll.vcproj Release /platform:x64
vcbuild hook-launcher\HookLauncher.vcproj Release
vcbuild clipper.vcproj Release

