<?php
/**
 * GET/POST /user_FE/theme/heritage/chatbot/ask.php?id=<id_path>&q=<câu hỏi>
 * So khớp câu hỏi với kho Q&A theo từ khoá/độ trùng từ. Trả {answer, matched, score}.
 *
 * Ghi chú: đây là bộ so khớp từ khoá đơn giản (rule-based), không gọi LLM.
 * Có thể nâng cấp sang Claude API ở Phase 5 nếu cần hiểu ngữ nghĩa.
 */
require_once dirname(__DIR__, 4) . '/core/response.php';
require_once dirname(__DIR__, 4) . '/core/i18n.php';
require_once dirname(__DIR__, 4) . '/core/ratelimit.php';

// Chống lạm dụng: tối đa 30 câu hỏi / phút / IP
rate_limit_enforce('chatbot', 30, 60);

$idPath = trim((string)($_REQUEST['id'] ?? ''));
$tour = $idPath !== '' ? tour_by_path($idPath) : null;
if (!$tour) {
    json_out(['answer' => null, 'matched' => false]);
}

$q = mb_strtolower(trim((string)($_REQUEST['q'] ?? '')));
if ($q === '') {
    json_error('Thiếu câu hỏi (q)');
}

// Tách từ (bỏ dấu câu), loại từ quá ngắn
$norm = fn(string $s): string => preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', mb_strtolower($s));
$qTokens = array_filter(preg_split('/\s+/', $norm($q)), fn($w) => mb_strlen($w) >= 2);

$rows = db_all('SELECT question, answer, keywords FROM chatbot_qa WHERE tour_id = ?', [(int)$tour['id']]);

$best = null;
$bestScore = 0.0;
foreach ($rows as $r) {
    $haystack = $norm($r['question'] . ' ' . (string)$r['keywords']);
    $hayTokens = array_filter(preg_split('/\s+/', $haystack), fn($w) => mb_strlen($w) >= 2);
    if (!$hayTokens) {
        continue;
    }
    // Điểm = số từ truy vấn xuất hiện trong câu hỏi/từ khoá
    $hit = 0;
    foreach ($qTokens as $t) {
        if (in_array($t, $hayTokens, true) || mb_strpos($haystack, $t) !== false) {
            $hit++;
        }
    }
    $score = $qTokens ? $hit / count($qTokens) : 0;
    // Cộng điểm nếu khớp cụm từ khoá nguyên văn
    foreach (explode(',', (string)$r['keywords']) as $kw) {
        $kw = trim(mb_strtolower($kw));
        if ($kw !== '' && mb_strpos($q, $kw) !== false) {
            $score += 0.5;
        }
    }
    if ($score > $bestScore) {
        $bestScore = $score;
        $best = $r;
    }
}

if ($best && $bestScore >= 0.4) {
    json_out(['answer' => $best['answer'], 'matched' => true, 'score' => round($bestScore, 2)]);
}

json_out([
    'answer'  => 'Xin lỗi, tôi chưa có thông tin cho câu hỏi này. Bạn thử hỏi cách khác nhé.',
    'matched' => false,
    'score'   => round($bestScore, 2),
]);
