<?php

namespace XcentricItFoundation\LaravelCrudController\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use XcentricItFoundation\LaravelCrudController\Actions\Crud\CrudActionPayload;

class CrudModelAfterAddRelation
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public CrudActionPayload $actionPayload)
    {
        //
    }
}
