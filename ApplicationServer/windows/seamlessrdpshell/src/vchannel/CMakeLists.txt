file(GLOB_RECURSE VCHANNEL_FILE *.c)

add_library(vchannel SHARED ${VCHANNEL_FILE})
target_link_libraries(vchannel wtsapi32)


install(TARGETS vchannel RUNTIME DESTINATION .)

