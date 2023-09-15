<?php

namespace Gourabsutradhar\Secureimage;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
//use Illuminate\Support\Str;

use Illuminate\Support\Facades\Hash;

//use Illuminate\Session\Store as Session;

class SecureImage
{
    /**
     * the image
     *
     *@var resource image
     */
    protected $image;

    /**
     *height of image
     *
     *@var int height
     */
    protected $height;

    /**
     *width of image
     *
     *@var int width
     */
    protected $width;

    /**
     *length of code
     *
     *@var int length
     */
    protected $length;

    /**
     *angle of code
     *
     *@var int angle
     */
    protected $angle;

    /**
     *text size
     *
     *@var int size
     */
    protected $size;

    /**
     *text spacing
     *
     *@var int spacing
     */
    protected $spacing;

    /**
     *characters for code
     *
     *@var string characters
     */
    protected $characters;

    /**
     *the font file
     *
     *@var font
     */
    protected $font;

    /**
     *total line count
     *
     *@var lines
     */
    protected $lines;

    /**
     *background color of image
     *
     *@var bgcolor
     */
    protected $bgcolor;

    /**
     *captcha code of image
     *
     *@var code
     */
    protected $code;

    /**
     *max expire time
     *
     *@var expire
     */
    protected $expire;

    public function __construct()
    {
        //load config
        $this->width = config('secureimage.width', 120);
        $this->height = config('secureimage.height', 50);
        $this->length = config('secureimage.length', 6);
        $this->characters = config('secureimage.characters', '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $this->angle = config('secureimage.angle', 15);
        $this->size = (int) (\round($this->width / $this->length) - \rand(0, 3) - 1);
        $this->spacing = config('secureimage.spacing', 4);
        $this->lines = config('secureimage.lines', 6);
        $this->expire = config('secureimage.expire', 60);
        //
        $this->font = __DIR__.'/../assets/fonts/KumbhSans-Bold.ttf';
    }

    /**
     *create image and return hashed string
     *
     *@param bool api
     */
    public function create(bool $api = false)
    {
        $this->image = \imagecreate($this->width, $this->height);
        $this->bgcolor = $this->hexColorAllocate($this->image, config('secureimage.background_color', '#ffffff'));
        \imagefill($this->image, 0, 0, $this->bgcolor);

        $this->code = $this->generateCode($this->characters, $this->length);
        $fontColors = $this->generateFontColors($this->image, $this->length);
        //echo $this->code;
        //write codes/texts

        $box = \imagettfbbox($this->size, 0, $this->font, $this->code);
        //dd($box);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = (int) \round(($this->width - $textWidth) / 2);
        $y = (int) \round(($this->height - $textHeight) / 2) + $this->size;
        for ($i = 0; $i < $this->length; $i++) {
            $text = $this->code[$i];

            //$x=\round(($this->width/$this->length)+($i*$this->size)+$this->spacing);
            //$y=\round(($this->height/2)+\rand(-($this->height/6),$this->height/6));
            \imagettftext(
                $this->image,
                $this->size,
                \round(\rand(0, 1) === 1 ? \rand(5, $this->angle) : \rand((360 - $this->angle), 355)),
                $x + (\round(($textWidth / $this->length)) * $i) + \rand(-($this->spacing / 2), $this->spacing),
                $y + \round(\rand(-($textHeight / 4), $textHeight / 4)),
                $fontColors[$i],
                $this->font,
                $text);
        }

        //draw lines
        $this->drawLines($this->image, $this->lines);
        //apply filters
        \imagefilter($this->image, IMG_FILTER_NEGATE);
        $this->image = $this->filter($this->image);
        $this->image = $this->distort($this->image);

        \ob_start();
        \imagepng($this->image);
        $base64 = 'data:image/png;base64,'.\base64_encode(\ob_get_clean());
        \imagedestroy($this->image);

        $bag['code'] = Hash::make($this->code);
        //$bag['expire'] = $this->expire + \time();
        //$bag['issue_at'] = \time();
        $key = Crypt::encryptString(\json_encode($bag));

        $randStr = \hash('sha256', \time());
        $cacheKey = 'secureimage_'.$randStr;

        Cache::put($cacheKey, $key, $this->expire);

        if ($api) {
            return ['$key' => $randStr,
                'img' => $base64];
        } else {
            session(['secureimage' => $randStr]);

            return $base64;
        }

        //return '<img src="data:image/jpeg;base64,'.$base64.'"><p>'.$this->code.'</p>';
    }

    protected function uniqueRand($min, $max, ...$duplicates)
    {

        do {
            $num = \rand($min, $max);
        } while (\array_search($num, $duplicates) !== false);

        return $num;
    }

    protected function filter($source)
    {
        //make image copys
        //negative
        $negative = \imagecreatetruecolor($this->width, $this->height);
        \imagecopy($negative, $source, 0, 0, 0, 0, $this->width, $this->height);
        \imagefilter($negative, IMG_FILTER_NEGATE);

        //blackand white
        $blackWhite = \imagecreatetruecolor($this->width, $this->height);
        \imagecopy($blackWhite, $source, 0, 0, 0, 0, $this->width, $this->height);
        \imagefilter($blackWhite, IMG_FILTER_CONTRAST, \rand(-30, 10));
        \imagefilter($blackWhite, IMG_FILTER_EDGEDETECT);

        //
        $modified = \imagecreatetruecolor($this->width, $this->height);

        //slice the negative image
        $horizontalSliceCount = \rand(3, 6);
        $verticalSliceCount = \rand(1, 2);
        $totalSliceCount = $verticalSliceCount * $horizontalSliceCount;
        $normalSliceAt = \rand(1, $totalSliceCount);
        $blackWhiteSliceAt = $this->uniqueRand(1, $totalSliceCount, $normalSliceAt);
        $negativeSliceAt = $this->uniqueRand(1, $totalSliceCount, $normalSliceAt, $blackWhiteSliceAt);
        //echo $totalSliceCount.'-'.$normalSliceAt.'-'.$blackWhiteSliceAt.'-'.$negativeSliceAt;
        $sliceWidth = (int) \round($this->width / $horizontalSliceCount);
        $sliceHeight = (int) \round($this->height / $verticalSliceCount);
        for ($h = 0; $h <= $verticalSliceCount; $h++) {
            for ($w = 0; $w <= $horizontalSliceCount; $w++) {
                //$slice=\imagecreate($sliceWidth,$sliceHeight);
                $x = $w * $sliceWidth;
                $y = $h * $sliceHeight;

                if (($h + 1) * ($w + 1) === $normalSliceAt) {
                    \imagecopymerge($modified,
                        $source,
                        $x,
                        $y,
                        $x,
                        $y,
                        $sliceWidth,
                        $sliceHeight,
                        100);
                } elseif (($h + 1) * ($w + 1) === $negativeSliceAt) {
                    \imagecopymerge($modified,
                        $negative,
                        $x,
                        $y,
                        $x,
                        $y,
                        $sliceWidth,
                        $sliceHeight,
                        100);
                } elseif (($h + 1) * ($w + 1) === $blackWhiteSliceAt) {
                    \imagecopymerge($modified,
                        $blackWhite,
                        $x,
                        $y,
                        $x,
                        $y,
                        $sliceWidth,
                        $sliceHeight,
                        100);
                } else {
                    switch (\rand(0, 2)) {
                        case 0:
                            \imagecopymerge($modified,
                                $negative,
                                $x,
                                $y,
                                $x,
                                $y,
                                $sliceWidth,
                                $sliceHeight,
                                100);
                            break;

                        case 1:
                            \imagecopymerge($modified,
                                $blackWhite,
                                $x,
                                $y,
                                $x,
                                $y,
                                $sliceWidth,
                                $sliceHeight,
                                100);
                            break;
                        case 2:
                            \imagecopymerge($modified,
                                $source,
                                $x,
                                $y,
                                $x,
                                $y,
                                $sliceWidth,
                                $sliceHeight,
                                100);
                            break;

                    }
                }
            }
        }
        \imagedestroy($negative);
        \imagedestroy($blackWhite);
        \imagedestroy($source);

        return $modified;
    }

    protected function distort($image)
    {
        $copy = \imagecreatetruecolor($this->width, $this->height);

        $maxAmplitudeX = config('max_amplitude_x', 3);
        $maxAmplitudeY = config('max_amplitude_y', 2);
        $frequencyX = \rand(1, 2); // Randomize the frequency for a varied effect
        $frequencyY = \rand(1, 2); // Randomize the frequency for a varied effect

        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {

                $distortionX = (int) ($maxAmplitudeX * \sin(2 * M_PI * $frequencyX * $y / $this->height));
                $distortionY = (int) ($maxAmplitudeY * \sin(2 * M_PI * $frequencyY * $x / $this->width));

                $srcX = $x + $distortionX;
                $srcY = $y + $distortionY;

                if ($srcX >= 0 && $srcX < $this->width && $srcY >= 0 && $srcY < $this->height) {
                    $color = \imagecolorat($image, $srcX, $srcY);
                    //var_dump($color);
                    \imagesetpixel($copy, $x, $y, $color);
                }
            }
        }
        \imagedestroy($image);

        return $copy;
    }

    /**
     *draw random lines on image
     *
     *@var resource image
     *@var int lines
     */
    protected function drawLines($image, $lines)
    {
        //colorful lines
        for ($i = 0; $i <= $lines; $i++) {
            if (\rand(0, 1)) {
                //vertical line
                \imageline($this->image, 0, \rand(0, $this->height), $this->width, \rand(0, $this->height), $this->randomColor($this->image));
            } else {
                //horizontal line
                \imageline($this->image, \rand(0, $this->width), 0, \rand(0, $this->width), $this->height, $this->randomColor($this->image));
            }
        }
        //invisible line
        for ($i = 0; $i <= ((int) $lines / 3); $i++) {
            if (\rand(0, 1)) {
                //vertical line
                \imageline($this->image, 0, \rand(0, $this->height), $this->width, \rand(0, $this->height), $this->bgcolor);
            } else {
                //horizontal line
                \imageline($this->image, \rand(0, $this->width), 0, \rand(0, $this->width), $this->height, $this->bgcolor);
            }
        }

    }

    /**
     *generate random color for font
     *
     *@param resource image
     *@param int count
     */
    protected function generateFontColors($image, int $count): array
    {
        $colors = [];
        for ($i = 0; $i <= $count; $i++) {
            $colors[] = $this->randomColor($image);
        }

        return $colors;
    }

    /**
     *generate a code for captcha
     *
     *@param string characters
     *@param int count
     */
    protected function generateCode(string $characters, int $count): string
    {
        $code = '';
        for ($i = 1; $i <= $count; $i++) {
            $code .= $characters[\rand(0, (\strlen($characters) - 1))];
        }

        return $code;
    }

    protected function randomColor($image)
    {
        return \imagecolorallocate($image, \rand(0, 200), \rand(0, 200), \rand(0, 200));
    }

    /**
     *hex string to gd color
     *
     *@param resource image
     *@param strimg hex
     */
    protected function hexColorAllocate($im, $hex)
    {
        $hex = \ltrim($hex, '#');
        $r = \hexdec(\substr($hex, 0, 2));
        $g = \hexdec(\substr($hex, 2, 2));
        $b = \hexdec(\substr($hex, 4, 2));

        return \imagecolorallocate($im, $r, $g, $b);
    }

    public function verify(string $code, string $key)
    {
        $cacheKey = 'secureimage_'.$key;
        $hash = Cache::get($cacheKey);
        Cache::forget($cacheKey);
        if ($hash == null || ! isset($hash)) {
            return false;
        }
        $bag = (array) \json_decode(Crypt::decryptString($hash));
        if (! isset($bag) || $bag == null) {
            return false;
        }
        /*if (! \array_key_exist('expire', $bag) || \time() < $bag['expire']) {
            return false;
        }*/
        if (! \array_key_exists('code', $bag) || $bag['code'] == null) {
            return false;
        }
        $check = Hash::check($code, $bag['code']);

        return $check;
    }
}
