<?php
// 应用公共文件

if (!function_exists('strcut')) {
    /**
     * 字符截取 支持UTF8/GBK
     * @param $string
     * @param $length
     * @param string $dot
     * @return string
     */
    function strcut($string, $length, $dot = '...')
    { //$string 为截取的原文字信息   $length截取长度   $dot为填充符
        $charset = 'utf-8';
        if (strlen($string) <= $length) {
            return $string;
        }
        $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
        $strcut = '';
        if (strtolower($charset) == 'utf-8') {
            $n = $tn = $noc = 0;
            while ($n < strlen($string)) {
                $t = ord($string[$n]);
                if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $tn = 1;
                    $n++;
                    $noc++;
                } elseif (194 <= $t && $t <= 223) {
                    $tn = 2;
                    $n += 2;
                    $noc += 2;
                } elseif (224 <= $t && $t <= 239) {
                    $tn = 3;
                    $n += 3;
                    $noc += 2;
                } elseif (240 <= $t && $t <= 247) {
                    $tn = 4;
                    $n += 4;
                    $noc += 2;
                } elseif (248 <= $t && $t <= 251) {
                    $tn = 5;
                    $n += 5;
                    $noc += 2;
                } elseif ($t == 252 || $t == 253) {
                    $tn = 6;
                    $n += 6;
                    $noc += 2;
                } else {
                    $n++;
                }
                if ($noc >= $length) {
                    break;
                }
            }
            if ($noc > $length) {
                $n -= $tn;
            }
            $strcut = substr($string, 0, $n);
        } else {
            for ($i = 0; $i < $length; $i++) {
                $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
            }
        }
        $strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
        return $strcut . $dot;
    }
}

if (!function_exists('clear_html')) {
    /**
     * 清楚HTML标记
     * @param $str
     * @return string|string[]|null
     */
    function clear_html($str)
    {
        $str = str_replace(array('&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array(' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $str);
        $str = preg_replace("/\<[a-z]+(.*)\>/iU", "", $str);
        $str = preg_replace("/\<\/[a-z]+\>/iU", "", $str);
        $str = str_replace(array(' ', '  ', chr(13), chr(10), '&nbsp;'), array('', '', '', '', ''), $str);
        return $str;
    }
}

if (!function_exists('upload_img')) {
    /**
     * 图片上传
     * @param $name -为文件原名称
     * @param $size -为大小kb
     * @param $tmpname -为临时文件名
     * @param $dir -上传路径
     * @param string $maxsize
     * @return array
     */
    function upload_img($name, $size, $tmpname, $dir, $maxsize = '1048576')
    {
        // $picname = $_FILES['file']['name'];
        // $picsize = $_FILES['file']['size'];
        //$_FILES['file']['tmp_name']
        $picname = $name;
        $picsize = $size;
        if ($picname != "") {
            if ($picsize > $maxsize) { //限制上传大小

                echo json_encode(array('error' => 1, 'msg' => '图片大小不能超过' . ($maxsize / 1024 / 1024) . 'M'));
                die;
            }
            if ($picsize < 1024) { //限制最低上传大小
                echo '图片大小不能低于1k';
                exit;
            }

            $type = '.' . pathinfo($picname, PATHINFO_EXTENSION); //限制上传格式
            if ($type != ".gif" && $type != ".jpg" && $type != ".jpeg" && $type != ".bmp" && $type != ".png") {
                echo json_encode(array('error' => 1, 'msg' => '图片格式不对！'));
                die;
            }
            $rand = rand(100, 999);
            $pics = date("YmdHis") . $rand . $type; //命名图片名称
            //上传路径
            $r_path = $dir . date("Ymd") . "/";
            $path = $dir . date("Ymd") . "/";
            if (!file_exists($path)) {
                mkdir($path, 0777, true); //不存在创建目录
            }
            $pic_path = $path . $pics;
            $pic_path_r = $r_path . $pics;
            move_uploaded_file($tmpname, $pic_path);
        }
        $size = round($picsize / 1024, 2); //转换成kb
        $arr = array(
            'name' => $picname,
            'pic' => $pics,
            'size' => $size,
            'url' => $pic_path_r
        );
        return $arr;
    }
}

if (!function_exists('img_move')) {
    /**图片移动位置函数
     * @param $type -1为单个图片，2为富文本多标签替换路径
     * @param $str -原路径或者html
     * @param $newFile - *$type类型：1为单个图片，2为富文本多标签替换路径 $str原路径或者html 新的移动路径
     * @return string|string[]|null
     */
    function imgmove($type, $str, $newFile)
    {

        if ($type == 1) {
            $newFile = $newFile . date("Ymd") . '/';//新目录按日期分类
            if (!file_exists($newFile)) {//判断新目录是否存在
//            echo 123;die;
                mkdir($newFile, 0777, true); //不存在创建目录
            }

            //远程图片处理
            if (preg_match('/(http|ftp|https):\/\/([\w.]+\/?)\S*/', $str, $arr)) {
                $img_type = '.' . pathinfo($arr[0], PATHINFO_EXTENSION); //获取文件后缀
                if ($img_type == ".gif" || $img_type == ".jpg" || $img_type == ".jpeg" || $img_type == ".bmp" || $img_type == ".png") {//判断限制下载格式
                    $img = file_get_contents($arr[0]);//下载远程图片
                    $rand = rand(100, 999);//随机函数，用于下载图片文件名
                    //命名图片名称
                    $pics = '/uploads/temp/ueditor/image/' . date("YmdHis") . $rand . $img_type;
                    if (file_put_contents($pics, $img)) {//重新命名文件
                        $str = $pics;//加入移动对象
                    }
//                echo 12;die;
                }
            }
            $new = $newFile . basename($str);//新文件路径
            if (file_exists($str) && copy($str, $new)) { //拷贝到新目录
                unlink($str); //删除旧目录下的文件
                return $new;
            }

        } else if ($type == 2) {
            $pattern = "/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png|jpeg))\"?.+>/i";//html图片正则
            preg_match_all($pattern, $str, $match);//查找对应规则图片
            $newFile = $newFile . date("Ymd") . '/';//新目录按日期分类
            if (!file_exists($newFile)) {//判断新目录是否存在
                mkdir($newFile, 0777, true); //不存在创建目录
            }
            //替换开始，替换所有图片
            foreach ($match[1] as $file) {
                //远程图片处理
                if (preg_match('/(http|ftp|https):\/\/([\w.]+\/?)\S*/', $file, $arr)) {
                    $img_type = '.' . pathinfo($arr[0], PATHINFO_EXTENSION); //获取文件后缀
                    if ($img_type == ".gif" || $img_type == ".jpg" || $img_type == ".jpeg" || $img_type == ".bmp" || $img_type == ".png") {//判断限制下载格式
                        $img = file_get_contents($arr[0]);//下载远程图片
                        $rand = rand(100, 999);//随机函数，用于下载图片文件名
                        //命名图片名称
                        $pics = '/uploads/temp/ueditor/image/' . date("YmdHis") . $rand . $img_type;
                        if (file_put_contents($pics, $img)) {//重新命名文件
                            $file = $pics;//加入移动对象
                        }
                    }
                }
                $new = $newFile . basename($file);//新文件路径
                if (file_exists($file) && copy($file, '..' . $new)) { //拷贝到新目录
                    unlink($file); //删除旧目录下的文件
                }
            }
            //替换后
            $newstr = preg_replace('/(<img.+?src=")[^"]+(\/.+?>)/', '$1' . $newFile . '$2', $str);
            return $newstr;
        }
    }
}

if (!function_exists('alert')) {
    /**
     * 格式化输出函数
     * @param $data
     * @param string $flag
     */
    function alert($data = '', $flag = '')
    {
        if ($flag == 1) {
            echo '<pre>';
            print_r($data);
        } else {
            echo '<pre>';
            print_r($data);
            die;
        }
    }
}

if (!function_exists('verify_encrypt')) {
    /**
     * 加密解密函数
     * 加密verify_encrypt($password,'E','www.xiaohan.com') 解密 verify_encrypt($password,'D','www.xiaohan.com')
     * @param $string
     * @param $operation
     * @param string $key
     * @return false|string|string[]
     */
    function verify_encrypt($string, $operation, $key = '')
    {
        $key = md5($key);
        $key_length = strlen($key);
        $string = $operation == 'D' ? base64_decode($string) : substr(md5($string . $key), 0, 8) . $string;
        $string_length = strlen($string);
        $rndkey = $box = array();
        $result = '';
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'D') {
            if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
                return substr($result, 8);
            } else {
                return '';
            }
        } else {
            return str_replace('=', '', base64_encode($result));
        }
    }
}

if (!function_exists('get_ip')) {
    /**
     * 获取IP地址
     * @return array|false|mixed|string|null
     */
    function get_ip()
    {
        static $realip = NULL;
        if ($realip !== NULL) return $realip;
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($arr as $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        $realip = $ip;
                        break;
                    }
                }
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    $realip = $_SERVER['REMOTE_ADDR'];
                } else {
                    $realip = '0.0.0.0';
                }
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }
        preg_match('/[\d\.]{7,15}/', $realip, $onlineip);
        $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
        return $realip;
    }
}

if (!function_exists('downFile')) {
    /**
     * 下载文件
     * @param $file_path
     */
    function downFile($file_path)
    {
        //判断文件是否存在
        $file_path = iconv('utf-8', 'gb2312', $file_path); //对可能出现的中文名称进行转码
        if (!file_exists($file_path)) {
            exit('文件不存在！');
        }
        $file_name = basename($file_path); //获取文件名称
        $file_size = filesize($file_path); //获取文件大小
        $fp = fopen($file_path, 'r'); //以只读的方式打开文件
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: {$file_size}");
        header("Content-Disposition: attachment;filename={$file_name}");
        $buffer = 1024;
        $file_count = 0;
        //判断文件是否结束
        while (!feof($fp) && ($file_size - $file_count > 0)) {
            $file_data = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_data;
        }
        fclose($fp); //关闭文件
    }
}

if (!function_exists('curl_get')) {
    /**
     * 使用curl获取远程数据
     * @param string $url url连接路径
     * @return string      获取到的数据
     */
    function curl_get($url)
    {
        $ch = curl_init();
        //设置访问的url地址
        curl_setopt($ch, CURLOPT_URL, $url);
        //启用时会将头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_HEADER, false);
        //允许 cURL 函数执行的最长秒数。
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        //在HTTP请求中包含一个"User-Agent: "头的字符串。
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        //在HTTP请求头中"Referer: "的内容。
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
        //TRUE 时将会根据服务器返回 HTTP 头中的 "Location: " 重定向
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //这个是重点，加上这个便可以支持http和https下载
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}

if (!function_exists('curl_post')) {
    function curl_post($url, $data = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;

    }
}

if (!function_exists('list_to_tree')) {
    /**
     * 无限极分类树的生成
     * @param $list -数据
     * @param string $pk key名称
     * @param string $pid 父级编号
     * @param string $child
     * @param int $root
     * @return array
     */
    function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'child', $root = 0)
    {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }
}

if (!function_exists('message_tips')) {
    /**
     * json数据返回
     * @param $type *返回类型id  1默认成功
     * @param string $msg 返回提示信息
     * @param array $data 返回数据参数
     * @param string $exit_flag 判断是否终止程序执行
     */
    function message_tips($type, $msg = '', $data = [], $exit_flag = '')
    {
        $data = array(
            'success' => $type,
            'msg' => $msg,
            'data' => $data,
        );
        echo json_encode($data);
        if (empty($exit_flag)) {
            exit();
        }
    }
}

if (!function_exists('split_ch_str')) {
    /**
     * 将中文字符串转成数组
     * @param $str
     * @return array|false|string[]
     */
    function split_ch_str($str)
    {
        return preg_split('/(?!^)(?!$)/u', $str);
    }
}

if (!function_exists('readFolderFiles')) {
    /**
     * 递归读取文件数据
     * @param $path
     * @return array
     */
    function readFolderFiles($path)
    {
        $list = [];
        $resource = opendir($path);
        while ($file = readdir($resource)) {
            //排除根目录
            if ($file != ".." && $file != ".") {
                if (is_dir($path . "/" . $file)) {
                    //子文件夹，进行递归
                    $list[$file] = readFolderFiles($path . "/" . $file);
                } else {
                    //根目录下的文件
                    $list[] = $file;
                }
            }
        }
        closedir($resource);
        return $list ? $list : [];
    }
}

if (!function_exists('get_zip_originalsize')) {
    /**
     * 解压文件
     * @param $filename
     * @param $path
     * @return string[]
     */
    function get_zip_originalsize($filename, $path)
    {
        //先判断待解压的文件是否存在
        if (!file_exists($filename)) {
            return array('code' => 'error', 'msg' => '文件不存在');
        }
        $starttime = explode(' ', microtime()); //解压开始的时间
        //将文件名和路径转成windows系统默认的gb2312编码，否则将会读取不到
        $filename = iconv("utf-8", "gb2312", $filename);
        $path = iconv("utf-8", "gb2312", $path);
        //打开压缩包
        $resource = zip_open($filename);
        //遍历读取压缩包里面的一个个文件
        while ($dir_resource = zip_read($resource)) {
            //如果能打开则继续
            if (zip_entry_open($resource, $dir_resource)) {
                //获取当前项目的名称,即压缩包里面当前对应的文件名
                $file_name = $path . zip_entry_name($dir_resource);
                //以最后一个“/”分割,再用字符串截取出路径部分
                $file_path = substr($file_name, 0, strrpos($file_name, "/"));
                //如果路径不存在，则创建一个目录，true表示可以创建多级目录
                if (!is_dir($file_path)) {
                    mkdir($file_path, 0777, true);
                }
                //如果不是目录，则写入文件
                if (!is_dir($file_name)) {
                    //读取这个文件
                    $file_size = zip_entry_filesize($dir_resource);
                    $file_content = zip_entry_read($dir_resource, $file_size);
                    file_put_contents($file_name, $file_content);
                }
                //关闭当前
                zip_entry_close($dir_resource);
            }
        }
        //关闭压缩包
        zip_close($resource);
        $endtime = explode(' ', microtime()); //解压结束的时间
        $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
        $thistime = round($thistime, 3); //保留3为小数
        return array('code' => 'ok', 'msg' => '本次解压时间' . $thistime . '秒');
    }
}

if (!function_exists('uuid')) {
    function uuid()
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-'
            . substr($chars, 8, 4) . '-'
            . substr($chars, 12, 4) . '-'
            . substr($chars, 16, 4) . '-'
            . substr($chars, 20, 12);
        return $uuid;
    }
}

if (!function_exists('write_file')) {
    /**
     * 文件写入
     * @param $file_dir
     * @param $content
     * @return string[]
     */
    function write_file($file_dir, $content)
    {
        $handle = fopen($file_dir, 'ab+');
        if ($handle) {
            fwrite($handle, json_encode($content) . "\r\n");
            fclose($handle);
            return ['code' => 'ok', 'msg' => '写入成功!'];
        } else {
            return ['code' => 'error', 'msg' => '写入成功!'];
        }
    }
}

if (!function_exists('resize')) {
    /**
     * 图片分辨率调整
     * @param $src
     * @param $width
     * @param $height
     * @return mixed
     */
    function resize($src, $width, $height)
    {
        //$src 就是 $_FILES['upload_image_file']['tmp_name']
        //$width和$height是指定的分辨率
        //如果想按指定比例放缩，可以将$width和$height改为$src的指定比例
        $info = getimagesize($src);//获取图片的真实宽、高、类型
        if ($info[0] == $width && $info[1] == $height) {
            //如果分辨率一样，直接返回原图
            return $src;
        }
        switch ($info['mime']) {
            case 'image/jpeg':
                $image_wp = imagecreatetruecolor($width, $height);
                $image_src = imagecreatefromjpeg($src);
                imagecopyresampled($image_wp, $image_src, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
                imagedestroy($image_src);
                imagejpeg($image_wp, $src);
                break;
            case 'image/png':
                $image_wp = imagecreatetruecolor($width, $height);
                $image_src = imagecreatefrompng($src);
                imagecopyresampled($image_wp, $image_src, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
                imagedestroy($image_src);
                imagejpeg($image_wp, $src);
                break;
            case 'image/gif':
                $image_wp = imagecreatetruecolor($width, $height);
                $image_src = imagecreatefromgif($src);
                imagecopyresampled($image_wp, $image_src, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
                imagedestroy($image_src);
                imagejpeg($image_wp, $src);
                break;
        }
        return $src;
    }
}

if (!function_exists('base64EncodeImage')) {
    /**
     * 图片转base64编码(无请求头)
     * @param $image_file
     * @return string
     */
    function base64EncodeImage($image_file)
    {
        $base64_image = '';
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        //$base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        //$base64_image =chunk_split(base64_encode($image_data));
        $base64_image = base64_encode($image_data);
        return $base64_image;
    }
}

if (!function_exists('base64_image_content')) {
    /**
     * base64图片转为本地图片
     * @param $base64_image_content
     * @param $path
     * @return bool|string
     */
    function base64_image_content($base64_image_content, $path)
    {
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            $file_addr_name = "/" . date('Y', time()) . "/" . date('m', time()) . "/" . date('d', time()) . "/";
            $new_file = $path . $file_addr_name;
            if (!file_exists($new_file)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                superBuilt($new_file);
            }
            $file_name = uuid() . ".{$type}";
            $new_file = $new_file . $file_name;
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return $file_addr_name . $file_name;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

if (!function_exists('superBuilt')) {
    /**
     * 递归创建文件目录
     * @param $dirname
     * @return bool
     */
    function superBuilt($dirname)
    {
        return is_dir($dirname) or superBuilt(dirname($dirname)) and mkdir($dirname, 0777);
    }
}

if (!function_exists('delDirAndFile')) {
    /**
     * 递归删除目录及其文件
     * @param $path -删除的文件路径 例C:\xxx
     * @param bool $delDir -是否只删除文件
     * @param string $check -为空则保留第一层目录 是否只删除目录下所有文件或文件夹，保留当前目录 例保留xxx目录
     * @return bool
     */
    function delDirAndFile($path, $delDir = FALSE, $check = '')
    {
        if (empty($check)) {
            $check = $path;
        }
        $handle = opendir($path);
        if ($handle) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..")
                    is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir, $check) : unlink("$path/$item");
            }
            closedir($handle);
            if ($delDir)
                if ($path != $check) {
                    return rmdir($path);
                }
        } else {
            if (file_exists($path)) {
                return unlink($path);
            } else {
                return FALSE;
            }
        }
    }
}

if (!function_exists('imgturn')) {
    /**
     * 旋转图片
     * @param $src
     * @param int $direction
     */
    function imgturn($src, $direction = 1)
    {
        $ext = strtolower(pathinfo($src)['extension']);
        switch ($ext) {
            case 'gif':
                $img = imagecreatefromgif($src);
                break;
            case 'jpg':
            case 'jpeg':
                $img = imagecreatefromjpeg($src);
                break;
            case 'png':
                $img = imagecreatefrompng($src);
                break;
            default:
                die('图片格式错误!');
                break;
        }
        $width = imagesx($img);
        $height = imagesy($img);
        $img2 = imagecreatetruecolor($height, $width);
        //顺时针旋转90度
        if ($direction == 1) {
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    imagecopy($img2, $img, $height - 1 - $y, $x, $x, $y, 1, 1);
                }
            }
        } else if ($direction == 2) {
            //逆时针旋转90度
            for ($x = 0; $x < $height; $x++) {
                for ($y = 0; $y < $width; $y++) {
                    imagecopy($img2, $img, $x, $y, $width - 1 - $y, $x, 1, 1);
                }
            }
        }
        switch ($ext) {
            case 'jpg':
            case "jpeg":
                imagejpeg($img2, $src, 100);
                break;
            case "gif":
                imagegif($img2, $src, 100);
                break;

            case "png":
                imagepng($img2, $src, 100);
                break;

            default:
                die('图片格式错误!');
                break;
        }
        imagedestroy($img);
        imagedestroy($img2);
    }
}

if (!function_exists('tmp_exif_img')){
    function tmp_exif_img($tmp_dir)
    {
        //>>文件基本验证
        if (!file_exists($tmp_dir)) {
            return ['code' => 'error', 'mag' => '文件不存在'];
        }
        try {
            $exif_arr = exif_read_data($tmp_dir);
            //>>判断图片类型
            if (!isset($exif_arr['Orientation'])) {
                return ['code' => 'success', 'mag' => '图片无旋转信息'];
            }
            switch ($exif_arr['Orientation']) {
                case 1:
                    return ['code' => 'success', 'msg' => '正常图片'];
                    break;
                case 2:
                    return ['code' => 'success', 'msg' => '水平翻转图片，暂未处理'];
                    break;
                case 3:
                    $image = imagerotate(imagecreatefromstring(file_get_contents($tmp_dir)), 180, 0);
                    imagepng($image, $tmp_dir);
                    return ['code' => 'success', 'msg' => '180°，已处理'];
                    break;
                case 4:
                    return ['code' => 'success', 'msg' => '垂直翻转，暂未处理'];
                    break;
                case 5:
                    return ['code' => 'success', 'msg' => '顺时针90°+水平翻转，暂未处理'];
                    break;
                case 6:
                    $image = imagerotate(imagecreatefromstring(file_get_contents($tmp_dir)), -90, 0);
                    imagepng($image, $tmp_dir);
                    return ['code' => 'success', 'msg' => '顺时针90°，已处理'];
                    break;
                case 7:
                    return ['code' => 'success', 'msg' => '顺时针90°+垂直翻转，暂未处理'];
                    break;
                case 8:
                    $image = imagerotate(imagecreatefromstring(file_get_contents($tmp_dir)), 90, 0);
                    imagepng($image, $tmp_dir);
                    return ['code' => 'success', 'msg' => '逆时针90°，已处理'];
                    break;
                default:
                    return ['code' => 'error', 'mag' => '数据异常'];
            }
        } catch (\Exception $e) {
            return ['code' => 'success', 'mag' => $e->getMessage()];
        }

    }
}
