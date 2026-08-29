<?php

namespace App\Http\Controllers\MassMailer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\MassMailerTemplate;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = MassMailerTemplate::query();
        if ($request->has('search')) {
            $search = strtolower($request->search);
            $templates->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(subject) LIKE ?', ['%' . $search . '%']);
            });
        }
        $templates = $templates->latest()->paginate(20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.mass_mailer.templates.partials.table', compact('templates'))->render(),
                'pagination' => $templates->links()->toHtml(),
            ]);
        }

        return view('pages.mass_mailer.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('pages.mass_mailer.templates.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        MassMailerTemplate::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully!',
            'redirect_url' => route('mass-mailer.templates.index')
        ]);
    }

    public function edit($id)
    {
        $template = MassMailerTemplate::findOrFail($id);
        return view('pages.mass_mailer.templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = MassMailerTemplate::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        $template->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully!',
            'redirect_url' => route('mass-mailer.templates.index')
        ]);
    }

    public function destroy($id)
    {
        try {
            MassMailerTemplate::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Template deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
