<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $script
 */
    use Cake\Core\Configure;
    $minFilename = WWW_ROOT . 'js' . DS . "$script.min.js";
    $useMin = Configure::read('debug') && file_exists($minFilename);
    $filename = $script . ($useMin ? '.min' : '');
    $this->Html->script($filename, ['block' => 'scriptBottom']);
