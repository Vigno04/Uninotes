<?php
require_once "bootstrap.php";
require_once "db/NoteModel.php";

// Check if user is logged in
if (!isset($_SESSION["person_id"])) {
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION["person_id"];
$noteModel = new NoteModel();

// Detect if we are editing an existing note via GET parameter
$noteId   = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$mode     = $noteId ? 'edit' : 'create';

// Placeholder values for now (no DB)
$selectedCourse        = '';
$selectedCourseOffering = '';
$selectedTopic         = '';
$titleValue            = '';
$contentValue          = '';

$courseOfferings = [];
$userOfferings = $noteModel->getUserFollowedOfferings($userId);
foreach ($userOfferings as $offering) {
    $label = $offering['name'] . ' - Academic Year ' . $offering['year'] . '/' . ($offering['year'] + 1) . ' - ' . ($offering['semester'] == 1 ? 'First' : 'Second') . ' semester';
    $courseOfferings[$offering['id']] = $label;
}

$topics = [];
if ($selectedCourseOffering) {
    $topicsData = $noteModel->getTopicsByOfferingId($selectedCourseOffering);
    foreach ($topicsData as $topic) {
        $topics[$topic['id']] = $topic['name'];
    }
}

// If editing, fetch note data
if ($mode === 'edit') {
    $note = $noteModel->getById($noteId);
    if (!$note || $note['owner_id'] != $userId) {
        // Cannot edit: note not found or not owned, fall back to create mode
        $mode = 'create';
        $noteId = null;
        $selectedCourseOffering = '';
        $selectedTopic = '';
        $titleValue = '';
        $contentValue = '';
        $topics = [];
    } else {
        $titleValue = $note['title'];
        $contentValue = $note['content'];
        $selectedTopic = $note['topic_id'];
        // Get offering from topic
        $offering = $noteModel->getOfferingByTopicId($selectedTopic);
        if ($offering) {
            $selectedCourseOffering = $offering['id'];
            // Load topics for this offering
            $topicsData = $noteModel->getTopicsByOfferingId($selectedCourseOffering);
            foreach ($topicsData as $topic) {
                $topics[$topic['id']] = $topic['name'];
            }
        }
    }
}

?>

<div class="row justify-content-center mt-4">
    <div class="col-12 col-lg-10 col-xl-9">
        <h1 class="h4 mb-3">
            <?php echo $mode === 'edit' ? 'Edit Note' : 'Create New Note'; ?>
        </h1>
        <p class="text-muted small mb-4">
            Select the course and topic, then write the content in Markdown.
        </p>

        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <!-- If in the future it will be necessary to distinguish create/edit -->
                    <?php if ($noteId): ?>
                        <input type="hidden" name="note_id" value="<?php echo htmlspecialchars((string)$noteId); ?>">
                    <?php endif; ?>

                    <!-- 2. Course offering selection -->
                    <div class="mb-3">
                        <label for="course_offering" class="form-label">Course *</label>
                        <select class="form-select" id="course_offering" name="course_offering" required>
                            <option value="" disabled <?php echo $selectedCourseOffering === '' ? 'selected' : ''; ?>>Select offering</option>
                            <?php foreach ($courseOfferings as $id => $label): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>" <?php echo ($selectedCourseOffering === $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 3. Topic selection -->
                    <div class="mb-3">
                        <label for="topic" class="form-label">Topic *</label>
                        <select class="form-select" id="topic" name="topic" required <?php echo !$selectedCourseOffering ? 'disabled' : ''; ?>>
                            <option value="" disabled <?php echo $selectedTopic === '' ? 'selected' : ''; ?>>Select topic</option>
                            <?php foreach ($topics as $id => $label): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>" <?php echo ($selectedTopic === $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
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
                            rows="10"
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
                            After uploading the files, you can refer to them in the Markdown text using the file name.
                            For example: <code>![Image description](filename.png)</code> or <code>[Download file](document.pdf)</code>.
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Quick Markdown guide -->
                    <div class="mb-3">
                        <h2 class="h6 mb-2">Quick Markdown Guide</h2>
                        <div class="row small">
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Headings</strong><br>
                                <code># Heading 1</code><br>
                                <code>## Heading 2</code><br>
                                <code>### Heading 3</code>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Text</strong><br>
                                <code>**bold**</code><br>
                                <code>*italic*</code><br>
                                <code>~~strikethrough~~</code>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Lists</strong><br>
                                <code>- item</code><br>
                                <code>1. numbered item</code>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Links and Images</strong><br>
                                <code>[link text](https://example.com)</code><br>
                                <code>![alt image](image.png)</code>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Code</strong><br>
                                <code>`inline`</code><br>
                                <code>```<br>code block<br>```</code>
                            </div>
                        </div>
                    </div>

                    <!-- Only visual buttons for now (no real submit) -->
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="index.php?page=home" class="btn btn-outline-secondary">Cancel</a>
                        <button type="button" class="btn btn-primary" disabled>Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('course_offering').addEventListener('change', function() {
    const offeringId = this.value;
    const topicSelect = document.getElementById('topic');
    
    if (offeringId) {
        topicSelect.disabled = false;
        // Clear current topics
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
</script>
