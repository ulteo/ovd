/*
 * Copyright (C) 2008 Gauvain Pocentek <gpocentek@linutop.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 2 of the License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 */

#ifndef __S_MENU_H__
#define __S_MENU_H__

#include <gtk/gtk.h>

#define S_TYPE_MENU          (s_menu_get_type ())
#define S_MENU(obj)          (G_TYPE_CHECK_INSTANCE_CAST ((obj), S_TYPE_MENU, SMenu))
#define S_MENU_CLASS(klass)  (G_TYPE_CHECK_CLASS_CAST ((klass), S_TYPE_MENU, SMenuClass))
#define S_IS_MENU(obj)       (G_TYPE_CHECK_INSTANCE_TYPE ((obj), S_TYPE_MENU))
#define S_IS_MENU_CLASS(klass)   (G_TYPE_CHECK_CLASS_TYPE ((obj), S_TYPE_MENU))

typedef struct _SMenuClass   SMenuClass;
typedef struct _SMenu        SMenu;
typedef struct _SMenuPrivate SMenuPrivate;

struct _SMenuClass
{
    GtkMenuClass __parent__;

    /* signals */
    void (*menu_repopulated) (SMenu *me);
};

struct _SMenu
{
    GtkMenu menu;

    /*< private >*/
    SMenuPrivate *priv;
};

GType      s_menu_get_type               (void) G_GNUC_CONST;
GtkWidget *s_menu_new                    (const gchar *path,
                                          const gchar *common_path);
void       s_menu_refresh                (SMenu *sm);
gboolean   s_menu_vfs_monitor_get_active (SMenu *sm);
void       s_menu_vfs_monitor_set_active (SMenu *sm,
                                          gboolean active);
gboolean menu_is_activated (GtkWidget *menu);

#endif /* __S_MENU_H__ */
