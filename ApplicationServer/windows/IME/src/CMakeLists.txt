file(GLOB_RECURSE SOURCE_FILE *.cpp)
file(GLOB_RECURSE DEF_FILE *.def)
file(GLOB_RECURSE RC_FILE *.def)

add_library(UlteoTSF SHARED ${SOURCE_FILE} ${DEF_FILE} ${RC_FILE})
#target_link_libraries(VFS common Shlwapi)

install(TARGETS UlteoTSF RUNTIME DESTINATION .)

