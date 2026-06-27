function initExcludeDirsSettings() {
    const textarea = document.getElementById('excludedirs-patterns');
    const btnSave = document.getElementById('excludedirs-save');
    const btnPreview = document.getElementById('excludedirs-preview');
    const btnCleanup = document.getElementById('excludedirs-cleanup');
    const resultsBox = document.getElementById('excludedirs-results');
    const resultsContent = document.getElementById('excludedirs-results-content');
    const resultsTitle = document.getElementById('excludedirs-results-title');

    // 1. If Nextcloud hasn't rendered the HTML yet, wait 200ms and try again
    if (!textarea || !btnSave) {
        setTimeout(initExcludeDirsSettings, 200);
        return;
    }

    // Prevent double-binding if Nextcloud reloads the view
    if (textarea.dataset.initialized) return;
    textarea.dataset.initialized = "true";

    // 2. Fetch existing patterns when the page loads
    fetch(OC.generateUrl('/apps/files_excludedirs/api/settings'), {
        headers: { requesttoken: OC.requestToken }
    })
    .then(res => res.json())
    .then(data => {
        textarea.value = data.patterns.join('\n');
    });

    function getPatternsArray() {
        return textarea.value.split('\n').map(p => p.trim()).filter(p => p !== '');
    }

    // 3. Save Patterns
    btnSave.addEventListener('click', function() {
        btnSave.textContent = "Saving...";
        fetch(OC.generateUrl('/apps/files_excludedirs/api/settings'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken },
            body: JSON.stringify({ patterns: getPatternsArray() })
        })
        .then(res => res.json())
        .then(data => {
            btnSave.textContent = "Save Patterns";
            //OC.Notification.showTemporary('Exclusion patterns saved successfully!');
            OCP.Toast.success('Exclusion patterns saved successfully!');
        });
    });

    // 4. Preview Cleanup (Dry-Run)
    btnPreview.addEventListener('click', function() {
        btnPreview.textContent = "Loading preview...";
        fetch(OC.generateUrl('/apps/files_excludedirs/api/preview'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken },
            body: JSON.stringify({ patterns: getPatternsArray() })
        })
        .then(res => res.json())
        .then(data => {
            btnPreview.textContent = "Preview Changes";
            resultsBox.style.display = 'block';
            resultsTitle.textContent = "Dry-Run Preview";
            
            if (data.count === 0) {
                resultsContent.textContent = "No matching paths found in the database. It is already clean!";
            } else {
                let text = `Found ${data.count} matching database records.\n\nSample paths:\n`;
                data.paths.forEach(path => text += `- ${path}\n`);
                if (data.count > data.paths.length) {
                    text += `... and ${data.count - data.paths.length} more.`;
                }
                resultsContent.textContent = text;
            }
        });
    });

    // 5. Run Cleanup
    btnCleanup.addEventListener('click', function() {
        if (!confirm("Are you sure you want to delete these cached entries from the Nextcloud database?")) return;
        
        btnCleanup.textContent = "Cleaning...";
        fetch(OC.generateUrl('/apps/files_excludedirs/api/cleanup'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken },
            body: JSON.stringify({ patterns: getPatternsArray() })
        })
        .then(res => res.json())
        .then(data => {
            btnCleanup.textContent = "Run Database Cleanup";
            resultsBox.style.display = 'block';
            resultsTitle.textContent = "Cleanup Complete";
            resultsContent.textContent = `Successfully deleted ${data.deleted} cached file entries from the database!`;
            OCP.Toast.success('Database cleanup finished!');
        });
    });
}

// Start the polling immediately when the script loads
initExcludeDirsSettings();