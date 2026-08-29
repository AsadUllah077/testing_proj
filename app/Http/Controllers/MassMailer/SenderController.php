<?php

namespace App\Http\Controllers\MassMailer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\MassMailerCampaign;
use App\Models\MassMailerContact;
use App\Models\MassMailerTag;
use App\Models\MassMailerTemplate;
use App\Jobs\SendMassEmailJob;
use Illuminate\Support\Facades\Validator;

class SenderController extends Controller
{
    public function create()
    {
        $tags = MassMailerTag::withCount(['contacts'])->get(['*']);
        $templates = MassMailerTemplate::all();
        return view('pages.mass_mailer.sender.create', compact('tags', 'templates'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_id' => 'required|exists:mass_mailer_tags,id',
            'template_id' => 'required|exists:mass_mailer_templates,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        $tag = MassMailerTag::with('contacts')->findOrFail($request->tag_id);
        $contact_ids = $tag->contacts->pluck('id')->toArray();

        if (empty($contact_ids)) {
            return response()->json(['success' => false, 'message' => 'No contacts found for the selected tag.']);
        }

        $campaign = MassMailerCampaign::create([
            'template_id' => $request->template_id,
            'subject' => $request->subject,
            'body' => $request->body,
            'status' => 'pending',
            'total_contacts' => count($contact_ids),
        ]);

        SendMassEmailJob::dispatch($campaign, $contact_ids);

        return response()->json([
            'success' => true,
            'message' => 'Campaign started!',
            'campaign_id' => $campaign->id,
            'redirect_url' => route('mass-mailer.dashboard')
        ]);
    }

    public function progress($id)
    {
        $campaign = MassMailerCampaign::findOrFail($id, ['*']);
        return response()->json([
            'status' => $campaign->status,
            'total' => $campaign->total_contacts,
            'sent' => $campaign->sent_contacts,
            'failed' => $campaign->failed_contacts,
            'percentage' => $campaign->total_contacts > 0 ? round(($campaign->sent_contacts / $campaign->total_contacts) * 100) : 0,
        ]);
    }
}
