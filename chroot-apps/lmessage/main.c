/**
 * Copyright (C) 2008 Gauvain Pocentek <gpocentek@linutop.com>
 * Copyright (c) 2004-2007 os-cillation e.K.
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

#include <stdio.h>
#include <string.h>

#include <gtk/gtk.h>

int type = GTK_MESSAGE_INFO;
gchar *title = NULL;

static gboolean get_type(const gchar * option_name, const gchar * value,
                         gpointer data, GError ** error);

static GOptionEntry entries[] = {
    {"type", 't', G_OPTION_FLAG_IN_MAIN, G_OPTION_ARG_CALLBACK,
     (gpointer) get_type,
     "Message type (can be 'info', 'warn' or 'error')", NULL},
    {"title", 'T', G_OPTION_FLAG_IN_MAIN, G_OPTION_ARG_STRING,
     &title,
     "Window title", NULL},
    {NULL}
};

static gboolean
get_type(const gchar * option_name, const gchar * value,
         gpointer data, GError ** error)
{
gboolean retval = TRUE;

    if (!strncmp("info", value, sizeof(value)))
        type = GTK_MESSAGE_INFO;
    else if (!strncmp("warn", value, sizeof(value)))
        type = GTK_MESSAGE_WARNING;
    else if (!strncmp("error", value, sizeof(value)))
        type = GTK_MESSAGE_ERROR;
    else {
        type = -1;
        retval = FALSE;
    }
    return retval;
}

/*
 * From libexo
 */
gchar*
exo_str_replace (const gchar *str,
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

int main(int argc, gchar ** argv)
{
    int i;

    gtk_init_with_args(&argc, &argv, "", entries, "lmessage", NULL);

    if (type == -1)
        return 1;

    if (argc == 1) {
        return 1;
    }

    gchar *message = exo_str_replace (argv[1], "\\n", "\n");

    gchar *tmp = NULL;
    for (i = 2; i < argc; i++)
    {
        tmp = g_strconcat (message, " ", argv[i], NULL);
        g_free (message);
        message = g_strdup (tmp);
        g_free (tmp);
    }

    GtkWidget *d = gtk_message_dialog_new_with_markup (NULL, 0,
                                type,
                                GTK_BUTTONS_OK,
                                "<span>%s</span>", message);
    if (title)
        gtk_window_set_title (GTK_WINDOW (d), title);

    gtk_dialog_run (GTK_DIALOG (d));
    gtk_widget_destroy (d);

    g_free (message);
    g_free (title);

    return 0;
}


