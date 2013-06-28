cmake_minimum_required (VERSION 2.8) 
project (seamlessrdpshell CXX C)


set( ${PROJECT_NAME}_MAJOR_VERSION 0 )
set( ${PROJECT_NAME}_MINOR_VERSION 1 )
set( ${PROJECT_NAME}_PATCH_LEVEL 0 )

set(ARCH "32")

if (MSVC)
	set(CMAKE_CXX_FLAGS_RELEASE "${CMAKE_CXX_FLAGS_RELEASE} /MT")
	set(CMAKE_CXX_FLAGS_DEBUG "${CMAKE_CXX_FLAGS_DEBUG} /MTd")

	set(CMAKE_C_FLAGS_RELEASE "${CMAKE_C_FLAGS_RELEASE} /MT")
	set(CMAKE_C_FLAGS_DEBUG "${CMAKE_C_FLAGS_DEBUG} /MTd")
endif()

if (${CMAKE_GENERATOR} MATCHES ".*Win64")
	set(ARCH "64")
endif()

include_directories(${CMAKE_CURRENT_SOURCE_DIR}/src)

add_subdirectory (src)
