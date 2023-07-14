<?php

namespace Outl1ne\NovaSimpleRepeatable;

use Laravel\Nova\Http\Controllers\CreationFieldSyncController as BaseCreationFieldSyncController;
use Laravel\Nova\Http\Requests\ResourceCreateOrAttachRequest;
use Laravel\Nova\Http\Resources\CreateViewResource;
use Laravel\Nova\Http\Resources\ReplicateViewResource;

class CreationFieldSyncController extends BaseCreationFieldSyncController
{
    public function __invoke(ResourceCreateOrAttachRequest $request)
    {
        $resource = $request->has('fromResourceId')
            ? ReplicateViewResource::make($request->fromResourceId)->newResourceWith($request)
            : CreateViewResource::make()->newResourceWith($request);
        $field = $request->query('field');
        if (str_contains($field, '---')) {
            [$repeatableField, $field] = explode('---', $field);
            $repeatableField = $resource->creationFields($request)
                ->filter(function ($field) use ($repeatableField, $request) {
                    return $repeatableField === $field->attribute;
                })->firstOrFail();
            foreach ($request->all() as $key => $value) {
                if (str_contains($key, '---')) {
                    $key = explode('---', $key)[1] ?? $key;
                    $request->merge([
                        "$key" => $value,
                    ]);
                }

            }
            $sync_depends_on = $repeatableField->fields->findFieldByAttribute($field)->syncDependsOn($request);
            $sync_depends_on->attribute = $request->query('field');
            $sync_depends_on->dependentComponentKey = $request->query('component');

            return response()->json($sync_depends_on);
        }

        return response()->json(
            $resource->creationFields($request)
                ->filter(function ($field) use ($request) {
                    return $request->query('field') === $field->attribute &&
                        $request->query('component') === $field->dependentComponentKey();
                })->each->syncDependsOn($request)
                ->first()
        );
    }
}
