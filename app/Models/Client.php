<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'dob', 'address',
        'status', 'lead_source_id', 'user_id', 'notes',
        'policy_no', 'company', 'login_date', 'anp',
        'suburb', 'city', 'post_code', 'adviser', 'not_counting',
        'compliance_by', 'roa_due_date', 'status_compliance',
        'sent_to_client', 'outcome',
        'inforce_date', 'inforce_request_type', 'inforce_process_status',
        'inforce_process_by', 'inforce_outcome', 'inforce_comments', 'inforce_finished_date',
        'claim_type', 'claim_admin', 'claim_processed_date',
        'claim_update_status',        'claim_approved_date',
        'claim_result',
        'canc_date_sent',
        'canc_completed_date',
        'canc_outcome',
        'canc_comments',
        'canc_admin',
        'npw_issue_date',
        'npw_premium',
        'npw_premium_mode',
        'npw_admin',
        'npw_pending',
        'npw_comments',
    ];

    protected $casts = [
        'dob' => 'date',
        'login_date' => 'date',
        'roa_due_date' => 'date',
        'not_counting' => 'boolean',
        'inforce_date' => 'date',
        'inforce_finished_date' => 'date',
        'claim_processed_date' => 'date',
        'claim_approved_date' => 'date',
        'canc_date_sent' => 'date',
        'canc_completed_date' => 'date',
        'npw_issue_date' => 'date',
    ];

    public function scopeLoginClients($query)
    {
         return $query->whereIn('status', ['Login Client', 'login', 'Login']);
    }

    public function scopeInforce($query)
    {
        return $query->where('status', 'Inforce');
    }

    public function scopeValidNames($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('first_name')->orWhere('first_name', 'not like', '%#VALUE!%');
        })->where(function ($q) {
            $q->whereNull('last_name')->orWhere('last_name', 'not like', '%#VALUE!%');
        });
    }

    public function policies()
    {
        return $this->hasMany(Policy::class);
    }

    public function serviceRequests()
    {
        return $this->hasMany(ClientServiceRequest::class);
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
