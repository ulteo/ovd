/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
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

package org.ulteo.ovd.client;

import java.awt.ComponentOrientation;
import java.awt.Window;
import java.util.Locale;

public class Language {

	/** {@link Window} that can be translated */
	private interface Translater {
		/** change the language of the component */
		public void changeLanguage();
	}

	/** Ulteo custom JFrame */
	public abstract static class JFrame extends javax.swing.JFrame implements Translater {};
	
	/** Ulteo custom JDialog */
	public abstract static class JDialog extends javax.swing.JDialog implements Translater {};

	/**
	 * give a {@link Runnable} that can translate a specific object
	 * @param t the object to translate
	 * @return a {@link Runnable} component translater
	 */
	public static Runnable translate(final Translater t) {
		return new Runnable() {
			@Override
			public void run() {
				t.changeLanguage();
				((Window)t).applyComponentOrientation(ComponentOrientation.getOrientation(Locale.getDefault()));
				((Window)t).pack();
			}
		};
	}
 
	public static String[][] languageList = {
//			{"Afrikaans", "", "af"},
//			{"Albanian", "", "sq"},
			{"Arabic", "العربية", "ar"}, 
			{"Bulgarian", "Български", "bg"}, 
//			{"Belarusian", "", "be"},
			{"Chinese", "中文", "zh", "cn"},
//			{"Croatian", "", "hr"},
//			{"Czech", "Česky", "cs"},
			{"Danish", "Dansk", "da"},
			{"Dutch", "Nederlands", "nl"},
			{"English (US)", "", "en", "us"},
			{"English (GB)", "", "en", "gb"},
//			{"Estonian", "", "et"},
//			{"Faeroese", "", "fo"},
			{"Finnish", "Suomi", "fi"},
//			{"French (Belgium)", "Français (Belgique)", "fr-be"},
//			{"French (Canada)", "Français", "fr-ca"},
//			{"French (Switzerland)", "Français (Suisse)", "fr-ch"},
			{"French", "Français", "fr"},
//			{"French (Luxembourg)", "Français", "fr-lu"},
			{"German", "Deutsch", "de"},
			{"Greek", "Ελληνικά", "el"},
			{"Hebrew", "עברית", "he"},
//			{"Hindi", "", "hi"},
			{"Hungarian", "Magyar", "hu"},
			{"Icelandic", "Íslenska", "is"},
			{"Indonesian", "Bahasa Indonesia", "id"},
			{"Italian", "Italiano", "it"},
			{"Japanese", "日本語", "ja", "jp"},
//			{"Korean", "한국어", "ko"},
//			{"Latvian", "", "lv"},
//			{"Lithuanian", "Lietuvių", "lt"},
//			{"Maltese", "", "mt"},
			{"Norwegian (Bokmal)", "Norsk (Bokmål)", "nb", "no"},
//			{"Norwegian (Nynorsk)", "", "no"},
			{"Persian", "فارسی", "fa", "ir"},
			{"Polish", "Polski", "pl"},
//			{"Portuguese", "Português", "pt"},
			{"Portuguese (Brazil)", "Português (Brasil)", "pt", "br"},
			{"Romanian", "Română", "ro"},
			{"Russian", "Русский", "ru"},
			{"Slovak", "Slovenčina", "sk"},
//			{"Slovenian", "", "sl"},
//			{"Sorbian", "", "sb"},
			{"Spanish", "Español", "es"},
//			{"Spanish (Spain)", "Español (España)", "es-es"},
//			{"Swedish", "Svenska", "sv"},
//			{"Thai", "", "th"},
//			{"Tswana", "", "tn"},
//			{"Turkish", "Türkçe", "tr"},
//			{"Ukrainian", "Українська", "uk"},
//			{"Venda", "", "ve"},
//			{"Vietnamese", "Tiếng Việt", "vi"}
	};
	
	public static String[][] keymapList = {
			{"Arabic", "ar"},
			{"Danish", "da"},
			{"German", "de"},
			{"English (US)", "us", "en"},
			{"English (GB)", "gb"},
			{"Spanish", "es"},
			{"Finnish", "fi"},
			{"French", "fr", "fr"},
			{"French (Belgium)", "be"},
			{"Croatian", "hr"},
			{"Italian", "it"},
			{"Japanese", "ja"},
			{"Lithuanian", "lt"},
			{"Latvian", "lv"},
			{"Norwegian (Nynorsk)", "no"},
			{"Polish", "pl"},
			{"Portuguese", "pt"},
			{"Portuguese (Brazil)", "br"},
			{"Russian", "ru"},
			{"Slovenian", "sl"},
			{"Swedish", "sv"},
			{"Turkish", "tr"}
	};
	
	public static final String keymap_default = "us";
}
