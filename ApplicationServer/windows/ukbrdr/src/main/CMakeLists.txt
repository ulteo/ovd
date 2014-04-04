file(GLOB_RECURSE SOURCE_FILE *.cpp)

add_executable(ukbrdr ${SOURCE_FILE})
target_link_libraries(ukbrdr vchannel)

install(TARGETS ukbrdr DESTINATION .)

