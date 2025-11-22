<div class="container mt-5">
    <h1><?php echo htmlspecialchars($note['title'] ?? 'Nota'); ?></h1>
    <div><?php echo $htmlContent; ?></div>
</div>

<script>
    window.MathJax = {
        tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] },
        svg: { fontCache: 'global' }
    };
</script>
<script id="MathJax-script" async src="vendor/mathjax/tex-svg.js"></script>
