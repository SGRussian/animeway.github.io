<?php
/*
=====================================================
 DataLife Engine - by SoftNews Media Group 
-----------------------------------------------------
 http://dle-news.ru/
-----------------------------------------------------
 Copyright (c) 2004-2017 SoftNews Media Group
=====================================================
 Данный код защищен авторскими правами
=====================================================
 Файл: quote.php
-----------------------------------------------------
 Назначение: цитирование комментариев
=====================================================
*/

@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );

define( 'DATALIFEENGINE', true );
define( 'ROOT_DIR', substr( dirname(  __FILE__ ), 0, -12 ) );
define( 'ENGINE_DIR', ROOT_DIR . '/engine' );

include ENGINE_DIR . '/data/config.php';

date_default_timezone_set ( $config['date_adjust'] );

require_once ENGINE_DIR . '/classes/mysql.php';
require_once ENGINE_DIR . '/data/dbconfig.php';
require_once ENGINE_DIR . '/modules/functions.php';
require_once ENGINE_DIR . '/classes/parse.class.php';

dle_session();
$_COOKIE['dle_skin'] = trim(totranslit( $_COOKIE['dle_skin'], false, false ));

if( $_COOKIE['dle_skin'] ) {
	if( @is_dir( ROOT_DIR . '/templates/' . $_COOKIE['dle_skin'] ) ) {
		$config['skin'] = $_COOKIE['dle_skin'];
	}
}

if( $config["lang_" . $config['skin']] ) {

	if ( file_exists( ROOT_DIR . '/language/' . $config["lang_" . $config['skin']] . '/website.lng' ) ) {	
		include_once ROOT_DIR . '/language/' . $config["lang_" . $config['skin']] . '/website.lng';
	} else die("Language file not found");

} else {
	
	include_once ROOT_DIR . '/language/' . $config['langs'] . '/website.lng';

}
$config['charset'] = ($lang['charset'] != '') ? $lang['charset'] : $config['charset'];

//################# Определение групп пользователей
$user_group = get_vars( "usergroup" );

if( ! $user_group ) {
	$user_group = array ();
	
	$db->query( "SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC" );
	
	while ( $row = $db->get_row() ) {
		
		$user_group[$row['id']] = array ();
		
		foreach ( $row as $key => $value ) {
			$user_group[$row['id']][$key] = stripslashes($value);
		}
	
	}
	set_vars( "usergroup", $user_group );
	$db->free();
}

$is_logged = false;
$member_id = array ();

require_once ENGINE_DIR . '/modules/sitelogin.php';

if( $_REQUEST['user_hash'] == "" OR $_REQUEST['user_hash'] != $dle_login_hash ) {
	die ("error");
}

if( !$is_logged ) {
	$member_id['user_group'] = 5;
}

if ($is_logged AND $member_id['banned'] == "yes") die("error");

$id = intval( $_GET['id'] );
$area = $_GET['area'];

if(!$id) die( "error" );

if( $config['allow_comments_wysiwyg'] > 0) {
	
	$allowed_tags = array ('div[style|class]', 'span[style|class]', 'p[style|class]', 'br', 'strong', 'em', 'ul', 'li', 'ol', 'b', 'u', 'i', 's' );
	
	if( $user_group[$member_id['user_group']]['allow_url'] ) $allowed_tags[] = 'a[href|target|style|class|title]';
	if( $user_group[$member_id['user_group']]['allow_image'] ) $allowed_tags[] = 'img[style|class|src|alt|width|height]';
	
	$parse = new ParseFilter( $allowed_tags );
	$parse->wysiwyg = true;
	
} else {
	$parse = new ParseFilter();
}

$parse->safe_mode = true;
$parse->remove_html = false;

$row = $db->super_query( "SELECT post_id, autor, text FROM " . PREFIX . "_comments WHERE id = '{$id}'" );

if (!$row['text']) die( "error" );

$row_news = $db->super_query( "SELECT allow_comm, approve, access FROM " . PREFIX . "_post LEFT JOIN " . PREFIX . "_post_extras ON (" . PREFIX . "_post.id=" . PREFIX . "_post_extras.news_id) WHERE id='{$row['post_id']}'" );
$options = news_permission( $row_news['access'] );

if( (!$user_group[$member_id['user_group']]['allow_addc'] and $options[$member_id['user_group']] != 2) or $options[$member_id['user_group']] == 1 ) die( "error" );

if( !$row_news['allow_comm'] OR !$row_news['approve'] ) {
	die( "error" );
}

if( $config['allow_comments_wysiwyg'] < 1 ) {
	
	$text = $parse->decodeBBCodes( $row['text'], false );
	$text = str_replace( "&#58;", ":", $text );
	$text = str_replace( "&#91;", "[", $text );
	$text = str_replace( "&#93;", "]", $text );
	$text = str_replace( "&#123;", "{", $text );

} else {
	$parse->wysiwyg = true;
	$text = $parse->decodeBBCodes( $row['text'], TRUE, $config['allow_comments_wysiwyg'] );
	$text = preg_replace('/<p[^>]*>/', '', $text); 
	$text = str_replace("</p>", "<br>", $text);	
	$text = preg_replace('/<div[^>]*>/', '', $text); 
	$text = str_replace("</div>", "<br>", $text);
	$text = str_replace( "\r", "", $text );
	$text = str_replace( "\n", "", $text );
	$text = trim($text);

}

$text = preg_replace ( "#\[hide(.*?)\](.+?)\[/hide\]#is", "", $text );

@header( "Content-type: text/html; charset=" . $config['charset'] );

echo "[quote={$row['autor']}]{$text}[/quote]";

?>