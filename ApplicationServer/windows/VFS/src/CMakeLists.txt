if (${ARCH} MATCHES "32")
	add_subdirectory (VFS)
endif()

add_subdirectory (VFSHook)
add_subdirectory (VFSHookLoader)
