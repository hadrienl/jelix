/**
*  Jelix
*  a php extension for Jelix Framework
* @copyright Copyright (c) 2006-2007 Laurent Jouanneau
* @author : Laurent Jouanneau
* @link http://jelix.org
* @licence  GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/


#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_jelix.h"
#include "jelix_interfaces.h"

ZEND_DECLARE_MODULE_GLOBALS(jelix)

/* True global resources - no need for thread safety here */
static int le_jelix;

/* {{{ jelix_functions[]
 *
 * Every user visible function must have an entry in jelix_functions[].
 */
zend_function_entry jelix_functions[] = {
	PHP_FE(jelix_version,	NULL)
	PHP_FE(jelix_read_ini,  NULL)
	PHP_FE(jelix_scan_selector,  NULL)
	{NULL, NULL, NULL}	/* Must be the last line in jelix_functions[] */
};
/* }}} */

/* {{{ jelix_module_entry
 */
zend_module_entry jelix_module_entry = {
	STANDARD_MODULE_HEADER,
	"jelix",
	jelix_functions,
	PHP_MINIT(jelix),
	PHP_MSHUTDOWN(jelix),
	PHP_RINIT(jelix),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(jelix),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(jelix),
	JELIX_VERSION,
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_JELIX
ZEND_GET_MODULE(jelix)
#endif

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("jelix.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_jelix_globals, jelix_globals)
    STD_PHP_INI_ENTRY("jelix.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_jelix_globals, jelix_globals)
PHP_INI_END()
*/
/* }}} */

/* {{{ php_jelix_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_jelix_init_globals(zend_jelix_globals *jelix_globals)
{
	jelix_globals->global_value = 0;
	jelix_globals->global_string = NULL;
}
*/
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(jelix)
{
	/* If you have INI entries, uncomment these lines
	REGISTER_INI_ENTRIES();
	*/

	PHP_MINIT(jelix_interfaces)(INIT_FUNC_ARGS_PASSTHRU);

    REGISTER_LONG_CONSTANT("JELIX_SEL_MODULE", JELIX_SELECTOR_MODULE, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JELIX_SEL_ACTION", JELIX_SELECTOR_ACTION, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JELIX_SEL_LOCALE", JELIX_SELECTOR_LOCALE, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JELIX_SEL_SIMPLEFILE", JELIX_SELECTOR_SIMPLEFILE, CONST_CS | CONST_PERSISTENT);
    REGISTER_STRING_CONSTANT("JELIX_NAMESPACE_BASE", "http://jelix.org/ns/", CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_FETCH_OBJ", 5, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_FETCH_ORI_NEXT", 0, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_FETCH_ORI_FIRST", 2, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_FETCH_COLUMN", 7, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_FETCH_CLASS", 8, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_ATTR_STATEMENT_CLASS", 13, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_ATTR_AUTOCOMMIT", 0, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_ATTR_CURSOR", 10, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_CURSOR_SCROLL", 1, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_ATTR_ERRMODE", 3, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_ERRMODE_EXCEPTION", 2, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("JPDO_MYSQL_ATTR_USE_BUFFERED_QUERY", 1000, CONST_CS | CONST_PERSISTENT);

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(jelix)
{
	/* uncomment this line if you have INI entries
	UNREGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request start */
/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(jelix)
{
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(jelix)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(jelix)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "jelix framework support", "enabled");
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */


/* {{{ proto string confirm_jelix_compiled(string arg)
   Return a string to confirm that the module is compiled in */
PHP_FUNCTION(jelix_version)
{
	if(ZEND_NUM_ARGS() != 0)  ZEND_WRONG_PARAM_COUNT()

	RETURN_STRINGL(JELIX_VERSION, sizeof(JELIX_VERSION)-1, 1);
}
/* }}} */



static void jelix_ini_parser_cb(zval *arg1, zval *arg2, int callback_type, zval *obj)
{
	TSRMLS_FETCH();

/*
ZEND_INI_PARSER_ENTRY       foo = bar
ZEND_INI_PARSER_POP_ENTRY   foo[]=bar
ZEND_INI_PARSER_SECTION		[section]
*/

	if (callback_type == ZEND_INI_PARSER_SECTION) {

		zval *hash, **find_hash;

		if (zend_hash_find(Z_OBJPROP_P(obj), Z_STRVAL_P(arg1), Z_STRLEN_P(arg1)+1, (void **) &find_hash) == SUCCESS
			&& Z_TYPE_P(*find_hash) == IS_ARRAY) {

			JELIX_G(active_ini_file_section) = *find_hash;

		} else if (is_numeric_string(Z_STRVAL_P(arg1), Z_STRLEN_P(arg1), NULL, NULL, 0) != IS_LONG) {
			ALLOC_ZVAL(hash);
			INIT_PZVAL(hash);
			array_init(hash);
			zend_hash_update(Z_OBJPROP_P(obj), Z_STRVAL_P(arg1), Z_STRLEN_P(arg1)+1, &hash, sizeof(zval *), NULL);

			JELIX_G(active_ini_file_section) = hash;
		}


	} else if (arg2) {

		zval *element;
		ALLOC_ZVAL(element);
		*element = *arg2;
		zval_copy_ctor(element);
		INIT_PZVAL(element);


		if (JELIX_G(active_ini_file_section)) {
			// il faut ajouter la valeur en tant qu'element � un tableau
			zval * arr;
			arr = JELIX_G(active_ini_file_section);

			if (callback_type == ZEND_INI_PARSER_ENTRY) {
				if (is_numeric_string(Z_STRVAL_P(arg1), Z_STRLEN_P(arg1), NULL, NULL, 0) != IS_LONG) {
					zend_hash_update(Z_ARRVAL_P(arr), Z_STRVAL_P(arg1), Z_STRLEN_P(arg1)+1, &element, sizeof(zval *), NULL);
				} else {
					ulong key = (ulong) zend_atoi(Z_STRVAL_P(arg1), Z_STRLEN_P(arg1));
					zend_hash_index_update(Z_ARRVAL_P(arr), key, &element, sizeof(zval *), NULL);
				}
			} else if (	callback_type == ZEND_INI_PARSER_POP_ENTRY ) {
				zval *hash, **find_hash;

				if (is_numeric_string(Z_STRVAL_P(arg1), Z_STRLEN_P(arg1), NULL, NULL, 0) != IS_LONG) {
					if (zend_hash_find(Z_ARRVAL_P(arr), Z_STRVAL_P(arg1), Z_STRLEN_P(arg1)+1, (void **) &find_hash) == FAILURE) {
						ALLOC_ZVAL(hash);
						INIT_PZVAL(hash);
						array_init(hash);
						zend_hash_update(Z_ARRVAL_P(arr), Z_STRVAL_P(arg1), Z_STRLEN_P(arg1)+1, &hash, sizeof(zval *), NULL);
					} else {
						hash = *find_hash;
					}
				} else {
					ulong key = (ulong) zend_atoi(Z_STRVAL_P(arg1), Z_STRLEN_P(arg1));
					if (zend_hash_index_find(Z_ARRVAL_P(arr), key, (void **) &find_hash) == FAILURE) {
						ALLOC_ZVAL(hash);
						INIT_PZVAL(hash);
						array_init(hash);
						zend_hash_index_update(Z_ARRVAL_P(arr), key, &hash, sizeof(zval *), NULL);
					} else {
						hash = *find_hash;
					}
				}

				add_next_index_zval(hash, element);
			}

		} else if (is_numeric_string(Z_STRVAL_P(arg1), Z_STRLEN_P(arg1), NULL, NULL, 0) != IS_LONG) {
			// il faut ajouter la valeur en tant que propri�t� d'objet
			if (callback_type == ZEND_INI_PARSER_ENTRY) {

				add_property_zval(obj , Z_STRVAL_P(arg1), element);

			} else if (	callback_type == ZEND_INI_PARSER_POP_ENTRY ) {
				zval *hash, **find_hash;
				if (zend_hash_find(Z_OBJPROP_P(obj), Z_STRVAL_P(arg1), Z_STRLEN_P(arg1)+1, (void **) &find_hash) == SUCCESS
					&& Z_TYPE_P(*find_hash) == IS_ARRAY ) {
					hash = *find_hash;
				} else {
					ALLOC_ZVAL(hash);
					INIT_PZVAL(hash);
					array_init(hash);
					zend_hash_update(Z_OBJPROP_P(obj), Z_STRVAL_P(arg1), Z_STRLEN_P(arg1)+1, &hash, sizeof(zval *), NULL);
				}
				add_next_index_zval(hash, element);
			}
		}
	}
}


/* {{{ proto object jelix_read_ini(string filename [, object existingobject])
   Parse configuration file */
PHP_FUNCTION(jelix_read_ini)
{
	zval **filename, **confObjectArg, *confObject;
	zend_file_handle fh;

	switch (ZEND_NUM_ARGS()) {

		case 1:
			if (zend_get_parameters_ex(1, &filename) == FAILURE) {
				RETURN_FALSE;
			}
			object_init(return_value);
			confObject = return_value;
			break;

		case 2:
			if (zend_get_parameters_ex(2, &filename, &confObjectArg) == FAILURE) {
				RETURN_FALSE;
			}
			if(Z_TYPE_P(*confObjectArg) != IS_OBJECT){
				php_error_docref(NULL TSRMLS_CC, E_WARNING, "Invalid second argument, not an object");
				object_init(return_value);
				confObject = return_value;
			}else{
				confObject = *confObjectArg;
			}
			break;

		default:
			ZEND_WRONG_PARAM_COUNT();
			break;
	}

	convert_to_string_ex(filename);

	memset(&fh, 0, sizeof(fh));
	fh.filename = Z_STRVAL_PP(filename);
	Z_TYPE(fh) = ZEND_HANDLE_FILENAME;
	JELIX_G(active_ini_file_section) = NULL;

	zend_parse_ini_file(&fh, 0, (zend_ini_parser_cb_t)jelix_ini_parser_cb, confObject);
}
/* }}} */

/* {{{ proto boolean jelix_scan_selector(string arg, object tofill [, int type])
   scna a string as a jelix selector, and fill object properties with founded values */
PHP_FUNCTION(jelix_scan_selector)
{
    zval **selectorStr, **objectArg, **typeArg;
    int length, type;
    char * sel, *cursor, *module, *resource;


    switch (ZEND_NUM_ARGS()) {
        case 2:
            if (zend_get_parameters_ex(2, &selectorStr, &objectArg) == FAILURE) {
                RETURN_FALSE;
            }
			type=JELIX_SELECTOR_MODULE;
			break;

		case 3:
			if (zend_get_parameters_ex(3, &selectorStr, &objectArg, &typeArg) == FAILURE) {
                php_error_docref(NULL TSRMLS_CC, E_WARNING, "cannot read arguments");
				RETURN_FALSE;
			}
	        convert_to_long_ex(typeArg);
            type = Z_LVAL_PP(typeArg);
            if(type < 1 || type > 4){
                php_error_docref(NULL TSRMLS_CC, E_WARNING, "Third argument doesn't correspond to one of JELIX_SEL_* constant");
                RETURN_FALSE
            }
            break;
		default:
			ZEND_WRONG_PARAM_COUNT();
			break;
	}

    if(Z_TYPE_P(*objectArg) != IS_OBJECT){
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "Invalid second argument, not an object");
        RETURN_FALSE
	}

    int module_length=0;
    int resource_length=0;
    int cursor_count=0;

    /*
    JELIX_SELECTOR_MODULE           /^(([\w\.]+)~)?([\w\.]+)$/
    JELIX_SELECTOR_ACTION           /^(?:([\w\.]+|\#)~)?([\w\.]+|\#)?(?:@([\w\.]+))?$/
    JELIX_SELECTOR_LOCALE           /^(([\w\.]+)~)?(\w+)\.([\w\.]+)$/
    JELIX_SELECTOR_SIMPLEFILE       /^([\w\.\/]+)$/
    */
    convert_to_string_ex(selectorStr);
    length = Z_STRLEN_PP(selectorStr);
    sel = Z_STRVAL_PP(selectorStr);

    cursor_count=0;
    cursor = module = resource = sel;

    int error = 0;
    int sharpOk = 0;

    // parse the module part
    while(cursor_count < length){
        if(*cursor == '~'){
            break;
        }
        if(*cursor == '#'){
            if(type != JELIX_SELECTOR_ACTION ){
                RETURN_FALSE
            }
            if(sharpOk || module_length > 1){
                RETURN_FALSE
            }
            sharpOk=1;
        }else{
            if(!( ( *cursor >= 'a' && *cursor <= 'z')
                || ( *cursor >= 'A' && *cursor <= 'Z')
                || ( *cursor >= '0' && *cursor <= '9')
                || *cursor == '_' || *cursor == '.') || sharpOk){
                RETURN_FALSE
            }
        }
        module_length ++;
        cursor_count ++;
        cursor++;
    }


    if(cursor_count >= length){
        // we don't find any '~' characters, so we have parsed the resource
        resource_length = module_length;
        module_length = 0;
    }else{
        // the string starts by a ~ : it's not really a problem, but we generate an error
        // to keep compatibily with php version.
        if(module_length == 0){
            RETURN_FALSE
        }

        cursor_count++;
        cursor++;
        resource = cursor;
        resource_length = 0;
        while(cursor_count < length){
            if( ( *cursor >= 'a' && *cursor <= 'z')
                || ( *cursor >= 'A' && *cursor <= 'Z')
                || ( *cursor >= '0' && *cursor <= '9')
                || ( *cursor == '_') || ( *cursor == '.')){
                resource_length ++;
                cursor_count ++;
                cursor++;
            }else{
                error =1;
                break;
            }
        }
        if(error){
            RETURN_FALSE
        }
    }

    if( resource_length == 0 ){
        RETURN_FALSE
    }

    zend_update_property_stringl(Z_OBJCE_P(*objectArg), *objectArg, "module", sizeof("module") - 1,	module, module_length TSRMLS_CC);
    zend_update_property_stringl(Z_OBJCE_P(*objectArg), *objectArg, "resource", sizeof("resource") - 1,	resource, resource_length TSRMLS_CC);

	RETURN_TRUE
}
/* }}} */

