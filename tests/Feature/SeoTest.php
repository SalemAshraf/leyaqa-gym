<?php

it('serves robots.txt pointing at the sitemap and shielding private areas', function () {
    $body = $this->get('/robots.txt')->assertOk()->headers->get('content-type');
    expect($body)->toContain('text/plain');

    $content = $this->get('/robots.txt')->getContent();
    expect($content)->toContain('Allow: /')
        ->toContain('Disallow: /platform')
        ->toContain('Disallow: /my/')
        ->toContain('Disallow: /card/')
        ->toContain('Sitemap: '.url('/sitemap.xml'));
});

it('serves a sitemap with the public pages', function () {
    $content = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($content)->toContain(url('/'))
        ->toContain(url('/login'))
        ->toContain(url('/register'));
});

it('ships crawler-visible description and social tags in the document head', function () {
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('name="description"')
        ->toContain('property="og:title"')
        ->toContain('rel="canonical"')
        ->not->toContain('Leyaqa');
});
