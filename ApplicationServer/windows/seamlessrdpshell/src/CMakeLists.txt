add_subdirectory (common)
add_subdirectory (hook)

if (${ARCH} MATCHES "32")
	add_subdirectory (vchannel)
	add_subdirectory (seamlessrdpshell)
endif()

if (${ARCH} MATCHES "64")
	add_subdirectory (hook-launcher)
endif()

