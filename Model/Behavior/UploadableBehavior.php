<?php

App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

class UploadableBehavior extends ModelBehavior {

    /**
     * _imageTypes
     *
     * @var array
     * @access protected
     */
    protected $_imageTypes = array(
        'image/jpg',
        'image/png',
        'image/jpeg',
        'image/gif'
    );

    /**
     * _uploadDir
     *
     * @var mixed
     * @access protected
     */
    protected $_uploadDir = null;

    /**
     * _toDelete
     *
     * @var array
     * @access protected
     */
    protected $_toDelete = array();

    /**
     * _Upload
     *
     * @var mixed
     * @access protected
     */
    protected $_Upload = null;

    /**
     * thumbs
     *
     * @var array
     * @access public
     */
    public $thumbs = array();

    /**
    * __construct
    *
    * @access public
    * @return void
    */
    public function __construct() {
        if (!$this->_uploadDir = Configure::read('Upload.dir')) {
            $this->_uploadDir = APP . 'Uploads' . DS;
        }

        parent::__construct();
    }

    /**
    * setup
    *
    * @param mixed $model
    * @param array $config
    * @access public
    * @return void
    */
    public function setup(Model $model, $config = array()) {
        $defaults = array(
            'keepOriginal' => true
        );
        if (empty($config)) {
            $config = array('upload_id' => array());
        }
        $_settings = array();

        foreach ($config as $field => $settings) {
            if (is_numeric($field)) {
                $field = $settings;
                $settings = array();
            }
            $_settings = array_merge($defaults, $settings);

            $this->thumbs[$model->alias][$field] = array_diff_key($settings, $defaults);
            $this->settings[$model->alias][$field] = array_intersect_key($_settings, $defaults);
        }

        $this->_toDelete[$model->alias] = array();
    }

    /**
    * beforeSave
    *
    * @param mixed $model
    * @access public
    * @return void
    */
    public function beforeSave(Model $model) {
        foreach ($this->settings[$model->alias] as $field => $settings) {
            if (!isset($model->data[$model->alias][$field])
                || !is_array($model->data[$model->alias][$field])) {
                continue;
            }

            $data = $model->data[$model->alias][$field];
            if ($data['error'] == 0) {
                if ($model->exists()) {
                    $recursive = $model->recursive;
                    $model->recursive = -1;
                    $this->_toDelete[$model->alias][] = $model->field($field);
                    $model->recursive = $recursive;
                }
                $value = $this->_upload(
                    $data,
                    $this->thumbs[$model->alias][$field],
                    $this->settings[$model->alias][$field]
                );
            } elseif ($model->exists()) {
                $value = $model->field($field);
            } else {
                $value = null;
            }
            $model->data[$model->alias][$field] = $value;
        }
        return true;
    }

    /**
    * beforeDelete
    *
    * @param mixed $model
    * @access public
    * @return void
    */
    public function beforeDelete(Model $model, $cascade = true) {
        $fields = array();
        foreach ($this->settings[$model->alias] as $field => $thumb) {
            if (is_numeric($field)) {
                $field = $thumb;
            }
            $fields[] = $field;
        }
        $recursive = $model->recursive;
        $model->recursive = -1;
        $ids = $model->read($fields);
        $model->recursive = $recursive;
        $this->_toDelete[$model->alias] = array_values($ids[$model->alias]);

        return true;
    }

    /**
    * afterSave
    *
    * @param mixed $model
    * @param mixed $created
    * @access public
    * @return void
    */
    public function afterSave(Model $model, $created) {
        if (!$created) {
            $this->_delete($model);
        }
    }

    /**
    * afterDelete
    *
    * @param mixed $model
    * @access public
    * @return void
    */
    public function afterDelete(Model $model) {
        $this->_delete($model);
    }

    /**
     * _delete
     *
     * @access protected
     * @return void
     */
    protected function _delete(Model $model) {
        if (!empty($this->_toDelete[$model->alias])) {
            $Upload = $this->_uploadInstance();
            foreach ($this->_toDelete[$model->alias] as $id) {
                if (!empty($id)) {
                    $Upload->create();
                    $Upload->delete($id);
                }
            }
            $this->_toDelete[$model->alias] = array();
        }
    }

    /**
     * _uploadInstance
     *
     * @access protected
     * @return void
     */
    protected function _uploadInstance() {
        if (!$this->_Upload) {
            $this->_Upload = ClassRegistry::init('Upload.Upload', 'Model');
        }
        return $this->_Upload;
    }

    /**
     * _upload
     *
     * @param mixed $data
     * @param mixed $thumbs
     * @param mixed $settings
     * @access protected
     * @return void
     */
    protected function _upload($data, $thumbs, $settings) {
        $name = $type = $size = $image = $path = null;
        $paths = array();
        extract($settings, EXTR_OVERWRITE);
        extract($data, EXTR_OVERWRITE);
        $image = $this->_isImageType($type);

        if (!$image || $keepOriginal) {
            if (!$path = $this->_moveFile($data['tmp_name'])) {
                return null;
            }
        }

        if ($image && !empty($thumbs)) {
            $func = $this->_imagefunc($type);
            if ($keepOriginal && $image && $path) {
                $img = $func[0]($this->_uploadDir . $path['path']);
            } else {
                $img = $func[0]($tmp_name);
            }

            foreach ($thumbs as $thumb) {
                if ($t = $this->_thumb($img, $func, $thumb)) {
                    $paths[] = $t;
                }
            }
        }

        if ($path && (!$image || $keepOriginal)) {
            $paths[] = $path;
        }

        if (isset($img)) {
            imagedestroy($img);
        }

        $upload = array(
            'Upload' => compact('name', 'type', 'size', 'image'),
            'UploadPath' => $paths
        );

        $Upload = $this->_uploadInstance();
        $Upload->create();
        if ($Upload->saveAll($upload)) {
            return $Upload->id;
        }
        return null;
    }

    /**
     * _isImageType
     *
     * @param mixed $type
     * @access protected
     * @return void
     */
    protected function _isImageType($type) {
        return in_array($type, $this->_imageTypes);
    }

    /**
     * _moveFile
     *
     * @param mixed $from
     * @access protected
     * @return void
     */
    protected function _moveFile($from) {
        $path = $this->_path();

        $to = $this->_uploadDir . $path;
        if (move_uploaded_file($from, $to)) {
            chmod($to, 0777);
            return array(
                'name' => null,
                'path' => $path
            );
        }

        return false;
    }

    /**
     * _path
     *
     * @access protected
     * @return void
     */
    protected function _path() {
        $dir = $this->_folder();
        $file = tempnam($this->_uploadDir . $dir, 'up_');
        return $dir . DS . basename($file);
    }

    protected function _folder() {
        $dir = date('Y' . DS . 'm' . DS . 'd');
        $folder = new Folder($this->_uploadDir . $dir, true, 0777);
        return $dir;
    }

    /**
     * _thumb
     *
     * @param mixed $img
     * @param mixed $func
     * @param mixed $thumb
     * @access protected
     * @return void
     */
    protected function _thumb($img, $func, $thumb) {
        $path = $this->_path();
        $name = $thumb[0];

        $size = array(
            imagesx($img),
            imagesy($img)
        );

        $xr = $size[0] / $thumb[1];
        $yr = $size[1] / $thumb[2];

        $crop = isset($thumb[3]) ? $thumb[3] : false;

        $src = array(0, 0);
        if ($xr > $yr) {
            $w = $thumb[1];
            if ($crop) {
                $h = $thumb[2];
                $diff = ($size[0] - $w) / $yr;
                $src[0] += $diff;
                $size[0] -= $diff;
            } else {
                $h = $size[1] / $xr;
            }
        } else {
            $h = $thumb[2];
            if ($crop) {
                $w = $thumb[1];
                $diff = ($size[1] - $h) / $xr;
                $src[1] += $diff;
                $size[1] -= $diff;
            } else {
                $w = $size[0] / $yr;
            }
        }

        $tImg = imagecreatetruecolor($w, $h);
        imagecopyresampled($tImg, $img, 0, 0, $src[0], $src[1], $w, $h, $size[0], $size[1]);

        if ($func[1]($tImg, $this->_uploadDir . $path)) {
            $return = compact('name', 'path');
        }

        imagedestroy($tImg);

        return $return;
    }

    /**
     * _imagefunc
     *
     * @param mixed $type
     * @access protected
     * @return void
     */
    protected function _imagefunc($type) {
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
    * Valideaza faptul ca un fisier a fost adaugat pentru upload
    *
    * @param mixed $value
    * @access public
    * @return void
    */
    public function validateFileUploaded($model, $value) {
        $field = current($value);
        return $field['error'] === UPLOAD_ERR_OK;
    }

    /**
    * validateFile
    *
    * @param mixed $model
    * @param mixed $value
    * @access public
    * @return void
    */
    public function validateFileRequired($model, $value) {
        $field = current($value);
        return $field['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
    * validateFileSize
    *
    * @param mixed $model
    * @param mixed $value
    * @access public
    * @return void
    */
    public function validateFileSize($model, $value) {
        $field = current($value);
        if ($field['error'] == UPLOAD_ERR_INI_SIZE) {
            return false;
        }
        return true;
    }

    /**
     * validateIsImage
     *
     * @param $model $value
     * @access public
     * @return void
     */
    public function validateIsImage($model, $value) {
        // validate true if file not uploaded
        if (!$this->validateFileRequired($model, $value)) {
            return true;
        }
        $field = current($value);
        return $this->_isImageType($field['type']);
    }

}

?>
