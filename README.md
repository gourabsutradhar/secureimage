A captcha for laravel framework.

# Setup
Require this package with composer:
```
composer require gourabsutradhar/secureimage
```
To use your own settings, publish config using the command
```
php artisan vendor:publish
```
Add `Gourabsutradhar\SecureImage\SecireImageServiceProvoder` to `config\app.php` file.

# Web route 
### Show a captcha
use the `secureimage_base64()` to show an image.it returns base 64 string of image.
```php
<form method="post" action="{{route('verify.web')}}">
<image src="{{secureimage_base64()}}">
@csrf
<br>
<input type="text" name="code">
<br>
<input type="submit" value="verify">
</form>
```
### Verify it
here is example code to verify a image in web route
```php
 $validator=Validator::make(request()->all(),['code'=>'secureimage_web']);
```

# API
### Show a captcha
Api route [path : api/secureimage, name : secureimage.api] returns json data with 2 key named 'image' and 'key'. the 'image' key is base64 string representation of the image and 'key' is key of image. Save the 'key' value to verify it.
send user typed code and the 'key' value to your server.
here is example verification code.
```php
 $validator=Validator::make(request()->all(),['code'=>'secureimage_api:'.$request->key]);
```
