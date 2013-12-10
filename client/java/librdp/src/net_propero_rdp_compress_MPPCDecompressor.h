/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

#include <jni.h>
/* Header for class net_propero_rdp_compress_MPPCDecompressor */

#ifndef _Included_net_propero_rdp_compress_MPPCDecompressor
#define _Included_net_propero_rdp_compress_MPPCDecompressor


JNIEXPORT jbyteArray JNICALL Java_net_propero_rdp_compress_MPPCDecompressor_nMPPCDecompress
  (JNIEnv *, jobject, jbyteArray, jint, jint);

#endif
