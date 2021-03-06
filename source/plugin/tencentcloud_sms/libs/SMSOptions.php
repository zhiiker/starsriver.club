<?php
/*
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace TencentDiscuzSMS;
class SMSOptions
{
    //使用全局密钥
    const GLOBAL_KEY = 0;
    //使用自定义密钥
    const CUSTOM_KEY = 1;
    //验证码默认有效时间
    const DEFAULT_EXPIRED = 5;
    //模板参数不包含过期时间
    const NOT_EXPIRED_TIME = 0;
    //模版参数包含过期时间
    const HAS_EXPIRED_TIME = 1;
    //评论需要验证手机号
    const COMMENT_NEED_PHONE = 1;
    //发文章需要验证手机号
    const POST_NEED_PHONE = 1;

    private $commonOptions;
    private $secretID;
    private $customKey;
    private $secretKey;
    private $SDKAppID;
    private $sign;
    private $templateID;
    private $hasExpireTime;
    private $codeExpired;
    private $commentNeedPhone;
    private $postNeedPhone;


    public function __construct($customKey = self::GLOBAL_KEY, $secretID = '', $secretKey = '', $SDKAppID = '', $sign = '', $templateID = '', $hasExpiredTime = self::NOT_EXPIRED_TIME, $codeExpired = self::DEFAULT_EXPIRED, $commentNeedPhone = self::COMMENT_NEED_PHONE, $postNeedPhone = self::POST_NEED_PHONE)
    {
        $this->customKey = $customKey;
        $this->secretID = $secretID;
        $this->secretKey = $secretKey;
        $this->SDKAppID = $SDKAppID;
        $this->sign = $sign;
        $this->templateID = $templateID;
        $this->hasExpireTime = $hasExpiredTime;
        $this->codeExpired = $codeExpired;
        $this->commentNeedPhone = $commentNeedPhone;
        $this->postNeedPhone = $postNeedPhone;
        global $_G;
        if (isset($_G['setting']['tencentcloud_center'])) {
            $this->commonOptions = unserialize($_G['setting']['tencentcloud_center']);
        }
    }

    /**
     * 获取全局的配置项
     */
    public function getCommonOptions()
    {
        return $this->commonOptions;
    }


    public function setSecretID($secretID)
    {
        if ( empty($secretID) && $this->customKey !== self::GLOBAL_KEY) {
            throw new \Exception('secretID'.lang('plugin/tencentcloud_sms','param_empty'));
        }
        $this->secretID = $secretID;
    }

    public function setCustomKey($customKey)
    {
        if ( !in_array($customKey, array(self::GLOBAL_KEY, self::CUSTOM_KEY)) ) {
            throw new \Exception(lang('plugin/tencentcloud_sms','custom_error'));
        }
        $this->customKey = intval($customKey);
    }

    public function setSecretKey($secretKey)
    {
        if ( empty($secretKey) && $this->customKey !== self::GLOBAL_KEY ) {
            throw new \Exception('secretKey'.lang('plugin/tencentcloud_sms','param_empty'));
        }
        $this->secretKey = $secretKey;
    }

    public function setSDKAppID($SDKAppID)
    {
        if ( empty($SDKAppID) ) {
            throw new \Exception('SDKAppID'.lang('plugin/tencentcloud_sms','param_empty'));
        }
        $this->SDKAppID = $SDKAppID;
    }

    public function setSign($sign)
    {
        if ( empty($sign) ) {
            throw new \Exception(lang('plugin/tencentcloud_sms','sign_empty'));
        }
        $this->sign = $sign;
    }

    public function setTemplateID($templateID)
    {
        if ( empty($templateID) ) {
            throw new \Exception(lang('plugin/tencentcloud_sms','template_id_empty'));
        }
        $this->templateID = $templateID;
    }

    public function setHasExpiredTime($hasExpiredTime)
    {
        $this->hasExpireTime = intval($hasExpiredTime);
    }

    public function setCodeExpired($codeExpired)
    {
        if ( empty($codeExpired) ) {
            throw new \Exception(lang('plugin/tencentcloud_sms','code_expired_empty'));
        }
        if ( $codeExpired > 360 ) {
            $codeExpired = 360;
        }
        if ( $codeExpired < 1 ) {
            $codeExpired = 1;
        }
        $this->codeExpired = strval(ceil($codeExpired));
    }

    public function setCommentNeedPhone($commentNeedPhone)
    {
        $this->commentNeedPhone = intval($commentNeedPhone);
    }

    public function setPostNeedPhone($postNeedPhone)
    {
        $this->postNeedPhone = intval($postNeedPhone);
    }


    public function getSecretID()
    {
        if ( $this->customKey === self::GLOBAL_KEY && isset($this->commonOptions['secretId']) ) {
            $this->secretID = $this->commonOptions['secretId'] ?: '';
        }
        return $this->secretID;
    }

    public function getSecretKey()
    {
        if ( $this->customKey === self::GLOBAL_KEY && isset($this->commonOptions['secretKey']) ) {
            $this->secretKey = $this->commonOptions['secretKey'] ?: '';
        }
        return $this->secretKey;
    }

    public function getSDKAppID()
    {
        return $this->SDKAppID;
    }

    public function getSign()
    {
        return $this->sign;
    }

    public function getTemplateID()
    {
        return $this->templateID;
    }

    public function getHasExpiredTime()
    {
        return $this->hasExpireTime;
    }

    public function getCodeExpired()
    {
        return $this->codeExpired;
    }

    public function getCommentNeedPhone()
    {
        return $this->commentNeedPhone;
    }

    public function getPostNeedPhone()
    {
        return $this->postNeedPhone;
    }

    public function getCustomKey()
    {
        return $this->customKey;
    }

    public function toArray()
    {
        return array(
            'secretId'=>$this->secretID,
            'customKey'=>$this->customKey,
            'secretKey'=>$this->secretKey,
            'SDKAppID'=>$this->SDKAppID,
            'sign'=>$this->sign,
            'templateId'=>$this->templateID,
            'postNeedPhone'=>$this->postNeedPhone,
            'commentNeedPhone'=>$this->commentNeedPhone,
            'codeExpired'=>$this->codeExpired,
            'hasExpireTime'=>$this->hasExpireTime,
        );
    }
}
