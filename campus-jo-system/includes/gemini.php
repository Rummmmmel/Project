<?php
/* AI priority + recommendation.
   Sends ONLY the issue description to Gemini (never the reporter's name or email).
   If the API fails or no key is set, a simple keyword fallback is used so reports still work. */
require_once __DIR__ . '/config.php';

function keyword_classify(string $text): array {
    $t = mb_strtolower($text);
    $has = fn(array $w) => (bool) array_filter($w, fn($k) => str_contains($t, $k));

    if     ($has(['light','ilaw','outlet','socket','wire','kuryente','aircon','air con','ac ','fan','spark','breaker'])) $cat = 'Electrical';
    elseif ($has(['leak','faucet','toilet','drain','tubig','flush','pipe','tumutulo','clogged','baradong'])) $cat = 'Plumbing';
    elseif ($has(['projector','computer','wifi','internet','monitor','printer','keyboard'])) $cat = 'IT Equipment';
    elseif ($has(['dirty','trash','garbage','cockroach','ipis','mop','kalat','marumi'])) $cat = 'Cleanliness';
    elseif ($has(['chair','door','window','desk','table','railing','locker','ceiling','hinge','upuan','pinto'])) $cat = 'Carpentry';
    else $cat = 'Other';

    if     ($has(['fire','sunog','nasusunog','spark','sparking','exposed wire','smoke','usok','gas','flood','baha','collapse','electrocute','short circuit','injur'])) $pri = 'High';
    elseif ($has(['leak','tumutulo','broken','sira','not working','hindi gumagana','malfunction','no water'])) $pri = 'Medium';
    else $pri = 'Low';

    return ['category'=>$cat, 'priority'=>$pri, 'team'=>team_for_ai($cat), 'reason'=>'Keyword rules (AI unavailable)', 'source'=>'rules'];
}
function team_for_ai(string $c): string {
    return ['Electrical'=>'Electrical Team','Plumbing'=>'Plumbing Team','Carpentry'=>'Carpentry Team','Cleanliness'=>'Cleaning Team','IT Equipment'=>'IT Support'][$c] ?? 'General Maintenance';
}

function classify_report(string $description): array {
    $fallback = keyword_classify($description);
    if (GEMINI_API_KEY === '' || str_starts_with(GEMINI_API_KEY, 'YOUR_')) return $fallback;

    $prompt = "You help a campus maintenance office. Read the report (it may be English, Filipino or Taglish).\n"
        . "Return JSON only with keys: category, priority, team, reason.\n"
        . "category must be one of: " . implode(', ', CATEGORIES) . ".\n"
        . "priority must be one of: Low, Medium, High. Use High for safety risks (fire, sparks, exposed wires, flooding, gas, collapse risk), Medium for broken things that block normal use, Low for minor or cosmetic issues.\n"
        . "team must be one of: " . implode(', ', TEAMS) . ".\n"
        . "reason is one short sentence.\n\nReport: " . json_encode($description, JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . GEMINI_API_KEY],
        CURLOPT_POSTFIELDS => json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.1],
        ]),
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($res === false || $code !== 200) { error_log('Gemini failed: HTTP ' . $code . ' ' . curl_error($ch) . ' ' . substr((string)$res, 0, 200)); curl_close($ch); return $fallback; }
    curl_close($ch);

    $text = json_decode($res, true)['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $ai = json_decode(preg_replace('/```json|```/', '', $text), true);

    // never trust the AI output blindly: accept only allowed values
    if (!is_array($ai) || !in_array($ai['category'] ?? '', CATEGORIES, true) || !in_array($ai['priority'] ?? '', PRIORITIES, true)) return $fallback;
    $team = in_array($ai['team'] ?? '', TEAMS, true) ? $ai['team'] : team_for_ai($ai['category']);

    return ['category'=>$ai['category'], 'priority'=>$ai['priority'], 'team'=>$team, 'reason'=>mb_substr((string)($ai['reason'] ?? ''), 0, 250), 'source'=>'gemini'];
}
