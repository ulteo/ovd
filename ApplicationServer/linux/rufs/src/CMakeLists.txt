file(GLOB SRC_FILE "*.c")

add_subdirectory (common)

add_executable (${PROJECT_NAME} ${SRC_FILE})

target_link_libraries(${PROJECT_NAME} common fuse ulockmgr pam m)

install(TARGETS ${PROJECT_NAME} DESTINATION ${BIN_PATH})

