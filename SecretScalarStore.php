<?php

final class SecretScalarStore
{
//    private static $map = [];
    /**
     * @param SecretScalar $object
     * @param scalar       $value
     */
    public static function set ( SecretScalar $object, $value ) : void {
        if ( !is_scalar( $value ) ) {
            throw new \TypeError( "Expected scalar, got " . gettype( $value ) );
        }
        self::map()[spl_object_id( $object )] = self::enclose( PlainSerialEncoder::encode( $value, spl_object_hash( $object ) ) );
//        self::map()[spl_object_id( $object )] = self::enclose( $value );
    }

    /**
     * @param SecretScalar $object
     *
     * @return scalar
     */
    public static function get ( SecretScalar $object ) {
        return PlainSerialEncoder::decode( ( self::map() )[spl_object_id( $object )](), spl_object_hash( $object ) );
//        return ( self::map() )[spl_object_id( $object )]();
    }

    public static function has ( SecretScalar $object ) : bool {
        return array_key_exists( spl_object_id( $object ), self::map() );
    }

    public static function drop ( SecretScalar $object ) : void {
        SecretScalarStore::expectPreviousFrameMethodCall( 'SecretScalar', '__destruct' );
        if ( !self::has( $object ) ) {
            return;
        }
        wipe( self::map()[( spl_object_id( $object ) )]() );
        unset( self::map()[( spl_object_id( $object ) )] );
    }

    public static function expectNoReflectionContext () {
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 0 );
        foreach ( $backtrace as $frame ) {
            if ( !array_key_exists( 'object', $frame ) ) {
                continue;
            }
            if ( $frame['object'] instanceof \ReflectionFunctionAbstract ) {
                throw new \TypeError( "Cannot be accessed in reflection context" );
            }
        }
    }

    public static function expectPreviousFrameMethodCall ( string $class, string $function ) {
        $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 3 );
        if ( empty( $trace[2]["class"] ) || empty( $trace[2]["function"] ) || $trace[2]["class"] !== $class || $trace[2]["function"] !== $function ) {
            throw new \RuntimeException( "Only accessible from within $class::$function" );
        }
    }

    public static function expectPreviousFrameClass ( string $class ) {
        $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 3 );
        if ( empty( $trace[2]["class"] ) || $trace[2]["class"] !== $class ) {
            throw new \RuntimeException( "Only accessible from within $class" );
        }
    }

    private static function enclose ( string $value ) {
        return function &() use ( &$value ) {
            self::expectPreviousFrameClass( 'SecretScalarStore' );
            self::expectNoReflectionContext();
            return $value;
        };
//        $code = "return function () {
//            SecretScalarStore::expectPreviousFrameMethodCall('SecretScalarStore','get');
//            SecretScalarStore::expectNoReflectionContext();
//            return " . var_export( $value, 1 ) . ";
//        };";
//        $callback = eval( $code );
//        wipe( $code );
//        return $callback;
    }

    private static function &map () {
        self::expectPreviousFrameClass( 'SecretScalarStore' );
        self::expectNoReflectionContext();
        static $map = []; // generic array can stay "immutable" in reflected values even if returned by reference
//        static $map;
//        $map = $map ?? $map = new ArrayObject();
//        return $map;
        return $map;
    }
}