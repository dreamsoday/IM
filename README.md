# IM
# 1，在composer.json中引用"lgy/console": “*”
# 2，composer update
# 3，在app.php中的providers进行配置 Lgy\IM\IMServiceProvider::class
# 4，php artisan vendor:publish
# 5，在config文件夹中寻找im.php，对应参数
# 6，引用方式：IMFacade::createUser(2,2);（可安装laravel_ide）
