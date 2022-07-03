var bmapcfg = {
    'imgext': '.png',   //瓦片图的后缀  根据需要修改，一般是 .png .jpg
    'tiles_dir': '/baiduOffline/wuyelan-maptile'       //普通瓦片图的地址，为空默认在tiles/ 目录
};
var scripts = document.getElementsByTagName("script");
var JS__FILE__ = scripts[scripts.length - 1].getAttribute("src");  //获得当前js文件路径

if (JS__FILE__ == null) {
    JS__FILE__ = '/baiduOffline/js/wuyelan_mp_load.js';
}
bmapcfg.home = JS__FILE__.substr(0, JS__FILE__.lastIndexOf("/js") + 1); //地图API主目录

