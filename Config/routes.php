<?php

Router::connect('/upload/*', array('controller' => 'uploads', 'action' => 'index', 'plugin' => 'upload'));

?>
