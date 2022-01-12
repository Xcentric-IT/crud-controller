<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Support\Facades\DB;
use Throwable;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\Actions\ActionResponse;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableAction;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableActionResponseContract;

abstract class TransactionableAction implements ExecutableAction
{
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

    abstract protected function doRun(ActionPayloadInterface $actionPayload): bool;
}
