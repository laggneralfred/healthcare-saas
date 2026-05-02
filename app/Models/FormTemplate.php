<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    use BelongsToPractice, HasFactory;

    public const TYPE_INTAKE = 'intake';
    public const TYPE_CONSENT = 'consent';
    public const TYPE_HEALTH_HISTORY = 'health_history';
    public const TYPE_MASSAGE_INTAKE = 'massage_intake';
    public const TYPE_FIVE_ELEMENT_INTAKE = 'five_element_intake';
    public const TYPE_PRIVACY_ACKNOWLEDGEMENT = 'privacy_acknowledgement';

    public const DEFAULT_NEW_PATIENT_INTAKE_NAME = 'New Patient Intake';

    protected $fillable = [
        'practice_id',
        'name',
        'description',
        'type',
        'schema_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function findOrCreateDefaultNewPatientIntake(int $practiceId): self
    {
        $template = self::withoutPracticeScope()->firstOrCreate(
            [
                'practice_id' => $practiceId,
                'name' => self::DEFAULT_NEW_PATIENT_INTAKE_NAME,
                'type' => self::TYPE_INTAKE,
            ],
            [
                'description' => 'A simple intake form for prospective new patients.',
                'schema_json' => self::defaultNewPatientIntakeSchema(),
                'is_active' => true,
            ],
        );

        if (! $template->is_active) {
            $template->update(['is_active' => true]);
        }

        return $template;
    }

    public static function defaultNewPatientIntakeSchema(): array
    {
        return [
            'fields' => [
                ['name' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date', 'required' => false],
                ['name' => 'main_concern', 'label' => 'Main reason for visit', 'type' => 'textarea', 'required' => true],
                ['name' => 'health_history', 'label' => 'Relevant health history', 'type' => 'textarea', 'required' => false],
                ['name' => 'current_medications', 'label' => 'Current medications', 'type' => 'textarea', 'required' => false],
                ['name' => 'consent_to_contact', 'label' => 'I agree the clinic may contact me about my request', 'type' => 'checkbox', 'required' => true],
            ],
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
