add_executable(VFS main.cpp)
target_link_libraries(VFS common Shlwapi)

install(TARGETS VFS DESTINATION .)

