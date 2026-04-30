<?php

namespace App\Support;

use App\Models\Practice;

class PracticeType
{
    public const GENERAL_WELLNESS = 'general_wellness';

    public const TCM_ACUPUNCTURE = 'tcm_acupuncture';

    public const FIVE_ELEMENT_ACUPUNCTURE = 'five_element_acupuncture';

    public const CHIROPRACTIC = 'chiropractic';

    public const MASSAGE_THERAPY = 'massage_therapy';

    public const PHYSIOTHERAPY = 'physiotherapy';

    public static function options(): array
    {
        return [
            self::GENERAL_WELLNESS => 'General Wellness',
            self::TCM_ACUPUNCTURE => 'TCM Acupuncture',
            self::FIVE_ELEMENT_ACUPUNCTURE => 'Five Element Acupuncture',
            self::CHIROPRACTIC => 'Chiropractic',
            self::MASSAGE_THERAPY => 'Massage Therapy',
            self::PHYSIOTHERAPY => 'Physiotherapy',
        ];
    }

    public static function label(?string $practiceType): string
    {
        return self::options()[self::normalize($practiceType)] ?? self::options()[self::GENERAL_WELLNESS];
    }

    public static function normalize(?string $practiceType, ?string $discipline = null): string
    {
        if (array_key_exists((string) $practiceType, self::options())) {
            return (string) $practiceType;
        }

        return self::fromDiscipline($discipline);
    }

    public static function fromPractice(?Practice $practice): string
    {
        return self::normalize($practice?->practice_type, $practice?->discipline);
    }

    public static function fromDiscipline(?string $discipline): string
    {
        return match ($discipline) {
            'acupuncture', 'Acupuncture' => self::TCM_ACUPUNCTURE,
            'chiropractic', 'Chiropractic' => self::CHIROPRACTIC,
            'massage', 'massage_therapy', 'Massage Therapy' => self::MASSAGE_THERAPY,
            'physiotherapy', 'physical_therapy', 'Physiotherapy' => self::PHYSIOTHERAPY,
            default => self::GENERAL_WELLNESS,
        };
    }

    public static function disciplineFallback(string $practiceType): string
    {
        return match (self::normalize($practiceType)) {
            self::TCM_ACUPUNCTURE, self::FIVE_ELEMENT_ACUPUNCTURE => 'acupuncture',
            self::CHIROPRACTIC => 'chiropractic',
            self::MASSAGE_THERAPY => 'massage',
            self::PHYSIOTHERAPY => 'physiotherapy',
            default => 'general',
        };
    }

    public static function aiInstructions(?string $practiceType): string
    {
        $base = <<<'TEXT'
Practice type safety rules:
- AI output is a draft for practitioner review.
- Improve clarity and organization using only documented information.
- Do not invent clinical findings.
- Do not invent diagnoses.
- Do not invent treatment details.
- Do not invent patient statements.
- Do not invent response to treatment.
- Do not invent points, techniques, exercises, assessments, tongue, pulse, color, sound, odor, emotion, orthopedic findings, or exam results.
- When information may be clinically useful but is absent, suggest documenting it rather than asserting it.
- Keep the practitioner's terminology unless it is unclear or unsafe.
- Prefer concise, usable note text over verbose explanations.
TEXT;

        $specific = match (self::normalize($practiceType)) {
            self::TCM_ACUPUNCTURE => <<<'TEXT'
For TCM Acupuncture:
- Use TCM-compatible language when supported by the note.
- Preserve the practitioner's wording where possible.
- May clarify pattern impression, treatment principle, channel logic, and point rationale if already documented.
- Do not invent tongue, pulse, pattern diagnosis, point rationale, points, techniques, or treatment response.
- Do not translate into Five Element / Worsley language unless already present.
TEXT,
            self::FIVE_ELEMENT_ACUPUNCTURE => <<<'TEXT'
For Five Element Acupuncture:
- The Worsley Five Element system has its own nomenclature and clinical language.
- Do not rewrite Five Element notes into generic TCM language.
- Preserve practitioner wording where possible, especially around element, Causative Factor / CF, Officials, rapport, patient presentation, and treatment intention.
- Respect Roman numeral meridian/channel nomenclature used in Worsley-style notes:
  - Roman I = Heart
  - Roman II = Small Intestine
  - Roman III = Bladder
  - Roman IV = Kidney
  - Roman V = Circulation-Sex / Heart Protector / Pericardium
  - Roman VI = Triple Heater / San Jiao
  - Roman VII = Gallbladder
  - Roman VIII = Liver
  - Roman IX = Lung
  - Roman X = Large Intestine
  - Roman XI = Stomach
  - Roman XII = Spleen
- Recognize that point names may use Worsley/Five Element names and should be preserved when present.
- Recognize Five Element treatment concepts including Aggressive Energy treatment, AE drain, Husband-Wife treatment, Entry-Exit blocks, Possession treatment, Akabane imbalance/testing, Causative Factor / CF, Officials, Color/Sound/Odor/Emotion (CSOE), command points, source points, horary points, tonification and sedation points, and moxa as part of treatment documentation.
- Worsley/Classical Five Element pulse notes are comparative and energetic, focused on relative strength or weakness before and after treatment.
- Preserve pre/post pulse shorthand and clarify change over treatment without changing the recorded meaning.
- Do not convert Five Element pulse notes into generic TCM 28-pulse terminology.
- Recognize pulse symbols +++, ++, +, =, -, --, ---, and 0.
- Recognize optional numeric pulse style 0 to 5 when present.
- Recognize official abbreviations L, LI, St, Sp, Ht, SI, B, K, PC, TB, GB, and Lv.
- Make the note clearer and more complete while keeping the Five Element clinical meaning intact.
- Do not force TCM pattern diagnosis.
- Do not "correct" Worsley terminology into generic TCM terms.
- Do not invent diagnosis, CF, points, pulses, blocks, or treatment details not present.
- If pulse information is missing, suggest optional pulse documentation prompts rather than filling it in.
- If details are missing, phrase gaps as optional documentation prompts or neutral suggestions.
- Maintain warm, concise clinical language.
- For patient-facing text, avoid overly technical Five Element jargon unless the practitioner used it intentionally; translate concepts into plain language when appropriate.
- For practitioner-facing clinical notes, preserve Five Element terminology and point nomenclature.
TEXT,
            self::CHIROPRACTIC => <<<'TEXT'
For Chiropractic:
- Use chiropractic-compatible language when supported by the note.
- May organize around complaint, functional limitations, exam findings, treatment performed, response, and plan.
- Do not invent orthopedic findings, neurological findings, ROM, palpation findings, adjustment details, contraindications, or response.
TEXT,
            self::MASSAGE_THERAPY => <<<'TEXT'
For Massage Therapy:
- Use massage/bodywork-compatible language without overmedicalizing the note.
- May organize around client goals, areas treated, techniques used, tissue response, self-care, and plan.
- Do not invent tissue findings, pressure level, contraindications, techniques, client feedback, or response.
TEXT,
            self::PHYSIOTHERAPY => <<<'TEXT'
For Physiotherapy:
- Use physiotherapy/rehab-compatible language.
- May organize around subjective report, objective findings, assessment, intervention, exercise plan, response, and follow-up.
- Do not invent ROM, strength grades, special tests, functional measures, exercises, home program details, or response.
TEXT,
            default => <<<'TEXT'
For General Wellness:
- Use neutral, plain, flexible visit-note language.
- Avoid discipline-specific terminology unless already present in the note.
- Do not make the note feel like insurance documentation.
TEXT,
        };

        return $base."\n\n".$specific;
    }

    public static function intakeAiInstructions(?string $practiceType): string
    {
        $base = <<<'TEXT'
Intake summary safety rules:
- Treat intake content as patient-reported information.
- Do not invent facts.
- Do not diagnose.
- Do not create a treatment plan.
- Do not recommend points, adjustments, exercises, techniques, herbs, supplements, or procedures.
- Do not convert patient statements into verified clinician findings.
- Use patient-reported framing such as "Patient reports...", "Patient mentions...", "Needs practitioner review...", and "Consider clarifying...".
- If information is missing, suggest a question instead of asserting an answer.
- Keep the summary concise and useful.
- Organize around patient-reported main concerns, relevant patient-reported history, medications/allergies/contraindications mentioned, functional impact or goals if present, safety concerns or red-flag statements for practitioner review, and questions to clarify.
TEXT;

        $specific = match (self::normalize($practiceType)) {
            self::TCM_ACUPUNCTURE => <<<'TEXT'
For TCM Acupuncture intake:
- May suggest clarifying questions around sleep, digestion, stress, temperature, pain quality, menstrual history when relevant, and symptom patterns.
- Do not invent tongue or pulse.
- Do not assign TCM pattern diagnosis.
- Do not suggest points or herbs.
TEXT,
            self::FIVE_ELEMENT_ACUPUNCTURE => <<<'TEXT'
For Five Element Acupuncture intake:
- May suggest clarifying questions around patient story, emotional context, life impact, rapport, and themes in presentation.
- May remind the practitioner to observe color/sound/odor/emotion during the visit if relevant.
- Do not assign CF or element.
- Do not invent color, sound, odor, emotion, Officials, or treatment intention.
- Do not translate patient story into TCM diagnosis.
TEXT,
            self::CHIROPRACTIC => <<<'TEXT'
For Chiropractic intake:
- May suggest clarifying mechanism of injury, pain behavior, functional limits, neurological symptoms, prior imaging/treatment, and red flags.
- Do not invent exam findings, orthopedic tests, neurological findings, adjustment plan, or diagnosis.
TEXT,
            self::MASSAGE_THERAPY => <<<'TEXT'
For Massage Therapy intake:
- May suggest clarifying areas of concern, pressure preference, contraindications, injuries, medications, comfort boundaries, and goals.
- Do not invent tissue findings, pressure level, techniques, contraindications, or response.
TEXT,
            self::PHYSIOTHERAPY => <<<'TEXT'
For Physiotherapy intake:
- May suggest clarifying function, pain behavior, activity goals, prior therapy, home exercises, red flags, and measurable limitations.
- Do not invent ROM, strength grades, special tests, functional measures, exercise prescription, or diagnosis.
TEXT,
            default => <<<'TEXT'
For General Wellness intake:
- Use plain language.
- Avoid specialty framing unless already present.
- Focus on patient goals, concerns, history, and questions to clarify.
TEXT,
        };

        return $base."\n\n".$specific;
    }
}
