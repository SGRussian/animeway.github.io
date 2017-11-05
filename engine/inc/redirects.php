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
 Файл: redirects.php
-----------------------------------------------------
 Назначение: управление редиректами по сайту
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) {
  die("Hacking attempt!");
}

if( $member_id['user_group'] != 1  ) {
	msg( "error", $lang['index_denied'], $lang['index_denied'] );
}

if (!$config['allow_redirects']) {

$lang['opt_redirectshelp'] .= "<br /><br /><span class=\"note large\"><font color=\"red\">{$lang['module_disabled']}</font></span>";

}

$start_from = intval( $_REQUEST['start_from'] );
$news_per_page = 50;

if( $start_from < 0 ) $start_from = 0;

if ($_REQUEST['searchword']) {
  
  $searchword = htmlspecialchars( strip_tags( stripslashes( trim( urldecode ( $_REQUEST['searchword'] ) ) ) ), ENT_COMPAT, $config['charset'] );
  
} else $searchword = "";

if ($searchword) $urlsearch = "&searchword={$searchword}"; else $urlsearch = "";


function clear_url_for_redirect ($a) {
	if (!$a) return '';
	
	if (strpos($a, "//") === 0) $a = "http:".$a;
	$a = parse_url($a);
	
	if ($a['query']) $a = $a['path'].'?'.$a['query']; else $a = $a['path'];
	
	$a = preg_replace( '#[/]+#i', '/', $a );
	
	if($a[0] != '/') $a = '/'.$a;
	
	return $a;
}

if ($_GET['action'] == "delete") {
	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		
		die( "Hacking attempt! User not found" );
	
	}

	$id = intval ( $_GET['id'] );

	$db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$_TIME}', '{$_IP}', '104', '')" );
	$db->query( "DELETE FROM " . PREFIX . "_redirects WHERE id='{$id}'" );

	@unlink( ENGINE_DIR . '/cache/system/redirects.php' );
	header( "Location: ?mod=redirects&start_from={$start_from}{$urlsearch}" );
	die();

}

if ($_POST['action'] == "mass_delete") {

	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		
		die( "Hacking attempt! User not found" );
	
	}

	if( !$_POST['selected_tags'] ) {
		msg( "error", $lang['mass_error'], $lang['redirects_err_4'], "?mod=redirects&start_from={$start_from}{$urlsearch}" );
	}

	foreach ( $_POST['selected_tags'] as $id ) {
		$id = intval($id);
		$db->query( "DELETE FROM " . PREFIX . "_redirects WHERE id='{$id}'" );
	}

	$db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$_TIME}', '{$_IP}', '104', '')" );

	@unlink( ENGINE_DIR . '/cache/system/redirects.php' );
	header( "Location: ?mod=redirects&start_from={$start_from}{$urlsearch}" );
	die();

}

if ($_GET['action'] == "add") {

	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		
		die( "Hacking attempt! User not found" );
	
	}

	$oldurl = clear_url_for_redirect(strip_tags( stripslashes( trim( convert_unicode( $_GET['oldurl'], $config['charset'] )))));
	$newurl = strip_tags( stripslashes( trim( convert_unicode( $_GET['newurl'], $config['charset'] ))));

	$oldurl = str_ireplace( "document.cookie", "d&#111;cument.cookie", $oldurl );
	$oldurl = preg_replace( "/javascript:/i", "j&#1072;vascript:", $oldurl );
	$oldurl = preg_replace( "/data:/i", "d&#1072;ta:", $oldurl );
	$newurl = str_ireplace( "document.cookie", "d&#111;cument.cookie", $newurl );
	$newurl = preg_replace( "/javascript:/i", "j&#1072;vascript:", $newurl );
	$newurl = preg_replace( "/data:/i", "d&#1072;ta:", $newurl );

	if (!$oldurl OR !$newurl ) {
		msg( "error", $lang['opt_error'], $lang['redirects_err'], "?mod=redirects&start_from={$start_from}" );
	}
	
	if ($oldurl == $newurl OR clear_url_for_redirect ($oldurl) == clear_url_for_redirect ($newurl) ) {
		msg( "error", $lang['opt_error'], $lang['redirects_err_2'], "?mod=redirects&start_from={$start_from}" );
	}

	$oldurl = @$db->safesql( $oldurl );
	$newurl = @$db->safesql( $newurl );
	
	$row = $db->super_query( "SELECT `from` FROM " . PREFIX . "_redirects WHERE `from` = '{$oldurl}'" );

	if( $row['from'] ) {
		msg( "error", $lang['opt_error'], $lang['redirects_err_3'], "?mod=redirects&start_from={$start_from}" );
	}
	
	$db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$_TIME}', '{$_IP}', '102', '{$oldurl}')" );
	$db->query( "INSERT INTO " . PREFIX . "_redirects (`from`, `to`) values ('{$oldurl}', '{$newurl}')" );

	@unlink( ENGINE_DIR . '/cache/system/redirects.php' );
	header( "Location: ?mod=redirects" );
	die();
}

if ($_GET['action'] == "edit") {

	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		
		die( "Hacking attempt! User not found" );
	
	}
	
	$id = intval ( $_GET['id'] );
	$oldurl = clear_url_for_redirect(strip_tags( stripslashes( trim( convert_unicode( $_GET['oldurl'], $config['charset'] )))));
	$newurl = strip_tags( stripslashes( trim( convert_unicode( $_GET['newurl'], $config['charset'] ))));

	$oldurl = str_ireplace( "document.cookie", "d&#111;cument.cookie", $oldurl );
	$oldurl = preg_replace( "/javascript:/i", "j&#1072;vascript:", $oldurl );
	$oldurl = preg_replace( "/data:/i", "d&#1072;ta:", $oldurl );
	$newurl = str_ireplace( "document.cookie", "d&#111;cument.cookie", $newurl );
	$newurl = preg_replace( "/javascript:/i", "j&#1072;vascript:", $newurl );
	$newurl = preg_replace( "/data:/i", "d&#1072;ta:", $newurl );

	if (!$oldurl OR !$newurl ) {
		msg( "error", $lang['opt_error'], $lang['redirects_err'], "?mod=redirects&start_from={$start_from}{$urlsearch}" );
	}
	
	if ($oldurl == $newurl OR clear_url_for_redirect ($oldurl) == clear_url_for_redirect ($newurl) ) {
		msg( "error", $lang['opt_error'], $lang['redirects_err_2'], "?mod=redirects&start_from={$start_from}{$urlsearch}" );
	}

	$oldurl = @$db->safesql( $oldurl );
	$newurl = @$db->safesql( $newurl );
	
	$row = $db->super_query( "SELECT `from` FROM " . PREFIX . "_redirects WHERE `from` = '{$oldurl}' AND id != '{$id}'" );

	if( $row['from'] ) {
		msg( "error", $lang['opt_error'], $lang['redirects_err_3'], "?mod=redirects&start_from={$start_from}{$urlsearch}" );
	}	

	$db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$_TIME}', '{$_IP}', '103', '{$oldurl}')" );
	$db->query( "UPDATE " . PREFIX . "_redirects SET `from`='{$oldurl}', `to`='{$newurl}' WHERE id='{$id}'" );

	@unlink( ENGINE_DIR . '/cache/system/redirects.php' );
	header( "Location: ?mod=redirects&start_from={$start_from}{$urlsearch}" );
	die();
}

echoheader( "<i class=\"icon-link\"></i>".$lang['opt_redirects'], $lang['header_r_1'] );

echo <<<HTML
<form action="?mod=redirects" method="get" name="navi" id="navi">
<input type="hidden" name="mod" value="redirects">
<input type="hidden" name="start_from" id="start_from" value="{$start_from}">
<input type="hidden" name="searchword" value="{$searchword}">
</form>
<form action="?mod=redirects" method="post" name="optionsbar" id="optionsbar">
<input type="hidden" name="mod" value="redirects">
<input type="hidden" name="user_hash" value="{$dle_login_hash}">
<input type="hidden" name="start_from" id="start_from" value="{$start_from}">
<div class="box">
  <div class="box-header">
    <div id="newstitlelist" class="title">{$lang['opt_redirects']}</div>
	<ul class="box-toolbar">
      <li>
        <label class="input-with-submit">
          <input type="text" name="searchword" placeholder="{$lang['search_field']}" style="width:300px;" onchange="document.optionsbar.start_from.value=0;" value="{$searchword}">
          <button type="submit" class="submit-icon">
            <i class="icon-search"></i>
          </button>
        </label>
      </li>
    </ul>
  </div>
  <div class="box-content table-responsive">
HTML;

$i = $start_from+$news_per_page;

if ( $searchword ) {
  
  $searchword = @$db->safesql($searchword);
  $where = "WHERE `from` like '%$searchword%' OR `to` like '%$searchword%' ";
  $lang['links_not_found'] = $lang['tags_s_not_found'];
  
} else $where = "";

$result_count = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_redirects {$where}");
$all_count_news = $result_count['count'];


		// pagination

		$npp_nav = "";
		
		if( $all_count_news > $news_per_page ) {

			if( $start_from > 0 ) {
				$previous = $start_from - $news_per_page;
				$npp_nav .= "<li><a onclick=\"javascript:search_submit($previous); return(false);\" href=\"#\" title=\"{$lang['edit_prev']}\">&lt;&lt;</a></li>";
			}
			
			$enpages_count = @ceil( $all_count_news / $news_per_page );
			$enpages_start_from = 0;
			$enpages = "";
			
			if( $enpages_count <= 10 ) {
				
				for($j = 1; $j <= $enpages_count; $j ++) {
					
					if( $enpages_start_from != $start_from ) {
						
						$enpages .= "<li><a onclick=\"javascript:search_submit($enpages_start_from); return(false);\" href=\"#\">$j</a></li>";
					
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
					
					$enpages .= "<li><a onclick=\"javascript:search_submit(0); return(false);\" href=\"#\">1</a></li> <li><span>...</span></li>";
				
				}
				
				for($j = $start; $j <= $end; $j ++) {
					
					if( $enpages_start_from != $start_from ) {
						
						$enpages .= "<li><a onclick=\"javascript:search_submit($enpages_start_from); return(false);\" href=\"#\">$j</a></li>";
					
					} else {
						
						$enpages .= "<li class=\"active\"><span>$j</span></li>";
					}
					
					$enpages_start_from += $news_per_page;
				}
				
				$enpages_start_from = ($enpages_count - 1) * $news_per_page;
				$enpages .= "<li><span>...</span></li><li><a onclick=\"javascript:search_submit($enpages_start_from); return(false);\" href=\"#\">$enpages_count</a></li>";
				
				$npp_nav .= $enpages;
			
			}
			
			if( $all_count_news > $i ) {
				$how_next = $all_count_news - $i;
				if( $how_next > $news_per_page ) {
					$how_next = $news_per_page;
				}
				$npp_nav .= "<li><a onclick=\"javascript:search_submit($i); return(false);\" href=\"#\" title=\"{$lang['edit_next']}\">&gt;&gt;</a></li>";
			}
			
			$npp_nav = "<ul class=\"pagination pagination-sm\">".$npp_nav."</ul>";
		
		}
		
		// pagination

$i = 0;

if ( $all_count_news ) {

	$entries = "";

	$db->query("SELECT * FROM " . PREFIX . "_redirects {$where}ORDER BY id DESC LIMIT {$start_from},{$news_per_page}");

	while($row = $db->get_row()) {
	
		$menu_link = <<<HTML
        <div class="btn-group">
          <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">{$lang['filter_action']} <span class="caret"></span></button>
          <ul class="dropdown-menu text-left">
            <li><a uid="{$row['id']}" href="?mod=redirects" class="editlink"><i class="icon-pencil"></i> {$lang['word_ledit']}</a></li>
			<li class="divider"></li>
            <li><a uid="{$row['id']}" class="dellink" href="?mod=redirects"><i class="icon-trash"></i> {$lang['word_ldel']}</a></li>
          </ul>
        </div>
HTML;
		$row['from'] = htmlspecialchars($row['from'], ENT_QUOTES, $config['charset'] );
		$row['to'] = htmlspecialchars($row['to'], ENT_QUOTES, $config['charset'] );
		
		$entries .= "<tr>
        <td><div id=\"content_{$row['id']}\">{$row['from']}</div></td>
        <td><div id=\"url_{$row['id']}\">{$row['to']}</div></td>
        <td align=\"center\">{$menu_link}</td>
        <td align=\"center\"><input name=\"selected_tags[]\" value=\"{$row['id']}\" type=\"checkbox\"></td>
        </tr>";


	}

	$db->free();

echo <<<HTML

    <table class="table table-normal table-hover">
      <thead>
      <tr>
        <td>{$lang['header_r_2']}</td>
        <td>{$lang['header_r_3']}</td>
        <td style="width: 200px">{$lang['user_action']}</td>
        <td style="width: 40px"><input type="checkbox" name="master_box" title="{$lang['edit_selall']}" onclick="javascript:ckeck_uncheck_all()"></td>
      </tr>
      </thead>
	  <tbody>
		{$entries}
	  </tbody>
	</table>
	</div>
<div class="box-footer padded">
    <div class="pull-left">{$npp_nav}</div>
	<div class="pull-right">
	<input class="btn btn-green" type="button" onclick="addLink()" value="{$lang['add_links']}">&nbsp;
	<select class="uniform" name="action">
	<option value="">{$lang['edit_selact']}</option>
	<option value="mass_delete">{$lang['edit_seldel']}</option>
	</select>&nbsp;<input class="btn btn-gold" type="submit" value="{$lang['b_start']}">
	</div>
</div>
HTML;


}  else {

if($where) $lang['redirects_not_found'] = $lang['redirects_not_found_1'];

echo <<<HTML
</div>
<div class="row box-section">
<table width="100%">
    <tr>
        <td style="height:50px;"><div align="center"><br /><br />{$lang['redirects_not_found']}<br /><br></a></div><input class="btn btn-green" type="button" onclick="addLink()" value="{$lang['add_links']}"></td>
    </tr>
</table>
</div>
HTML;

}

echo <<<HTML
</div>
</form>


<div class="well relative"><span class="triangle-button green"><i class="icon-bell"></i></span>{$lang['opt_redirectshelp']}</div>
<script language="javascript" type="text/javascript">  
<!-- 
    function search_submit(prm){
      document.navi.start_from.value=prm;
      document.navi.submit();
      return false;
    }

	function ckeck_uncheck_all() {
	    var frm = document.optionsbar;
	    for (var i=0;i<frm.elements.length;i++) {
	        var elmnt = frm.elements[i];
	        if (elmnt.type=='checkbox') {
	            if(frm.master_box.checked == true){ elmnt.checked=false; }
	            else{ elmnt.checked=true; }
	        }
	    }
	    if(frm.master_box.checked == true){ frm.master_box.checked = false; }
	    else{ frm.master_box.checked = true; }
	}
	
	function addLink() {
		var b = {};
	
		b[dle_act_lang[3]] = function() { 
						$(this).dialog("close");						
				    };
	
		b[dle_act_lang[2]] = function() { 
						if ( $("#dle-promt-oldurl").val().length < 1) {
							 $("#dle-promt-oldurl").addClass('ui-state-error');
						} else if ( $("#dle-promt-newurl").val().length < 1 ) {
							 $("#dle-promt-oldurl").removeClass('ui-state-error');
							 $("#dle-promt-newurl").addClass('ui-state-error');
						} else {
							var oldurl = $("#dle-promt-oldurl").val();
							var newurl = $("#dle-promt-newurl").val();

							$(this).dialog("close");
							$("#dlepopup").remove();

							document.location='?mod=redirects&user_hash={$dle_login_hash}&action=add&oldurl=' + encodeURIComponent(oldurl) + '&newurl=' + encodeURIComponent(newurl);

						}				
					};

		$("#dlepopup").remove();

		$("body").append("<div id='dlepopup' title='{$lang['add_links_new']}' style='display:none'><br />{$lang['input_oldurl']}<br /><input type='text' name='dle-promt-oldurl' id='dle-promt-oldurl' class='ui-widget-content ui-corner-all' style='width:97%;' value=''/><br /><br />{$lang['input_newurl']}<br /><input type='text' name='dle-promt-newurl' id='dle-promt-newurl' class='ui-widget-content ui-corner-all' style='width:97%;' value=''/></div>");
	
		$('#dlepopup').dialog({
			autoOpen: true,
			width: 500,
			resizable: false,
			buttons: b
		});

	}

$(function(){

		var old_link = '';

		$('.dellink').click(function(){

			old_link = $('#content_'+$(this).attr('uid')).text();
			var urlid = $(this).attr('uid');

		    DLEconfirm( '{$lang['redirects_del']} <b>&laquo;'+old_link+'&raquo;</b> {$lang['redirects_del_1']}', '{$lang['p_confirm']}', function () {

				document.location="?mod=redirects&start_from={$start_from}&user_hash={$dle_login_hash}{$urlsearch}&action=delete&id=" + urlid;

			} );

			return false;
		});


		$('.editlink').click(function(){

			var oldurl = $('#content_'+$(this).attr('uid')).text();
			var newurl = $('#url_'+$(this).attr('uid')).text();
			var urlid = $(this).attr('uid');
			
			var b = {};
		
			b[dle_act_lang[3]] = function() { 
							$(this).dialog("close");						
					    };
		
			b[dle_act_lang[2]] = function() { 
						if ( $("#dle-promt-oldurl").val().length < 1) {
							 $("#dle-promt-oldurl").addClass('ui-state-error');
						} else if ( $("#dle-promt-newurl").val().length < 1 ) {
							 $("#dle-promt-oldurl").removeClass('ui-state-error');
							 $("#dle-promt-newurl").addClass('ui-state-error');
						} else {
							var oldurl = $("#dle-promt-oldurl").val();
							var newurl = $("#dle-promt-newurl").val();
							
							$(this).dialog("close");
							$("#dlepopup").remove();
	
							document.location='?mod=redirects&user_hash={$dle_login_hash}&action=edit&id='+urlid+'&oldurl=' + encodeURIComponent(oldurl) + '&newurl=' + encodeURIComponent(newurl);
	
						}				
					};
	
			$("#dlepopup").remove();

			$("body").append("<div id='dlepopup' title='{$lang['add_links_new']}' style='display:none'><br />{$lang['input_oldurl']}<br /><input type='text' name='dle-promt-oldurl' id='dle-promt-oldurl' class='ui-widget-content ui-corner-all' style='width:97%;' value='"+oldurl+"'/><br /><br />{$lang['input_newurl']}<br /><input type='text' name='dle-promt-newurl' id='dle-promt-newurl' class='ui-widget-content ui-corner-all' style='width:97%;' value='"+newurl+"'/></div>");
		
			$('#dlepopup').dialog({
				autoOpen: true,
				width: 500,
				resizable: false,
				buttons: b
			});

			return false;
		});

});
//-->
</script>
HTML;


echofooter();
?>