<?php

namespace Outl1ne\NovaSimpleRepeatable;

use Laravel\Nova\Http\Controllers\UpdateFieldController as BaseUpdateFieldController;
use Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest;
use Laravel\Nova\Http\Resources\UpdateViewResource;

class UpdateFieldController extends BaseUpdateFieldController
{
    /**
     * List the update fields for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ResourceUpdateOrUpdateAttachedRequest $request)
    {
        return UpdateViewResource::make()->toResponse($request);
    }

    /**
     * Synchronize the field for updating.
     *
     * @param  \Laravel\Nova\Http\Requests\ResourceUpdateOrUpdateAttachedRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(ResourceUpdateOrUpdateAttachedRequest $request)
    {
        $resource = UpdateViewResource::make()->newResourceWith($request);

        $field = $request->query('field');
        if (str_contains($field, '---')) {
            [$repeatableField, $field] = explode('---', $field);
            $repeatableField = $resource->updateFields($request)
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
            $resource->updateFields($request)
                ->filter(function ($field) use ($request) {
                    return $request->query('field') === $field->attribute &&
                            $request->query('component') === $field->dependentComponentKey();
                })->each->syncDependsOn($request)
                ->first()
        );
    }
}
