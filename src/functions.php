<?php

if (! function_exists('secureimage_base64')) {
    function secureimage_base64()
    {
        return (new Gourabsutradhar\SecureImage\SecureImage)->create();
    }
}
