<?php

App::uses('Up', 'Upload.Utility');

class Upload extends UploadAppModel {

    /**
     * actsAs
     *
     * @var string
     * @access public
     */
    public $actsAs = array('Containable');

    /**
     * hasMany
     *
     * @var array
     * @access public
     */
    public $hasMany = array(
        'UploadPath' => array(
            'className' => 'Upload.UploadPath',
            'foreignKey' => 'upload_id',
            'dependent' => true
        )
    );

    /**
     * getUpload
     *
     * @param mixed $id
     * @param mixed $name
     * @access public
     * @return void
     */
    public function getUpload($id, $name = null) {
        $this->id = $id;
        if (!Configure::check("Upload.thumbs.{$name}") || !$this->exists()) {
            return null;
        }

        $upload = $this->find('first', array(
            'contain' => array(
                'UploadPath' => array(
                    'conditions' => array(
                        'UploadPath.name' => $name
                    )
                )
            ),
            'conditions' => array('Upload.id' => $id)
        ));
        if (empty($upload['UploadPath'])) {
            // generate thumb
            $upload = $this->_thumb($upload, $name);
        }

        return $upload;
    }

    public function etagHash($id) {
        return Security::hash($id);
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
    protected function _thumb($upload, $name) {
        $u = $upload['Upload'];

        // original file path
        $uPath = $this->UploadPath->defaultPath($u['id']);
        if (is_null($uPath)) {
            return null;
        }

        $func = Up::imagefunc($u['type']);
        $img = $func[0](Up::uploadDir() . $uPath);

        $thumb = Hash::merge(array(
            100, 100, 0
        ), Configure::read("Upload.thumbs.{$name}"));

        $size = array(imagesx($img), imagesy($img));
        // var_dump($thumb); die;
        $xr = $size[0] / $thumb[0];
        $yr = $size[1] / $thumb[1];

        $crop = isset($thumb[2]) ? $thumb[2] : false;

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

        $path = Up::path();
        if ($func[1]($tImg, Up::uploadDir() . $path)) {
            $uploadPath = compact('name', 'path') + array(
                'upload_id' => $u['id']
            );
            if (!$this->UploadPath->save($uploadPath)) {
                return null;
            }
            $upload['UploadPath'][] = $uploadPath;
        }

        imagedestroy($tImg);
        imagedestroy($img);

        return $upload;
    }

}
