<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use App\Actions\ActionPayloadInterface;
use App\Actions\ActionResponse;
use App\Actions\ExecutableAction;
use App\Actions\ExecutableActionResponseContract;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class TransactionableAction implements ExecutableAction
{
    /**
     * @param ActionPayloadInterface $actionPayload
     * @return ExecutableActionResponseContract
     */
    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        DB::beginTransaction();
        try {
            $result = $this->doRun($actionPayload);
            DB::commit();
        } catch (Throwable $t) {
            DB::rollBack();
            throw $t;
        }

        return new ActionResponse($result);
    }

    /**
     * @param ActionPayloadInterface $actionPayload
     * @return bool
     */
    abstract protected function doRun(ActionPayloadInterface $actionPayload): bool;
}
