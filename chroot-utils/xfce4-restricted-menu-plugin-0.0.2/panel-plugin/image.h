/**
 * Copyright (C) 2008 Gauvain Pocentek <gpocentek@linutop.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

G_END_DECLS

#endif /* !__ULTEO_IMG_BTN_H__ */
