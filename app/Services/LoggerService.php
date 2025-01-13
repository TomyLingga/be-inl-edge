<?php

namespace App\Services;

use App\Models\Log;
use App\Models\Master\Log as MasterLog;

class LoggerService
{
    public static function logAction($user, $model, $action, $oldData = null, $newData = null)
    {
        $oldDataJson = json_encode($oldData);
        $newDataJson = json_encode($newData);

        MasterLog::create([
            'user_id' => $user->sub,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'action' => $action,
            'old_data' => $oldDataJson,
            'new_data' => $newDataJson,
        ]);
    }
}
