add_executable(hook_launcher WIN32 *.cpp)
target_link_libraries(hook_launcher)


if (${ARCH} MATCHES "64")
        set_target_properties(hook_launcher PROPERTIES OUTPUT_NAME "hook_launcher_x64")
endif()

install(TARGETS hook_launcher DESTINATION .)

