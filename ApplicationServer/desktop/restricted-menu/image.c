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
 **/

#include "image.h"

enum {
    TOGGLED,
    LAST_SIGNAL
};

struct _UlteoImgBtnPrivate {
    GtkWidget *image;
    GdkPixbuf *normal;
    GdkPixbuf *hover;

    gchar   *normal_file;
    gchar   *hover_file;
    gboolean active;
};

static guint signals[LAST_SIGNAL] = { 0 };

static void ulteo_img_btn_class_init (UlteoImgBtnClass *class);
static void ulteo_img_btn_init       (UlteoImgBtn      *img);
static void ulteo_img_btn_finalize   (GObject          *object);

G_DEFINE_TYPE (UlteoImgBtn, ulteo_img_btn, GTK_TYPE_EVENT_BOX);

static void
ulteo_img_btn_class_init (UlteoImgBtnClass *class)
{
    g_type_class_add_private (class, sizeof (UlteoImgBtnPrivate));

    GObjectClass   *gobject_class;
    gobject_class = G_OBJECT_CLASS (class);
    gobject_class->finalize = ulteo_img_btn_finalize;

    signals [TOGGLED] = g_signal_new ("toggled",
                       G_TYPE_FROM_CLASS (class),
                       G_SIGNAL_RUN_FIRST | G_SIGNAL_ACTION,
                       G_STRUCT_OFFSET (UlteoImgBtnClass, toggled),
                       NULL,
                       NULL,
                       g_cclosure_marshal_VOID__VOID,
                       G_TYPE_NONE,
                       0);
}

static void
ulteo_img_btn_init (UlteoImgBtn *img)
{
    img->priv = G_TYPE_INSTANCE_GET_PRIVATE (img, ULTEO_TYPE_IMG_BTN,
                                             UlteoImgBtnPrivate);
    img->priv->active = FALSE;
}

static void
ulteo_img_btn_finalize (GObject *object)
{
    UlteoImgBtn *img = ULTEO_IMG_BTN (object);

    g_object_unref (img->priv->normal);
    g_object_unref (img->priv->hover);

    (*G_OBJECT_CLASS (ulteo_img_btn_parent_class)->finalize) (object);
}

/* callbacks */
static gboolean
on_enter_notify (GtkWidget      *widget,
                 GdkEventMotion *event,
                 gpointer        data)
{
    UlteoImgBtn *img = (UlteoImgBtn *) data;
    if (img->priv->active)
        return FALSE;
    gtk_image_set_from_pixbuf (GTK_IMAGE (img->priv->image), img->priv->hover);

    return FALSE;
}

static gboolean
on_leave_notify (GtkWidget      *widget,
                 GdkEventMotion *event,
                 gpointer        data)
{
    UlteoImgBtn *img = (UlteoImgBtn *) data;
    if (img->priv->active)
        return FALSE;
    gtk_image_set_from_pixbuf (GTK_IMAGE (img->priv->image), img->priv->normal);

    return FALSE;
}

static gboolean
on_button_press (GtkWidget      *widget,
                 GdkEventButton *event,
                 gpointer        data)
{
    if (event->button != 1)
        return FALSE;
    UlteoImgBtn *img = (UlteoImgBtn *) data;
    img->priv->active = img->priv->active ? FALSE : TRUE;
    g_signal_emit (G_OBJECT (img), signals[TOGGLED], 0);

    return FALSE;
}

/* helpers */
static GdkPixbuf *
get_pxb_scaled (const gchar   *file,
                gint           size,
                GtkOrientation orientation)
{
    GdkPixbuf *px, *tmp;
    int w, h;
    gdouble aspect;

    px = gdk_pixbuf_new_from_file (file, NULL);
    w = gdk_pixbuf_get_width (px);
    h = gdk_pixbuf_get_height (px);
    aspect = (gdouble) w / h;

    w = orientation == GTK_ORIENTATION_HORIZONTAL ? size*aspect : size;
    h = orientation == GTK_ORIENTATION_VERTICAL ? size*aspect : size;
    
    if (orientation == GTK_ORIENTATION_VERTICAL) {
        tmp = gdk_pixbuf_rotate_simple (px, 270);
        g_object_unref (G_OBJECT (px));
        px = tmp;
    }

    tmp = gdk_pixbuf_scale_simple (px, w, h, GDK_INTERP_BILINEAR);
    g_object_unref (G_OBJECT (px));
    px = tmp;

    return px;
}

void ulteo_img_btn_update (UlteoImgBtn *img, 
                           gint           size,
                           GtkOrientation orientation)
{
    img->priv->normal = get_pxb_scaled (img->priv->normal_file, size, orientation);
    img->priv->hover = get_pxb_scaled (img->priv->hover_file ? img->priv->hover_file : img->priv->normal_file, size, orientation);

    gtk_image_set_from_pixbuf (GTK_IMAGE (img->priv->image), img->priv->normal);
}

/* public api */
GtkWidget *
ulteo_img_btn_new (const gchar   *normal_file,
                   const gchar   *hover_file,
                   gint           size,
                   GtkOrientation orientation)
{
    g_return_val_if_fail (normal_file != NULL, NULL);
    UlteoImgBtn *img;

    img = g_object_new (ulteo_img_btn_get_type (), NULL);

    img->priv->normal_file = g_strdup(normal_file);
    img->priv->hover_file = g_strdup(hover_file);

    /* connect to mouse events */
    gtk_widget_set_events (GTK_WIDGET (img), GDK_ENTER_NOTIFY_MASK);
    g_signal_connect (G_OBJECT (img), "enter-notify-event",
        G_CALLBACK (on_enter_notify), (gpointer) img);

    gtk_widget_set_events (GTK_WIDGET (img), GDK_LEAVE_NOTIFY_MASK);
    g_signal_connect (G_OBJECT (img), "leave-notify-event",
        G_CALLBACK (on_leave_notify), (gpointer) img);

    gtk_widget_set_events (GTK_WIDGET (img), GDK_BUTTON_MOTION_MASK);
    g_signal_connect (G_OBJECT (img), "button-press-event",
        G_CALLBACK (on_button_press), (gpointer) img);

    img->priv->image = gtk_image_new ();

    /* TODO: error checking */
    img->priv->normal = get_pxb_scaled (normal_file, size, orientation);
    img->priv->hover = get_pxb_scaled (hover_file ? hover_file : normal_file,
                                       size, orientation);

    gtk_image_set_from_pixbuf (GTK_IMAGE (img->priv->image), img->priv->normal);
    gtk_container_add (GTK_CONTAINER (img), img->priv->image);

    gtk_widget_show (img->priv->image);

    return GTK_WIDGET (img);
}

gboolean
ulteo_img_btn_get_active (UlteoImgBtn *img)
{
    return img->priv->active;
}

void
ulteo_img_btn_set_active (UlteoImgBtn *img,
                          gboolean     active)
{
    img->priv->active = active;
    g_signal_emit (G_OBJECT (img), signals[TOGGLED], 0);
    if (!active)
        gtk_image_set_from_pixbuf (GTK_IMAGE (img->priv->image), img->priv->normal);
}
