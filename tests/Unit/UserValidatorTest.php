<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
use App\Validation\UserValidator;
final class UserValidatorTest extends TestCase {
 public function testInvalidEmailIsRejected():void{$e=UserValidator::validate(['username'=>'member1','name'=>'Member','email'=>'not-email','role'=>'Member','is_active'=>1,'password'=>'x']);$this->assertArrayHasKey('email',$e);}
 public function testOnlyAdminOrMemberRolesAreAllowed():void{$e=UserValidator::validate(['username'=>'member1','name'=>'Member','email'=>'a@example.test','role'=>'Manager','is_active'=>1,'password'=>'x']);$this->assertArrayHasKey('role',$e);}
}
