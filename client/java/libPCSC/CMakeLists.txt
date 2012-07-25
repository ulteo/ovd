# Copyright (C) 2012 Ulteo SAS
# http://www.ulteo.com
# Author Vincent Roullier <vincent.roullier@ulteo.com> 2012
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
project(libPCSC)

find_package(JNI)

if(MSVC)
	set(CMAKE_CXX_FLAGS_DEBUG "${CMAKE_CXX_FLAGS_DEBUG} /EHsc /RTCsu /W3 /MTd /Od")
	set(CMAKE_C_FLAGS_DEBUG "${CMAKE_C_FLAGS_DEBUG} /EHsc /RTCsu /W3 /MTd /Od")
	
	set(CMAKE_CXX_FLAGS_RELEASE "${CMAKE_CXX_FLAGS_RELEASE} /EHsc /MT /O1")
	set(CMAKE_C_FLAGS_RELEASE "${CMAKE_C_FLAGS_RELEASE} /EHsc /MT /O1")
	
	set(CMAKE_CXX_FLAGS_RELWITHDEBINFO "${CMAKE_CXX_FLAGS_RELWITHDEBINFO} /EHsc /MT")
	set(CMAKE_C_FLAGS_RELWITHDEBINFO "${CMAKE_C_FLAGS_RELWITHDEBINFO} /EHsc /MT")
	
	set(CMAKE_CXX_FLAGS_MINSIZEREL "${CMAKE_CXX_FLAGS_MINSIZEREL} /EHsc /MT")
	set(CMAKE_C_FLAGS_MINSIZEREL "${CMAKE_C_FLAGS_MINSIZEREL} /EHsc /MT")
else(MSVC)
	set(CMAKE_CXX_FLAGS_DEBUG "${CMAKE_CXX_FLAGS_DEBUG} -g -Wall -Wno-unused-variable")
	set(CMAKE_C_FLAGS_DEBUG "${CMAKE_C_FLAGS_DEBUG} -g -Wall -Wno-unused-variable")
	
	set(CMAKE_CXX_FLAGS_RELWITHDEBINFO "${CMAKE_CXX_FLAGS_DEBUG} -pg")
	set(CMAKE_C_FLAGS_RELWITHDEBINFO "${CMAKE_C_FLAGS_DEBUG} -pg")
	
	set(CMAKE_CXX_FLAGS_RELEASE "${CMAKE_CXX_FLAGS_DEBUG} -0s")
	set(CMAKE_CXX_FLAGS_RELEASE "${CMAKE_C_FLAGS_DEBUG} -0s")
endif(MSVC)


file (GLOB_RECURSE libpcsc_SRCS ${CMAKE_CURRENT_SOURCE_DIR}/src/*.c)
file (GLOB_RECURSE libpcsc_hdrs ${CMAKE_CURRENT_SOURCE_DIR}/src/*.h)

include_directories(
	${JNI_INCLUDE_DIRS}
	)

set(LINK_LIB
	${JAVA_AWT_LIBRARY}
	winscard
	)

add_definitions("-D_JNI_IMPLEMENTATION_")
add_library(libPCSC SHARED ${libpcsc_SRCS} ${libpcsc_hdrs})
target_link_libraries(libPCSC ${LINK_LIB})