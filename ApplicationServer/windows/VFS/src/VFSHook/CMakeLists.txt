file(GLOB_RECURSE DISASM_FILE disasm-lib/*.c)
file(GLOB_RECURSE MHOOK_FILE  mhook-lib/*.cpp)

include_directories(${CMAKE_CURRENT_SOURCE_DIR})
add_library(VFSHook SHARED dllmain.cpp Logger.cpp InterceptAPI.cpp VirtualFileSystem.cpp stdafx.cpp ${DISASM_FILE} ${MHOOK_FILE})
target_link_libraries(VFSHook Shlwapi)

set_target_properties(VFSHook PROPERTIES OUTPUT_NAME "VFSHook${ARCH}")

install(TARGETS VFSHook DESTINATION .)
