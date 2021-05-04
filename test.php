<?php
require __DIR__ . "/BasicValueObject.php";
require __DIR__ . "/StaticClass.php";
require __DIR__ . "/PlainSerialEncoder.php";
require __DIR__ . "/SecretScalarStore.php";
require __DIR__ . "/SecretScalar.php";
require __DIR__ . "/SpecialException.php";

function crash () {
    clone new class {
        public function __clone () { clone $this; }
    };
}

function wipe ( &$string ) {
    for ( $i = 0; $i < strlen( $string ); $i++ ) {
        $string[$i] = "\0";
    }
}

function backtraceLeak ( $argument ) {
    debug_print_backtrace();
}

function failing ( $argument ) {
    throw new SpecialException( "Argument leaked", 0, null, $argument );
}

function someArbitraryAccessor ( $a ) {
    return $a();
}

$secret = file_get_contents( __DIR__ . "/secret" );
$value = new \SecretScalar( $secret );
$value = new \SecretScalar( 31337 );
$value = new \SecretScalar( $secret );
ini_set( 'memory_limit', '128m' );
try {
    $reflectedObject = new ReflectionObject( $value );
    $reflectedObject->getMethod( "getValue" )->invoke( $value );
    echo "ReflectionObject(value): WAS EVALUATED !!!\n";
    die;
} catch ( \TypeError $error ) {
//    echo "ReflectionObject(value) errored out: " . $error->getMessage() . "\n";
}
try {
    $reflectedArbitraryFunction = new ReflectionFunction( "someArbitraryAccessor" );
    $reflectedArbitraryFunction->invoke( $value );
    echo "ReflectionFunction(someArbitraryAccessor()) WAS EVALUATED !!!\n";
    die;
} catch ( \TypeError $error ) {
//    echo "Errored out in ReflectionFunction(someArbitraryAccessor()): " . $error->getMessage() . "\n";
}
$reflectionStoreClass = new ReflectionClass( 'SecretScalarStore' );
if ( $reflectionStoreClass->hasMethod( "map" ) ) {
    $reflectionStoreMethod = $reflectionStoreClass->getMethod( 'map' );
    try {
        $reflectionStoreMethod = new ReflectionMethod( 'SecretScalarStore', 'map' );
        $reflectionStoreMethod->setAccessible( true );
        $reflectionStoreMethod->invoke( null );
        echo "ReflectionMethod(SecretScalarStore::map()) WAS EXECUTED !!!\n";
        die;
    } catch ( \TypeError $error ) {
    } catch ( \RuntimeException $error ) {
//        echo "ReflectionMethod(SecretScalarStore::map()) errored out: " . $error->getMessage() . "\n";
    }
    try {
        foreach ( $reflectionStoreMethod->getStaticVariables()["map"] as $secretValue ) {
            $secretValue();
        }
        echo "ReflectionMethod(SecretScalarStore::map())::getStaticVariables(map) WAS EVALUATED !!!\n";
        die;
    } catch ( \RuntimeException $exception ) {
//        echo "ReflectionMethod(SecretScalarStore::map())::getStaticVariables(map) errored out: " . $exception->getMessage() . "\n";
    }
    $closureReflector = new ReflectionFunction( $reflectionStoreMethod->getStaticVariables()["map"][1] );
    if ( isset( $closureReflector->getStaticVariables()["value"] ) ) {
        if ( is_object( $closureReflector->getStaticVariables()["value"] ) ) {
            $closureReflector->getStaticVariables()["value"]->value = "TAMPERED";
            if ( $closureReflector->getStaticVariables()["value"]->value === "TAMPERED" ) {
                echo "Reflection of closure from reflected method WAS EVALUATED !!!\n";
                die;
            }
        } else {
            $closureReflector->getStaticVariables()["value"] = "TAMPERED";
            if ( $closureReflector->getStaticVariables()["value"] === "TAMPERED" ) {
                echo "Reflection of closure from reflected method WAS EVALUATED !!!\n";
                die;
            }
        }
    }
} else {
//    echo "SecretScalarStore::map() is undefined\n";
}
if ( isset( $reflectionStoreClass->getStaticProperties()["map"] ) ) {
    $variables = $reflectionStoreClass->getStaticProperties();
    $variables["map"]["TAMPERED"] = "TAMPERED";
    $variables = $reflectionStoreClass->getStaticProperties();
    if ( isset( $variables["map"]["TAMPERED"] ) ) {
        echo "ReflectionClass@SecretScalarStore::\$map WAS TAMPERED !!!\n";
        die;
    } else {
//        echo "ReflectionClass@SecretScalarStore::\$map stayed clean\n";
    }
    try {
        foreach ( $variables["map"] as $valueObject ) {
            $valueObject();
        }
        echo "ReflectionClass@SecretScalarStore::\$map[]() WAS EVALUATED !!!\n";
        die;
    } catch ( \RuntimeException $exception ) {
//        echo "ReflectionClass@SecretScalarStore::\$map[]() errored out: " . $exception->getMessage() . "\n";
    }
    $reflectedStaticProperty = new ReflectionProperty( SecretScalarStore::class, 'map' );
    $reflectedStaticProperty->setAccessible( true );
    try {
        $map = $reflectedStaticProperty->getValue( null );
        $map["TAMPERED"] = "TAMPERED";
        $map = $reflectedStaticProperty->getValue( null );
        if ( isset( $map["TAMPERED"] ) ) {
            echo "ReflectionProperty@SecretScalarStore::\$map WAS TAMPERED !!!\n";
            die;
        } else {
//            echo "ReflectionProperty@SecretScalarStore::\$map stayed clean\n";
        }
        $reflectedStaticProperty->setValue( null, ["TAMPERED" => "TAMPERED"] );
        $map = $reflectedStaticProperty->getValue( null );
        if ( isset( $map["TAMPERED"] ) ) {
            echo "ReflectionProperty::setValue@SecretScalarStore::\$map WAS TAMPERED !!!\n";
            die;
        } else {
//            echo "ReflectionProperty::setValue@SecretScalarStore::\$map stayed clean\n";
        }
    } catch ( \TypeError $exception ) {
//        echo "::newInstanceWithoutConstructor errored out: " . $exception->getMessage() . "\n";
    }
} else {
//    echo "SecretScalarStore::\$map is undefined\n";
}
try {
    $funky = $reflectedObject->newInstanceWithoutConstructor();
    $funky->getValue();
    echo "::newInstanceWithoutConstructor RESULT WAS EVALUATED !!!\n";
    die;
} catch ( \TypeError $exception ) {
//    echo "::newInstanceWithoutConstructor errored out: " . $exception->getMessage() . "\n";
}
try {
    $value->__construct( "TAMPERED" );
    echo "SecretScalar->__construct RESULT WAS EVALUATED !!!\n";
    die;
} catch ( \TypeError $typeError ) {
}
$lastMemUsage = memory_get_usage();
$testAmountBound = 10;
$leakEpsilon = 10;
for ( $i = 1; $i <= $testAmountBound; $i++ ) {
//    $value2 = new SecretScalar( $secret . $i );
    $temp = $secret . $i;
    $value2 = new SecretScalar( $temp );
    wipe( $temp );
    if ( $i % 1000 === 0 ) {
        $map = $reflectionStoreMethod->getStaticVariables()["map"];
        $last = array_key_last( $map );
        $previousMem = $lastMemUsage;
        $lastMemUsage = memory_get_usage();
        $bytesPerItem = floor( ( $lastMemUsage - $previousMem ) / 1000 );
        if ( $bytesPerItem > $leakEpsilon ) {
            echo "~ $bytesPerItem bytes leaked per item\n";
//            die;
        }
    }
}
if ( count( $reflectionStoreMethod->getStaticVariables()["map"] ) !== 2 ) {
    echo "Unreferenced elements remain!!!\n";
    var_dump( $reflectionStoreMethod->getStaticVariables()["map"] );
    die;
}
$temp2 = $secret . $testAmountBound;
if ( $value2->getValue() !== $temp2 ) {
    echo "VALUE DAMAGED: {$value2->getValue()}\n";
    die;
}
wipe( $temp2 );
if ( $value->getValue() !== $secret ) {
    echo "VALUE DAMAGED: {$value->getValue()}\n";
    die;
}
wipe( $secret );
$closure = function () {
    return $this->getValue();
};
//echo $closure->call( $value ) . "\n"; // todo:
$closure = ( function () {
    return $this->getValue();
} )->bindTo( $value ); // todo:
//echo $closure() . "\n";
unset( $value );
unset( $value2 );
crash();
// todo: forward_static_call