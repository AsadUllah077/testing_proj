<?php

namespace App\Http\Controllers\MassMailer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\MassMailerContact;
use App\Models\MassMailerTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $contacts = MassMailerContact::with('tags');
        if ($request->has('search')) {
            $search = strtolower($request->search);
            $contacts->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(firstname) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(lastname) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(company) LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($request->has('tag_id') && $request->tag_id != '') {
            $contacts->whereHas('tags', function ($q) use ($request) {
                $q->where('mass_mailer_tags.id', $request->tag_id);
            });
        }

        $contacts = $contacts->latest()->paginate(20);
        $tags = MassMailerTag::all();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.mass_mailer.contacts.partials.table', compact('contacts'))->render(),
                'pagination' => $contacts->links()->toHtml(),
            ]);
        }

        return view('pages.mass_mailer.contacts.index', compact('contacts', 'tags'));
    }

    public function create()
    {
        $tags = MassMailerTag::all();
        return view('pages.mass_mailer.contacts.create', compact('tags'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:mass_mailer_contacts,email',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:mass_mailer_tags,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        try {
            DB::beginTransaction();
            $contact = MassMailerContact::create($request->except('tag_ids'));
            if ($request->has('tag_ids')) {
                $contact->tags()->sync($request->tag_ids);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contact created successfully!',
                'redirect_url' => route('mass-mailer.contacts.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contact store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $contact = MassMailerContact::with('tags')->findOrFail($id);
        $tags = MassMailerTag::all();
        return view('pages.mass_mailer.contacts.edit', compact('contact', 'tags'));
    }

    public function update(Request $request, $id)
    {
        $contact = MassMailerContact::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:mass_mailer_contacts,email,' . $id,
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:mass_mailer_tags,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        try {
            DB::beginTransaction();
            $contact->update($request->except('tag_ids'));
            if ($request->has('tag_ids')) {
                $contact->tags()->sync($request->tag_ids);
            } else {
                $contact->tags()->detach();
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contact updated successfully!',
                'redirect_url' => route('mass-mailer.contacts.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contact update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $contact = MassMailerContact::findOrFail($id);
            $contact->delete();
            return response()->json(['success' => true, 'message' => 'Contact deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
