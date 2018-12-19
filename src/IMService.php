<?php
namespace Lgy\IM;


class IMService {
    /**
     * @var string
     */
    private $client_id;

    /**
     * @var mixed|string
     */
    private $client_secret;

    /**
     * @var mixed|string
     */
    private $org_name;

    /**
     * @var mixed|string
     */
    private $app_name;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $EXTEND_PATH = '';

    public function __construct($client_id, $client_secret, $app_name, $org_name, $url) {
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->org_name      = $app_name;
        $this->app_name      = $org_name;
        $this->url           = $url;
    }




    /**
     * 读取file中保存的token
     * @return bool|string
     */
    private function getFileToken()
    {
        $filePath = $this->EXTEND_PATH . "hx/Hx.txt";
        if (file_exists($filePath)) {
            $fileStr = @file_get_contents($filePath, 'r');
            if ($fileStr) {
                $arr = unserialize($fileStr);
                if ($arr && !empty($arr['access_token']) && !empty($arr['expires_in'])) {
                    if ($arr['expires_in'] > time()) {
                        return "Authorization:Bearer " . $arr['access_token'];
                    }
                }
            }
        }
        return false;
    }

    /**
     * 保存token到file
     * @param $arr
     */
    private function saveFileToken($arr)
    {
        @file_put_contents($this->EXTEND_PATH . 'hx/Hx.txt', serialize($arr));
    }

    /**
     * 获取token
     * @return bool|mixed|string
     */
    function getToken()
    {
        $tokenResult = $this->getFileToken();
        if ($tokenResult) {
            return $tokenResult;
        } else {
            unset($tokenResult);
        }
        $options     = array(
            "grant_type"    => "client_credentials",
            "client_id"     => $this->client_id,
            "client_secret" => $this->client_secret
        );
        $body        = json_encode($options);
        $url         = $this->url . 'token';
        $tokenResult = $this->postCurl($url, $body, $header = array());
        if (empty($tokenResult['access_token'])) {//存在错误
            return '';
        } else {
            $tokenResult['expires_in'] += time();
            $this->saveFileToken($tokenResult);
            return "Authorization:Bearer " . $tokenResult['access_token'];
        }
    }

    /**
     * 授权注册
     * @param $username
     * @param $password
     * @return mixed
     */
    function createUser($username, $password)
    {
        $url     = $this->url . 'users';
        $options = array(
            "username" => $username,
            "password" => $password
        );
        $body    = json_encode($options);
        $header  = array($this->getToken());
        $result  = $this->postCurl($url, $body, $header);
        return $result;
    }

    /**
     * 批量注册用户
     * @param $options
     * @return mixed
     */
    function createUsers($options)
    {
        $url    = $this->url . 'users';
        $body   = json_encode($options);
        $header = array($this->getToken());
        $result = $this->postCurl($url, $body, $header);
        return $result;
    }

    /**
     * 重置用户密码
     * @param $username
     * @param $newPassword
     * @return mixed
     */
    function resetPassword($username, $newPassword)
    {
        $url     = $this->url . 'users/' . $username . '/password';
        $options = array(
            "newpassword" => $newPassword
        );
        $body    = json_encode($options);
        $header  = array($this->getToken());
        $result  = $this->postCurl($url, $body, $header, "PUT");
        return $result;
    }

    /**
     * 获取单个用户
     * @param $username
     * @return mixed
     */
    function getUser($username)
    {
        $url    = $this->url . 'users/' . $username;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, "GET");
        return $result;
    }

    /**
     * 获取批量用户----不分页
     * @param int $limit
     * @return mixed
     */
    function getUsers($limit = 0)
    {
        if (!empty($limit)) {
            $url = $this->url . 'users?limit=' . $limit;
        } else {
            $url = $this->url . 'users';
        }
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, "GET");
        return $result;
    }

    /**
     * 获取批量用户---分页
     * @param int    $limit
     * @param string $cursor
     * @return mixed
     */
    function getUsersForPage($limit = 0, $cursor = '')
    {
        $url    = $this->url . 'users?limit=' . $limit . '&cursor=' . $cursor;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, "GET");
        if (!empty($result["cursor"])) {
            $cursor = $result["cursor"];
            $this->writeCursor("userfile.txt", $cursor);
        }
        return $result;
    }


    /**
     * 创建文件夹
     * @param     $dir
     * @param int $mode
     * @return bool
     */
    function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
        if (!$this->mkdirs(dirname($dir), $mode)) return FALSE;
        return @mkdir($dir, $mode);
    }

    /**
     * 写入cursor
     * @param $filename
     * @param $content
     */
    function writeCursor($filename, $content)
    {
        if (!file_exists("resource/txtfile")) {
            $this->mkdirs("resource/txtfile",0777);
        }
        $myFile = @fopen("resource/txtfile/" . $filename, "w+") or die("Unable to open file!");
        @fwrite($myFile, $content);
        fclose($myFile);
    }

    /**
     * 读取cursor
     * @param $filename
     * @return bool|string
     */
    function readCursor($filename)
    {
        //判断文件夹是否存在，不存在的话创建
        if (!file_exists("resource/txtfile")) {
            $this->mkdirs("resource/txtfile" ,0777);
            chmod("resource/txtfile" ,0777);
        }
        $file = "resource/txtfile/" . $filename;
        $fp   = fopen($file, "a+");//这里这设置成a+
        if ($fp) {
            while (!feof($fp)) {
                //第二个参数为读取的长度
                $data = fread($fp, 1000);
            }
            fclose($fp);
        }
        return $data;
    }

    /**
     * 删除单个用户
     * @param $username
     * @return mixed
     */
    function deleteUser($username)
    {
        $url    = $this->url . 'users/' . $username;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;
    }

    /**
     * 删除批量用户
     * limit:建议在100-500之间，
     * 注：具体删除哪些并没有指定, 可以在返回值中查看。
     * @param $limit
     * @return mixed
     */
    function deleteUsers($limit)
    {
        $url    = $this->url . 'users?limit=' . $limit;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;

    }

    /**
     * 修改用户昵称
     * @param $username
     * @param $nickname
     * @return mixed
     */
    function editNickname($username, $nickname)
    {
        $url     = $this->url . 'users/' . $username;
        $options = array(
            "nickname" => $nickname
        );
        $body    = json_encode($options);
        $header  = array($this->getToken());
        $result  = $this->postCurl($url, $body, $header, 'PUT');
        return $result;
    }

    /**
     * 添加好友
     * @param $username
     * @param $friend_name
     * @return mixed
     */
    function addFriend($username, $friend_name)
    {
        $url    = $this->url . 'users/' . $username . '/contacts/users/' . $friend_name;
        $header = array($this->getToken(), 'Content-Type:application/json');
        $result = $this->postCurl($url, '', $header, 'POST');
        return $result;


    }

    /**
     * 删除好友
     * @param $username
     * @param $friend_name
     * @return mixed
     */
    function deleteFriend($username, $friend_name)
    {
        $url    = $this->url . 'users/' . $username . '/contacts/users/' . $friend_name;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;

    }

    /**
     * 查看好友
     * @param $username
     * @return mixed
     */
    function showFriends($username)
    {
        $url    = $this->url . 'users/' . $username . '/contacts/users';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;

    }

    /**
     * 查看用户黑名单
     * @param $username
     * @return mixed
     */
    function getBlacklist($username)
    {
        $url    = $this->url . 'users/' . $username . '/blocks/users';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;

    }

    /**
     * 往黑名单中加人
     * @param $username
     * @param $usernames
     * @return mixed
     */
    function addUserForBlacklist($username, $usernames)
    {
        $url    = $this->url . 'users/' . $username . '/blocks/users';
        $body   = json_encode($usernames);
        $header = array($this->getToken());
        $result = $this->postCurl($url, $body, $header, 'POST');
        return $result;

    }

    /**
     * 从黑名单中减人
     * @param $username
     * @param $blocked_name
     * @return mixed
     */
    function deleteUserFromBlacklist($username, $blocked_name)
    {
        $url    = $this->url . 'users/' . $username . '/blocks/users/' . $blocked_name;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;

    }

    /**
     * 查看用户是否在线
     * @param $username
     * @return mixed
     */
    function isOnline($username)
    {
        $url    = $this->url . 'users/' . $username . '/status';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;

    }

    /**
     * 查看用户离线消息数
     * @param $username
     * @return mixed
     */
    function getOfflineMessages($username)
    {
        $url    = $this->url . 'users/' . $username . '/offline_msg_count';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;

    }

    /**
     * 查看某条消息的离线状态
     * deliverd 表示此用户的该条离线消息已经收到
     * @param $username
     * @param $msg_id
     * @return mixed
     */
    function getOfflineMessageStatus($username, $msg_id)
    {
        $url    = $this->url . 'users/' . $username . '/offline_msg_status/' . $msg_id;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;

    }

    /**
     * 禁用用户账号
     * @param $username
     * @return mixed
     */
    function deactiveUser($username)
    {
        $url    = $this->url . 'users/' . $username . '/deactivate';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header);
        return $result;
    }

    /**
     * 解禁用户账号
     * @param $username
     * @return mixed
     */
    function activeUser($username)
    {
        $url    = $this->url . 'users/' . $username . '/activate';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header);
        return $result;
    }

    /**
     * 强制用户下线
     * @param $username
     * @return mixed
     */
    function disconnectUser($username)
    {
        $url    = $this->url . 'users/' . $username . '/disconnect';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;
    }

    //--------------------------------------------------------上传下载

    /**
     * 上传图片或文件
     * @param $filePath
     * @return mixed
     */
    function uploadFile($filePath)
    {
        $url          = $this->url . 'chatfiles';
        $file         = file_get_contents($filePath);
        $body['file'] = $file;
        $header       = array('enctype:multipart/form-data', $this->getToken(), "restrict-access:true");
        $result       = $this->postCurl($url, $body, $header, 'XXX');
        return $result;

    }

    /**
     * 下载文件或图片
     * @param $uuid
     * @param $shareSecret
     * @return string
     */
    function downloadFile($uuid, $shareSecret)
    {
        $url      = $this->url . 'chatfiles/' . $uuid;
        $header   = array("share-secret:" . $shareSecret, "Accept:application/octet-stream", $this->getToken());
        $result   = $this->postCurl($url, '', $header, 'GET');
        $filename = md5(time() . mt_rand(10, 99)) . ".png"; //新图片名称
        if (!file_exists("resource/down")) {
            //mkdir("../image/down");
            $this->mkdirs("resource/down/");
        }

        $file = @fopen("resource/down/" . $filename, "w+");//打开文件准备写入
        @fwrite($file, $result);//写入
        fclose($file);//关闭
        return $filename;

    }

    /**
     * 下载图片缩略图
     * @param $uuid
     * @param $shareSecret
     * @return string
     */
    function downloadThumbnail($uuid, $shareSecret)
    {
        $url      = $this->url . 'chatfiles/' . $uuid;
        $header   = array("share-secret:" . $shareSecret, "Accept:application/octet-stream", $this->getToken(), "thumbnail:true");
        $result   = $this->postCurl($url, '', $header, 'GET');
        $filename = md5(time() . mt_rand(10, 99)) . "th.png"; //新图片名称
        if (!file_exists("resource/down")) {
            //mkdir("../image/down");
            $this->mkdirs("resource/down/");
        }

        $file = @fopen("resource/down/" . $filename, "w+");//打开文件准备写入
        @fwrite($file, $result);//写入
        fclose($file);//关闭
        return $filename;
    }


    //--------------------------------------------------------发送消息

    /**
     * 发送文本消息
     * @param string $from
     * @param        $target_type
     * @param        $target
     * @param        $content
     * @param        $ext
     * @param        $to
     * @return mixed
     */
    function sendText($from = "admin", $target_type, $target, $content, $ext , $to='')
    {
        $url                 = $this->url . 'messages';
        $body['target_type'] = $target_type;
        $body['target']      = $target;
        $options['type']     = "txt";
        $options['msg']      = $content;
        $body['msg']         = $options;
        $body['from']        = $from;
        $body['ext']         = $ext;
        $b                   = json_encode($body);
        $header              = array($this->getToken());
        $result              = $this->postCurl($url, $b, $header);
        $result['argument']  = $to;
        return $result;
    }

    /**
     * 发送透传消息
     * @param string $from
     * @param        $target_type
     * @param        $target
     * @param        $action
     * @param        $ext
     * @return mixed
     */
    function sendCmd($from = "admin", $target_type, $target, $action, $ext)
    {
        $url                 = $this->url . 'messages';
        $body['target_type'] = $target_type;
        $body['target']      = $target;
        $options['type']     = "cmd";
        $options['action']   = $action;
        $body['msg']         = $options;
        $body['from']        = $from;
        $body['ext']         = $ext;
        $b                   = json_encode($body);
        $header              = array($this->getToken());
        //$b=json_encode($body,true);
        $result = $this->postCurl($url, $b, $header);
        return $result;
    }

    /**
     * 发图片消息
     * @param        $filePath
     * @param string $from
     * @param        $target_type
     * @param        $target
     * @param        $filename
     * @param        $ext
     * @return mixed
     */
    function sendImage($filePath, $from = "admin", $target_type, $target, $filename, $ext)
    {
        $result              = $this->uploadFile($filePath);
        $uri                 = $result['uri'];
        $uuid                = $result['entities'][0]['uuid'];
        $shareSecret         = $result['entities'][0]['share-secret'];
        $url                 = $this->url . 'messages';
        $body['target_type'] = $target_type;
        $body['target']      = $target;
        $options['type']     = "img";
        $options['url']      = $uri . '/' . $uuid;
        $options['filename'] = $filename;
        $options['secret']   = $shareSecret;
        $options['size']     = array(
            "width"  => 480,
            "height" => 720
        );
        $body['msg']         = $options;
        $body['from']        = $from;
        $body['ext']         = $ext;
        $b                   = json_encode($body);
        $header              = array($this->getToken());
        //$b=json_encode($body,true);
        $result = $this->postCurl($url, $b, $header);
        return $result;
    }

    /**
     * 发语音消息
     * @param        $filePath
     * @param string $from
     * @param        $target_type
     * @param        $target
     * @param        $filename
     * @param        $length
     * @param        $ext
     * @return mixed
     */
    function sendAudio($filePath, $from = "admin", $target_type, $target, $filename, $length, $ext)
    {
        $result              = $this->uploadFile($filePath);
        $uri                 = $result['uri'];
        $uuid                = $result['entities'][0]['uuid'];
        $shareSecret         = $result['entities'][0]['share-secret'];
        $url                 = $this->url . 'messages';
        $body['target_type'] = $target_type;
        $body['target']      = $target;
        $options['type']     = "audio";
        $options['url']      = $uri . '/' . $uuid;
        $options['filename'] = $filename;
        $options['length']   = $length;
        $options['secret']   = $shareSecret;
        $body['msg']         = $options;
        $body['from']        = $from;
        $body['ext']         = $ext;
        $b                   = json_encode($body);
        $header              = array($this->getToken());
        //$b=json_encode($body,true);
        $result = $this->postCurl($url, $b, $header);
        return $result;
    }

    /**
     * 发视频消息
     * @param        $filePath
     * @param string $from
     * @param        $target_type
     * @param        $target
     * @param        $filename
     * @param        $length
     * @param        $thumb
     * @param        $thumb_secret
     * @param        $ext
     * @return mixed
     */
    function sendVedio($filePath, $from = "admin", $target_type, $target, $filename, $length, $thumb, $thumb_secret, $ext)
    {
        $result                  = $this->uploadFile($filePath);
        $uri                     = $result['uri'];
        $uuid                    = $result['entities'][0]['uuid'];
        $shareSecret             = $result['entities'][0]['share-secret'];
        $url                     = $this->url . 'messages';
        $body['target_type']     = $target_type;
        $body['target']          = $target;
        $options['type']         = "video";
        $options['url']          = $uri . '/' . $uuid;
        $options['filename']     = $filename;
        $options['thumb']        = $thumb;
        $options['length']       = $length;
        $options['secret']       = $shareSecret;
        $options['thumb_secret'] = $thumb_secret;
        $body['msg']             = $options;
        $body['from']            = $from;
        $body['ext']             = $ext;
        $b                       = json_encode($body);
        $header                  = array($this->getToken());
        //$b=json_encode($body,true);
        $result = $this->postCurl($url, $b, $header);
        return $result;
    }

    /**
     * 发文件消息
     * @param        $filePath
     * @param string $from
     * @param        $target_type
     * @param        $target
     * @param        $filename
     * @param        $length
     * @param        $ext
     * @return mixed
     */
    function sendFile($filePath, $from = "admin", $target_type, $target, $filename, $length, $ext)
    {
        $result              = $this->uploadFile($filePath);
        $uri                 = $result['uri'];
        $uuid                = $result['entities'][0]['uuid'];
        $shareSecret         = $result['entities'][0]['share-secret'];
        $url                 = $GLOBALS['base_url'] . 'messages';
        $body['target_type'] = $target_type;
        $body['target']      = $target;
        $options['type']     = "file";
        $options['url']      = $uri . '/' . $uuid;
        $options['filename'] = $filename;
        $options['length']   = $length;
        $options['secret']   = $shareSecret;
        $body['msg']         = $options;
        $body['from']        = $from;
        $body['ext']         = $ext;
        $b                   = json_encode($body);
        $header              = array($this->getToken());
        //$b=json_encode($body,true);
        $result = $this->postCurl($url, $b, $header);
        return $result;
    }

    /**
     * 获取app中的所有群组----不分页
     * @param int $limit
     * @return mixed
     */
    function getGroups($limit = 0)
    {
        if (!empty($limit)) {
            $url = $this->url . 'chatgroups?limit=' . $limit;
        } else {
            $url = $this->url . 'chatgroups';
        }

        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, "GET");
        return $result;
    }

    /**
     * 获取app中的所有群组---分页
     * @param int    $limit
     * @param string $cursor
     * @return mixed
     */
    function getGroupsForPage($limit = 0, $cursor = '')
    {
        $url    = $this->url . 'chatgroups?limit=' . $limit . '&cursor=' . $cursor;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, "GET");

        if (!empty($result["cursor"])) {
            $cursor = $result["cursor"];
            $this->writeCursor("groupfile.txt", $cursor);
        }
        return $result;
    }

    /**
     * 获取一个或多个群组的详情
     * @param $group_ids
     * @return mixed
     */
    function getGroupDetail($group_ids)
    {
        $g_ids  = implode(',', $group_ids);
        $url    = $this->url . 'chatgroups/' . $g_ids;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;
    }

    /**
     * 创建一个群组
     * @param $options
     * @return mixed
     */
    function createGroup($options)
    {
        $url    = $this->url . 'chatgroups';
        $header = array($this->getToken());
        $body   = json_encode($options);
        $result = $this->postCurl($url, $body, $header);
        return $result;
    }

    /**
     * 修改群组信息
     * @param $group_id
     * @param $options
     * @return mixed
     */
    function modifyGroupInfo($group_id, $options)
    {
        $url    = $this->url . 'chatgroups/' . $group_id;
        $body   = json_encode($options);
        $header = array($this->getToken());
        $result = $this->postCurl($url, $body, $header, 'PUT');
        return $result;
    }

    /**
     * 删除群组
     * @param $group_id
     * @return mixed
     */
    function deleteGroup($group_id)
    {
        $url    = $this->url . 'chatgroups/' . $group_id;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;
    }

    /**
     * 获取群组中的成员
     * @param $group_id
     * @return mixed
     */
    function getGroupUsers($group_id)
    {
        $url    = $this->url . 'chatgroups/' . $group_id . '/users';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;
    }

    /**
     * 群组单个加人
     * @param $group_id
     * @param $username
     * @return mixed
     */
    function addGroupMember($group_id, $username)
    {
        $url    = $this->url . 'chatgroups/' . $group_id . '/users/' . $username;
        $header = array($this->getToken(), 'Content-Type:application/json');
        $result = $this->postCurl($url, '', $header);
        return $result;
    }

    /**
     * 群组批量加人
     * @param $group_id
     * @param $usernames
     * @return mixed
     */
    function addGroupMembers($group_id, $usernames)
    {
        $url    = $this->url . 'chatgroups/' . $group_id . '/users';
        $body   = json_encode($usernames);
        $header = array($this->getToken(), 'Content-Type:application/json');
        $result = $this->postCurl($url, $body, $header);
        return $result;
    }

    /**
     * 群组单个减人
     * @param $group_id
     * @param $username
     * @return mixed
     */
    function deleteGroupMember($group_id, $username)
    {
        $url    = $this->url . 'chatgroups/' . $group_id . '/users/' . $username;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;
    }

    /**
     * 群组批量减人
     * @param $group_id
     * @param $usernames
     * @return mixed
     */
    function deleteGroupMembers($group_id, $usernames)
    {
        $url = $this->url . 'chatgroups/' . $group_id . '/users/' . $usernames;
        //$body=json_encode($usernames);
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;
    }

    /**
     * 获取一个用户参与的所有群组
     * @param $username
     * @return mixed
     */
    function getGroupsForUser($username)
    {
        $url    = $this->url . 'users/' . $username . '/joined_chatgroups';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;
    }

    /**
     * 群组转让
     * @param $group_id
     * @param $options
     * @return mixed
     */
    function changeGroupOwner($group_id, $options)
    {
        $url    = $this->url . 'chatgroups/' . $group_id;
        $body   = json_encode($options);
        $header = array($this->getToken());
        $result = $this->postCurl($url, $body, $header, 'PUT');
        return $result;
    }

    /**
     * 查询一个群组黑名单用户名列表
     * @param $group_id
     * @return mixed
     */
    function getGroupBlackList($group_id)
    {
        $url    = $this->url . 'chatgroups/' . $group_id . '/blocks/users';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;
    }

    /**
     * 群组黑名单单个加人
     * @param $group_id
     * @param $username
     * @return mixed
     */
    function addGroupBlackMember($group_id, $username)
    {
        $url    = $this->url . 'chatgroups/' . $group_id . '/blocks/users/' . $username;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header);
        return $result;
    }

    /**
     * 群组黑名单批量加人
     * @param $group_id
     * @param $usernames
     * @return mixed
     */
    function addGroupBlackMembers($group_id, $usernames)
    {
        $url    = $this->url . 'chatgroups/' . $group_id . '/blocks/users';
        $body   = json_encode($usernames);
        $header = array($this->getToken());
        $result = $this->postCurl($url, $body, $header);
        return $result;
    }

    /**
     * 群组黑名单批量减人
     * @param $group_id
     * @param $username
     * @return mixed
     */
    function deleteGroupBlackMember($group_id, $username)
    {
        $url    = $this->url . 'chatgroups/' . $group_id . '/blocks/users/' . $username;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;
    }

    /**
     * 群组黑名单批量减人
     * @param $group_id
     * @param $usernames
     * @return mixed
     */
    function deleteGroupBlackMembers($group_id, $usernames)
    {
        $url    = $this->url . 'chatgroups/' . $group_id . '/blocks/users';
        $body   = json_encode($usernames);
        $header = array($this->getToken());
        $result = $this->postCurl($url, $body, $header, 'DELETE');
        return $result;
    }

    //-------------------------------------------------------------聊天室操作

    /**
     * 创建聊天室
     * @param $options
     * @return mixed
     */
    function createChatRoom($options)
    {
        $url    = $this->url . 'chatrooms';
        $header = array($this->getToken());
        $body   = json_encode($options);
        $result = $this->postCurl($url, $body, $header);
        return $result;
    }

    /**
     * 修改聊天室信息
     * @param $chatroom_id
     * @param $options
     * @return mixed
     */
    function modifyChatRoom($chatroom_id, $options)
    {
        $url    = $this->url . 'chatrooms/' . $chatroom_id;
        $header = array($this->getToken());
        $body   = json_encode($options);
        $result = $this->postCurl($url, $body, $header, 'PUT');
        return $result;
    }

    /**
     * 删除聊天室
     * @param $chatroom_id
     * @return mixed
     */
    function deleteChatRoom($chatroom_id)
    {
        $url    = $this->url . 'chatrooms/' . $chatroom_id;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;
    }

    /**
     * 获取app中所有的聊天室
     * @return mixed
     */
    function getChatRooms()
    {
        $url    = $this->url . 'chatrooms';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, "GET");
        return $result;
    }

    /**
     * 获取一个聊天室的详情
     * @param $chatroom_id
     * @return mixed
     */
    function getChatRoomDetail($chatroom_id)
    {
        $url    = $this->url . 'chatrooms/' . $chatroom_id;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;
    }

    /**
     * 获取一个用户加入的所有聊天室
     * @param $username
     * @return mixed
     */
    function getChatRoomJoined($username)
    {
        $url    = $this->url . 'users/' . $username . '/joined_chatrooms';
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'GET');
        return $result;
    }

    /**
     * 聊天室单个成员添加
     * @param $chatroom_id
     * @param $username
     * @return mixed
     */
    function addChatRoomMember($chatroom_id, $username)
    {
        $url = $this->url . 'chatrooms/' . $chatroom_id . '/users/' . $username;
        //$header=array($this->getToken());
        $header = array($this->getToken(), 'Content-Type:application/json');
        $result = $this->postCurl($url, '', $header);
        return $result;
    }

    /**
     * 聊天室批量成员添加
     * @param $chatroom_id
     * @param $usernames
     * @return mixed
     */
    function addChatRoomMembers($chatroom_id, $usernames)
    {
        $url    = $this->url . 'chatrooms/' . $chatroom_id . '/users';
        $body   = json_encode($usernames);
        $header = array($this->getToken());
        $result = $this->postCurl($url, $body, $header);
        return $result;
    }

    /**
     * 聊天室单个成员删除
     * @param $chatroom_id
     * @param $username
     * @return mixed
     */
    function deleteChatRoomMember($chatroom_id, $username)
    {
        $url    = $this->url . 'chatrooms/' . $chatroom_id . '/users/' . $username;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;
    }

    /**
     * 聊天室批量成员删除
     * @param $chatroom_id
     * @param $usernames
     * @return mixed
     */
    function deleteChatRoomMembers($chatroom_id, $usernames)
    {
        $url = $this->url . 'chatrooms/' . $chatroom_id . '/users/' . $usernames;
        //$body=json_encode($usernames);
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, 'DELETE');
        return $result;
    }

    //-------------------------------------------------------------聊天记录

    /**
     * 导出聊天记录----不分页
     * @param $ql
     * @return mixed
     */
    function getChatRecord($ql)
    {
        if (!empty($ql)) {
            $url = $this->url . 'chatmessages?ql=' . $ql;
        } else {
            $url = $this->url . 'chatmessages';
        }
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, "GET");
        return $result;
    }


    /**
     * 导出聊天记录---分页
     * @param     $ql
     * @param int $limit
     * @param     $cursor
     * @return mixed
     */
    function getChatRecordForPage($ql, $limit = 0, $cursor)
    {
        if (!empty($ql)) {
            $url = $this->url . 'chatmessages?ql=' . $ql . '&limit=' . $limit . '&cursor=' . $cursor;
        }
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, "GET");
        $cursor = $result["cursor"];
        $this->writeCursor("chatfile.txt", $cursor);
        //var_dump($GLOBALS['cursor'].'00000000000000');
        return $result;
    }

    /**
     * 导出聊天记录----不分页
     * @param $time
     * @return mixed
     */
    function getChatRecordFile($time)
    {
        $url     = $this->url . 'chatmessages/' . $time;
        $header = array($this->getToken());
        $result = $this->postCurl($url, '', $header, "GET");
        return $result;
    }

    /**
     * @param        $url
     * @param        $body
     * @param        $header
     * @param string $type
     * @return mixed
     */
    function postCurl($url, $body, $header, $type = "POST")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_URL, $url);
        //1)设置请求头
        //array_push($header, 'Accept:application/json');
        //array_push($header,'Content-Type:application/json');
        //array_push($header, 'http:multipart/form-data');
        //设置为false,只会获得响应的正文(true的话会连响应头一并获取到)
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 设置超时限制防止死循环
        //设置发起连接前的等待时间，如果设置为0，则无限等待。
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //2)设备请求体
        if (count($body) > 0) {
            //$b=json_encode($body,true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);//全部数据使用HTTP协议中的"POST"操作来发送。
        }
        //设置请求头
        if (count($header) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        //上传文件相关设置
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算
        //3)设置提交方式
        switch ($type) {
            case "GET":
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case "PUT"://使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请									                     求。这对于执行"DELETE" 或者其他更隐蔽的HTT
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
        }
        //4)在HTTP请求中包含一个"User-Agent: "头的字符串。-----必设
        curl_setopt($ch, CURLOPT_USERAGENT, 'SSTS Browser/1.0');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        // 模拟用户使用的浏览器(5)
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)');

        //3.抓取URL并把它传递给浏览器
        $res    = curl_exec($ch);
        $result = json_decode($res, true);
        //4.关闭curl资源，并且释放系统资源
        curl_close($ch);
        return empty($result) ? $res : $result;
    }
}





