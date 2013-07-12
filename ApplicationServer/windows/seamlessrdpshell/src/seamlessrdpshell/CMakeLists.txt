file(GLOB_RECURSE SEAMLESSRDPSHELL_FILE *.c)
file(GLOB_RECURSE SEAMLESSRDPSHELL_RC_FILE *.rc)


add_executable(seamlessrdpshell WIN32 ${SEAMLESSRDPSHELL_FILE} ${SEAMLESSRDPSHELL_RC_FILE})
target_link_libraries(seamlessrdpshell common vchannel)


install(TARGETS seamlessrdpshell DESTINATION .)

