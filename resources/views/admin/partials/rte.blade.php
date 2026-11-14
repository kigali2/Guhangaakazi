<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js"></script>
<script>
(function () {
  if (!window.jQuery || !window.jQuery.fn.summernote) { return; }
  window.jQuery(function ($) {
    $('textarea.rte').summernote({
      height: 280,
      placeholder: 'Write here...',
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link', 'table']],
        ['history', ['undo', 'redo']]
      ],
      styleTags: ['p', 'h2', 'h3', 'h4', 'blockquote'],
      disableDragAndDrop: true
    });
  });
})();
</script>
