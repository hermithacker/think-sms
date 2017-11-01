<?php
namespace think\sms\supplier;

abstract class Supplier
{
    protected $options = [];

    abstract public function template();

    abstract public function applyTemplate($name, $content);

    abstract public function deleteTemplate($templateCode);

    abstract public function message($mobile,$templateCode,$data);
}
