<?php

namespace App\Http\Controllers\MassMailer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\MassMailerTag;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = MassMailerTag::query();
        if ($request->has('search')) {
            $tags->where('title', 'LIKE', '%' . $request->search . '%');
        }
        $tags = $tags->latest()->paginate(20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.mass_mailer.tags.partials.table', compact('tags'))->render(),
                'pagination' => $tags->links()->toHtml(),
            ]);
        }

        return view('pages.mass_mailer.tags.index', compact('tags'));
    }

    public function create()
    {
        return view('pages.mass_mailer.tags.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        MassMailerTag::create($request->only('title'));

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully!',
            'redirect_url' => route('mass-mailer.tags.index')
        ]);
    }

    public function edit($id)
    {
        $tag = MassMailerTag::findOrFail($id);
        return view('pages.mass_mailer.tags.edit', compact('tag'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        $tag = MassMailerTag::findOrFail($id);
        $tag->update($request->only('title'));

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully!',
            'redirect_url' => route('mass-mailer.tags.index')
        ]);
    }

    public function destroy($id)
    {
        try {
            MassMailerTag::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Tag deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
