<?php

App::uses('Up', 'Upload.Utility');

class UploadPath extends UploadAppModel {


    /**
     * _delete
     *
     * @var array
     * @access protected
     */
    protected $_delete = null;

    /**
     * beforeDelete
     *
     * @access public
     * @return void
     */
    public function beforeDelete($cascade = true) {
        $this->_delete = $this->field('path');
    }


    /**
     * afterDelete
     *
     * @access public
     * @return void
     */
    public function afterDelete() {
        if ($this->_delete) {
            unlink(Up::uploadDir() . $this->_delete);
            $this->_delete = null;
        }
    }

    /**
     * Returns path of file for name = null and uploadId
     */
    public function defaultPath($uploadId) {
        $path =  $this->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'UploadPath.upload_id' => $uploadId,
                'UploadPath.name' => null
            )
        ));

        if (!empty($path)) {
            return $path['UploadPath']['path'];
        }
        return null;
    }

}

