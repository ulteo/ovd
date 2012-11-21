file(GLOB_RECURSE SRC_FILE "*.c")

add_executable (${PROJECT_NAME} ${SRC_FILE})

target_link_libraries(${PROJECT_NAME} fuse ulockmgr )

install(TARGETS ${PROJECT_NAME} DESTINATION ${BIN_PATH})

