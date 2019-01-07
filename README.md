#laravel 还信使用插件
本插件对于php版本未设置要求
本地测试环境php7.1，laravel5.5

##使用步骤：
*1.在composer.json中引用"lgy/console": “*”
*2.composer update
*3.在app.php中的providers进行配置 Lgy\IM\IMServiceProvider::class
*4.php artisan vendor:publish（使用该命令后在config中生成配置文件im.php，相应配置在这里修改）
*5.在config文件夹中寻找im.php，修改对应参数
*6.引用方式：IMFacade::createUser(2,2);（可安装laravel_ide）
