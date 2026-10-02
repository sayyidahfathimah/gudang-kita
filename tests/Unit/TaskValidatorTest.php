<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
use App\Validation\TaskValidator;
final class TaskValidatorTest extends TestCase {
 private function project():array{return ['start_date'=>'2026-10-01','target_date'=>'2026-10-31'];}
 public function testDueDateMustBeInsideProjectRange():void{$e=TaskValidator::validate(['project_id'=>1,'title'=>'T','description'=>'D','assignee_id'=>2,'status'=>'To Do','priority'=>'Medium','due_date'=>'2026-11-01'],$this->project());$this->assertArrayHasKey('due_date',$e);}
 public function testInvalidTaskStatusIsRejected():void{$e=TaskValidator::validate(['project_id'=>1,'title'=>'T','description'=>'D','assignee_id'=>2,'status'=>'Blocked','priority'=>'Medium','due_date'=>'2026-10-10'],$this->project());$this->assertArrayHasKey('status',$e);}
}
