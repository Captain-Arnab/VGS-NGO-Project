<?php
$pageTitle = 'Add Blog Post';
$extraCss = ['https://cdn.quilljs.com/1.3.7/quill.snow.css'];
$extraJs = ['https://cdn.quilljs.com/1.3.7/quill.min.js'];
require_once dirname(__DIR__) . '/includes/header.php';

$id = get_int('id');
$post = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM blogs WHERE id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) {
        redirect('blogs/index.php', null, 'Post not found.');
    }
    $pageTitle = 'Edit Blog Post';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = post_string('title', 255);
    $slug = post_string('slug', 255) ?: slugify($title);
    $author = post_string('author', 150);
    $category = $_POST['category'] ?? 'News';
    $tags = post_string('tags', 255);
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $status = $_POST['status'] ?? 'Draft';
    $published_at = $_POST['published_at'] ?: null;

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if ($slug === '') {
        $errors[] = 'Slug is required.';
    }

    $featured = $post['featured_image'] ?? null;
    try {
        if (!empty($_FILES['featured_image']['name'])) {
            if ($featured) {
                delete_upload($featured);
            }
            $featured = upload_file($_FILES['featured_image'], 'blogs', ['jpg', 'jpeg', 'png', 'webp']);
        }
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    if (empty($errors)) {
        try {
            if ($id) {
                $pdo->prepare('UPDATE blogs SET title=?, slug=?, content=?, excerpt=?, featured_image=?, author=?, category=?, tags=?, status=?, published_at=? WHERE id=?')
                    ->execute([$title, $slug, $content, $excerpt ?: null, $featured, $author ?: null, $category, $tags ?: null, $status, $published_at, $id]);
                redirect('blogs/index.php', 'Post updated.');
            } else {
                $pdo->prepare('INSERT INTO blogs (title, slug, content, excerpt, featured_image, author, category, tags, status, published_at) VALUES (?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$title, $slug, $content, $excerpt ?: null, $featured, $author ?: null, $category, $tags ?: null, $status, $published_at]);
                redirect('blogs/index.php', 'Post created.');
            }
        } catch (PDOException $ex) {
            if ($ex->getCode() == 23000) {
                $errors[] = 'Slug already exists. Please use a unique slug.';
            } else {
                $errors[] = 'Could not save post.';
            }
        }
    }
    stash_form_errors($errors);
    $post = array_merge($post ?? [], $_POST);
}

$p = $post ?? ['status' => 'Draft', 'category' => 'News', 'published_at' => date('Y-m-d')];
?>

<div class="page-header-row">
    <a href="<?= base_url('blogs/index.php') ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card card-shadow form-card p-4">
    <form method="post" enctype="multipart/form-data" class="js-prevent-double" id="blogForm">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="blog_title" class="form-control" required value="<?= e($p['title'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" id="blog_slug" class="form-control" value="<?= e($p['slug'] ?? '') ?>">
            </div>
            <div class="col-md-4"><label class="form-label">Author</label><input type="text" name="author" class="form-control" value="<?= e($p['author'] ?? '') ?>"></div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <?php foreach (['News','Story','Announcement','Campaign','Other'] as $cat): ?>
                    <option value="<?= $cat ?>" <?= ($p['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['Draft','Published','Archived'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($p['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Tags (comma separated)</label><input type="text" name="tags" class="form-control" value="<?= e($p['tags'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Publish Date</label><input type="text" name="published_at" class="form-control flatpickr" value="<?= e($p['published_at'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label">Excerpt</label><textarea name="excerpt" class="form-control" rows="2"><?= e($p['excerpt'] ?? '') ?></textarea></div>
            <div class="col-12">
                <label class="form-label">Content</label>
                <div id="quillEditor"><?= $p['content'] ?? '' ?></div>
                <input type="hidden" name="content" id="blogContent">
            </div>
            <div class="col-md-6">
                <label class="form-label">Featured Image</label>
                <input type="file" name="featured_image" class="form-control file-upload-preview" accept="image/*">
                <?php if (!empty($p['featured_image'])): ?>
                <img src="<?= base_url('uploads/' . e($p['featured_image'])) ?>" class="thumb-sm mt-2" alt="">
                <?php endif; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-accent mt-4"><span class="btn-text"><?= $id ? 'Update' : 'Publish' ?> Post</span></button>
    </form>
</div>

<?php
$inlineJs = "
var quill = new Quill('#quillEditor', { theme: 'snow', modules: { toolbar: [[{header:[1,2,3,false]}],['bold','italic','underline'],['link','image'],[{list:'ordered'},{list:'bullet'}]] } });
document.getElementById('blogForm').addEventListener('submit', function() {
  document.getElementById('blogContent').value = quill.root.innerHTML;
});
";
require_once dirname(__DIR__) . '/includes/footer.php';
