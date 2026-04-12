<?php

namespace Database\Factories;

use App\Models\Appointment;
use Faker\Factory as FakerFactory;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Encounter>
 */
class EncounterFactory extends Factory
{
    protected $model = Encounter::class;

    public function definition(): array
    {
        $faker = FakerFactory::create();
        return [
            'practice_id'       => Practice::factory(),
            'patient_id'        => Patient::factory(),
            'appointment_id'    => Appointment::factory(),
            'practitioner_id'   => Practitioner::factory(),
            'status'            => 'draft',
            'visit_date'        => $faker->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'chief_complaint'   => null,
            'subjective'        => null,
            'objective'         => null,
            'assessment'        => null,
            'plan'              => null,
            'visit_notes'       => null,
            'completed_on'      => null,
        ];
    }

    public function complete(): static
    {
        $faker = FakerFactory::create();

        $cases = [
            [
                'chief_complaint' => 'Chronic lower back pain (6/10), worse with prolonged sitting',
                'subjective' => 'Patient reports pain has been present for 3 months following a minor lifting injury at work. Sleep is disrupted — waking 2–3 times per night. Ibuprofen provides temporary relief but symptoms return within 4–6 hours.',
                'objective' => 'Palpation reveals tenderness at L3–L5 paraspinals bilaterally. Restricted lumbar flexion (40° vs normal 60°). Straight leg raise negative bilaterally. Muscle guarding noted in left QL.',
                'assessment' => 'Lumbar strain with secondary muscle guarding and sleep disruption. Responding positively to treatment.',
                'plan' => 'Continue weekly sessions × 4 weeks. Home exercise program provided (cat-cow, pelvic tilts). Advised on ergonomic adjustments for workstation. Follow up to reassess range of motion.',
            ],
            [
                'chief_complaint' => 'Tension headaches 3–4×/week, rated 5/10 at peak',
                'subjective' => 'Patient has had recurrent tension headaches for 6 weeks, predominantly occipital and bilateral temporal. Onset coincides with increased work stress and long hours at computer screen. Paracetamol helps but patient prefers to reduce medication reliance.',
                'objective' => 'Suboccipital muscle tightness on palpation. Forward head posture noted. Restricted cervical rotation (L > R). No visual disturbances or neurological signs reported.',
                'assessment' => 'Cervicogenic tension headaches secondary to postural strain and stress. Good candidate for manual therapy and acupressure.',
                'plan' => 'Address suboccipital release and cervical mobility. Recommend ergonomic screen positioning and hourly stretch breaks. Review in 2 weeks.',
            ],
            [
                'chief_complaint' => 'Insomnia — difficulty falling asleep and early waking (3–4 am)',
                'subjective' => 'Patient reports 3–4 hours of disrupted sleep per night for the past 2 months. Associated with increased work anxiety. Energy levels poor; afternoon fatigue severe. No current sleep aids. Partner reports patient is restless.',
                'objective' => 'Tongue pale with slight coating. Pulse thin and slightly rapid. Mild dark circles. Patient appears fatigued.',
                'assessment' => 'Sleep disruption associated with anxiety and stress. Improving — patient reports 5–6 hours last week vs 3–4 hours at intake.',
                'plan' => 'Continue calming protocol. Lifestyle advice given: no screens after 9 pm, consistent wake time. Herbal sleep support discussed. Reassess in 3 weeks.',
            ],
            [
                'chief_complaint' => 'Right shoulder impingement — pain 7/10 with overhead reaching',
                'subjective' => 'Acute onset 3 weeks ago after painting ceiling at home. Sharp pain with abduction beyond 90° and internal rotation. Aching at rest at night. No previous shoulder injury. Patient is right-hand dominant.',
                'objective' => 'Positive Hawkins-Kennedy test (R). Painful arc 80–120° abduction. Supraspinatus tenderness on palpation. Full passive ROM intact. Grip strength symmetric.',
                'assessment' => 'Right rotator cuff impingement syndrome, likely supraspinatus involvement. No signs of full-thickness tear.',
                'plan' => 'Avoid overhead activities and heavy lifting for 2 weeks. Targeted soft tissue work and mobilisation of glenohumeral joint. Home exercises: pendulum swings, external rotation band work. Reassess at next visit.',
            ],
            [
                'chief_complaint' => 'Knee pain — right medial joint line, 4/10 at rest, 7/10 with stairs',
                'subjective' => 'Gradual onset over 3 months. No specific injury. Patient is a recreational runner (30 km/week). Pain worsens with downhill running and prolonged sitting. No swelling or locking reported. Previously tried rest — symptoms returned on resuming activity.',
                'objective' => 'Mild joint line tenderness medially. McMurray\'s test equivocal. Valgus stress test negative. Quads and glutes weak on single-leg squat. No effusion palpable.',
                'assessment' => 'Likely medial compartment irritation with contributing hip and quad weakness. Possible early meniscal wear pattern.',
                'plan' => 'Reduced running volume by 50%. Strengthening protocol: glute bridges, step-downs, terminal knee extensions. Manual therapy to IT band and hip flexors. Review in 3 weeks.',
            ],
            [
                'chief_complaint' => 'Fatigue, low energy, afternoon crash',
                'subjective' => 'Patient reports persistent fatigue for 4 months. Energy adequate in morning but crashes significantly after lunch. Difficulty concentrating in afternoon. Sleep is 7 hours but unrefreshing. Appetite variable.',
                'objective' => 'Blood pressure within normal range. Tongue slightly pale. Pulse weak on left chi position. No obvious lymphadenopathy. Patient appears well but tired.',
                'assessment' => 'Functional fatigue pattern with possible Spleen Qi deficiency component. Ruled out acute illness. Recommend thyroid and CBC review with GP if no improvement in 4 weeks.',
                'plan' => 'Dietary advice provided: regular meals, reduce cold/raw foods, increase warming proteins. Stress management techniques introduced. Continuation of supportive treatment protocol.',
            ],
            [
                'chief_complaint' => 'Anxiety, shallow breathing, difficulty unwinding',
                'subjective' => 'Patient experiences near-daily anxiety symptoms — racing thoughts, shallow breathing, tight chest, and difficulty winding down after work. No panic attacks. Sleep onset delayed (45–60 min). Has not tried medication.',
                'objective' => 'Breathing pattern: upper-chest dominant, rate ~18–20/min. Tense upper trapezius and scalene muscles bilaterally. Heart rate 82 bpm resting.',
                'assessment' => 'Anxiety with somatic presentation. Breathing retraining and vagal nerve stimulation strategies are appropriate. Responding well to treatment — reports feeling calmer post-session.',
                'plan' => 'Teach diaphragmatic breathing (4-7-8 technique). Progressive muscle relaxation handout provided. Continue current treatment frequency. Refer to counsellor if symptoms persist beyond 6 weeks.',
            ],
        ];

        $case = $faker->randomElement($cases);

        return $this->state(fn (array $attributes) => [
            'status'          => 'complete',
            'chief_complaint' => $case['chief_complaint'],
            'subjective'      => $case['subjective'],
            'objective'       => $case['objective'],
            'assessment'      => $case['assessment'],
            'plan'            => $case['plan'],
            'completed_on'    => $faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'completed_on' => null]);
    }
}
