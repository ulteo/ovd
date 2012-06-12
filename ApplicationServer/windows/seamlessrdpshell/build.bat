rd /s/q build
vcbuild vchannel.vcproj /clean
vcbuild HookDll\hookdll.vcproj /clean
vcbuild clipper.vcproj /clean

mkdir build
vcbuild vchannel.vcproj Release
vcbuild HookDll\hookdll.vcproj Release
vcbuild clipper.vcproj Release

