<?php

/**
 * Special olympics kinda quest.
 * A trick to hide value from reflection, debugging and other kinds of indirect "non-userland" access;
 * i.e. should only allow direct access by a straightforward code.
 * No extensions, otherwise we could possibly write a simple wrapper to discard and then recreate zvals.
 * Avoid serialization to preserve links.
 *
 * @template T<scalar>
 *
 * @see      SecretScalarStore - staticly bound value storage
 */
final class SecretScalar
{
    /**
     * Constructs a secret value
     *
     * @param T<scalar> $value
     *
     * @throws \TypeError
     */
    public function __construct ( $value ) {
        if ( !is_scalar( $value ) ) {
            throw new \TypeError( "Expected scalar, got " . gettype( $value ) );
        }
        if ( SecretScalarStore::has( $this ) ) {
            throw new \TypeError( "Value was already initialized" );
        }
        SecretScalarStore::set( $this, $value );
    }

    /**
     * Gets secret, prevents indirect access
     *
     * @return T<scalar>
     *
     * @throws \TypeError
     */
    public function getValue () {
//        var_dump(debug_backtrace());
        SecretScalarStore::expectNoReflectionContext();
        if ( !SecretScalarStore::has( $this ) ) {
            throw new \TypeError( "Value was never initialised; only constructor provided value can be used." );
        }
        return SecretScalarStore::get( $this );
    }

    public function __toString () : string {
        return "";
    }

    public function __invoke () {
        throw new \TypeError( "Magic invoke can't be used" );
    }

    public function __set ( string $name, $value ) : void {
        throw new \TypeError( "Magic set can't be used" );
    }

    public function __get ( string $name ) {
        throw new \TypeError( "Magic get can't be used" );
    }

    public function __call ( string $name, array $arguments ) {
        throw new \TypeError( "Magic call can't be used" );
    }

    public static function __callStatic ( string $name, array $arguments ) {
        throw new \TypeError( "Magic static call can't be used" );
    }

    /**
     * Frees secret
     */
    public function __destruct () {
        SecretScalarStore::drop( $this );
    }

    /**
     * Prevents debugging
     */
    public function __debugInfo () {
    }

    /**
     * Prevents cloning
     *
     * @throws \TypeError
     */
    public function __clone () {
        throw new \TypeError( "Value cannot be cloned" );
    }

    /**
     * Prevents serialization
     *
     * @return array
     *
     * @throws \TypeError
     */
    public function __serialize () : array {
        throw new \TypeError( "Value cannot be serialized" );
    }

    /**
     * Prevents deserialization
     *
     * @param array $data
     *
     * @throws \TypeError
     */
    public function __unserialize ( array $data ) : void {
        throw new \TypeError( "Value cannot be unserialized" );
    }
}