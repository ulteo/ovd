add_executable(VFSHookLoader main.cpp)
set_target_properties(VFSHookLoader PROPERTIES OUTPUT_NAME "VFSHookLoader${ARCH}")

install(TARGETS VFSHookLoader DESTINATION .)

