/*
 * proto.h
 *
 *  Created on: 4 avr. 2014
 *      Author: david
 */

#ifndef PROTO_H_
#define PROTO_H_

#include <vchannel/vchannel.h>

typedef unsigned __int8  __u8;
typedef unsigned __int16 __u16;
typedef unsigned __int32 __u32;


#define UKB_VERSION 1;

enum message_type {
	UKB_INIT = 0,
	UKB_CARET_POS,
	UKB_IME_STATUS,
	UKB_PUSH_TEXT,
	UKB_PUSH_COMPOSITION,
};



PACK(
struct ukb_header {
	__u16 type;
	__u16 flags;
	__u32 len;
};)


PACK(
struct ukb_init {
	__u16 version;
};)


PACK(
struct ukb_caret_pos {
	__u32 x;
	__u32 y;
};)


PACK(
struct ukb_ime_status {
	__u8 state;
};)


PACK(
struct ukb_push_text {
	__u16 text_len;
	// data
};)


PACK(
struct ukb_update_composition {
	__u16 text_len;
	// data
};)


PACK(
struct ukb_msg {
	struct ukb_header header;

	union {
		struct ukb_init  init;
		struct ukb_caret_pos  caret_pos;
		struct ukb_ime_status  ime_status;
		struct ukb_push_text  push_text;
		struct ukb_update_composition  update_composition;
	} u;
};)


#endif /* PROTO_H_ */
