<?php

final class PlainSerialEncoder
{
    public static function encode ( $value, string $key ) {
        return serialize( $value );
    }

    public static function decode ( $data, string $key ) {
        return unserialize( $data );
    }
}