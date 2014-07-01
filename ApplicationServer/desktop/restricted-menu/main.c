/*
 *  Copyright (c) 2008 Gauvain Pocentek <gpocentek@linutop.com>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, version 2 of the License.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include <stdlib.h>
#include <string.h>

#include <glib/gprintf.h>
#include <gtk/gtk.h>

#include <libxfce4util/libxfce4util.h>
#include <libxfcegui4/libxfcegui4.h>
#include <libxfce4panel/xfce-panel-plugin.h>
#include <libxfce4panel/xfce-hvbox.h>

#include "s-menu.h"
#include "image.h"

#define DEFAULT_NAME _("Menu")
#define DEFAULT_ICON "/usr/share/pixmaps/xfce4_xicon1.png"
#define DEFAULT_TPL "/var/spool/menus/@USER@"
#define DEFAULT_CDIR "/var/spool/menus-common"

typedef struct
{
    XfcePanelPlugin *plugin;

    GtkWidget *ebox;
    GtkWidget *hvbox;
    GtkWidget *button;
    GtkWidget *sm; /* s-menu */

    /* settings */
    const gchar *icon_normal;
    const gchar *icon_hover;
    const gchar *ddir;
    const gchar *cdir;
} RmPlugin;

static void
rm_construct (XfcePanelPlugin *plugin);

XFCE_PANEL_PLUGIN_REGISTER_EXTERNAL (rm_construct);

static void
rm_position_menu (GtkMenu *menu,
                  int *x, int *y,
                  gboolean *push_in,
                  RmPlugin *rm)
{
    XfceScreenPosition pos;
    GtkRequisition req;

    gtk_widget_size_request (GTK_WIDGET (menu), &req);
    gdk_window_get_origin (GTK_WIDGET (rm->plugin)->window, x, y);
    pos = xfce_panel_plugin_get_screen_position(rm->plugin);

    switch(pos) {
        case XFCE_SCREEN_POSITION_NW_V:
        case XFCE_SCREEN_POSITION_W:
        case XFCE_SCREEN_POSITION_SW_V:
            *x += rm->button->allocation.width;
            *y += rm->button->allocation.height - req.height;
            break;

        case XFCE_SCREEN_POSITION_NE_V:
        case XFCE_SCREEN_POSITION_E:
        case XFCE_SCREEN_POSITION_SE_V:
            *x -= req.width;
            *y += rm->button->allocation.height - req.height;
            break;

        case XFCE_SCREEN_POSITION_NW_H:
        case XFCE_SCREEN_POSITION_N:
        case XFCE_SCREEN_POSITION_NE_H:
            *y += rm->button->allocation.height;
            break;

        case XFCE_SCREEN_POSITION_SW_H:
        case XFCE_SCREEN_POSITION_S:
        case XFCE_SCREEN_POSITION_SE_H:
            *y -= req.height;
            break;

        case XFCE_SCREEN_POSITION_NONE:
        case XFCE_SCREEN_POSITION_FLOATING_H:
        case XFCE_SCREEN_POSITION_FLOATING_V:
            break;
    }

    if (*x < 0)
        *x = 0;

    if (*y < 0)
        *y = 0;

    *push_in = TRUE;
}

gchar*
str_replace (const gchar *str,
                 const gchar *pattern,
                 const gchar *replacement)
{
  const gchar *s, *p;
  GString     *result;

  g_return_val_if_fail (str != NULL, NULL);
  g_return_val_if_fail (pattern != NULL, NULL);
  g_return_val_if_fail (replacement != NULL, NULL);

  /* empty patterns are kinda useless, so we just return a copy of str */
  if (G_UNLIKELY (*pattern == '\0'))
    return g_strdup (str);

  /* allocate the result string */
  result = g_string_new (NULL);

  /* process the input string */
  while (*str != '\0')
    {
      if (G_UNLIKELY (*str == *pattern))
        {
          /* compare the pattern to the current string */
          for (p = pattern + 1, s = str + 1; *p == *s; ++s, ++p)
            if (*p == '\0' || *s == '\0')
              break;

          /* check if the pattern matches */
          if (G_LIKELY (*p == '\0'))
            {
              g_string_append (result, replacement);
              str = s;
              continue;
            }
        }

      g_string_append_c (result, *str++);
    }

  return g_string_free (result, FALSE);
}

const gchar *
build_ddir (const gchar *template)
{
    gchar *tmp;
    const gchar *ret;
    gchar uid_str[10];

    tmp = str_replace (template, "@USER@", g_get_user_name ());
    ret = g_strdup (tmp);
    g_free (tmp);

    g_sprintf (uid_str, "%d", getuid ());
    tmp = str_replace (ret, "@UID@", uid_str);
    ret = g_strdup (tmp);
    g_free (tmp);

    /* TODO: do we handle $HOME too? */

    return ret;
}

static void
set_defaults (RmPlugin *rm)
{
    rm->icon_normal = DEFAULT_ICON;
    rm->icon_hover = DEFAULT_ICON;
    rm->ddir = build_ddir (DEFAULT_TPL);
    rm->cdir = DEFAULT_CDIR;
}

void
rm_read (RmPlugin *rm)
{
    gchar *cfgfile = NULL;
    const gchar *tmp;

    cfgfile = g_build_filename (SYSCONFDIR,
                                G_DIR_SEPARATOR_S,
                                "restricted-menu.cfg",
                                NULL);

    XfceRc *rc = xfce_rc_simple_open (cfgfile, TRUE);
    g_free (cfgfile);
    if (!rc)
    {
        set_defaults (rm);
        return;
    }

    rm->icon_normal = g_strdup (xfce_rc_read_entry (rc, "icon_normal", DEFAULT_ICON));
    rm->icon_hover = g_strdup (xfce_rc_read_entry (rc, "icon_hover", DEFAULT_ICON));
    tmp = g_strdup (xfce_rc_read_entry (rc, "from_env", NULL));
    if (tmp)
    {
        rm->ddir = getenv (tmp);
    }
    if (!rm->ddir)
    {
        tmp = g_strdup (xfce_rc_read_entry (rc, "spool_tpl", DEFAULT_TPL));
        rm->ddir = build_ddir (tmp);
    }

    rm->cdir = g_strdup (xfce_rc_read_entry (rc, "spool_common", DEFAULT_CDIR));

    xfce_rc_close (rc);
}

static void
btn_toggled (GtkWidget *b, gpointer data)
{
    RmPlugin *rm = (RmPlugin *)data;

    if (!ulteo_img_btn_get_active (ULTEO_IMG_BTN (b)) && !menu_is_activated(rm->sm))
        return;

    xfce_panel_plugin_register_menu (rm->plugin, GTK_MENU (rm->sm));
    gtk_menu_popup (GTK_MENU (rm->sm), NULL, NULL,
        (GtkMenuPositionFunc) rm_position_menu, rm, 1,
        gtk_get_current_event_time ());
}

static void
menu_deactivated (GtkWidget *menu, gpointer data)
{
    RmPlugin *rm = (RmPlugin *) data;
    ulteo_img_btn_set_active (ULTEO_IMG_BTN (
        rm->button), FALSE);
}

static void
fake_popup (GtkWidget *menu, gpointer data)
{
    gtk_menu_popup (GTK_MENU (menu), NULL, NULL, NULL, NULL,
                    1, gtk_get_current_event_time ());
    gtk_menu_popdown (GTK_MENU (menu));
}

static RmPlugin *
rm_new (XfcePanelPlugin *plugin)
{
  RmPlugin       *rm;
  GtkOrientation  orientation;
  gint            size;

  rm = panel_slice_new0 (RmPlugin);
  rm->plugin = plugin;
  orientation = xfce_panel_plugin_get_orientation (plugin);
  size = xfce_panel_plugin_get_size (plugin);
  rm_read (rm);

  rm->ebox = gtk_event_box_new ();
  gtk_container_set_border_width (GTK_CONTAINER (rm->ebox), 0);
  gtk_widget_show (rm->ebox);

  rm->button = ulteo_img_btn_new (rm->icon_normal, rm->icon_hover,
                                  size, orientation);
  gtk_container_add (GTK_CONTAINER (rm->ebox), rm->button);
  gtk_widget_show (rm->button);

  rm->sm = s_menu_new (rm->ddir, rm->cdir);

  /* FIXME: first display the menu to try to get its correct size on next request */
  g_signal_connect (G_OBJECT (rm->sm), "repopulated",
    G_CALLBACK (fake_popup), NULL);
  s_menu_refresh (S_MENU (rm->sm));

  g_signal_connect (G_OBJECT (rm->button), "toggled",
    G_CALLBACK (btn_toggled), rm);
  g_signal_connect (G_OBJECT (rm->sm), "deactivate",
    G_CALLBACK (menu_deactivated), rm);

  return rm;
}

static void
rm_free (XfcePanelPlugin *plugin,
         RmPlugin        *rm)
{
  gtk_widget_destroy (rm->sm);
  gtk_widget_destroy (rm->button);
  gtk_widget_destroy (rm->ebox);

  panel_slice_free (RmPlugin, rm);
}

static void
rm_orientation_changed (XfcePanelPlugin *plugin,
                        GtkOrientation   orientation,
                        RmPlugin        *rm)
{
  gint size = xfce_panel_plugin_get_size (plugin);
  ulteo_img_btn_update((UlteoImgBtn*)rm->button, size, orientation);

  return TRUE;
}

static gboolean
rm_size_changed (XfcePanelPlugin *plugin,
                 gint             size,
                 RmPlugin         *rm)
{
  GtkOrientation orientation =
    xfce_panel_plugin_get_orientation (plugin);
    
  ulteo_img_btn_update((UlteoImgBtn*)rm->button, size, orientation);

  return TRUE;
}

static void
rm_construct (XfcePanelPlugin *plugin)
{
  RmPlugin *rm;

  xfce_textdomain(GETTEXT_PACKAGE, PACKAGE_LOCALE_DIR, "UTF-8");
  rm = rm_new (plugin);

  xfce_panel_plugin_add_action_widget (plugin, rm->button);
  gtk_container_add (GTK_CONTAINER (plugin), rm->ebox);

  g_signal_connect (G_OBJECT (plugin), "free-data",
                    G_CALLBACK (rm_free), rm);
  g_signal_connect (G_OBJECT (plugin), "size-changed",
                    G_CALLBACK (rm_size_changed), rm);
  g_signal_connect (G_OBJECT (plugin), "orientation-changed",
                    G_CALLBACK (rm_orientation_changed), rm);
}

