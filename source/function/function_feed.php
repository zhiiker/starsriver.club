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
    
    function feed_add($arr = []) {
        
        global $_G;
        
        if (!helper_access::check_module('feed') || empty($arr)) {
            return false;
        }
        
        $data = [
            'dateline'       => !empty($arr['dateline']) ? $arr['dateline'] : $_G['timestamp'],
            'icon'           => !empty($arr['icon']) ? $arr['icon'] : '',
            'type'           => !empty($arr['type']) ? $arr['type'] : '',
            'title_template' => !empty($arr['title_template']) ? $arr['title_template'] : '',
            'title_data'     => !empty($arr['title_data']) ? $arr['title_data'] : [],
            'body_template'  => !empty($arr['body_template']) ? $arr['body_template'] : '',
            'body_data'      => !empty($arr['body_data']) ? $arr['body_data'] : [],
            'body_general'   => !empty($arr['body_general']) ? $arr['body_general'] : '',
            'id'             => !empty($arr['id']) ? $arr['id'] : 0,
            'idtype'         => !empty($arr['idtype']) ? $arr['idtype'] : '',
            'target_ids'     => !empty($arr['target_ids']) ? $arr['target_ids'] : '',
            'uid'            => !empty($arr['uid']) ? $arr['uid'] : 0,
            'username'       => !empty($arr['username']) ? $arr['username'] : '',
            'friend'         => !empty($arr['friend']) ? $arr['friend'] : '',
            'returnid'       => !empty($arr['returnid']) ? $arr['returnid'] : 0,
        ];
        
        $date['body_data']['className'] = $data['body_template'];
        
        if (empty($data['uid']) || empty($data['username'])) $data['uid'] = $data['username'] = '';
        
        $feedarr = [
            'icon'           => $data['icon'],
            'type'           => $data['type'],
            'hash_data'      => empty($data['title_data']['hash_data']) ? '' : $data['title_data']['hash_data'],
            'dateline'       => $data['dateline'],
            'title_data'     => serialize($data['title_data']),
            'title_template' => $data['title_template'],
            'body_data'      => serialize($data['body_data']),
            'body_template'  => $data['body_template'],
            'body_general'   => $data['body_general'],
            'id'             => $data['id'],
            'idtype'         => $data['idtype'],
            'target_ids'     => $data['target_ids'],
            'uid'            => $data['uid'] ? intval($data['uid']) : $_G['uid'],
            'username'       => $data['username'] ? $data['username'] : $_G['username'],
            'friend'         => $data['friend'],
        ];
        /*
        if ($feedarr['hash_data']) {
            $oldfeed = C::t('home_feed')->fetch_feedid_by_hashdata($feedarr['uid'], $feedarr['hash_data']);
            if ($oldfeed) {
                return 0;
            }
        }
        */
        return C::t('home_feed')->insert($feedarr, $data['returnid']);
    }
    
    function mkfeed($feed, $actors = []) {
        global $_G;
        
        if ($feed['icon'] == 'share') {
            
            require_once libfile('function/share');
            $feed = mkshare($feed);
            
        } else {
    
            $feed['title_data'] = unserialize($feed['title_data']);
            $feed['body_data'] = unserialize($feed['body_data']);
    
            if(!empty($feed['body_data']['retemplate'])){
                $feed['title_template'] = $feed['body_data']['retemplate'];
                $feed['body_template'] = $feed['body_data']['retemplate'];
            }
            
            $feed['title_template'] = $feed['title_template'] ? lang('feed', 'feed_template_' . $feed['title_template'] . '_title') : '';
            $feed['body_template']  = $feed['body_template']  ? lang('feed', 'feed_template_' . $feed['body_template'] . '_body') : '';
            
            if ($feed['title_data']) {
                mkfeed_template($feed, $feed['title_template'],$feed['title_data'], $actors);
            }
            
            if ($feed['body_data']) {
                mkfeed_template($feed, $feed['body_template'],$feed['body_data'], $actors);
            }
            
            if($feed['body_data']['original']){
                $feed['body_data']['original']['template']  = $feed['body_data']['original']['template']  ? lang('feed', 'feed_template_' . $feed['body_data']['original']['template'] . '_body') : '';
                mkfeed_template($feed, $feed['body_data']['original']['template'],$feed['body_data']['original']['data']);
            }
            
            $feed['magic_class'] = !empty($feed['body_data']['magic_thunder']) ? 'magic-thunder' : '';
        }
        
        $feed['icon_image'] = IMGURL . "/feed/feed-type-icon/{$feed['icon']}.svg";
        
        $feed['new'] = 0;
        
        if ($_G['cookie']['home_readfeed'] && $feed['dateline'] + 300 > $_G['cookie']['home_readfeed']) {
            $feed['new'] = 1;
        }
        
        return $feed;
    }
    
    function mkfeed_template(&$feed, &$template, &$data, $actors = []) {
        $searchs = ['{actor}'];
        $replaces = [empty($actors) ? '<a class="user-tag" href="home.php?mod=space&uid='.$feed['uid'].'" target="_blank"><s class="avatar">'.avatar($feed['uid'],'').'</s><s class="username">'.$feed['username'].'</s></a>' : implode(lang('core', 'dot'), $actors)];
        foreach (array_keys($data) as $key) {
            $searchs[] = '{' . $key . '}';
            $replaces[] = $data[$key];
        }
        $template = str_replace($searchs, $replaces, $template);
    }
    
    
    function feed_publish($id, $idtype, $add = 0) {
        
        global $_G;
        
        if (!helper_access::check_module('feed') || empty($id)) {
            return 0;
        }
        
        $id = intval($id);
        
        $setarr = [];
        
        switch ($idtype) {
            case 'blogid':
                $value = array_merge(C::t('home_blog')->fetch($id), C::t('home_blogfield')->fetch($id));
                if ($value) {
                    if ($value['friend'] != 3) {
                        $status = $value['status'];
                        
                        $userurl = 'home.php?mod=space&uid='.$value['uid'];
                        $blogurl = $userurl.'&do=blog&id='.$value['blogid'];
                        $content = preg_replace("/&[a-z]+\;/i", '', $value['message']);
                        $template = $value['friend'] == 4 ? 'blog_passwd' : 'blog';
                        
                        $setarr = [
                            'icon'       => 'blog',
                            'dateline'   => time(),
                            'title_template' => $template,
                            'title_data' => [
                                'blogurl' => $blogurl,
                                'subject' => $value['subject'],
                            ],
                            'body_template' => $template,
                            'body_data' => [
                                'url' => $blogurl,
                                'blogid' => $value['blogid'],
                                'subject' => $value['subject'],
                                'content' => getstr($content, 150, 0, 0, 0, -1),
                                
                                'uid' => $value['uid'],
                                'username'   => $value['username'],
                                'user_link'   => $userurl,
                                'user_avatar' => avatar($value['uid'],'small',true),
                            ],
                            'id'         => $value['blogid'],
                            'idtype'     => $idtype,
                            'uid'        => $value['uid'],
                            'username'   => $value['username'],
                            'friend'     => $value['friend'],
                            'hot'        => $value['hot'],
                        ];
                        
                        if ($value['pic']) {
                            $setarr['body_data']['retemplate'] = $template.'_withimg';
                            $setarr['body_data']['image'] = pic_cover_get($value['pic'], $value['picflag']);
                        }
                    }
                }
                break;
                
            case 'albumid':
                $key = 1;
                if ($id > 0) {
                    $pics = C::t('home_pic')->fetch_all_by_albumid($id, 0, 9, 0, 0, 1);
                    $album = C::t('home_album')->fetch($id);
                    foreach ($pics as $pic) {
                        if ($pic['friend'] <= 2) {
                            if (empty($setarr['icon'])) {
                                $status = $pic['status'];
                                $userurl = 'home.php?mod=space&uid=' . $pic['uid'];
                                $albumurl = $userurl . '&do=album&id=' . $id;
    
                                $setarr = [
                                    'icon'           => 'album',
                                    'dateline'       => time(),
                                    'title_template' => 'album',
                                    'body_template'  => 'album',
                                    'body_data'      => [
                                        'album'      => $album['albumname'],
                                        'album_link' => $albumurl,
                                        'picnum'     => $album['picnum'],
                                    ],
                                    'id'             => $id,
                                    'idtype'         => $idtype,
                                    'uid'            => $pic['uid'],
                                    'username'       => $pic['username'],
                                    'friend'         => $album['friend'],
                                ];
                            }
                            
                            $setarr['body_data']['imgs'][] = [
                                'img' => pic_get($pic['filepath'], 'album', $pic['thumb'], $pic['remote']),
                                'img_id' => $pic['picid'],
                                'img_url' => 'home.php?mod=space&uid='.$pic['uid'].'&do=album&picid='.$pic['picid'],
                                'img_name' => $pic['title'] ? $pic['title'] : $pic['filename']
                            ];
                    
                            $key++;
                    
                        } else {
                            break;
                        }
                    }
                }
                break;
                
            case 'picid':
                $plussql = $id > 0 ? 'p.' . DB::field('picid', $id) : 'p.' . DB::field('uid', $_G['uid']) . ' ORDER BY dateline DESC LIMIT 1';
                $query = C::t('home_pic')->fetch_all_by_sql($plussql);
                if ($value = $query[0]) {
                    if (empty($value['friend'])) {
                        $status = $value['status'];
    
                        $album = C::t('home_album')->fetch($value['albumid']);
                        $userurl = 'home.php?mod=space&uid='.$value['uid'];
                        $imgurl = $userurl . '&do=album&picid=' . $value['picid'];
                        $albumurl = $userurl . '&do=album&id=' . $value['albumid'];
                        
                        $setarr = [
                            'icon'           => 'pic',
                            'dateline'       => time(),
                            'hot'            => $value['hot'],
                            'title_template' => 'pic',
                            'title_data'      => [
                                'url'         => $imgurl,
                                'image'       => $value['title'] ? $value['title'] : $value['filename'],
                            ],
                            'body_template'  => 'pic',
                            'body_data'      => [
                                'uid'         => $value['uid'],
                                'username'    => $value['username'],
                                'user_link'   => $userurl,
                                'user_avatar' => avatar($value['uid'],'small',true),
                                
                                'image'       => $value['title'] ? $value['title'] : $value['filename'],
                                'image_togo'  => $imgurl,
                                'image_link'  => pic_get($value['filepath'], 'album', $value['thumb'], $value['remote']),
                                
                                'album'       => $album['albumname'],
                                'album_link'  => $albumurl,
                            ],
                            'id'             => $value['picid'],
                            'idtype'         => $idtype,
                            'uid'            => $value['uid'],
                            'username'       => $value['username'],
                            'friend'         => $album['friend'],
                        ];
                    }
                }
                break;
        }
        
        if ($setarr['icon']) {
            
            $setarr['title_data']['hash_data'] = "{$idtype}{$id}";
            $setarr['title_data'] = serialize($setarr['title_data']);
            $setarr['body_data'] = serialize($setarr['body_data']);
            
            $feedid = 0;
            
            if (!$add && $setarr['id']) {
                $feedid = C::t('home_feed')->fetch($id, $idtype)['feedid'];
            }
            if ($status == 0) {
                if ($feedid) {
                    C::t('home_feed')->update('', $setarr, '', '', $feedid);
                } else {
                    C::t('home_feed')->insert($setarr);
                }
            }
        }
    }