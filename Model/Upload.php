<?php

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
		return $this->find('first', array(
			'contain' => array(
				'UploadPath' => array(
					'conditions' => array(
						'UploadPath.name' => $name
					)
				)
			),
			'conditions' => array('Upload.id' => $id)
		));
	}

	public function etagHash($id) {
		return Security::hash($id);
	}

}
