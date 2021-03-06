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
    
    class extend_thread_debate extends extend_thread_base {
        
        public $affirmpoint;
        public $negapoint;
        public $endtime;
        public $stand;
        
        public function before_newthread($parameters) {
            if (empty($_GET['affirmpoint']) || empty($_GET['negapoint'])) {
                showmessage('debate_position_nofound');
            } elseif (!empty($_GET['endtime']) && (!($this->endtime = @strtotime($_GET['endtime'])) || $this->endtime < TIMESTAMP)) {
                showmessage('debate_endtime_invalid');
            } elseif (!empty($_GET['umpire'])) {
                if (!C::t('common_member')->fetch_uid_by_username($_GET['umpire'])) {
                    $_GET['umpire'] = dhtmlspecialchars($_GET['umpire']);
                    showmessage('debate_umpire_invalid', '', ['umpire' => $_GET['umpire']]);
                }
            }
            $this->affirmpoint = censor(dhtmlspecialchars($_GET['affirmpoint']));
            $this->negapoint = censor(dhtmlspecialchars($_GET['negapoint']));
            $this->stand = intval($_GET['stand']);
            $this->param['extramessage'] = "\t" . $_GET['affirmpoint'] . "\t" . $_GET['negapoint'];
        }
        
        public function before_feed() {
            
            global $_G;
            
            $message = !$this->param['readperm'] ? $this->param['message'] : '';
            
            $this->feed = [
                'icon'           => 'debate',
                'title_template' => 'thread_debate',
                'title_data'     => [
                    'tid'   => $this->tid,
                    'tsub'  => $this->param['subject'],
                    'tlink' => 'forum.php?mod=viewthread&tid=' . $this->tid,
                ],
                'body_template'  => 'thread_debate',
                'body_data'      => [
                    'tid'   => $this->tid,
                    'tsub'  => $this->param['subject'],
                    'tlink' => 'forum.php?mod=viewthread&tid=' . $this->tid,
                    
                    'uid'     => $_G['uid'],
                    'uname'   => $_G['username'],
                    'ulink'   => 'home.php?mod=space&uid=' . $_G['uid'],
                    'uavatar' => avatar($_G['uid'], 'small', true),
                    
                    'message'     => messagecutstr($message, 120),
                    'affirmpoint' => messagecutstr($this->affirmpoint, 50),
                    'negapoint'   => messagecutstr($this->negapoint, 50),
                    
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
    
            getattach_img($this->tid,$this->pid,9,$this->feed['body_data']['imgs']);
            
        }
        
        public function before_replyfeed() {
            
            global $stand;
        
            if ($this->forum['allowfeed'] && !$this->param['isanonymous']) {
            
                $message = !$this->param['readperm'] ? $this->param['message'] : '';
                $message =  messagecutstr(messagesafeclear($message), 100);
            
                if ($this->param['special'] == 5 && $this->thread['authorid'] != $this->member['uid']) {
                
                    $role = $stand == 1 ? lang('feed','feed_vote_1')
                        : ($stand == 2 ? lang('feed','feed_vote_2') : lang('feed','feed_vote_1') );
                
                    $this->feed = [
                        'icon'           => 'debate',
                        'title_template' => 'thread_debate_vote',
                        'title_data'     => [
                            'tid'   => $this->thread['tid'],
                            'tsub'  => $this->thread['subject'],
                            'tlink' => 'forum.php?mod=viewthread&tid=' . $this->thread['tid'],
        
                            'stand' => $role,
                        ],
                        'body_template'  => 'thread_debate_vote',
                        'body_data'      => [
                            'tid'   => $this->thread['tid'],
                            'tsub'  => $this->thread['subject'],
                            'tlink' => 'forum.php?mod=viewthread&tid=' . $this->thread['tid'],

                            'message' => $message,

                            'original' => [],
        
                            'stand' => $role,
        
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
                    
                    getattach_img($this->thread['tid'],$this->pid,9,$this->feed['body_data']['imgs']);
    
                    // Thread data
                    require_once libfile('function/thread');
                    getThread_sample($this->thread['tid'], $this->feed['body_data']['original']);
                }
            }
        }
        
        public function after_newthread() {
            if ($this->group['allowpostdebate']) {
                
                C::t('forum_debate')->insert([
                    'tid'            => $this->tid,
                    'uid'            => $this->member['uid'],
                    'starttime'      => $this->param['publishdate'],
                    'endtime'        => $this->endtime,
                    'affirmdebaters' => 0,
                    'negadebaters'   => 0,
                    'affirmvotes'    => 0,
                    'negavotes'      => 0,
                    'umpire'         => $_GET['umpire'],
                    'winner'         => '',
                    'bestdebater'    => '',
                    'affirmpoint'    => $this->affirmpoint,
                    'negapoint'      => $this->negapoint,
                    'umpirepoint'    => '',
                ]);
                
            }
        }
        
        public function after_newreply() {
            global $firststand, $stand;
            if ($this->param['special'] == 5) {
                if (!$firststand) {
                    C::t('forum_debate')->update_debaters($this->thread['tid'], $stand);
                } else {
                    $stand = $firststand;
                }
                C::t('forum_debate')->update_replies($this->thread['tid'], $stand);
                C::t('forum_debatepost')->insert([
                    'tid'      => $this->thread['tid'],
                    'pid'      => $this->pid,
                    'uid'      => $this->member['uid'],
                    'dateline' => getglobal('timestamp'),
                    'stand'    => $stand,
                    'voters'   => 0,
                    'voterids' => '',
                ]);
            }
        }
        
        public function before_editpost($parameters) {
            $isfirstpost = $this->post['first'] ? 1 : 0;
            if ($isfirstpost) {
                if ($this->thread['special'] == 5 && $this->group['allowpostdebate']) {
                    
                    if (empty($_GET['affirmpoint']) || empty($_GET['negapoint'])) {
                        showmessage('debate_position_nofound');
                    } elseif (!empty($_GET['endtime']) && (!($endtime = @strtotime($_GET['endtime'])) || $endtime < TIMESTAMP)) {
                        showmessage('debate_endtime_invalid');
                    } elseif (!empty($_GET['umpire'])) {
                        if (!C::t('common_member')->fetch_uid_by_username($_GET['umpire'])) {
                            $_GET['umpire'] = dhtmlspecialchars($_GET['umpire']);
                            showmessage('debate_umpire_invalid');
                        }
                    }
                    $affirmpoint = censor(dhtmlspecialchars($_GET['affirmpoint']));
                    $negapoint = censor(dhtmlspecialchars($_GET['negapoint']));
                    C::t('forum_debate')->update($this->thread['tid'], [
                        'affirmpoint' => $affirmpoint,
                        'negapoint'   => $negapoint,
                        'endtime'     => $endtime,
                        'umpire'      => $_GET['umpire'],
                    ]);
                    
                }
            }
        }
    }