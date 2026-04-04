<?php

namespace Database\Seeders;

use App\Models\AcupunctureEncounter;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\CheckoutLine;
use App\Models\CheckoutSession;
use App\Models\ConsentRecord;
use App\Models\Encounter;
use App\Models\IntakeSubmission;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Practice ──────────────────────────────────────────────────────────
        $practice = Practice::updateOrCreate(
            ['slug' => 'serenity-acupuncture'],
            [
                'name'       => 'Serenity Acupuncture & Wellness',
                'timezone'   => 'America/Los_Angeles',
                'is_active'  => true,
                'is_demo'    => true,
                'discipline' => 'acupuncture',
            ]
        );

        // ── Admin user ────────────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'demo@practiqapp.com'],
            [
                'name'        => 'Demo Admin',
                'password'    => Hash::make('demo1234'),
                'practice_id' => $practice->id,
            ]
        );

        // ── Cleanup old demo data from previous resets ────────────────────────
        // demo:reset runs db:seed (not migrate:fresh), so records accumulate.
        // Delete in dependency order to avoid FK violations.
        $apptIds = Appointment::where('practice_id', $practice->id)->pluck('id');

        if ($apptIds->isNotEmpty()) {
            $encounterIds = Encounter::where('practice_id', $practice->id)->pluck('id');
            if ($encounterIds->isNotEmpty()) {
                AcupunctureEncounter::whereIn('encounter_id', $encounterIds)->delete();
            }
            $checkoutIds = CheckoutSession::where('practice_id', $practice->id)->pluck('id');
            if ($checkoutIds->isNotEmpty()) {
                CheckoutLine::whereIn('checkout_session_id', $checkoutIds)->delete();
            }
            ConsentRecord::where('practice_id', $practice->id)->whereNotNull('appointment_id')->delete();
            IntakeSubmission::where('practice_id', $practice->id)->whereNotNull('appointment_id')->delete();
            Encounter::where('practice_id', $practice->id)->delete();
            CheckoutSession::where('practice_id', $practice->id)->delete();
            Appointment::where('practice_id', $practice->id)->delete();
        }

        // Also clean up any standalone orphaned consent records / intake submissions
        ConsentRecord::where('practice_id', $practice->id)->whereDoesntHave('appointment')->delete();
        IntakeSubmission::where('practice_id', $practice->id)->whereDoesntHave('appointment')->delete();

        // ── Service fees ──────────────────────────────────────────────────────
        $feeData = [
            ['name' => 'Initial Consultation',      'default_price' => 150.00],
            ['name' => 'Follow-up Treatment',        'default_price' => 95.00],
            ['name' => 'Stress & Anxiety Protocol',  'default_price' => 110.00],
            ['name' => 'Community Acupuncture',      'default_price' => 45.00],
        ];

        $fees = [];
        foreach ($feeData as $data) {
            $fees[$data['name']] = ServiceFee::firstOrCreate(
                ['practice_id' => $practice->id, 'name' => $data['name']],
                array_merge($data, ['practice_id' => $practice->id, 'is_active' => true])
            );
        }

        // ── Appointment types ─────────────────────────────────────────────────
        $types = [];
        $typeConfigs = [
            ['name' => 'Initial Consultation',     'fee' => 'Initial Consultation',     'duration' => 90],
            ['name' => 'Follow-up Treatment',       'fee' => 'Follow-up Treatment',       'duration' => 60],
            ['name' => 'Stress & Anxiety Protocol', 'fee' => 'Stress & Anxiety Protocol', 'duration' => 75],
            ['name' => 'Community Acupuncture',     'fee' => 'Community Acupuncture',     'duration' => 45],
        ];

        foreach ($typeConfigs as $config) {
            $types[$config['name']] = AppointmentType::firstOrCreate(
                ['practice_id' => $practice->id, 'name' => $config['name']],
                [
                    'practice_id'            => $practice->id,
                    'name'                   => $config['name'],
                    'duration_minutes'       => $config['duration'],
                    'is_active'              => true,
                    'default_service_fee_id' => $fees[$config['fee']]->id,
                ]
            );
        }

        // ── Practitioners ─────────────────────────────────────────────────────
        $sarahUser = User::firstOrCreate(
            ['email' => 'sarah@serenity.test'],
            [
                'name'        => 'Dr. Sarah Chen',
                'password'    => Hash::make('password'),
                'practice_id' => $practice->id,
            ]
        );

        $practitioner1 = Practitioner::firstOrCreate(
            ['practice_id' => $practice->id, 'user_id' => $sarahUser->id],
            [
                'practice_id'    => $practice->id,
                'user_id'        => $sarahUser->id,
                'license_number' => 'L.Ac. CA-12847',
                'specialty'      => 'Acupuncture & Oriental Medicine',
                'is_active'      => true,
            ]
        );

        $marcusUser = User::firstOrCreate(
            ['email' => 'marcus@serenity.test'],
            [
                'name'        => 'Dr. Marcus Webb',
                'password'    => Hash::make('password'),
                'practice_id' => $practice->id,
            ]
        );

        $practitioner2 = Practitioner::firstOrCreate(
            ['practice_id' => $practice->id, 'user_id' => $marcusUser->id],
            [
                'practice_id'    => $practice->id,
                'user_id'        => $marcusUser->id,
                'license_number' => 'L.Ac. CA-09231',
                'specialty'      => 'Traditional Chinese Medicine',
                'is_active'      => true,
            ]
        );

        // ── Five Elements map ─────────────────────────────────────────────────
        $fiveElements = [
            'Wood'  => ['color' => 'Green/Blue', 'sound' => 'Shouting',  'odor' => 'Rancid',   'emotion' => 'Anger'],
            'Fire'  => ['color' => 'Red',         'sound' => 'Laughing',  'odor' => 'Scorched',  'emotion' => 'Joy'],
            'Earth' => ['color' => 'Yellow',       'sound' => 'Singing',   'odor' => 'Fragrant',  'emotion' => 'Worry'],
            'Metal' => ['color' => 'White',        'sound' => 'Weeping',   'odor' => 'Rotten',    'emotion' => 'Grief'],
            'Water' => ['color' => 'Black',        'sound' => 'Groaning',  'odor' => 'Putrid',    'emotion' => 'Fear'],
        ];

        // ── 8 Patients with full demographics ────────────────────────────────
        $patientData = [
            // 1. Emma Nakamura — lower back pain, ~38
            [
                'first_name'                => 'Emma',
                'last_name'                 => 'Nakamura',
                'email'                     => 'emma.nakamura@email.com',
                'phone'                     => '(415) 555-0201',
                'dob'                       => '1987-06-14',
                'gender'                    => 'female',
                'address_line_1'            => '1842 Irving St',
                'city'                      => 'San Francisco',
                'state'                     => 'CA',
                'postal_code'               => '94122',
                'emergency_contact_name'    => 'Kenji Nakamura',
                'emergency_contact_phone'   => '(415) 555-0202',
                'emergency_contact_relationship' => 'Husband',
                'occupation'                => 'Graphic Designer',
                // Clinical profile
                'chief_complaint'   => 'Chronic lower back pain with occasional radiating ache into the left hip, aggravated by prolonged sitting.',
                'onset_duration'    => '10 months',
                'onset_type'        => 'gradual',
                'aggravating'       => 'Sitting at a desk for more than 45 minutes, cold and damp weather, stress.',
                'relieving'         => 'Heat pad, gentle stretching, lying on a firm surface.',
                'pain_scale'        => 6,
                'sleep_quality'     => 'fair',
                'sleep_hours'       => 6,
                'stress_level'      => 'high',
                'exercise'          => 'rarely',
                'diet'              => 'Mostly home-cooked meals, tendency to skip lunch when busy.',
                'goals'             => 'Reduce daily pain so I can sit at my desk for a full workday without discomfort. Improve core strength.',
                'tcm_diagnosis'     => 'Kidney Yang Deficiency',
                'zang_fu'           => 'Kidney Yang Deficiency with Cold Obstruction in the Lumbar Channels',
                'elements'          => ['Water'],
                'tongue_body'       => 'Pale with teeth marks',
                'tongue_coating'    => 'Thin white',
                'pulse_quality'     => 'Deep weak',
                'points'            => 'KI3, KI7, BL23, GV4, SP6, BL40',
                'meridians'         => 'Kidney, Bladder, Governing Vessel, Spleen',
                'protocol'          => 'Tonify Kidney Yang, warm the lumbar channels, strengthen the lower back.',
                'needle_count'      => 12,
                'element_key'       => 'Water',
                'tcm_responses'     => [
                    'energy_level' => 'low', 'energy_time_pattern' => 'afternoon',
                    'temperature_preference' => 'cold', 'appetite' => 'normal',
                    'digestion_issues' => ['bloating'], 'bowel_frequency' => 'less_than_daily',
                    'thirst' => 'low', 'beverage_preference' => 'hot',
                    'sleep_issues' => ['staying_asleep'], 'dream_frequency' => 'sometimes',
                    'emotional_tendencies' => ['stress', 'anxiety'], 'emotional_impact' => 'significantly',
                    'previous_acupuncture' => false, 'needle_comfort' => 'nervous',
                ],
                'visit_notes_prefix' => 'lower back pain with left hip radiation. Reports 6/10 pain today.',
                'assessment_notes'   => 'Kidney Yang Deficiency with Cold Obstruction. Lumbar tenderness at BL23.',
                'plan_notes'         => 'Warm needle technique at BL23 and GV4. Continue weekly sessions for 6 weeks.',
            ],
            // 2. James Whitfield — insomnia, ~52
            [
                'first_name'                => 'James',
                'last_name'                 => 'Whitfield',
                'email'                     => 'james.whitfield@email.com',
                'phone'                     => '(415) 555-0203',
                'dob'                       => '1973-09-28',
                'gender'                    => 'male',
                'address_line_1'            => '3567 California St',
                'city'                      => 'San Francisco',
                'state'                     => 'CA',
                'postal_code'               => '94118',
                'emergency_contact_name'    => 'Patricia Whitfield',
                'emergency_contact_phone'   => '(415) 555-0204',
                'emergency_contact_relationship' => 'Wife',
                'occupation'                => 'Financial Analyst',
                'chief_complaint'   => 'Chronic insomnia — difficulty falling asleep and frequent waking between 1–3 am. Feeling unrested despite 7 hours in bed.',
                'onset_duration'    => '14 months',
                'onset_type'        => 'gradual',
                'aggravating'       => 'Work deadlines, caffeine after noon, screen time before bed.',
                'relieving'         => 'Melatonin (mild relief only), reading before bed.',
                'pain_scale'        => 3,
                'sleep_quality'     => 'poor',
                'sleep_hours'       => 5,
                'stress_level'      => 'very_high',
                'exercise'          => '1-2x_week',
                'diet'              => 'Skips breakfast, heavy dinner, drinks 3–4 coffees daily.',
                'goals'             => 'Fall asleep within 20 minutes and sleep through the night. Wake feeling rested.',
                'tcm_diagnosis'     => 'Heart and Kidney Disharmony',
                'zang_fu'           => 'Heart and Kidney Disharmony with Heart Fire Flaring and Kidney Yin Deficiency',
                'elements'          => ['Fire', 'Water'],
                'tongue_body'       => 'Red',
                'tongue_coating'    => 'Thin yellow',
                'pulse_quality'     => 'Rapid thin',
                'points'            => 'HT7, KI3, KI6, PC6, SP6, GV20, An Mian',
                'meridians'         => 'Heart, Kidney, Pericardium, Spleen, Governing Vessel',
                'protocol'          => 'Calm the Heart, nourish Kidney Yin, clear Empty Heat, anchor the Shen.',
                'needle_count'      => 14,
                'element_key'       => 'Fire',
                'tcm_responses'     => [
                    'energy_level' => 'low', 'energy_time_pattern' => 'morning',
                    'temperature_preference' => 'hot', 'appetite' => 'poor',
                    'digestion_issues' => ['none'], 'bowel_frequency' => 'once_daily',
                    'thirst' => 'high', 'beverage_preference' => 'cold',
                    'sleep_issues' => ['falling_asleep', 'early_waking'], 'dream_frequency' => 'often',
                    'emotional_tendencies' => ['anxiety', 'stress'], 'emotional_impact' => 'significantly',
                    'previous_acupuncture' => false, 'needle_comfort' => 'nervous',
                ],
                'visit_notes_prefix' => 'insomnia — sleeping approximately 4–5 hours, waking at 2 am. Heart palpitations noted.',
                'assessment_notes'   => 'Heart and Kidney Disharmony with Empty Heat rising. Pulse rapid and thin.',
                'plan_notes'         => 'Needle HT7, KI3, SP6, PC6. Add An Mian for shen calming. Recommend limiting caffeine.',
            ],
            // 3. Sofia Reyes — anxiety and stress, ~34
            [
                'first_name'                => 'Sofia',
                'last_name'                 => 'Reyes',
                'email'                     => 'sofia.reyes@email.com',
                'phone'                     => '(415) 555-0205',
                'dob'                       => '1991-03-07',
                'gender'                    => 'female',
                'address_line_1'            => '887 Valencia St Apt 4',
                'city'                      => 'San Francisco',
                'state'                     => 'CA',
                'postal_code'               => '94103',
                'emergency_contact_name'    => 'Maria Reyes',
                'emergency_contact_phone'   => '(415) 555-0206',
                'emergency_contact_relationship' => 'Mother',
                'occupation'                => 'Software Engineer',
                'chief_complaint'   => 'Generalised anxiety, chest tightness, and frequent shallow breathing. Difficulty managing work-related stress.',
                'onset_duration'    => '2 years',
                'onset_type'        => 'gradual',
                'aggravating'       => 'High-pressure work environments, deadlines, social gatherings.',
                'relieving'         => 'Yoga, deep breathing, spending time in nature.',
                'pain_scale'        => 4,
                'sleep_quality'     => 'fair',
                'sleep_hours'       => 7,
                'stress_level'      => 'very_high',
                'exercise'          => '3-4x_week',
                'diet'              => 'Healthy but tends to under-eat when anxious. Occasional comfort eating.',
                'goals'             => 'Reduce baseline anxiety and manage stress without medication. Improve overall sense of calm.',
                'tcm_diagnosis'     => 'Liver Qi Stagnation',
                'zang_fu'           => 'Liver Qi Stagnation with Heart Blood Deficiency and Lung Qi Constraint',
                'elements'          => ['Wood', 'Fire'],
                'tongue_body'       => 'Slightly red sides',
                'tongue_coating'    => 'Thin white',
                'pulse_quality'     => 'Wiry',
                'points'            => 'LV3, LV14, PC6, HT7, LU7, SP6, GV20',
                'meridians'         => 'Liver, Pericardium, Heart, Lung, Spleen, Governing Vessel',
                'protocol'          => 'Move Liver Qi, calm the Shen, nourish Heart Blood, regulate Lung Qi.',
                'needle_count'      => 14,
                'element_key'       => 'Wood',
                'tcm_responses'     => [
                    'energy_level' => 'moderate', 'energy_time_pattern' => 'no_pattern',
                    'temperature_preference' => 'neutral', 'appetite' => 'normal',
                    'digestion_issues' => ['nausea'], 'bowel_frequency' => 'once_daily',
                    'thirst' => 'normal', 'beverage_preference' => 'room',
                    'sleep_issues' => ['falling_asleep'], 'dream_frequency' => 'often',
                    'emotional_tendencies' => ['stress', 'anxiety'], 'emotional_impact' => 'significantly',
                    'previous_acupuncture' => true, 'needle_comfort' => 'comfortable',
                ],
                'visit_notes_prefix' => 'anxiety and chest tightness. Reports ongoing work stress. Breathing feels restricted.',
                'assessment_notes'   => 'Liver Qi Stagnation with Lung Qi constraint. Wiry pulse. Slightly red tongue sides.',
                'plan_notes'         => 'Needle LV3, PC6, LU7, GV20. Breathing exercises recommended between sessions.',
            ],
            // 4. Marcus Chen — migraines, ~45
            [
                'first_name'                => 'Marcus',
                'last_name'                 => 'Chen',
                'email'                     => 'marcus.chen@email.com',
                'phone'                     => '(415) 555-0207',
                'dob'                       => '1980-11-19',
                'gender'                    => 'male',
                'address_line_1'            => '2210 Fillmore St',
                'city'                      => 'San Francisco',
                'state'                     => 'CA',
                'postal_code'               => '94115',
                'emergency_contact_name'    => 'Angela Chen',
                'emergency_contact_phone'   => '(415) 555-0208',
                'emergency_contact_relationship' => 'Spouse',
                'occupation'                => 'Architect',
                'chief_complaint'   => 'Recurring migraines, typically right-sided, with visual aura, nausea, and photophobia. Episodes 2–3 times per month.',
                'onset_duration'    => '5 years',
                'onset_type'        => 'recurring',
                'aggravating'       => 'Stress, poor sleep, skipping meals, bright lights, strong smells.',
                'relieving'         => 'Dark quiet room, ice pack, sumatriptan (prescribed).',
                'pain_scale'        => 8,
                'sleep_quality'     => 'fair',
                'sleep_hours'       => 7,
                'stress_level'      => 'high',
                'exercise'          => '1-2x_week',
                'diet'              => 'Irregular meal times; tends to skip meals during busy periods.',
                'goals'             => 'Reduce migraine frequency from 2–3 per month to less than 1. Decrease severity and duration.',
                'tcm_diagnosis'     => 'Liver Yang Rising',
                'zang_fu'           => 'Liver Yang Rising with Liver Blood Deficiency and Kidney Yin Deficiency',
                'elements'          => ['Wood', 'Water'],
                'tongue_body'       => 'Red',
                'tongue_coating'    => 'Thin yellow',
                'pulse_quality'     => 'Wiry slippery',
                'points'            => 'GB20, GB21, LV3, LI4, ST36, SP6, KI3, GV20',
                'meridians'         => 'Gallbladder, Liver, Large Intestine, Stomach, Spleen, Kidney, Governing Vessel',
                'protocol'          => 'Subdue Liver Yang, clear Liver Fire, nourish Kidney Yin and Liver Blood.',
                'needle_count'      => 16,
                'element_key'       => 'Wood',
                'tcm_responses'     => [
                    'energy_level' => 'moderate', 'energy_time_pattern' => 'afternoon',
                    'temperature_preference' => 'warm', 'appetite' => 'normal',
                    'digestion_issues' => ['nausea'], 'bowel_frequency' => 'once_daily',
                    'thirst' => 'moderate', 'beverage_preference' => 'room',
                    'sleep_issues' => ['staying_asleep'], 'dream_frequency' => 'sometimes',
                    'emotional_tendencies' => ['stress', 'anger'], 'emotional_impact' => 'somewhat',
                    'previous_acupuncture' => false, 'needle_comfort' => 'comfortable',
                ],
                'visit_notes_prefix' => 'migraines — last episode was 10 days ago, lasting 18 hours. Nausea and photophobia present.',
                'assessment_notes'   => 'Liver Yang Rising. Wiry slippery pulse. Red tongue. Occiput tender at GB20.',
                'plan_notes'         => 'Strong needle stimulation at GB20, LV3, LI4. Reduce Liver Yang, calm wind.',
            ],
            // 5. Priya Sharma — fertility support, ~32
            [
                'first_name'                => 'Priya',
                'last_name'                 => 'Sharma',
                'email'                     => 'priya.sharma@email.com',
                'phone'                     => '(415) 555-0209',
                'dob'                       => '1993-05-23',
                'gender'                    => 'female',
                'address_line_1'            => '450 Noe St Apt 2',
                'city'                      => 'San Francisco',
                'state'                     => 'CA',
                'postal_code'               => '94114',
                'emergency_contact_name'    => 'Arjun Sharma',
                'emergency_contact_phone'   => '(415) 555-0210',
                'emergency_contact_relationship' => 'Husband',
                'occupation'                => 'Pediatrician',
                'chief_complaint'   => 'Seeking fertility support. Irregular menstrual cycles (35–42 days), mild cramping, and low energy in the luteal phase.',
                'onset_duration'    => '18 months',
                'onset_type'        => 'gradual',
                'aggravating'       => 'Stress, overwork, irregular sleep.',
                'relieving'         => 'Rest, warmth, regular eating habits.',
                'pain_scale'        => 3,
                'sleep_quality'     => 'good',
                'sleep_hours'       => 8,
                'stress_level'      => 'moderate',
                'exercise'          => '3-4x_week',
                'diet'              => 'Balanced vegetarian diet. Avoids processed food. Drinks warm water throughout the day.',
                'goals'             => 'Regulate menstrual cycle, support ovarian function, and prepare body for natural conception.',
                'tcm_diagnosis'     => 'Kidney and Liver Blood Deficiency',
                'zang_fu'           => 'Kidney Essence Deficiency with Liver Blood Deficiency and Chong/Ren Vessel Disharmony',
                'elements'          => ['Water', 'Wood'],
                'tongue_body'       => 'Pale',
                'tongue_coating'    => 'Thin white',
                'pulse_quality'     => 'Thin',
                'points'            => 'KI3, KI7, SP6, SP10, CV4, CV6, LV3, ST36',
                'meridians'         => 'Kidney, Spleen, Conception Vessel, Liver, Stomach',
                'protocol'          => 'Nourish Kidney Essence, tonify Liver Blood, regulate Chong and Ren vessels, support ovulation.',
                'needle_count'      => 12,
                'element_key'       => 'Water',
                'tcm_responses'     => [
                    'energy_level' => 'low', 'energy_time_pattern' => 'evening',
                    'temperature_preference' => 'warm', 'appetite' => 'good',
                    'digestion_issues' => ['bloating'], 'bowel_frequency' => 'once_daily',
                    'thirst' => 'low', 'beverage_preference' => 'warm',
                    'sleep_issues' => ['none'], 'dream_frequency' => 'rarely',
                    'emotional_tendencies' => ['stress', 'anxiety'], 'emotional_impact' => 'somewhat',
                    'previous_acupuncture' => false, 'needle_comfort' => 'comfortable',
                ],
                'visit_notes_prefix' => 'fertility support — cycle day 18 today. Reports some mild fatigue and lower abdominal fullness.',
                'assessment_notes'   => 'Kidney and Liver Blood Deficiency. Thin pale pulse. Chong/Ren disharmony likely.',
                'plan_notes'         => 'Moxa at CV4 and KI3, needle SP6 and SP10. Schedule treatment around cycle phases.',
            ],
            // 6. David O'Brien — digestive issues, ~61
            [
                'first_name'                => 'David',
                'last_name'                 => "O'Brien",
                'email'                     => 'david.obrien@email.com',
                'phone'                     => '(415) 555-0211',
                'dob'                       => '1964-02-08',
                'gender'                    => 'male',
                'address_line_1'            => '1129 Divisadero St',
                'city'                      => 'San Francisco',
                'state'                     => 'CA',
                'postal_code'               => '94115',
                'emergency_contact_name'    => 'Colleen O\'Brien',
                'emergency_contact_phone'   => '(415) 555-0212',
                'emergency_contact_relationship' => 'Wife',
                'occupation'                => 'Retired Teacher',
                'chief_complaint'   => 'Chronic bloating, loose stools, and post-meal fatigue. IBS diagnosis 8 years ago. Symptoms worse after stress or rich foods.',
                'onset_duration'    => '8 years',
                'onset_type'        => 'recurring',
                'aggravating'       => 'Fatty or spicy foods, alcohol, emotional stress, cold drinks.',
                'relieving'         => 'Simple bland diet, warmth, rest after meals.',
                'pain_scale'        => 4,
                'sleep_quality'     => 'good',
                'sleep_hours'       => 7,
                'stress_level'      => 'moderate',
                'exercise'          => '3-4x_week',
                'diet'              => 'Mostly home-cooked. Avoids known triggers but still experiences flare-ups. Low-fibre diet.',
                'goals'             => 'Reduce bloating frequency and improve energy after meals. Achieve more consistent bowel movements.',
                'tcm_diagnosis'     => 'Spleen Qi Deficiency with Dampness',
                'zang_fu'           => 'Spleen Qi Deficiency with Damp Accumulation and Liver Overacting on Spleen',
                'elements'          => ['Earth', 'Wood'],
                'tongue_body'       => 'Pale with teeth marks',
                'tongue_coating'    => 'Greasy white',
                'pulse_quality'     => 'Slippery',
                'points'            => 'ST36, SP6, SP9, CV12, PC6, LV3, BL20, BL21',
                'meridians'         => 'Stomach, Spleen, Conception Vessel, Pericardium, Liver, Bladder',
                'protocol'          => 'Strengthen Spleen Qi, resolve Dampness, regulate Liver-Spleen harmony, improve digestive function.',
                'needle_count'      => 14,
                'element_key'       => 'Earth',
                'tcm_responses'     => [
                    'energy_level' => 'low', 'energy_time_pattern' => 'afternoon',
                    'temperature_preference' => 'warm', 'appetite' => 'poor',
                    'digestion_issues' => ['bloating', 'constipation'], 'bowel_frequency' => 'less_than_daily',
                    'thirst' => 'low', 'beverage_preference' => 'warm',
                    'sleep_issues' => ['none'], 'dream_frequency' => 'rarely',
                    'emotional_tendencies' => ['stress'], 'emotional_impact' => 'mildly',
                    'previous_acupuncture' => true, 'needle_comfort' => 'very_comfortable',
                ],
                'visit_notes_prefix' => 'digestive complaints — bloating after every meal, 2 loose stools per day. Post-meal fatigue rates 4/10.',
                'assessment_notes'   => 'Spleen Qi Deficiency with Damp accumulation. Pale swollen tongue. Slippery pulse.',
                'plan_notes'         => 'Moxa on ST36 and CV12. Needle SP9 to drain damp. Dietary advice: avoid cold foods and dairy.',
            ],
            // 7. Rachel Kim — shoulder tension, ~29
            [
                'first_name'                => 'Rachel',
                'last_name'                 => 'Kim',
                'email'                     => 'rachel.kim@email.com',
                'phone'                     => '(415) 555-0213',
                'dob'                       => '1996-08-31',
                'gender'                    => 'female',
                'address_line_1'            => '3211 20th St Apt 1',
                'city'                      => 'San Francisco',
                'state'                     => 'CA',
                'postal_code'               => '94110',
                'emergency_contact_name'    => 'Susan Kim',
                'emergency_contact_phone'   => '(415) 555-0214',
                'emergency_contact_relationship' => 'Mother',
                'occupation'                => 'UX Designer',
                'chief_complaint'   => 'Chronic right-side shoulder and upper trapezius tension. Stiffness in the morning and after long work sessions.',
                'onset_duration'    => '6 months',
                'onset_type'        => 'gradual',
                'aggravating'       => 'Prolonged laptop use, cold drafts, poor posture.',
                'relieving'         => 'Heat, massage, regular stretching breaks.',
                'pain_scale'        => 5,
                'sleep_quality'     => 'good',
                'sleep_hours'       => 8,
                'stress_level'      => 'moderate',
                'exercise'          => '3-4x_week',
                'diet'              => 'Varied and generally healthy. Tends to eat late dinners due to work schedule.',
                'goals'             => 'Eliminate morning stiffness and reduce shoulder tension to allow comfortable all-day work.',
                'tcm_diagnosis'     => 'Qi and Blood Stagnation',
                'zang_fu'           => 'Qi and Blood Stagnation in the Shoulder and Neck Channels with Wind-Cold Invasion',
                'elements'          => ['Metal', 'Wood'],
                'tongue_body'       => 'Slightly purple',
                'tongue_coating'    => 'Thin white',
                'pulse_quality'     => 'Wiry',
                'points'            => 'GB21, SI11, SI12, LI4, TW5, LU7, BL11',
                'meridians'         => 'Gallbladder, Small Intestine, Large Intestine, Triple Warmer, Lung, Bladder',
                'protocol'          => 'Move Qi and Blood in shoulder channels, dispel Wind-Cold, release local and distal point tension.',
                'needle_count'      => 12,
                'element_key'       => 'Metal',
                'tcm_responses'     => [
                    'energy_level' => 'normal', 'energy_time_pattern' => 'evening',
                    'temperature_preference' => 'warm', 'appetite' => 'good',
                    'digestion_issues' => ['none'], 'bowel_frequency' => 'once_daily',
                    'thirst' => 'moderate', 'beverage_preference' => 'room',
                    'sleep_issues' => ['none'], 'dream_frequency' => 'rarely',
                    'emotional_tendencies' => ['stress'], 'emotional_impact' => 'mildly',
                    'previous_acupuncture' => false, 'needle_comfort' => 'comfortable',
                ],
                'visit_notes_prefix' => 'right shoulder tension — rates stiffness 5/10 this morning. Limited internal rotation noted.',
                'assessment_notes'   => 'Qi and Blood Stagnation in shoulder and neck channels. Wind-Cold invasion likely contributing.',
                'plan_notes'         => 'Local needles GB21 and SI11 with distal points LI4 and TW5. Cupping on upper back.',
            ],
            // 8. Helen Fitzgerald — menopausal symptoms, ~56
            [
                'first_name'                => 'Helen',
                'last_name'                 => 'Fitzgerald',
                'email'                     => 'helen.fitzgerald@email.com',
                'phone'                     => '(415) 555-0215',
                'dob'                       => '1969-12-04',
                'gender'                    => 'female',
                'address_line_1'            => '4402 Washington St',
                'city'                      => 'San Francisco',
                'state'                     => 'CA',
                'postal_code'               => '94118',
                'emergency_contact_name'    => 'Thomas Fitzgerald',
                'emergency_contact_phone'   => '(415) 555-0216',
                'emergency_contact_relationship' => 'Husband',
                'occupation'                => 'Librarian',
                'chief_complaint'   => 'Peri-menopausal hot flushes (6–8 per day), night sweats, irritability, and brain fog. Some vaginal dryness.',
                'onset_duration'    => '2 years',
                'onset_type'        => 'gradual',
                'aggravating'       => 'Stress, spicy foods, alcohol, warm environments.',
                'relieving'         => 'Cool environments, loose cotton clothing, mindfulness practice.',
                'pain_scale'        => 4,
                'sleep_quality'     => 'poor',
                'sleep_hours'       => 5,
                'stress_level'      => 'high',
                'exercise'          => '3-4x_week',
                'diet'              => 'Varied diet. Reduces sugar and processed foods. Drinks herbal teas.',
                'goals'             => 'Reduce hot flush frequency and night sweats. Improve sleep quality and stabilise mood.',
                'tcm_diagnosis'     => 'Kidney Yin Deficiency with Empty Heat',
                'zang_fu'           => 'Kidney Yin Deficiency with Empty Heat Rising and Heart Fire Disturbing the Shen',
                'elements'          => ['Water', 'Fire'],
                'tongue_body'       => 'Red',
                'tongue_coating'    => 'No coating',
                'pulse_quality'     => 'Rapid thin',
                'points'            => 'KI3, KI6, SP6, HT6, LU7, CV4, BL23, PC6',
                'meridians'         => 'Kidney, Spleen, Heart, Lung, Conception Vessel, Bladder, Pericardium',
                'protocol'          => 'Nourish Kidney Yin, clear Empty Heat, calm the Shen, balance the Chong and Ren vessels.',
                'needle_count'      => 14,
                'element_key'       => 'Water',
                'tcm_responses'     => [
                    'energy_level' => 'low', 'energy_time_pattern' => 'morning',
                    'temperature_preference' => 'hot', 'appetite' => 'normal',
                    'digestion_issues' => ['none'], 'bowel_frequency' => 'once_daily',
                    'thirst' => 'high', 'beverage_preference' => 'cold',
                    'sleep_issues' => ['staying_asleep', 'early_waking'], 'dream_frequency' => 'often',
                    'emotional_tendencies' => ['anxiety', 'stress'], 'emotional_impact' => 'significantly',
                    'previous_acupuncture' => true, 'needle_comfort' => 'very_comfortable',
                ],
                'visit_notes_prefix' => 'menopausal symptoms — 6–7 hot flushes today, two night sweats last night. Sleep 4–5 hours.',
                'assessment_notes'   => 'Kidney Yin Deficiency with Empty Heat. Red peeled tongue, rapid thin pulse.',
                'plan_notes'         => 'Needle KI3, KI6, HT6 to nourish Yin and clear Empty Heat. SP6 and CV4 to support Chong/Ren.',
            ],
        ];

        // Create patients (keyed on email for idempotency)
        $patients = [];
        foreach ($patientData as $data) {
            $patients[] = Patient::firstOrCreate(
                ['practice_id' => $practice->id, 'email' => $data['email']],
                [
                    'practice_id'               => $practice->id,
                    'first_name'                => $data['first_name'],
                    'last_name'                 => $data['last_name'],
                    'name'                      => $data['first_name'] . ' ' . $data['last_name'],
                    'email'                     => $data['email'],
                    'phone'                     => $data['phone'],
                    'dob'                       => $data['dob'],
                    'gender'                    => $data['gender'],
                    'address_line_1'            => $data['address_line_1'],
                    'city'                      => $data['city'],
                    'state'                     => $data['state'],
                    'postal_code'               => $data['postal_code'],
                    'emergency_contact_name'    => $data['emergency_contact_name'],
                    'emergency_contact_phone'   => $data['emergency_contact_phone'],
                    'emergency_contact_relationship' => $data['emergency_contact_relationship'],
                    'occupation'                => $data['occupation'],
                    'is_patient'                => true,
                ]
            );
        }

        // ── Standalone IntakeSubmission + ConsentRecord per patient ───────────
        // One per patient — complete status, not tied to a specific appointment
        foreach ($patientData as $idx => $data) {
            $patient = $patients[$idx];
            $submittedOn = Carbon::now('UTC')->subDays(30 + $idx * 7);

            IntakeSubmission::create([
                'practice_id'          => $practice->id,
                'patient_id'           => $patient->id,
                'status'               => 'complete',
                'discipline'           => 'acupuncture',
                'submitted_on'         => $submittedOn,
                'chief_complaint'      => $data['chief_complaint'],
                'onset_duration'       => $data['onset_duration'],
                'onset_type'           => $data['onset_type'],
                'aggravating_factors'  => $data['aggravating'],
                'relieving_factors'    => $data['relieving'],
                'pain_scale'           => $data['pain_scale'],
                'previous_episodes'    => true,
                'exercise_frequency'   => $data['exercise'],
                'sleep_quality'        => $data['sleep_quality'],
                'sleep_hours'          => $data['sleep_hours'],
                'stress_level'         => $data['stress_level'],
                'diet_description'     => $data['diet'],
                'treatment_goals'      => $data['goals'],
                'consent_given'        => true,
                'consent_signed_by'    => $data['first_name'] . ' ' . $data['last_name'],
                'consent_signed_at'    => $submittedOn,
                'discipline_responses' => ['tcm' => $data['tcm_responses']],
            ]);

            ConsentRecord::create([
                'practice_id'      => $practice->id,
                'patient_id'       => $patient->id,
                'status'           => 'complete',
                'signed_on'        => $submittedOn,
                'consent_given_by' => $data['first_name'] . ' ' . $data['last_name'],
                'consent_summary'  => 'Patient has read and consented to treatment terms and privacy policy.',
            ]);
        }

        // ── Historical appointments: 3 per patient = 24 total ────────────────
        $now = Carbon::now('America/Los_Angeles');
        $typeArray = array_values($types);
        $practitioners = [$practitioner1, $practitioner2];

        // Session notes variations for each patient visit
        $sessionNotesVariations = [
            'Patient responded well to needle placement. De qi sensation achieved at all major points. Reported immediate relaxation.',
            'Good response to treatment. Patient fell asleep during the session — positive sign of parasympathetic activation.',
            'Patient tolerated treatment well. Mild sensitivity at local points but no adverse reactions. Reported feeling lighter post-treatment.',
        ];

        foreach ($patientData as $idx => $data) {
            $patient = $patients[$idx];
            $practitioner = $practitioners[$idx % 2];

            // Spread 3 visits for this patient across last 6 months
            // Each visit ~6-8 weeks apart
            $visitOffsets = [150 + $idx * 3, 90 + $idx * 2, 30 + $idx];

            foreach ($visitOffsets as $visitIdx => $daysAgo) {
                // Alternate appointment types: initial for first, follow-up thereafter
                $apptType = $visitIdx === 0 ? $types['Initial Consultation'] : $types['Follow-up Treatment'];
                $fee = $apptType->defaultServiceFee;

                $hour = 9 + ($idx % 6) + ($visitIdx * 2);
                if ($hour > 16) {
                    $hour = 9 + $visitIdx;
                }

                $apptStart = $now->copy()->subDays($daysAgo)->setHour($hour)->setMinute(0)->setSecond(0)->utc();
                $apptEnd   = $apptStart->copy()->addMinutes($apptType->duration_minutes);

                $appointment = Appointment::create([
                    'practice_id'         => $practice->id,
                    'patient_id'          => $patient->id,
                    'practitioner_id'     => $practitioner->id,
                    'appointment_type_id' => $apptType->id,
                    'status'              => 'completed',
                    'start_datetime'      => $apptStart,
                    'end_datetime'        => $apptEnd,
                    'notes'               => 'Completed session — ' . $data['chief_complaint'],
                ]);

                // Encounter (SOAP note)
                $encounter = Encounter::create([
                    'practice_id'     => $practice->id,
                    'patient_id'      => $patient->id,
                    'appointment_id'  => $appointment->id,
                    'practitioner_id' => $practitioner->id,
                    'status'          => 'complete',
                    'visit_date'      => $apptStart->toDateString(),
                    'completed_on'    => $apptStart->copy()->addHour(),
                    'visit_notes'     => implode("\n\n", [
                        'SUBJECTIVE: ' . $patient->name . ' presented with ' . $data['visit_notes_prefix'],
                        'OBJECTIVE: Tongue: ' . $data['tongue_body'] . ', coating ' . $data['tongue_coating'] . '. Pulse: ' . $data['pulse_quality'] . '.',
                        'ASSESSMENT: ' . $data['assessment_notes'],
                        'PLAN: ' . $data['plan_notes'],
                    ]),
                ]);

                // AcupunctureEncounter — full fields
                $elementKey  = $data['element_key'];
                $elementData = $fiveElements[$elementKey];
                $elements    = $data['elements'];

                AcupunctureEncounter::create([
                    'encounter_id'       => $encounter->id,
                    'tcm_diagnosis'      => $data['tcm_diagnosis'],
                    'tongue_body'        => $data['tongue_body'],
                    'tongue_coating'     => $data['tongue_coating'],
                    'pulse_quality'      => $data['pulse_quality'],
                    'zang_fu_diagnosis'  => $data['zang_fu'],
                    'five_elements'      => $elements,
                    'csor_color'         => $elementData['color'],
                    'csor_sound'         => $elementData['sound'],
                    'csor_odor'          => $elementData['odor'],
                    'csor_emotion'       => $elementData['emotion'],
                    'points_used'        => $data['points'],
                    'meridians'          => $data['meridians'],
                    'treatment_protocol' => $data['protocol'],
                    'needle_count'       => $data['needle_count'],
                    'session_notes'      => $sessionNotesVariations[$visitIdx],
                ]);

                // IntakeSubmission for this appointment visit (with status complete)
                IntakeSubmission::create([
                    'practice_id'          => $practice->id,
                    'patient_id'           => $patient->id,
                    'appointment_id'       => $appointment->id,
                    'status'               => 'complete',
                    'discipline'           => 'acupuncture',
                    'submitted_on'         => $apptStart->copy()->subDay(),
                    'chief_complaint'      => $data['chief_complaint'],
                    'onset_type'           => $data['onset_type'],
                    'pain_scale'           => $data['pain_scale'],
                    'consent_given'        => true,
                    'consent_signed_by'    => $patient->name,
                    'consent_signed_at'    => $apptStart->copy()->subDay(),
                    'discipline_responses' => ['tcm' => $data['tcm_responses']],
                ]);

                ConsentRecord::create([
                    'practice_id'      => $practice->id,
                    'patient_id'       => $patient->id,
                    'appointment_id'   => $appointment->id,
                    'status'           => 'complete',
                    'signed_on'        => $apptStart->copy()->subDay(),
                    'consent_given_by' => $patient->name,
                    'consent_summary'  => 'Patient consented to treatment prior to visit.',
                ]);

                // CheckoutSession (paid)
                $checkoutSession = CheckoutSession::create([
                    'practice_id'     => $practice->id,
                    'appointment_id'  => $appointment->id,
                    'patient_id'      => $patient->id,
                    'practitioner_id' => $practitioner->id,
                    'state'           => 'paid',
                    'charge_label'    => $apptType->name,
                    'amount_total'    => $fee->default_price,
                    'amount_paid'     => $fee->default_price,
                    'tender_type'     => $visitIdx % 2 === 0 ? 'card' : 'cash',
                    'started_on'      => $apptStart,
                    'paid_on'         => $apptStart->copy()->addHour(),
                    'payment_note'    => 'Payment received in full.',
                ]);

                // CheckoutLine
                CheckoutLine::create([
                    'checkout_session_id' => $checkoutSession->id,
                    'practice_id'         => $practice->id,
                    'sequence'            => 1,
                    'description'         => $apptType->name,
                    'amount'              => $fee->default_price,
                ]);
            }
        }

        // ── Today's appointments (3, scheduled) ──────────────────────────────
        $todayHours = [10, 13, 15];
        $todayPatients = [$patients[0], $patients[1], $patients[2]];
        $todayTypes = [
            $types['Follow-up Treatment'],
            $types['Stress & Anxiety Protocol'],
            $types['Follow-up Treatment'],
        ];

        foreach ($todayHours as $i => $hour) {
            $patient = $todayPatients[$i];
            $data    = $patientData[$i];
            $apptType = $todayTypes[$i];

            $apptStart = $now->copy()->setHour($hour)->setMinute(0)->setSecond(0)->utc();
            $apptEnd   = $apptStart->copy()->addMinutes($apptType->duration_minutes);

            $appointment = Appointment::create([
                'practice_id'         => $practice->id,
                'patient_id'          => $patient->id,
                'practitioner_id'     => $practitioners[$i % 2]->id,
                'appointment_type_id' => $apptType->id,
                'status'              => 'scheduled',
                'start_datetime'      => $apptStart,
                'end_datetime'        => $apptEnd,
                'notes'               => 'Today\'s follow-up — ' . $data['chief_complaint'],
            ]);

            // Pending intake and consent for today's scheduled visits
            IntakeSubmission::create([
                'practice_id' => $practice->id,
                'patient_id'  => $patient->id,
                'appointment_id' => $appointment->id,
                'status'      => 'pending',
            ]);

            ConsentRecord::create([
                'practice_id' => $practice->id,
                'patient_id'  => $patient->id,
                'appointment_id' => $appointment->id,
                'status'      => 'pending',
            ]);
        }

        // ── This week's upcoming appointments (3, scheduled, next 3-5 days) ──
        $weekDayOffsets  = [2, 3, 5];
        $weekPatients    = [$patients[3], $patients[4], $patients[5]];
        $weekTypes       = [
            $types['Follow-up Treatment'],
            $types['Initial Consultation'],
            $types['Stress & Anxiety Protocol'],
        ];
        $weekHours       = [11, 14, 10];

        foreach ($weekDayOffsets as $i => $daysAhead) {
            $patient  = $weekPatients[$i];
            $data     = $patientData[3 + $i];
            $apptType = $weekTypes[$i];

            $apptStart = $now->copy()->addDays($daysAhead)->setHour($weekHours[$i])->setMinute(0)->setSecond(0)->utc();
            $apptEnd   = $apptStart->copy()->addMinutes($apptType->duration_minutes);

            $appointment = Appointment::create([
                'practice_id'         => $practice->id,
                'patient_id'          => $patient->id,
                'practitioner_id'     => $practitioners[$i % 2]->id,
                'appointment_type_id' => $apptType->id,
                'status'              => 'scheduled',
                'start_datetime'      => $apptStart,
                'end_datetime'        => $apptEnd,
                'notes'               => 'Upcoming appointment — ' . $data['chief_complaint'],
            ]);

            IntakeSubmission::create([
                'practice_id' => $practice->id,
                'patient_id'  => $patient->id,
                'appointment_id' => $appointment->id,
                'status'      => 'pending',
            ]);

            ConsentRecord::create([
                'practice_id' => $practice->id,
                'patient_id'  => $patient->id,
                'appointment_id' => $appointment->id,
                'status'      => 'pending',
            ]);
        }

        // ── Cancelled appointments (2) ────────────────────────────────────────
        $cancelledData = [
            ['patient' => $patients[6], 'daysAgo' => 45, 'hour' => 11],
            ['patient' => $patients[7], 'daysAgo' => 20, 'hour' => 14],
        ];

        foreach ($cancelledData as $i => $cData) {
            $apptType  = $types['Follow-up Treatment'];
            $apptStart = $now->copy()->subDays($cData['daysAgo'])->setHour($cData['hour'])->setMinute(0)->setSecond(0)->utc();
            $apptEnd   = $apptStart->copy()->addMinutes($apptType->duration_minutes);

            Appointment::create([
                'practice_id'         => $practice->id,
                'patient_id'          => $cData['patient']->id,
                'practitioner_id'     => $practitioners[$i % 2]->id,
                'appointment_type_id' => $apptType->id,
                'status'              => 'cancelled',
                'start_datetime'      => $apptStart,
                'end_datetime'        => $apptEnd,
                'notes'               => 'Cancelled by patient — unable to attend.',
            ]);
        }

        // ── Inventory Movements ───────────────────────────────────────────────
        // Seed movements only once to avoid accumulation on repeated demo:reset
        if (!InventoryMovement::where('practice_id', $practice->id)->exists()) {
            // First, seed the products
            $this->call(InventoryProductSeeder::class);

            // Get all products for this practice
            $products = InventoryProduct::where('practice_id', $practice->id)->get();

            foreach ($products as $product) {
                // 1. Initial stock receipt: 50-100 units, 6 months ago
                InventoryMovement::create([
                    'id'                   => \Illuminate\Support\Str::uuid(),
                    'practice_id'          => $practice->id,
                    'inventory_product_id' => $product->id,
                    'type'                 => 'in',
                    'quantity'             => rand(50, 100),
                    'unit_price'           => $product->cost_price,
                    'reference'            => null,
                    'notes'                => 'Initial stock',
                    'created_by'           => null,
                    'created_at'           => Carbon::now()->subMonths(6),
                    'updated_at'           => Carbon::now()->subMonths(6),
                ]);

                // 2. Two to three dispensing movements over the last 3 months
                for ($i = 0; $i < rand(2, 3); $i++) {
                    $randomPatient = $patients[array_rand($patients)];
                    InventoryMovement::create([
                        'id'                   => \Illuminate\Support\Str::uuid(),
                        'practice_id'          => $practice->id,
                        'inventory_product_id' => $product->id,
                        'type'                 => 'out',
                        'quantity'             => rand(1, 5),
                        'unit_price'           => $product->cost_price,
                        'reference'            => null,
                        'notes'                => 'Dispensed to ' . $randomPatient->first_name,
                        'created_by'           => null,
                        'created_at'           => Carbon::now()->subMonths(rand(1, 3)),
                        'updated_at'           => Carbon::now()->subMonths(rand(1, 3)),
                    ]);
                }

                // 3. Adjustment movement: ±5 units, 1 month ago
                InventoryMovement::create([
                    'id'                   => \Illuminate\Support\Str::uuid(),
                    'practice_id'          => $practice->id,
                    'inventory_product_id' => $product->id,
                    'type'                 => 'adjustment',
                    'quantity'             => rand(-5, 5),
                    'unit_price'           => null,
                    'reference'            => null,
                    'notes'                => 'Inventory adjustment',
                    'created_by'           => null,
                    'created_at'           => Carbon::now()->subMonth(),
                    'updated_at'           => Carbon::now()->subMonth(),
                ]);
            }
        } else {
            // Products already seeded, skip and call the seeder anyway to avoid skipping it entirely
            $this->call(InventoryProductSeeder::class);
        }

        // ── Communications ────────────────────────────────────────────────────
        (new DefaultMessageTemplatesSeeder())->seedForPractice($practice);

        // ── Summary ───────────────────────────────────────────────────────────
        $this->command->info('✔ Demo practice: Serenity Acupuncture & Wellness');
        $this->command->info('✔ 2 practitioners: Dr. Sarah Chen, Dr. Marcus Webb');
        $this->command->info('✔ 8 patients with full demographics, intake forms, and consent records');
        $this->command->info('✔ 24 historical appointments (3 per patient) with encounters, acupuncture data, and paid checkouts');
        $this->command->info('✔ 3 today\'s appointments (scheduled)');
        $this->command->info('✔ 3 upcoming this-week appointments (scheduled)');
        $this->command->info('✔ 2 cancelled appointments');
        $this->command->info('✔ Inventory products seeded');
        $this->command->info('✔ Default communication templates seeded');
    }
}
