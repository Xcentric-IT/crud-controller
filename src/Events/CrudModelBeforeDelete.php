<?php

namespace XcentricItFoundation\LaravelCrudController\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;

class CrudModelBeforeDelete
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public ActionPayloadInterface $actionPayload)
    {
        //
    }
}
