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
 Файл: comments.php
-----------------------------------------------------
 Назначение: Управления комментариями
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) {
	die( "Hacking attempt!" );
}

if( ! $user_group[$member_id['user_group']]['admin_comments'] ) {
	msg( "error", $lang['addnews_denied'], $lang['addnews_denied'], $_SESSION['admin_referrer'] );
}

$id = intval( $_REQUEST['id'] );


$_SESSION['admin_referrer'] = "?mod=comments&amp;action=edit";

if( $action == "dodelete" AND $id) {
	
	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		
		die( "Hacking attempt! User not found" );
	
	}
	
	deletecommentsbynewsid($id);
	$db->query( "UPDATE " . PREFIX . "_post SET comm_num='0' WHERE id ='{$id}'" );
	
	clear_cache();
	$db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$_TIME}', '{$_IP}', '20', '$id')" );
	
	msg( "info", $lang['mass_head'], $lang['mass_delokc'], $_SESSION['admin_referrer'] );

} elseif( $action == "mass_delete" ) {

	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		
		die( "Hacking attempt! User not found" );
	
	}

	if( ! $_POST['selected_comments'] ) {
		msg( "error", $lang['mass_error'], $lang['mass_dcomm'], $_SESSION['admin_referrer'] );
	}
	
	foreach ( $_POST['selected_comments'] as $c_id ) {

		$c_id = intval( $c_id );
		
		deletecomments( $c_id );

	}
	
	clear_cache( array('news_', 'full_', 'comm_', 'rss') );

	$db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$_TIME}', '{$_IP}', '21', '')" );
	
	msg( "info", $lang['mass_head'], $lang['mass_delokc'], "?mod=comments&action=edit&id={$id}" );

} elseif( $action == "edit" ) {

	if ( $id ) $where = "post_id = '{$id}' AND "; else $where = "";

	$start_from = intval( $_GET['start_from'] );
	if( $start_from < 0 ) $start_from = 0;
	$news_per_page = 50;
	$i = $start_from;

	$gopage = intval( $_GET['gopage'] );
	if( $gopage > 0 ) $start_from = ($gopage - 1) * $news_per_page;

	if ($config['allow_comments_wysiwyg'] == "2") {
	
		$js_array[] = "engine/editor/jscripts/tiny_mce/tinymce.min.js";
	
	}
	
	if ($config['allow_comments_wysiwyg'] == "1") {
	
		$js_array[] = "engine/editor/jscripts/froala/editor.js";
		$js_array[] = "engine/editor/jscripts/froala/languages/{$lang['wysiwyg_language']}.js";
	
	}
	
	echoheader( "<i class=\"icon-file-alt\"></i>".$lang['header_c_1'], $lang['header_c_3'] );
	
	$entries = "";

	$result_count = $db->super_query( "SELECT COUNT(*) as count FROM " . PREFIX . "_comments WHERE {$where}approve='1'" );

	$db->query( "SELECT " . PREFIX . "_comments.id, post_id, " . PREFIX . "_comments.date, " . PREFIX . "_comments.autor, text, ip, " . PREFIX . "_post.title, " . PREFIX . "_post.date as newsdate, " . PREFIX . "_post.alt_name, " . PREFIX . "_post.category FROM " . PREFIX . "_comments LEFT JOIN " . PREFIX . "_post ON " . PREFIX . "_comments.post_id=" . PREFIX . "_post.id WHERE {$where}" . PREFIX . "_comments.approve = '1' ORDER BY " . PREFIX . "_comments.date DESC LIMIT $start_from,$news_per_page" );
	
	while ( $row = $db->get_array() ) {
		$i ++;
		
		$row['text'] = str_ireplace( '{THEME}', 'templates/' . $config['skin'], $row['text'] );
		$row['text'] = "<div id='comm-id-" . $row['id'] . "'>" . stripslashes( $row['text'] ) . "</div>";
		$row['newsdate'] = strtotime( $row['newsdate'] );
		$row['date'] = strtotime( $row['date'] );
		if( !$langformatdatefull ) $langformatdatefull = "d.m.Y H:i:s";
		$date = date( $langformatdatefull, $row['date'] );
		
		if( $config['allow_alt_url'] ) {
			
			if( $config['seo_type'] == 1 OR $config['seo_type'] == 2 ) {
				
				if( intval( $row['category'] ) and $config['seo_type'] == 2 ) {
					
					$full_link = $config['http_home_url'] . get_url( intval( $row['category'] ) ) . "/" . $row['post_id'] . "-" . $row['alt_name'] . ".html";
				
				} else {
					
					$full_link = $config['http_home_url'] . $row['post_id'] . "-" . $row['alt_name'] . ".html";
				
				}
			
			} else {
				
				$full_link = $config['http_home_url'] . date( 'Y/m/d/', $row['newsdate'] ) . $row['alt_name'] . ".html";
			}
		
		} else {
			
			$full_link = $config['http_home_url'] . "index.php?newsid=" . $row['post_id'];
		
		}
		
		$news_title = "<a class=\"status-info\" href=\"" . $full_link . "\"  target=\"_blank\">" . stripslashes( $row['title'] ) . "</a>";
		$row['autor'] = "<a class=\"status-info\" onclick=\"javascript:popupedit('".urlencode($row['autor'])."'); return(false)\" href=\"#\">{$row['autor']}</a>";
		$row['ip'] = "<a class=\"status-info\" href=\"?mod=blockip&ip=".urlencode($row['ip'])."\" target=\"_blank\">{$row['ip']}</a>";

	
	$entries .= <<<HTML
 <li id='table-comm-{$row['id']}' class="arrow-box-left gray">
 <input type="hidden" name="post_id[{$row['id']}]" value="{$row['post_id']}">
 <div class="avatar"><input name="selected_comments[]" value="{$row['id']}" type="checkbox"></div>
    <div class="info">
      <span class="name">
        <span class="label label-green">{$lang['edit_autor']}</span> <strong class="indent">{$row['autor']}</strong> IP: {$row['ip']} {$lang['cmod_n_title']} <strong>{$news_title}</strong>
      </span>
      <span class="time"><i class="icon-time"></i>{$date}</span>
    </div>
    <div class="content">
        {$row['text']}
      <div style="margin-top:10px;">
		<a onclick="ajax_comm_edit('{$row['id']}'); return false;" href="#" class="btn btn-xs btn-blue"><i class="icon-pencil"></i> <b>{$lang['group_sel1']}</b></a>
		<a onclick="MarkSpam('{$row['id']}'); return false;" href="#" class="btn btn-xs btn-gold"><i class="icon-minus-sign"></i> <b>{$lang['btn_spam']}</b></a>
		<a onclick="DeleteComments('{$row['id']}'); return false;" href="#" class="btn btn-xs btn-red"><i class="icon-trash"></i> <b>{$lang['edit_dnews']}</b></a>
      </div>
    </div>
  </li>
HTML;
	
	}
	
	$db->free();

		// pagination

		$npp_nav = "";
		
		if( $start_from > 0 ) {
			$previous = $start_from - $news_per_page;
			$npp_nav .= "<li><a href=\"?mod=comments&action=edit&id={$id}&start_from={$previous}\" title=\"{$lang['edit_prev']}\">&lt;&lt;</a></li>";
		}
		
		if( $result_count['count'] > $news_per_page ) {
			
			$enpages_count = @ceil( $result_count['count'] / $news_per_page );
			$enpages_start_from = 0;
			$enpages = "";
			
			if( $enpages_count <= 10 ) {
				
				for($j = 1; $j <= $enpages_count; $j ++) {
					
					if( $enpages_start_from != $start_from ) {
						
						$enpages .= "<li><a href=\"?mod=comments&action=edit&id={$id}&start_from={$enpages_start_from}\">$j</a></li>";
					
					} else {
						
						$enpages .= "<li class=\"active\"><span>$j</span></li>";
					}
					
					$enpages_start_from += $news_per_page;
				}
				
				$npp_nav .= $enpages;
			
			} else {
				
				$start = 1;
				$end = 10;
				
				if( $start_from > 0 ) {
					
					if( ($start_from / $news_per_page) > 4 ) {
						
						$start = @ceil( $start_from / $news_per_page ) - 3;
						$end = $start + 9;
						
						if( $end > $enpages_count ) {
							$start = $enpages_count - 10;
							$end = $enpages_count - 1;
						}
						
						$enpages_start_from = ($start - 1) * $news_per_page;
					
					}
				
				}
				
				if( $start > 2 ) {
					
					$enpages .= "<li><a href=\"?mod=comments&action=edit&id={$id}&start_from=0\">1</a></li> <li><span>...</span></li>";
				
				}
				
				for($j = $start; $j <= $end; $j ++) {
					
					if( $enpages_start_from != $start_from ) {
						
						$enpages .= "<li><a href=\"?mod=comments&action=edit&id={$id}&start_from={$enpages_start_from}\">$j</a></li>";
					
					} else {
						
						$enpages .= "<li class=\"active\"><span>$j</span></li>";
					}
					
					$enpages_start_from += $news_per_page;
				}
				
				$enpages_start_from = ($enpages_count - 1) * $news_per_page;
				$enpages .= "<li><span>...</span></li><li><a href=\"?mod=comments&action=edit&id={$id}&start_from={$enpages_start_from}\">$enpages_count</a></li>";
				
				$npp_nav .= $enpages;
			
			}
		
			if( $result_count['count'] > $i ) {
				$how_next = $result_count['count'] - $i;
				if( $how_next > $news_per_page ) {
					$how_next = $news_per_page;
				}
				$npp_nav .= "<li><a href=\"?mod=comments&action=edit&id={$id}&start_from={$i}\" title=\"{$lang['edit_next']}\">&gt;&gt;</a></li>";
			}
			
			$npp_nav = "<div class=\"row box-section text-center\"><ul class=\"pagination pagination-sm\">".$npp_nav."</ul></div>";
		}		
		// pagination

if ($config['allow_comments_wysiwyg'] == "1") {

	echo "\n<link media=\"screen\" href=\"{$config['http_home_url']}engine/editor/jscripts/froala/css/editor.css\" type=\"text/css\" rel=\"stylesheet\" />\n";


}

	echo <<<HTML
<style type="text/css">
/*---BB Редактор---*/
.bb-pane {
  height: 1%; overflow: hidden;
  padding-bottom: 5px;
  padding-left: 5px;
  margin: 0;
  height: auto !important;
  text-decoration:none;
  background-image: -webkit-gradient(linear, left 0%, left 100%, from(#FBFBFB), to(#EAEAEA));
  background-image: -webkit-linear-gradient(top, #FBFBFB, 0%, #EAEAEA, 100%);
  background-image: -moz-linear-gradient(top, #FBFBFB 0%, #EAEAEA 100%);
  background-image: linear-gradient(to bottom, #FBFBFB 0%, #EAEAEA 100%);
  background-repeat: repeat-x;
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FBFBFB', endColorstr='#EAEAEA', GradientType=0);
  border-radius: 3px 3px 3px 3px;
  -moz-border-radius-bottomright: 0px;
  -webkit-border-bottom-right-radius: 0px;
  -khtml-border-bottom-right-radius: 0px; 
  border-bottom-right-radius: 0px;
  -moz-border-radius-bottomleft: 0px;
  -webkit-border-bottom-left-radius: 0px;
  -khtml-border-bottom-left-radius: 0px;
  border-bottom-left-radius: 0px;
  border-top:1px solid #d7d7d7;
  border-left:1px solid #d7d7d7;
  border-right:1px solid #d7d7d7;
  box-shadow: none !important;
}

.bb-pane>b {
    margin-top: 5px;
    margin-left: 0;
	vertical-align: middle;
}
.bb-pane .bb-btn + .bb-btn,.bb-pane .bb-btn + .bb-pane,.bb-pane .bb-pane + .bb-btn,.bb-pane .bb-pane + .bb-pane {
    margin-left:-1px;
}
.bb-btn {
	display: inline-block; overflow: hidden; float: left;
	padding: 4px 10px;
    border: 1px solid #d4d4d4;
    -webkit-box-shadow: inset 0 1px 2px white;
    -moz-box-shadow: inset 0 1px 2px white;
    box-shadow: inset 0 1px 2px white;
    background-repeat: repeat-x;
    background-image: -webkit-gradient(linear, left 0%, left 100%, color-stop(0%, #fdfdfd), color-stop(100%, #e9e9e9));
    background-image: -webkit-linear-gradient(top, #fdfdfd, 0%, #e9e9e9, 100%);
    background-image: -moz-linear-gradient(top, #fdfdfd, 0%, #e9e9e9, 100%);
    background-image: linear-gradient(to bottom, #fdfdfd 0%, #e9e9e9  100%);

}
 

.bb-btn:hover {
      background: #e6e6e6;
      background-repeat: repeat-x;
      background-image: -webkit-gradient(linear, 50% 0%, 50% 100%, color-stop(0%, #fdfdfd), color-stop(100%, #e6e6e6));
      background-image: -webkit-linear-gradient(top, #fdfdfd, 0%, #e6e6e6, 100%);
      background-image: -moz-linear-gradient(top, #fdfdfd, 0%, #e6e6e6, 100%);
      background-image: -o-linear-gradient(top, #fdfdfd, 0%, #e6e6e6, 100%);
      background-image: linear-gradient(to bottom, #fdfdfd 0%, #e6e6e6 100%);
      -webkit-transition: box-shadow 0.05s ease-in-out;
      -moz-transition: box-shadow 0.05s ease-in-out;
      -o-transition: box-shadow 0.05s ease-in-out;
      transition: box-shadow 0.05s ease-in-out;
}
    
.bb-btn:active {
      background: #f3f3f3;
      border-color: #cfcfcf;
      -webkit-box-shadow: 0 0 5px #f3f3f3 inset;
      -moz-box-shadow: 0 0 5px #f3f3f3 inset;
      box-shadow: 0 0 5px #f3f3f3 inset;
}

.bb-editor textarea { 
    -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box;
    padding: 7px; border: 1px solid #d7d7d7; width: 100%; -webkit-box-shadow:inset 0 1px 1px rgba(0, 0, 0, 0.075);
    box-shadow:inset 0 1px 1px rgba(0, 0, 0, 0.075);
    -webkit-transition:border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
    transition:border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
	margin-top: -1px;
	outline: none;
	height: 300px;
}

.bb-editor textarea:focus{
		border-color: #d7d7d7 !important;
}
	.bb-pane-dropdown {
		position: absolute;
		top: 100%; left: 0;
		z-index: 1000;
		display: none;
		min-width: 180px;
		padding: 5px 0 !important;
		margin: 2px 0 0;
		list-style: none;
		font-size: 11px;
		border: 1px solid #e6e6e6; border-color: rgba(0,0,0,0.1);
		border-radius: 2px;
		background: #fff;
		background-clip: padding-box;
		-webkit-box-shadow: 0 1px 2px #dadada; box-shadow: 0 1px 2px #dadada;
		max-height: 300px;
    	overflow: auto;
	}
	.bb-pane-dropdown > li > a {
		display: block;
		padding: 3px 10px;
		clear: both;
		font-weight: normal;
		line-height: 1.42857;
		color: #353535;
		white-space: nowrap;
	}
	.bb-pane-dropdown > li > a:hover { text-decoration:none; color: #262626; background-color:whitesmoke; }
	.bb-pane-dropdown .color-palette div .color-btn {
		width: 17px; height: 17px;
		padding: 0; margin: 0;
		border: 1px solid #fff;
		cursor: pointer;
	}
	.bb-pane-dropdown .color-palette { padding: 0px 5px; }

	.bb-pane-dropdown table { margin: 0px; }

	.bb-sel { float: left; padding: 2px 2px 0 2px; }
	.bb-sel select { font-size: 11px; }
	.bb-sep { display: inline-block; float: left; width: 1px; padding: 2px; }
	.bb-btn { cursor: pointer;  outline: 0; }

	#b_font select, #b_size select { padding: 0;}

	.bb-pane h1, .bb-pane h2, .bb-pane h3, .bb-pane h4, .bb-pane h5, .bb-pane h6 { margin-top: 5px; margin-bottom: 5px; }
	.bb-pane h1 { font-size: 36px; }
	.bb-pane h2 { font-size: 30px; }
	.bb-pane h3 { font-size: 24px; }
	.bb-pane h4 { font-size:18px; }
	.bb-pane h5 { font-size:14px; }
	.bb-pane h6 { font-size:12px; }

	[class^="bb-btn"], [class*=" bb-btn"] {
	    font-family: 'bb-editor-font';
	    speak: none;
	    font-style: normal;
	    font-weight: normal;
	    font-variant: normal;
	    text-transform: none;
	    line-height: 1;
	    font-size: 14px;
	    -webkit-font-smoothing: antialiased;
	    -moz-osx-font-smoothing: grayscale;
	}

	.bb-sel { float: left; padding: 2px 2px 0 2px; }
	.bb-sel select { font-size: 11px; }
	.bb-sep { display: inline-block; float: left; width: 1px; padding: 2px; }
	.bb-btn { cursor: pointer;  outline: 0; }

	#b_font select, #b_size select { padding: 0;}

	#b_b:before {content: "\\f032";}
	#b_i:before {content: "\\f033";}
	#b_u:before {content: "\\f0cd";}
	#b_s:before {content: "\\f0cc";}
	#b_img:before { content: "\\f03e"; }
	#b_up:before { content: "\\e930"; }
	#b_emo:before { content: "\\f118"; }
	#b_url:before { content: "\\f0c1"; }
	#b_leech:before { content: "\\e98d"; }
	#b_mail:before { content: "\\f003"; }
	#b_video:before { content: "\\e913"; }
	#b_audio:before { content: "\\e911"; }
	#b_hide:before { content: "\\e9d1"; }
	#b_quote:before { content: "\\e977"; }
	#b_code:before { content: "\\f121"; }
	#b_left:before { content: "\\f036"; }
	#b_center:before { content: "\\f037"; }
	#b_right:before { content: "\\f038"; }
	#b_color:before { content: "\\e601"; }
	#b_spoiler:before { content: "\\e600"; }
	#b_fla:before { content: "\\ea8d"; }
	#b_yt:before { content: "\\f166"; }
	#b_tf:before { content: "\\ea61"; }
	#b_list:before { content: "\\f0ca"; }
	#b_ol:before { content: "\\f0cb"; }
	#b_tnl:before { content: "\\ea61"; }
	#b_br:before { content: "\\ea68"; }
	#b_pl:before { content: "\\ea72"; }
	#b_size:before { content: "\\f034"; }
	#b_font:before { content: "\\f031"; }
	#b_header:before { content: "\\f1dc"; }
	#b_sub:before { content: "\\f12c"; }
	#b_sup:before { content: "\\f12b"; }
	#b_justify:before { content: "\\f039"; }
	.bbcodes {
		display:inline-block;
		padding: 4px 10px;
		margin-bottom:0;
		font-size:11px;
		font-weight:600;
		line-height: 1.5;
		text-shadow: 0 -1px #6f6f6f;
		cursor:pointer;
		background: #3d94c0;
		background-image: linear-gradient(top, #5ba5cb, #3d94c0);
		border: 1px solid #337ca1;
		box-shadow: inset 0 1px 2px #6eb0d1;
		border-radius: 2px;
		white-space:nowrap;
		-webkit-user-select:none;
		-moz-user-select:none;
		-ms-user-select:none;
		-o-user-select:none;
		user-select:none;
		outline:0;
		color: #fff;
	}
	 .btn:focus {
		outline:0;
	}
	.content ol,  .content ul{
		padding-left: 15px;
	}
</style>
<script type="text/javascript">
<!--
function popupedit( name ){

		var rndval = new Date().getTime(); 

		$('body').append('<div id="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #666666; opacity: .40;filter:Alpha(Opacity=40); z-index: 999; display:none;"></div>');
		$('#modal-overlay').css({'filter' : 'alpha(opacity=40)'}).fadeIn('slow');
	
		$("#dleuserpopup").remove();
		$("body").append("<div id='dleuserpopup' title='{$lang['user_edhead']}' style='display:none'></div>");
	
		$('#dleuserpopup').dialog({
			autoOpen: true,
			width: 570,
			height: 510,
			resizable: false,
			dialogClass: "modalfixed",
			buttons: {
				"{$lang['user_can']}": function() { 
					$(this).dialog("close");
					$("#dleuserpopup").remove();							
				},
				"{$lang['user_save']}": function() { 
					document.getElementById('edituserframe').contentWindow.document.getElementById('saveuserform').submit();							
				}
			},
			open: function(event, ui) { 
				$("#dleuserpopup").html("<iframe name='edituserframe' id='edituserframe' width='100%' height='389' src='?mod=editusers&action=edituser&user=" + name + "&rndval=" + rndval + "' frameborder='0' marginwidth='0' marginheight='0' allowtransparency='true'></iframe>");
			},
			beforeClose: function(event, ui) { 
				$("#dleuserpopup").html("");
			},
			close: function(event, ui) {
					$('#modal-overlay').fadeOut('slow', function() {
			        $('#modal-overlay').remove();
			    });
			 }
		});

		if ($(window).width() > 830 && $(window).height() > 530 ) {
			$('.modalfixed.ui-dialog').css({position:"fixed"});
			$('#dleuserpopup').dialog( "option", "position", ['0','0'] );
		}

		$('#dleuserpopup').css("-webkit-overflow-scrolling","touch");

		return false;

};

var c_cache = [];
var dle_root = '';
var dle_prompt = '{$lang['p_prompt']}';
var dle_wysiwyg    = '{$config['allow_comments_wysiwyg']}';

function setNewField(which, formname)
{
	if (which != selField)
	{
		fombj    = formname;
		selField = which;

	}
};

function ajax_comm_edit( c_id )
{

	for (var i = 0, length = c_cache.length; i < length; i++) {
	    if (i in c_cache) {
			if ( c_cache[ i ] !== '' )
			{
				ajax_cancel_comm_edit( i );
			}
	    }
	}

	if ( ! c_cache[ c_id ] || c_cache[ c_id ] === '' )
	{
		c_cache[ c_id ] = $('#comm-id-'+c_id).html();
	}

	ShowLoading('');

	$.get("engine/ajax/editcomments.php", { id: c_id, area: 'news', action: "edit" }, function(data){

		HideLoading('');

		$('#comm-id-'+c_id).html(data);

		setTimeout(function() {
           $("html,body").stop().animate({scrollTop: $("#comm-id-" + c_id).offset().top - 70}, 700);
        }, 100);

	}, 'html');
	return false;
};

function ajax_cancel_comm_edit( c_id ) {
	if ( c_cache[ c_id ] != "" )
	{
		$("#comm-id-"+c_id).html(c_cache[ c_id ]);
	}

	c_cache[ c_id ] = '';

	return false;
};

function ajax_save_comm_edit( c_id, area )
{

	if (dle_wysiwyg == "2") {

		tinyMCE.triggerSave();

	}

	var comm_txt = $('#dleeditcomments'+c_id).val();


	ShowLoading('');

	$.post("engine/ajax/editcomments.php", { id: c_id, comm_txt: comm_txt, area: area, action: "save", user_hash: "{$dle_login_hash}" }, function(data){

		HideLoading('');
		c_cache[ c_id ] = '';
		$("#comm-id-"+c_id).html(data);

	});
	return false;
	
};

function ckeck_uncheck_all() {
    var frm = document.dlemasscomments;
    for (var i=0;i<frm.elements.length;i++) {
        var elmnt = frm.elements[i];
        if (elmnt.type=='checkbox') {
            if(frm.master_box.checked == true){ elmnt.checked=false; }
            else{ elmnt.checked=true; }
        }
    }
    if(frm.master_box.checked == true){ frm.master_box.checked = false; }
    else{ frm.master_box.checked = true; }
	
	return false;
};

function DeleteComments(id) {

    DLEconfirm( '{$lang['d_c_confirm']}', '{$lang['p_confirm']}', function () {

		ShowLoading('');
	
		$.get("engine/ajax/deletecomments.php", { id: id, dle_allow_hash: '{$dle_login_hash}' }, function(r){
	
			HideLoading('');
	
			ShowOrHide('table-comm-'+id);
	
		});

	} );

};
function MarkSpam(id) {

    DLEconfirm( '{$lang['mark_spam_c']}', '{$lang['p_confirm']}', function () {

		ShowLoading('');
	
		$.get("engine/ajax/adminfunction.php", { id: id, action: 'commentsspam', user_hash: '{$dle_login_hash}' }, function(data){
	
			HideLoading('');
	
			if (data != "error") {
	
			    DLEconfirm( data, '{$lang['p_confirm']}', function () {
					location.reload(true);
				} );
	
			}
	
		});

	} );

};
//-->
</script>
<form action="" method="post" name="dlemasscomments" id="dlemasscomments">
<input type=hidden name="mod" value="comments">
<input type="hidden" name="user_hash" value="{$dle_login_hash}" />
<div class="box">
  <div class="box-header">
    <div class="title">{$lang['comm_einfo']}</div>
  </div>
  <div class="box-content">

	<div class="row box-section">
<div style="padding-left:15px"><input type="checkbox" name="master_box" id="master_box" title="{$lang['edit_selall']}" onclick="javascript:ckeck_uncheck_all();"><label for="master_box" style="padding-left:35px;cursor:pointer;" class="status-info">{$lang['edit_selall']}</label></div>
<ul class="chat-box timeline">
{$entries}
</ul>
  
	</div>
	{$npp_nav}
	<div class="row box-section">
		<select class="uniform" name="action"><option value="edit">---</option><option value="mass_delete">{$lang['edit_seldel']}</option></select>
		<input class="btn btn-gray" type="submit" value="{$lang['b_start']}" />
	</div>
   </div>
</div>
</form>
HTML;

	$entries .= <<<HTML
<li class="arrow-box-left gray">
<div class="avatar"><input type="checkbox" name="master_box" title="{$lang['edit_selall']}" onclick="javascript:ckeck_uncheck_all();"></div>
</li>
HTML;

	echofooter();
} else {
	msg( "error", $lang['addnews_denied'], $lang['addnews_denied'], $_SESSION['admin_referrer'] );
}
?>