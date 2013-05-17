add_executable(VFS main.cpp)
target_link_libraries(VFS common)

install(TARGETS VFS DESTINATION .)

