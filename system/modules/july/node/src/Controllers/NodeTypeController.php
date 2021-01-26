<?php

namespace July\Node\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use July\Node\NodeField;
use July\Node\NodeType;

class NodeTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('node::node_type.index', [
            'nodeTypes' => NodeType::index(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = [
            'model' => NodeType::template(),
            'mold_fields' => [],
            'all_fields' => $this->fieldsToArray(NodeField::all()),
            'langcode' => langcode('content'),
        ];

        // dd($data);

        return view('node_type.create_edit', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \July\Node\NodeType  $nodeType
     * @return \Illuminate\Http\Response
     */
    public function edit(NodeType $nodeType)
    {
        $data = [
            'model' => $nodeType->gather(),
            'mold_fields' => $this->fieldsToArray($nodeType->fields),
            'all_fields' => $this->fieldsToArray(NodeField::all()),
            'langcode' => langcode('content'),
        ];

        return view('node_type.create_edit', $data);
    }

    /**
     * 获取所有字段
     *
     * @param  \Illuminate\Database\Eloquent\Collection|\July\Node\NodeField[]
     * @return array
     */
    protected function fieldsToArray(Collection $fields)
    {
        $keys = array_keys(NodeField::template());
        return $fields->map(function(NodeField $field) use($keys) {
            return $field->gather($keys);
        })->keyBy('id')->all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $nodeType = NodeType::make($request->all());
        $nodeType->save();
        $nodeType->updateRelatedFields($request->input('fields', []));
        return response('');
    }

    /**
     * Display the specified resource.
     *
     * @param  \July\Node\NodeType  $nodeType
     * @return \Illuminate\Http\Response
     */
    public function show(NodeType $nodeType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \July\Node\NodeType  $nodeType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, NodeType $nodeType)
    {
        $nodeType->update($request->all());
        $nodeType->updateRelatedFields($request->input('fields', []));
        return response('');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \July\Node\NodeType  $nodeType
     * @return \Illuminate\Http\Response
     */
    public function destroy(NodeType $nodeType)
    {
        $nodeType->fields()->detach();
        $nodeType->delete();
        return response('');
    }

    /**
     * 检查主键是否重复
     *
     * @param  string|int  $id
     * @return \Illuminate\Http\Response
     */
    public function exists($id)
    {
        return response([
            'exists' => !empty(NodeType::find($id)),
        ]);
    }
}
