file(GLOB_RECURSE HOOK_FILE *.c)

add_library(hook SHARED ${HOOK_FILE})
target_link_libraries(hook common)

set_target_properties(hook PROPERTIES OUTPUT_NAME "seamlessrdp")

if (${ARCH} MATCHES "64")
        set_target_properties(hook PROPERTIES OUTPUT_NAME "seamlessrdp_x64")
endif()

install(TARGETS hook RUNTIME DESTINATION .)
