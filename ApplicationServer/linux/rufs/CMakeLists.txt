cmake_minimum_required (VERSION 2.6) 
project (RegularUnionFS C)

set( ${PROJECT_NAME}_MAJOR_VERSION 0 )
set( ${PROJECT_NAME}_MINOR_VERSION 1 )
set( ${PROJECT_NAME}_PATCH_LEVEL 0 )

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
