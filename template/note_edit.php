<link rel="stylesheet" href="vendor/github-markdown/github-markdown.css">

<?php



// COurse
$courseFilter = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : null;

$courseOptions = [];
$sqlCourseOptions = "
    SELECT DISTINCT c.id, c.name
    FROM course c
    JOIN course_offering co ON co.course_id = c.id
    JOIN topic t ON t.offering_id = co.id
    JOIN note n ON n.topic_id = t.id AND n.status = 'published'
    ORDER BY c.name
";
$resultCourseOptions = mysqli_query($conn, $sqlCourseOptions);
if ($resultCourseOptions) {
    while ($row = mysqli_fetch_assoc($resultCourseOptions)) {
        $courseOptions[] = $row;
    }
}

// Topic
$topicFilter = isset($_GET['topic_id']) && $_GET['topic_id'] !== '' ? (int)$_GET['topic_id'] : null;


$topicOptions = [];
$sqlTopicOptions = "
    SELECT DISTINCT t.id, t.name
    FROM topic t
    ORDER BY t.name
";

$resultTopicOptions = mysqli_query($conn, $sqlTopicOptions);
if ($resultTopicOptions) {
    while ($row = mysqli_fetch_assoc($resultTopicOptions)) {
        $topicOptions[] = $row;
    }
}

?>



<!-- Success/Error Messages -->
<?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <?php echo htmlspecialchars($successMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <?php echo htmlspecialchars($errorMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Mobile Preview Toggle Button (visible only on mobile) -->
<div class="d-lg-none mb-3 mt-3">
    <div class="btn-group w-100" role="group">
        <button type="button" class="btn btn-outline-primary active" id="btn-editor-mobile" onclick="showMobileView('editor')">
            <i class="bi bi-pencil me-1"></i> Editor
        </button>
        <button type="button" class="btn btn-outline-primary" id="btn-preview-mobile" onclick="showMobileView('preview')" <?php echo !$showPreview ? 'disabled' : ''; ?>>
            <i class="bi bi-eye me-1"></i> Preview
        </button>
    </div>
</div>

<div class="row mt-4">
    <!-- Editor Column -->
    <div class="col-12 col-lg-6" id="editor-column">
        <h1 class="h4 mb-3">
            <?php echo $mode === 'edit' ? 'Edit Note' : 'Create New Note'; ?>
        </h1>
        <p class="text-muted small mb-4">
            Select the course and topic, then write the content in Markdown.
        </p>

        <div class="card" id="editor-card">
            <div class="card-body">
                <form method="POST" action="index.php?page=note_edit<?php echo $noteId ? '&id=' . $noteId : ''; ?>" enctype="multipart/form-data">
                    <?php if ($noteId): ?>
                        <input type="hidden" name="note_id" value="<?php echo htmlspecialchars((string)$noteId); ?>">
                    <?php endif; ?>


                                <!-- Corso (Esame) -->
                    <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Course (Exam) *</label>
                    <select class="form-select" name="course" required>
                        <option value="" disabled selected>Select course</option>
                        <?php foreach ($courseOptions as $course): ?>
                            <option value="<?php echo (int)$course['id']; ?>"
                                <?php echo ($courseFilter === (int)$course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    </div>

                    <!-- Topic selection -->
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Topic *</label>
                        <select class="form-select" name="topic" required>
                        <option value="" disabled selected>Select topic</option>
                        <?php foreach ($topicOptions as $topic): ?>
                            <option value="<?php echo (int)$topic['id']; ?>"
                                <?php echo ($topicFilter === (int)$topic['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($topic['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    </div>

                    <hr class="my-4">

                    <!-- Note title -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input
                            type="text"
                            class="form-control"
                            id="title"
                            name="title"
                            value="<?php echo htmlspecialchars($titleValue); ?>"
                            placeholder="e.g. Notes Lecture 3 - Algorithms"
                            required
                        >
                    </div>

                    <!-- Note content (Markdown) -->
                    <div class="mb-3">
                        <label for="content" class="form-label">Content (Markdown) *</label>
                        <textarea
                            class="form-control"
                            id="content"
                            name="content"
                            rows="15"
                            placeholder="Write your notes here in Markdown..."
                            required
                        ><?php echo htmlspecialchars($contentValue); ?></textarea>
                    </div>

                    <!-- Upload attached files -->
                    <div class="mb-3">
                        <label for="attachments" class="form-label">Attached Files</label>
                        <input
                            type="file"
                            class="form-control"
                            id="attachments"
                            name="attachments[]"
                            multiple
                        >
                        <div class="form-text">
                            Upload files to attach to this note. After saving, you can reference them in Markdown.
                        </div>
                    </div>

                    <!-- List of existing uploaded files -->
                    <?php if (!empty($existingFiles)): ?>
                    <div class="mb-3">
                        <label class="form-label">Uploaded Files</label>
                        <div class="list-group">
                            <?php foreach ($existingFiles as $file): ?>
                                <?php 
                                    $isImage = str_starts_with($file['mime_type'] ?? '', 'image/');
                                    $markdownString = $isImage 
                                        ? '![Image description](' . htmlspecialchars($file['filename']) . ')'
                                        : '[Download file](' . htmlspecialchars($file['filename']) . ')';
                                ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">
                                                <?php if ($isImage): ?>
                                                    <i class="bi bi-image me-1"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-file-earmark me-1"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($file['filename']); ?>
                                            </div>
                                            <div class="mt-1">
                                                <small class="text-muted">Include in Markdown:</small>
                                                <code class="d-block bg-light p-2 mt-1 rounded user-select-all" style="font-size: 0.85em;">
                                                    <?php echo $markdownString; ?>
                                                </code>
                                            </div>
                                        </div>
                                        <div class="ms-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteFile(<?php echo (int)$file['id']; ?>, '<?php echo htmlspecialchars(addslashes($file['filename'])); ?>')"
                                                    title="Delete file">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Buttons -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <?php if ($noteStatus === 'published'): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Published</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-file-earmark me-1"></i>Draft</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="index.php?page=home" class="btn btn-outline-secondary">Cancel</a>
                            <?php if ($noteStatus === 'published'): ?>
                                <button type="submit" name="action" value="save" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Save
                                </button>
                            <?php else: ?>
                                <button type="submit" name="action" value="save" class="btn btn-outline-primary">
                                    <i class="bi bi-save me-1"></i>Save Draft
                                </button>
                                <button type="submit" name="action" value="publish" class="btn btn-success">
                                    <i class="bi bi-send me-1"></i>Publish
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <!-- Hidden form for file deletion (outside the main form) -->
                <?php if ($noteId): ?>
                <form id="delete-file-form" method="POST" action="index.php?page=note_edit&id=<?php echo $noteId; ?>" style="display: none;">
                    <input type="hidden" name="note_id" value="<?php echo htmlspecialchars((string)$noteId); ?>">
                    <input type="hidden" name="delete_file_id" id="delete-file-id" value="">
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Preview Column (visible on desktop, toggleable on mobile) -->
    <div class="col-12 col-lg-6" id="preview-column">
        <div class="preview-sticky-wrapper">
            <h2 class="h4 mb-3">Preview</h2>
            <p class="text-muted small mb-4">
                This preview updates when you save the note.
            </p>
            
            <div class="card" id="preview-card">
                <div class="card-body preview-scrollable">
                    <?php if ($showPreview): ?>
                        <div class="markdown-body">
                            <?php echo $previewHtml; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted text-center py-5">
                            <i class="bi bi-eye-slash fs-1 d-block mb-3"></i>
                            <p>Save the note to see a preview here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script id="MathJax-script" async src="vendor/mathjax/tex-svg.js"></script>
<script>
// Delete file function
function deleteFile(fileId, filename) {
    if (confirm('Are you sure you want to delete "' + filename + '"? This action cannot be undone.')) {
        document.getElementById('delete-file-id').value = fileId;
        document.getElementById('delete-file-form').submit();
    }
}

// Course offering change handler
document.getElementById('course_offering').addEventListener('change', function() {
    const offeringId = this.value;
    const topicSelect = document.getElementById('topic');
    
    if (offeringId) {
        topicSelect.disabled = false;
        topicSelect.innerHTML = '<option value="" disabled selected>Select topic</option>';
        
        fetch(`get_topics.php?offering_id=${offeringId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                data.forEach(topic => {
                    const option = document.createElement('option');
                    option.value = topic.id;
                    option.textContent = topic.name;
                    topicSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching topics:', error));
    } else {
        topicSelect.disabled = true;
        topicSelect.innerHTML = '<option value="" disabled selected>Select topic</option>';
    }
});

// Mobile view toggle
function showMobileView(view) {
    const editorColumn = document.getElementById('editor-column');
    const previewColumn = document.getElementById('preview-column');
    const btnEditor = document.getElementById('btn-editor-mobile');
    const btnPreview = document.getElementById('btn-preview-mobile');
    
    if (view === 'preview') {
        editorColumn.classList.add('hide-mobile');
        previewColumn.classList.add('show-mobile');
        btnEditor.classList.remove('active');
        btnPreview.classList.add('active');
    } else {
        editorColumn.classList.remove('hide-mobile');
        previewColumn.classList.remove('show-mobile');
        btnEditor.classList.add('active');
        btnPreview.classList.remove('active');
    }
}
</script>

<style>
/* Desktop preview - sticky and scrollable */
@media (min-width: 992px) {
    .preview-sticky-wrapper {
        position: sticky;
        top: 1rem;
    }
    .preview-scrollable {
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }
}

/* Mobile view toggle */
@media (max-width: 991.98px) {
    #preview-column {
        display: none;
    }
    #preview-column.show-mobile {
        display: block !important;
    }
    #editor-column.hide-mobile {
        display: none !important;
    }
}
</style>

<script>
// Match preview height to editor height on desktop
function matchPreviewHeight() {
    if (window.innerWidth >= 992) {
        const editorCard = document.getElementById('editor-card');
        const previewScrollable = document.querySelector('.preview-scrollable');
        if (editorCard && previewScrollable) {
            const editorHeight = editorCard.offsetHeight;
            // Subtract approximate header height (title + description)
            previewScrollable.style.maxHeight = (editorHeight - 60) + 'px';
        }
    }
}

// Run on load and resize
window.addEventListener('load', matchPreviewHeight);
window.addEventListener('resize', matchPreviewHeight);
</script>
