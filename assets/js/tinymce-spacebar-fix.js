/**
 * TinyMCE Spacebar Fix
 */
document.addEventListener("DOMContentLoaded", function() {
    if (typeof tinymce === "undefined") {
        setTimeout(arguments.callee, 100);
        return;
    }
    
    tinymce.on("AddEditor", function(e) {
        e.editor.on("init", function() {
            const editor = this;
            editor.on("keydown", function(e) {
                if (e.keyCode === 32 || e.which === 32 || e.key === " ") {
                    e.stopImmediatePropagation();
                    return true;
                }
            }, true);
        });
    });
});