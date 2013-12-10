# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author Vincent Roullier <vincent.roullier@ulteo.com> 2013
# 
# This program is free software; you can redistribute it and/or 
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#

cmake_minimum_required (VERSION 2.8)
project(libRDP C)

set(CMAKE_INSTALL_PREFIX  "/usr/lib")

option(STATIC_COMPILATION "Static Compilation" OFF)
set(CMAKE_MODULE_PATH ${CMAKE_MODULE_PATH} "${CMAKE_SOURCE_DIR}/cmake/")
 
if (${STATIC_COMPILATION})
  set(PREFIX "${CMAKE_CURRENT_SOURCE_DIR}/")
else()
  set(PREFIX "./")
endif()

if(CMAKE_SIZEOF_VOID_P EQUAL 8)
    SET(LIBRARY_OUTPUT_PATH ${PREFIX}64)
    SET(CMAKE_SYSTEM_PROCESSOR "x86_64")
else()
    SET(LIBRARY_OUTPUT_PATH ${PREFIX}32)
   SET(CMAKE_SYSTEM_PROCESSOR "i386")
 endif() 

if (WIN32)
    find_package(WindowsSDK)
    find_package(Jpg REQUIRED)
else(WIN32)
    find_package(JPEG REQUIRED)
endif(WIN32)


#
#if (STATIC_COMPILATION) 
#    set(CMAKE_FIND_LIBRARY_SUFFIXES ".a")
#endif()
find_package(JNI)

message(${JNI_LIBRARIES})


if(MSVC)
	set(CMAKE_C_FLAGS_DEBUG "${CMAKE_C_FLAGS_DEBUG} /EHsc /RTCsu /W3 /MTd /Od")
	
	set(CMAKE_C_FLAGS_RELEASE "${CMAKE_C_FLAGS_RELEASE} /EHsc /MT /O1")
	
	set(CMAKE_C_FLAGS_RELWITHDEBINFO "${CMAKE_C_FLAGS_RELWITHDEBINFO} /EHsc /MT")
	
	set(CMAKE_C_FLAGS_MINSIZEREL "${CMAKE_C_FLAGS_MINSIZEREL} /EHsc /MT")
else(MSVC)
	set(CMAKE_C_FLAGS_DEBUG "${CMAKE_C_FLAGS_DEBUG} -g -Wall -Wno-unused-variable")
	
	set(CMAKE_C_FLAGS_RELEASE "${CMAKE_C_FLAGS_DEBUG} -0s")
endif(MSVC)


file (GLOB librdp_SRCS ${CMAKE_CURRENT_SOURCE_DIR}/src/*.c)
file (GLOB_RECURSE librdp_HDRS ${CMAKE_CURRENT_SOURCE_DIR}/src/*.h)

include_directories(
	${JNI_INCLUDE_DIRS}
	${JPEG_INCLUDE_DIR}
	)

set(LINK_LIB
#	${JNI_LIBRARIES}
	${JPEG_LIBRARY}
)

if (WIN32)
  set (libNAME "librdp")
else (WIN32)
  set (libNAME "rdp")
endif(WIN32)

add_definitions("-D_JNI_IMPLEMENTATION_")
add_library(${libNAME} SHARED ${librdp_SRCS} ${librdp_HDRS})
target_link_libraries(${libNAME} ${LINK_LIB})
install(TARGETS ${libNAME} DESTINATION $ENV{DESTDIR}${CMAKE_INSTALL_PREFIX})
