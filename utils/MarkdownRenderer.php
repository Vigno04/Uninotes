<?php

require_once __DIR__ . '/../vendor/parsedown/Parsedown.php';
require_once __DIR__ . '/../vendor/parsedown/ParsedownExtra.php';
require_once __DIR__ . '/../vendor/parsedown/ParsedownExtended.php';

use BenjaminHoegh\ParsedownExtended\ParsedownExtended;

/**
 * MarkdownRenderer - A utility class for rendering Markdown content to HTML.
 * 
 * This class wraps ParsedownExtended to provide enhanced Markdown rendering
 * with support for MathJax, code highlighting, tables, and more.
 */
class MarkdownRenderer
{
    /**
     * @var object The Parsedown parser instance
     */
    private $parser;

    /**
     * Create a new MarkdownRenderer instance.
     * 
     * @param array $options Configuration options for ParsedownExtended
     */
    public function __construct(array $options = [])
    {
        // Default configuration optimized for notes
        $defaultOptions = [
            'math' => [
                'enabled' => true,
                'inline' => [
                    'delimiters' => [
                        ['left' => '$', 'right' => '$'],
                        ['left' => '\\(', 'right' => '\\)']
                    ],
                ],
                'block' => [
                    'delimiters' => [
                        ['left' => '$$', 'right' => '$$'],
                        ['left' => '\\[', 'right' => '\\]']
                    ],
                ],
            ],
            'code' => [
                'blocks' => true,
                'inline' => true,
            ],
            'tables' => [
                'tablespan' => true,
            ],
            'footnotes' => true,
            'abbreviations' => [
                'allow_custom' => true,
                'predefined' => [],
            ],
            'emojis' => true,
            'emphasis' => [
                'bold' => true,
                'italic' => true,
                'strikethroughs' => true,
                'insertions' => true,
                'subscript' => true,
                'superscript' => true,
                'keystrokes' => true,
                'mark' => true,
            ],
            'headings' => [
                'allowed_levels' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
                'auto_anchors' => [
                    'delimiter' => '-',
                    'lowercase' => true,
                ],
                'special_attributes' => true,
            ],
            'lists' => [
                'tasks' => true,
            ],
            'toc' => [
                'levels' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
                'tag' => '[TOC]',
                'id' => 'toc',
            ],
            'diagrams' => [
                'enabled' => true,
                'chartjs' => true,
                'mermaid' => true,
            ],
            'alerts' => [
                'types' => ['note', 'tip', 'important', 'warning', 'caution'],
                'class' => 'markdown-alert',
            ],
            // Allow raw HTML entities to render properly
            'allow_raw_html' => true,
        ];

        // Merge user options with defaults
        $mergedOptions = array_replace_recursive($defaultOptions, $options);

        $this->parser = new ParsedownExtended($mergedOptions);
        
        // Disable safe mode to allow proper rendering of code blocks and special characters
        // Note: User content should be sanitized at input time, not rendering time
        $this->parser->setSafeMode(false);
        
        // Enable markup escaping to prevent XSS while still allowing proper rendering
        $this->parser->setMarkupEscaped(true);
    }

    /**
     * Render Markdown content to HTML.
     * 
     * @param string $markdown The Markdown content to render
     * @return string The rendered HTML
     */
    public function render(string $markdown): string
    {
        return $this->parser->text($markdown);
    }

    /**
     * Render Markdown content and replace file references with secure URLs.
     * 
     * @param string $markdown The Markdown content to render
     * @param array $fileMap Map of filename => ['id' => fileId, 'mime' => mimeType]
     * @return string The rendered HTML with replaced file URLs
     */
    public function renderWithFiles(string $markdown, array $fileMap): string
    {
        $htmlContent = $this->render($markdown);

        if (empty($fileMap)) {
            return $htmlContent;
        }

        // Use DOM manipulation to replace file references with secure URLs
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Handle images
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if (isset($fileMap[$src])) {
                $img->setAttribute('src', 'file.php?id=' . $fileMap[$src]['id']);
            }
        }

        // Handle links (for downloadable files)
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (isset($fileMap[$href])) {
                $link->setAttribute('href', 'file.php?id=' . $fileMap[$href]['id']);
                // Add download attribute for non-images
                if (!str_starts_with($fileMap[$href]['mime'] ?? '', 'image/')) {
                    $link->setAttribute('download', '');
                }
            }
        }

        return $dom->saveHTML();
    }

    /**
     * Get the Table of Contents from the parsed content.
     * 
     * @return string The HTML Table of Contents
     */
    public function getTableOfContents(): string
    {
        return $this->parser->contentsList();
    }

    /**
     * Enable or disable safe mode.
     * 
     * @param bool $safeMode Whether to enable safe mode
     * @return self
     */
    public function setSafeMode(bool $safeMode): self
    {
        $this->parser->setSafeMode($safeMode);
        return $this;
    }

    /**
     * Enable or disable automatic line breaks.
     * 
     * @param bool $breaksEnabled Whether to enable automatic line breaks
     * @return self
     */
    public function setBreaksEnabled(bool $breaksEnabled): self
    {
        $this->parser->setBreaksEnabled($breaksEnabled);
        return $this;
    }

    /**
     * Generate the file list HTML for attached files.
     * 
     * @param array $files Array of file records with 'id' and 'filename' keys
     * @return string The HTML for the file list
     */
    public static function generateFileList(array $files): string
    {
        if (empty($files)) {
            return '';
        }

        $fileList = '<div class="attached-files mt-4"><h3>File Allegati</h3><ul>';
        foreach ($files as $file) {
            $url = 'file.php?id=' . $file['id'];
            $fileList .= '<li><a href="' . htmlspecialchars($url) . '" download>' . htmlspecialchars($file['filename']) . '</a></li>';
        }
        $fileList .= '</ul></div>';

        return $fileList;
    }
}
