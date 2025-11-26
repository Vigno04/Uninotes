<link rel="stylesheet" href="vendor/github-markdown/github-markdown.css">

<div class="container mt-5">
    <h1 class="mb-4"><?php echo htmlspecialchars($note['title'] ?? 'Nota'); ?></h1>
    <div class="card">
        <div class="card-body">
            <div class="markdown-body"><?php echo $htmlContent; ?></div>
        </div>
    </div>
</div>

<script>
    window.MathJax = {
        tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] },
        svg: { fontCache: 'global' }
    };
</script>
<script id="MathJax-script" async src="vendor/mathjax/tex-svg.js"></script>
