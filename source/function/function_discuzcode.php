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
    
    global $_G;
    
    include template('forum/discuzcode');
    
    $_G['forum_discuzcode'] = [
        'pcodecount'      => -1,
        'codecount'       => 0,
        'codehtml'        => [],
        'passwordlock'    => [],
        'smiliesreplaced' => 0,
        'seoarray'        => [
            0 => '',
            1 => $_SERVER['HTTP_HOST'],
            2 => $_G['setting']['bbname'],
            3 => str_replace('{bbname}', $_G['setting']['bbname'], $_G['setting']['seotitle']),
            4 => $_G['setting']['seokeywords'],
            5 => $_G['setting']['seodescription'],
        ],
    ];
    
    if (!isset($_G['cache']['bbcodes']) || !is_array($_G['cache']['bbcodes']) || !is_array($_G['cache']['smilies'])) {
        loadcache([
            'bbcodes',
            'smilies',
            'smileytypes',
        ]);
    }
    
    function creditshide($creditsrequire, $message, $pid, $authorid) {
        global $_G;
        if ($_G['member']['credits'] >= $creditsrequire || $_G['forum']['ismoderator'] || $_G['uid'] && $authorid == $_G['uid']) {
            return tpl_hide_credits($creditsrequire, str_replace('\\"', '"', $message));
        } else {
            return tpl_hide_credits_hidden($creditsrequire);
        }
    }
    
    function expirehide($expiration, $creditsrequire, $message, $dateline) {
        $expiration = $expiration ? substr($expiration, 1) : 0;
        if ($expiration && $dateline && (TIMESTAMP - $dateline) / 86400 > $expiration) {
            return str_replace('\\"', '"', $message);
        }
        return '[hide' . ($creditsrequire ? "=$creditsrequire" : '') . ']' . str_replace('\\"', '"', $message) . '[/hide]';
    }
    
    function codedisp($code) {
        global $_G;
        $_G['forum_discuzcode']['pcodecount']++;
        $code = dhtmlspecialchars(str_replace('\\"', '"', $code));
        //$code = str_replace("\n", "<li>", $code);
        $_G['forum_discuzcode']['codehtml'][$_G['forum_discuzcode']['pcodecount']] = tpl_codedisp($code);
        $_G['forum_discuzcode']['codecount']++;
        return "[\tkaf:element:" . $_G['forum_discuzcode']['pcodecount'] . "\t]";
    }
    
    function discuzcode($message, $smileyoff = false, $bbcodeoff = false, $htmlon = 0, $allowsmilies = 1, $allowbbcode = 1, $allowimgcode = 1, $allowhtml = 0, $jammer = 0, $parsetype = '0', $authorid = '0', $allowmediacode = '0', $pid = 0, $lazyload = 0, $pdateline = 0, $first = 0) {
        global $_G;
        
        static $authorreplyexist;
        
        if ($pid && strpos($message, '[/password]') !== false) {
            if ($authorid != $_G['uid'] && !$_G['forum']['ismoderator']) {
                $message = preg_replace_callback("/\s?\[password\](.+?)\[\/password\]\s?/i", create_function('$matches', 'return parsepassword($matches[1], ' . intval($pid) . ');'), $message);
                if ($_G['forum_discuzcode']['passwordlock'][$pid]) {
                    return '';
                }
            } else {
                $message = preg_replace("/\s?\[password\](.+?)\[\/password\]\s?/i", "", $message);
                $_G['forum_discuzcode']['passwordauthor'][$pid] = 1;
            }
        }
        
        if ($parsetype != 1 && !$bbcodeoff && $allowbbcode && (strpos($message, '[/code]') || strpos($message, '[/CODE]')) !== false) {
            $message = preg_replace_callback("/\s?\[code\](.+?)\[\/code\]\s?/is", 'discuzcode_callback_codedisp_1', $message);
        }
        
        $msglower = strtolower($message);
        
        $htmlon = $htmlon && $allowhtml ? 1 : 0;
        
        if (!$htmlon) {
            $message = dhtmlspecialchars($message);
        } else {
            $message = preg_replace("/<script[^\>]*?>(.*?)<\/script>/i", '', $message);
        }
        
        if ($_G['setting']['plugins']['func'][HOOKTYPE]['discuzcode']) {
            $_G['discuzcodemessage'] = &$message;
            $param = func_get_args();
            hookscript('discuzcode', 'global', 'funcs', [
                'param'  => $param,
                'caller' => 'discuzcode',
            ], 'discuzcode');
        }
        
        if (!$smileyoff && $allowsmilies) {
            $message = parsesmiles($message);
        }
        
        if ($_G['setting']['allowattachurl'] && strpos($msglower, 'attach://') !== false) {
            $message = preg_replace_callback("/attach:\/\/(\d+)\.?(\w*)/i", 'discuzcode_callback_parseattachurl_12', $message);
        }
        
        if ($allowbbcode) {
            if (strpos($msglower, 'ed2k://') !== false) {
                $message = preg_replace_callback("/ed2k:\/\/(.+?)\//", 'discuzcode_callback_parseed2k_1', $message);
            }
        }
        
        if (!$bbcodeoff && $allowbbcode) {
            if (strpos($msglower, '[/url]') !== false) {
                $message = preg_replace_callback("/\[url(=((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.|mailto:)?([^\r\n\[\"']+?))?\](.+?)\[\/url\]/is", 'discuzcode_callback_parseurl_152', $message);
            }
            if (strpos($msglower, '[/email]') !== false) {
                $message = preg_replace_callback("/\[email(=([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+))?\](.+?)\[\/email\]/is", 'discuzcode_callback_parseemail_14', $message);
            }
            
            $nest = 0;
            while (strpos($msglower, '[table') !== false && strpos($msglower, '[/table]') !== false) {
                $message = preg_replace_callback("/\[table(?:=(\d{1,4}%?)(?:,([\(\)%,#\w ]+))?)?\]\s*(.+?)\s*\[\/table\]/is", 'discuzcode_callback_parsetable_123', $message);
                if (++$nest > 4)
                    break;
            }
            
            $message = str_replace([
                '[/color]', '[/backcolor]', '[/size]', '[/font]', '[/align]', '[/float]', '[/p]',
                '[i=s]',
                '[b]', '[/b]',
                '[i]', '[/i]',
                '[s]', '[/s]',
                '[u]', '[/u]',
                '[indent]', '[/indent]',
                '[hr]',
                '[list]',
                '[list=1]',
                '[list=a]',
                '[list=A]',
                "\r\n[*]",
                '[*]',
                '[/list]',
            ], [
                '</kaf>', '</kaf>', '</kaf>', '</kaf>', '</kaf>', '</kaf>', '</kaf>',
                '<kaf class="pstatus">',
                '<kaf type="text" class="bold">', '</kaf>',
                '<kaf type="text" class="italic">', '</kaf>',
                '<kaf type="text" class="overline">', '</kaf>',
                '<kaf type="text" class="underline">', '</kaf>',
                '<kaf type="para" class="quote">', '</kaf>',
                '<hr/>',
                '<ul>',
                '<ul class="lister-type-number">',
                '<ul class="lister-type-dotted">',
                '<ul class="lister-type-square">',
                '<li>',
                '<li>',
                '</ul>',
            ], preg_replace([
                "/\[color=([#\w]+?)\]/i",
                "/\[color=((rgb|rgba)\([\d\s,]+?\))\]/i",
                "/\[backcolor=([#\w]+?)\]/i",
                "/\[backcolor=((rgb|rgba)\([\d\s,]+?\))\]/i",
                "/\[size=(\d{1,2}?)\]/i",
                "/\[size=(\d{1,2}(\.\d{1,2}+)?(px|pt)+?)\]/i",
                "/\[font=([^\[\<]+?)\]/i",
                "/\[align=(left|center|right)\]/i",
                "/\[float=left\]/i",
                "/\[float=right\]/i",
                "/\[p=(\d{1,2}|null), (\d{1,2}|null), (left|center|right)\]/i",
            ], [
                '<kaf type="text" style="color:\\1;">',
                '<kaf type="text" style="color:\\1;">',
                '<kaf type="text" style="background-color:\\1;">',
                '<kaf type="text" style="background-color:\\1;">',
                '<kaf type="text" style="font-size:\\1;">',
                '<kaf type="text" style="font-size:\\1;">',
                '<kaf type="text" style="font-family:\\1, Roboto, sans-serif !important;">',
                '<kaf type="para" style="text-align:\\1;">',
                '<kaf type="text" style="float:left;>',
                '<kaf type="text" style="float:right;>',
                '<kaf type="para" style="line-height:\\1px;text-indent:\\2em;text-align:\\3">',
            ], $message));
            
            if ($pid && !defined('IN_MOBILE')) {
                $message = preg_replace_callback("/\s?\[postbg\]\s*([^\[\<\r\n;'\"\?\(\)]+?)\s*\[\/postbg\]\s?/is", create_function('$matches', 'return parsepostbg($matches[1], ' . intval($pid) . ');'), $message);
            } else {
                $message = preg_replace("/\s?\[postbg\]\s*([^\[\<\r\n;'\"\?\(\)]+?)\s*\[\/postbg\]\s?/is", "", $message);
            }
            
            if ($parsetype != 1) {
                if (strpos($msglower, '[/quote]') !== false) {
                    $message = preg_replace("/\s?\[quote\][\n\r]*(.+?)[\n\r]*\[\/quote\]\s?/is", tpl_quote(), $message);
                }
                if (strpos($msglower, '[/free]') !== false) {
                    $message = preg_replace("/\s*\[free\][\n\r]*(.+?)[\n\r]*\[\/free\]\s*/is", tpl_free(), $message);
                }
            }
            if (!defined('IN_MOBILE') || !in_array(constant('IN_MOBILE'), [
                    '1',
                    '3',
                    '4',
                ])) {
                if (strpos($msglower, '[/media]') !== false) {
                    $message = preg_replace_callback("/\[media=([\w,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/is", $allowmediacode ? 'discuzcode_callback_parsemedia_12' : 'discuzcode_callback_bbcodeurl_2', $message);
                }
                if (strpos($msglower, '[/audio]') !== false) {
                    $message = preg_replace_callback("/\[audio(=1)*\]\s*([^\[\<\r\n]+?)\s*\[\/audio\]/is", $allowmediacode ? 'discuzcode_callback_parseaudio_2' : 'discuzcode_callback_bbcodeurl_2', $message);
                }
            } else {
                if (strpos($msglower, '[/media]') !== false) {
                    $message = preg_replace("/\[media=([\w,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/is", "[media]\\2[/media]", $message);
                }
                if (strpos($msglower, '[/audio]') !== false) {
                    $message = preg_replace("/\[audio(=1)*\]\s*([^\[\<\r\n]+?)\s*\[\/audio\]/is", "[media]\\2[/media]", $message);
                }
            }
            
            if ($parsetype != 1 && $allowbbcode < 0 && isset($_G['cache']['bbcodes'][-$allowbbcode])) {
                $message = preg_replace($_G['cache']['bbcodes'][-$allowbbcode]['searcharray'], $_G['cache']['bbcodes'][-$allowbbcode]['replacearray'], $message);
            }
            if ($parsetype != 1 && strpos($msglower, '[/hide]') !== false && $pid) {
                if ($_G['setting']['hideexpiration'] && $pdateline && (TIMESTAMP - $pdateline) / 86400 > $_G['setting']['hideexpiration']) {
                    $message = preg_replace("/\[hide[=]?(d\d+)?[,]?(\d+)?\]\s*(.*?)\s*\[\/hide\]/is", "\\3", $message);
                    $msglower = strtolower($message);
                }
                if (strpos($msglower, '[hide=d') !== false) {
                    $message = preg_replace_callback("/\[hide=(d\d+)?[,]?(\d+)?\]\s*(.*?)\s*\[\/hide\]/is", create_function('$matches', 'return expirehide($matches[1], $matches[2], $matches[3], ' . intval($pdateline) . ');'), $message);
                    $msglower = strtolower($message);
                }
                if (strpos($msglower, '[hide]') !== false) {
                    if ($authorreplyexist === null) {
                        if (!$_G['forum']['ismoderator']) {
                            if ($_G['uid']) {
                                $_post = C::t('forum_post')->fetch('tid:' . $_G['tid'], $pid);
                                $authorreplyexist = $_post['tid'] == $_G['tid'] ? C::t('forum_post')->fetch_pid_by_tid_authorid($_G['tid'], $_G['uid']) : false;
                            }
                        } else {
                            $authorreplyexist = true;
                        }
                    }
                    if ($authorreplyexist) {
                        $message = preg_replace("/\[hide\]\s*(.*?)\s*\[\/hide\]/is", tpl_hide_reply(), $message);
                    } else {
                        $message = preg_replace("/\[hide\](.*?)\[\/hide\]/is", tpl_hide_reply_hidden(), $message);
                        $message = '<script>replyreload += \',\' + ' . $pid . ';</script>' . $message;
                    }
                }
                if (strpos($msglower, '[hide=') !== false) {
                    $message = preg_replace_callback("/\[hide=(\d+)\]\s*(.*?)\s*\[\/hide\]/is", create_function('$matches', 'return creditshide($matches[1], $matches[2], ' . intval($pid) . ', ' . intval($authorid) . ');'), $message);
                }
            }
        }
        
        if (!$bbcodeoff) {
            $attrsrc = !IS_ROBOT && $lazyload ? 'file' : 'src';
            if (strpos($msglower, '[/img]') !== false) {
                $message = preg_replace_callback("/\[img\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/is", create_function('$matches', 'return ' . intval($allowimgcode) . ' ? parseimg(0, 0, $matches[1], ' . intval($lazyload) . ', ' . intval($pid) . ', \'onmouseover="img_onmouseoverfunc(this)" \'.(' . intval($lazyload) . ' ? \'lazyloadthumb="1"\' : \'onload="thumbImg(this)"\')) : (' . intval($allowbbcode) . ' ? (!defined(\'IN_MOBILE\') ? bbcodeurl($matches[1], \'<a href="{url}" target="_blank">{url}</a>\') : bbcodeurl($matches[1], \'\')) : bbcodeurl($matches[1], \'{url}\'));'), $message);
                $message = preg_replace_callback("/\[img=(\d{1,4})[x|\,](\d{1,4})\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/is", create_function('$matches', 'return ' . intval($allowimgcode) . ' ? parseimg($matches[1], $matches[2], $matches[3], ' . intval($lazyload) . ', ' . intval($pid) . ') : (' . intval($allowbbcode) . ' ? (!defined(\'IN_MOBILE\') ? bbcodeurl($matches[3], \'<a href="{url}" target="_blank">{url}</a>\') : bbcodeurl($matches[3], \'\')) : bbcodeurl($matches[3], \'{url}\'));'), $message);
            }
        }
        
        for ($i = 0; $i <= $_G['forum_discuzcode']['pcodecount']; $i++) {
            $message = str_replace("[\tkaf:element:$i\t]", $_G['forum_discuzcode']['codehtml'][$i], $message);
        }
        
        unset($msglower);
        
        if ($jammer) {
            $message = preg_replace_callback("/\r\n|\n|\r/", 'discuzcode_callback_jammer', $message);
        }
        
        if ($first) {
            if (helper_access::check_module('group')) {
                $message = preg_replace("/\[groupid=(\d+)\](.*)\[\/groupid\]/i", lang('forum/template', 'fromgroup') . ': <a href="forum.php?mod=forumdisplay&fid=\\1" target="_blank">\\2</a>', $message);
            } else {
                $message = preg_replace("/(\[groupid=\d+\].*\[\/groupid\])/i", '', $message);
            }
            
        }
        
        //return $htmlon ? $message : nl2br(str_replace(array("\t", '   ', '  '), array('&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;'), $message));
        return $htmlon ? $message : str_replace([
            "\t",
            '   ',
            '  ',
        ], [
            '&nbsp;&nbsp;&nbsp;&nbsp; ',
            '&nbsp;&nbsp;',
            '&nbsp;&nbsp;',
        ], $message);
    }
    
    function discuzcode_callback_codedisp_1($matches) {
        return codedisp($matches[1]);
    }
    
    function discuzcode_callback_parseattachurl_12($matches) {
        return parseattachurl($matches[1], $matches[2], 1);
    }
    
    function discuzcode_callback_parseed2k_1($matches) {
        return parseed2k($matches[1]);
    }
    
    function discuzcode_callback_parseurl_152($matches) {
        return parseurl($matches[1], $matches[5], $matches[2]);
    }
    
    function discuzcode_callback_parseemail_14($matches) {
        return parseemail($matches[1], $matches[4]);
    }
    
    function discuzcode_callback_parsetable_123($matches) {
        return parsetable($matches[1], $matches[2], $matches[3]);
    }
    
    function discuzcode_callback_parsemedia_12($matches) {
        return parsemedia($matches[1], $matches[2]);
    }
    
    function discuzcode_callback_bbcodeurl_2($matches) {
        return bbcodeurl($matches[2], '<a href="{url}" target="_blank">{url}</a>');
    }
    
    function discuzcode_callback_parseaudio_2($matches) {
        return parseaudio($matches[2], 400);
    }
    
    function discuzcode_callback_bbcodeurl_4($matches) {
        return bbcodeurl($matches[4], '<a href="{url}" target="_blank">{url}</a>');
    }
    
    function discuzcode_callback_jammer($matches) {
        return jammer();
    }
    
    function parseurl($url, $text, $scheme) {
        global $_G;
        if (!$url && preg_match("/((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.)[^\[\"']+/i", trim($text), $matches)) {
            $url = $matches[0];
            $length = 65;
            if (strlen($url) > $length) {
                $text = substr($url, 0, intval($length * 0.5)) . ' ... ' . substr($url, -intval($length * 0.3));
            }
            return '<a href="' . (substr(strtolower($url), 0, 4) == 'www.' ? 'http://' . $url : $url) . '" target="_blank">' . $text . '</a>';
        } else {
            $url = substr($url, 1);
            if (substr(strtolower($url), 0, 4) == 'www.') {
                $url = 'http://' . $url;
            }
            $url = !$scheme ? $_G['siteurl'] . $url : $url;
            return '<a href="' . $url . '" target="_blank">' . $text . '</a>';
        }
    }
    
    function parseed2k($url) {
        global $_G;
        [
            ,
            $type,
            $name,
            $size,
        ] = explode('|', $url);
        $url = 'ed2k://' . $url . '/';
        $name = addslashes($name);
        if ($type == 'file') {
            $ed2kid = 'ed2k_' . random(3);
            return '<a id="' . $ed2kid . '" href="' . $url . '" target="_blank">' . dhtmlspecialchars(urldecode($name)) . ' (' . sizecount($size) . ')</a><script>$(\'' . $ed2kid . '\').innerHTML=htmlspecialchars(unescape(decodeURIComponent(\'' . $name . '\')))+\' (' . sizecount($size) . ')\';</script>';
        } else {
            return '<a href="' . $url . '" target="_blank">' . $url . '</a>';
        }
    }
    
    function parseattachurl($aid, $ext, $ignoretid = 0) {
        global $_G;
        $_G['forum_skipaidlist'][] = $aid;
        return $_G['siteurl'] . 'forum.php?mod=attachment&aid=' . aidencode($aid, $ext, $ignoretid ? '' : $_G['tid']) . ($ext ? '&request=yes&_f=.' . $ext : '');
    }
    
    function parseemail($email, $text) {
        $text = str_replace('\"', '"', $text);
        if (!$email && preg_match("/\s*([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+)\s*/i", $text, $matches)) {
            $email = trim($matches[0]);
            return '<a href="mailto:' . $email . '">' . $email . '</a>';
        } else {
            return '<a href="mailto:' . substr($email, 1) . '">' . $text . '</a>';
        }
    }
    
    function parsetable($width, $bgcolor, $message) {
        if (strpos($message, '[/tr]') === false && strpos($message, '[/td]') === false) {
            $rows = explode("\n", $message);
            $s = !defined('IN_MOBILE') ? '<table cellspacing="0" class="t_table" ' . ($width == '' ? null : 'style="width:' . $width . '"') . ($bgcolor ? ' bgcolor="' . $bgcolor . '">' : '>') : '<table>';
            foreach ($rows as $row) {
                $s .= '<tr><td>' . str_replace([
                        '\|',
                        '|',
                        '\n',
                    ], [
                        '&#124;',
                        '</td><td>',
                        "\n",
                    ], $row) . '</td></tr>';
            }
            $s .= '</table>';
            return $s;
        } else {
            if (!preg_match("/^\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[td([=\d,%]+)?\]/", $message) && !preg_match("/^<tr[^>]*?>\s*<td[^>]*?>/", $message)) {
                return str_replace('\\"', '"', preg_replace("/\[tr(?:=([\(\)\s%,#\w]+))?\]|\[td([=\d,%]+)?\]|\[\/td\]|\[\/tr\]/", '', $message));
            }
            if (substr($width, -1) == '%') {
                $width = substr($width, 0, -1) <= 98 ? intval($width) . '%' : '98%';
            } else {
                $width = intval($width);
                $width = $width ? ($width <= 560 ? $width . 'px' : '98%') : '';
            }
            $message = preg_replace_callback("/\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[td(?:=(\d{1,4}%?))?\]/i", 'parsetable_callback_parsetrtd_12', $message);
            $message = preg_replace_callback("/\[\/td\]\s*\[td(?:=(\d{1,4}%?))?\]/i", 'parsetable_callback_parsetrtd_1', $message);
            $message = preg_replace_callback("/\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[td(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/i", 'parsetable_callback_parsetrtd_1234', $message);
            $message = preg_replace_callback("/\[\/td\]\s*\[td(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/i", 'parsetable_callback_parsetrtd_123', $message);
            $message = preg_replace("/\[\/td\]\s*\[\/tr\]\s*/i", '</td></tr>', $message);
            return (!defined('IN_MOBILE') ? '<table cellspacing="0" class="t_table" ' . ($width == '' ? null : 'style="width:' . $width . '"') . ($bgcolor ? ' bgcolor="' . $bgcolor . '">' : '>') : '<table>') . str_replace('\\"', '"', $message) . '</table>';
        }
    }
    
    function parsetable_callback_parsetrtd_12($matches) {
        return parsetrtd($matches[1], 0, 0, $matches[2]);
    }
    
    function parsetable_callback_parsetrtd_1($matches) {
        return parsetrtd('td', 0, 0, $matches[1]);
    }
    
    function parsetable_callback_parsetrtd_1234($matches) {
        return parsetrtd($matches[1], $matches[2], $matches[3], $matches[4]);
    }
    
    function parsetable_callback_parsetrtd_123($matches) {
        return parsetrtd('td', $matches[1], $matches[2], $matches[3]);
    }
    
    function parsetrtd($bgcolor, $colspan, $rowspan, $width) {
        return ($bgcolor == 'td' ? '</td>' : '<tr' . ($bgcolor && !defined('IN_MOBILE') ? ' style="background-color:' . $bgcolor . '"' : '') . '>') . '<td' . ($colspan > 1 ? ' colspan="' . $colspan . '"' : '') . ($rowspan > 1 ? ' rowspan="' . $rowspan . '"' : '') . ($width && !defined('IN_MOBILE') ? ' width="' . $width . '"' : '') . '>';
    }
    
    function parseaudio($url, $width = 400) {
        $url = addslashes($url);
        if (!in_array(strtolower(substr($url, 0, 6)), ['http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://',]) &&
            !preg_match('/^static\//', $url) &&
            !preg_match('/^data\//', $url)
        ) {
            return dhtmlspecialchars($url);
        }
        $type = fileext($url);
        $randomid = random(3);
        return '<div id="' . $type . '_' . $randomid . '" class="media"><div id="' . $type . '_' . $randomid . '_container" class="media_container"></div><div id="' . $type . '_' . $randomid . '_tips" class="media_tips"><a href="' . $url . '" target="_blank">' . lang('template', 'parse_av_tips') . '</a></div></div><script>detectPlayer("' . $type . '_' . $randomid . '", "' . $type . '", "' . $url . '", "' . $width . '", "66");</script>';
    }
    
    function parsemedia($params, $url) {
        $params = explode(',', $params);
        $width = intval($params[1]) > 800 ? 800 : intval($params[1]);
        $height = intval($params[2]) > 600 ? 600 : intval($params[2]);
        
        $url = addslashes($url);
        if (!in_array(strtolower(substr($url, 0, 6)), ['http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://',]) &&
            !preg_match('/^static\//', $url) &&
            !preg_match('/^data\//', $url)
        ) {
            return dhtmlspecialchars($url);
        }
        
        if ($flv = parseflv($url, $width, $height)) {
            return $flv;
        }
        if (in_array(count($params), [3, 4,])) {
            $type = fileext($url);
            $url = str_replace(['<', '>',], '', str_replace('\\"', '\"', $url));
            if (in_array($params[0], ['rtsp', 'mms',])) {
                $mediaid = 'media_' . random(3);
                return $params[0] == 'rtsp' ? '<object classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" width="' . $width . '" height="' . $height . '"><param name="autostart" value="0" /><param name="src" value="' . $url . '" /><param name="controls" value="imagewindow" /><param name="console" value="' . $mediaid . '_" /><embed src="' . $url . '" autostart="0" type="audio/x-pn-realaudio-plugin" controls="imagewindow" console="' . $mediaid . '_" width="' . $width . '" height="' . $height . '"></embed></object><br><object classid="clsid:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA" width="' . $width . '" height="32"><param name="src" value="' . $url . '" /><param name="controls" value="controlpanel" /><param name="console" value="' . $mediaid . '_" /><embed src="' . $url . '" autostart="0" type="audio/x-pn-realaudio-plugin" controls="controlpanel" console="' . $mediaid . '_" width="' . $width . '" height="32"></embed></object>' : '<object classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6" width="' . $width . '" height="' . $height . '"><param name="invokeURLs" value="0"><param name="autostart" value="0" /><param name="url" value="' . $url . '" /><embed src="' . $url . '" autostart="0" type="application/x-mplayer2" width="' . $width . '" height="' . $height . '"></embed></object>';
            }
            $audio = ['aac', 'flac', 'ogg', 'mp3', 'm4a', 'weba', 'wma', 'mid', 'wav', 'ra', 'ram',];
            $video = ['rm', 'rmvb', 'asf', 'asx', 'wmv', 'avi', 'mpg', 'mpeg', 'mp4', 'm4v', '3gp', 'ogv', 'webm', 'mov',];
            if (in_array($type, $audio)) {
                return parseaudio($url, $width);
            } else if (in_array($type, $video)) {
                $randomid = random(3);
                return '<div id="' . $type . '_' . $randomid . '" class="media"><div id="' . $type . '_' . $randomid . '_container" class="media_container"></div><div id="' . $type . '_' . $randomid . '_tips" class="media_tips"><a href="' . $url . '" target="_blank">' . lang('template', 'parse_av_tips') . '</a></div></div><script>detectPlayer("' . $type . '_' . $randomid . '", "' . $type . '", "' . $url . '", "' . $width . '", "' . $height . '");</script>';
            } else {
                return '<a href="' . $url . '" target="_blank">' . $url . '</a>';
            }
        }
        return '';
    }
    
    function bbcodeurl($url, $tags) {
        if (!preg_match("/<.+?>/s", $url)) {
            if (!in_array(strtolower(substr($url, 0, 6)), ['http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://',]) && !preg_match('/^static\//', $url) && !preg_match('/^data\//', $url)) {
                $url = 'http://' . $url;
            }
            return str_replace(['submit', 'member.php?mod=logging',], ['', '',], str_replace('{url}', addslashes($url), $tags));
        } else {
            return $url;
        }
    }
    
    function jammer() {
        $randomstr = '';
        for ($i = 0; $i < rand(5, 15); $i++) {
            $randomstr .= chr(rand(32, 59)) . ' ' . chr(rand(63, 126));
        }
        return rand(0, 1) ? '<kaf type="text" class="jammer">' . $randomstr . '</kaf>' . "\r\n" : "\r\n" . '<span style="display:none">' . $randomstr . '</span>';
    }
    
    function highlightword($text, $words, $prepend) {
        $text = str_replace('\"', '"', $text);
        foreach ($words as $key => $replaceword) {
            $text = str_replace($replaceword, '<highlight>' . $replaceword . '</highlight>', $text);
        }
        return "$prepend$text";
    }
    
    function parseflv($url, $width = 0, $height = 0) {
        global $_G;
        $lowerurl = strtolower($url);
        $flv = $iframe = $imgurl = '';
        if (empty($_G['setting']['parseflv']) || !is_array($_G['setting']['parseflv'])) {
            return false;
        }
        
        foreach ($_G['setting']['parseflv'] as $script => $checkurl) {
            $check = false;
            foreach ($checkurl as $row) {
                if (strpos($lowerurl, $row) !== false) {
                    $check = true;
                    break;
                }
            }
            if ($check) {
                @include_once libfile('media/' . $script, 'function');
                if (function_exists('media_' . $script)) {
                    [$iframe, $url, $imgurl] = call_user_func('media_' . $script, $url, $width, $height);
                }
                break;
            }
        }
        if ($iframe) {
            if (!$width && !$height) {
                return ['iframe' => $iframe, 'imgurl' => $imgurl,];
            } else {
                $iframe = addslashes($iframe);
                $randomid = 'iframe_' . random(3);
                $player_iframe = $iframe ? "<iframe src='$iframe'></iframe>" : '';
                $player =  $player_iframe;
                return '<span id="' . $randomid . '"></span><script reload="1">document.getElementById(\'' . $randomid . '\').innerHTML=(' . $player . ');</script>';
            }
        } else {
            return false;
        }
    }
    
    function parseimg($width, $height, $src, $lazyload, $pid, $extra = '') {
        global $_G;
        static $styleoutput = null;
        if ($_G['setting']['domainwhitelist_affectimg']) {
            $tmp = parse_url($src);
            if (!empty($tmp['host']) && !iswhitelist($tmp['host'])) {
                return $src;
            }
        }
        if (strstr($src, 'file:') || substr($src, 1, 1) == ':') {
            return $src;
        }
        if ($width > $_G['setting']['imagemaxwidth']) {
            $height = intval($_G['setting']['imagemaxwidth'] * $height / $width);
            $width = $_G['setting']['imagemaxwidth'];
            if (defined('IN_MOBILE')) {
                $extra = '';
            } else {
                $extra = 'onmouseover="img_onmouseoverfunc(this)" onclick="zoom(this)" style="cursor:pointer"';
            }
        }
        $attrsrc = !IS_ROBOT && $lazyload ? 'file' : 'src';
        $rimg_id = random(5);
        $GLOBALS['aimgs'][$pid][] = $rimg_id;
        $guestviewthumb = !empty($_G['setting']['guestviewthumb']['flag']) && empty($_G['uid']);
        $img = '';
        if ($guestviewthumb) {
            if (!isset($styleoutput)) {
                $img .= guestviewthumbstyle();
                $styleoutput = true;
            }
            $img .= '<div class="thread-element-img-preview px4-radius">
                    <img id="aimg_' . $rimg_id . '" class="guestviewthumb_cur" ' . $attrsrc . '="{url}" border="0" alt="" />
                    <canvas id="aimg_' . $rimg_id . '_canvas"></canvas>
                    <p class="preview-tip" onclick="showWindow(\'login\', \'{loginurl}\'+\'&referer=\'+encodeURIComponent(location))">' . lang('template', 'noprem_viewimg') . '</p>
				</div>';
        
        } else {
            if (defined('IN_MOBILE')) {
                //$img = '<img'.($width > 0 ? ' width="'.$width.'"' : '').($height > 0 ? ' height="'.$height.'"' : '').' src="{url}" border="0" alt="" />';
                $img = '<div class="thread-element-img"><img src="{url}"/></div>';
            } else {
                //$img = '<img id="aimg_'.$rimg_id.'" onclick="zoom(this, this.src, 0, 0, '.($_G['setting']['showexif'] ? 1 : 0).')" class="zoom"'.($width > 0 ? ' width="'.$width.'"' : '').($height > 0 ? ' height="'.$height.'"' : '').' '.$attrsrc.'="{url}" '.($extra ? $extra.' ' : '').'border="0" alt="" />';
                $img = '<div class="thread-element-img"><img id="aimg_' . $rimg_id . '" onclick="zoom(this, this.src, 0, 0, ' . ($_G['setting']['showexif'] ? 1 : 0) . ')" class="zoom" ' . $attrsrc . '="{url}" alt="" /></div>';
            }
        }
        $code = bbcodeurl($src, $img);
        if ($guestviewthumb) {
            $code = str_replace('{loginurl}', 'member.php?mod=logging&action=login', $code);
        }
        return $code;
    }
    
    function parsesmiles(&$message) {
        global $_G;
        static $enablesmiles;
        if ($enablesmiles === null) {
            $enablesmiles = false;
            if (!empty($_G['cache']['smilies']) && is_array($_G['cache']['smilies'])) {
                foreach ($_G['cache']['smilies']['replacearray'] as $key => $smiley) {
                    $_G['cache']['smilies']['replacearray'][$key] = '<img class="smilie" src="' . STATICURL . 'image/smiley/' . $_G['cache']['smileytypes'][$_G['cache']['smilies']['typearray'][$key]]['directory'] . '/' . $smiley . '" smilieid="' . $key . '"/>';
                }
                $enablesmiles = true;
            }
        }
        $enablesmiles && $message = preg_replace($_G['cache']['smilies']['searcharray'], $_G['cache']['smilies']['replacearray'], $message, $_G['setting']['maxsmilies']);
        return $message;
    }
    
    function parsepostbg($bgimg, $pid) {
        global $_G;
        static $postbg;
        if ($postbg[$pid]) {
            return '';
        }
        loadcache('postimg');
        foreach ($_G['cache']['postimg']['postbg'] as $postbg) {
            if ($postbg['url'] != $bgimg) {
                continue;
            }
            $bgimg = dhtmlspecialchars(basename($bgimg), ENT_QUOTES);
            $postbg[$pid] = true;
            $_G['forum_posthtml']['header'][$pid] .= '<style type="text/css">#pid' . $pid . '{background-image:url("' . STATICURL . 'image/postbg/' . $bgimg . '");}</style>';
            break;
        }
        return '';
    }
    
    function parsepassword($password, $pid) {
        global $_G;
        static $postpw;
        if ($postpw[$pid]) {
            return '';
        }
        $postpw[$pid] = true;
        if (empty($_G['cookie']['postpw_' . $pid]) || $_G['cookie']['postpw_' . $pid] != md5($password)) {
            $_G['forum_discuzcode']['passwordlock'][$pid] = 1;
        }
        return '';
    }
    
    function guestviewthumbstyle() {
        static $styleoutput = null;
        $return = '';
        if ($styleoutput === null) {
            global $_G;
            $return = '<style>.guestviewthumb {margin:10px auto; text-align:center;}.guestviewthumb a {font-size:12px;}.guestviewthumb_cur {cursor:url("' . IMGDIR . '/scf.gif"), default; max-width:' . $_G['setting']['guestviewthumb']['width'] . 'px;}.ie6 .guestviewthumb_cur { width:' . $_G['setting']['guestviewthumb']['width'] . 'px !important;}</style>';
            $styleoutput = true;
        }
        return $return;
    }