<?php
loadcore();
global $_G;
$lang = lang('api/wechat_bind');
if (!isset($_GET['openid'])) {
    showmessage("$lang[tips]", '/forum.php');
}
$openid = htmlspecialchars(trim($_GET['openid']));

require_once DISCUZ_ROOT . './source/plugin/wechat/wechat.lib.class.php';

/*
$token = $client->getAccessToken();
dd($token);
*/
// ��ʼ��
$flag = false; // �ж��Ƿ�ͨ����
$message = ''; // δͨ����ʱ�Ĵ�����ʾ
$mobile = checkmobile(); // ����Ƿ����ֻ���������, ���ж��Ƿ�ת��
$has_login = (isset($_GET['type']) && trim($_GET['type']) == 'other');
if (!$_G['uid'] || $has_login) {
// ��֤�û��ύ��
    if (submitcheck('bindsubmit')) {
        if (!isset($_GET) || !$_GET['username'] || !$_GET['password'] || !$_GET['openid']) {
            showmessage("$lang[system_error_or_unfirendly_access]", "/api.php?mod=bind&openid=$openid");
        }
        // ��֤���û��Ƿ���ڣ��Լ������Ƿ���ȷ��
        $username = (bool)$mobile ? dhtmlspecialchars(trim($_GET['username'])) : dhtmlspecialchars(trim(diconv($_GET['username'], 'UTF-8', CHARSET)));
        $password = trim($_GET['password']);
        loaducenter();
        $result = uc_user_login($username, $password);
        /*
        // ���Ա�������
        dd($username);
        dd($result);
        exit;
        */
        /*
         * ���� $result[0] ��ֵ���ж��Ƿ���֤�ɹ�
         * ֵ > 0 ʱ����֤�ɹ����Ҹ�ֵΪ�û��� uid
         * ֵ = -1 ʱ����֤ʧ�ܣ�ԭ�����û���������
         * ֵ = -2 ʱ����֤ʧ�ܣ�ԭ�������벻��ȷ
         */
        if ($result[0] < 0) {
            switch ($result[0]) {
                case -1:
                    $message = $lang['username_not_register'];
                    break;
                case -2:
                    $message = $lang['password_not_corret'];
                    break;
                default:
                    showmessage("$lang[system_error_or_unfirendly_access]", "/api.php?mod=bind&openid=$openid");
                    break;
            }
        } else {
            $result = getResult($result[0], $openid, $lang);
            $result === true ? $flag = true : $message = $result;
        }
    }
    include_once template('api/wechat/bind');
} else {
    if (submitcheck('fastbindsubmit')) {
        $result = getResult($_G['uid'], $openid, $lang);
        $result === true ? $flag = true : $message = $result;
    }
    include_once template('api/wechat/fast_bind');
}

function getResult ($uid, $openid, $lang){
    global $_G;
    $_G['wechat']['setting'] = unserialize($_G['setting']['mobilewechat']);
    $client = new WeChatClient($_G['wechat']['setting']['wechat_appId'], $_G['wechat']['setting']['wechat_appsecret']);
    $uid_result = C::t("#wechat#common_member_wechat")->fetch_by_uid($uid);
    $openid_result = C::t("#wechat#common_member_wechat")->fetch_by_openid($openid);
    if (!empty($uid_result)) {
        return $lang['mould_has_been_binded'];
    } elseif (!empty($openid_result)) {
        return $lang['openid_has_been_binded'];
    } else {
        weChatHook::bindOpenId($uid, $openid);
        updatemembercount($uid, array('2' => '10'), true, '', 667, '', '�󶨹��ں�', '�״ΰ󶨹��ںŻ�ý���');
        $client->sendTextMsg($openid, urlencode(diconv("��ϲ�����״ΰ󶨳ɹ���/::P\n\n��� 10 ��Ǯ��<a href='$_G[siteurl]'>������̳�в鿴</a>/:circle", CHARSET, 'UTF-8')));
        return true;
    }
}
?>