<?php

App::uses('Up', 'Upload.Utility');

class UploadableBehavior extends ModelBehavior {

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
    public function beforeSave(Model $model, $options = array()) {
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
                // do upload
                $value = $this->_upload($data, $this->settings[$model->alias][$field]);
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
     * @param mixed $settings
     * @access protected
     * @return void
     */
    protected function _upload($data, $settings) {
        $name = $type = $size = $isImage = $path = null;
        $paths = array();
        extract($settings, EXTR_OVERWRITE);
        extract($data, EXTR_OVERWRITE);
        $isImage = Up::isImageType($type);

        if (!$isImage || $keepOriginal) {
            if (!$path = $this->_moveFile($data['tmp_name'])) {
                return null;
            }
        }

        // if ($isImage && !empty($thumbs)) {
        //     $func = $this->_imagefunc($type);
        //     if ($keepOriginal && $isImage && $path) {
        //         $img = $func[0](Up::uploadDir() . $path['path']);
        //     } else {
        //         $img = $func[0]($tmp_name);
        //     }

            // foreach ($thumbs as $thumb) {
            //     if ($t = $this->_thumb($img, $func, $thumb)) {
            //         $paths[] = $t;
            //     }
            // }
        // }

        if ($isImage) {
            $sizes = getimagesize(Up::uploadDir() . $path['path']);
            $path = Hash::merge($path, array(
                'width' => $sizes[0],
                'height' => $sizes[1],
            ));
        }
        if ($path && (!$isImage || $keepOriginal)) {
            $paths[] = $path;
        }

        // if (isset($img)) {
        //     imagedestroy($img);
        // }

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
     * _moveFile
     *
     * @param mixed $from
     * @access protected
     * @return void
     */
    protected function _moveFile($from) {
        $path = Up::path();

        $to = Up::uploadDir() . $path;
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
        return Up::isImageType($field['type']);
    }

}

?>
