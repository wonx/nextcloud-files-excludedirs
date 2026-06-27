<div id="files-excludedirs-settings" class="section">
    <h2>Exclude Directories</h2>
    <p>Configure which directories should be ignored by the Nextcloud file scanner. <strong>Write one pattern per line.</strong></p>
    
    <p style="font-size: 13px; color: #555; margin-bottom: 12px;">
        Supports exact folders (e.g., <code>node_modules</code>), relative paths (e.g., <code>Documents/Temp</code>), and wildcards (e.g., <code>*.tmp</code> or <code>temp/*/logs</code>).
    </p>
    
    <textarea id="excludedirs-patterns" rows="6" style="width: 100%; max-width: 600px; font-family: monospace;"></textarea>
    <br /><br />
    
    <!-- Reordered & re-styled buttons -->
    <button id="excludedirs-preview" class="button">Preview Changes</button>
    <button id="excludedirs-save" class="button primary">Save Patterns</button>
    <button id="excludedirs-cleanup" class="button">Run Database Cleanup</button>

    <!-- This box will display the preview/cleanup results -->
    <div id="excludedirs-results" style="margin-top: 15px; display: none; background: #f8f8f8; padding: 15px; border-radius: 5px; border: 1px solid #ccc; max-width: 600px;">
        <h3 id="excludedirs-results-title" style="margin-top: 0;">Results</h3>
        <pre id="excludedirs-results-content" style="white-space: pre-wrap; font-size: 12px;"></pre>
    </div>

    <!-- Friendly explanation guide at the bottom -->
    <div style="margin-top: 25px; border-top: 1px solid #ddd; padding-top: 15px; font-size: 13px; color: #555; max-width: 600px;">
        <p><strong>Button Guide:</strong></p>
        <ul style="list-style-type: disc; margin-left: 20px; padding-left: 0;">
            <li style="margin-bottom: 5px;"><strong>Preview Changes (Dry-Run):</strong> Safely scans your database using the patterns shown above (even if unsaved) and displays matching files without making any database changes.</li>
            <li style="margin-top: 5px; margin-bottom: 5px;"><strong>Save Patterns:</strong> Commits your current list of patterns to Nextcloud's system configuration.</li>
            <li style="margin-top: 5px;"><strong>Run Database Cleanup:</strong> Instantly deletes all database entries matching your active patterns.</li>
        </ul>
    </div>
</div>