<?php

App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

class Up {

    protected static $_uploadDir = null;

    /**
     * imageTypes
     *
     * @var array
     * @access public
     */
    public static $imageTypes = array(
        'image/jpg',
        'image/png',
        'image/jpeg',
        'image/gif'
    );

    /**
     * Return upload dir path
     */
    public static function uploadDir() {
        if (!self::$_uploadDir = Configure::read('Upload.dir')) {
            self::$_uploadDir = APP . 'Uploads' . DS;
        }
        return self::$_uploadDir;
    }

    /**
     * imagefunc
     *
     * @param string $type image type
     * @return array function names to generate and create image
     */
    public static function imagefunc($type) {
        switch ($type) {
            case 'image/png':
                return array('imagecreatefrompng', 'imagepng');
            case 'image/gif':
                return array('imagecreatefromgif', 'imagegif');
            default:
                return array('imagecreatefromjpeg', 'imagejpeg');
        }
    }

    /**
     * isImageType
     *
     * @param  string $type mime type of the image
     * @return boolean True if the type is an image type
     */
    public static function isImageType($type) {
        return in_array($type, self::$imageTypes);
    }

    /**
     * Generate filename in path
     * @return string Relative path to self::$uploadDir
     */
    public static function path() {
        $dir = self::folder();
        $file = tempnam(self::uploadDir() . $dir, 'up_');
        return $dir . DS . basename($file);
    }

    /**
     * Generate folder for upload based on date
     *
     * @return string
     */
    public static function folder() {
        $dir = date('Y' . DS . 'm' . DS . 'd');
        $folder = new Folder(self::uploadDir() . $dir, true, 0777);
        return $dir;
    }


}
