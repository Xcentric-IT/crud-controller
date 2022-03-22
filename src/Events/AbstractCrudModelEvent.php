<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;

abstract class AbstractCrudModelEvent
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
