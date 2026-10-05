<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
use App\Validation\UserValidator;
final class UserValidatorTest extends TestCase {
 public function testInvalidEmailIsRejected():void{$e=UserValidator::validate(['username'=>'member1','name'=>'Sales','email'=>'not-email','role'=>'Sales','is_active'=>1,'password'=>'x']);$this->assertArrayHasKey('email',$e);}
 public function testOnlyConfiguredRolesAreAllowed():void{$e=UserValidator::validate(['username'=>'member1','name'=>'Sales','email'=>'a@example.test','role'=>'Manager','is_active'=>1,'password'=>'x']);$this->assertArrayHasKey('role',$e);}
 public function testInvalidUsernameAndRequiredFieldsAreRejected():void{$e=UserValidator::validate(['username'=>'x!','name'=>'','email'=>'','role'=>'','is_active'=>1]);$this->assertArrayHasKey('username',$e);$this->assertArrayHasKey('name',$e);$this->assertArrayHasKey('role',$e);}
 public function testWarehouseStaffIsAllowed():void{$e=UserValidator::validate(['username'=>'warehouse1','name'=>'Warehouse','email'=>'warehouse@example.test','role'=>'WarehouseStaff','is_active'=>1]);$this->assertSame([],$e);}
}
