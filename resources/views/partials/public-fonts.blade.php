{{-- Shared: Google Fonts (Lora + DM Sans) and public design system CSS --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'DM Sans', sans-serif; }
    h1, h2, h3 { font-family: 'Lora', Georgia, serif; }
    .price-num { font-family: 'Lora', Georgia, serif; }
    /* Accordion */
    .acc-body    { max-height: 0; overflow: hidden; transition: max-height 0.32s ease; }
    .acc-body.open { max-height: 800px; }
    .acc-chevron { transition: transform 0.32s ease; }
    .acc-chevron.open { transform: rotate(180deg); }
    /* Thin section separator */
    .art-hr { border: none; border-top: 1px solid #e8e8e4; }
</style>
