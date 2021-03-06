<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_profile.php 36354 2017-01-17 07:52:44Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function profile_setting($fieldid, $space=[], $showstatus=false, $ignoreunchangable = false) {
	global $_G;

	if(empty($_G['cache']['profilesetting'])) {
		loadcache('profilesetting');
	}
	$field = $_G['cache']['profilesetting'][$fieldid];
	if(empty($field) || !$field['available'] || in_array($fieldid, array('uid', 'constellation', 'zodiac', 'birthmonth', 'birthyear', 'birthprovince', 'birthdist', 'birthcommunity', 'resideprovince', 'residedist', 'residecommunity'))) {
		return '';
	}

	if($showstatus) {
		$uid = intval($space['uid']);
		if($uid && !isset($_G['profile_verifys'][$uid])) {
			$_G['profile_verifys'][$uid] = [];
			if($value = C::t('common_member_verify_info')->fetch_by_uid_verifytype($uid, 0)) {
				$fields = dunserialize($value['field']);
				foreach($fields as $key => $fvalue) {
					if($_G['cache']['profilesetting'][$key]['needverify']) {
						$_G['profile_verifys'][$uid][$key] = $fvalue;
					}
				}
			}
		}
		$verifyvalue = NULL;
		if(isset($_G['profile_verifys'][$uid][$fieldid])) {
			if($fieldid=='gender') {
				$verifyvalue = lang('space', 'gender_'.intval($_G['profile_verifys'][$uid][$fieldid]));
			} elseif($fieldid=='birthday') {
				$verifyvalue = $_G['profile_verifys'][$uid]['birthyear'].'-'.$_G['profile_verifys'][$uid]['birthmonth'].'-'.$_G['profile_verifys'][$uid]['birthday'];
			} else {
				$verifyvalue = $_G['profile_verifys'][$uid][$fieldid];
			}
		}
	}

	$html = '';
	$field['unchangeable'] = !$ignoreunchangable && $field['unchangeable'] ? 1 : 0;
	if($fieldid == 'birthday') {
		if($field['unchangeable'] && !empty($space[$fieldid])) {
			return '<s class="input-simulate unchangeable">'.$space['birthyear'].'-'.$space['birthmonth'].'-'.$space['birthday'].'</s>';
		}
		$birthyeayhtml = '';
		$nowy = dgmdate($_G['timestamp'], 'Y');
		for ($i=0; $i<100; $i++) {
			$they = $nowy - $i;
			$selectstr = $they == $space['birthyear']?' selected':'';
			$birthyeayhtml .= "<option value=\"$they\"$selectstr>$they</option>";
		}
		$birthmonthhtml = '';
		for ($i=1; $i<13; $i++) {
			$selectstr = $i == $space['birthmonth']?' selected':'';
			$birthmonthhtml .= "<option value=\"$i\"$selectstr>$i</option>";
		}
		$birthdayhtml = '';
		if(empty($space['birthmonth']) || in_array($space['birthmonth'], array(1, 3, 5, 7, 8, 10, 12))) {
			$days = 31;
		} elseif(in_array($space['birthmonth'], array(4, 6, 9, 11))) {
			$days = 30;
		} elseif($space['birthyear'] && (($space['birthyear'] % 400 == 0) || ($space['birthyear'] % 4 == 0 && $space['birthyear'] % 400 != 0))) {
			$days = 29;
		} else {
			$days = 28;
		}
		for ($i=1; $i<=$days; $i++) {
			$selectstr = $i == $space['birthday']?' selected':'';
			$birthdayhtml .= "<option value=\"$i\"$selectstr>$i</option>";
		}
		$html = '<label class="awe-calendar" for="birthday" title="'.$field['title'].'" type="icon"></label>'
                .'<select name="birthyear" id="birthyear" onchange="showbirthday();" datatype="smallint">'
				.'<option value="">'.lang('space', 'year').'</option>'
				.$birthyeayhtml
				.'</select>'
				.'<select name="birthmonth" id="birthmonth" onchange="showbirthday();" datatype="smallint">'
				.'<option value="">'.lang('space', 'month').'</option>'
				.$birthmonthhtml
				.'</select>'
				.'<select name="birthday" id="birthday" datatype="smallint">'
				.'<option value="">'.lang('space', 'day').'</option>'
				.$birthdayhtml
				.'</select>';

	} elseif($fieldid=='gender') {
		if($field['unchangeable'] && $space[$fieldid] > 0) {
			return '<s class="input-simulate unchangeable">'.lang('space', 'gender_'.intval($space[$fieldid])).'</s>';
		}
        $html = "<div class='check-slot'>";
        if(!$field['unchangeable']) {
            $html .= "<s class='csbox mager'><input id='gender_0' type='radio' name='gender' value='0' ".($space[$fieldid] == '0' ? ' checked="checked"' : '')."/><label for='gender_0'>".lang('space', 'gender_0')."</label></s>";
		}
        $html .= "<s class='csbox mager'><input id='gender_1' type='radio' name='gender' value='1' ".($space[$fieldid] == '1' ? ' checked="checked"' : '')."/><label for='gender_1'>".lang('space', 'gender_1')."</label></s>";
        $html .= "<s class='csbox mager'><input id='gender_2' type='radio' name='gender' value='2' ".($space[$fieldid] == '2' ? ' checked="checked"' : '')."/><label for='gender_2'>".lang('space', 'gender_2')."</label></s>";

        $html .= '</div>';

	} elseif($fieldid=='birthcity') {
		if($field['unchangeable'] && !empty($space[$fieldid])) {
			return '<s class="input-simulate unchangeable">'.$space['birthprovince'].'-'.$space['birthcity'].'</s>';
		}
		$values = array(0,0,0,0);
		$elems = array('birthprovince', 'birthcity', 'birthdist', 'birthcommunity');
		if(!empty($space['birthprovince'])) {
			$html = '<s class="input-simulate pointer" onclick="showdistrict(\'birthdistrictbox\', [\'birthprovince\', \'birthcity\', \'birthdist\', \'birthcommunity\'], 4, \'\', \'birth\'); return false;">'.profile_show('birthcity', $space).'</s>';
			$html .= '<p id="birthdistrictbox"></p>';
		} else {
			$html = '<p id="birthdistrictbox">'.showdistrict($values, $elems, 'birthdistrictbox', 1, 'birth').'</p>';
		}

	} elseif($fieldid=='residecity') {
		if($field['unchangeable'] && !empty($space[$fieldid])) {
			return '<s class="input-simulate unchangeable">'.$space['resideprovince'].'-'.$space['residecity'].'</s>';
		}
		$values = array(0,0,0,0);
		$elems = array('resideprovince', 'residecity', 'residedist', 'residecommunity');
		if(!empty($space['resideprovince'])) {
			$html = '<s class="input-simulate pointer" onclick="showdistrict(\'residedistrictbox\', [\'resideprovince\', \'residecity\', \'residedist\', \'residecommunity\'], 4, \'\', \'reside\'); return false;">'.profile_show('residecity', $space).'</s>';
			$html .= '<p id="residedistrictbox"></p>';
		} else {
			$html = '<p id="residedistrictbox">'.showdistrict($values, $elems, 'residedistrictbox', 1, 'reside').'</p>';
		}
	} else {
		if($field['unchangeable'] && $space[$fieldid]!='') {
			if($field['formtype']=='file') {
				$imgurl = getglobal('setting/attachurl').'./profile/'.$space[$fieldid];
				return '<s class="img-slot"><a href="'.$imgurl.'" target="_blank"><img src="'.$imgurl.'"/></a></s>';
			} else {
				return '<s class="input-simulate unchangeable">'.nl2br($space[$fieldid]).'</s>';
			}
		}
		if($field['formtype']=='textarea') {
			$html = "<textarea name=\"$fieldid\" id=\"$fieldid\" >$space[$fieldid]</textarea>";
		} elseif($field['formtype']=='select') {
			$field['choices'] = explode("\n", $field['choices']);
			$html = "<select name='$fieldid' id='$fieldid' >";
			foreach($field['choices'] as $op) {
				$html .= "<option value='$op'".($op==$space[$fieldid] ? 'selected="selected"' : '').">$op</option>";
			}
			$html .= '</select>';
		} elseif($field['formtype']=='list') {
			$field['choices'] = explode("\n", $field['choices']);
			$html = "<select class='select-multi' name='{$fieldid}[]' id='$fieldid' multiple='multiplue' >";
            $html .= "<option class='hide' value='list_submit_mark_empty' selected='selected'></option>";
			$space[$fieldid] = explode("\n", $space[$fieldid]);
			foreach($field['choices'] as $op) {
				$html .= "<option value='$op'".(in_array($op, $space[$fieldid]) ? 'selected="selected"' : '').">$op</option>";
			}
			$html .= '</select>';
		} elseif($field['formtype']=='checkbox') {
			$field['choices'] = explode("\n", $field['choices']);
			$space[$fieldid] = explode("\n", $space[$fieldid]);
            $html = "<div class='check-slot'>";
            $html .= "<s><input type='hidden' name='{$fieldid}[]' value='list_submit_mark_empty' checked='checked'/></s>";
            foreach($field['choices'] as $op) {
                $idhash = substr(md5($op),0,10);
				$html .= "<s class='ccbox mager'><input id='$fieldid".$idhash."' type='checkbox' name='{$fieldid}[]' value='$op'".(in_array($op, $space[$fieldid]) ? ' checked="checked"' : '')." /><label for='$fieldid".$idhash."'>$op</label></s>";
			}
            $html .= "</div>";
		} elseif($field['formtype']=='radio') {
			$field['choices'] = explode("\n", $field['choices']);
            $html = "<div class='check-slot'>";
			foreach($field['choices'] as $op) {
			    $idhash = substr(md5($op),0,10);
				$html .= "<s class='csbox mager'><input id='$fieldid".$idhash."' type='radio' name='$fieldid' value='$op' ".($op == $space[$fieldid] ? ' checked="checked"' : '')."/><label for='$fieldid".$idhash."'>$op</label></s>";
			}
            $html .= "</div>";
		} elseif($field['formtype']=='file') {
			$html = "<div class='img-selector' role='img-selector-container-$fieldid'>";
            $idhash = substr(md5('delfile'),0,10);
			if(!empty($space[$fieldid])) {
				$url = getglobal('setting/attachurl').'./profile/'.$space[$fieldid];
                $html .= "<s class='img-slot'><a class='mt-link' href='$url' target='_blank'><img role='canvas' src='$url'/></a></s>";
                $html .= "<s class='ccbox admin'><input type='checkbox' name='deletefile[$fieldid]' id='$fieldid".$idhash."' value='yes' /><label for='$fieldid".$idhash."'>".lang('spacecp', 'delete')."</label></s>";
                $html .= "<span class='img-size' role='file-info'></span>";
			} else {
                $html .= "<s class='img-slot'><img role='canvas' src='".$_G['style']['imgurl']."/common/no-img/no-img.svg'/></s>";
                $html .= "<s class='ccbox admin disabled'><input type='checkbox' id='$fieldid".$idhash."' value='yes' /><label for='$fieldid".$idhash."'>".lang('spacecp', 'delete')."</label></s>";
                $html .= "<span class='img-size' role='file-info'></span>";
            }
            $html .= "<label class='btn btn-small' for='$fieldid'>".lang('spacecp', 'select_file')."</label>";
            $html .= "<input class='hide' type='file'   name='$fieldid' value='' id='$fieldid' role='file-elem'/>";
            $html .= "<input class='hide' type='hidden' name='$fieldid' value='$space[$fieldid]' />";
            $html .= "</div>";
            $html .= "<script>addEvent(document,'DOMContentLoaded',function() { imgpreview(document.querySelector('[role=img-selector-container-$fieldid]')) })</script>";
		} else {
			$html = "<input type='text' name='$fieldid' id='$fieldid' value='$space[$fieldid]' />";
		}
	}
	$html .= "<div class='input-tip'></div>";

	if($showstatus) {
		$html .= "<p class='message'>".(!empty($field['description']) ? ($field['description']."<br>") : '');
		if($verifyvalue !== null) {
			if($field['formtype'] == 'file') {
				$imgurl = getglobal('setting/attachurl').'./profile/'.$verifyvalue;
				$verifyvalue = "<img src='$imgurl' alt='$imgurl'/>";
			}
			$html .= "<em>".lang('spacecp', 'profile_is_verifying')."<span class='tooltip'>".$verifyvalue."</span></em>";
		} elseif($field['needverify']) {
			$html .= "<span class='warning'>".lang('spacecp', 'profile_need_verifying')."</span>";
		}
        if($space[$fieldid]=='' && $field['unchangeable']) {
            $html .= "<span class='warning'>".lang('spacecp', 'profile_unchangeable')."</span>";
        }
		$html .= '</p>';
	}
	return $html;
}

function profile_check($fieldid, &$value, $space=[]) {
	global $_G;

	if(empty($_G['cache']['profilesetting'])) {
		loadcache('profilesetting');
	}
	
	if(empty($_G['profilevalidate'])) {
		include libfile('spacecp/profilevalidate', 'include');
		$_G['profilevalidate'] = $profilevalidate;
	}

	$field = $_G['cache']['profilesetting'][$fieldid];
	
    $field['choices'] = !empty($field['choices']) ? explode("\n", $field['choices']) : [];
    
    if(empty($field) || !$field['available']) {
		return false;
	}

	if(empty($value)) {
		if($field['required']) {
			if(in_array($fieldid, array('birthprovince', 'birthcity', 'birthdist', 'birthcommunity', 'resideprovince', 'residecity', 'residedist', 'residecommunity'))) {
				if(substr($fieldid, 0, 5) == 'birth') {
					if(!empty($_GET['birthprovince']) || !empty($_GET['birthcity']) || !empty($_GET['birthdist']) || !empty($_GET['birthcommunity'])) {
						return true;
					}
				} elseif(!empty($_GET['resideprovince']) || !empty($_GET['residecity']) || !empty($_GET['residedist']) || !empty($_GET['residecommunity'])) {
					return true;
				}
			}
			return false;
		} else {
			return true;
		}
	}
	if($field['unchangeable'] && !empty($space[$fieldid])) {
		return false;
	}

	include_once libfile('function/home');
	
	if(in_array($fieldid, array('birthday', 'birthmonth', 'birthyear', 'gender'))) {
		$value = intval($value);
		return true;
	} elseif(in_array($fieldid, array('birthprovince', 'birthcity', 'birthdist', 'birthcommunity', 'resideprovince', 'residecity', 'residedist', 'residecommunity'))) {
		$value = getstr($value);
		return true;
	}
	
	if($field['formtype'] == 'text' || $field['formtype'] == 'textarea') {
		if($field['size'] && strlen($value) > $field['size']) {
			return false;
		} else {
			$field['validate'] = !empty($field['validate']) ? $field['validate'] : ($_G['profilevalidate'][$fieldid] ? $_G['profilevalidate'][$fieldid] : '');
			if($field['validate'] && !preg_match($field['validate'], $value)) {
				return false;
			}
		}
        $value = getstr($value);

    } elseif($field['formtype'] == 'checkbox' || $field['formtype'] == 'list') {
        
        if($field['required'] && empty($arr)){
            return false;
        }
        
		$arr = [];
  
		foreach ($value as $op) {
			if(in_array($op, $field['choices'])) {
				$arr[] = $op;
			}
		}
		
        if($field['size'] && (count($arr) > $field['size'])) {
            return false;
        }
        
		$value = implode("\n", $arr);

	} elseif($field['formtype'] == 'radio' || $field['formtype'] == 'select') {
		if(!in_array($value, $field['choices'])){
			return false;
		}
	}
	return true;
}

function profile_show($fieldid, $space=[], $getalone = false) {
	global $_G;

	if(empty($_G['cache']['profilesetting'])) {
		loadcache('profilesetting');
	}
	if($fieldid == 'qqnumber') {
		$_G['cache']['profilesetting'][$fieldid] = $_G['cache']['profilesetting']['qq'];
	}
	$field = $_G['cache']['profilesetting'][$fieldid];
	if(empty($field) || !$field['available'] || (!$getalone && in_array($fieldid, array('uid', 'birthmonth', 'birthyear', 'birthprovince', 'resideprovince')))) {
		return false;
	}

	if($fieldid=='gender') {
		return lang('space', 'gender_'.intval($space['gender']));
	} elseif($fieldid=='birthday' && !$getalone) {
		$return = $space['birthyear'] ? $space['birthyear'].' '.lang('space', 'year').' ' : '';
		if($space['birthmonth'] && $space['birthday']) {
			$return .= $space['birthmonth'].' '.lang('space', 'month').' '.$space['birthday'].' '.lang('space', 'day');
		}
		return $return;
	} elseif($fieldid=='birthcity' && !$getalone) {
		return $space['birthprovince']
				.(!empty($space['birthcity']) ? ' '.$space['birthcity'] : '')
				.(!empty($space['birthdist']) ? ' '.$space['birthdist'] : '')
				.(!empty($space['birthcommunity']) ? ' '.$space['birthcommunity'] : '');
	} elseif($fieldid=='residecity' && !$getalone) {
		return $space['resideprovince']
				.(!empty($space['residecity']) ? ' '.$space['residecity'] : '')
				.(!empty($space['residedist']) ? ' '.$space['residedist'] : '')
				.(!empty($space['residecommunity']) ? ' '.$space['residecommunity'] : '');
	} elseif($fieldid == 'site') {
		$url = str_replace('"', '\\"', $space[$fieldid]);
		return "<a href=\"$url\" target=\"_blank\">$url</a>";
	} elseif($fieldid == 'position') {
		//return nl2br($space['office'] ? $space['office'] : $space['position']);
		return str_replace("\n",'、',$space['office'] ? $space['office'] : $space['position']);
	} elseif($fieldid == 'qq') {
		return '<a href="//wpa.qq.com/msgrd?v=3&uin='.$space[$fieldid].'&site='.$_G['setting']['bbname'].'&menu=yes&from=discuz" target="_blank" title="'.lang('spacecp', 'qq_dialog').'"><img src="'.STATICURL.'/image/common/qq.gif" alt="QQ" style="margin:0px;"/></a>';
	} elseif($fieldid == 'qqnumber') {
		return $space['qq'];
	} else {
        //return nl2br($space[$fieldid]);
		return str_replace("\n",'、',$space[$fieldid]);
	}
}


function showdistrict($values, $elems=[], $container='districtbox', $showlevel=null, $containertype = 'birth') {
	$html = '';
	if(!preg_match("/^[A-Za-z0-9_]+$/", $container)) {
		return $html;
	}
	$showlevel = !empty($showlevel) ? intval($showlevel) : count($values);
	$showlevel = $showlevel <= 4 ? $showlevel : 4;
	$upids = array(0);
	for($i=0;$i<$showlevel;$i++) {
		if(!empty($values[$i])) {
			$upids[] = intval($values[$i]);
		} else {
			for($j=$i; $j<$showlevel; $j++) {
				$values[$j] = '';
			}
			break;
		}
	}
	$options = array(1=>[], 2=>[], 3=>[], 4=>[]);
	if($upids && is_array($upids)) {
		foreach(C::t('common_district')->fetch_all_by_upid($upids, 'displayorder', 'ASC') as $value) {
			if($value['level'] == 1 && ($value['id'] != $values[0] && ($value['usetype'] == 0 || !(($containertype == 'birth' && in_array($value['usetype'], array(1, 3))) || ($containertype != 'birth' && in_array($value['usetype'], array(2, 3))))))) {
				continue;
			}
			$options[$value['level']][] = array($value['id'], $value['name']);
		}
	}
	$names = array('province', 'city', 'district', 'community');
	for($i=0; $i<4;$i++) {
		if(!empty($elems[$i])) {
			$elems[$i] = dhtmlspecialchars(preg_replace("/[^\[A-Za-z0-9_\]]/", '', $elems[$i]));
		} else {
			$elems[$i] = ($containertype == 'birth' ? 'birth' : 'reside').$names[$i];
		}
	}

	for($i=0;$i<$showlevel;$i++) {
		$level = $i+1;
		if(!empty($options[$level])) {
			$jscall = "showdistrict('$container', ['$elems[0]', '$elems[1]', '$elems[2]', '$elems[3]'], $showlevel, $level, '$containertype')";
			$html .= '<select name="'.$elems[$i].'" id="'.$elems[$i].'" onchange="'.$jscall.'" datatype="smallint">';
			$html .= '<option value="">'.lang('spacecp', 'district_level_'.$level).'</option>';
			foreach($options[$level] as $option) {
				$selected = $option[0] == $values[$i] ? ' selected="selected"' : '';
				$html .= '<option did="'.$option[0].'" value="'.$option[1].'"'.$selected.'>'.$option[1].'</option>';
			}
			$html .= '</select>';
		}
	}
	return $html;
}

function countprofileprogress($uid = 0) {
	global $_G;

	$uid = intval(!$uid ? $_G['uid'] : $uid);
	if(($profilegroup = C::t('common_setting')->fetch('profilegroup', true))) {
		$fields = [];
		foreach($profilegroup as $type => $value) {
			foreach($value['field'] as $key => $field) {
				$fields[$key] = $field;
			}
		}
		if(isset($fields['sightml']) && empty($_G['group']['maxsigsize'])) {
			unset($fields['sightml']);
		}
		if(isset($fields['customstatus']) && empty($_G['group']['allowcstatus'])) {
			unset($fields['customstatus']);
		}
		loadcache('profilesetting');
		$allowcstatus = !empty($_G['group']['allowcstatus']) ? true : false;
		$complete = 0;
		$profile = array_merge(C::t('common_member_profile')->fetch($uid), C::t('common_member_field_forum')->fetch($uid));
		foreach($fields as $key) {
			if((!isset($_G['cache']['profilesetting'][$key]) || !$_G['cache']['profilesetting'][$key]['available']) && !in_array($key, array('sightml', 'customstatus'))) {
				unset($fields[$key]);
				continue;
			}
			if(in_array($key, array('birthday', 'birthyear', 'birthprovince', 'birthcity', 'birthdist', 'birthcommunity', 'resideprovince', 'residecity', 'residedist', 'residecommunity'))) {
				if($key=='birthday') {
					if(!empty($profile['birthyear']) || !empty($profile[$key])) {
						$complete++;
					}
					unset($fields['birthyear']);
				} elseif($key=='birthcity') {
					if(!empty($profile['birthprovince']) || !empty($profile[$key]) || !empty($profile['birthdist']) || !empty($profile['birthcommunity'])) {
						$complete++;
					}
					unset($fields['birthprovince']);
					unset($fields['birthdist']);
					unset($fields['birthcommunity']);
				} elseif($key=='residecity') {
					if(!empty($profile['resideprovince']) || !empty($profile[$key]) || !empty($profile['residedist']) || !empty($profile['residecommunity'])) {
						$complete++;
					}
					unset($fields['resideprovince']);
					unset($fields['residedist']);
					unset($fields['residecommunity']);
				}
			} else if($profile[$key] != '') {
				$complete++;
			}
		}
		$progress = floor($complete / count($fields) * 100);
		C::t('common_member_status')->update($uid, array('profileprogress' => $progress > 100 ? 100 : $progress), 'UNBUFFERED');
		return $progress;
	}
}

function get_constellation($birthmonth,$birthday) {
	$birthmonth = intval($birthmonth);
	$birthday = intval($birthday);
	$idx = $birthmonth;
	if ($birthday <= 22) {
		if (1 == $birthmonth) {
			$idx = 12;
		} else {
			$idx = $birthmonth - 1;
		}
	}
	return $idx > 0 && $idx <= 12 ? lang('space', 'constellation_'.$idx) : '';
}

function get_zodiac($birthyear) {
	$birthyear = intval($birthyear);
	$idx = (($birthyear - 1900) % 12) + 1;
	return $idx > 0 && $idx <= 12 ? lang('space', 'zodiac_'. $idx) : '';
}
?>