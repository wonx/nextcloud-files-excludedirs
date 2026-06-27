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
Tested and compatible with Nextcloud versions up to 34.x.

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
You can configure your exclusion rules either directly through the Nextcloud Web Admin interface or via the Command Line Interface (CLI).

### 1. Web Administration Interface (Recommended)
This app registers a dedicated **Exclude Directories** menu on the left sidebar of your Nextcloud Administration Settings. 
- **Configure:** Edit your list of patterns directly inside the text area (one pattern per line).
- **Preview:** Click **Preview Changes** to run a safe, read-only dry-run scan of your database cache.
- **Save:** Click **Save Patterns** to write your rules to Nextcloud.
- **Cleanup:** Click **Run Database Cleanup** to instantly delete matching excluded paths from the SQL file cache database.

### 2. Command Line Interface (CLI)
The default rule is to exclude paths that match `[".snapshot"]` (i.e., to exclude Btrfs snapshot folders from being indexed). 

The default rule can be overridden using the Nextcloud CLI with a command like the following:

```bash
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

## Important Limitations on Mount Points and Shared Folders
Exclusion patterns are matched against the database `path` column. In Nextcloud, file paths in the database are stored **relative to the root of each storage mount** (including External Storages and Shared Folders).

- **Subdirectories inside a storage** (e.g., `Documents/Temp` inside your primary storage) have database paths starting with `Documents/Temp` and can be excluded normally.
- **Root-level Mounts / Shared Folders** (e.g., if a folder like `Shared/Holiday` is mounted directly as an incoming share or an external storage) are treated as their own independent storage roots. The files inside them are cached starting at their own root (e.g., `path = "photo.jpg"`). 

Because the parent mount path (`Shared/Holiday`) is not part of the database file path, you cannot exclude files inside direct storage roots using a path prefix that includes the mount point name.


## Credits
Initially authored by Roeland Jago Douma, with contributions from:
- Robin Appelman
- King
- Alan J. Pippin
- Guy Elsmore-Paddock
- Marc Palaus

## Project History
This repository is a community-maintained continuation of the `files_excludedirs` project. It traces its codebase and history through the following repositories:

1. **Original Version:** [nextcloud/files_excludedirs](https://github.com/nextcloud/files_excludedirs)
2. **First Fork:** [apippin/files_excludedirs](https://github.com/apippin/files_excludedirs)
3. **Second Fork:** [Inveniem/nextcloud-files-excludedirs](https://github.com/Inveniem/nextcloud-files-excludedirs)
4. **Current Version:** [wonx/nextcloud-files-excludedirs](https://github.com/wonx/nextcloud-files-excludedirs) (Active, updated for Nextcloud 32/34, and enhanced with a native database cache cleanup utility)