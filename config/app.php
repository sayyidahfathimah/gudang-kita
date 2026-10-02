<?php
return [
 'name' => envv('APP_NAME','Gudang Kita'),
 'debug' => filter_var(envv('APP_DEBUG',false), FILTER_VALIDATE_BOOL),
 'timezone' => envv('APP_TIMEZONE','Asia/Jakarta'),
];
