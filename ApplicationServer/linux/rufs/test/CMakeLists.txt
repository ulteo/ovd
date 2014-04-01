file(GLOB SRC_TEST "*.cpp")

include_directories(${gtest_SOURCE_DIR}/include ${gtest_SOURCE_DIR})

add_executable(${TEST_BIN} ${SRC_TEST})

target_link_libraries(${TEST_BIN} common fuse ulockmgr pam m gtest gtest_main pthread)
