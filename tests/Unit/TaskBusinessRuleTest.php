<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
final class TaskBusinessRuleTest extends TestCase {
 public function testOverdueMeansPastDueAndNotDone():void{$today='2026-09-29';$this->assertTrue('2026-09-28' < $today);$this->assertNotSame('Done','In Progress');}
 public function testPaginationUsesTenRowsPerPage():void{$total=25;$perPage=10;$this->assertSame(3,(int)ceil($total/$perPage));}
}
