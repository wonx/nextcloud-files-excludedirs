# Exclude Directories Plugin for Nextcloud
Excludes directories and files from being added to the Nextcloud file cache.

- **For files not yet in Nextcloud that match an ignored path:** The files will
  be skipped and will not appear in Nextcloud after future file scans.
- **For files already in Nextcloud that match an ignored path:** The files will
  appear in search results, but users will be blocked from accessing them. If a
  user attempts to open a blocked directory, they will be blocked from creating
  new files under the directory. Users are also blocked from creating any file
  or folder that matches any of the rules.

## Compatibility
Should be compatible up to Nextcloud 34.x.

## Installation
### From Package
1. Download a package from the Releases page of this GitHub page.
2. Expand the package using `tar -xvzf nextcloud-files-excludedirs-vVERSION.tar.gz`.
3. Move the expanded `files_excludedirs` folder into your `nextcloud/apps` 
   directory, such that `composer.json` can be found as 
   `nextcloud/apps/files_excludedirs/composer.json`.
4. Enable the "Exclude directories" plugin under the "Apps" admin page
   (`/settings/apps`).

### From Source
1. Ensure that your system has Git and Composer installed.
2. Clone this repository into a new subdirectory under your `nextcloud/apps`
   directory, and name the folder `files_excludedirs`.
3. In the `files_excludedirs` subdirectory, run `composer install` to install
   required dependencies.
4. Ensure the `files_excludedirs` subdirectory and all its subdirectories are
   owned by a user that the Nextcloud user can read (e.g., if NC user is in the
   `www-data` group, the folder should be owned by `root:www-data` with dir
   permissions set to `0755` and files set to `0644`).

## Configuring Excluded Path Patterns
The default rule is to exclude paths that match `[".snapshot"]` (i.e., to
exclude Btrfs snapshot folders from being indexed). 

There is no settings page (yet) for this plugin, but the default rule can be
overridden using the Nextcloud CLI using a command like the following:

```
occ config:app:set files_excludedirs exclude \
    --value '[".snapshot","anotherfolder", "pattern/*/match"]'
```

This will exclude:
- All folders named `.snapshot`.
- All folders named `anotherfolder`.
- Folders that match the glob pattern `pattern/*/match`, including 
  `pattern/xyz/match` and `pattern/abc/match`.

### Viewing Active Patterns
To view your currently configured exclusion patterns at any time, run this command:

```bash
occ config:app:get files_excludedirs exclude
```

## Removing Unwanted Existing Files from Nextcloud File Cache
When you add new directories to your exclusion rules, Nextcloud does not automatically delete their existing indexed records from its database file cache. 

To remove these files from the database cache, you can choose one of the following methods:

### Method A: Use the Native Cache Cleanup Command (Recommended)
This app provides a custom CLI command to instantly purge all currently configured excluded directories and files from your Nextcloud database file cache:

```bash
occ files_excludedirs:clean-cache
```

This command parses your active exclusion patterns, searches the database using Doctrine DBAL, and safely deletes matching records in one go.

#### Previewing Affected Files (Dry-Run Mode)
To see exactly what files and folders match your exclusion patterns and would be deleted without actually making any database changes, you can use the `--dry-run` (or `-d`) option:

```bash
occ files_excludedirs:clean-cache --dry-run
```

This performs a safe, read-only search of your database and displays a preview list (limited to the first 50 results to prevent terminal flooding) along with a summary of matching entries.

### Method B: Scan the Parent Folder
Alternatively, you can force Nextcloud to perform a directory differential sync. By scanning the **parent** folder of your excluded directory, Nextcloud will notice the excluded folder is hidden (blocked by our filesystem wrapper) and recursively delete it and its children from the cache database:

```bash
occ files:scan --path="username/files/path_to_parent_folder"
```

## Credits
Initially authored by Roeland Jago Douma, with contributions from:
- Robin Appelman
- King
- Alan J. Pippin
- Guy Elsmore-Paddock
- Marc Palaus