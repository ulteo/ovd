add_executable(VFS WIN32 main.cpp VFS.cpp)
target_link_libraries(VFS common Shlwapi)

install(TARGETS VFS DESTINATION .)

