<?php
/********************************************************************
 * Copyright (c) 2020 All Right Reserved By [StarsRiver]            *
 *                                                                  *
 * Author  Zhangyu                                                  *
 * Email   starsriver@yahoo.com                                     *
 ********************************************************************/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

global $_G;

$sid = intval($_GET['sid']);

if($_GET['op'] == 'delete') {
	if(submitcheck('deletesubmit')) {
		require_once libfile('function/delete');
		deleteshares(array($sid));
		showmessage('do_success', $_GET['type']=='view'?'home.php?mod=space&quickforward=1&do=share':dreferer(), array('sid' => $sid), array('showdialog'=>1, 'showmsg' => true, 'closetime' => true));
	}
} elseif($_GET['op'] == 'edithot') {
	if(!checkperm('manageshare')) {
		showmessage('no_privilege_edithot_share');
	}

	if($sid) {
		if(!$share = C::t('home_share')->fetch($sid)) {
			showmessage('share_does_not_exist');
		}
	}

	if(submitcheck('hotsubmit')) {
		C::t('home_share')->update($sid, array('hot'=>$_POST['hot']));
		C::t('home_feed')->update($sid, array('hot'=>$_POST['hot']), 'sid');

		showmessage('do_success', dreferer());
	}

} else {

	if(!checkperm('allowshare') || !helper_access::check_module('share')) {
		showmessage('no_privilege_share');
	}

	cknewuser();

    $commentcable = [
        'blog' => 'blogid',
        'pic' => 'picid',
        'thread' => 'thread',
        'article' => 'article'
    ];

    $type = empty($_GET['type']) ? '' : $_GET['type'];
    $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
    
    $note_uid = 0;
    $note_message = '';
    $note_values = [];

    $hotarr = [];
	$arr = [];
	$feed_hash_data = '';

	switch ($type) {
        case 'space':
            
            $feed_hash_data = "uid{$id}";
            
            $user = getuserbyuid($id);
            
            if (empty($user)) {
                showmessage('space_does_not_exist');
            }
            if (isblacklist($id)) {
                showmessage('is_blacklist');
            }
            
            $tospace = C::t('common_member_field_home')->fetch($id);
            $userprofile = C::t('common_member_profile')->fetch($id);
            
            $arr = [
                'itemid' => $id,
                'fromuid' => $id,
                'body_data' => [
                    'avatar' => avatar($id, 'small', true),
                    'username' => $user['username'],
                    'userlink' => 'home.php?mod=space&uid=' . $id,
                    'reside' => $userprofile['resideprovince'] . $userprofile['residecity'],
                    'spacenote' => $tospace['spacenote'] ? $tospace['spacenote'] : lang('template','should_write_that'),

                    'expend0' => '',
                    'expend1' => '',
                    'expend2' => '',
                    'expend3' => '',
                    'expend4' => '',
                    'expend5' => '',
                    'expend6' => '',
                    'expend7' => '',
                ],
            ];
    
            $note_uid = $id;
            $note_message = 'share_space';

            break;

        case 'blog':

            $feed_hash_data = "blogid{$id}";

            $blog = array_merge(
                C::t('home_blog')->fetch($id),
                C::t('home_blogfield')->fetch($id)
            );
            
            if (!$blog) {
                showmessage('blog_does_not_exist');
            }
            if (in_array($blog['status'], array(1, 2))) {
                showmessage('moderate_blog_not_share');
            }
            if ($blog['friend']) {
                showmessage('logs_can_not_share');
            }
            if (isblacklist($blog['uid'])) {
                showmessage('is_blacklist');
            }
    
            $arr = [
                'fromuid' => $blog['uid'],
                'itemid' => $id,
                'body_data' => [
                    'url' => 'home.php?mod=space&uid='.$blog['uid'].'&do=blog&id='.$blog['blogid'],
                    'subject'  => $blog['subject'],
                    'username' => $blog['username'],
                    'user_avatar' => avatar($blog['uid'], 'small', true),
                    'user_link' => 'home.php?mod=space&uid='.$blog['uid'],
                    'content'  => getstr($blog['message'], 150, 0, 0, 0, -1),
                    
                    'expend0' => '',
                    'expend1' => '',
                    'expend2' => '',
                    'expend3' => '',
                    'expend4' => '',
                    'expend5' => '',
                    'expend6' => '',
                    'expend7' => '',
                ]
            ];
            
            if ($blog['pic']) {
                $arr['body_data']['retemplate'] = 'blog_withimg';
                $arr['body_data']['image'] = pic_cover_get($blog['pic'], $blog['picflag']);
            }
            
            $note_uid = $blog['uid'];
            $note_message = 'share_blog';
            $note_values = [
                'url' => "home.php?mod=space&uid=$blog[uid]&do=blog&id=$blog[blogid]",
                'subject' => $blog['subject'],
                'from_id' => $id,
                'from_idtype' => 'blogid'
            ];

            $hotarr = ['blogid', $blog['blogid'], $blog['hotuser']];

            break;

        case 'album':

            $feed_hash_data = "albumid{$id}";

            if (!$album = C::t('home_album')->fetch($id)) {
                showmessage('album_does_not_exist');
            }
            if ($album['friend']) {
                showmessage('album_can_not_share');
            }
            if (isblacklist($album['uid'])) {
                showmessage('is_blacklist');
            }
    
            $arr = [
                'itemid' => $id,
                'fromuid' => $album['uid'],
                'body_data' => [
                    'owner' => $album['username'],
                    'owner_link' => 'home.php?mod=space&uid='.$album['uid'],
                    'owner_avatar' => avatar($album['uid'], 'small', true),

                    'album' => $album['albumname'],
                    'album_desc' => $album['depict'],
                    'album_link' => "home.php?mod=space&uid=$album[uid]&do=album&id=$album[albumid]",
                    
                    'image_link' => pic_cover_get($album['pic'], $album['picflag']),
                    
                    'expend0' => '',
                    'expend1' => '',
                    'expend2' => '',
                    'expend3' => '',
                    'expend4' => '',
                    'expend5' => '',
                    'expend6' => '',
                    'expend7' => '',
                ],
            ];

            $note_uid = $album['uid'];
            $note_message = 'share_album';
            $note_values = [
                'url' => "home.php?mod=space&uid=$album[uid]&do=album&id=$album[albumid]",
                'albumname' => $album['albumname'],
                'from_id' => $id,
                'from_idtype' => 'albumid'
            ];

            break;

        case 'pic':

            $feed_hash_data = "picid{$id}";
            
            $pic = C::t('home_pic')->fetch($id);
            
            if (!$pic) {
                showmessage('image_does_not_exist');
            }
            
            $picfield = C::t('home_picfield')->fetch($id);
            $album = C::t('home_album')->fetch($pic['albumid']);
            $pic = array_merge($pic, $picfield, $album);
            
            if (in_array($pic['status'], [1, 2])) {
                showmessage('moderate_pic_not_share');
            }
            if ($pic['friend']) {
                showmessage('image_can_not_share');
            }
            if (isblacklist($pic['uid'])) {
                showmessage('is_blacklist');
            }
            if (empty($pic['albumid'])) {
                $pic['albumid'] = 0;
            };
            if (empty($pic['albumname'])) {
                $pic['albumname'] = lang('spacecp', 'default_albumname');
            };
    
            $arr = [
                'itemid' => $id,
                'fromuid' => $pic['uid'],
                'body_data' => [
                    'retemplate' => 'album_pic',
                    
                    'owner' => $pic['username'],
                    'owner_link' => 'home.php?mod=space&uid=' . $pic['uid'],
                    'owner_avatar' => avatar($pic['uid'], 'small', true),

                    'album' => $pic['albumname'],
                    'album_link' => 'home.php?mod=space&uid=' . $pic['uid'] . '&do=album&id=' . $pic['albumid'],

                    'image' => getstr($pic['title'] ? $pic['title'] : $pic['filename'], 100, 0, 0, 0, -1),
                    'image_link' => pic_get($pic['filepath'], 'album', $pic['thumb'], $pic['remote']),
                    'image_togo' => 'home.php?mod=space&uid=' . $pic['uid'] . '&do=album&picid=' . $pic['picid'],
                    
                    'expend0' => '',
                    'expend1' => '',
                    'expend2' => '',
                    'expend3' => '',
                    'expend4' => '',
                    'expend5' => '',
                    'expend6' => '',
                    'expend7' => '',
                ],
            ];
            
            $note_uid = $pic['uid'];
            $note_message = 'share_pic';
            $note_values = [
                'url' => 'home.php?mod=space&uid='.$pic['uid'].'&do=album&picid='.$pic['picid'],
                'albumname' => $pic['albumname'],
                'from_id' => $id,
                'from_idtype' => 'picid'
            ];

            $hotarr = ['picid', $pic['picid'], $pic['hotuser']];

            break;

        case 'thread':
    
            $feed_hash_data = "tid{$id}";
    
            require_once libfile('function/post');
    
            $thread = C::t('forum_thread')->fetch($id);
            
            if (in_array($thread['displayorder'], [-2, -3])) {
                showmessage('moderate_thread_not_share');
            }
            
            $post = C::t('forum_post')->fetch_threadpost_by_tid_invisible($id);
            $post['message'] = messagecutstr($post['message']);
            
            $attachment = !preg_match("/\[hide=?\d*\](.*?)\[\/hide\]/is", $post['message'], $a) && preg_match("/\[attach\]\d+\[\/attach\]/i", $a[1]);
            $attachment = $attachment ? C::t('forum_attachment_n')->fetch_max_image('tid:' . $id, 'tid', $id) : false;
    
            $arr = [
                'itemid' => $id,
                'fromuid' => $thread['authorid'],
                'body_data' => [
                    'url' => 'forum.php?mod=viewthread&tid='.$id,
                    'subject' => $thread['subject'],
                    'author'  => $thread['author'],
                    'author_avatar' => avatar($thread['authorid'], 'small', true),
                    'author_link'  => 'home.php?mod=space&uid='.$thread['authorid'],
                    'message' => $post['message'] ? getstr($post['message'], 150, 0, 0, 0, -1) : lang('template','should_redirect'),

                    'expend0' => '',
                    'expend1' => '',
                    'expend2' => '',
                    'expend3' => '',
                    'expend4' => '',
                    'expend5' => '',
                    'expend6' => '',
                    'expend7' => '',
                ]
            ];
            
            if ($attachment) {
                $arr['body_data']['retemplate'] = 'thread_withimg';
                $arr['body_data']['image'] = pic_get($attachment['attachment'], 'forum', $attachment['thumb'], $attachment['remote'], 1);
            }

            $note_uid = $thread['authorid'];
            $note_message = 'share_thread';
            $note_values = [
                'url' => "forum.php?mod=viewthread&tid=$id",
                'subject' => $thread['subject'],
                'from_id' => $id,
                'from_idtype' => 'tid',
            ];
    
            $actives = [
                'share' => ' class="active"'
            ];
            
            break;

        case 'article':
            
            $feed_hash_data = "articleid{$id}";
    
            require_once libfile('function/portal');

            $article = C::t('portal_article_title')->fetch($id);
            if (!$article) {
                showmessage('article_does_not_exist');
            }
            if (in_array($article['status'], [1, 2])) {
                showmessage('moderate_article_not_share');
            }

            $article_url = fetch_article_url($article);
    
            $arr = [
                'itemid' => $id,
                'fromuid' => $article['uid'],
                'body_data' => [
                    'url' => $article_url,
                    'title' => $article['title'],
                    'username' => $article['username'],
                    'user_avatar' => avatar($article['uid'], 'small', true),
                    'user_link' => 'home.php?mod=space&uid='.$article['uid'],
                    'summary' => getstr($article['summary'], 150, 0, 0, 0, -1),
                    
                    'expend0' => '',
                    'expend1' => '',
                    'expend2' => '',
                    'expend3' => '',
                    'expend4' => '',
                    'expend5' => '',
                    'expend6' => '',
                    'expend7' => '',
                ]
            ];
            
            if ($article['pic']) {
                $arr['body_data']['retemplate'] = 'article_withimg';
                $arr['body_data']['image'] = pic_get($article['pic'], 'portal', $article['thumb'], $article['remote'], 1, 1);
            }
            
            $note_uid = $article['uid'];
            $note_message = 'share_article';
            $note_values = [
                'url' => $article_url,
                'subject' => $article['title'],
                'from_id' => $id,
                'from_idtype' => 'aid'
            ];
    
            break;

        default:
            
            $type = 'link';
            
            $_G['refer'] = 'home.php?mod=space&uid=' . $_G['uid'] . '&do=share&view=me';
            
            $_GET['op'] = 'link';
            
            $linkdefault = 'http://';
            
            $generaldefault = '';
    
            $actives = [
                'share' => ' class="active"'
            ];
            
            break;
    }

	if(submitcheck('sharesubmit', 0, $seccodecheck, $secqaacheck)) {

		$magvalues = [];
		$redirecturl = '';
		$showmessagecontent = '';

		if($type == 'link') {

		    $link = trim($_POST['link']);

            $linkmatch = [];

            if (preg_match("/\.(mp4|mkv|avi|webm|3gp|wmv|mpg|vob|mov)$/i", $link)) {
                $type = 'video';
                $arr['body_data'] = [
                    'url' => $link,
                    
                    'expend0'  => '',
                    'expend1'  => '',
                    'expend2'  => '',
                    'expend3'  => '',
                    'expend4'  => '',
                    'expend5'  => '',
                    'expend6'  => '',
                    'expend7'  => '',
                ];
            } elseif (preg_match("/\.(mp3|wma|ogg|ape|flac|aac|ac3|mmf|amr|m4a|m4r|wav|wavpack|mp2)$/i", $link)) {
                $type = 'music';
                $arr['body_data'] = [
                    'url' => $link,
                    'name' => pathinfo($link)['filename'],
                    
                    'expend0'  => '',
                    'expend1'  => '',
                    'expend2'  => '',
                    'expend3'  => '',
                    'expend4'  => '',
                    'expend5'  => '',
                    'expend6'  => '',
                    'expend7'  => '',
                ];
            } elseif (preg_match("/\.(webp|jpg|jpeg|png|ico|bmp|gif|tif|tga)$/i", $link)) {
                $type = 'pic';
                $arr['body_data'] = [
                    'url' => $link,
                    'name' => pathinfo($link)['filename'],

                    'expend0'  => '',
                    'expend1'  => '',
                    'expend2'  => '',
                    'expend3'  => '',
                    'expend4'  => '',
                    'expend5'  => '',
                    'expend6'  => '',
                    'expend7'  => '',
                ];
            } elseif (preg_match("/\<iframe\s.*src=\"(.*?)\".*\>.*\<\/iframe\>/is", $link, $linkmatch)) {
                $hostmatchs = [];
                preg_match("/\/\/(.*?)\//is", $link, $hostmatchs);
                $support_url = [
                    'player.pptv.com',
                    'player.youku.com',
                    'v.douyu.com',
                    'liveshare.huya.com',
                    'tv.sohu.com',
                    'www.wasu.cn',
                    'open.iqiyi.com',
                    'v.qq.com',
                    'www.acfun.cn',
                    'player.bilibili.com',
                    'www.youtube.com',
                    'www.youtube-nocookie.com',
                    'player.vimeo.com',
                    'www.dailymotion.com',
                    'clips.twitch.tv',
                    'www.liveleak.com',
                ];
                if(in_array($hostmatchs[1],$support_url)){
                    $type = 'iframe';
                    $arr['body_data'] = [
                        'url' => $linkmatch[1],

                        'expend0'  => '',
                        'expend1'  => '',
                        'expend2'  => '',
                        'expend3'  => '',
                        'expend4'  => '',
                        'expend5'  => '',
                        'expend6'  => '',
                        'expend7'  => '',
                    ];
                } else {
                    showmessage('iframe_incorrect_format');
                }
            } else {
                preg_match("/((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.)[^\[\"']+/i", dhtmlspecialchars($link), $matches);

                $link = $matches[0] ? (preg_match("/^(http|https|ftp|mms)\:\/\/.{3,}$/i", $matches[0]) ? $matches[0] : '' ) : '' ;

                if(empty($link)) {
                    showmessage('url_incorrect_format');
                }
                $arr['itemid'] = '0';
                $arr['fromuid'] = '0';

                $arr['body_data'] = [
                    'url' => $link,
                    'name' => sub_url($link, 45),

                    'expend0'  => '',
                    'expend1'  => '',
                    'expend2'  => '',
                    'expend3'  => '',
                    'expend4'  => '',
                    'expend5'  => '',
                    'expend6'  => '',
                    'expend7'  => '',
                ];

                require_once libfile('function/discuzcode');

                $flashvar = parseflv($link);

                if(!empty($flashvar)) {
                    $type = 'video';

                    $arr['body_template'] = "";

                    $arr['body_data'] = [
                        'host' => 'flash',
                        'title' => geturltitle($link),
                        'url' => $link,
                        'imgurl' =>   $flashvar['imgurl'],
                        'flashvar' => $flashvar['flv'],

                        'expend0'  => '',
                        'expend1'  => '',
                        'expend2'  => '',
                        'expend3'  => '',
                        'expend4'  => '',
                        'expend5'  => '',
                        'expend6'  => '',
                        'expend7'  => '',
                    ];
                }
            }
		}

		if($_GET['iscomment'] && $_POST['general'] && $commentcable[$type] && $id) {

			$_POST['general'] = censor($_POST['general']);

			$currenttype = $commentcable[$type];
			$currentid = $id;

			if($currenttype == 'article') {
				$article = C::t('portal_article_title')->fetch($currentid);
				include_once libfile('function/portal');
				loadcache('portalcategory');
				$cat = $_G['cache']['portalcategory'][$article['catid']];
				$article['allowcomment'] = !empty($cat['allowcomment']) && !empty($article['allowcomment']) ? 1 : 0;
				if(!$article['allowcomment']) {
					showmessage('no_privilege_commentadd', '', [], array('return' => true));
				}
				if($article['idtype'] == 'blogid') {
					$currentid = $article['id'];
					$currenttype = 'blogid';
				} elseif($article['idtype'] == 'tid') {
					$currentid = $article['id'];
					$currenttype = 'thread';
				}
			}

			if($currenttype == 'thread') {
				if($commentcable[$type] == 'article') {
					$_POST['portal_referer'] = $article_url ? $article_url : 'portal.php?mod=view&aid='.$id;
				}


				$modpost = C::m('forum_post', $currentid);


				$params = array(
					'subject' => '',
					'message' => $_POST['general'],
				);

				$modpost->newreply($params);

				if($_POST['portal_referer']) {
					$redirecturl = $_POST['portal_referer'];
				} else {
					if($modnewreplies) {
						$redirecturl = "forum.php?mod=viewthread&tid=".$currentid;
					} else {
						$redirecturl = "forum.php?mod=viewthread&tid=".$currentid."&pid=".$modpost->pid."&page=".$modpost->param('page')."&extra=".$extra."#pid".$modpost->pid;
					}
				}
				$showmessagecontent = ($modnewreplies && $commentcable[$type] != 'article') ? 'do_success_thread_share_mod' : '';

			} elseif($currenttype == 'article') {

				if(!checkperm('allowcommentarticle')) {
					showmessage('group_nopermission', NULL, array('grouptitle' => $_G['group']['grouptitle']), array('login' => 1));
				}

				include_once libfile('function/spacecp');
				include_once libfile('function/portalcp');

				cknewuser();

				$waittime = interval_check('post');
				if($waittime > 0) {
					showmessage('operating_too_fast', '', array('waittime' => $waittime), array('return' => true));
				}

				$aid = intval($currentid);
				$message = $_POST['general'];

				$retmessage = addportalarticlecomment($aid, $message);
				if($retmessage != 'do_success') {
					showmessage($retmessage);
				}

			} elseif($currenttype == 'picid' || $currenttype == 'blogid') {

				if(!checkperm('allowcomment')) {
					showmessage('no_privilege_comment', '', [], array('return' => true));
				}
				cknewuser();
				$waittime = interval_check('post');
				if($waittime > 0) {
					showmessage('operating_too_fast', '', array('waittime' => $waittime), array('return' => true));
				}
				$message = getstr($_POST['general'], 0, 0, 0, 2);
				if(strlen($message) < 2) {
					showmessage('content_is_too_short', '', [], array('return' => true));
				}
				include_once libfile('class/bbcode');
				$bbcode = & bbcode::instance();

				require_once libfile('function/comment');
				$cidarr = add_comment($message, $currentid, $currenttype, 0);
				if($cidarr['cid']) {
					$magvalues['cid'] = $cidarr['cid'];
					$magvalues['id'] = $currentid;
				}
			}
			$magvalues['type'] = $commentcable[$type];
		}

		$arr['body_general'] = censor(getstr($_POST['general'], 500, 0, 0, 1));

		if(censormod($arr['body_general']) || $_G['group']['allowsharemod']) {
			$arr['status'] = 1;
		} else {
			$arr['status'] = 0;
		}

		$arr['type'] = $type;
		$arr['uid'] = $_G['uid'];
		$arr['username'] = $_G['username'];
		$arr['dateline'] = $_G['timestamp'];

		if($arr['status'] == 0 && ckprivacy('share', 'feed')) {
			require_once libfile('function/feed');
            feed_add([
                'icon'           => 'share',
                'type'           => $type,
                'title_template' => $arr['title_template'],
                'title_data'     => ['hash_data' => $feed_hash_data],
                'body_template'  => $arr['body_template'],
                'body_data'      => $arr['body_data'],
                'body_general'   => $arr['body_general'],
            ]);
		}

		$arr['body_data'] = serialize($arr['body_data']);

		$sid = C::t('home_share')->insert($arr, true);
        
        switch ($type) {
            case 'space':
                C::t('common_member_status')->increase($id, ['sharetimes' => 1]);
                break;
            case 'blog':
                C::t('home_blog')->increase($id, null, ['sharetimes' => 1]);
                break;
            case 'album':
                C::t('home_album')->update_num_by_albumid($id, 1, 'sharetimes');
                break;
            case 'pic':
                C::t('home_pic')->update_sharetimes($id);
                break;
            case 'article':
                C::t('portal_article_count')->increase($id, ['sharetimes' => 1]);
                break;
            case 'thread':
                C::t('forum_thread')->increase($id, ['sharetimes' => 1]);
                require_once libfile('function/forum');
                update_threadpartake($id);
                break;
        }
        
        if ($arr['status'] == 1) {
			updatemoderate('sid', $sid);
			manage_addnotify('verifyshare');
		}

		if($type == 'link' || !(C::t('home_share')->count_by_uid_itemid_type($_G['uid'], $id ? $id : '', $type ? $type : ''))) {
			include_once libfile('function/stat');
			updatestat('share');
		}

		if($note_uid && $note_uid != $_G['uid']) {
			notification_add($note_uid, 'sharenotice', $note_message, $note_values);
		}

		$needle = $id ? $type.$id : '';
		updatecreditbyaction('createshare', $_G['uid'], array('sharings' => 1), $needle);

		$referer = "home.php?mod=space&uid=$_G[uid]&do=share&view=$_GET[view]&from=$_GET[from]";
		$magvalues['sid'] = $sid;

		if(!$redirecturl) {
			$redirecturl = dreferer();
		}
		if(!$showmessagecontent) {
			$showmessagecontent = 'do_success';
		}
		showmessage($showmessagecontent, $redirecturl, $magvalues, ($_G['inajax'] && $_GET['view'] != 'me' ? array('showdialog'=>1, 'showmsg' => true, 'closetime' => true) : []));
	}

	$arr['body_data'] = serialize($arr['body_data']);

	require_once libfile('function/share');

	$arr = mkshare($arr);

	$arr['dateline'] = $_G['timestamp'];
}

if($type != 'link') {
	if((C::t('home_share')->count_by_uid_itemid_type($_G['uid'], $id ? $id : '', $type ? $type : ''))) {
		showmessage('spacecp_share_repeat');
	}
}

$share_count = C::t('home_share')->count_by_uid_itemid_type(0, $id ? $id : '', $type ? $type : '');

include template('home/spacecp_share');
?>