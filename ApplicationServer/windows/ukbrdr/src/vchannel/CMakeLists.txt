file(GLOB_RECURSE VCHANNEL_FILE *.c)

add_library(vchannelb SHARED ${VCHANNEL_FILE})
target_link_libraries(vchannelb wtsapi32)


install(TARGETS vchannelb RUNTIME DESTINATION .)

