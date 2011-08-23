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

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include <sys/types.h>
#include <string.h>

#include <libxfce4util/libxfce4util.h>
#include <libxfcegui4/libxfcegui4.h>
#include <libxfce4panel/xfce-hvbox.h>
#include <thunar-vfs/thunar-vfs.h>

#include "s-menu.h"

#define DEFAULT_ICON_SIZE 16

const gchar *categories[] =
{
    "Name",
    "GenericName",
    "Exec",
    "Icon",
};


enum
{
    REPOPULATED_SIGNAL,
    LAST_SIGNAL
};

struct _SMenuPrivate
{
    ThunarVfsMonitorHandle *handle;
    ThunarVfsMonitorHandle *common_handle;
    gchar                  *path;
    gchar                  *common_path;
    gboolean                active;
    gboolean                needs_refresh;
};

static guint sm_signals[LAST_SIGNAL] = { 0 };

static void s_menu_class_init (SMenuClass *klass);
static void s_menu_init       (SMenu      *sm);
static void s_menu_finalize   (GObject    *object);

static void empty             (SMenu      *sm);

G_DEFINE_TYPE (SMenu, s_menu, GTK_TYPE_MENU);

static void
s_menu_class_init (SMenuClass *klass)
{
    GObjectClass   *gobject_class;

    g_type_class_add_private (klass, sizeof (SMenuPrivate));

    gobject_class = G_OBJECT_CLASS (klass);
    gobject_class->finalize = s_menu_finalize;

    sm_signals[REPOPULATED_SIGNAL] = g_signal_new ("repopulated",
                                     G_TYPE_FROM_CLASS (klass),
                                     G_SIGNAL_RUN_FIRST | G_SIGNAL_ACTION,
                                     G_STRUCT_OFFSET (SMenuClass, menu_repopulated),
                                     NULL,
                                     NULL,
                                     g_cclosure_marshal_VOID__VOID,
                                     G_TYPE_NONE, 0);
}

static void
s_menu_init (SMenu *sm)
{
    sm->priv = G_TYPE_INSTANCE_GET_PRIVATE (sm, S_TYPE_MENU, SMenuPrivate);

    sm->priv->needs_refresh = FALSE;
    sm->priv->active = FALSE;
}

static void
s_menu_finalize (GObject *object)
{
    SMenu *sm = S_MENU (object);

    if (sm->priv->path)
        g_free (sm->priv->path);
    if (sm->priv->common_path)
        g_free (sm->priv->common_path);

    if (sm->priv->handle)
    {
        thunar_vfs_monitor_remove (thunar_vfs_monitor_get_default (),
                                   sm->priv->handle);
        sm->priv->handle = NULL;
    }
    if (sm->priv->common_handle)
    {
        thunar_vfs_monitor_remove (thunar_vfs_monitor_get_default (),
                                   sm->priv->common_handle);
        sm->priv->common_handle = NULL;
    }


    empty (sm);

    (*G_OBJECT_CLASS (s_menu_parent_class)->finalize) (object);
}

gint
entries_compare (gconstpointer a, gconstpointer b)
{
    const gchar *name_a = xfce_app_menu_item_get_name (XFCE_APP_MENU_ITEM (a));
    const gchar *name_b = xfce_app_menu_item_get_name (XFCE_APP_MENU_ITEM (b));

    return strcmp (name_a, name_b);
}

static void
append_to_menu (gpointer data, gpointer user_data)
{
    GtkWidget *item = (GtkWidget *) data;
    GtkWidget *menu = (GtkWidget *) user_data;

    gtk_menu_shell_append (GTK_MENU_SHELL (menu), item);
}

static void
populate (SMenu *sm)
{
    GDir *dir;
    gchar *tmp;
    GSList *items_list = NULL;
    dir = g_dir_open (sm->priv->path, 0, NULL);
    if (dir)
    {
        while ((tmp = g_strdup (g_dir_read_name (dir))) != NULL)
        {
            gchar *path = g_strconcat (sm->priv->path, "/", tmp, NULL);
            XfceDesktopEntry *de = xfce_desktop_entry_new (
                path, categories, 4);
            if (de)
            {
                GtkWidget *appitem = xfce_app_menu_item_new_from_desktop_entry (
                    de, TRUE);
                xfce_app_menu_item_set_startup_notification (
                    XFCE_APP_MENU_ITEM (appitem), TRUE);
                items_list = g_slist_append (items_list, appitem);
                gtk_widget_show (appitem);
                g_object_unref (de);
            }
            g_free (path);
            g_free (tmp);
        }
        g_dir_close (dir);
    }
    if (items_list)
    {
        items_list = g_slist_sort (items_list, (GCompareFunc) entries_compare);
        g_slist_foreach (items_list, (GFunc) append_to_menu, sm);
        g_slist_free (items_list);
    }

    /*
     * Add the common items.
     * We will not order them by app name, but by file name
     */
    if (sm->priv->common_path)
    {
        dir = g_dir_open (sm->priv->common_path, 0, NULL);
        if (dir)
        {
            GtkWidget *sep = gtk_separator_menu_item_new ();
            gtk_widget_show (sep);
            append_to_menu (sep, sm);

            while ((tmp = g_strdup (g_dir_read_name (dir))) != NULL)
            {
                gchar *path = g_strconcat (sm->priv->common_path, "/", tmp, NULL);
                XfceDesktopEntry *de = xfce_desktop_entry_new (
                    path, categories, 4);
                if (de)
                {
                    GtkWidget *appitem = xfce_app_menu_item_new_from_desktop_entry (
                        de, TRUE);
                    xfce_app_menu_item_set_startup_notification (
                        XFCE_APP_MENU_ITEM (appitem), TRUE);
                    append_to_menu (appitem, sm);
                    gtk_widget_show (appitem);
                    g_object_unref (de);
                }
                g_free (path);
                g_free (tmp);
            }
            g_dir_close (dir);
        }
    }

    g_signal_emit (G_OBJECT (sm), sm_signals[REPOPULATED_SIGNAL], 0);
}

static void
rm_list_item (gpointer data, gpointer user_data)
{
    GtkWidget *w = (GtkWidget *) data;
    gtk_widget_destroy (w);
}

static void
empty (SMenu *sm)
{
    GList *list = gtk_container_get_children (GTK_CONTAINER (sm));
    g_list_foreach (list, rm_list_item, sm);
}

static void
ddir_updated (ThunarVfsMonitor *monitor,
              ThunarVfsMonitorHandle *handle,
              ThunarVfsMonitorEvent event,
              ThunarVfsPath *handle_path,
              ThunarVfsPath *event_path,
              gpointer data)
{
    SMenu *sm = (SMenu *) data;

    if (event == THUNAR_VFS_MONITOR_EVENT_CREATED)
        return;
    thunar_vfs_monitor_wait (monitor);

    s_menu_refresh (sm);
}

static void
menu_activated (GtkWidget *menu, gpointer data)
{
    S_MENU (menu)->priv->active = TRUE;
}

static void
menu_deactivated (GtkWidget *menu, gpointer data)
{
    S_MENU (menu)->priv->active = FALSE;
}

gboolean
menu_is_activated (GtkWidget *menu)
{
    return S_MENU (menu)->priv->active;
}

/* public API */
GtkWidget *
s_menu_new (const gchar *path,
            const gchar *common_path)
{
    SMenu *sm;
    sm = g_object_new (s_menu_get_type (), NULL);
    sm->priv->path = g_strdup (path);
    if (common_path)
        sm->priv->common_path = g_strdup (common_path);

    xfce_app_menu_item_set_icon_size (DEFAULT_ICON_SIZE);
    populate (sm);
    s_menu_vfs_monitor_set_active (sm, TRUE);

    g_signal_connect (G_OBJECT (sm), "show",
        G_CALLBACK (menu_activated), NULL);
    g_signal_connect (G_OBJECT (sm), "deactivate",
        G_CALLBACK (menu_deactivated), NULL);

    return GTK_WIDGET (sm);
}

void
s_menu_refresh (SMenu *sm)
{
    empty (sm);
    populate (sm);
    if (sm->priv->handle)
        s_menu_vfs_monitor_set_active (sm, FALSE);
    s_menu_vfs_monitor_set_active (sm, TRUE);
    if (sm->priv->common_handle)
        s_menu_vfs_monitor_set_active (sm, FALSE);
    s_menu_vfs_monitor_set_active (sm, TRUE);
}

gboolean
s_menu_vfs_monitor_get_active (SMenu *sm)
{
    if (sm->priv->handle)
        return TRUE;
    return FALSE;
}

void
s_menu_vfs_monitor_set_active (SMenu *sm, gboolean active)
{
    if (!active && sm->priv->handle)
    {
        thunar_vfs_monitor_remove (thunar_vfs_monitor_get_default (),
                                   sm->priv->handle);
        sm->priv->handle = NULL;
    }

    if (active && !sm->priv->handle)
    {
        sm->priv->handle = thunar_vfs_monitor_add_directory (
                            thunar_vfs_monitor_get_default (),
                            thunar_vfs_path_new (sm->priv->path, NULL),
                            (ThunarVfsMonitorCallback)ddir_updated,
                            sm);
    }

    if (!active && sm->priv->common_handle)
    {
        thunar_vfs_monitor_remove (thunar_vfs_monitor_get_default (),
                                   sm->priv->common_handle);
        sm->priv->common_handle = NULL;
    }

    if (active && !sm->priv->common_handle)
    {
        sm->priv->common_handle = thunar_vfs_monitor_add_directory (
                                  thunar_vfs_monitor_get_default (),
                                  thunar_vfs_path_new (sm->priv->common_path, NULL),
                                  (ThunarVfsMonitorCallback)ddir_updated,
                                  sm);
    }
}
