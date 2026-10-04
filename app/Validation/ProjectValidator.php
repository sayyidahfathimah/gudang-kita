<?php

namespace App\Validation;

final class ProjectValidator
{
    public static function validate(array $d): array
    {
        $e = Validator::required($d, ['name','description','status','start_date','target_date']);
        if (isset($d['status']) && !Validator::enum($d['status'], ['Planning','Active','Completed','Archived'])) {
            $e['status'] = 'Status project tidak valid.';
        }
        if (!empty($d['start_date']) && !empty($d['target_date']) && !Validator::dateOrder($d['start_date'], $d['target_date'])) {
            $e['target_date'] = 'Tanggal target tidak boleh lebih awal dari tanggal mulai.';
        }
        return $e;
    }
}
