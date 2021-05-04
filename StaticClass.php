<?php

class StaticClass
{
    private function __construct () { }

    public function __debugInfo () { }

    private function __clone () { }

    public function __serialize () : array {
        return [];
    }

    public function __unserialize ( array $data ) : void {
    }
}