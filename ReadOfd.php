<?php


namespace app\index\api;


class ReadOfd
{
    /**
     * 需要解压的ofd发票资源的文件路径
     * @var
     */
    private $file_path;
    /**
     * 解压后的ofd发票文件存在路径
     * @var
     */
    private $file_unzip_path;

    public function __construct($file_path,$file_unzip_path)
    {
        $this->file_path = $file_path;
        $this->file_unzip_path = $file_unzip_path;
    }

    /**
     * 获取ofd发票的基本内容
     * @return array
     */
    public function get_ofd_message(){
        //>>验证文件是否为OFD格式
        if(is_string($this->file_path) == false || is_string($this->file_unzip_path)== false){
            return ['code'=>2001,'error_msg'=>'传入参数类型异常,参数需为字符串类型'];
        }
        $check_arr = explode('.',$this->file_path);
        if(end($check_arr)!='ofd' && end($check_arr)!='OFD'){
            return ['code'=>2001,'error_msg'=>'此接口仅支持获取OFD文件类型的数据'];
        }
        //>>解压ofd文件到指定目录
       $unzip_result = $this->get_zip_original_file();
       if($unzip_result['code'] != 200) return $unzip_result;
       //>>准备读取解压的xml文件内容
       $xml_path = $this->file_unzip_path.'OFD.xml';
       if(!file_exists($xml_path)) return ['code'=>2001,'error_msg'=>'未检测到解压路径下存在相关OFD.xml文件!'];
        $xml_object = new \DOMDocument();
        $xml_object->load($xml_path);
        $xml_content = $xml_object->getElementsByTagName('CustomData');
        if($xml_content->length == 0) return ['code'=>2001,'error_msg'=>'读取xml文件异常!'];
        $ofd_arr = [];
        foreach ($xml_content as $key=>$val){
            //>判断属性值是否只有一个
           if($val->attributes->length != 1) return ['code'=>2001,'error_msg'=>'读取xml文件异常!'];
           $ofd_arr[$val->attributes->item(0)->nodeValue] =  $val->textContent;
        }
        //>>读取发票名称
        $xml_content_path = $this->file_unzip_path.'Doc_0/Pages/Page_0/Content.xml';
        if(!file_exists($xml_content_path)) return ['code'=>2001,'error_msg'=>'未检测到解压路径下存在相关Conent.xml文件!'];
        $xml_object->load($xml_content_path);
        unset($xml_content);
        $xml_content = $xml_object->getElementsByTagName('TextCode');
        if($xml_content->length == 0) return ['code'=>2001,'error_msg'=>'读取xml文件异常!'];
        $xml_name = $xml_content->item(0)->textContent;
        //检测获得的数据是否包含相关关键词
        if(strpos($xml_name,'增值税')==false || strpos($xml_name,'发票')==false) return ['code'=>2001,'error_msg'=>'检测到获取的发票名称未存在指定关键词(增值税、发票),疑似OFD文件异常!'];
        $ofd_arr['发票名称'] = $xml_name;
        //>>清空解压后的文件
        $this->clear_dir($this->file_unzip_path);
        // ofd_arr 需要的xml文件内容  unzip_time 解压时间   break_file 跳过解压的文件名
        return ['code'=>200,'ofd_arr'=>$ofd_arr,'unzip_time'=>$unzip_result['unzip_time'],'break_file'=>$unzip_result['break_file']];
    }

    /**
     * 解压指定ofd发票文件到指定目录
     * @return array
     */
    private function get_zip_original_file(){
        //先判断待解压的文件是否存在
        if(!file_exists($this->file_path)) return ['code'=>2001,'error_msg'=>'文件路径异常!'];
        //解压开始的时间
        $unzip_start_time = explode( ' ',microtime());
        //将文件名和路径转成windows系统默认的gb2312编码，否则将会读取不到
        $filename = iconv("utf-8","gb2312",$this->file_path);
        $path = iconv("utf-8","gb2312",$this->file_unzip_path);
        //打开压缩包
        $resource = zip_open($this->file_path);
        if($resource==false) return ['code'=>2001,'error_msg'=>'打开文件资源异常!'];
        $break_file=[];
        //遍历读取压缩包里面的一个个文件
        while ($dir_resource = zip_read($resource)) {
            //如果能打开则继续
            if (zip_entry_open($resource,$dir_resource)) {
                //获取当前项目的名称,即压缩包里面当前对应的文件名
                $file_name = $path.zip_entry_name($dir_resource);
                //以最后一个“/”分割,再用字符串截取出路径部分
                $file_path = substr($file_name,0,strrpos($file_name, "/"));
                //如果路径不存在，则创建一个目录，true表示可以创建多级目录
                if(!is_dir($file_path)){
                    mkdir($file_path,0777,true);
                }
                //如果不是目录，则写入文件
                if(!is_dir($file_name)){
                    //读取这个文件
                    $file_size = zip_entry_filesize($dir_resource);
                    //最大读取6M，如果文件过大，跳过解压，继续下一个
                    if($file_size<(1024*1024*30)){
                        $file_content = zip_entry_read($dir_resource,$file_size);
                        file_put_contents($file_name,$file_content);
                    }else{
                        $break_file[] = iconv("gb2312","utf-8",$file_name);
                    }
                }
                //关闭当前
                zip_entry_close($dir_resource);
            }
        }
        //关闭压缩包
        zip_close($resource);
        $unzip_end_time = explode(' ',microtime()); //解压结束的时间
        $unzip_time = $unzip_end_time[0]+$unzip_end_time[1]-($unzip_start_time[0]+$unzip_start_time[1]);
        $unzip_time = round($unzip_time,3); //保留3为小数
        return ['code'=>200,'msg'=>'解压成功','unzip_time'=>$unzip_time,'break_file'=>$break_file];
    }

    /**
     * 读取文件内容之后清空解压后的相关文件
     * @param $path
     */
    private function clear_dir($path){
        if (is_dir($path)) {
            //扫描一个目录内的所有目录和文件并返回数组
            $dirs = scandir($path);
            foreach ($dirs as $dir) {
                //排除目录中的当前目录(.)和上一级目录(..)
                if ($dir != '.' && $dir != '..') {
                    //如果是目录则递归子目录，继续操作
                    $sonDir = $path.'/'.$dir;
                    if (is_dir($sonDir)) {
                        //递归删除
                        $this->clear_dir($sonDir);
                        //目录内的子目录和文件删除后删除空目录
                        @rmdir($sonDir);
                    } else {
                        //如果是文件直接删除
                        @unlink($sonDir);
                    }
                }
            }
            //删除目录本身
            //@rmdir($path);
        }
    }
}