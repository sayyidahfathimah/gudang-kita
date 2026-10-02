<?php
namespace App\Security;
final class Auth {
 public static function login(array $user): void { session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id']; $_SESSION['username']=$user['username']; $_SESSION['name']=$user['name']; $_SESSION['role']=$user['role']; }
 public static function logout(): void { $_SESSION=[]; if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),' ',time()-42000,$p['path'],$p['domain']??'',(bool)$p['secure'],(bool)$p['httponly']); } session_destroy(); }
 public static function check(): bool { return isset($_SESSION['user_id']); }
 public static function id(): int { return (int)($_SESSION['user_id']??0); }
 public static function role(): string { return (string)($_SESSION['role']??''); }
 public static function user(): array { return ['id'=>self::id(),'username'=>$_SESSION['username']??'','name'=>$_SESSION['name']??'','role'=>self::role()]; }
 public static function require(): void { if (!self::check()) { redirect('login'); } }
 public static function requireAdmin(): void { self::require(); if (self::role()!=='Admin') { http_response_code(403); require BASE_PATH.'/views/403.php'; exit; } }
}
