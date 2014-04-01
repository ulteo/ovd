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
#include "stream.h"
#include <stdio.h>
#include <jpeglib.h>
#include <jerror.h>

#include "color.h"
#include "jpeg.h"

struct mydata_decomp
{
  char* data;
  int data_bytes;
};

/*****************************************************************************/
static void my_init_source(j_decompress_ptr cinfo)
{
}

/*****************************************************************************/
static BOOL my_fill_input_buffer(j_decompress_ptr cinfo)
{
  struct mydata_decomp* md;

  md = (struct mydata_decomp*)(cinfo->client_data);
  cinfo->src->next_input_byte = (unsigned char*)(md->data);
  cinfo->src->bytes_in_buffer = md->data_bytes;
  return TRUE;
}

/*****************************************************************************/
static void my_skip_input_data(j_decompress_ptr cinfo, long num_bytes)
{
}

/*****************************************************************************/
static BOOL my_resync_to_restart(j_decompress_ptr cinfo, int desired)
{
  return TRUE;
}

/*****************************************************************************/
static void my_term_source(j_decompress_ptr cinfo)
{
}

static void jpeg_print_cinfo(struct jpeg_decompress_struct cinfo) {
  printf("Values of attributes of jpeg_decompress_struct: \n");
  printf("  image_width = \t%d\n", cinfo.image_width );
  printf("  image_height = \t%d\n", cinfo.image_height );
  printf("  num_components = \t%d\n", cinfo.num_components );
  printf("  jpeg_color_space = \t%d\n", cinfo.jpeg_color_space );
  printf("  out_color_space = \t%d\n", cinfo.out_color_space );
  printf("  scale_num = \t%d\n", cinfo.scale_num );
  printf("  scale_denom = \t%d\n", cinfo.scale_denom );
  printf("  buffered_image = \t%d\n", cinfo.buffered_image );
  printf("  raw_data_out = \t%d\n", cinfo.raw_data_out );
  printf("  quantize_colors = \t%d\n", cinfo.quantize_colors );
  printf("  desired_number_of_colors = \t%d\n", cinfo.desired_number_of_colors );
  printf("  output_width = \t%d\n", cinfo.output_width );
  printf("  output_height = \t%d\n", cinfo.output_height );
  printf("  out_color_components = \t%d\n", cinfo.out_color_components );
  printf("  output_components = \t%d\n", cinfo.output_components );
  printf("  rec_outbuf_height = \t%d\n", cinfo.rec_outbuf_height );
  printf("  actual_number_of_colors = \t%d\n", cinfo.actual_number_of_colors );
}

/*****************************************************************************/
static int
do_decompress(char* comp_data, int comp_data_bytes, int width, int height, char* decomp_data)
{

  struct jpeg_decompress_struct cinfo;
  struct jpeg_error_mgr jerr;
  struct jpeg_source_mgr src_mgr;
  struct mydata_decomp md;
  int bmp_size;
  int i;
  int pixel_size, row_stride;
  unsigned char *bmp_buffer;
  JSAMPROW row_pointer[1];

  memset(&cinfo, 0, sizeof(cinfo));
  cinfo.err = jpeg_std_error(&jerr);
  jpeg_create_decompress(&cinfo);

  memset(&src_mgr, 0, sizeof(src_mgr));
  cinfo.src = &src_mgr;
  src_mgr.init_source = my_init_source;
  src_mgr.fill_input_buffer = my_fill_input_buffer;
  src_mgr.skip_input_data = my_skip_input_data;
  src_mgr.resync_to_restart = my_resync_to_restart;
  src_mgr.term_source = my_term_source;

  memset(&md, 0, sizeof(md));
  md.data = comp_data;
  md.data_bytes = comp_data_bytes;
  cinfo.client_data = &md;

  jpeg_read_header(&cinfo, 1);

  jpeg_start_decompress(&cinfo);
  bmp_size = width * height * cinfo.output_components;
  bmp_buffer = (unsigned char*) malloc(bmp_size);

  row_stride = width * cinfo.output_components;

  while (cinfo.output_scanline < cinfo.output_height) {
    unsigned char *buffer_array[1];
    buffer_array[0] = bmp_buffer + (cinfo.output_scanline) * row_stride;
    jpeg_read_scanlines(&cinfo, buffer_array, 1);
    for (i = 0 ; i < width ; i++){
      *(decomp_data + 2) = *buffer_array[0]++;
      *(decomp_data + 1) = *buffer_array[0]++;
      *decomp_data = *buffer_array[0]++;
      decomp_data+=4;
    }
  }

  jpeg_finish_decompress(&cinfo);
  jpeg_destroy_decompress(&cinfo);
  free(bmp_buffer);
  return FALSE;
}

BOOL jpeg_decompress(BYTE* input, BYTE* output, int width, int height, int size)
{
  if (do_decompress((char*)input, size, width, height,(char*)output) != 0)
  {
    return FALSE;
  }
  return TRUE;
}

