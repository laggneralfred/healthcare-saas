<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use App\Models\Traits\HasAuditLog;
use App\Traits\HasAccessToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntakeSubmission extends Model
{
    use HasFactory, HasAccessToken, BelongsToPractice, HasAuditLog;

    protected $fillable = [
        'practice_id', 'patient_id', 'appointment_id',
        'status', 'submitted_on', 'access_token',
        'reason_for_visit', 'current_concerns', 'relevant_history',
        'medications', 'notes', 'summary_text',

        // Health history
        'discipline', 'chief_complaint', 'onset_duration', 'onset_type',
        'aggravating_factors', 'relieving_factors', 'pain_scale',
        'previous_episodes', 'previous_episodes_description',
        'current_medications', 'allergies', 'past_diagnoses', 'past_surgeries',
        'is_pregnant', 'has_pacemaker', 'takes_blood_thinners',
        'has_bleeding_disorder', 'has_infectious_disease',
        'exercise_frequency', 'sleep_quality', 'sleep_hours',
        'stress_level', 'diet_description', 'smoking_status', 'smoking_amount',
        'alcohol_use', 'had_previous_treatment', 'previous_treatments_tried',
        'previous_treatment_results', 'other_practitioner', 'other_practitioner_name',
        'treatment_goals', 'success_indicators', 'discipline_responses',
        'consent_given', 'consent_signed_at', 'consent_signed_by', 'consent_ip_address',
    ];

    protected function casts(): array
    {
        return [
            'submitted_on'             => 'datetime',
            'consent_signed_at'        => 'datetime',
            'current_medications'      => 'array',
            'allergies'                => 'array',
            'past_diagnoses'           => 'array',
            'past_surgeries'           => 'array',
            'previous_treatments_tried' => 'array',
            'discipline_responses'     => 'array',
            'previous_episodes'        => 'boolean',
            'is_pregnant'              => 'boolean',
            'has_pacemaker'            => 'boolean',
            'takes_blood_thinners'     => 'boolean',
            'has_bleeding_disorder'    => 'boolean',
            'has_infectious_disease'   => 'boolean',
            'had_previous_treatment'   => 'boolean',
            'other_practitioner'       => 'boolean',
            'consent_given'            => 'boolean',
            'pain_scale'               => 'integer',
            'sleep_hours'              => 'integer',
        ];
    }

    // ── Booted ────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (IntakeSubmission $submission) {
            if ($submission->consent_given && ! $submission->consent_signed_at) {
                $submission->consent_signed_at = now();
            }
        });
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getPainScaleLabelAttribute(): ?string
    {
        if ($this->pain_scale === null) {
            return null;
        }

        return match (true) {
            $this->pain_scale <= 3  => 'Mild',
            $this->pain_scale <= 6  => 'Moderate',
            $this->pain_scale <= 9  => 'Severe',
            $this->pain_scale >= 10 => 'Worst Possible',
            default                 => null,
        };
    }

    public function getOnsetTypeLabelAttribute(): ?string
    {
        return match ($this->onset_type) {
            'sudden'    => 'Sudden / Acute',
            'gradual'   => 'Gradual / Chronic',
            'recurring' => 'Recurring',
            default     => $this->onset_type,
        };
    }

    public function getDisciplineLabelAttribute(): ?string
    {
        return match ($this->discipline) {
            'acupuncture'    => 'Acupuncture',
            'massage'        => 'Massage Therapy',
            'chiropractic'   => 'Chiropractic',
            'physiotherapy'  => 'Physiotherapy',
            'general'        => 'General',
            default          => $this->discipline,
        };
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function hasRedFlags(): bool
    {
        return $this->is_pregnant
            || $this->has_pacemaker
            || $this->takes_blood_thinners
            || $this->has_bleeding_disorder
            || $this->has_infectious_disease;
    }

    public function getDisciplineResponsesFor(string $key): mixed
    {
        return data_get($this->discipline_responses, $key);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForDiscipline(Builder $query, string $discipline): Builder
    {
        return $query->where('discipline', $discipline);
    }

    public function scopeWithRedFlags(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('is_pregnant', true)
                ->orWhere('has_pacemaker', true)
                ->orWhere('takes_blood_thinners', true)
                ->orWhere('has_bleeding_disorder', true)
                ->orWhere('has_infectious_disease', true);
        });
    }

    public function scopeWithConsent(Builder $query): Builder
    {
        return $query->where('consent_given', true);
    }

    public function scopePendingConsent(Builder $query): Builder
    {
        return $query->where('consent_given', false);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
