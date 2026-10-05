<?php

namespace App\Http\Middleware;

use App\Support\LineIcons;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Minimalist look: every page is sent with simple line icons instead of colored emoji
 * (e.g. 📅 becomes a thin calendar drawing). The pages themselves are not changed, so the
 * original design files stay as they are; only the finished HTML is adjusted.
 *  - Only normal web pages (HTML) are changed. Downloads, CSV files and JSON are not touched.
 *  - Text inside <title>, <option> and <textarea> cannot show drawings, so the emoji is just removed there.
 *  - <script> and <style> blocks and everything inside a tag (e.g. alt or aria-label text) are left alone.
 */
class EmojiToLineIcons
{
    // Icon size and color. Icons inside the round/square icon boxes of the design are orange.
    private const STYLE = '<style>'
        . '.ui-icon{width:1.1em;height:1.1em;vertical-align:-0.17em;flex-shrink:0;}'
        . '.logo .ui-icon,.admin-stat-icon,.superadmin-stat-icon,.admin-summary-icon,.superadmin-action-icon,.admin-action-icon,'
        . '.pet-avatar,.pet-icon,.history-icon,.history-pet-icon,.profile-avatar,.profile-detail>span,.forgot-icon,.logout-icon,'
        . '.dashboard-icon,.option-icon,.account-type-icon,.modal-icon,.login-modal-icon,.no-history-icon,.history-empty-icon,'
        . '.help-box>span,.services>div,.activity-empty>span,.summary-box>span{color:#e89427;}'
        . '.activity-icon{color:var(--tone-text,#e89427);}'
        . '</style>';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $type = (string) $response->headers->get('Content-Type');
        $content = $response->getContent();
        if (! str_contains($type, 'text/html') || ! is_string($content) || $content === '') {
            return $response;
        }

        // keep the original view object: Laravel features (and the tests) still look at it
        $original = $response instanceof \Illuminate\Http\Response ? $response->getOriginalContent() : null;
        $response->setContent($this->convert($content));
        if ($original !== null) {
            $response->original = $original;
        }

        return $response;
    }

    public function convert(string $html): string
    {
        $pattern = LineIcons::pattern();
        $withSpace = substr($pattern, 0, -2) . '\s?/u';   // the emoji and the space after it
        $skip = null;   // inside <script>/<style> (left alone) or <title>/<option>/<textarea> (emoji removed)
        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $i => $part) {
            if ($part === '') {
                continue;
            }

            // inside <script> or <style>: wait for its closing tag, change nothing
            if ($skip === 'script' || $skip === 'style') {
                if (preg_match('#^</' . $skip . '\b#i', $part)) {
                    $skip = null;
                }
                continue;
            }

            if ($part[0] === '<') {
                if (preg_match('#^<(/?)(script|style|title|option|textarea)\b#i', $part, $tag)) {
                    $skip = $tag[1] === '/' ? null : strtolower($tag[2]);
                }
                continue;
            }

            if ($skip === null) {
                $parts[$i] = preg_replace_callback($pattern, fn ($m) => LineIcons::svg($m[0]) ?? $m[0], $part);
            } else {
                // e.g. <title>🐾 FMH</title> becomes <title>FMH</title>
                $parts[$i] = preg_replace($withSpace, '', $part);
            }
        }

        $html = implode('', $parts);

        // the icon style goes once into the <head> of the page
        return str_contains($html, '</head>') ? preg_replace('#</head>#i', self::STYLE . '</head>', $html, 1) : $html;
    }
}
