<?php

namespace App\Exports;

use App\Models\MassMailerContact;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MassMailerContactsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;
    protected $tagId;

    public function __construct($search = null, $tagId = null)
    {
        $this->search = $search;
        $this->tagId = $tagId;
    }

    public function collection()
    {
        $query = MassMailerContact::with('tags');

        if ($this->search) {
            $search = strtolower($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(firstname) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(lastname) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($this->tagId) {
            $query->whereHas('tags', function ($q) {
                $q->where('mass_mailer_tags.id', $this->tagId);
            });
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Company',
            'Position',
            'City',
            'Country',
            'Tags'
        ];
    }

    public function map($contact): array
    {
        return [
            $contact->id,
            $contact->firstname,
            $contact->lastname,
            $contact->email,
            $contact->phone,
            $contact->company,
            $contact->position,
            $contact->city,
            $contact->country,
            $contact->tags->pluck('title')->implode(', '),
        ];
    }
}
