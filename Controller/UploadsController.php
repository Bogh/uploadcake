<?php

class UploadsController extends UploadAppController {

    public function beforeFilter() {
        parent::beforeFilter();

        if (isset($this->Auth)) {
            $this->Auth->allow('index');
        }
    }

    /**
     * index
     *
     * @access public
     * @return void
     */
    public function index($id, $name = null) {
        if (!$dir = Configure::read('Upload.dir')) {
            $dir = APP . 'Uploads' . DS;
        }

        $upload = $this->Upload->getUpload($id, $name);
        if (empty($upload)) {
            throw new NotFoundException();
        }

        $uploadPath = $upload['UploadPath'][0];
        $upload = $upload['Upload'];
        $path = pathinfo($upload['name']);

        $this->response->file($dir . $uploadPath['path'], array(
            'name' => $upload['name'],
            'download' => isset($this->request->named['download']) ? true : false
        ));
        $this->response->type($path['extension']);
        $this->autoRender = false;
    }

}

