<?php

final class MemKeySerialEncoder
{
    public static function &key () {
        static $key;
        if ( $key ) {
            fseek( $key, 0 );
            return $key;
        }
        $key = fopen( "php://memory", "wb" );
        $temp = file_get_contents( __DIR__ . "/key" ) . substr( base64_encode( random_bytes( 32 ) ), 0, 27 );
        fwrite( $key, $temp );
        sodium_memzero( $temp );
        unset( $temp );
        return $key;
    }

    public static function encode ( $value, string $key ) {
        $vector = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-128-gcm' ) );
        return base64_encode( serialize( [openssl_encrypt( serialize( $value ), 'aes-128-gcm', fread( self::key(), 32 ), 0, $vector, $tag ), $vector, $tag] ) );
    }

    public static function decode ( $data, string $key ) {
        $data = unserialize( base64_decode( $data ) );
        return unserialize( openssl_decrypt( $data[0], 'aes-128-gcm', fread( self::key(), 32 ), 0, $data[1], $data[2] ) );
    }
}