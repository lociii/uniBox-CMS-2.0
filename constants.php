<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
* 0.1       01.03.2005  jn      1st release
* 0.11      06.04.2005  jn      added many constants ;)
*
*/

define('SESSION_NAME', 'unibox_session');

// TODO: add mime file to uniBox distribution
define('DIR_MIME_FILE', 'D:\Programme\Apache2\php5\extras\magic');

// define directories
define('DIR_FRAMEWORK', 'framework/');
define('DIR_FRAMEWORK_DATABASE', DIR_FRAMEWORK.'database/');
define('DIR_FRAMEWORK_DATABASE_LAYERS', DIR_FRAMEWORK_DATABASE.'layers/');
define('DIR_FRAMEWORK_DATABASE_TOOLS', DIR_FRAMEWORK_DATABASE.'tools/');
define('DIR_FRAMEWORK_SCHEMATA', DIR_FRAMEWORK.'schemata/');
define('DIR_INSTALL', 'install/');
define('DIR_TEMP', DIR_FRAMEWORK.'temp/');
define('DIR_MEDIA', 'media/');
define('DIR_MEDIA_CACHE', DIR_MEDIA.'cache/');
define('DIR_MEDIA_BASE', DIR_MEDIA.'base/');
define('DIR_BANNER', DIR_MEDIA.'banner/');
define('DIR_CHARTS', DIR_MEDIA.'charts/');
define('DIR_MEDIA_BASE_UPLOAD', DIR_MEDIA_BASE.'upload/');
define('DIR_MEDIA_BASE_UPLOAD_POOL', DIR_MEDIA_BASE.'pool/');
define('DIR_USER_DETAILS', 'media/saarintim/userdetails/');
define('DIR_USER_DETAILS_UPLOAD', DIR_USER_DETAILS.'upload/');
define('DIR_COMMERCIALS', 'media/saarintim/commercials/');
define('DIR_COMMERCIALS_UPLOAD', DIR_COMMERCIALS.'upload/');
define('DIR_MODULES', 'modules/');
define('DIR_THEMES', 'themes/');
define('DIR_CONFIG', 'config/');
define('DIR_EDITOR', 'tinymce/');
define('DIR_BACKUP', 'backup/');

define('LOG_ALTER', 1);
define('LOG_ERR_GENERAL', 2);
define('LOG_ERR_SECURITY', 3);
define('LOG_ERR_DB', 4);
define('LOG_ERR_RUNTIME', 5);

define('INPUT_TEXT', 'text');
define('INPUT_HIDDEN', 'hidden');
define('INPUT_TEXTAREA', 'textarea');
define('INPUT_CHECKBOX', 'checkbox');
define('INPUT_RADIO', 'radio');
define('INPUT_SELECT', 'select');
define('INPUT_FILE', 'file');
define('INPUT_SUBMIT', 'submit');
define('INPUT_CANCEL', 'cancel');
define('INPUT_PASSWORD', 'password');
define('INPUT_CHECKBOX_MULTI', 'checkbox_multi');
define('INPUT_PLAINTEXT', 'plaintext');
define('INPUT_EDITOR', 'editor');
define('INPUT_COLOR', 'color');

define('LOGIN_SUCCESSFUL', 0);
define('LOGIN_FAILED', 1);
define('LOGIN_DISABLED', 2);
define('LOGIN_JUST_DISABLED', 3);
define('LOGIN_LOCKED', 4);
define('LOGIN_FAILED_NO_COOKIES', 5);

define('TYPE_STRING', 1);
define('TYPE_INTEGER', 2);
define('TYPE_FLOAT', 3);
define('TYPE_DATE', 4);
define('TYPE_TIME', 5);
define('TYPE_ARRAY', 6);

define('CHECK_TYPE', 1);
define('CHECK_ISSET', 2);
define('CHECK_NOTEMPTY', 3);
define('CHECK_INRANGE', 4);
define('CHECK_INSET', 5);
define('CHECK_INSET_SQL', 6);
define('CHECK_INSET_CI', 7);
define('CHECK_NOTINSET', 9);
define('CHECK_NOTINSET_SQL', 10);
define('CHECK_NOTINSET_CI', 11);
define('CHECK_PREG', 13);
define('CHECK_MULTILANG', 14);
define('CHECK_EMAIL', 15);
define('CHECK_FILE_EXTENSION', 16);
define('CHECK_FILE_SIZE', 17);
define('CHECK_EQUAL', 18);
define('CHECK_FILE_EXISTS', 19);
define('CHECK_URL', 20);
define('CHECK_UNEQUAL', 21);

define('XML_VALUE', 1);
define('XML_ATTRIBUTE', 2);
define('XML_NODE', 3);
define('XML_TEXT', 4);

define('URL_REWRITE_NONE', 0);
define('URL_REWRITE_MOD', 1);

define('MSG_ERROR', 'ERROR');
define('MSG_SUCCESS', 'SUCCESS');
define('MSG_QUESTION', 'QUESTION');
define('MSG_WARNING', 'WARNING');
define('MSG_NOTICE', 'NOTICE');
define('MSG_INFO', 'INFO');

define('TAG_OPENING', 0);
define('TAG_CLOSING', 1);
define('TAG_SELFCONTAINED', 2);

define('SQL_QUERY_SELECT', 1);
define('SQL_QUERY_SELECT_DISTINCT', 2);
define('SQL_QUERY_INSERT', 3);
define('SQL_QUERY_UPDATE', 4);
define('SQL_QUERY_DELETE', 5);

define('CONSTRAINT_PRIMARY', 1);
define('CONSTRAINT_SECONDARY', 2);
define('CONSTRAINT_ORDER', 4);

define('CALLBACK_PLACEHOLDER', 'callback_placeholder');

define('DIALOG_STEPS_DISABLE', 1);
define('DIALOG_STEPS_ENABLE', 2);

define('TIME_TYPE_USER', 1);
define('TIME_TYPE_DB', 2);
define('TIME_TYPE_SERVER', 3);

define('UB_COMPRESS_NONE', 1);
define('UB_COMPRESS_ZIP', 2);
define('UB_COMPRESS_BZ2', 3);

?>