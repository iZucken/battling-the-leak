<?php

final class FileKeySerialEncoder
{
    public static function encode ( $value, string $key ) {
        $vector = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-128-gcm' ) );
        $key = file_get_contents( __DIR__ . "/key" );
        $result = base64_encode( serialize( [openssl_encrypt( serialize( $value ), 'aes-128-gcm', $key, 0, $vector, $tag ), $vector, $tag] ) );
        sodium_memzero( $key );
        return $result;
    }

    public static function decode ( $data, string $key ) {
        $data = unserialize( base64_decode( $data ) );
        $key = file_get_contents( __DIR__ . "/key" );
        $result = unserialize( openssl_decrypt( $data[0], 'aes-128-gcm', $key, 0, $data[1], $data[2] ) );
        sodium_memzero( $key );
        return $result;
    }
}