file(GLOB_RECURSE SOURCE_FILE *.cpp)

add_executable(imer ${SOURCE_FILE})
target_link_libraries(imer vchannel)

install(TARGETS imer DESTINATION .)

