<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
use App\Validation\ProjectValidator;
final class ProjectValidatorTest extends TestCase {
 public function testTargetDateCannotBeBeforeStartDate():void{$e=ProjectValidator::validate(['name'=>'X','description'=>'D','status'=>'Planning','start_date'=>'2026-10-10','target_date'=>'2026-10-01']);$this->assertArrayHasKey('target_date',$e);}
 public function testValidProjectDatePasses():void{$e=ProjectValidator::validate(['name'=>'X','description'=>'D','status'=>'Active','start_date'=>'2026-10-01','target_date'=>'2026-10-10']);$this->assertArrayNotHasKey('target_date',$e);}
 public function testInvalidProjectStatusIsRejected():void{$e=ProjectValidator::validate(['name'=>'X','description'=>'D','status'=>'Unknown','start_date'=>'2026-10-01','target_date'=>'2026-10-10']);$this->assertArrayHasKey('status',$e);}
 public function testRequiredProjectFieldsAreValidated():void{$e=ProjectValidator::validate(['name'=>'','description'=>'','status'=>'','start_date'=>'','target_date'=>'']);$this->assertCount(5,$e);}
}
