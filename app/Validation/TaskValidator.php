<?php

namespace App\Validation;

final class TaskValidator
{
    public static function validate(array $d, ?array $project = null): array
    {
        $e = Validator::required($d, ['project_id','title','description','assignee_id','status','priority','due_date']);
        if (isset($d['status']) && !Validator::enum($d['status'], ['To Do','In Progress','Done'])) {
            $e['status'] = 'Status task tidak valid.';
        }
        if (isset($d['priority']) && !Validator::enum($d['priority'], ['Low','Medium','High'])) {
            $e['priority'] = 'Priority task tidak valid.';
        }
        if ($project && !empty($d['due_date']) && ($d['due_date'] < $project['start_date'] || $d['due_date'] > $project['target_date'])) {
            $e['due_date'] = 'Due date harus berada dalam rentang tanggal project.';
        }
        return $e;
    }
}
