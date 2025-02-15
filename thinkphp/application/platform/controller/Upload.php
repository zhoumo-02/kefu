<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/2/13
 * Time: 16:35
 */

namespace app\platform\controller;

class Upload
{

    public function image()
    {
        $file = request()->file("file");
        if ($file) {
            $newpath = ROOT_PATH . "/public/upload/images/";
            $info = $file->validate(['size'=>5*1024*1024,'ext' => 'jpg,png,gif,jpeg'])->move($newpath, time());

            if ($info) {
                $imgname = $info->getFilename();
                $basename = request()->root();
                if (pathinfo($basename, PATHINFO_EXTENSION) == 'php') {
                    $basename = dirname($basename);
                }
                $imgpath = $basename."/upload/images/" . $imgname;

                $data =['code'=>0,'msg'=>'','data'=>[ 'url' => $imgpath,]];
            }else{
                // 上传失败获取错误信息
                $data =['code'=>1,'msg'=>$file->getError()];
            }
            return json($data);
        }
    }

    public function file()
    {
        $file = request()->file("file");
        if ($file) {
            $newpath = ROOT_PATH . "/public/";
            $info = $file->validate(['size'=>5*1024*1024,'ext' => 'txt'])->move($newpath, '');

            if ($info) {
                $filename = $info->getFilename();
                $basename = request()->root();
                if (pathinfo($basename, PATHINFO_EXTENSION) == 'php') {
                    $basename = dirname($basename);
                }
                $filename = $basename. $filename;

                $data =['code'=>0,'msg'=>'','data'=>[ 'url' => $filename,]];
            }else{
                // 上传失败获取错误信息
                $data =['code'=>1,'msg'=>$file->getError()];
            }
            return json($data);
        }
    }

}