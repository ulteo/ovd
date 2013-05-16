set(CPACK_PACKAGE_NAME "rufs")
set(CPACK_PACKAGE_VERSION "${${PROJECT_NAME}_MAJOR_VERSION}.${${PROJECT_NAME}_MINOR_VERSION}.${${PROJECT_NAME}_PATCH_LEVEL}")
execute_process(COMMAND "${DPKG_CMD}" --print-architecture OUTPUT_VARIABLE CPACK_DEBIAN_PACKAGE_ARCHITECTURE OUTPUT_STRIP_TRAILING_WHITESPACE)
set(CPACK_DEBIAN_PACKAGE_DEPENDS "fuse-utils")
set(CPACK_PACKAGE_CONTACT "David LECHEVALIER <david@ulteo.com>")
set(CPACK_DEBIAN_PACKAGE_DESCRIPTION "Provide a regular epression based union filesystem")
set(CPACK_INSTALL_PREFIX /usr)

set (CPACK_SET_DESTDIR ON)


FIND_PROGRAM(DPKG_CMD dpkg)
if (DPKG_CMD)
        set(CPACK_GENERATOR ${CPACK_GENERATOR};DEB)
	if( NOT EXISTS "/bin/gzip")
		MESSAGE(FATAL_ERROR "Program gzip is missing on your system.")
	endif(NOT EXISTS "/bin/gzip")

	add_custom_target (
		debian_package
		DEPENDS ${CMAKE_CURRENT_BINARY_DIR}/changelog.Debian.gz
	)

	add_custom_command (
		OUTPUT     ${CMAKE_CURRENT_BINARY_DIR}/changelog.Debian.gz
		COMMAND    /bin/cp ARGS ${CMAKE_CURRENT_SOURCE_DIR}/CHANGELOG ${CMAKE_CURRENT_BINARY_DIR}/changelog.Debian
		COMMAND    /bin/gzip ARGS -9 ${CMAKE_CURRENT_BINARY_DIR}/changelog.Debian
		DEPENDS    ${CMAKE_CURRENT_SOURCE_DIR}/CHANGELOG
	)

	#install (
	#	FILES         ${CMAKE_CURRENT_SOURCE_DIR}/COPYRIGHT
	#	DESTINATION   /usr/share/doc/${CMAKE_PROJECT_NAME}
	#	RENAME        copyright
	#)

	#install (
	#	FILES         ${CMAKE_CURRENT_BINARY_DIR}/changelog.Debian.gz
	#	DESTINATION   /usr/share/doc/${CMAKE_PROJECT_NAME}
	#)
endif()
ADD_DEPENDENCIES(${CMAKE_PROJECT_NAME} debian_package)


INCLUDE(CPack)

