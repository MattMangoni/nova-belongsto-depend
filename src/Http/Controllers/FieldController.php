<?php

namespace Orlyapps\NovaBelongsToDepend\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Orlyapps\NovaBelongsToDepend\NovaBelongsToDepend;
use \Illuminate\Http\Resources\MergeValue;

class FieldController extends Controller
{
    public function index(NovaRequest $request)
    {
        if (is_null($request->dependKey)) {
            abort(500, 'depend On Relationship not found on the Resource spefified for the Field "' . $request->attribute . '" Please check you have set correct /App/Nova/Resource');
        }

        $resource = new $request->resourceClass($request->resourceClass::newModel());

        // Create Nested Array Fields from Panels, Flatten and find
        $fields = $this->returnFields($resource->fields($request));

        $fields = $fields->filter(function ($value) use ($request) {
            return ($value instanceof NovaBelongsToDepend);
        });

        $field = $fields->first(function ($value, $key) use ($request) {
            return ($value instanceof NovaBelongsToDepend && $value->attribute == $request->attribute);
        });

        if (is_null($field)) {
            abort(500, 'Can not find the Field "' . $request->attribute . '" in the Model "' . $request->resourceClass . '"');
        }

        $model = $request->modelClass::find($request->dependKey);

        if (is_null($model)) {
            abort(500, 'Can not find the Model "' . $request->modelClass . '::find(' . $request->dependKey . ')');
        }

        $result = ($field->optionResolveCallback)($model);

        return $result;
    }
    
    public function returnFields($fields) {
        return collect($fields)->map(function ($field) {
            if (isset($field->data)) {
                return $this->returnFields($field->data);
            } elseif (isset($field->meta['fields'])) {
                return $this->returnFields($field->meta['fields']);
            }
            return $field;
        })->flatten();
    }
}
