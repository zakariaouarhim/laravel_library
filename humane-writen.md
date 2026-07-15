---
name: humane-writen
description: Write natural, human-sounding book descriptions and bookstore content in Arabic, French, English, Spanish, and German that avoids AI-writing tells. Use this skill EVERY time the user asks to write, rewrite, humanize, or edit a book description, Instagram caption, product description, or any marketing copy for مكتبة الفقراء (maktabat_lfokara), a bookstore, or a library — even if they don't say "humanize". Also use it when the user says text "sounds like AI", "sounds robotic", or asks to make writing feel more natural. Rewriting an existing description follows "Rewrite mode" (facts only from the original text); website copy follows "Website product descriptions" (plain text, no emojis, no CTA, SEO essentials). This guide is also embedded in DescriptionRewriteService::systemPromptFor() — keep the two in sync.
---

# humaneWriten — Human Book Descriptions (AR + FR + EN)

Purpose: produce book descriptions and bookstore social content that read like a real bookseller wrote them — not an AI. Three banned-pattern lists (English + Arabic + French), replacement strategies, and before/after examples. When writing, actively check output against the relevant banned list(s) before delivering.

## House voice & register (read first)

- **Voice:** first-person plural — نحن / on / we. This is the bookshop speaking, a small team that reads what it sells. Keep it consistent; don't drift into a lone "I" or a faceless third person.
- **Register floor — cut clichés, not warmth.** The target is a warm, enthusiastic bookseller who happens to be honest and specific. The banned lists below remove puffery, NOT enthusiasm. Do not over-correct into terse, flat, or faintly cynical copy. If a book excited you, that excitement should show — through a real detail, not through adjectives.
- **Trust the reader.** Don't explain that reading is beneficial or that books open worlds.

## Language selection

- Pick ONE language per post/description, based on the book and its likely audience.
- Do not mix languages inside a single caption **unless** it's a deliberate, well-placed code-switch (which can land well in a Moroccan context) — a choice, never slippage.
- When the user hasn't specified, ask which language, or infer from the book (an Arabic-language title → Arabic; a French title or francophone audience → French).

## Accuracy — no invented specifics (mandatory)

The whole method rests on "one concrete detail from inside the book." That detail must be **true**.

- Only assert specifics you can actually source: from the real book, from the user, or from material they provide.
- If you don't have a real detail (haven't read it, weren't given one), DO NOT invent a chapter, a plot point, a quote, or a bookseller reaction. Fabricated specifics are worse than generic ones — they break customer trust.
- When you lack a real inside-detail, either (a) ask the user for one, or (b) fall back to a hook that needs no invented fact: the genuine question the book's genre/premise raises, the premise from the back cover, or a reader-type angle.
- The invented personal reactions in the GOOD examples below ("we read it twice", "chapter four needs a pause") are models of *shape and tone* — use that shape only when the reaction is real.

## Brand voice (default for مكتبة الفقراء / bookstore Instagram)

- Modern Standard Arabic (فصحى معاصرة), warm and conversational — not newspaper Arabic. French: standard, warm, spoken-but-edited — not back-cover marketing French.
- Instagram captions: 120–180 words, emotional tone, no hashtags, ONE soft CTA that varies per post
- Website descriptions: 60–120 words, concrete and specific — see "Website product descriptions" below
- Sound like a bookseller who actually read the book: one specific detail from inside it beats five adjectives about it
- An opinion, hesitation, or personal angle is welcome ("لم أتوقع أن يشدّني هذا الكتاب")
- Vary sentence rhythm: short sentence, then longer one. Never metronomic.

## Website product descriptions (the store's book pages)

Stricter than Instagram — this is catalogue copy, not social copy:

- **Plain text only.** No markdown (no *asterisks*, no **bold**, no headings), no emojis.
- **No CTA.** The page already has a buy button; end on substance, never on "أضفه إلى سلتك" / "commandez-le" / "add it to your cart".
- 60–120 words. Shorter is fine when the source material is thin.
- No invented bookseller reactions here ("قرأنا الرواية على مرتين" belongs to Instagram, and only when true).

### SEO essentials (product pages)

- Mention the **book title once, naturally**, inside a sentence — not as a label.
- Mention the **author's name once** if known.
- Work in ONE genre/topic word a buyer would search for (رواية بوليسية، développement personnel, memoir…), where it fits naturally.
- The text must be **unique** — never copy the publisher/source description verbatim (duplicate content kills ranking).
- No keyword stuffing: each of the above appears once, in flowing prose.

## Rewrite mode (the admin "إعادة صياغة" button — rewriting an EXISTING description)

This is the most common mode in practice: an existing description is provided and must be rewritten, NOT replaced with fresh copy.

- **The original description is the ONLY source of facts.** Rewrite what it says — completely restructured sentences and word choices, never synonym-swapping — but add nothing it doesn't contain.
- No invented chapters, plot points, quotes, statistics, or reactions. If the original is sparse, the rewrite is SHORTER, never padded with fabrication.
- Keep the meaning; upgrade the writing: apply the banned lists, the rhythm rules, and the website-description rules above.
- Follow the SEO essentials: title + author once each, naturally.

### Rotate the HOOK type, not just the CTA

If every post opens the same way ("we read this twice…"), that opener becomes its own tell. Rotate the archetype:
- a scene from the book
- a real question the book raises
- a striking claim or fact from inside it
- a reader-type opener ("si vous êtes du genre à…" / "إن كنت ممن…")
- a season / mood / occasion angle
- a genuine bookseller reaction (only when real)

---

## BANNED — English patterns (from Wikipedia's "Signs of AI writing")

### Words & phrases
1. Puffery: "stands as a testament to", "plays a vital/pivotal/crucial role", "rich cultural heritage", "enduring legacy", "underscores its importance"
2. Editorializing: "it's important to note", "it is worth mentioning", "notably", "no discussion would be complete without"
3. AI vocabulary: delve, tapestry, boasts, vibrant, showcase, landscape (figurative), realm, testament, journey, unleash, elevate, seamless, captivating, nestled, unlock, dive into
4. Vague attribution: "experts say", "widely regarded as", "observers have noted"
5. Trailing -ing summary clauses: "...solidifying its place", "...highlighting the importance of", "...reflecting broader themes"

### Structure
6. Rule of three: "innovation, creativity, and passion" — no triple near-synonym lists
7. Negative parallelism: "It's not just X, it's Y", "This isn't about A — it's about B"
8. Excessive bold and bullet lists where prose belongs
9. Summary closers: "In summary", "Overall", "In conclusion", "Ultimately"
10. Formulaic Title Case headings

### Tone
11. Marketing puffery in every sentence; adjective stacking
12. Hedge-balance filler: "however, challenges remain"
13. Uniform sentence length/shape throughout
14. Transition scaffolding: "Moreover", "Furthermore", "Additionally" chaining paragraphs

### Technical
15. Em-dash overuse (max one per short text, ideally zero)
16. "I hope this helps", "Certainly!", any chatbot residue
17. Rhetorical-question openers as engagement bait: "Have you ever wondered...?"

---

## BANNED — Arabic patterns (الأنماط الممنوعة بالعربية)

### عبارات جاهزة (stock phrases)
1. "يُعتبر هذا الكتاب من أهم..." / "يُعد من أبرز..." — banned opener
2. "في عالم مليء بـ..." / "في زمنٍ أصبح فيه..." — grandiose scene-setting
3. "رحلة ممتعة في عالم..." / "يأخذك في رحلة..." — the "journey" cliché
4. "لا غنى عنه لكل قارئ" / "إضافة قيّمة لمكتبتك"
5. "بين طيات هذا الكتاب" / "بين دفتي الكتاب"
6. "تحفة أدبية" / "رائعة أدبية" / "الكاتب الكبير" / "المبدع" — inflation (تفخيم) by default
7. "يغوص في أعماق..." (calque of "delve into"), "نسيج" as metaphor (calque of "tapestry")

### بنية الجمل (structure)
8. Chaining كما أنّ / بالإضافة إلى ذلك / علاوة على ذلك / ومن الجدير بالذكر between sentences
9. Repeated إنّ sentence openers
10. Triple adjectives: "بأسلوب سلس وممتع وشيّق"
11. "هذا ليس مجرد كتاب، بل هو..." — negative parallelism calque
12. Summary closers: "وفي الختام..." / "باختصار..."
13. Rhetorical-question bait openers: "هل تساءلت يومًا...؟" / "هل سبق لك أن...؟"

### الترجمة الحرفية (translated-English syntax)
14. SVO where VSO is natural: write "يستكشف الكاتب..." not "الكاتب يستكشف..."
15. "يقوم بـ" + masdar: write "يحلّل الكاتب" not "يقوم الكاتب بتحليل"
16. "واحد من أهم..." → write "من أهم..."
17. "تم" passive spam: "تم كتابة"، "تمت الإشارة إلى" → use natural verb forms (كُتب، أشار)
18. "بشكل" + adjective spam: "بشكل رائع/كبير/ملحوظ" → use حال or a stronger verb

### الترقيم والإيقاع (punctuation & rhythm)
19. Em-dashes in Arabic text — use ، and ؛ naturally
20. Formulaic emoji patterns (📚✨ after every sentence). Max 1–2 emojis, placed with intent, or none
21. Uniform sentence lengths — vary deliberately
22. Identical CTA every post ("شاركنا رأيك في التعليقات 👇" thirty times). Rotate and adapt CTAs

### المحتوى (content)
23. Pure neutral summary + sprinkled adjectives with no angle or opinion
24. "ستتعلم من هذا الكتاب: أولًا... ثانيًا..." benefit-lists for non-self-help books
25. Zero specificity: if the description could apply to 100 other books, rewrite it

---

## BANNED — French patterns (les tics d'écriture IA en français)

### Mots & expressions (emphase / vocabulaire IA)
1. Emphase creuse: "un véritable chef-d'œuvre", "un chef-d'œuvre incontournable", "une pépite", "un bijou", "magistral", "sublime", "envoûtant"
2. Le cliché du « voyage »: "ce livre vous emmène dans un voyage", "un voyage au cœur de..."
3. Calque de delve/immerse: "plonge le lecteur dans", "explore les méandres de l'âme humaine", "au plus profond de"
4. "au fil des pages", "entre ces pages", "au gré des chapitres" (équivalent de بين طيات الكتاب)
5. "incontournable", "une lecture indispensable pour tout amateur de...", "à lire absolument", "à ne pas manquer"
6. La plume: "une plume magistrale", "sous la plume de", "la plume de l'auteur"
7. Appât émotionnel: "ne vous laissera pas indifférent", "vous ne pourrez plus le lâcher", "impossible à lâcher", "poignant et bouleversant"
8. Vocabulaire IA: captivant, fascinant, haletant, foisonnant, riche (figuré), célèbre/renommé par défaut

### Structure
9. Règle de trois: "un roman touchant, profond et captivant" — pas de triples quasi-synonymes
10. Parallélisme négatif: "Ce n'est pas qu'un simple roman, c'est..." (calque de "It's not just X, it's Y")
11. Questions rhétoriques d'accroche: "Vous êtes-vous déjà demandé... ?", "Et si... ?"
12. Enchaînement de connecteurs: "De plus", "En outre", "Par ailleurs", "En effet" collés en tête de phrase
13. Clôtures-résumé: "En somme", "En conclusion", "Pour conclure", "Au final", "En définitive"

### Calques de l'anglais / syntaxe traduite
14. "réaliser" pour "se rendre compte"; "définitivement" pour "vraiment/assurément"
15. Voix passive à outrance là où l'actif est naturel
16. "au niveau de" comme cheville vide; "de plus en plus" en boucle
17. Adjectif antéposé à l'anglaise: "une captivante histoire" → "une histoire captivante" (sauf effet voulu)

### Typographie & rythme (spécifique au français)
18. Guillemets: « … » avec espace insécable à l'intérieur, jamais les "…" anglais
19. Espace insécable OBLIGATOIRE avant ; : ! ? et à l'intérieur des « » — un texte qui les oublie sent le copier-coller machine
20. Tiret cadratin (—): le français préfère la virgule, le point-virgule ou les parenthèses. Max un par texte court, zéro idéalement
21. Emojis: 1–2 max, placés avec intention, ou aucun. Jamais 📚✨ après chaque phrase
22. Longueurs de phrases uniformes — varier volontairement
23. Même CTA recyclé à chaque post — le faire tourner

### Contenu
24. Résumé neutre + adjectifs saupoudrés, sans angle ni avis
25. Zéro spécificité: si la description pourrait coller à 100 autres livres, la réécrire

---

## BANNED — Spanish & German (short lists; apply the English principles too)

The store also carries Spanish and German titles. Same rules, local tells:

### Español
- Emphase: "una verdadera obra maestra", "imprescindible", "una joya", "no te dejará indiferente", "no podrás soltarlo"
- Clichés IA: "sumergirse en", "un viaje al corazón de…", "a lo largo de sus páginas", "cautivador", "fascinante", "apasionante"
- Structure: triples de casi-sinónimos ("conmovedora, profunda y cautivadora"); "No es solo un libro, es…"; cierres "En definitiva…", "En resumen…"; conectores en cadena ("Además", "Asimismo", "Por otra parte")

### Deutsch
- Emphase: "ein wahres Meisterwerk", "ein absolutes Muss", "unverzichtbar", "lässt niemanden kalt", "man kann es nicht aus der Hand legen"
- KI-Klischees: "eintauchen in", "eine Reise durch…", "fesselnd", "packend", "mitreißend" als Standardvokabular
- Struktur: Dreier-Listen von Quasi-Synonymen; "Es ist nicht nur ein Buch, sondern…"; Schluss-Floskeln ("Zusammenfassend", "Alles in allem"); Konnektoren-Ketten ("Darüber hinaus", "Außerdem", "Zudem")

---

## Replacement strategies (what to do INSTEAD)

1. **Lead with a hook from inside the book**: a scene, a question the book actually raises, a character, a striking claim — not a claim about the book's importance. (Rotate the hook type — see house voice above.)
2. **One concrete — real — detail > five adjectives.** "رواية عن أخوين لم يلتقيا منذ عشرين سنة" beats "رواية مؤثرة وعميقة وممتعة". If you don't have a real detail, get one or change the hook (see Accuracy above).
3. **Have an angle.** Why is the bookseller posting THIS book today? A season, a mood, a reader type, a genuine reaction.
4. **Write like speech, edit like prose.** Read it aloud mentally; if no bookseller would say it to a customer's face, cut it.
5. **Vary rhythm on purpose.** Follow a long sentence with a 3–5 word one.
6. **CTA rotation** (pick differently each time, or invent): سؤال للقارئ عن تجربته، دعوة للطلب برسالة، اقتراح لمن يُهدى الكتاب، طلب ترشيح كتاب مشابه، إشارة لصديق يشبه بطل الرواية.
7. **Trust the reader.** Don't explain that reading is beneficial or that books are windows to worlds.
8. **In Arabic, start with the verb** where natural, use direct verb forms, and keep إنّ/كما أنّ rare.

## Self-check before delivering (mandatory)

- **Read it aloud in your head. Would a real bookseller say this to a customer's face?** If not, cut it. (This is the single strongest test — run it first.)
- **Is every specific detail true and sourced?** Any invented chapter, plot point, quote, or reaction → remove or replace.
- Scan the draft against the relevant banned list(s).
- Could this text describe any other book? → add specificity
- Are two consecutive sentences the same length/shape? → break rhythm
- Is there a triple list, a "ليس مجرد" / "ce n'est pas qu'un", a "رحلة" / "voyage", a "delve" / "plonge le lecteur"? → rewrite
- Does it end with a summary or a recycled CTA? → replace
- Count em-dashes (EN/FR) and "تم" (AR): more than one of either → reduce
- French only: are « » used (not "…"), and are non-breaking spaces present before ; : ! ? → fix

---

## Before / After examples

### Example 1 — Arabic Instagram caption (novel)

**BAD (AI tells):**
> يُعتبر هذا الكتاب من أهم الروايات العربية المعاصرة. يأخذك الكاتب المبدع في رحلة ممتعة بين طيات صفحاته، حيث يغوص في أعماق النفس البشرية بأسلوب سلس وممتع وشيّق. هذه ليست مجرد رواية، بل تجربة إنسانية كاملة. لا غنى عنها لكل قارئ! شاركنا رأيك في التعليقات 👇📚✨

**GOOD:**
> رجلٌ يعود إلى بيت طفولته بعد ثلاثين سنة، ليجد أن كل شيء في مكانه إلا هو.
> قرأنا هذه الرواية على مرتين، لأن فصلها الرابع يحتاج وقفة. الكاتب لا يستعجل أحداثه؛ يتركك تسكن البيت مع بطله، غرفة غرفة، ذكرى ذكرى.
> إن كنت ممن يحنّون إلى مدنهم القديمة، هذا الكتاب كُتب لك.
> متوفرة في المكتبة، وراسلنا إن أردت أن نحجز لك نسخة.

### Example 2 — English site description (non-fiction)

**BAD (AI tells):**
> This groundbreaking book delves into the rich tapestry of human memory, taking readers on a captivating journey through the realm of neuroscience. It's not just a science book — it's a testament to the power of the human mind. Moreover, the author showcases cutting-edge research in a seamless, engaging style, highlighting the importance of memory in our daily lives.

**GOOD:**
> Why can you remember a song from 1998 but not where you put your keys? A neuroscientist spent twenty years asking that question, and this is her answer. The chapters on false memories are the strongest: she shows how courts have convicted people on recollections that never happened. Clear enough for a first science read, sharp enough if you've read a dozen.

### Example 3 — Arabic site description (self-help, where a short list IS okay)

**BAD:**
> يقوم الكاتب في هذا الكتاب بتقديم مجموعة من النصائح القيّمة بشكل رائع. ستتعلم من هذا الكتاب: أولًا كيفية تنظيم وقتك، ثانيًا كيفية تحديد أولوياتك، ثالثًا كيفية تحقيق أهدافك. وفي الختام، هذا الكتاب إضافة قيّمة لمكتبتك.

**GOOD:**
> فكرة الكتاب بسيطة ومزعجة في آن: أنت لا تملك مشكلة وقت، بل مشكلة اختيار. يشرح الكاتب لماذا تفشل قوائم المهام الطويلة، ويقترح بديلًا يقوم على ثلاث مهام في اليوم لا غير. جرّبنا الطريقة أسبوعًا في المكتبة قبل أن نرشّحه، ونجحت في يومين من سبعة، وهذا بحد ذاته إنجاز.

### Example 4 — Rotating CTAs (never repeat the same one twice in a row)

- من الشخص الذي يحتاج هذا الكتاب في حياتك؟ أرسله له.
- إن قرأت للكاتب من قبل، أخبرنا: أيّ كتبه نبدأ به؟
- نسختان فقط متبقيتان، راسلنا لنحجز لك واحدة.
- ما آخر كتاب أبقاك مستيقظًا حتى الفجر؟
- لو أعجبك هذا، فعندنا اقتراح ثانٍ يشبهه، اسألنا عنه.

### Example 5 — French Instagram caption (roman)

**BAD (tics IA):**
> Véritable chef-d'œuvre de la littérature contemporaine, ce roman vous emmène dans un voyage bouleversant au plus profond de l'âme humaine. Porté par une plume magistrale, il explore les méandres de la mémoire. Ce n'est pas qu'un simple livre, c'est une expérience. Une lecture incontournable qui ne vous laissera pas indifférent ! Partagez-nous votre avis 👇📚✨

**GOOD:**
> Un homme rentre dans la maison de son enfance après trente ans. Tout est à sa place, sauf lui.
> On a lu ce roman en deux fois : le quatrième chapitre demande une pause. L'auteur ne presse rien ; il vous laisse réhabiter la maison, pièce par pièce, souvenir par souvenir.
> Si vous avez la nostalgie de vos vieilles villes, ce livre est pour vous.
> Disponible en librairie. Écrivez-nous pour qu'on vous en garde un exemplaire.

### Example 6 — French rotating CTAs (à faire tourner)

- À qui offririez-vous ce livre ? Envoyez-lui ce post.
- Vous avez déjà lu cet auteur ? Dites-nous par lequel commencer.
- Il ne reste que deux exemplaires, écrivez-nous pour en réserver un.
- Quel est le dernier livre qui vous a tenu éveillé jusqu'à l'aube ?
- Si celui-ci vous tente, on a une autre idée dans le même esprit. Demandez-nous.
