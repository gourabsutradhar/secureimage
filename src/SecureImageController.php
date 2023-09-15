<?php

namespace Gourabsutradhar\SecureImage;

use Illuminate\Routing\Controller;

/**
 * Class CaptchaController
 */
class SecureImageController extends Controller
{
    /*protected $secureImage;

    public function __construct()
    {
        $this->secureImage = new SecureImage();
    }*/

    public function getSecureImageApi()
    {
        return (new SecureImage)->create(true);
    }

    /*public function getSecureImage()
    {
        //return '<img src="'.$this->secureImage->create().'"/>';
        return (new SecureImage)->create();
    }*/
}
