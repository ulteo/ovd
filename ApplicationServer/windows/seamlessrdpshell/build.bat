rd /s/q build Release HookDll\Release

mkdir build
vcbuild vchannel.vcproj Release
vcbuild HookDll\hookdll.vcproj Release
vcbuild clipper.vcproj Release

