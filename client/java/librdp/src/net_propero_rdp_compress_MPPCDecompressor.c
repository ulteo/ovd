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

#include "net_propero_rdp_compress_MPPCDecompressor.h"
#include "mppc_dec.h"
#include <stdlib.h>

static struct rdp_mppc_dec* g_mppc_dec = NULL;

JNIEXPORT jbyteArray JNICALL Java_net_propero_rdp_compress_MPPCDecompressor_nMPPCDecompress
(JNIEnv *env, jobject self, jbyteArray compressedDatas, jint clen, jint ctype)
{
  UINT32 roff;
  UINT32 rlen;
  BYTE *buf;

  if (g_mppc_dec == NULL)
    g_mppc_dec = mppc_dec_new();

  buf = (BYTE*) malloc(clen);

  if (buf == NULL)
    return NULL;

  (*env)->GetByteArrayRegion(env, compressedDatas, 0, clen, (jbyte *)buf);

  if (decompress_rdp(g_mppc_dec, buf, clen, ctype, &roff, &rlen))
  {
    jbyteArray decompressedDatas = (*env)->NewByteArray(env, rlen);
    if (decompressedDatas == NULL) {
      free(buf);
      return NULL; /* out of memory error thrown */
    }

    (*env)->SetByteArrayRegion(env, decompressedDatas, 0, rlen, (const jbyte *)g_mppc_dec->history_buf + roff);
    free(buf);
    return decompressedDatas;
  }
  else
  {
    free(buf);
    printf("decompress_rdp() failed\n");
    return NULL;
  }
}
