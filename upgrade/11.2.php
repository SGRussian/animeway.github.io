<?php

$config['version_id'] = "11.3";
$config['max_cache_pages'] = '10';
$config['only_ssl'] = '0';
$config['bbimages_in_wysiwyg'] = '0';
$config['allow_redirects'] = '0';

$tableSchema = array();

$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_redirects";
$tableSchema[] = "CREATE TABLE " . PREFIX . "_redirects (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `from` varchar(250) NOT NULL default '',
  `to` varchar(250) NOT NULL default '',
  PRIMARY KEY (`id`)
) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

$tableSchema[] = "ALTER TABLE `" . PREFIX . "_usergroups` ADD `allow_mail_files` TINYINT(1) NOT NULL DEFAULT '0' , ADD `max_mail_files` SMALLINT(6) NOT NULL DEFAULT '0' , ADD `max_mail_allfiles` MEDIUMINT(9) NOT NULL DEFAULT '0' , ADD `mail_files_type` VARCHAR(100) NOT NULL DEFAULT ''";
$tableSchema[] = "UPDATE " . PREFIX . "_usergroups SET `allow_mail_files` = '0', `max_mail_files` = '3', `max_mail_allfiles` = '1000', `mail_files_type` = 'jpg,png,zip,pdf'";
$tableSchema[] = "UPDATE " . PREFIX . "_usergroups SET `allow_mail_files` = '1' WHERE id < '3'";

foreach($tableSchema as $table) {
	$db->query ($table);
}


$handler = fopen(ENGINE_DIR.'/data/config.php', "w") OR die("Извините, но невозможно записать информацию в файл <b>.engine/data/config.php</b>.<br />Проверьте правильность проставленного CHMOD!");
fwrite($handler, "<?PHP \n\n//System Configurations\n\n\$config = array (\n\n");
foreach($config as $name => $value)
{
	fwrite($handler, "'{$name}' => \"{$value}\",\n\n");
}
fwrite($handler, ");\n\n?>");
fclose($handler);

require_once(ENGINE_DIR.'/data/videoconfig.php');
	
unset($video_config['tube_related']);
unset($video_config['tube_dle']);
unset($video_config['height']);
unset($video_config['play']);

$con_file = fopen(ENGINE_DIR.'/data/videoconfig.php', "w+");
if($con_file) {
	fwrite( $con_file, "<?PHP \n\n//Videoplayers Configurations\n\n\$video_config = array (\n\n" );
	foreach ( $video_config as $name => $value ) {
		
		fwrite( $con_file, "'{$name}' => \"{$value}\",\n\n" );
		
	}
	fwrite($con_file, ");\n\n?>" );
	fclose($con_file);
}
	
$fdir = opendir( ENGINE_DIR . '/cache/system/' );
while ( $file = readdir( $fdir ) ) {
	if( $file != '.' and $file != '..' and $file != '.htaccess' ) {
		@unlink( ENGINE_DIR . '/cache/system/' . $file );
		
	}
}

@unlink(ENGINE_DIR.'/data/snap.db');

listdir( ENGINE_DIR . '/cache/system/CSS' );
listdir( ENGINE_DIR . '/cache/system/HTML' );
listdir( ENGINE_DIR . '/cache/system/URI' );

clear_cache();

if ($db->error_count) {

	$error_info = "Всего запланировано запросов: <b>".$db->query_num."</b> Неудалось выполнить запросов: <b>".$db->error_count."</b>. Возможно они уже выполнены ранее.<br /><br /><div class=\"quote\"><b>Список не выполненных запросов:</b><br /><br />"; 

	foreach ($db->query_list as $value) {

		$error_info .= $value['query']."<br /><br />";

	}

	$error_info .= "</div>";

} else $error_info = "";

msgbox("info","Информация", "Обновление базы данных с версии <b>11.2</b> до версии <b>11.3</b> успешно завершено.<br /><br />{$error_info}<br />Нажмите далее для продолжения процессa обновления скрипта.");

?>