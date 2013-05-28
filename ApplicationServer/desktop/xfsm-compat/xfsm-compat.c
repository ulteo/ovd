/* $Id: xfsm-compat-gnome.c 22949 2006-08-30 11:15:03Z benny $ */
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com> 2009
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
 *
 * Most parts of this file where taken from gnome-session.
 **/

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#ifdef HAVE_STRING_H
#include <string.h>
#endif

#include "xfsm-compat-gnome.h"

int
main (int argc, char **argv) {
    if ((argc < 2) || (strcmp (argv[1], "start") == 0)) {
        xfsm_compat_gnome_startup ();
        return 0;
    }

    if ((argc > 1) && (strcmp (argv[1], "stop") == 0)) {
        xfsm_compat_gnome_shutdown ();
        return 0;
    }

    return 1;
}
