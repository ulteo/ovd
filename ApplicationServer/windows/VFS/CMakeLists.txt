cmake_minimum_required (VERSION 2.8) 
project (VFSHook CXX C)


set( ${PROJECT_NAME}_MAJOR_VERSION 0 )
set( ${PROJECT_NAME}_MINOR_VERSION 1 )
set( ${PROJECT_NAME}_PATCH_LEVEL 0 )

set(ARCH "32")

if (MSVC)
	add_definitions(-DUNICODE -D_UNICODE)
endif()

if (${CMAKE_GENERATOR} MATCHES ".*Win64")
	set(ARCH "64")
endif()

add_subdirectory (src)
