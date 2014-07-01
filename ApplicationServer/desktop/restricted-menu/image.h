/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gpocentek@linutop.com> 2008
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
 */

#ifndef __ULTEO_IMG_BTN_H__
#define __ULTEO_IMG_BTN_H__

#include <gtk/gtk.h>

G_BEGIN_DECLS

typedef struct _UlteoImgBtnPrivate UlteoImgBtnPrivate;
typedef struct _UlteoImgBtnClass   UlteoImgBtnClass;
typedef struct _UlteoImgBtn        UlteoImgBtn;

#define ULTEO_TYPE_IMG_BTN             (ulteo_img_btn_get_type ())
#define ULTEO_IMG_BTN(obj)             (G_TYPE_CHECK_INSTANCE_CAST ((obj), ULTEO_TYPE_IMG_BTN, UlteoImgBtn))
#define ULTEO_IMG_BTN_CLASS(klass)     (G_TYPE_CHECK_CLASS_CAST ((klass), ULTEO_TYPE_IMG_BTN, UlteoImgBtnClass))
#define ULTEO_IS_IMG_BTN(obj)          (G_TYPE_CHECK_INSTANCE_TYPE ((obj), ULTEO_TYPE_IMG_BTN))
#define ULTEO_IS_IMG_BTN_CLASS(klass)  (G_TYPE_CHECK_CLASS_TYPE ((klass), ULTEO_TYPE_IMG_BTN))
#define ULTEO_IMG_BTN_GET_CLASS(obj)   (G_TYPE_INSTANCE_GET_CLASS ((obj), ULTEO_TYPE_IMG_BTN, UlteoImgBtnClass))

struct _UlteoImgBtnClass
{
  GtkEventBoxClass __parent__;

  /* toggled */
  void (*toggled) (UlteoImgBtn *img);
};

struct _UlteoImgBtn
{
  GtkEventBox __parent__;

  /*< private >*/
  UlteoImgBtnPrivate *priv;
};

GType      ulteo_img_btn_get_type   (void) G_GNUC_CONST G_GNUC_INTERNAL;

GtkWidget *ulteo_img_btn_new        (const gchar   *normal_file,
                                     const gchar   *hover_file,
                                     gint           size,
                                     GtkOrientation orientation) G_GNUC_MALLOC G_GNUC_INTERNAL;

gboolean   ulteo_img_btn_get_active (UlteoImgBtn *img);
void       ulteo_img_btn_set_active (UlteoImgBtn *img,
                                     gboolean active);
void ulteo_img_btn_update (UlteoImgBtn *img, 
                           gint           size,
                           GtkOrientation orientation);

G_END_DECLS

#endif /* !__ULTEO_IMG_BTN_H__ */
