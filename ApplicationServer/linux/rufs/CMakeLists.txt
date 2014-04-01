IF(DEFINED CMAKE_BUILD_TYPE)
  SET(CMAKE_BUILD_TYPE ${CMAKE_BUILD_TYPE} CACHE STRING "Choose the type of build options are: None(CMAKE_CXX_FLAGS or CMAKE_C_FLAGS used) Debug Release RelWithDebInfo MinSizeRel.")
ELSE()
  SET(CMAKE_BUILD_TYPE Debug CACHE STRING "Choose the type of build options are: None(CMAKE_CXX_FLAGS or CMAKE_C_FLAGS used) Debug Release RelWithDebInfo MinSizeRel.")
ENDIF()

cmake_minimum_required (VERSION 2.6) 

option(TEST "Build all tests." OFF)
set(GTEST_SRC /usr/src/gtest)

project (RegularUnionFS)

set( ${PROJECT_NAME}_MAJOR_VERSION 0 )
set( ${PROJECT_NAME}_MINOR_VERSION 1 )
set( ${PROJECT_NAME}_PATCH_LEVEL 0 )

message("${PROJECT_NAME} will be compiled in ${CMAKE_BUILD_TYPE} mode")
set(CMAKE_C_FLAGS_DEBUG "-g -Wall")
set(CMAKE_CXX_FLAGS_DEBUG "-g -Wall -fpermissive")

include(FindPkgConfig)

set(BIN_PATH  /usr/sbin)
set(CONF_PATH /etc/ulteo/rufs)

pkg_check_modules(FUSE fuse>=2.6)
if (FUSE_FOUND EQUAL 1)
	set(CMAKE_C_FLAGS "${FUSE_CFLAGS_OTHER} ${CMAKE_C_FLAGS}")
endif()

add_subdirectory (src)
add_subdirectory (conf)
add_subdirectory (packaging)

if (TEST)
	set(CMAKE_CXX_FLAGS "-g -Wall")
	set(TEST_BIN ${PROJECT_NAME}_test)

	enable_testing()
	add_subdirectory(/usr/src/gtest gtest)
	include_directories("/usr/include/gtest")

	add_subdirectory(test)

	##############
	# Unit Tests
	##############
	add_test(${TEST_BIN} test/${TEST_BIN})
endif()
