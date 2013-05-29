add_executable(VFSHookLoader main.cpp)
target_link_libraries(VFSHookLoader common)
set_target_properties(VFSHookLoader PROPERTIES OUTPUT_NAME "VFSHookLoader${ARCH}")

install(TARGETS VFSHookLoader DESTINATION .)

