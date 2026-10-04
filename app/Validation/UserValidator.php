<?php
namespace App\Validation;
final class UserValidator {
 public static function validate(array $d): array {
  $e=Validator::required($d,['username','name','role']);
  if (!empty($d['username']) && !preg_match('/^[A-Za-z0-9._-]{4,50}$/',$d['username'])) $e['username']='Username 4-50 karakter dan hanya boleh huruf, angka, titik, underscore, dash.';
  if (!empty($d['role'])&&!Validator::enum($d['role'],['Admin','WarehouseStaff','Member'])) $e['role']='Role tidak valid.';
  if (isset($d['email']) && $d['email']!=='' && !Validator::email($d['email'])) $e['email']='Format email tidak valid.';
  return $e;
 }
}
