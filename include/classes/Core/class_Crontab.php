<?php
class Crontab
{
    private $registry;
    private $renderer;

    public function __construct()
    {
        global $website, $renderer;

        $this -> registry = $website;
        $this -> renderer = $renderer;
    }

    public function __destruct()
    {
        unset($this -> registry);
        unset($this -> renderer);
    }

    public function showCrontabList()
    {
        $this -> renderer -> loadTemplate('crontab' . DS . 'list.htm');
        return $this -> renderer -> renderTemplate();
    }
}