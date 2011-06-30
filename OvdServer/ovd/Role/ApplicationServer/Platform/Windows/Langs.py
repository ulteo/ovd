# -*- coding: UTF-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
#
# This program is free software; you can redistribute it and/or 
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


def getLCID(lang_):
	mappings = getMapping()
	
	if mappings.has_key(lang_):
		return mappings[lang_]
	
	code = lang_.split("_", 1)[0]
	for key in mappings.keys():
		if key.startswith(code):
			return mappings[key]
	
	return mappings["en_US"]


def getWinTimezone(tzid_):
	mappings = getTimezoneMapping()
	
	if mappings.has_key(tzid_):
		return mappings[tzid_]
	
	return mappings["Europe/London"]


def unixLocale2WindowsLocale(locale_):
	return locale_.replace("_", "-").lower()


def getMapping():
	array = {}
	array["af_ZA"] = 0x0436	# Afrikaans South Africa
	array["sq_AL"] = 0x041c	# Albanian Albania
	array["gsw_FR"] = 0x0484	# Alsatian France
	array["am_ET"] = 0x045e	# Amharic Ethiopia

	# Arabic
	array["ar_DZ"] = 0x1401	# Arabic Algeria
	array["ar_BH"] = 0x3c01	# Arabic Bahrain
	array["ar_EG"] = 0x0c01	# Arabic Egypt
	array["ar_IQ"] = 0x0801	# Arabic Iraq
	array["ar_JO"] = 0x2c01	# Arabic Jordan
	array["ar_KW"] = 0x3401	# Arabic Kuwait
	array["ar_LB"] = 0x3001	# Arabic Lebanon
	array["ar_LY"] = 0x1001	# Arabic Libya
	array["ar_MA"] = 0x1801	# Arabic Morocco
	array["ar_OM"] = 0x2001	# Arabic Oman
	array["ar_QA"] = 0x4001	# Arabic Qatar
	array["ar_SA"] = 0x0401	# Arabic Saudi Arabia
	array["ar_SY"] = 0x2801	# Arabic Syria
	array["ar_TN"] = 0x1c01	# Arabic Tunisia
	array["ar_AE"] = 0x3801	# Arabic U.A.E.
	array["ar_YE"] = 0x2401	# Arabic Yemen

	array["hy_AM"] = 0x042b	# Armenian Armenia
	array["as_IN"] = 0x044d	# Assamese India

	# Azeri
	array["az_AZ"] = 0x082c	# Azeri Azerbaijan, Cyrillic
	array["az_AZ"] = 0x042c	# Azeri Azerbaijan, Latin

	array["ba_RU"] = 0x046d	# Bashkir Russia
	array["eu_ES"] = 0x042d	# Basque Basque

	# Belarusian
	array["be_BY"] = 0x0423	# Belarusian Belarus

	# Bosnian
	array["bs_BA"] = 0x201a	# Bosnian Bosnia and Herzegovina, Cyrillic
	array["bs_BA"] = 0x141a	# Bosnian Bosnia and Herzegovina, Latin

	array["br_FR"] = 0x047e	# Breton France
	array["bg_BG"] = 0x0402	# Bulgarian Bulgaria
	array["ca_ES"] = 0x0403	# Catalan Catalan

	# Chinese
	array["zh_HK"] = 0x0c04	# Chinese Hong Kong SAR, PRC
	array["zh_MO"] = 0x1404	# Chinese Macao SAR
	array["zh_SG"] = 0x1004	# Chinese Singapore

	array["zh_Hans"] = 0x0804	# Chinese Simplified
	array["zh_Hant"] = 0x0404	# Chinese Traditional
	array["co_FR"] = 0x0483	# Corsican France

	# Croatian
	array["hr_BA"] = 0x101a	# Croatian Bosnia and Herzegovina, Latin
	array["hr_HR"] = 0x041a	# Croatian Croatia

	array["cs_CZ"] = 0x0405	# Czech Czech Republic
	array["da_DK"] = 0x0406	# Danish Denmark
	array["gbz_AF"] = 0x048c	# Dari Afghanistan
	array["dv_MV"] = 0x0465	# Divehi Maldives

	# Dutch
	array["nl_BE"] = 0x0813	# Dutch Belgium
	array["nl_NL"] = 0x0413	# Dutch Netherlands

	# English
	array["en_AU"] = 0x0c09	# English Australia
	array["en_BE"] = 0x2809	# English Belize
	array["en_CA"] = 0x1009	# English Canada
	array["en_029"] = 0x2409	# English Caribbean
	array["en_IN"] = 0x4009	# English India
	array["en_IE"] = 0x1809	# English Ireland
	array["en_IE"] = 0x1809	# English Ireland
	array["en_JM"] = 0x2009	# English Jamaica
	array["en_MY"] = 0x4409	# English Malaysia
	array["en_NZ"] = 0x1409	# English New Zealand
	array["en_PH"] = 0x3409	# English Philippines
	array["en_SG"] = 0x4809	# English Singapore
	array["en_ZA"] = 0x1c09	# English South Africa
	array["en_TT"] = 0x2c09	# English Trinidad and Tobago
	array["en_GB"] = 0x0809	# English United Kingdom
	array["en_US"] = 0x0409	# English United States
	array["en_ZW"] = 0x3009	# English Zimbabwe

	array["et_EE"] = 0x0425	# Estonian Estonia
	array["fo_FO"] = 0x0438	# Faroese Faroe Islands
	array["fil_PH"] = 0x0464	# Filipino Philippines
	array["fi_FI"] = 0x040b	# Finnish Finland

	# French
	array["fr_BE"] = 0x080c	# French Belgium
	array["fr_CA"] = 0x0c0c	# French Canada
	array["fr_FR"] = 0x040c	# French France
	array["fr_LU"] = 0x140c	# French Luxembourg
	array["fr_MC"] = 0x180c	# French Monaco
	array["fr_CH"] = 0x100c	# French Switzerland

	array["fy_NL"] = 0x0462	# Frisian Netherlands
	array["gl_ES"] = 0x0456	# Galician Spain
	array["ka_GE"] = 0x0437	# Georgian Georgia

	# German
	array["de_AT"] = 0x0c07	# German Austria
	array["de_DE"] = 0x0407	# German Germany
	array["de_LI"] = 0x1407	# German Liechtenstein
	array["de_LU"] = 0x1007	# German Luxembourg
	array["de_CH"] = 0x0807	# German Switzerland

	array["el_GR"] = 0x0408	# Greek Greece
	array["kl_GL"] = 0x046f	# Greenlandic Greenland
	array["gu_IN"] = 0x0447	# Gujarati India
	array["ha_NG"] = 0x0468	# Hausa Nigeria
	array["he_IL"] = 0x040d	# Hebrew Israel
	array["hi_IN"] = 0x0439	# Hindi India
	array["hu_HU"] = 0x040e	# Hungarian Hungary
	array["is_IS"] = 0x040f	# Icelandic Iceland
	array["ig_NG"] = 0x0470	# Igbo Nigeria
	array["id_ID"] = 0x0421	# Indonesian Indonesia

	# Inuktitut
	array["iu_CA"] = 0x085d	# Inuktitut Canada
	array["iu_CA"] = 0x045d	# Inuktitut Canada

	array["ga_IE"] = 0x083c	# Irish Ireland

	# Italian
	array["it_IT"] = 0x0410	# Italian Italy
	array["it_CH"] = 0x0810	# Italian Switzerland

	array["ja_JP"] = 0x0411	# Japanese Japan

	# Kannada
	array["kn_IN"] = 0x044b	# Kannada India

	array["kk_KZ"] = 0x043f	# Kazakh Kazakhstan
	array["kh_KH"] = 0x0453	# Khmer Cambodia
	array["qut_GT"] = 0x0486	# K'iche Guatemala
	array["rw_RW"] = 0x0487	# Kinyarwanda Rwanda
	array["kok_IN"] = 0x0457	# Konkani India
	array["ko_KR"] = 0x0412	# Korean Korea
	array["ky_KG"] = 0x0440	# Kyrgyz Kyrgyzstan
	array["lo_LA"] = 0x0454	# Lao Lao PDR
	array["lv_LV"] = 0x0426	# Latvian Latvia
	array["lt_LT"] = 0x0427	# Lithuanian Lithuanian
	array["dsb_DE"] = 0x082e	# Lower Sorbian Germany
	array["lb_LU"] = 0x046e	# Luxembourgish Luxembourg
	array["mk_MK"] = 0x042f	# Macedonian Macedonia, FYROM

	# Malay
	array["ms_BN"] = 0x083e	# Malay Brunei Darassalam
	array["ms_MY"] = 0x043e	# Malay Malaysia

	array["ml_IN"] = 0x044c	# Malayalam India
	array["mt_MT"] = 0x043a	# Maltese Malta
	array["mi_NZ"] = 0x0481	# Maori New Zealand
	array["arn_CL"] = 0x047a	# Mapudungun Chile
	array["mr_IN"] = 0x044e	# Marathi India
	array["moh_CA"] = 0x047c	# Mohawk Canada

	# Mongolian
	array["mn_MN"] = 0x0450	# Mongolian Mongolia, Cyrillic
	array["mn_CN"] = 0x0850	# Mongolian Mongolia

	# Nepali
	array["ne_NP"] = 0x0461	# Nepali Nepal

	# Norwegian
	array["no_NO"] = 0x0414	# Norwegian Bokmål, Norway
	array["no_NO"] = 0x0814	# Norwegian Nynorsk, Norway

	array["oc_FR"] = 0x0482	# Occitan France
	array["or_IN"] = 0x0448	# Oriya India
	array["ps_AF"] = 0x0463	# Pashto Afghanistan
	array["fa_IR"] = 0x0429	# Persian Iran
	array["pl_PL"] = 0x0415	# Polish Poland

	# Portuguese
	array["pt_BR"] = 0x0416	# Portuguese Brazil
	array["pt_PT"] = 0x0816	# Portuguese Portugal
	array["pt_PT"] = 0x0816	# Portuguese Portugal

	array["pa_IN"] = 0x0446	# Punjabi India

	# Quechua
	array["quz_BO"] = 0x046b	# Quechua Bolivia
	array["quz_EC"] = 0x086b	# Quechua Ecuador
	array["quz_PE"] = 0x0c6b	# Quechua Peru

	array["ro_RO"] = 0x0418	# Romanian Romania
	array["rm_CH"] = 0x0417	# Romansh Switzerland
	array["ru_RU"] = 0x0419	# Russian Russia

	# Sami
	array["se_FI"] = 0x243b	# Sami Inari, Finland
	array["se_NO"] = 0x103b	# Sami Lule, Norway
	array["se_SE"] = 0x143b	# Sami Lule, Sweden
	array["se_FI"] = 0x0c3b	# Sami Northern, Finland
	array["se_NO"] = 0x043b	# Sami Northern, Norway
	array["se_SE"] = 0x083b	# Sami Northern, Sweden
	array["se_FI"] = 0x203b	# Sami Skolt, Finland
	array["se_NO"] = 0x183b	# Sami Southern, Norway
	array["se_SE"] = 0x1c3b	# Sami Southern, Sweden

	# Sanskrit
	array["sa_IN"] = 0x044f	# Sanskrit India
	array["sa_BA"] = 0x181a	# Sanskrit Bosnia and Herzegovina, Latin
	array["sa_CS"] = 0x0c1a	# Sanskrit Serbia, Cyrillic
	array["sa_CS"] = 0x081a	# Sanskrit Serbia, Latin

	array["ns_ZA"] = 0x046c	# Sesotho sa Leboa/Northern Sotho South Africa
	array["tn_ZA"] = 0x0432	# Setswana/Tswana South Africa
	array["si_LK"] = 0x045b	# Sinhala Sri Lanka
	array["sk_SK"] = 0x041b	# Slovak Slovakia
	array["sl_SI"] = 0x0424	# Slovenian Slovenia

	# Spanish
	array["es_AR"] = 0x2c0a	# Spanish Argentina
	array["es_BO"] = 0x400a	# Spanish Bolivia
	array["es_CL"] = 0x340a	# Spanish Chile
	array["es_CO"] = 0x240a	# Spanish Colombia
	array["es_CR"] = 0x140a	# Spanish Costa Rica
	array["es_DO"] = 0x1c0a	# Spanish Dominican Republic
	array["es_EC"] = 0x300a	# Spanish Ecuador
	array["es_SV"] = 0x440a	# Spanish El Salvador
	array["es_GT"] = 0x100a	# Spanish Guatemala
	array["es_HN"] = 0x480a	# Spanish Honduras
	array["es_MX"] = 0x080a	# Spanish Mexico
	array["es_NI"] = 0x4c0a	# Spanish Nicaragua
	array["es_PA"] = 0x180a	# Spanish Panama
	array["es_PY"] = 0x3c0a	# Spanish Paraguay
	array["es_PE"] = 0x280a	# Spanish Peru
	array["es_PR"] = 0x500a	# Spanish Puerto Rico
	array["es_ES"] = 0x0c0a	# Spanish Spain
	array["es_UY"] = 0x380a	# Spanish Uruguay
	array["es_VE"] = 0x200a	# Spanish Venezuela

	array["sw_KE"] = 0x0441	# Swahili Kenya

	# Swedish
	array["sv_FI"] = 0x081d	# Swedish Finland
	array["sv_SE"] = 0x041d	# Swedish Sweden
	array["sv_SE"] = 0x041d	# Swedish Sweden

	array["syr_SY"] = 0x045a	# Syriac Syria
	array["tg_TJ"] = 0x0428	# Tajik Tajikistan
	array["tmz_DZ"] = 0x085f	# Tamazight Algeria, Latin
	array["ta_IN"] = 0x0449	# Tamil India
	array["tt_RU"] = 0x0444	# Tatar Russia
	array["te_IN"] = 0x044a	# Telugu India
	array["th_TH"] = 0x041e	# Thai Thailand
	array["bo_CN"] = 0x0451	# Tibetan PRC
	array["tr_TR"] = 0x041f	# Turkish Turkey
	array["tk_TM"] = 0x0442	# Turkmen Turkmenistan
	array["ug_CN"] = 0x0480	# Uighur PRC
	array["uk_UA"] = 0x0422	# Ukrainian Ukraine

	# Upper Sorbian
	array["wen_DE"] = 0x042e	# Upper Sorbian Germany
	array["wen_PK"] = 0x0420	# Upper Sorbian Pakistan

	# Uzbek
	array["uz_UZ"] = 0x0843	# Uzbek Uzbekistan, Cyrillic
	array["uz_UZ"] = 0x0443	# Uzbek Uzbekistan, Latin

	array["vi_VN"] = 0x042a	# Vietnamese Vietnam
	array["cy_GB"] = 0x0452	# Welsh United Kingdom
	array["wo_SN"] = 0x0488	# Wolof Senegal
	array["xh_ZA"] = 0x0434	# Xhosa/isiXhosa South Africa
	array["sah_RU"] = 0x0485	# Yakut Russia
	array["ii_CN"] = 0x0478	# Yi PRC
	array["yo_NG"] = 0x046a	# Yoruba Nigeria
	array["zu_ZA"] = 0x0435	# Zulu/isiZulu South Afric
	
	return array


def getTimezoneMapping():
	# Windows tzid matching, ref http://unicode.org/repos/cldr-tmp/trunk/diff/supplemental/zone_tzid.html
	
	array = {}
	array["Africa/Cairo"] = "Egypt Standard Time"
	array["Africa/Casablanca"] = "Greenwich Standard Time"
	array["Africa/Johannesburg"] = "South Africa Standard Time"
	array["Africa/Lagos"] = "W. Central Africa Standard Time"
	array["Africa/Nairobi"] = "E. Africa Standard Time"
	array["Africa/Windhoek"] = "Namibia Standard Time"
	array["America/Anchorage"] = "Alaskan Standard Time"
	array["America/Bogota"] = "SA Pacific Standard Time"
	array["America/Buenos_Aires"] = "Argentina Standard Time"
	array["America/Caracas"] = "Venezuela Standard Time"
	array["America/Chicago"] = "Central Standard Time"
	array["America/Chihuahua"] = "Mexico Standard Time 2"
	array["America/Chihuahua"] = "Mountain Standard Time (Mexico)"
	array["America/Denver"] = "Mountain Standard Time"
	array["America/Godthab"] = "Greenland Standard Time"
	array["America/Guatemala"] = "Central America Standard Time"
	array["America/Halifax"] = "Atlantic Standard Time"
	array["America/La_Paz"] = "SA Western Standard Time"
	array["America/Los_Angeles"] = "Pacific Standard Time"
	array["America/Mexico_City"] = "Central Standard Time (Mexico)"
	array["America/Mexico_City"] = "Mexico Standard Time"
	array["America/Montevideo"] = "Montevideo Standard Time"
	array["America/New_York"] = "Eastern Standard Time"
	array["America/Phoenix"] = "US Mountain Standard Time"
	array["America/Regina"] = "Canada Central Standard Time"
	array["America/Santiago"] = "Pacific SA Standard Time"
	array["America/Sao_Paulo"] = "E. South America Standard Time"
	array["America/St_Johns"] = "Newfoundland Standard Time"
	array["America/Tijuana"] = "Pacific Standard Time (Mexico)"
	array["Asia/Amman"] = "Jordan Standard Time"
	array["Asia/Baghdad"] = "Arabic Standard Time"
	array["Asia/Baku"] = "Azerbaijan Standard Time"
	array["Asia/Bangkok"] = "SE Asia Standard Time"
	array["Asia/Beirut"] = "Middle East Standard Time"
	array["Asia/Calcutta"] = "India Standard Time"
	array["Asia/Colombo"] = "Sri Lanka Standard Time"
	array["Asia/Dhaka"] = "Central Asia Standard Time"
	array["Asia/Dubai"] = "Arabian Standard Time"
	array["Asia/Irkutsk"] = "North Asia East Standard Time"
	array["Asia/Jerusalem"] = "Israel Standard Time"
	array["Asia/Kabul"] = "Afghanistan Standard Time"
	array["Asia/Karachi"] = "West Asia Standard Time"
	array["Asia/Katmandu"] = "Nepal Standard Time"
	array["Asia/Krasnoyarsk"] = "North Asia Standard Time"
	array["Asia/Novosibirsk"] = "N. Central Asia Standard Time"
	array["Asia/Rangoon"] = "Myanmar Standard Time"
	array["Asia/Riyadh"] = "Arab Standard Time"
	array["Asia/Seoul"] = "Korea Standard Time"
	array["Asia/Shanghai"] = "China Standard Time"
	array["Asia/Singapore"] = "Singapore Standard Time"
	array["Asia/Taipei"] = "Taipei Standard Time"
	array["Asia/Tbilisi"] = "Caucasus Standard Time"
	array["Asia/Tehran"] = "Iran Standard Time"
	array["Asia/Tokyo"] = "Tokyo Standard Time"
	array["Asia/Vladivostok"] = "Vladivostok Standard Time"
	array["Asia/Yakutsk"] = "Yakutsk Standard Time"
	array["Asia/Yekaterinburg"] = "Ekaterinburg Standard Time"
	array["Asia/Yerevan"] = "Armenian Standard Time"
	array["Atlantic/Azores"] = "Azores Standard Time"
	array["Atlantic/Cape_Verde"] = "Cape Verde Standard Time"
	array["Atlantic/South_Georgia"] = "Mid-Atlantic Standard Time"
	array["Australia/Adelaide"] = "Cen. Australia Standard Time"
	array["Australia/Brisbane"] = "E. Australia Standard Time"
	array["Australia/Darwin"] = "AUS Central Standard Time"
	array["Australia/Hobart"] = "Tasmania Standard Time"
	array["Australia/Perth"] = "W. Australia Standard Time"
	array["Australia/Sydney"] = "AUS Eastern Standard Time"
	array["Etc/GMT+12"] = "Dateline Standard Time"
	array["Etc/GMT-3"] = "Georgian Standard Time"
	array["Etc/GMT+3"] = "SA Eastern Standard Time"
	array["Etc/GMT+4"] = "Central Brazilian Standard Time"
	array["Etc/GMT+5"] = "US Eastern Standard Time"
	array["Europe/Berlin"] = "W. Europe Standard Time"
	array["Europe/Budapest"] = "Central Europe Standard Time"
	array["Europe/Istanbul"] = "GTB Standard Time"
	array["Europe/Kiev"] = "FLE Standard Time"
	array["Europe/London"] = "GMT Standard Time"
	array["Europe/Minsk"] = "E. Europe Standard Time"
	array["Europe/Moscow"] = "Russian Standard Time"
	array["Europe/Paris"] = "Romance Standard Time"
	array["Europe/Warsaw"] = "Central European Standard Time"
	array["Pacific/Apia"] = "Samoa Standard Time"
	array["Pacific/Auckland"] = "New Zealand Standard Time"
	array["Pacific/Fiji"] = "Fiji Standard Time"
	array["Pacific/Guadalcanal"] = "Central Pacific Standard Time"
	array["Pacific/Honolulu"] = "Hawaiian Standard Time"
	array["Pacific/Port_Moresby"] = "West Pacific Standard Time"
	array["Pacific/Tongatapu"] = "Tonga Standard Time"
	
	return array
