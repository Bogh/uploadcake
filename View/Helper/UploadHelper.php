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
        if (empty($id)) {
            return '';
        }
        return $this->Html->link($title, $this->uri($id, $name, true, false, $extra), $options);
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
        if (empty($id)) {
            return '';
        }
        if (isset($options['url'])) {
            if ($options['url'] === true) {
                $options['url'] = $this->uri($id, $name, true);
            } elseif (is_int($options['url'])) {
                $options['url'] = $this->uri($options['url'], null, true);
            }
        }

        $attrs = (isset($options['attrs']) ? $options['attrs'] : array());

        $image = $this->Html->image($this->uri($id, $name, true), $attrs);
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
    public function uri($id, $name = null, $array = false, $full = false, $extra = array()) {
        if (empty($id)) {
            return '';
        }
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

    public function editImage($field, $type = null) {
        if (Hash::check($this->request->data, $field)) {
            $value = Hash::extract($this->request->data, $field);
            $value = array_pop($value);
            if (!empty($value)) {
                return $this->image($value, $type);
            }
        }
        return '';
    }

}
