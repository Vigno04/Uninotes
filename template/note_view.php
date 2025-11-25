<link rel="stylesheet" href="vendor/github-markdown/github-markdown.css">

<div class="container mt-5">
    <h1><?php echo htmlspecialchars($note['title'] ?? 'Nota'); ?></h1>
    <div class="markdown-body"><?php echo $htmlContent; ?></div>
</div>

<script>
    window.MathJax = {
        tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] },
        svg: { fontCache: 'global' }
    };
</script>
<script id="MathJax-script" async src="vendor/mathjax/tex-svg.js"></script>
