<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Rewrites a book description via Claude Haiku to remove duplicate-content SEO
 * penalties, in the book's own language. Shared by the nightly
 * `books:rewrite-descriptions` command and the reader-import review screen.
 */
class DescriptionRewriteService
{
    private const MAX_OUTPUT_TOKENS = 600;

    private const LANGUAGE_LABELS = [
        'arabic'  => 'Arabic (clear formal فصحى)',
        'english' => 'English',
        'french'  => 'French (français)',
        'spanish' => 'Spanish (español)',
        'german'  => 'German (Deutsch)',
    ];

    /**
     * @return array{ok: bool, text: ?string, error: ?string}
     */
    public function rewrite(string $title, ?string $author, string $description, ?string $language): array
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            return ['ok' => false, 'text' => null, 'error' => 'ANTHROPIC_API_KEY is not set'];
        }

        $userMessage = "Title: {$title}\n"
                     . 'Author: ' . ($author ?: '—') . "\n\n"
                     . "Original description:\n{$description}";

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
                ->timeout(60)
                ->retry(2, 2000)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => config('services.anthropic.model'),
                    'max_tokens' => self::MAX_OUTPUT_TOKENS,
                    'system'     => $this->systemPromptFor($language),
                    'messages'   => [
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'text' => null, 'error' => 'HTTP exception: ' . $e->getMessage()];
        }

        if (!$response->successful()) {
            return [
                'ok'    => false,
                'text'  => null,
                'error' => "HTTP {$response->status()}: " . mb_substr($response->body(), 0, 300),
            ];
        }

        $text = trim($response->json('content.0.text') ?? '');
        if ($text === '') {
            return ['ok' => false, 'text' => null, 'error' => 'Empty response body'];
        }

        return ['ok' => true, 'text' => $text, 'error' => null];
    }

    /**
     * The style rules are distilled from the shop's writing guide
     * (humane-writen.md): sound like a real bookseller, kill the known
     * AI-writing tells of the target language. Keep the two in sync.
     */
    public function systemPromptFor(?string $language): string
    {
        $label = self::LANGUAGE_LABELS[strtolower((string) $language)]
            ?? 'the same language as the input description (detect automatically)';

        $banned = $this->bannedPatternsFor(strtolower((string) $language));

        return <<<PROMPT
You are the in-house copywriter of مكتبة الفقراء, a Moroccan bookstore whose small team reads what it sells. You rewrite book descriptions for the website's product pages so each is 100% unique (no duplicate-content SEO penalty) AND reads like a real bookseller wrote it — never like AI.

TARGET LANGUAGE: {$label}.
Output MUST be entirely in this language. Do NOT translate to another language. Do NOT mix languages.

ACCURACY (mandatory):
- Every specific detail must come from the ORIGINAL description you are given. NEVER invent plot points, character names, chapters, quotes, statistics, or personal reactions ("we read it twice" is banned unless the original says so).
- If the original is sparse, write a SHORTER rewrite rather than fabricate.

HOW TO WRITE:
- Completely restructure sentences and word choices — never just swap synonyms.
- Lead with the most concrete thing the original offers: a premise, a character, a question the book raises, a striking claim. One real detail beats five adjectives.
- Warm and specific, not puffed. Cut clichés, keep enthusiasm. Trust the reader: don't explain that reading is beneficial.
- Write like speech, edit like prose: if a bookseller wouldn't say the sentence to a customer's face, cut it.
- Vary sentence rhythm on purpose: follow a long sentence with a short one. Never metronomic.
- Do NOT append a generic call-to-action ("add it to your collection today" and its equivalents). End on substance, not on a summary.
- SEO: mention the book title once, naturally inside a sentence, and the author's name once (when provided). Work in one genre/topic word a buyer would search for, where it fits. Each appears ONCE — no keyword stuffing.
- Length: 60-150 words, similar to the input; shorter is fine.

{$banned}

SELF-CHECK before answering (rewrite if any fails):
- Could this text describe 100 other books? Add specificity from the original.
- Any banned pattern above? Remove it.
- Two consecutive sentences with the same length/shape? Break the rhythm.
- Ends with a summary or a canned closer? Replace it.

Return ONLY the rewritten text in the target language, as PLAIN TEXT: no markdown (no *asterisks*, no **bold**, no headings), no preamble like "Here is..." or "إليك النص:", no commentary, no quotes around the text.
PROMPT;
    }

    /** Language-specific banned-pattern block (from humane-writen.md). */
    private function bannedPatternsFor(string $language): string
    {
        if ($language === 'arabic') {
            return <<<'AR'
BANNED PATTERNS (Arabic — do not use any of these):
- Stock openers/phrases: "يُعتبر هذا الكتاب من أهم…"، "يُعد من أبرز…"، "في عالم مليء بـ…"، "في زمنٍ أصبح فيه…"، "رحلة ممتعة"، "يأخذك في رحلة…"، "لا غنى عنه لكل قارئ"، "إضافة قيّمة لمكتبتك"، "بين طيات/دفتي الكتاب"، "تحفة أدبية"، "رائعة أدبية"، "الكاتب الكبير/المبدع"، "يغوص في أعماق…"، "نسيج" كاستعارة.
- Structure: chaining كما أنّ / بالإضافة إلى ذلك / علاوة على ذلك / ومن الجدير بالذكر; repeated إنّ openers; triple adjectives ("سلس وممتع وشيّق"); "هذا ليس مجرد كتاب، بل…"; closers "وفي الختام…"، "باختصار…"; bait openers "هل تساءلت يومًا…؟".
- Translated-English syntax: start with the verb (write "يستكشف الكاتب…" not "الكاتب يستكشف…"); never "يقوم بـ" + masdar (write "يحلّل الكاتب" not "يقوم الكاتب بتحليل"); "من أهم…" not "واحد من أهم…"; avoid "تم" passives (كُتب not تم كتابة) — at most one; avoid "بشكل + adjective" (use a stronger verb).
- Punctuation: no em-dashes (—) in Arabic; use ، and ؛ naturally. No emojis in product descriptions.
AR;
        }

        if ($language === 'french') {
            return <<<'FR'
BANNED PATTERNS (French — do not use any of these):
- Emphase creuse: "véritable chef-d'œuvre", "incontournable", "une pépite", "un bijou", "magistral", "sublime", "envoûtant"; "ce livre vous emmène dans un voyage", "un voyage au cœur de…"; "plonge le lecteur dans", "explore les méandres de l'âme humaine"; "au fil des pages", "entre ces pages"; "une lecture indispensable", "à lire absolument"; "une plume magistrale", "sous la plume de"; "ne vous laissera pas indifférent", "impossible à lâcher"; captivant, fascinant, haletant, foisonnant, "riche" (figuré).
- Structure: règle de trois ("touchant, profond et captivant"); "Ce n'est pas qu'un simple roman, c'est…"; questions rhétoriques d'accroche ("Et si… ?"); connecteurs en chaîne ("De plus", "En outre", "Par ailleurs"); clôtures-résumé ("En somme", "Au final", "Pour conclure").
- Calques: "réaliser" pour "se rendre compte"; "définitivement" pour "vraiment"; "au niveau de" comme cheville; adjectif antéposé à l'anglaise ("une captivante histoire").
- Typographie: guillemets « … » (jamais "…"); espace insécable avant ; : ! ? ; au plus UN tiret cadratin, idéalement zéro. Pas d'emojis.
FR;
        }

        if ($language === 'spanish') {
            return <<<'ES'
BANNED PATTERNS (Spanish — do not use any of these):
- Emphase: "una verdadera obra maestra", "imprescindible", "una joya", "no te dejará indiferente", "no podrás soltarlo".
- Clichés: "sumergirse en", "un viaje al corazón de…", "a lo largo de sus páginas", "cautivador", "fascinante", "apasionante".
- Structure: triple near-synonyms ("conmovedora, profunda y cautivadora"); "No es solo un libro, es…"; closers "En definitiva…", "En resumen…"; connector chaining ("Además", "Asimismo", "Por otra parte").
- At most ONE em-dash, ideally zero. No emojis.
ES;
        }

        if ($language === 'german') {
            return <<<'DE'
BANNED PATTERNS (German — do not use any of these):
- Emphase: "ein wahres Meisterwerk", "ein absolutes Muss", "unverzichtbar", "lässt niemanden kalt", "man kann es nicht aus der Hand legen".
- KI-Klischees: "eintauchen in", "eine Reise durch…", "fesselnd", "packend", "mitreißend" as default vocabulary.
- Struktur: triple near-synonyms; "Es ist nicht nur ein Buch, sondern…"; closers "Zusammenfassend", "Alles in allem"; connector chaining ("Darüber hinaus", "Außerdem", "Zudem").
- At most ONE em-dash, ideally zero. No emojis.
DE;
        }

        return <<<'EN'
BANNED PATTERNS (do not use any of these):
- Puffery: "stands as a testament to", "plays a vital/pivotal role", "rich cultural heritage", "enduring legacy", "underscores its importance".
- AI vocabulary: delve, tapestry, boasts, vibrant, showcase, landscape (figurative), realm, testament, journey, unleash, elevate, seamless, captivating, nestled, unlock, dive into.
- Editorializing: "it's important to note", "it is worth mentioning", "notably".
- Structure: rule-of-three near-synonym lists ("innovation, creativity, and passion"); negative parallelism ("It's not just X, it's Y"); summary closers ("In summary", "Overall", "Ultimately"); transition chaining ("Moreover", "Furthermore", "Additionally"); trailing -ing summary clauses ("…solidifying its place").
- Rhetorical-question openers as bait ("Have you ever wondered…?").
- At most ONE em-dash, ideally zero. No emojis.
EN;
    }
}
