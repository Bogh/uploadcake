<?php

class UploadPath extends UploadAppModel {

	/**
	 * _uploadDir
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_uploadDir = null;

	/**
	 * _delete
	 *
	 * @var array
	 * @access protected
	 */
	protected $_delete = null;

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
	 * beforeDelete
	 *
	 * @access public
	 * @return void
	 */
	public function beforeDelete() {
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
			unlink($this->_uploadDir . $this->_delete);
			$this->_delete = null;
		}
	}

}

