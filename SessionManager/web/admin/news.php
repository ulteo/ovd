<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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
require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once(dirname(__FILE__).'/includes/page_template.php');

if (! checkAuthorization('viewNews'))
	redirect('index.php');


if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action'] == 'rename' && isset($_REQUEST['id'])) {
		if (! checkAuthorization('manageNews'))
			redirect();

		if (isset($_REQUEST['news_title']) && isset($_REQUEST['news_content'])) {
			$news = Abstract_News::load($_REQUEST['id']);
			if (is_object($news)) {
				$news->title = $_REQUEST['news_title'];
				$news->content = $_REQUEST['news_content'];
				Abstract_News::save($news);
				popup_info(_('News successfully modified'));
			}
		}

		redirect();
	}

	if ($_REQUEST['action'] == 'manage' && isset($_REQUEST['id']))
		show_manage($_REQUEST['id']);
} else
	show_default();

function show_default() {
	$news = Abstract_News::load_all();

	$can_manage_news = isAuthorized('manageNews');

	page_header();

	echo '<div id="news_div">';
	echo '<h1>'._('News').'</h1>';

	echo '<div id="news_list_div">';
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	echo '<tr><th>'._('Title').'</th><th>'._('Content').'</th><th>'._('Date').'</th><th></th></tr>';

	foreach ($news as $new) {
		echo '<tr>';
		echo '<td><a href="news.php?action=manage&amp;id='.$new->id.'">'.$new->title.'</a></td>';
		echo '<td>';
		if (strlen($new->content) > 32)
			echo substr($new->content, 0, 32).'...';
		else
			echo $new->content;
		echo '</td>';
		echo '<td>'.date('r', $new->timestamp).'</td>';
		if ($can_manage_news) {
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this news?').'\');">';
			echo '<input type="hidden" name="name" value="News" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="id" value="'.$new->id.'" />';
			echo '<input type="submit" value="'._('Delete this news').'" />';
			echo '</form></td>';
		}
		echo '</tr>';
	}

	echo '</table>';
	echo '</div>';

	echo '<br />';
	echo '<h2>'._('Add news').'</h2>';

	echo '<div>';
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	if ($can_manage_news) {
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="News" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<tr><td><strong>'._('Title:').'</strong></td><td><input type="text" name="news_title" value="" /></td></tr>';
		echo '<tr><td><strong>'._('Content:').'</strong></td><td><textarea name="news_content" cols="40" rows="4"></textarea></td></tr>';
		echo '<tr><td><strong>'._('Date:').'</strong></td><td>'.date('r').'</td></tr>';
		echo '<tr><td colspan="2"><input type="submit" value="'._('Add this news').'" /></td></tr>';
		echo '</form>';
	}
	echo '</table>';
	echo '</div>';

	page_footer();

	die();
}

function show_manage($news_id_) {
	$news = Abstract_News::load($news_id_);

	if (! is_object($news))
		redirect('news.php');

	$can_manage_news = isAuthorized('manageNews');

	page_header();

	echo '<div id="news_div">';
	echo '<h1>'.$news->title.'</h1>';

	echo '<div>';
	echo '<h2>'._('Modify').'</h2>';

	echo '<table border="0" cellspacing="1" cellpadding="3">';
	if ($can_manage_news) {
		echo '<form action="news.php" method="post">';
		echo '<input type="hidden" name="action" value="rename" />';
		echo '<input type="hidden" name="id" value="'.$news->id.'" />';
		echo '<tr><td><strong>Title:</strong></td><td><input type="text" name="news_title" value="'.$news->title.'" /></td></tr>';
		echo '<tr><td><strong>Content:</strong></td><td><textarea name="news_content" cols="40" rows="4">'.$news->content.'</textarea></td></tr>';
		echo '<tr><td colspan="2"><input type="submit" value="'._('Modify').'" /></td></tr>';
		echo '</form>';
	}
	echo '</table>';

	echo '</div>';

	echo '</div>';

	page_footer();
}
