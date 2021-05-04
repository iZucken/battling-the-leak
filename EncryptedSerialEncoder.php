<?php

final class EncryptedSerialEncoder
{
    public static function encode ( $value, string $key ) {
        $vector = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-128-gcm' ) );
        return base64_encode( serialize( [openssl_encrypt( serialize( $value ), 'aes-128-gcm', $key, 0, $vector, $tag ), $vector, $tag] ) );
    }

    public static function decode ( $data, string $key ) {
        $data = unserialize( base64_decode( $data ) );
        return unserialize( openssl_decrypt( $data[0], 'aes-128-gcm', $key, 0, $data[1], $data[2] ) );
    }
}