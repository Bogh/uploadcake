<?php

class UploadHelper extends AppHelper {

    /**
     * helpers
     *
     * @var string
     * @access public
     */
    public $helpers = array('Html');

    /**
     * Returneaza un link catre controller-ul ce randeaza imaginea
     *
     * @param string $title Ce sa afiseze link-ul
     * @param int $id ID Upload
     * @param string $name Numele thumb-ului (optional)
     * @param array $options Extra options pentru link (optional)
     *
     * @return string
     */
    public function link($title, $id, $name = null, $options = array(), $extra = array()) {
        return $this->Html->link($title, $this->url($id, $name, true, false, $extra), $options);
    }

    /**
     * Returneaza un tag de imagine cu src-ul setat catre imaginea cu ID
     *
     * @param int $id ID Upload
     * @param string $name Numele thumb-ului (optional)
     * @param array $options Extra options pentru link (optional)
     * - daca url e True in options atunci genereaza si un link in jurul imagini,
     * daca e array atunci creeaza un link normal cu HtmlHelper::link
     *
     * @return string
     */
    public function image($id, $name = null, $options = array()) {
        if (isset($options['url'])) {
            if ($options['url'] === true) {
                $options['url'] = $this->url($id, $name, true);
            } elseif (is_int($options['url'])) {
                $options['url'] = $this->url($options['url'], null, true);
            }
        }

        $image = $this->Html->image($this->url($id, $name, true), $options);
        return  $image;
    }

    /**
     * Returneaza un link catre o imagine data de ID si Name
     *
     * @param int $id ID Upload
     * @param string $name Numele thumb-ului (optional)
     * @param boolean $array
     * @param boolean $full True returneaza link-ul complet, valabil daca $array = false (optional)
     *
     * @return mixed Array daca $array e True, string daca e false
     */
    public function url($id, $name = null, $array = false, $full = false, $extra = array()) {
        $url = Hash::merge(array(
            'plugin' => 'upload',
            'controller' => 'uploads',
            'action' => 'index',
            $id,
            $name
        ), $extra);

        if (!empty($this->params['prefix'])) {
            $url[$this->params['prefix']] = false;
        }
        if ($array) {
            return $url;
        }
        return Router::url($url, $full);
    }

}
