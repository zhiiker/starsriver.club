<?php
/********************************************************************
 * Copyright (c) 2020 All Right Reserved By [StarsRiver]            *
 *                                                                  *
 * Author  Zhangyu                                                  *
 * Email   starsriver@yahoo.com                                     *
 ********************************************************************/
    
    if (!defined('IN_DISCUZ')) {
        exit('Access Denied');
    }
    
    function add_comment($message, $id, $idtype, $cid = 0) {
        global $_G, $bbcode;
        
        $allowcomment = false;
        
        switch ($idtype) {
            case 'uid':
                $allowcomment = helper_access::check_module('wall');
                break;
            case 'sid':
                $allowcomment = helper_access::check_module('share');
                break;
            case 'picid':
                $allowcomment = helper_access::check_module('album');
                break;
            case 'blogid':
                $allowcomment = helper_access::check_module('blog');
                break;
        }
        
        if (!$allowcomment) {
            showmessage('quickclear_noperm');
        }
        
        $summay = getstr($message, 150, 0, 0, 0, -1);
        
        $comment = [];
        if ($cid) {
            $comment = C::t('home_comment')->fetch_by_id_idtype($id, $idtype, $cid);
            if ($comment && $comment['authorid'] != $_G['uid']) {
                $comment['message'] = preg_replace("/\<blockquote\>.*?\<\/blockquote\>/is", '', $comment['message']);
                $comment['message'] = $bbcode->html2bbcode($comment['message']);
                $message = ("<blockquote>" . $comment['author'] . ": " . getstr($comment['message'], 150, 0, 0, 2, 1) . '</blockquote>') . $message;
                if ($comment['idtype'] == 'uid') {
                    $id = $comment['authorid'];
                }
            } else {
                $comment = [];
            }
        }
        
        $stattype = '';
        $hotarr = $tospace = $pic = $blog = $album = $share = $poll = [];
        
        switch ($idtype) {
            case 'uid':
                $tospace = getuserbyuid($id);
                $stattype = 'wall';
                break;
            
            case 'sid':
                $share = C::t('home_share')->fetch($id);
                if (empty($share)) {
                    showmessage('sharing_does_not_exist');
                }
                
                $tospace = getuserbyuid($share['uid']);
                
                $hotarr = [
                    'sid',
                    $share['sid'],
                    $share['hotuser'],
                ];
                $stattype = 'sharecomment';
                break;
            
            case 'picid':
                $pic = C::t('home_pic')->fetch($id);
                if (empty($pic)) {
                    showmessage('view_images_do_not_exist');
                }
                $picfield = C::t('home_picfield')->fetch($id);
                $pic['hotuser'] = $picfield['hotuser'];
                $tospace = getuserbyuid($pic['uid']);
                $album = [];
                if ($pic['albumid']) {
                    $query = C::t('home_album')->fetch($pic['albumid']);
                    if (!$query['albumid']) {
                        C::t('home_pic')->update_for_albumid($albumid, ['albumid' => 0]);
                    }
                }
                if (!ckfriend($album['uid'], $album['friend'], $album['target_ids'])) {
                    showmessage('no_privilege_ckfriend_pic');
                } elseif (!$tospace['self'] && $album['friend'] == 4) {
                    $cookiename = "view_pwd_album_$album[albumid]";
                    $cookievalue = empty($_G['cookie'][$cookiename]) ? '' : $_G['cookie'][$cookiename];
                    if ($cookievalue != md5(md5($album['password']))) {
                        showmessage('no_privilege_ckpassword_pic');
                    }
                }
                $hotarr = [
                    'picid',
                    $pic['picid'],
                    $pic['hotuser'],
                ];
                $stattype = 'piccomment';
                break;
            
            case 'blogid':
                $blog = array_merge(C::t('home_blog')->fetch($id), C::t('home_blogfield')->fetch($id));
                if (empty($blog)) {
                    showmessage('view_to_info_did_not_exist');
                }
                
                $tospace = getuserbyuid($blog['uid']);
                
                if (!ckfriend($blog['uid'], $blog['friend'], $blog['target_ids'])) {
                    showmessage('no_privilege_ckfriend_blog');
                } elseif (!$tospace['self'] && $blog['friend'] == 4) {
                    $cookiename = "view_pwd_blog_$blog[blogid]";
                    $cookievalue = empty($_G['cookie'][$cookiename]) ? '' : $_G['cookie'][$cookiename];
                    if ($cookievalue != md5(md5($blog['password']))) {
                        showmessage('no_privilege_ckpassword_blog');
                    }
                }
                
                if (!empty($blog['noreply'])) {
                    showmessage('do_not_accept_comments');
                }
                if ($blog['target_ids']) {
                    $blog['target_ids'] .= ",$blog[uid]";
                }
                
                $hotarr = [
                    'blogid',
                    $blog['blogid'],
                    $blog['hotuser'],
                ];
                $stattype = 'blogcomment';
                break;
            
            default:
                showmessage('non_normal_operation');
                break;
        }
        if (empty($tospace)) {
            showmessage('space_does_not_exist', '', [], ['return' => true]);
        }
        
        if (isblacklist($tospace['uid'])) {
            showmessage('is_blacklist');
        }
        
        if ($hotarr && $tospace['uid'] != $_G['uid']) {
            hot_update($hotarr[0], $hotarr[1], $hotarr[2]);
        }
        
        $fs = [
            'icon'         => 'comment',
            'body_general' => $summay,
        ];
        
        switch ($idtype) {
            case 'uid':
                $fs['icon'] = 'wall';
                $fs['title_template'] = 'comment_space';
                $fs['title_data'] = [
                    'to_uid'     => $tospace['uid'],
                    'to_uname'   => $tospace['username'],
                    'to_ulink'   => 'home.php?mod=space&uid=' . $tospace['uid'],
                    'to_uavatar' => avatar($tospace['uid'], 'small', true),
                ];
                break;
            
            case 'sid':
                $fs['title_template'] = 'comment_share';
                $fs['title_data'] = [
                    'to_uid'     => $tospace['uid'],
                    'to_uname'   => $tospace['username'],
                    'to_ulink'   => 'home.php?mod=space&uid=' . $tospace['uid'],
                    'to_uavatar' => avatar($tospace['uid'], 'small', true),
                    'share_url'  => 'home.php?mod=space&uid=' . $tospace['uid'] . '&do=share&id=' . $id,
                    'share_act'  => str_replace(lang('spacecp', 'share_action'), '', lang('share', 'share_title_template_' . $share['type'])),
                ];
                break;
            
            case 'picid':
                $fs['title_template'] = 'comment_image';
                $fs['title_data'] = [
                    'to_uid'     => $tospace['uid'],
                    'to_uname'   => $tospace['username'],
                    'to_ulink'   => 'home.php?mod=space&uid=' . $tospace['uid'],
                    'to_uavatar' => avatar($tospace['uid'], 'small', true),
                ];
                $fs['body_template'] = 'comment_image';
                $fs['body_data'] = [
                    'to_uid'     => $tospace['uid'],
                    'to_uname'   => $tospace['username'],
                    'to_ulink'   => 'home.php?mod=space&uid=' . $tospace['uid'],
                    'to_uavatar' => avatar($tospace['uid'], 'small', true),
                    
                    'image_link' => pic_get($pic['filepath'], 'album', $pic['thumb'], $pic['remote']),
                    'image_togo' => 'home.php?mod=space&uid=' . $tospace['uid'] . '&do=album&picid=' . $pic['picid'],
                    
                    'expend0' => '',
                    'expend1' => '',
                    'expend2' => '',
                    'expend3' => '',
                    'expend4' => '',
                    'expend5' => '',
                    'expend6' => '',
                    'expend7' => '',
                ];
                $fs['target_ids'] = $album['target_ids'];
                $fs['friend'] = $album['friend'];
                break;
            
            case 'blogid':
                C::t('home_blog')->increase($id, 0, ['replynum' => 1]);
                $fs['title_template'] = 'comment_blog';
                $fs['title_data'] = [
                    'to_uid'     => $tospace['uid'],
                    'to_uname'   => $tospace['username'],
                    'to_ulink'   => 'home.php?mod=space&uid=' . $tospace['uid'],
                    'to_uavatar' => avatar($tospace['uid'], 'small', true),
                    'blog_url'   => 'home.php?mod=space&uid=' . $tospace['uid'] . '&do=blog&id=' . $id,
                    'blog_sub'   => $blog['subject'],
                ];
                $fs['body_template'] = 'comment_blog';
                $fs['body_data'] = [
                    'to_uid'     => $tospace['uid'],
                    'to_uname'   => $tospace['username'],
                    'to_ulink'   => 'home.php?mod=space&uid=' . $tospace['uid'],
                    'to_uavatar' => avatar($tospace['uid'], 'small', true),
                    
                    'blog_url'     => 'home.php?mod=space&uid=' . $tospace['uid'] . '&do=blog&id=' . $id,
                    'blog_sub'     => $blog['subject'],
                    'blog_content' => getstr($blog['message'], 50, 0, 0, 0, -1),
                    
                    'expend0' => '',
                    'expend1' => '',
                    'expend2' => '',
                    'expend3' => '',
                    'expend4' => '',
                    'expend5' => '',
                    'expend6' => '',
                    'expend7' => '',
                ];
                
                if (!empty($blog['pic'])) {
                    $fs['body_data']['retemplate'] = 'comment_blog_withimg';
                    $fs['body_data']['image'] = pic_cover_get($blog['pic'], $blog['picflag']);
                }
                
                $fs['target_ids'] = $blog['target_ids'];
                $fs['friend'] = $blog['friend'];
                
                break;
            
        }
        
        $message = censor($message);
        if (censormod($message)) {
            $comment_status = 1;
        } else {
            $comment_status = 0;
        }
        
        $setarr = [
            'uid'      => $tospace['uid'],
            'id'       => $id,
            'idtype'   => $idtype,
            'authorid' => $_G['uid'],
            'author'   => $_G['username'],
            'dateline' => $_G['timestamp'],
            'message'  => $message,
            'ip'       => $_G['clientip'],
            'port'     => $_G['remoteport'],
            'status'   => $comment_status,
        ];
        
        $cid = C::t('home_comment')->insert($setarr, true);
        
        $action = 'comment';
        $becomment = 'getcomment';
        $note = $q_note = '';
        $note_values = $q_values = [];
        
        switch ($idtype) {
            case 'uid':
                $n_url = "home.php?mod=space&uid=$tospace[uid]&do=wall&cid=$cid";
                
                $note_type = 'wall';
                $note = 'wall';
                $note_values = ['url' => $n_url];
                $q_note = 'wall_reply';
                $q_values = ['url' => $n_url];
                
                if ($comment) {
                    $msg = 'note_wall_reply_success';
                    $magvalues = ['username' => $tospace['username']];
                    $becomment = '';
                } else {
                    $msg = 'do_success';
                    $magvalues = [];
                    $becomment = 'getguestbook';
                }
                
                $action = 'guestbook';
                break;
            case 'picid':
                $n_url = "home.php?mod=space&uid=$tospace[uid]&do=album&picid=$id&cid=$cid";
                
                $note_type = 'comment';
                $note = 'pic_comment';
                $note_values = ['url' => $n_url];
                $q_note = 'pic_comment_reply';
                $q_values = ['url' => $n_url];
                
                $msg = 'do_success';
                $magvalues = [];
                
                break;
            case 'blogid':
                $n_url = "home.php?mod=space&uid=$tospace[uid]&do=blog&id=$id&cid=$cid";
                
                $note_type = 'comment';
                $note = 'blog_comment';
                $note_values = ['url'     => $n_url,
                                'subject' => $blog['subject'],
                ];
                $q_note = 'blog_comment_reply';
                $q_values = ['url' => $n_url];
                
                $msg = 'do_success';
                $magvalues = [];
                
                break;
            case 'sid':
                $n_url = "home.php?mod=space&uid=$tospace[uid]&do=share&id=$id&cid=$cid";
                
                $note_type = 'comment';
                $note = 'share_comment';
                $note_values = ['url' => $n_url];
                $q_note = 'share_comment_reply';
                $q_values = ['url' => $n_url];
                
                $msg = 'do_success';
                $magvalues = [];
                
                break;
        }
        
        if (empty($comment)) {
            
            if ($tospace['uid'] != $_G['uid']) {
                if (ckprivacy('comment', 'feed')) {
                    $fs['title_data']['hash_data'] = "{$idtype}{$id}";
                    
                    require_once libfile('function/feed');
                    feed_add([
                        'icon'           => $fs['icon'],
                        'title_template' => $fs['title_template'],
                        'title_data'     => $fs['title_data'],
                        'body_template'  => $fs['body_template'],
                        'body_data'      => $fs['body_data'],
                        'body_general'   => $fs['body_general'],
                        'target_ids'     => $fs['target_ids'],
                        'friend'         => $fs['friend'],
                    ]);
                }
                
                $note_values['from_id'] = $id;
                $note_values['from_idtype'] = $idtype;
                $note_values['url'] .= "&goto=new#comment_{$cid}_li";
                
                notification_add($tospace['uid'], $note_type, $note, $note_values);
            }
            
        } elseif ($comment['authorid'] != $_G['uid']) {
            notification_add($comment['authorid'], $note_type, $q_note, $q_values);
        }
        
        if ($comment_status == 1) {
            updatemoderate($idtype . '_cid', $cid);
            manage_addnotify('verifycommontes');
        }
        if ($stattype) {
            include_once libfile('function/stat');
            updatestat($stattype);
        }
        if ($tospace['uid'] != $_G['uid']) {
            $needle = $id;
            if ($idtype != 'uid') {
                $needle = $idtype . $id;
            } else {
                $needle = $tospace['uid'];
            }
            updatecreditbyaction($action, 0, [], $needle);
            if ($becomment) {
                if ($idtype == 'uid') {
                    $needle = $_G['uid'];
                }
                updatecreditbyaction($becomment, $tospace['uid'], [], $needle);
            }
        }
        
        C::t('common_member_status')->update($_G['uid'], ['lastpost' => $_G['timestamp']], 'UNBUFFERED');
        $magvalues['cid'] = $cid;
        
        return ['cid'       => $cid,
                'msg'       => $msg,
                'magvalues' => $magvalues,
        ];
    }

?>