<?php

namespace Jericho;

use PHPMailer\PHPMailer\PHPMailer;

class EmailHelper
{
    private static $_debug = false;

    public static function debug($debug = false)
    {
        self::$_debug = $debug;
    }

    public static function send($title, $content, $to)
    {
        $mail = new PHPMailer;

        $mail->SMTPDebug = self::$_debug;
        //使用smtp鉴权方式发送邮件
        $mail->isSMTP();
        //smtp需要鉴权 这个必须是true
        $mail->SMTPAuth = env('EMAIL_SMTPAUTH');
        //链接qq域名邮箱的服务器地址
        $mail->Host = env('EMAIL_HOST');
        //设置使用ssl加密方式登录鉴权
        $mail->SMTPSecure = env('EMAIL_SECRET');
        //设置ssl连接smtp服务器的远程服务器端口号，以前的默认是25，但是现在新的好像已经不可用了 可选465或587
        $mail->Port = env('EMAIL_PORT');
        //设置smtp的helo消息头 这个可有可无 内容任意
//        $mail->Helo = ''; //Hello smtp.qq.com Server
        //设置发件人的主机域 可有可无 默认为localhost 内容任意，建议使用你的域名
        $mail->Hostname = env('EMAIL_HOSTNAME');
        //设置发送的邮件的编码 可选GB2312 我喜欢utf-8 据说utf8在某些客户端收信下会乱码
        $mail->CharSet = env('EMAIL_CHARSET');
        //设置发件人姓名（昵称） 任意内容，显示在收件人邮件的发件人邮箱地址前的发件人姓名
        $mail->FromName = env('EMAIL_FROMNAME');
        //smtp登录的账号 这里填入字符串格式的qq号即可
        $mail->Username = env('EMAIL_USERNAME');
        //smtp登录的密码 使用生成的授权码 你的最新的授权码
        $mail->Password = env('EMAIL_PASSWORD');
        //设置发件人邮箱地址 这里填入上述提到的“发件人邮箱”
        $mail->From = env('EMAIL_FROM');
        //邮件正文是否为html编码 注意此处是一个方法 不再是属性 true或false
        $mail->isHTML(true);
        //设置收件人邮箱地址 该方法有两个参数 第一个参数为收件人邮箱地址 第二参数为给该地址设置的昵称 不同的邮箱系统会自动进行处理变动 这里第二个参数的意义不大
        $mail->AddAddress($to, "尊敬的客户");
        //添加多个收件人 则多次调用方法即可
//        $mail->addAddress('');
        //添加该邮件的主题
        $mail->Subject = $title;
        //添加邮件正文 上方将isHTML设置成了true，则可以是完整的html字符串 如：使用file_get_contents函数读取本地的html文件
        $mail->Body = $content;

        //为该邮件添加附件 该方法也有两个参数 第一个参数为附件存放的目录（相对目录、或绝对目录均可） 第二参数为在邮件附件中该附件的名称
        // $mail->addAttachment('./d.jpg','mm.jpg');
        //同样该方法可以多次调用 上传多个附件
//        $mail->addAttachment('/index.php','index.php');

        $mail->send();

        if ($mail->isError()) return $mail->ErrorInfo;
        return true;
    }

}
