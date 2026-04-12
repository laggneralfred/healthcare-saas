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
            'practice_id'    => Practice::factory(),
            'patient_id'     => Patient::factory(),
            'appointment_id' => Appointment::factory(),
            'practitioner_id' => Practitioner::factory(),
            'status'         => 'draft',
            'visit_date'     => $faker->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'visit_notes'    => null,
            'completed_on'   => null,
        ];
    }

    public function complete(): static
    {
        $faker = FakerFactory::create();

        $notes = [
            "Chief Complaint: Chronic lower back pain (6/10), worse with prolonged sitting.\n\nSubjective: Patient reports pain has been present for 3 months following a minor lifting injury at work. Sleep is disrupted — waking 2–3 times per night. Ibuprofen provides temporary relief but symptoms return within 4–6 hours.\n\nObjective: Palpation reveals tenderness at L3–L5 paraspinals bilaterally. Restricted lumbar flexion (40° vs normal 60°). Straight leg raise negative bilaterally. Muscle guarding noted in left QL.\n\nAssessment: Lumbar strain with secondary muscle guarding and sleep disruption. Responding positively to treatment.\n\nPlan: Continue weekly sessions × 4 weeks. Home exercise program provided (cat-cow, pelvic tilts). Advised on ergonomic adjustments for workstation. Follow up to reassess range of motion.",

            "Chief Complaint: Tension headaches 3–4×/week, rated 5/10 at peak.\n\nSubjective: Patient has had recurrent tension headaches for 6 weeks, predominantly occipital and bilateral temporal. Onset coincides with increased work stress and long hours at a computer screen. Paracetamol helps but patient prefers to reduce medication reliance.\n\nObjective: Suboccipital muscle tightness on palpation. Forward head posture noted. Restricted cervical rotation (L > R). No visual disturbances or neurological signs reported.\n\nAssessment: Cervicogenic tension headaches secondary to postural strain and stress. Good candidate for manual therapy and acupressure.\n\nPlan: Address suboccipital release and cervical mobility. Recommend ergonomic screen positioning and hourly stretch breaks. Review in 2 weeks.",

            "Chief Complaint: Insomnia — difficulty falling asleep and early waking (3–4 am).\n\nSubjective: Patient reports 3–4 hours of disrupted sleep per night for the past 2 months. Associated with increased work anxiety. Energy levels poor; afternoon fatigue severe. No current sleep aids. Partner reports patient is restless during the night.\n\nObjective: Tongue pale with slight coating. Pulse thin and slightly rapid. Mild dark circles. Patient appears fatigued.\n\nAssessment: Sleep disruption associated with anxiety and stress. Improving — patient reports 5–6 hours last week vs 3–4 hours at intake.\n\nPlan: Continue calming protocol. Lifestyle advice given: no screens after 9 pm, consistent wake time. Herbal sleep support discussed. Reassess in 3 weeks.",

            "Chief Complaint: Right shoulder impingement — pain 7/10 with overhead reaching.\n\nSubjective: Acute onset 3 weeks ago after painting ceiling at home. Sharp pain with abduction beyond 90° and internal rotation. Aching at rest at night. No previous shoulder injury. Patient is right-hand dominant.\n\nObjective: Positive Hawkins-Kennedy test (R). Painful arc 80–120° abduction. Supraspinatus tenderness on palpation. Full passive ROM intact. Grip strength symmetric.\n\nAssessment: Right rotator cuff impingement syndrome, likely supraspinatus involvement. No signs of full-thickness tear.\n\nPlan: Avoid overhead activities and heavy lifting for 2 weeks. Targeted soft tissue work and mobilisation of glenohumeral joint. Home exercises: pendulum swings, external rotation band work. Reassess at next visit.",

            "Chief Complaint: Knee pain — right medial joint line, 4/10 at rest, 7/10 with stairs.\n\nSubjective: Gradual onset over 3 months. No specific injury. Patient is a recreational runner (30 km/week). Pain worsens with downhill running and prolonged sitting. No swelling or locking reported. Previously tried rest — symptoms returned on resuming activity.\n\nObjective: Mild joint line tenderness medially. McMurray's test equivocal. Valgus stress test negative. Quads and glutes show weakness on single-leg squat assessment. No effusion palpable.\n\nAssessment: Likely medial compartment irritation with contributing hip and quad weakness. Possible early meniscal wear pattern.\n\nPlan: Reduced running volume by 50%. Strengthening protocol: glute bridges, step-downs, terminal knee extensions. Manual therapy to IT band and hip flexors. Review in 3 weeks.",

            "Chief Complaint: Fatigue, low energy, afternoon crash.\n\nSubjective: Patient reports persistent fatigue for 4 months. Energy is adequate in the morning but crashes significantly after lunch. Difficulty concentrating in the afternoon. Sleep is 7 hours but unrefreshing. Appetite variable. No recent illness or significant life changes.\n\nObjective: Blood pressure within normal range. Tongue slightly pale. Pulse weak on left chi position. No obvious lymphadenopathy. Patient appears well but tired.\n\nAssessment: Functional fatigue pattern with possible Spleen Qi deficiency component. Ruled out acute illness. Recommend thyroid and CBC review with GP if no improvement in 4 weeks.\n\nPlan: Dietary advice provided: regular meals, reduce cold/raw foods, increase warming proteins. Stress management techniques introduced. Continuation of supportive treatment protocol.",

            "Chief Complaint: Anxiety, shallow breathing, difficulty unwinding.\n\nSubjective: Patient experiences near-daily anxiety symptoms — racing thoughts, shallow breathing, tight chest, and difficulty winding down after work. No panic attacks. Sleep onset delayed (takes 45–60 min). Has not tried medication. Seeking natural approaches first.\n\nObjective: Breathing pattern observed: upper-chest dominant, rate ~18–20/min. Tense upper trapezius and scalene muscles bilaterally. Heart rate 82 bpm resting.\n\nAssessment: Anxiety with somatic presentation. Breathing retraining and vagal nerve stimulation strategies are appropriate. Responding well to treatment — reports feeling calmer post-session.\n\nPlan: Teach diaphragmatic breathing (4-7-8 technique). Progressive muscle relaxation handout provided. Continue current treatment frequency. Refer to counsellor if symptoms persist beyond 6 weeks.",
        ];

        return $this->state(fn (array $attributes) => [
            'status'       => 'complete',
            'visit_notes'  => $faker->randomElement($notes),
            'completed_on' => $faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'completed_on' => null]);
    }
}
