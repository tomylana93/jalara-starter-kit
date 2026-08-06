{{--
    The running footer.

    Chromium renders header and footer templates in their own document, so this
    view inherits nothing from the page it sits under - not the stylesheet, not
    the font size, not even a sensible default margin. The styles are therefore
    written inline here. That duplication is acceptable only because the content
    is this small; anything richer belongs in the body of the document.

    `pageNumber` and `totalPages` are Chromium's own classes: it substitutes the
    values, so nothing has to be passed in.
--}}
<div style="width: 100%; padding: 0 12mm; font-family: sans-serif; font-size: 8pt; color: #6b6b6b; text-align: right;">
    <span class="pageNumber"></span> / <span class="totalPages"></span>
</div>
