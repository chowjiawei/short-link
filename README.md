# short-link
Tool for changing long links into short links.

长链接生成短链接  实现 短链跳转

#### 安装
- 发布配置文件
`php artisan vendor:publish --provider="Chowjiawei\ShortLink\Providers\ShortLinkServiceProvider"`
- 执行迁移命令 `php artisan migrate`

#### 使用
- 引入服务(以下功能均需引入)
```php
$shortLinkService=new \Chowjiawei\ShortLink\Services\ShortLinkService();
```
- 系统自生成 新的短链接 支持：`mix`混合 `number`纯数字 `minLetter`纯小写字母 `maxLetter`纯大写字母
```php
$shortLinkService->short('apple122','maxLetter');
```
- 自定义新链接（短链接）
```php
$shortLinkService->customShort('apple1111','apple123');
```

- 删除关系链接（使用旧链接【长连接】进行删除）
```php
$shortLinkService->deleteOldUrl('apple122');
```

- 删除关系链接（使用新链接【短连接】进行删除）
```php
$shortLinkService->deleteNewUrl('apple122');
```
- 使用跳转 建议放入Laravel(app\Http\Controller.php 的fallback方法内) ，路由（`Route::fallback('Controller@fallback');`）
```php
$shortLinkService=new \Chowjiawei\ShortLink\Services\ShortLinkService();
return  $shortLinkService->redirect();  
```