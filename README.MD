Story:
- notice a data leak
- elaborate risks
- try to protect from accidental leaks
- fight reflection
- what if we could protect from client code?
- fight reflection again
- what if it could be immutable?
- memory leak
- what if we could make it secure?
- core dump
- keys
- why defend from user-land?
- why defend from third-party?
- everything is global
- finally manage to "win", only to find out...
- indistinguishable callables
- try the vendors https://geekflare.com/secret-management-software/
- recommend the best practices (https://medium.com/@joecrobak/seven-best-practices-for-keeping-sensitive-data-out-of-logs-3d7bbd12904)

Principles
- dont keep in memory
- overwrite null
- setrlimit (no core dumping)
- mlock / munlock / mlockall (no swapping)
- do not store on disk

todo: explicit constructor call

ReflectionClass::getStaticProperties
ReflectionFunctionAbstract::getStaticVariables

```php
$memoryHandle = fopen( "php://memory", "wb" );
fwrite( $memoryHandle, self::encode( $value, self::key( $object ) ) );
self::instance()[spl_object_id( $object )] = $memoryHandle;
self::instance()[spl_object_id( $object )] = self::enclose( self::encode( $value, self::key( $object ) ) );

//    public static function &instance () {
//    private static function &instance () {
//        self::errorOutInReflectionCallStack();
        // only generic array can stay immutable even if returned by reference
//        static $map = [];
//        static $map;
//        $map = $map ?? $map = new ArrayObject();
//        return $map;
//        return self::$map;
//    }
```

Can't use closure evaluation in an intensive scenario due to a verified bug: https://bugs.php.net/bug.php?id=76982; still relevant in php8 to date.
Astonishingly, this bug "leaks" memory only in name - php manages to clean it up if starved for available memory.

```c
ZEND_METHOD(ReflectionFunctionAbstract, getStaticVariables)
{
	reflection_object *intern;
	zend_function *fptr;
	zval *val;

	if (zend_parse_parameters_none() == FAILURE) {
		RETURN_THROWS();
	}
	GET_REFLECTION_OBJECT_PTR(fptr);

	/* Return an empty array in case no static variables exist */
	if (fptr->type == ZEND_USER_FUNCTION && fptr->op_array.static_variables != NULL) {
		HashTable *ht;

		array_init(return_value);
		ht = ZEND_MAP_PTR_GET(fptr->op_array.static_variables_ptr);
		if (!ht) {
			ZEND_ASSERT(fptr->op_array.fn_flags & ZEND_ACC_IMMUTABLE);
			ht = zend_array_dup(fptr->op_array.static_variables);
			ZEND_MAP_PTR_SET(fptr->op_array.static_variables_ptr, ht);
		}
		ZEND_HASH_FOREACH_VAL(ht, val) {
			if (UNEXPECTED(zval_update_constant_ex(val, fptr->common.scope) != SUCCESS)) {
				return;
			}
		} ZEND_HASH_FOREACH_END();
		zend_hash_copy(Z_ARRVAL_P(return_value), ht, zval_add_ref);
	} else {
		RETURN_EMPTY_ARRAY();
	}
}
```

```c
ZEND_METHOD(ReflectionClass, getStaticProperties)
{
	reflection_object *intern;
	zend_class_entry *ce;
	zend_property_info *prop_info;
	zval *prop;
	zend_string *key;

	if (zend_parse_parameters_none() == FAILURE) {
		RETURN_THROWS();
	}

	GET_REFLECTION_OBJECT_PTR(ce);

	if (UNEXPECTED(zend_update_class_constants(ce) != SUCCESS)) {
		RETURN_THROWS();
	}

	if (ce->default_static_members_count && !CE_STATIC_MEMBERS(ce)) {
		zend_class_init_statics(ce);
	}

	array_init(return_value);

	ZEND_HASH_FOREACH_STR_KEY_PTR(&ce->properties_info, key, prop_info) {
		if (((prop_info->flags & ZEND_ACC_PRIVATE) &&
		     prop_info->ce != ce)) {
			continue;
		}
		if ((prop_info->flags & ZEND_ACC_STATIC) == 0) {
			continue;
		}

		prop = &CE_STATIC_MEMBERS(ce)[prop_info->offset];
		ZVAL_DEINDIRECT(prop);

		if (ZEND_TYPE_IS_SET(prop_info->type) && Z_ISUNDEF_P(prop)) {
			continue;
		}

		/* enforce read only access */
		ZVAL_DEREF(prop);
		Z_TRY_ADDREF_P(prop);

		zend_hash_update(Z_ARRVAL_P(return_value), key, prop);
	} ZEND_HASH_FOREACH_END();
}
```
