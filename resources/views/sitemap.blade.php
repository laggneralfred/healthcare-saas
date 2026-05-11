{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($urls as $url)
    <url>
        <loc>{{ $url }}</loc>
        <lastmod>{{ $lastmod }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>{{ $url === 'https://practiqapp.com/' ? '1.0' : '0.8' }}</priority>
    </url>
@endforeach
</urlset>
