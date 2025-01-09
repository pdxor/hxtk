jQuery(document).ready(function($) {
    let editor = null;
    let currentFile = null;
    let hasUnsavedChanges = false;

    // Initialize Monaco Editor
    require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.33.0/min/vs' }});
    require(['vs/editor/editor.main'], function() {
        editor = monaco.editor.create(document.getElementById('htk-code-editor'), {
            value: '// Start coding here...',
            language: 'javascript',
            theme: 'vs-dark',
            minimap: {
                enabled: true
            },
            automaticLayout: true,
            fontSize: 14,
            lineNumbers: 'on',
            scrollBeyondLastLine: false,
            renderWhitespace: 'selection',
            tabSize: 4,
            insertSpaces: true
        });

        // Handle content changes
        editor.onDidChangeModelContent(function() {
            hasUnsavedChanges = true;
        });

        // Handle language changes
        $('#htk-language-select').on('change', function() {
            const language = $(this).val();
            monaco.editor.setModelLanguage(editor.getModel(), language);
        });
    });

    // Template Management
    $('.htk-template-item').on('click', function() {
        if (hasUnsavedChanges) {
            if (!confirm(htkDev.strings.confirmDiscard)) {
                return;
            }
        }

        const $template = $(this);
        const templateId = $template.data('id');
        const category = $template.data('category');

        $.ajax({
            url: htkDev.ajaxurl,
            type: 'POST',
            data: {
                action: 'htk_load_template',
                nonce: htkDev.nonce,
                template_id: templateId,
                category: category
            },
            success: function(response) {
                if (response.success) {
                    editor.setValue(response.data.content);
                    $('#htk-language-select').val(response.data.language).trigger('change');
                    currentFile = templateId;
                    hasUnsavedChanges = false;
                    logToConsole('Template loaded successfully', 'success');
                } else {
                    logToConsole('Failed to load template: ' + response.data, 'error');
                }
            },
            error: function() {
                logToConsole('Failed to load template', 'error');
            }
        });
    });

    // Save Code
    $('.htk-save-code').on('click', function() {
        const code = editor.getValue();
        const language = $('#htk-language-select').val();
        const filename = generateFilename(currentFile, language);

        $.ajax({
            url: htkDev.ajaxurl,
            type: 'POST',
            data: {
                action: 'htk_save_code',
                nonce: htkDev.nonce,
                code: code,
                language: language,
                filename: filename
            },
            success: function(response) {
                if (response.success) {
                    hasUnsavedChanges = false;
                    logToConsole('Code saved to: ' + response.data.path, 'success');
                } else {
                    logToConsole('Failed to save code: ' + response.data, 'error');
                }
            },
            error: function() {
                logToConsole('Failed to save code', 'error');
            }
        });
    });

    // Git Integration
    $('.htk-git-btn').on('click', function() {
        const action = $(this).data('action');
        
        if (action === 'commit') {
            $('#htk-git-modal').show();
            return;
        }

        performGitOperation(action);
    });

    $('#htk-commit-form').on('submit', function(e) {
        e.preventDefault();
        
        const message = $('#commit_message').val();
        performGitOperation('commit', message);
        $('#htk-git-modal').hide();
        $('#commit_message').val('');
    });

    function performGitOperation(action, message = '') {
        $.ajax({
            url: htkDev.ajaxurl,
            type: 'POST',
            data: {
                action: 'htk_git_operation',
                nonce: htkDev.nonce,
                git_action: action,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    $('.htk-git-output').html(response.data.message);
                    logToConsole('Git operation successful: ' + action, 'success');
                } else {
                    $('.htk-git-output').html(response.data.message);
                    logToConsole('Git operation failed: ' + response.data.message, 'error');
                }
            },
            error: function() {
                logToConsole('Git operation failed', 'error');
            }
        });
    }

    // Console Management
    function logToConsole(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const $console = $('.htk-console-output');
        const logEntry = `[${timestamp}] ${message}\n`;
        
        $console.append($('<span>', {
            class: type,
            text: logEntry
        }));

        // Auto-scroll to bottom
        $console.scrollTop($console[0].scrollHeight);
    }

    $('.htk-clear-console').on('click', function() {
        $('.htk-console-output').empty();
    });

    // Modal Management
    $('.htk-modal-close').on('click', function() {
        $(this).closest('.htk-modal').hide();
    });

    $(window).on('click', function(e) {
        if ($(e.target).hasClass('htk-modal')) {
            $('.htk-modal').hide();
        }
    });

    // Utility Functions
    function generateFilename(base, language) {
        const timestamp = new Date().getTime();
        const extension = getFileExtension(language);
        return `${base || 'code'}_${timestamp}${extension}`;
    }

    function getFileExtension(language) {
        const extensions = {
            'javascript': '.js',
            'csharp': '.cs',
            'cpp': '.cpp',
            'python': '.py'
        };
        return extensions[language] || '.txt';
    }

    // Window Events
    $(window).on('beforeunload', function() {
        if (hasUnsavedChanges) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
}); 