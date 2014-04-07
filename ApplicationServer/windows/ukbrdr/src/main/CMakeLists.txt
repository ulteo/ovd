file(GLOB_RECURSE SOURCE_FILE *.cpp)

add_executable(ukbrdr WIN32 ${SOURCE_FILE})
target_link_libraries(ukbrdr vchannel)

install(TARGETS ukbrdr DESTINATION .)

