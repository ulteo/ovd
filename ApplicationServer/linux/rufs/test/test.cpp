#include <iostream>
#include "gtest/gtest.h"

extern "C" {
	#include "../src/common/str.h"
	#include "../src/common/xdg_user_dir.h"
	#include "../src/common/fs.h"
	#include "../src/common/regexp.h"
}

char* buffer = (char*)malloc(1000);

TEST(str, str_len) {
	ASSERT_EQ(4, str_len("test"));
	ASSERT_EQ(4, str_len("test\0un"));
	ASSERT_EQ(0, str_len(""));
	ASSERT_EQ(0, str_len(NULL)) << "str_len(NULL) should return 0";
}

TEST(str, str_cpy) {
	ASSERT_STREQ("test", str_cpy(buffer, "test"));
	ASSERT_EQ(0, str_cpy(NULL, "test"));
	ASSERT_EQ(0, str_cpy(buffer, NULL));	
	ASSERT_EQ(0, str_len(buffer));
}

TEST(str, str_ncpy) {
	str_cpy(buffer, "----");
	ASSERT_STREQ("te--", str_ncpy(buffer, "test", 2));
	ASSERT_EQ(0, str_ncpy(NULL, "test", 2));
	ASSERT_EQ(0, str_ncpy(buffer, NULL, 2));
}

TEST(xdg, xdg_user_dir_lookup) {
	ASSERT_NE(0, xdg_user_dir_lookup("DESKTOP"));
}

TEST(fs, getShortName) {
	ASSERT_STREQ("david", file_getShortName("/home/david"));
	ASSERT_STREQ("home", file_getShortName("/home"));
	ASSERT_EQ(0, file_getShortName("david"));
}

TEST(fs, expandPath) {
	ASSERT_NE(0, fs_expandPath("/home/david/test", buffer));
	ASSERT_STREQ("/home/david/test", buffer);
	ASSERT_NE(0, fs_expandPath("%{DESKTOP}/test", buffer));
	ASSERT_STRNE("/test", buffer);
	ASSERT_NE(0, fs_expandPath("test${HOME}test", buffer));
	ASSERT_STRNE("test${HOME}test", buffer);
}

TEST(fs, getRoot) {
	ASSERT_STREQ("home", fs_getRoot("/home/david/test"));
	ASSERT_STREQ("david", fs_getRoot("david/test"));
	ASSERT_STREQ("test", fs_getRoot("test"));
}

TEST(fs, join) {
	ASSERT_EQ(0, fs_join(NULL, NULL));
	ASSERT_EQ(0, fs_join("test", NULL));
	ASSERT_EQ(0, fs_join(NULL, "test"));
	ASSERT_STREQ("david/test", fs_join("david", "test"));
	ASSERT_STREQ("david/test", fs_join("david/", "test"));
	ASSERT_STREQ("david/test", fs_join("david/", "/test"));
	ASSERT_STREQ("david/test", fs_join("david//", "test"));
	ASSERT_STREQ("david/test", fs_join("david/", "//test"));
	ASSERT_STREQ("david/test", fs_join("david", "/test"));
	ASSERT_STREQ("david", fs_join("david", ""));
	ASSERT_STREQ("david", fs_join("david", "/"));
	ASSERT_STREQ("david", fs_join("david/", ""));
	ASSERT_STREQ("david", fs_join("david/", "/"));
	ASSERT_STREQ("david", fs_join("david/////", "////"));
}


TEST(regexp, regexp) {
	Regexp* r = regexp_create("^/[^/]+/test[0-9]$");
	ASSERT_EQ(0, regexp_match(r, "test"));
	ASSERT_EQ(0, regexp_match(r, "test1"));
	ASSERT_EQ(0, regexp_match(r, "/home/david/test1"));
	ASSERT_NE(0, regexp_match(r, "/home/test1"));
	ASSERT_EQ(0, regexp_match(r, "test/home/test110"));
	regexp_delete(r);
}

