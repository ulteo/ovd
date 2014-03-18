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

#include <time.h>
#include <stdlib.h>
#include <string.h>
#include <jni.h>

#include "net_propero_rdp_Bitmap.h"
#include "bitmap.h"
#include "jpeg.h"
#include "color.h"

/*
 * Class:     net_propero_rdp_Bitmap
 * Method:    nRLEDecompress
 * Signature: (III[BI)[I
 */
JNIEXPORT jintArray JNICALL Java_net_propero_rdp_Bitmap_nRLEDecompress
(JNIEnv *env, jobject thiz, jint width, jint height, jint size, jbyteArray compressed_pixel, jint Bpp)
{
  jboolean isCopy;
  jbyte* buf;
  jintArray rgb_pixel;
  BYTE *decompressed_pixel;
  int decompressed_size;
  Bpp *= 8;
  decompressed_size = width * height * (Bpp + 7) / 8;
  rgb_pixel = (*env)->NewIntArray(env, width * height);
  if (rgb_pixel == NULL) {
    return NULL; /* out of memory error thrown */
  }

  buf = (*env)->GetByteArrayElements(env, compressed_pixel, &isCopy);
  if (!buf)
    return NULL;

  decompressed_pixel = (BYTE *)malloc(decompressed_size);
  if (bitmap_decompress((BYTE *)buf, decompressed_pixel, width, height, size, Bpp, Bpp))
  {
    // convert to the 32- rgb
    CLRCONV clrconv = {0};
    BYTE* rgbs;

    // may need to adjust these values on linux or other different platforms
    clrconv.invert = 0;
    clrconv.alpha = 0;

    rgbs = (BYTE *)malloc(width * height * 4);
    freerdp_image_convert(decompressed_pixel, rgbs, width, height, Bpp, 32, &clrconv);
    (*env)->SetIntArrayRegion(env, rgb_pixel, 0, width * height, (jint *)rgbs);
    free(rgbs);
  }
  else
  {
    printf("Bitmap Decompression Failed.\n");
  }

  (*env)->ReleaseByteArrayElements(env, compressed_pixel, buf, 0);
  (*env)->DeleteLocalRef(env, compressed_pixel);
  free(decompressed_pixel);
  return rgb_pixel;
}

JNIEXPORT jintArray JNICALL Java_net_propero_rdp_Bitmap_nJpegDecompress
(JNIEnv *env, jclass clazz, jbyteArray jpeg_pixel, jint bufsize, jint width, jint height)
{
  jboolean isCopy;
  jbyte* buf;
  BYTE *output;
  jintArray rgb_pixel;

  rgb_pixel = (*env)->NewIntArray(env, (width * height * 4));

  if (rgb_pixel == NULL) {
    return NULL; /* out of memory error thrown */
  }

  buf = (*env)->GetByteArrayElements(env, jpeg_pixel, &isCopy);
  if (!buf)
    return NULL;

  output = (BYTE *)malloc(width * height * 4); // ARGB format
  if (output)
  {
    jpeg_decompress((BYTE *)buf, output, width, height, bufsize);
    (*env)->SetIntArrayRegion(env, rgb_pixel, 0, width * height, (jint *)output);

  }
  else
  {
    (*env)->DeleteLocalRef(env, rgb_pixel);
    rgb_pixel = NULL;
  }

  (*env)->ReleaseByteArrayElements(env, jpeg_pixel, buf, 0);
  (*env)->DeleteLocalRef(env, jpeg_pixel);
  free(output);

  return rgb_pixel;
}
