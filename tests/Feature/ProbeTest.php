<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProbeTest extends TestCase
{
    public function test_page_titles_and_ordering(): void
    {
        foreach (['/resorts', '/booking'] as $url) {
            $html = $this->get($url)->getContent();
            preg_match('/<title>(.*?)<\/title>/s', $html, $t);

            $stylePos = strpos($html, '.src-bar {');
            $headEnd = strpos($html, '</head>');
            $scriptPos = strpos($html, 'const rootOf');
            $bodyEnd = strpos($html, '</body>');
            $sectionPos = strpos($html, 'data-src ');

            dump([
                'url' => $url,
                'title' => $t[1] ?? null,
                'style_in_head' => $stylePos !== false && $stylePos < $headEnd,
                'script_before_body_end' => $scriptPos !== false && $scriptPos < $bodyEnd,
                'script_after_section' => $scriptPos > $sectionPos,
                'aria_label_on_tablist' => preg_match('/role="tablist" aria-label="([^"]*)"/', $html, $a) ? $a[1] : 'none',
            ]);
        }
    }
}
