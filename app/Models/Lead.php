<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'estimated_cover',
        'lead_source_id',
        'status',
        'user_id',
        'notes',
        'policy_no',
        'company',
        'login_date',
        'anp',
        'dob',
        'address',
        'suburb',
        'city',
        'post_code',
        'adviser',
        'not_counting',
        'compliance_by',
        'roa_due_date',
        'status_compliance',
        'sent_to_client',
        'outcome',
    ];
    

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
